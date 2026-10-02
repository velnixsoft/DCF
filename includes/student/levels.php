<?php

if (!class_exists('StudentLevelEngine')) {
    class StudentLevelEngine
    {
        /** @var PDO */
        private $pdo;

        /** @var int|null */
        private $actorUserId;

        public const BASE_LEVEL = 'Student Ambassador';

        public const LEVELS = [
            'Community Intern',
            'Senior Intern',
            'Campus Ambassador',
            'Team Leader',
            'City Coordinator',
            'State Coordinator',
        ];

        public function __construct(PDO $pdo, ?int $actorUserId = null)
        {
            $this->pdo = $pdo;
            $this->actorUserId = $actorUserId !== null ? (int)$actorUserId : null;
        }

        public function evaluatePromotion(int $studentId, array $options = []): array
        {
            return $this->runInTransaction(function () use ($studentId, $options) {
                $student = $this->lockStudent($studentId);
                $currentLevel = $this->normalizeLevel((string)($student['level_name'] ?? self::BASE_LEVEL));
                $metrics = $this->collectMetrics($student);
                $rules = $this->getPromotionRules();

                if (!$rules) {
                    throw new RuntimeException('No active promotion rules found.');
                }

                $eligibleRule = null;
                foreach ($rules as $rule) {
                    if ($this->metricsMeetRule($metrics, $rule)) {
                        $eligibleRule = $rule;
                    }
                }

                $targetLevel = $eligibleRule ? (string)$eligibleRule['level_name'] : self::BASE_LEVEL;
                $direction = $this->compareLevels($targetLevel, $currentLevel);

                $result = [
                    'success' => true,
                    'student_id' => $studentId,
                    'current_level' => $currentLevel,
                    'target_level' => $targetLevel,
                    'direction' => $direction > 0 ? 'promote' : ($direction < 0 ? 'demote' : 'retain'),
                    'eligible' => $direction > 0,
                    'metrics' => $metrics,
                    'matched_rule' => $eligibleRule,
                    'message' => 'Level evaluated successfully.',
                ];

                if (!empty($options['apply'])) {
                    if ($direction > 0) {
                        return $this->promoteStudent($studentId, $targetLevel, [
                            'reason' => 'Promotion rules matched.',
                            'metrics' => $metrics,
                            'matched_rule' => $eligibleRule,
                        ]);
                    }

                    if ($direction < 0 && !empty($options['allow_demotion'])) {
                        return $this->demoteStudent($studentId, $targetLevel, [
                            'reason' => 'Promotion rules no longer matched.',
                            'metrics' => $metrics,
                            'matched_rule' => $eligibleRule,
                        ]);
                    }
                }

                return $result;
            });
        }

        public function promoteStudent(int $studentId, string $targetLevel, array $context = []): array
        {
            return $this->runInTransaction(function () use ($studentId, $targetLevel, $context) {
                return $this->changeStudentLevel($studentId, $targetLevel, 'promotion', $context);
            });
        }

        public function demoteStudent(int $studentId, string $targetLevel, array $context = []): array
        {
            return $this->runInTransaction(function () use ($studentId, $targetLevel, $context) {
                return $this->changeStudentLevel($studentId, $targetLevel, 'demotion', $context);
            });
        }

        public function evaluateActiveStudents(bool $allowDemotion = false, int $limit = 500): array
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
                'promoted' => 0,
                'demoted' => 0,
                'retained' => 0,
                'failed' => 0,
                'results' => [],
            ];

            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $studentId) {
                $result = $this->evaluatePromotion((int)$studentId, [
                    'apply' => true,
                    'allow_demotion' => $allowDemotion,
                ]);

                $summary['evaluated']++;
                if (empty($result['success'])) {
                    $summary['failed']++;
                } elseif (($result['direction'] ?? '') === 'promote') {
                    $summary['promoted']++;
                } elseif (($result['direction'] ?? '') === 'demote') {
                    $summary['demoted']++;
                } else {
                    $summary['retained']++;
                }

                $summary['results'][] = $result;
            }

            return $summary;
        }

        private function changeStudentLevel(int $studentId, string $targetLevel, string $activityType, array $context): array
        {
            $targetLevel = $this->normalizeLevel($targetLevel);
            if (!$this->isKnownLevel($targetLevel)) {
                throw new RuntimeException('Unknown target level: ' . $targetLevel);
            }

            $student = $this->lockStudent($studentId);
            $currentLevel = $this->normalizeLevel((string)($student['level_name'] ?? self::BASE_LEVEL));
            $direction = $this->compareLevels($targetLevel, $currentLevel);

            if ($direction === 0) {
                return [
                    'success' => true,
                    'student_id' => $studentId,
                    'current_level' => $currentLevel,
                    'target_level' => $targetLevel,
                    'direction' => 'retain',
                    'message' => 'Student already has this level.',
                ];
            }

            if ($activityType === 'promotion' && $direction < 0) {
                throw new RuntimeException('Promotion target is lower than current level.');
            }

            if ($activityType === 'demotion' && $direction > 0) {
                throw new RuntimeException('Demotion target is higher than current level.');
            }

            $stmt = $this->pdo->prepare("
                UPDATE sa_students
                SET level_name = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$targetLevel, $studentId]);

            $title = $activityType === 'promotion' ? 'Ambassador Promotion' : 'Ambassador Demotion';
            $reason = trim((string)($context['reason'] ?? 'Level rules evaluated.'));
            $description = sprintf('%s from %s to %s. %s', $title, $currentLevel, $targetLevel, $reason);
            $this->logActivity($studentId, $activityType, $title, $description);

            if ($activityType === 'promotion') {
                require_once __DIR__ . '/student_notify_helper.php';
                student_send_notification($this->pdo, $studentId, 'promotion_achieved', [
                    'student_name' => (string)($student['full_name'] ?? 'Student'),
                    'old_level' => $currentLevel,
                    'new_level' => $targetLevel,
                    'benefits' => 'New level unlocked. Check your dashboard for updated targets.',
                ]);

                if (file_exists(__DIR__ . '/certificates.php')) {
                    require_once __DIR__ . '/certificates.php';
                    if (class_exists('StudentCertificateService')) {
                        $certService = new StudentCertificateService($this->pdo);
                        $certService->autoIssueOnPromotion($studentId, $targetLevel, (int)($this->actorUserId ?? 0));
                    }
                }
            }

            return [
                'success' => true,
                'student_id' => $studentId,
                'current_level' => $currentLevel,
                'target_level' => $targetLevel,
                'direction' => $activityType === 'promotion' ? 'promote' : 'demote',
                'metrics' => $context['metrics'] ?? null,
                'matched_rule' => $context['matched_rule'] ?? null,
                'message' => $title . ' applied successfully.',
            ];
        }

        private function collectMetrics(array $student): array
        {
            $studentId = (int)$student['id'];

            return [
                'points' => (int)($student['total_points'] ?? 0),
                'attendance_days' => $this->countAttendanceDays($studentId),
                'attendance_rate' => $this->calculateAttendanceRate($student),
                'referrals' => $this->countVerifiedReferrals($studentId),
                'vendor_onboardings' => $this->countVendorOnboardings($studentId),
                'campaign_approvals' => $this->countApprovedCampaigns($studentId),
                'campaign_points' => $this->sumCampaignPoints($studentId),
            ];
        }

        private function getPromotionRules(): array
        {
            if (!$this->tableExists('promotion_rules')) {
                return [];
            }

            $stmt = $this->pdo->query("
                SELECT *
                FROM promotion_rules
                WHERE is_active = 1
                  AND (effective_from IS NULL OR effective_from <= NOW())
                  AND (effective_to IS NULL OR effective_to >= NOW())
                ORDER BY level_rank ASC
            ");

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        private function metricsMeetRule(array $metrics, array $rule): bool
        {
            return $metrics['points'] >= (int)$rule['min_points'];
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

        private function calculateAttendanceRate(array $student): float
        {
            if (!$this->tableExists('sa_attendance_logs')) {
                return 0.00;
            }

            $createdAt = !empty($student['created_at']) ? strtotime((string)$student['created_at']) : time();
            $daysSinceStart = max(1, (int)floor((time() - $createdAt) / 86400) + 1);
            $attendanceDays = $this->countAttendanceDays((int)$student['id']);

            return round(min(100, ($attendanceDays / $daysSinceStart) * 100), 2);
        }

        private function countVerifiedReferrals(int $studentId): int
        {
            $legacyCount = $this->countLegacyActiveReferrals($studentId);

            if ($this->tableExists('sa_referrals')) {
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(*)
                    FROM sa_referrals
                    WHERE referrer_student_id = ?
                      AND verification_status = 'verified'
                      AND deleted_at IS NULL
                ");
                $stmt->execute([$studentId]);
                return max($legacyCount, (int)$stmt->fetchColumn());
            }

            return $legacyCount;
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
                LEFT JOIN sa_tasks t ON t.id = s.task_id
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
                LEFT JOIN sa_tasks t ON t.id = s.task_id
                WHERE s.student_id = ?
                  AND s.status = 'Approved'
                  AND (NULLIF(t.campaign_name, '') IS NOT NULL OR LOWER(t.task_type) IN ('campaign', 'awareness', 'event'))
            ");
            $stmt->execute([$studentId]);

            return (int)$stmt->fetchColumn();
        }

        private function normalizeLevel(string $level): string
        {
            $level = trim($level);
            $aliases = [
                '' => self::BASE_LEVEL,
                'State Leadership' => 'State Coordinator',
                'Volunteer' => self::BASE_LEVEL,
            ];

            return $aliases[$level] ?? $level;
        }

        private function isKnownLevel(string $level): bool
        {
            return $level === self::BASE_LEVEL || in_array($level, self::LEVELS, true);
        }

        private function compareLevels(string $left, string $right): int
        {
            return $this->levelRank($left) <=> $this->levelRank($right);
        }

        private function levelRank(string $level): int
        {
            if ($level === self::BASE_LEVEL) {
                return 0;
            }

            $index = array_search($level, self::LEVELS, true);
            return $index === false ? 0 : $index + 1;
        }

        private function lockStudent(int $studentId): array
        {
            $stmt = $this->pdo->prepare("
                SELECT *
                FROM sa_students
                WHERE id = ?
                LIMIT 1
                FOR UPDATE
            ");
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
                ) VALUES (?, ?, ?, ?, 0, 'promotion_rules', NOW())
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
                    'message' => $e->getMessage(),
                ];
            }
        }
    }
}
