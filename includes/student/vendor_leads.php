<?php

require_once __DIR__ . '/points.php';
require_once __DIR__ . '/levels.php';
require_once __DIR__ . '/badges.php';

if (!class_exists('StudentVendorLeadEngine')) {
    class StudentVendorLeadEngine
    {
        /** @var PDO */
        private $pdo;

        /** @var int|null */
        private $actorUserId;

        private const LEAD_TYPES = [
            'vendor_meeting' => [
                'label' => 'Vendor Meeting',
                'rule_code' => 'VENDOR_MEETING',
                'next_status' => 'meeting_done',
            ],
            'vendor_onboarding' => [
                'label' => 'Vendor Onboarding',
                'rule_code' => 'VENDOR_ONBOARDING',
                'next_status' => 'converted',
            ],
            'premium_vendor_onboarding' => [
                'label' => 'Premium Vendor Onboarding',
                'rule_code' => 'PREMIUM_VENDOR_ONBOARDING',
                'next_status' => 'converted',
            ],
            'sponsor_partnership' => [
                'label' => 'Sponsor Lead',
                'rule_code' => 'SPONSOR_BUSINESS_PARTNERSHIP_LEAD',
                'next_status' => 'converted',
            ],
            'college_partnership' => [
                'label' => 'College Partnership',
                'rule_code' => 'COLLEGE_PARTNERSHIP_LEAD',
                'next_status' => 'converted',
            ],
        ];

        public function __construct(PDO $pdo, ?int $actorUserId = null)
        {
            $this->pdo = $pdo;
            $this->actorUserId = $actorUserId !== null ? (int)$actorUserId : null;
        }

        public static function leadTypes(): array
        {
            return self::LEAD_TYPES;
        }

        public function submitLead(int $studentId, array $data): array
        {
            return $this->runInTransaction(function () use ($studentId, $data) {
                $student = $this->lockStudent($studentId);
                $leadType = $this->normalizeLeadType($data['lead_type'] ?? '');
                $businessName = $this->requireText($data['business_name'] ?? '', 'Business / organization name is required.');

                $contactName = $this->normalizeNullableString($data['contact_name'] ?? null);
                if ($contactName !== null) {
                    if (!preg_match("/^[a-zA-Z\s'\.\-]+$/", $contactName)) {
                        throw new RuntimeException('Contact name should only contain letters, spaces, hyphens, apostrophes, and dots.');
                    }
                }

                $contactPhone = $this->normalizeNullableString($data['contact_phone'] ?? null);
                if ($contactPhone !== null) {
                    $phoneClean = preg_replace('/[^0-9]/', '', $contactPhone);
                    if (strlen($phoneClean) !== 10 || !preg_match("/^[6-9][0-9]{9}$/", $phoneClean)) {
                        throw new RuntimeException('Contact phone must start with 6, 7, 8, or 9 and be exactly 10 digits.');
                    }
                    $contactPhone = $phoneClean;
                }

                $contactEmail = $this->normalizeNullableString($data['contact_email'] ?? null);

                $cityName = $this->normalizeNullableString($data['city_name'] ?? null);
                if ($cityName !== null) {
                    if (!preg_match("/^[a-zA-Z\s]+$/", $cityName)) {
                        throw new RuntimeException('City name should only contain letters and spaces.');
                    }
                }

                $stateName = $this->normalizeNullableString($data['state_name'] ?? null);
                if ($stateName !== null) {
                    if (!preg_match("/^[a-zA-Z\s]+$/", $stateName)) {
                        throw new RuntimeException('State name should only contain letters and spaces.');
                    }
                }

                $meetingDate = $this->normalizeDateTime($data['meeting_date'] ?? null);
                if ($meetingDate !== null) {
                    $mDateStr = substr($meetingDate, 0, 10);
                    $todayStr = date('Y-m-d');
                    if ($mDateStr < $todayStr) {
                        throw new RuntimeException('Meeting date cannot be in the past.');
                    }
                    if (preg_match('/^(\d{4,})/', $mDateStr, $mYear)) {
                        if (strlen($mYear[1]) > 4) {
                            throw new RuntimeException('Invalid meeting date year.');
                        }
                    }
                }

                if ($this->findDuplicateLead($studentId, $leadType, $businessName, $contactPhone, $contactEmail)) {
                    return [
                        'success' => false,
                        'duplicate' => true,
                        'student_id' => $studentId,
                        'lead_type' => $leadType,
                        'message' => 'A pending or verified lead already exists for this business and lead type.',
                    ];
                }

                $stmt = $this->pdo->prepare("
                    INSERT INTO sa_vendor_leads (
                        student_id, program_id, lead_type, lead_status, verification_status,
                        business_name, contact_name, contact_phone, contact_email, city_name, state_name,
                        is_premium, meeting_date, proof_path, notes, created_by_user_id, updated_by_user_id, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, 'new', 'pending',
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, NOW(), NOW()
                    )
                ");
                $stmt->execute([
                    $studentId,
                    isset($student['program_id']) && $student['program_id'] !== null ? (int)$student['program_id'] : null,
                    $leadType,
                    $businessName,
                    $this->normalizeNullableString($data['contact_name'] ?? null),
                    $contactPhone,
                    $contactEmail,
                    $this->normalizeNullableString($data['city_name'] ?? $student['city_name'] ?? null),
                    $this->normalizeNullableString($data['state_name'] ?? $student['state_name'] ?? null),
                    $leadType === 'premium_vendor_onboarding' ? 1 : 0,
                    $this->normalizeDateTime($data['meeting_date'] ?? null),
                    $this->normalizeNullableString($data['proof_path'] ?? null),
                    $this->normalizeNullableString($data['notes'] ?? null),
                    $this->actorUserId,
                    $this->actorUserId,
                ]);

                $leadId = (int)$this->pdo->lastInsertId();
                $this->logActivity(
                    $studentId,
                    'vendor_lead_submitted',
                    'Vendor Lead Submitted',
                    self::LEAD_TYPES[$leadType]['label'] . ' submitted: ' . $businessName,
                    $leadId
                );

                return [
                    'success' => true,
                    'lead_id' => $leadId,
                    'student_id' => $studentId,
                    'lead_type' => $leadType,
                    'message' => 'Lead submitted successfully and is pending verification.',
                ];
            });
        }

        public function verifyLead(int $leadId, array $context = []): array
        {
            return $this->runInTransaction(function () use ($leadId, $context) {
                $lead = $this->lockLead($leadId);

                if ($lead['verification_status'] === 'verified') {
                    return [
                        'success' => true,
                        'duplicate' => true,
                        'lead_id' => $leadId,
                        'message' => 'Lead is already verified.',
                    ];
                }

                if ($lead['verification_status'] === 'rejected') {
                    throw new RuntimeException('Rejected leads cannot be verified without resubmission.');
                }

                $leadType = $this->normalizeLeadType($lead['lead_type']);
                $nextStatus = self::LEAD_TYPES[$leadType]['next_status'];
                $conversionDate = in_array($leadType, ['vendor_onboarding', 'premium_vendor_onboarding', 'sponsor_partnership', 'college_partnership'], true)
                    ? date('Y-m-d H:i:s')
                    : null;
                $notes = $this->normalizeNullableString($context['verification_notes'] ?? null);

                $stmt = $this->pdo->prepare("
                    UPDATE sa_vendor_leads
                    SET lead_status = ?,
                        verification_status = 'verified',
                        verified_at = NOW(),
                        verified_by_user_id = ?,
                        conversion_date = COALESCE(?, conversion_date),
                        verification_notes = ?,
                        updated_by_user_id = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$nextStatus, $this->actorUserId, $conversionDate, $notes, $this->actorUserId, $leadId]);

                $pointsResult = $this->awardLeadPoints($lead, $leadType);
                if (empty($pointsResult['success'])) {
                    throw new RuntimeException('Point award failed: ' . ($pointsResult['message'] ?? 'Unknown error.'));
                }

                $levelEngine = new StudentLevelEngine($this->pdo, $this->actorUserId);
                $levelEngine->evaluatePromotion((int)$lead['student_id'], ['apply' => true]);

                $badgeEngine = new StudentBadgeEngine($this->pdo, $this->actorUserId);
                $badgeEngine->evaluateBadges((int)$lead['student_id'], ['apply' => true]);

                $this->logActivity(
                    (int)$lead['student_id'],
                    'vendor_lead_verified',
                    'Vendor Lead Verified',
                    self::LEAD_TYPES[$leadType]['label'] . ' verified: ' . $lead['business_name'],
                    $leadId,
                    (int)($pointsResult['points_delta'] ?? 0)
                );

                require_once __DIR__ . '/student_notify_helper.php';
                $studentStmt = $this->pdo->prepare("SELECT full_name FROM sa_students WHERE id = ? LIMIT 1");
                $studentStmt->execute([(int)$lead['student_id']]);
                $studentName = (string)($studentStmt->fetchColumn() ?: 'Student');
                student_send_notification($this->pdo, (int)$lead['student_id'], 'vendor_lead_verified', [
                    'student_name' => $studentName,
                    'business_name' => (string)$lead['business_name'],
                    'points_earned' => abs((int)($pointsResult['points_delta'] ?? 0)),
                ]);

                return [
                    'success' => true,
                    'duplicate' => false,
                    'lead_id' => $leadId,
                    'student_id' => (int)$lead['student_id'],
                    'lead_type' => $leadType,
                    'points' => (int)($pointsResult['points_delta'] ?? 0),
                    'message' => 'Lead verified and points awarded.',
                ];
            });
        }

        public function rejectLead(int $leadId, string $reason = ''): array
        {
            return $this->runInTransaction(function () use ($leadId, $reason) {
                $lead = $this->lockLead($leadId);

                if ($lead['verification_status'] === 'verified') {
                    throw new RuntimeException('Verified leads cannot be rejected.');
                }

                $reason = $this->normalizeNullableString($reason) ?: 'Lead did not pass verification.';
                $stmt = $this->pdo->prepare("
                    UPDATE sa_vendor_leads
                    SET lead_status = 'rejected',
                        verification_status = 'rejected',
                        verification_notes = ?,
                        verified_by_user_id = ?,
                        verified_at = NOW(),
                        updated_by_user_id = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$reason, $this->actorUserId, $this->actorUserId, $leadId]);

                $this->logActivity(
                    (int)$lead['student_id'],
                    'vendor_lead_rejected',
                    'Vendor Lead Rejected',
                    'Rejected lead: ' . $lead['business_name'] . '. Reason: ' . $reason,
                    $leadId
                );

                return [
                    'success' => true,
                    'lead_id' => $leadId,
                    'student_id' => (int)$lead['student_id'],
                    'message' => 'Lead rejected successfully.',
                ];
            });
        }

        private function awardLeadPoints(array $lead, string $leadType): array
        {
            $pointEngine = new StudentPointEngine($this->pdo, $this->actorUserId);

            return $pointEngine->awardPoints((int)$lead['student_id'], self::LEAD_TYPES[$leadType]['rule_code'], [
                'source_type' => 'vendor_lead',
                'source_id' => (int)$lead['id'],
                'reference_code' => 'VL-' . (int)$lead['id'],
                'description' => self::LEAD_TYPES[$leadType]['label'] . ': ' . $lead['business_name'],
                'idempotency_key' => 'vendor-lead:' . (int)$lead['id'] . ':' . self::LEAD_TYPES[$leadType]['rule_code'],
            ]);
        }

        private function normalizeLeadType($leadType): string
        {
            $leadType = trim((string)$leadType);
            if ($leadType === 'sponsor_lead') {
                $leadType = 'sponsor_partnership';
            }

            if (!isset(self::LEAD_TYPES[$leadType])) {
                throw new RuntimeException('Invalid lead type.');
            }

            return $leadType;
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

        private function lockLead(int $leadId): array
        {
            $stmt = $this->pdo->prepare("SELECT * FROM sa_vendor_leads WHERE id = ? LIMIT 1 FOR UPDATE");
            $stmt->execute([$leadId]);
            $lead = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lead) {
                throw new RuntimeException('Vendor lead not found.');
            }

            return $lead;
        }

        private function findDuplicateLead(int $studentId, string $leadType, string $businessName, ?string $contactPhone, ?string $contactEmail): bool
        {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM sa_vendor_leads
                WHERE student_id = ?
                  AND lead_type = ?
                  AND verification_status IN ('pending', 'verified')
                  AND deleted_at IS NULL
                  AND (
                    LOWER(business_name) = LOWER(?)
                    OR (? IS NOT NULL AND contact_phone = ?)
                    OR (? IS NOT NULL AND contact_email = ?)
                  )
            ");
            $stmt->execute([
                $studentId,
                $leadType,
                $businessName,
                $contactPhone,
                $contactPhone,
                $contactEmail,
                $contactEmail,
            ]);

            return (int)$stmt->fetchColumn() > 0;
        }

        private function logActivity(int $studentId, string $activityType, string $title, string $description, ?int $leadId = null, int $points = 0): void
        {
            $stmt = $this->pdo->prepare("
                INSERT INTO sa_activity_logs (
                    student_id, activity_type, title, description, points, reference_id, reference_type, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'sa_vendor_leads', NOW())
            ");
            $stmt->execute([$studentId, $activityType, $title, $description, $points, $leadId]);
        }

        private function requireText($value, string $message): string
        {
            $value = trim((string)$value);
            if ($value === '') {
                throw new RuntimeException($message);
            }

            return $value;
        }

        private function normalizeNullableString($value): ?string
        {
            if ($value === null) {
                return null;
            }

            $value = trim((string)$value);
            return $value === '' ? null : $value;
        }

        private function normalizeDateTime($value): ?string
        {
            $value = $this->normalizeNullableString($value);
            if ($value === null) {
                return null;
            }

            $timestamp = strtotime($value);
            return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
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
