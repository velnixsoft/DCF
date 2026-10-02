<?php

if (!class_exists('StudentBadgeEngine')) {
    class StudentBadgeEngine
    {
        /** @var PDO */
        private $pdo;

        /** @var int|null */
        private $actorUserId;

        public function __construct(PDO $pdo, ?int $actorUserId = null)
        {
            $this->pdo = $pdo;
            $this->actorUserId = $actorUserId !== null ? (int)$actorUserId : null;
        }

        public function evaluateBadges(int $studentId, array $options = []): array
        {
            return $this->runInTransaction(function () use ($studentId, $options) {
                $student = $this->lockStudent($studentId);
                $metrics = $this->collectMetrics($student);
                $rules = $this->getActiveBadgeRules();
                $awarded = [];
                $eligible = [];

                foreach ($rules as $rule) {
                    if (!$this->metricsMeetRule($metrics, $rule)) {
                        continue;
                    }

                    $eligible[] = $rule['badge_code'];

                    if (!empty($options['apply'])) {
                        $result = $this->assignBadge($studentId, (string)$rule['badge_code'], [
                            'assignment_type' => 'auto',
                            'rule_id' => (int)$rule['id'],
                            'notes' => 'Automatically awarded by badge criteria.',
                        ]);

                        if (!empty($result['success']) && empty($result['duplicate'])) {
                            $awarded[] = $result;
                        }
                    }
                }

                return [
                    'success' => true,
                    'student_id' => $studentId,
                    'metrics' => $metrics,
                    'eligible_badges' => $eligible,
                    'awarded_badges' => $awarded,
                    'message' => 'Badge eligibility evaluated successfully.',
                ];
            });
        }

        public function assignBadge(int $studentId, string $badgeCode, array $context = []): array
        {
            return $this->runInTransaction(function () use ($studentId, $badgeCode, $context) {
                $student = $this->lockStudent($studentId);
                $badge = $this->getBadgeByCode($badgeCode);
                $badgeId = (int)$badge['id'];

                if ($this->studentHasBadge($studentId, $badgeId)) {
                    return [
                        'success' => true,
                        'duplicate' => true,
                        'student_id' => $studentId,
                        'badge_id' => $badgeId,
                        'badge_code' => $badgeCode,
                        'message' => 'Student already has this badge.',
                    ];
                }

                $assignmentType = (string)($context['assignment_type'] ?? 'auto');
                $allowedTypes = ['auto', 'manual', 'override'];
                if (!in_array($assignmentType, $allowedTypes, true)) {
                    $assignmentType = 'auto';
                }

                $notes = $this->normalizeNullableString($context['notes'] ?? null);
                $ruleId = isset($context['rule_id']) ? (int)$context['rule_id'] : null;

                $columns = ['student_id', 'badge_id', 'notes', 'awarded_by_user_id', 'awarded_at'];
                $values = [$studentId, $badgeId, $notes, $this->actorUserId, date('Y-m-d H:i:s')];

                if ($this->columnExists('sa_student_badges', 'assignment_type')) {
                    $columns[] = 'assignment_type';
                    $values[] = $assignmentType;
                }

                if ($this->columnExists('sa_student_badges', 'badge_rule_id')) {
                    $columns[] = 'badge_rule_id';
                    $values[] = $ruleId;
                }

                if ($this->columnExists('sa_student_badges', 'override_reason')) {
                    $columns[] = 'override_reason';
                    $values[] = $this->normalizeNullableString($context['override_reason'] ?? null);
                }

                $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                $sql = "INSERT INTO sa_student_badges (" . implode(', ', $columns) . ") VALUES ($placeholders)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($values);

                $this->logActivity(
                    $studentId,
                    $assignmentType === 'auto' ? 'badge_auto_awarded' : 'badge_awarded',
                    $assignmentType === 'auto' ? 'Badge Auto Awarded' : 'Badge Awarded',
                    'Awarded badge: ' . $badge['badge_name'] . ($notes ? '. Notes: ' . $notes : '')
                );

                require_once __DIR__ . '/student_notify_helper.php';
                student_send_notification($this->pdo, $studentId, 'badge_earned', [
                    'student_name' => (string)($student['full_name'] ?? 'Student'),
                    'badge_name' => (string)($badge['badge_name'] ?? $badgeCode),
                    'badge_description' => (string)($badge['description'] ?? ''),
                    'points_earned' => (int)($badge['points_reward'] ?? 0),
                ]);

                return [
                    'success' => true,
                    'duplicate' => false,
                    'student_id' => $studentId,
                    'badge_id' => $badgeId,
                    'badge_code' => $badgeCode,
                    'assignment_type' => $assignmentType,
                    'message' => 'Badge assigned successfully.',
                ];
            });
        }

        public function manualAwardBadge(int $studentId, int $badgeId, array $context = []): array
        {
            return $this->runInTransaction(function () use ($studentId, $badgeId, $context) {
                $badge = $this->getBadgeById($badgeId);

                return $this->assignBadge($studentId, (string)$badge['badge_code'], [
                    'assignment_type' => 'manual',
                    'notes' => $context['notes'] ?? null,
                    'override_reason' => $context['override_reason'] ?? 'Manual admin award.',
                ]);
            });
        }

        public function overrideBadge(int $studentId, int $badgeId, bool $award, string $reason = ''): array
        {
            if ($award) {
                return $this->manualAwardBadge($studentId, $badgeId, [
                    'notes' => $reason,
                    'override_reason' => $reason ?: 'Manual override award.',
                ]);
            }

            return $this->runInTransaction(function () use ($studentId, $badgeId, $reason) {
                $this->lockStudent($studentId);
                $badge = $this->getBadgeById($badgeId);

                $stmt = $this->pdo->prepare("DELETE FROM sa_student_badges WHERE student_id = ? AND badge_id = ?");
                $stmt->execute([$studentId, $badgeId]);

                $this->logActivity(
                    $studentId,
                    'badge_override_removed',
                    'Badge Removed',
                    'Removed badge: ' . $badge['badge_name'] . ($reason ? '. Reason: ' . $reason : '')
                );

                return [
                    'success' => true,
                    'duplicate' => false,
                    'student_id' => $studentId,
                    'badge_id' => $badgeId,
                    'badge_code' => $badge['badge_code'],
                    'message' => 'Badge removed by manual override.',
                ];
            });
        }

        public function evaluateActiveStudents(int $limit = 500): array
        {
            $stmt = $this->pdo->prepare("
                SELECT id
                FROM sa_students
                WHERE status = 'Active'
                ORDER BY id ASC
                LIMIT ?
            ");
            $stmt->bindValue(1, max(1, $limit), PDO::PARAM_INT);
            $stmt->execute();

            $summary = [
                'success' => true,
                'evaluated' => 0,
                'awarded' => 0,
                'failed' => 0,
                'results' => [],
            ];

            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $studentId) {
                $result = $this->evaluateBadges((int)$studentId, ['apply' => true]);
                $summary['evaluated']++;

                if (empty($result['success'])) {
                    $summary['failed']++;
                } else {
                    $summary['awarded'] += count($result['awarded_badges'] ?? []);
                }

                $summary['results'][] = $result;
            }

            return $summary;
        }

        private function collectMetrics(array $student): array
        {
            $studentId = (int)$student['id'];

            return [
                'points' => (int)($student['total_points'] ?? 0),
                'attendance_days' => $this->countAttendanceDays($studentId),
                'referrals' => $this->countReferrals($studentId),
                'vendor_onboardings' => $this->countVendorOnboardings($studentId),
                'campaign_approvals' => $this->countApprovedCampaigns($studentId),
                'campaign_points' => $this->sumCampaignPoints($studentId),
                'donation_count' => $this->countDonations($studentId),
                'donation_amount' => $this->sumDonations($studentId),
                'city_rank' => $this->calculateRank($student, 'city_name'),
                'state_rank' => $this->calculateRank($student, 'state_name'),
                'level_rank' => $this->levelRank((string)($student['level_name'] ?? 'Volunteer')),
                'health_campaigns' => $this->countKeywordCampaigns($studentId, ['health', 'medical', 'blood', 'nutrition', 'hygiene']),
            ];
        }

        private function getActiveBadgeRules(): array
        {
            if (!$this->tableExists('badge_rules')) {
                return [];
            }

            $stmt = $this->pdo->query("
                SELECT *
                FROM badge_rules
                WHERE is_active = 1
                  AND (effective_from IS NULL OR effective_from <= NOW())
                  AND (effective_to IS NULL OR effective_to >= NOW())
                ORDER BY badge_rank ASC
            ");

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        private function metricsMeetRule(array $metrics, array $rule): bool
        {
            $cityRankLimit = (int)$rule['max_city_rank'];
            $stateRankLimit = (int)$rule['max_state_rank'];

            return $metrics['points'] >= (int)$rule['min_points']
                && $metrics['attendance_days'] >= (int)$rule['min_attendance_days']
                && $metrics['referrals'] >= (int)$rule['min_referrals']
                && $metrics['vendor_onboardings'] >= (int)$rule['min_vendor_onboardings']
                && $metrics['campaign_approvals'] >= (int)$rule['min_campaign_approvals']
                && $metrics['campaign_points'] >= (int)$rule['min_campaign_points']
                && $metrics['donation_count'] >= (int)$rule['min_donation_count']
                && $metrics['donation_amount'] >= (float)$rule['min_donation_amount']
                && $metrics['level_rank'] >= (int)$rule['min_level_rank']
                && $metrics['health_campaigns'] >= (int)$rule['min_health_campaigns']
                && ($cityRankLimit <= 0 || $metrics['city_rank'] <= $cityRankLimit)
                && ($stateRankLimit <= 0 || $metrics['state_rank'] <= $stateRankLimit);
        }

        private function getBadgeByCode(string $badgeCode): array
        {
            $badgeCode = trim($badgeCode);
            $badge = null;

            if ($this->columnExists('sa_badges', 'badge_code')) {
                $stmt = $this->pdo->prepare("SELECT * FROM sa_badges WHERE badge_code = ? AND is_active = 1 LIMIT 1");
                $stmt->execute([$badgeCode]);
                $badge = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if (!$badge) {
                $badgeName = $this->badgeNameFromCode($badgeCode);
                $stmt = $this->pdo->prepare("SELECT * FROM sa_badges WHERE badge_name = ? AND is_active = 1 LIMIT 1");
                $stmt->execute([$badgeName]);
                $badge = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if (!$badge) {
                throw new RuntimeException('Active badge not found: ' . $badgeCode);
            }

            if (empty($badge['badge_code'])) {
                $badge['badge_code'] = $badgeCode;
            }

            return $badge;
        }

        private function getBadgeById(int $badgeId): array
        {
            $stmt = $this->pdo->prepare("SELECT * FROM sa_badges WHERE id = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$badgeId]);
            $badge = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$badge) {
                throw new RuntimeException('Active badge not found.');
            }

            if (empty($badge['badge_code'])) {
                $badge['badge_code'] = $this->badgeCodeFromName((string)$badge['badge_name']);
            }

            return $badge;
        }

        private function studentHasBadge(int $studentId, int $badgeId): bool
        {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM sa_student_badges WHERE student_id = ? AND badge_id = ?");
            $stmt->execute([$studentId, $badgeId]);

            return (int)$stmt->fetchColumn() > 0;
        }

        private function countAttendanceDays(int $studentId): int
        {
            if (!$this->tableExists('sa_attendance_logs')) {
                return 0;
            }

            $stmt = $this->pdo->prepare("
                SELECT COUNT(DISTINCT attendance_date)
                FROM sa_attendance_logs
                WHERE student_id = ?
                  AND status = 'Present'
            ");
            $stmt->execute([$studentId]);

            return (int)$stmt->fetchColumn();
        }

        private function countReferrals(int $studentId): int
        {
            $legacy = $this->countLegacyActiveReferrals($studentId);

            if (!$this->tableExists('sa_referrals')) {
                return $legacy;
            }

            $stmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM sa_referrals
                WHERE referrer_student_id = ?
                  AND verification_status = 'verified'
                  AND deleted_at IS NULL
            ");
            $stmt->execute([$studentId]);

            return max($legacy, (int)$stmt->fetchColumn());
        }

        private function countLegacyActiveReferrals(int $studentId): int
        {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM sa_students
                WHERE referred_by_student_id = ?
                  AND status = 'Active'
            ");
            $stmt->execute([$studentId]);

            return (int)$stmt->fetchColumn();
        }

        private function countVendorOnboardings(int $studentId): int
        {
            if (!$this->tableExists('sa_vendor_leads')) {
                return 0;
            }

            $stmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM sa_vendor_leads
                WHERE student_id = ?
                  AND deleted_at IS NULL
                  AND verification_status = 'verified'
                  AND (
                    lead_type IN ('vendor_onboarding', 'premium_vendor_onboarding')
                    OR lead_status IN ('verified', 'converted')
                  )
            ");
            $stmt->execute([$studentId]);

            return (int)$stmt->fetchColumn();
        }

        private function countApprovedCampaigns(int $studentId): int
        {
            if (!$this->tableExists('sa_task_submissions') || !$this->tableExists('sa_tasks')) {
                return 0;
            }

            $stmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM sa_task_submissions s
                JOIN sa_tasks t ON t.id = s.task_id
                WHERE s.student_id = ?
                  AND s.status = 'Approved'
                  AND (NULLIF(t.campaign_name, '') IS NOT NULL OR LOWER(t.task_type) IN ('campaign', 'awareness', 'event'))
            ");
            $stmt->execute([$studentId]);

            return (int)$stmt->fetchColumn();
        }

        private function sumCampaignPoints(int $studentId): int
        {
            if (!$this->tableExists('sa_task_submissions') || !$this->tableExists('sa_tasks')) {
                return 0;
            }

            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(t.points_reward), 0)
                FROM sa_task_submissions s
                JOIN sa_tasks t ON t.id = s.task_id
                WHERE s.student_id = ?
                  AND s.status = 'Approved'
                  AND (NULLIF(t.campaign_name, '') IS NOT NULL OR LOWER(t.task_type) IN ('campaign', 'awareness', 'event'))
            ");
            $stmt->execute([$studentId]);

            return (int)$stmt->fetchColumn();
        }

        private function countKeywordCampaigns(int $studentId, array $keywords): int
        {
            if (!$this->tableExists('sa_task_submissions') || !$this->tableExists('sa_tasks')) {
                return 0;
            }

            $conditions = [];
            $params = [$studentId];
            foreach ($keywords as $keyword) {
                $conditions[] = "(LOWER(t.title) LIKE ? OR LOWER(t.description) LIKE ? OR LOWER(t.campaign_name) LIKE ?)";
                $like = '%' . strtolower($keyword) . '%';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }

            $stmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM sa_task_submissions s
                JOIN sa_tasks t ON t.id = s.task_id
                WHERE s.student_id = ?
                  AND s.status = 'Approved'
                  AND (" . implode(' OR ', $conditions) . ")
            ");
            $stmt->execute($params);

            return (int)$stmt->fetchColumn();
        }

        private function countDonations(int $studentId): int
        {
            if (!$this->tableExists('donations')) {
                return 0;
            }

            $stmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM donations
                WHERE sa_student_id = ?
                  AND payment_status = 'Success'
            ");
            $stmt->execute([$studentId]);

            return (int)$stmt->fetchColumn();
        }

        private function sumDonations(int $studentId): float
        {
            if (!$this->tableExists('donations')) {
                return 0.00;
            }

            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(amount), 0)
                FROM donations
                WHERE sa_student_id = ?
                  AND payment_status = 'Success'
            ");
            $stmt->execute([$studentId]);

            return (float)$stmt->fetchColumn();
        }

        private function calculateRank(array $student, string $scopeColumn): int
        {
            if (empty($student[$scopeColumn])) {
                return 999999;
            }

            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) + 1
                FROM sa_students
                WHERE total_points > ?
                  AND status = 'Active'
                  AND $scopeColumn = ?
            ");
            $stmt->execute([(int)($student['total_points'] ?? 0), $student[$scopeColumn]]);

            return (int)$stmt->fetchColumn();
        }

        private function levelRank(string $levelName): int
        {
            $levels = [
                'Volunteer' => 0,
                'Community Intern' => 1,
                'Senior Intern' => 2,
                'Campus Ambassador' => 3,
                'Team Leader' => 4,
                'City Coordinator' => 5,
                'State Coordinator' => 6,
                'State Leadership' => 6,
            ];

            return $levels[$levelName] ?? 0;
        }

        private function badgeNameFromCode(string $badgeCode): string
        {
            return ucwords(strtolower(str_replace('_', ' ', $badgeCode)));
        }

        private function badgeCodeFromName(string $badgeName): string
        {
            return strtoupper(str_replace(' ', '_', trim($badgeName)));
        }

        private function lockStudent(int $studentId): array
        {
            $stmt = $this->pdo->prepare("SELECT * FROM sa_students WHERE id = ? LIMIT 1 FOR UPDATE");
            $stmt->execute([$studentId]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                throw new RuntimeException('Student record not found.');
            }

            return $student;
        }

        private function logActivity(int $studentId, string $activityType, string $title, string $description): void
        {
            $stmt = $this->pdo->prepare("
                INSERT INTO sa_activity_logs (
                    student_id, activity_type, title, description, points, reference_type, created_at
                ) VALUES (?, ?, ?, ?, 0, 'badge_rules', NOW())
            ");
            $stmt->execute([$studentId, $activityType, $title, $description]);
        }

        private function tableExists(string $tableName): bool
        {
            static $cache = [];

            if (array_key_exists($tableName, $cache)) {
                return $cache[$tableName];
            }

            $stmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
            ");
            $stmt->execute([$tableName]);
            $cache[$tableName] = (int)$stmt->fetchColumn() > 0;

            return $cache[$tableName];
        }

        private function columnExists(string $tableName, string $columnName): bool
        {
            static $cache = [];
            $key = $tableName . '.' . $columnName;

            if (array_key_exists($key, $cache)) {
                return $cache[$key];
            }

            $stmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = ?
            ");
            $stmt->execute([$tableName, $columnName]);
            $cache[$key] = (int)$stmt->fetchColumn() > 0;

            return $cache[$key];
        }

        private function normalizeNullableString($value): ?string
        {
            if ($value === null) {
                return null;
            }

            $value = trim((string)$value);
            return $value === '' ? null : $value;
        }

        private function runInTransaction(callable $callback): array
        {
            $ownsTransaction = !$this->pdo->inTransaction();

            try {
                if ($ownsTransaction) {
                    $this->pdo->beginTransaction();
                }

                $result = $callback();

                if ($ownsTransaction) {
                    $this->pdo->commit();
                }

                return $result;
            } catch (Throwable $e) {
                if ($ownsTransaction && $this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                return [
                    'success' => false,
                    'duplicate' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }
    }
}
