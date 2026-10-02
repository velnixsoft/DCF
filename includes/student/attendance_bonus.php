<?php
/**
 * AttendanceBonusEngine - Award bonuses for attendance thresholds
 * 
 * Responsibilities:
 * - Award points for meeting attendance thresholds (90%, 95%, 100%)
 * - Prevent duplicate bonus awards (UNIQUE constraint)
 * - Track award history and audit trail
 * - Integrate with point system
 */

class AttendanceBonusEngine {
    private $pdo;
    private $scorer;
    private $DEBUG = false;

    public function __construct(\PDO $pdo, $debug = false) {
        $this->pdo = $pdo;
        $this->DEBUG = $debug;
        require_once __DIR__ . '/attendance_scoring.php';
        $this->scorer = new AttendanceScorer($pdo, $debug);
    }

    /**
     * Award monthly bonus to a student if eligible
     * 
     * Main entry point - handles all bonus logic
     * 
     * @param int $studentId
     * @param int $year
     * @param int $month
     * @return array ['success' => bool, 'bonus_awarded' => bool, 'points' => int, 'message' => string]
     */
    public function awardMonthlyBonus($studentId, $year, $month) {
        try {
            // Get monthly attendance
            $sql = "SELECT * FROM attendance_monthly_summary
                    WHERE student_id = ?
                    AND year = ?
                    AND month = ?
                    LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$studentId, $year, $month]);
            $summary = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$summary) {
                return [
                    'success' => false,
                    'bonus_awarded' => false,
                    'message' => 'No attendance summary found for this month'
                ];
            }

            $percentage = (float)$summary['attendance_percentage'];

            // Get applicable bonus
            $bonus = $this->scorer->getApplicableBonus($percentage);

            if (!$bonus) {
                return [
                    'success' => true,
                    'bonus_awarded' => false,
                    'percentage' => $percentage,
                    'message' => 'Attendance below all configured thresholds - no bonus'
                ];
            }

            // Check if bonus already awarded
            if ((int)$summary['attendance_bonus_awarded'] === 1) {
                return [
                    'success' => true,
                    'bonus_awarded' => false,
                    'percentage' => $percentage,
                    'message' => 'Bonus already awarded for this month',
                    'already_awarded' => true
                ];
            }

            // Award the bonus
            $result = $this->awardBonus(
                $studentId,
                $year,
                $month,
                $percentage,
                $bonus
            );

            return $result;
        } catch (\Exception $e) {
            $this->log("ERROR awarding monthly bonus: " . $e->getMessage());
            return [
                'success' => false,
                'bonus_awarded' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to process bonus award'
            ];
        }
    }

    /**
     * Process bonus award - internal method
     * Handles point award, history tracking, audit logging
     * 
     * @param int $studentId
     * @param int $year
     * @param int $month
     * @param float $percentage
     * @param array $threshold Threshold config from DB
     * @return array Result of bonus processing
     */
    private function awardBonus($studentId, $year, $month, $percentage, $threshold) {
        try {
            $this->pdo->beginTransaction();

            $bonusPoints = (int)$threshold['bonus_points'];
            $thresholdId = (int)$threshold['id'];

            // Award points to student
            $this->awardBonusPoints($studentId, $bonusPoints, "attendance_bonus_{$year}_{$month}");

            // Record in bonus history with UNIQUE constraint
            $this->recordBonusAward(
                $studentId,
                $year,
                $month,
                $percentage,
                $threshold['percentage_required'],
                $bonusPoints,
                $thresholdId
            );

            // Update monthly_summary
            $this->markBonusAwarded($studentId, $year, $month, $bonusPoints);

            // Log activity
            $this->logActivity($studentId, $bonusPoints, $year, $month, $threshold['threshold_name']);

            $this->pdo->commit();

            return [
                'success' => true,
                'bonus_awarded' => true,
                'points' => $bonusPoints,
                'threshold_name' => $threshold['threshold_name'],
                'percentage' => $percentage,
                'message' => "Successfully awarded {$bonusPoints} bonus points for {$percentage}% attendance"
            ];
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            $this->log("ERROR in award bonus: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Award bonus points to student
     * Updates sa_students.total_points
     * 
     * @param int $studentId
     * @param int $points Points to award
     * @param string $reference Reference for tracking
     * @return bool Success
     */
    public function awardBonusPoints($studentId, $points, $reference = '') {
        try {
            require_once __DIR__ . '/points.php';
            $pointEngine = new StudentPointEngine($this->pdo);
            $result = $pointEngine->awardBonus((int)$studentId, 'ATTENDANCE_90_BONUS', [
                'source_type' => 'attendance',
                'base_points' => (int)$points,
                'points_delta' => (int)$points,
                'description' => 'Attendance bonus: ' . $reference,
                'reference_code' => $reference !== '' ? $reference : ('ATT-BONUS-' . $studentId),
                'idempotency_key' => 'attendance-bonus:' . $studentId . ':' . ($reference !== '' ? $reference : uniqid()),
            ]);

            if (empty($result['success'])) {
                throw new \Exception($result['message'] ?? 'Failed to award attendance bonus points');
            }

            $this->log("Awarded {$points} points to student {$studentId}");
            return true;
        } catch (\Exception $e) {
            $this->log("ERROR awarding bonus points: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Log point transaction
     * 
     * @param int $studentId
     * @param int $points
     * @param string $reference
     * @return bool
     */
    private function logPointTransaction($studentId, $points, $reference) {
        try {
            // Check if sa_point_transactions table exists
            $checkSql = "SELECT 1 FROM information_schema.TABLES 
                         WHERE TABLE_SCHEMA = DATABASE() 
                         AND TABLE_NAME = 'sa_point_transactions'";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                $sql = "INSERT INTO sa_point_transactions 
                        (student_id, points, transaction_type, reference, created_at)
                        VALUES (?, ?, 'bonus', ?, NOW())";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$studentId, $points, $reference]);
            }
            
            return true;
        } catch (\Exception $e) {
            $this->log("WARNING: Could not log point transaction: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record bonus award in history (audit trail)
     * UNIQUE constraint prevents duplicate awards
     * 
     * @param int $studentId
     * @param int $year
     * @param int $month
     * @param float $percentage
     * @param float $threshold
     * @param int $bonusPoints
     * @param int $thresholdId
     * @return bool Success
     */
    private function recordBonusAward($studentId, $year, $month, $percentage, $threshold, $bonusPoints, $thresholdId) {
        try {
            $sql = "INSERT INTO attendance_bonus_history
                    (student_id, year, month, attendance_percentage, threshold_percentage, threshold_met, bonus_points_awarded, reference_threshold_id, processed_by_cron, cron_execution_timestamp, awarded_at)
                    VALUES (?, ?, ?, ?, ?, 1, ?, ?, 0, NULL, NOW())
                    ON DUPLICATE KEY UPDATE
                    bonus_points_awarded = VALUES(bonus_points_awarded),
                    awarded_at = NOW()";
            
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([
                $studentId,
                $year,
                $month,
                $percentage,
                $threshold,
                $bonusPoints,
                $thresholdId
            ]);

            if ($success) {
                $this->log("Recorded bonus award for student {$studentId}, {$year}-{$month}");
            }

            return $success;
        } catch (\Exception $e) {
            $this->log("ERROR recording bonus award: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mark bonus as awarded in monthly summary
     * 
     * @param int $studentId
     * @param int $year
     * @param int $month
     * @param int $bonusPoints
     * @return bool
     */
    private function markBonusAwarded($studentId, $year, $month, $bonusPoints) {
        try {
            $sql = "UPDATE attendance_monthly_summary
                    SET attendance_bonus_awarded = 1,
                        bonus_points_amount = ?,
                        bonus_processing_date = NOW(),
                        updated_at = NOW()
                    WHERE student_id = ?
                    AND year = ?
                    AND month = ?";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$bonusPoints, $studentId, $year, $month]);
        } catch (\Exception $e) {
            $this->log("ERROR marking bonus awarded: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Log activity in sa_activity_logs
     * 
     * @param int $studentId
     * @param int $bonusPoints
     * @param int $year
     * @param int $month
     * @param string $thresholdName
     * @return bool
     */
    private function logActivity($studentId, $bonusPoints, $year, $month, $thresholdName) {
        try {
            $sql = "INSERT INTO sa_activity_logs
                    (student_id, activity_type, title, description, points, reference_type, created_at)
                    VALUES (?, 'bonus', ?, ?, ?, 'attendance_bonus', NOW())";
            
            $title = "Monthly Attendance Bonus - {$thresholdName}";
            $description = "Earned {$bonusPoints} points for {$thresholdName} attendance in {$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT);
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$studentId, $title, $description, $bonusPoints]);
        } catch (\Exception $e) {
            $this->log("WARNING: Could not log activity: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get bonus history for a student
     * 
     * @param int $studentId
     * @param int $limit Number of records to retrieve
     * @return array Bonus history
     */
    public function getBonusHistory($studentId, $limit = 12) {
        try {
            $sql = "SELECT * FROM attendance_bonus_history
                    WHERE student_id = ?
                    ORDER BY year DESC, month DESC
                    LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$studentId, $limit]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->log("ERROR getting bonus history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get bonus statistics for a student
     * 
     * @param int $studentId
     * @return array Stats about earned bonuses
     */
    public function getBonusStatistics($studentId) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_bonuses,
                        SUM(bonus_points_awarded) as total_bonus_points,
                        AVG(bonus_points_awarded) as avg_bonus_points,
                        COUNT(CASE WHEN threshold_met = 1 THEN 1 END) as successful_awards
                    FROM attendance_bonus_history
                    WHERE student_id = ?
                    AND threshold_met = 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$studentId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->log("ERROR getting bonus statistics: " . $e->getMessage());
            return [
                'total_bonuses' => 0,
                'total_bonus_points' => 0,
                'avg_bonus_points' => 0,
                'successful_awards' => 0
            ];
        }
    }

    /**
     * Process all pending bonuses for a given month
     * Called by cron job
     * 
     * @param int $year
     * @param int $month
     * @return array ['success' => bool, 'processed' => int, 'awarded' => int, 'failed' => int]
     */
    public function processPendingBonuses($year, $month) {
        try {
            // Get all students with calculated attendance for this month
            // that haven't received bonus yet
            $sql = "SELECT student_id, attendance_percentage
                    FROM attendance_monthly_summary
                    WHERE year = ?
                    AND month = ?
                    AND attendance_bonus_awarded = 0
                    AND is_processed = 1
                    ORDER BY student_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$year, $month]);
            $records = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $results = [
                'success' => true,
                'total' => count($records),
                'processed' => 0,
                'awarded' => 0,
                'failed' => 0,
                'details' => []
            ];

            foreach ($records as $record) {
                $studentId = (int)$record['student_id'];
                $result = $this->awardMonthlyBonus($studentId, $year, $month);

                if ($result['success']) {
                    $results['processed']++;
                    if ($result['bonus_awarded']) {
                        $results['awarded']++;
                    }
                } else {
                    $results['failed']++;
                }

                $results['details'][] = [
                    'student_id' => $studentId,
                    'success' => $result['success'],
                    'bonus_awarded' => $result['bonus_awarded'] ?? false,
                    'message' => $result['message']
                ];
            }

            return $results;
        } catch (\Exception $e) {
            $this->log("ERROR processing pending bonuses: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processed' => 0,
                'awarded' => 0,
                'failed' => 0
            ];
        }
    }

    /**
     * Validate bonus eligibility for a student
     * 
     * @param int $studentId
     * @param int $year
     * @param int $month
     * @return array ['eligible' => bool, 'reason' => string, 'issues' => array]
     */
    public function validateBonusEligibility($studentId, $year, $month) {
        $issues = [];

        try {
            // Check if attendance summary exists
            $sql = "SELECT * FROM attendance_monthly_summary
                    WHERE student_id = ? AND year = ? AND month = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$studentId, $year, $month]);
            $summary = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$summary) {
                $issues[] = 'No attendance summary found';
                return ['eligible' => false, 'reason' => 'no_summary', 'issues' => $issues];
            }

            // Check if already awarded
            if ((int)$summary['attendance_bonus_awarded'] === 1) {
                $issues[] = 'Bonus already awarded this month';
                return ['eligible' => false, 'reason' => 'already_awarded', 'issues' => $issues];
            }

            // Check if meets any threshold
            $percentage = (float)$summary['attendance_percentage'];
            $bonus = $this->scorer->getApplicableBonus($percentage);

            if (!$bonus) {
                $issues[] = "Attendance {$percentage}% below all configured thresholds";
                return ['eligible' => false, 'reason' => 'below_threshold', 'issues' => $issues];
            }

            return [
                'eligible' => true,
                'reason' => 'meets_threshold',
                'issues' => [],
                'percentage' => $percentage,
                'applicable_bonus' => $bonus['threshold_name']
            ];
        } catch (\Exception $e) {
            $this->log("ERROR validating eligibility: " . $e->getMessage());
            $issues[] = $e->getMessage();
            return ['eligible' => false, 'reason' => 'error', 'issues' => $issues];
        }
    }

    /**
     * Log debug messages
     */
    private function log($message) {
        if ($this->DEBUG) {
            error_log('[AttendanceBonusEngine] ' . $message);
        }
    }
}
