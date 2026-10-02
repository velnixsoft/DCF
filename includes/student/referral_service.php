<?php

if (!class_exists('StudentReferralService')) {
    class StudentReferralService
    {
        /** @var PDO */
        private $pdo;

        /** @var int|null */
        private $actorUserId;

        public function __construct(PDO $pdo, ?int $actorUserId = null)
        {
            $this->pdo = $pdo;
            $this->actorUserId = $actorUserId;
        }

        public function tableExists(): bool
        {
            try {
                return (bool)$this->pdo->query("SHOW TABLES LIKE 'sa_referrals'")->fetchColumn();
            } catch (Throwable $e) {
                return false;
            }
        }

        public function createPendingReferral(
            int $referrerStudentId,
            int $referredStudentId,
            string $referralCodeUsed,
            array $referredStudent
        ): ?int {
            if (!$this->tableExists() || $referrerStudentId <= 0 || $referredStudentId <= 0) {
                return null;
            }

            $programId = $this->resolveProgramId($referrerStudentId);

            $stmt = $this->pdo->prepare("
                INSERT INTO sa_referrals (
                    program_id, referrer_student_id, referred_student_id, referral_type,
                    referral_code_used, referred_full_name, referred_email, referred_mobile,
                    verification_status, created_at, updated_at
                ) VALUES (?, ?, ?, 'intern', ?, ?, ?, ?, 'pending', NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    referred_student_id = VALUES(referred_student_id),
                    referred_full_name = VALUES(referred_full_name),
                    referred_email = VALUES(referred_email),
                    referred_mobile = VALUES(referred_mobile),
                    updated_at = NOW()
            ");

            $stmt->execute([
                $programId,
                $referrerStudentId,
                $referredStudentId,
                $referralCodeUsed,
                $referredStudent['full_name'] ?? null,
                $referredStudent['email'] ?? null,
                $referredStudent['mobile'] ?? null,
            ]);

            return (int)$this->pdo->lastInsertId();
        }

        public function verifyReferral(int $referralId): array
        {
            if (!$this->tableExists()) {
                return ['success' => false, 'message' => 'Referral table not available.'];
            }

            require_once __DIR__ . '/points.php';
            require_once __DIR__ . '/levels.php';
            require_once __DIR__ . '/badges.php';
            require_once __DIR__ . '/student_notify_helper.php';

            try {
                $this->pdo->beginTransaction();

                $stmt = $this->pdo->prepare("
                    SELECT r.*, ref.full_name AS referrer_name, ref.email AS referrer_email,
                           referred.full_name AS referred_name, referred.status AS referred_status
                    FROM sa_referrals r
                    JOIN sa_students ref ON ref.id = r.referrer_student_id
                    LEFT JOIN sa_students referred ON referred.id = r.referred_student_id
                    WHERE r.id = ? AND r.deleted_at IS NULL
                    LIMIT 1
                    FOR UPDATE
                ");
                $stmt->execute([$referralId]);
                $referral = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$referral) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Referral not found.'];
                }

                if ($referral['verification_status'] === 'verified') {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Referral already verified.'];
                }

                if ($referral['verification_status'] === 'rejected') {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Referral was rejected.'];
                }

                if (!empty($referral['referred_student_id']) && ($referral['referred_status'] ?? '') !== 'Active') {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Referred student must be approved before verifying referral.'];
                }

                $ruleCode = 'REFERRAL_NEW_INTERN';
                $pointEngine = new StudentPointEngine($this->pdo, $this->actorUserId);
                $pointResult = $pointEngine->awardPoints((int)$referral['referrer_student_id'], $ruleCode, [
                    'source_type' => 'referral',
                    'source_id' => $referralId,
                    'reference_code' => 'REF-' . $referralId,
                    'description' => 'Referral verified: ' . ($referral['referred_name'] ?: $referral['referred_full_name']),
                    'idempotency_key' => 'referral-verify:' . $referralId,
                    'approved_by_user_id' => $this->actorUserId,
                ]);

                if (empty($pointResult['success'])) {
                    throw new RuntimeException($pointResult['message'] ?? 'Failed to award referral points.');
                }

                $update = $this->pdo->prepare("
                    UPDATE sa_referrals
                    SET verification_status = 'verified',
                        verified_at = NOW(),
                        verified_by_user_id = ?,
                        updated_by_user_id = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $update->execute([$this->actorUserId, $this->actorUserId, $referralId]);

                $referrerId = (int)$referral['referrer_student_id'];
                $levelEngine = new StudentLevelEngine($this->pdo, $this->actorUserId);
                $levelEngine->evaluatePromotion($referrerId, ['apply' => true]);

                $badgeEngine = new StudentBadgeEngine($this->pdo, $this->actorUserId);
                $badgeEngine->evaluateBadges($referrerId, ['apply' => true]);

                $this->pdo->commit();

                student_send_notification($this->pdo, $referrerId, 'referral_verified', [
                    'student_name' => $referral['referrer_name'],
                    'referred_name' => $referral['referred_name'] ?: $referral['referred_full_name'],
                    'points_earned' => abs((int)($pointResult['points_delta'] ?? 10)),
                ]);

                return [
                    'success' => true,
                    'message' => 'Referral verified and points awarded.',
                    'points_delta' => $pointResult['points_delta'] ?? 0,
                ];
            } catch (Throwable $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }

        public function rejectReferral(int $referralId, string $reason = ''): array
        {
            if (!$this->tableExists()) {
                return ['success' => false, 'message' => 'Referral table not available.'];
            }

            try {
                $stmt = $this->pdo->prepare("
                    UPDATE sa_referrals
                    SET verification_status = 'rejected',
                        rejection_reason = ?,
                        verified_by_user_id = ?,
                        updated_by_user_id = ?,
                        updated_at = NOW()
                    WHERE id = ? AND verification_status = 'pending' AND deleted_at IS NULL
                ");
                $stmt->execute([
                    $reason !== '' ? $reason : null,
                    $this->actorUserId,
                    $this->actorUserId,
                    $referralId,
                ]);

                if ($stmt->rowCount() === 0) {
                    return ['success' => false, 'message' => 'Referral not found or not pending.'];
                }

                return ['success' => true, 'message' => 'Referral rejected.'];
            } catch (Throwable $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }

        private function resolveProgramId(int $referrerStudentId): ?int
        {
            $stmt = $this->pdo->prepare("SELECT program_id FROM sa_students WHERE id = ? LIMIT 1");
            $stmt->execute([$referrerStudentId]);
            $programId = $stmt->fetchColumn();
            if ($programId) {
                return (int)$programId;
            }

            $active = $this->pdo->query("SELECT id FROM sa_programs WHERE status = 'active' ORDER BY start_date DESC, id DESC LIMIT 1");
            $row = $active ? $active->fetchColumn() : false;

            return $row ? (int)$row : null;
        }
    }
}
