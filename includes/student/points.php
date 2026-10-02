<?php

if (!class_exists('StudentPointEngine')) {
    class StudentPointEngine
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

        public function awardPoints(int $studentId, string $ruleCode, array $context = []): array
        {
            return $this->applyRuleTransaction($studentId, $ruleCode, 'credit', $context, ['reward', 'bonus']);
        }

        public function awardBonus(int $studentId, string $ruleCode, array $context = []): array
        {
            return $this->applyRuleTransaction($studentId, $ruleCode, 'credit', $context, ['bonus', 'multiplier', 'reward']);
        }

        public function deductPoints(int $studentId, string $ruleCode, array $context = []): array
        {
            return $this->applyRuleTransaction($studentId, $ruleCode, 'debit', $context, ['penalty', 'reward', 'bonus']);
        }

        public function applyPenalty(int $studentId, string $penaltyCode, array $context = []): array
        {
            return $this->runInTransaction(function () use ($studentId, $penaltyCode, $context) {
                $student = $this->lockStudent($studentId);
                $rule = $this->getRuleByCode($penaltyCode);

                if ($rule['category'] !== 'penalty') {
                    throw new RuntimeException('Penalty rule category mismatch for rule: ' . $penaltyCode);
                }

                $status = (string)($context['status'] ?? 'active');
                $penaltyType = (string)($context['penalty_type'] ?? 'points_deduction');
                $severity = (string)($context['severity'] ?? 'medium');
                $reasonTitle = trim((string)($context['reason_title'] ?? $rule['rule_name']));
                $reasonDetails = $this->normalizeNullableString($context['reason_details'] ?? $context['description'] ?? $rule['notes'] ?? null);
                $evidencePath = $this->normalizeNullableString($context['evidence_path'] ?? null);
                $programId = $this->resolveProgramId($context, $rule, $student);
                $idempotencyKey = $this->buildIdempotencyKey($studentId, $penaltyCode, array_merge($context, [
                    'direction' => 'debit',
                    'source_type' => 'penalty',
                ]));

                $existing = $this->findTransactionByIdempotencyKey($idempotencyKey);
                if ($existing) {
                    $balance = $this->calculateBalance($studentId, true);
                    return $this->resultFromExisting($existing, $balance, true, 'Penalty already applied.');
                }

                $penaltyPoints = abs((int)$rule['base_points']);
                $startsAt = $this->normalizeDateTime($context['starts_at'] ?? null);
                $endsAt = $this->normalizeDateTime($context['ends_at'] ?? null);

                $penaltyStmt = $this->pdo->prepare("
                    INSERT INTO sa_penalties (
                        student_id, program_id, penalty_code, penalty_type, severity, status,
                        penalty_points, reason_title, reason_details, evidence_path, starts_at, ends_at,
                        imposed_by_user_id, created_by_user_id, updated_by_user_id, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, COALESCE(?, NOW()), ?, ?, ?, ?, NOW(), NOW())
                ");
                $penaltyStmt->execute([
                    $studentId,
                    $programId,
                    $penaltyCode,
                    $penaltyType,
                    $severity,
                    $status,
                    $penaltyPoints,
                    $reasonTitle,
                    $reasonDetails,
                    $evidencePath,
                    $startsAt,
                    $endsAt,
                    $this->actorUserId,
                    $this->actorUserId,
                    $this->actorUserId,
                ]);

                $penaltyId = (int)$this->pdo->lastInsertId();

                $transaction = $this->createTransaction([
                    'student_id' => $studentId,
                    'program_id' => $programId,
                    'rule_id' => (int)$rule['id'],
                    'source_type' => 'penalty',
                    'source_id' => $penaltyId,
                    'reference_code' => $this->normalizeNullableString($context['reference_code'] ?? ('PENALTY-' . $penaltyId)),
                    'idempotency_key' => $idempotencyKey,
                    'direction' => 'debit',
                    'base_points' => $penaltyPoints,
                    'multiplier_value' => 1.00,
                    'points_delta' => -$penaltyPoints,
                    'description' => $this->normalizeNullableString($context['description'] ?? $reasonTitle),
                    'approved_by_user_id' => $this->actorUserId,
                    'created_by_user_id' => $this->actorUserId,
                    'updated_by_user_id' => $this->actorUserId,
                ]);

                $this->pdo->prepare("
                    UPDATE sa_penalties
                    SET point_transaction_id = ?, updated_by_user_id = ?, updated_at = NOW()
                    WHERE id = ?
                ")->execute([$transaction['id'], $this->actorUserId, $penaltyId]);

                $balance = $this->calculateBalance($studentId, true);
                $this->logActivity($studentId, 'penalty_applied', 'Penalty Applied', $reasonTitle, -$penaltyPoints, [
                    'penalty_id' => $penaltyId,
                    'point_transaction_id' => $transaction['id'],
                ]);

                return [
                    'success' => true,
                    'duplicate' => false,
                    'student_id' => $studentId,
                    'transaction_id' => (int)$transaction['id'],
                    'penalty_id' => $penaltyId,
                    'points_delta' => (int)$transaction['points_delta'],
                    'balance' => $balance,
                    'message' => 'Penalty applied successfully.',
                ];
            });
        }

        public function reversePoints(int $transactionId, array $context = []): array
        {
            return $this->runInTransaction(function () use ($transactionId, $context) {
                $stmt = $this->pdo->prepare("
                    SELECT *
                    FROM sa_point_transactions
                    WHERE id = ? AND deleted_at IS NULL
                    LIMIT 1
                    FOR UPDATE
                ");
                $stmt->execute([$transactionId]);
                $original = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$original) {
                    throw new RuntimeException('Point transaction not found.');
                }

                if ($original['transaction_status'] === 'reversed') {
                    throw new RuntimeException('Point transaction has already been reversed.');
                }

                $studentId = (int)$original['student_id'];
                $this->lockStudent($studentId);

                $referenceCode = $this->normalizeNullableString($context['reference_code'] ?? ('REV-' . $transactionId));
                $idempotencyKey = $this->buildIdempotencyKey($studentId, 'reverse:' . $transactionId, array_merge($context, [
                    'direction' => 'credit',
                    'source_type' => 'system',
                    'source_id' => $transactionId,
                    'reference_code' => $referenceCode,
                ]));

                $existing = $this->findTransactionByIdempotencyKey($idempotencyKey);
                if ($existing) {
                    $balance = $this->calculateBalance($studentId, true);
                    return $this->resultFromExisting($existing, $balance, true, 'Reversal already exists.');
                }

                $reverseDirection = $original['direction'] === 'credit' ? 'debit' : 'credit';
                $reversePoints = (int)$original['points_delta'] * -1;

                $reversal = $this->createTransaction([
                    'student_id' => $studentId,
                    'program_id' => $original['program_id'] !== null ? (int)$original['program_id'] : null,
                    'rule_id' => $original['rule_id'] !== null ? (int)$original['rule_id'] : null,
                    'source_type' => 'system',
                    'source_id' => $transactionId,
                    'reference_code' => $referenceCode,
                    'idempotency_key' => $idempotencyKey,
                    'direction' => $reverseDirection,
                    'base_points' => abs((int)$original['base_points']),
                    'multiplier_value' => (float)$original['multiplier_value'],
                    'points_delta' => $reversePoints,
                    'description' => $this->normalizeNullableString($context['description'] ?? ('Reversal of transaction #' . $transactionId)),
                    'approved_by_user_id' => $this->actorUserId,
                    'reversed_transaction_id' => $transactionId,
                    'created_by_user_id' => $this->actorUserId,
                    'updated_by_user_id' => $this->actorUserId,
                ]);

                $this->pdo->prepare("
                    UPDATE sa_point_transactions
                    SET transaction_status = 'reversed', updated_by_user_id = ?, updated_at = NOW()
                    WHERE id = ?
                ")->execute([$this->actorUserId, $transactionId]);

                $balance = $this->calculateBalance($studentId, true);
                $this->logActivity(
                    $studentId,
                    'points_reversed',
                    'Points Reversed',
                    $this->normalizeNullableString($context['description'] ?? ('Reversal of transaction #' . $transactionId)) ?: 'Points reversed',
                    $reversePoints,
                    ['original_transaction_id' => $transactionId, 'reversal_transaction_id' => $reversal['id']]
                );

                return [
                    'success' => true,
                    'duplicate' => false,
                    'student_id' => $studentId,
                    'transaction_id' => (int)$reversal['id'],
                    'reversed_transaction_id' => $transactionId,
                    'points_delta' => $reversePoints,
                    'balance' => $balance,
                    'message' => 'Points reversed successfully.',
                ];
            });
        }

        public function calculateBalance(int $studentId, bool $persist = true): int
        {
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(points_delta), 0)
                FROM sa_point_transactions
                WHERE student_id = ?
                  AND deleted_at IS NULL
                  AND transaction_status = 'posted'
            ");
            $stmt->execute([$studentId]);
            $balance = (int)$stmt->fetchColumn();

            if ($persist) {
                $update = $this->pdo->prepare("
                    UPDATE sa_students
                    SET total_points = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $update->execute([$balance, $studentId]);

                require_once __DIR__ . '/leaderboard_cache.php';
                StudentLeaderboardCache::invalidateAll();

                // Trigger promotion logic evaluation automatically
                if (!class_exists('StudentLevelEngine')) {
                    require_once __DIR__ . '/levels.php';
                }
                if (class_exists('StudentLevelEngine')) {
                    $levelEngine = new StudentLevelEngine($this->pdo, $this->actorUserId);
                    $levelEngine->evaluatePromotion($studentId, ['apply' => true]);
                }
            }

            return $balance;
        }

        private function applyRuleTransaction(int $studentId, string $ruleCode, string $direction, array $context, array $allowedCategories): array
        {
            return $this->runInTransaction(function () use ($studentId, $ruleCode, $direction, $context, $allowedCategories) {
                $student = $this->lockStudent($studentId);
                $rule = $this->getRuleByCode($ruleCode);

                if (!in_array($rule['category'], $allowedCategories, true)) {
                    throw new RuntimeException('Rule category mismatch for action on rule: ' . $ruleCode);
                }

                $sourceType = trim((string)($context['source_type'] ?? $rule['trigger_key'] ?? 'system'));
                $sourceId = isset($context['source_id']) ? (int)$context['source_id'] : null;
                $referenceCode = $this->normalizeNullableString($context['reference_code'] ?? null);
                $idempotencyKey = $this->buildIdempotencyKey($studentId, $ruleCode, array_merge($context, [
                    'direction' => $direction,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'reference_code' => $referenceCode,
                ]));

                $existing = $this->findTransactionByIdempotencyKey($idempotencyKey);
                if ($existing) {
                    $balance = $this->calculateBalance($studentId, true);
                    return $this->resultFromExisting($existing, $balance, true, 'Duplicate award prevented.');
                }

                $programId = $this->resolveProgramId($context, $rule, $student);
                $basePoints = abs((int)($context['base_points'] ?? $rule['base_points']));
                $multiplier = isset($context['multiplier_value'])
                    ? max(0, (float)$context['multiplier_value'])
                    : max(0, (float)$rule['multiplier_value']);

                if ($multiplier <= 0) {
                    $multiplier = 1.00;
                }

                $computedPoints = isset($context['points_delta'])
                    ? (int)$context['points_delta']
                    : (int)round($basePoints * $multiplier);

                if ($direction === 'debit' && $computedPoints > 0) {
                    $computedPoints *= -1;
                }

                if ($direction === 'credit' && $computedPoints < 0) {
                    $computedPoints *= -1;
                }

                if ($computedPoints === 0) {
                    throw new RuntimeException('Point delta resolved to zero; transaction skipped.');
                }

                $description = $this->normalizeNullableString($context['description'] ?? $rule['rule_name']);

                $transaction = $this->createTransaction([
                    'student_id' => $studentId,
                    'program_id' => $programId,
                    'rule_id' => (int)$rule['id'],
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'reference_code' => $referenceCode,
                    'idempotency_key' => $idempotencyKey,
                    'direction' => $direction,
                    'base_points' => $basePoints,
                    'multiplier_value' => $multiplier,
                    'points_delta' => $computedPoints,
                    'description' => $description,
                    'approved_by_user_id' => $this->resolveApprovedByUserId($context, $rule),
                    'created_by_user_id' => $this->actorUserId,
                    'updated_by_user_id' => $this->actorUserId,
                ]);

                $balance = $this->calculateBalance($studentId, true);
                $activityType = $computedPoints >= 0 ? 'points_awarded' : 'points_deducted';
                $activityTitle = $computedPoints >= 0 ? 'Points Awarded' : 'Points Deducted';
                $this->logActivity($studentId, $activityType, $activityTitle, $description ?: $rule['rule_name'], $computedPoints, [
                    'point_transaction_id' => $transaction['id'],
                    'rule_code' => $rule['rule_code'],
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                ]);

                return [
                    'success' => true,
                    'duplicate' => false,
                    'student_id' => $studentId,
                    'transaction_id' => (int)$transaction['id'],
                    'rule_id' => (int)$rule['id'],
                    'rule_code' => $rule['rule_code'],
                    'points_delta' => $computedPoints,
                    'balance' => $balance,
                    'message' => 'Point transaction created successfully.',
                ];
            });
        }

        private function createTransaction(array $data): array
        {
            $insert = $this->pdo->prepare("
                INSERT INTO sa_point_transactions (
                    student_id, program_id, rule_id, source_type, source_id, reference_code, idempotency_key,
                    direction, base_points, multiplier_value, points_delta, balance_after, transaction_status,
                    description, approved_by_user_id, reversed_transaction_id, posted_at,
                    created_by_user_id, updated_by_user_id, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, NULL, 'posted',
                    ?, ?, ?, NOW(),
                    ?, ?, NOW(), NOW()
                )
            ");

            $insert->execute([
                $data['student_id'],
                $data['program_id'],
                $data['rule_id'],
                $data['source_type'],
                $data['source_id'],
                $data['reference_code'],
                $data['idempotency_key'],
                $data['direction'],
                $data['base_points'],
                $data['multiplier_value'],
                $data['points_delta'],
                $data['description'],
                $data['approved_by_user_id'],
                $data['reversed_transaction_id'] ?? null,
                $data['created_by_user_id'] ?? null,
                $data['updated_by_user_id'] ?? null,
            ]);

            $transactionId = (int)$this->pdo->lastInsertId();
            $balance = $this->calculateBalance((int)$data['student_id'], false);

            $update = $this->pdo->prepare("
                UPDATE sa_point_transactions
                SET balance_after = ?, updated_by_user_id = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $update->execute([$balance, $this->actorUserId, $transactionId]);

            $fetch = $this->pdo->prepare("SELECT * FROM sa_point_transactions WHERE id = ? LIMIT 1");
            $fetch->execute([$transactionId]);
            $transaction = $fetch->fetch(PDO::FETCH_ASSOC);

            if (!$transaction) {
                throw new RuntimeException('Unable to reload inserted point transaction.');
            }

            return $transaction;
        }

        private function getRuleByCode(string $ruleCode): array
        {
            $stmt = $this->pdo->prepare("
                SELECT *
                FROM sa_point_rules
                WHERE rule_code = ?
                  AND deleted_at IS NULL
                  AND is_active = 1
                  AND (effective_from IS NULL OR effective_from <= NOW())
                  AND (effective_to IS NULL OR effective_to >= NOW())
                LIMIT 1
            ");
            $stmt->execute([trim($ruleCode)]);
            $rule = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$rule) {
                throw new RuntimeException('Active point rule not found for code: ' . $ruleCode);
            }

            return $rule;
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

        private function findTransactionByIdempotencyKey(?string $idempotencyKey): ?array
        {
            if ($idempotencyKey === null || $idempotencyKey === '') {
                return null;
            }

            $stmt = $this->pdo->prepare("
                SELECT *
                FROM sa_point_transactions
                WHERE idempotency_key = ?
                  AND deleted_at IS NULL
                LIMIT 1
            ");
            $stmt->execute([$idempotencyKey]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        }

        private function buildIdempotencyKey(int $studentId, string $ruleCode, array $context): string
        {
            if (!empty($context['idempotency_key'])) {
                return substr(trim((string)$context['idempotency_key']), 0, 190);
            }

            $parts = [
                $studentId,
                trim($ruleCode),
                trim((string)($context['direction'] ?? 'credit')),
                trim((string)($context['source_type'] ?? 'system')),
                isset($context['source_id']) ? (string)(int)$context['source_id'] : '',
                trim((string)($context['reference_code'] ?? '')),
            ];

            return hash('sha256', implode('|', $parts));
        }

        private function resolveProgramId(array $context, array $rule, array $student): ?int
        {
            if (isset($context['program_id']) && $context['program_id'] !== null && $context['program_id'] !== '') {
                return (int)$context['program_id'];
            }

            if (!empty($rule['program_id'])) {
                return (int)$rule['program_id'];
            }

            if (isset($student['program_id']) && $student['program_id'] !== null && $student['program_id'] !== '') {
                return (int)$student['program_id'];
            }

            return null;
        }

        private function resolveApprovedByUserId(array $context, array $rule): ?int
        {
            if (isset($context['approved_by_user_id']) && $context['approved_by_user_id'] !== null && $context['approved_by_user_id'] !== '') {
                return (int)$context['approved_by_user_id'];
            }

            if ((int)$rule['requires_approval'] === 1) {
                return $this->actorUserId;
            }

            return $this->actorUserId;
        }

        private function logActivity(int $studentId, string $activityType, string $title, string $description, int $points, array $meta = []): void
        {
            $referenceId = isset($meta['point_transaction_id']) ? (int)$meta['point_transaction_id'] : (isset($meta['penalty_id']) ? (int)$meta['penalty_id'] : null);
            $referenceType = isset($meta['point_transaction_id']) ? 'sa_point_transactions' : (isset($meta['penalty_id']) ? 'sa_penalties' : null);

            $stmt = $this->pdo->prepare("
                INSERT INTO sa_activity_logs (
                    student_id, activity_type, title, description, points, reference_id, reference_type, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $studentId,
                $activityType,
                $title,
                $description,
                $points,
                $referenceId,
                $referenceType,
            ]);
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

        private function resultFromExisting(array $existing, int $balance, bool $duplicate, string $message): array
        {
            return [
                'success' => true,
                'duplicate' => $duplicate,
                'student_id' => (int)$existing['student_id'],
                'transaction_id' => (int)$existing['id'],
                'rule_id' => isset($existing['rule_id']) && $existing['rule_id'] !== null ? (int)$existing['rule_id'] : null,
                'points_delta' => (int)$existing['points_delta'],
                'balance' => $balance,
                'message' => $message,
            ];
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
            if ($value === null) {
                return null;
            }

            $value = trim((string)$value);
            return $value === '' ? null : $value;
        }
    }
}
