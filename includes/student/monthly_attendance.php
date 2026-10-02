<?php
/**
 * MonthlyAttendanceEngine - Monthly attendance calculation and tracking
 * 
 * Responsibilities:
 * - Calculate monthly metrics for all students
 * - Store results in monthly_summary table
 * - Prevent duplicate calculations
 * - Track processing status
 */

class MonthlyAttendanceEngine {
    private $pdo;
    private $scorer; // AttendanceScorer instance
    private $DEBUG = false;

    public function __construct(\PDO $pdo, $debug = false) {
        $this->pdo = $pdo;
        $this->DEBUG = $debug;
        // Initialize scorer
        require_once __DIR__ . '/attendance_scoring.php';
        $this->scorer = new AttendanceScorer($pdo, $debug);
    }

    /**
     * Process attendance for a specific student for a given month
     * Returns the calculated metrics and stores in DB
     * 
     * @param int $studentId
     * @param int $year
     * @param int $month
     * @return array ['success' => bool, 'data' => [...], 'message' => string]
     */
    public function processStudentMonthlyAttendance($studentId, $year, $month) {
        try {
            // Check if already processed (to prevent duplicate calculations)
            $existing = $this->getMonthlySummary($studentId, $year, $month);
            if ($existing) {
                $this->log("Monthly attendance already calculated for student {$studentId}, {$year}-{$month}");
                return [
                    'success' => true,
                    'data' => $existing,
                    'message' => 'Already processed',
                    'already_processed' => true
                ];
            }

            // Calculate metrics
            $metrics = $this->scorer->calculateMonthlyAttendance($studentId, $year, $month);
            
            if ($metrics['status'] !== 'success') {
                throw new \Exception("Scoring failed: {$metrics['status']}");
            }

            // Validate data
            $validation = $this->scorer->validateAttendanceData($metrics);
            if (!$validation['is_valid']) {
                throw new \Exception("Data validation failed: " . implode(', ', $validation['issues']));
            }

            // Store in database
            $summary = $this->storeMonthlySummary(
                $studentId,
                $year,
                $month,
                $metrics['eligible'],
                $metrics['attended'],
                $metrics['percentage']
            );

            return [
                'success' => true,
                'data' => $summary,
                'message' => 'Monthly attendance calculated and stored',
                'metrics' => $metrics,
                'warnings' => $validation['warnings']
            ];
        } catch (\Exception $e) {
            $this->log("ERROR processing monthly attendance: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Processing failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process monthly attendance for all active students in a given month
     * 
     * @param int $year
     * @param int $month
     * @param int $limit Max students to process in one call (0 = all)
     * @return array ['success' => bool, 'processed' => int, 'failed' => int, 'results' => array]
     */
    public function processAllStudentsMonthlyAttendance($year, $month, $limit = 0) {
        try {
            // Get all active students
            $sql = "SELECT id FROM sa_students WHERE status = 'Active' ORDER BY id";
            if ($limit > 0) {
                $sql .= " LIMIT {$limit}";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $students = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            $results = [
                'success' => true,
                'processed' => 0,
                'failed' => 0,
                'already_processed' => 0,
                'total_students' => count($students),
                'details' => []
            ];

            foreach ($students as $studentId) {
                $result = $this->processStudentMonthlyAttendance($studentId, $year, $month);
                
                if ($result['success']) {
                    if (isset($result['already_processed']) && $result['already_processed']) {
                        $results['already_processed']++;
                    } else {
                        $results['processed']++;
                    }
                } else {
                    $results['failed']++;
                }
                
                $results['details'][] = [
                    'student_id' => $studentId,
                    'success' => $result['success'],
                    'message' => $result['message']
                ];
            }

            return $results;
        } catch (\Exception $e) {
            $this->log("ERROR in batch processing: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processed' => 0,
                'failed' => 0
            ];
        }
    }

    /**
     * Get or create monthly summary record
     * 
     * @param int $studentId
     * @param int $year
     * @param int $month
     * @return array Monthly summary data or null
     */
    public function getMonthlySummary($studentId, $year, $month) {
        try {
            $sql = "SELECT * FROM attendance_monthly_summary
                    WHERE student_id = ?
                    AND year = ?
                    AND month = ?
                    LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$studentId, $year, $month]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->log("ERROR getting monthly summary: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Store monthly attendance summary in database
     * 
     * @param int $studentId
     * @param int $year
     * @param int $month
     * @param int $eligibleDays
     * @param int $attendedDays
     * @param float $percentage
     * @return array Inserted/updated record
     */
    private function storeMonthlySummary($studentId, $year, $month, $eligibleDays, $attendedDays, $percentage) {
        try {
            $sql = "INSERT INTO attendance_monthly_summary 
                    (student_id, year, month, total_eligible_days, days_attended, attendance_percentage, is_processed, processing_timestamp)
                    VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
                    ON DUPLICATE KEY UPDATE
                    total_eligible_days = VALUES(total_eligible_days),
                    days_attended = VALUES(days_attended),
                    attendance_percentage = VALUES(attendance_percentage),
                    is_processed = 1,
                    processing_timestamp = NOW(),
                    updated_at = NOW()";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $studentId,
                $year,
                $month,
                $eligibleDays,
                $attendedDays,
                $percentage
            ]);

            return $this->getMonthlySummary($studentId, $year, $month);
        } catch (\Exception $e) {
            $this->log("ERROR storing monthly summary: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update monthly summary with bonus award information
     * 
     * @param int $studentId
     * @param int $year
     * @param int $month
     * @param int $bonusPoints
     * @return bool Success
     */
    public function markBonusAwarded($studentId, $year, $month, $bonusPoints) {
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
            return false;
        }
    }

    /**
     * Get summary statistics for a month (all students)
     * 
     * @param int $year
     * @param int $month
     * @return array Statistics about that month's attendance
     */
    public function getMonthlyStatistics($year, $month) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_records,
                        COUNT(DISTINCT student_id) as students_with_attendance,
                        AVG(attendance_percentage) as avg_attendance,
                        MAX(attendance_percentage) as max_attendance,
                        MIN(attendance_percentage) as min_attendance,
                        SUM(CASE WHEN attendance_percentage >= 90 THEN 1 ELSE 0 END) as students_above_90,
                        SUM(CASE WHEN attendance_bonus_awarded = 1 THEN 1 ELSE 0 END) as bonuses_awarded,
                        SUM(bonus_points_amount) as total_bonus_points
                    FROM attendance_monthly_summary
                    WHERE year = ? AND month = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$year, $month]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->log("ERROR getting monthly statistics: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get top attendance performers for a month
     * 
     * @param int $year
     * @param int $month
     * @param int $limit Default 10
     * @return array Top performers with attendance details
     */
    public function getTopAttendancePerformers($year, $month, $limit = 10) {
        try {
            $sql = "SELECT 
                        ams.student_id,
                        s.full_name,
                        s.student_no,
                        s.level_name,
                        ams.attendance_percentage,
                        ams.days_attended,
                        ams.total_eligible_days,
                        ams.attendance_bonus_awarded,
                        ams.bonus_points_amount
                    FROM attendance_monthly_summary ams
                    JOIN sa_students s ON ams.student_id = s.id
                    WHERE ams.year = ? AND ams.month = ?
                    ORDER BY ams.attendance_percentage DESC
                    LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$year, $month, $limit]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->log("ERROR getting top performers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get students below a certain attendance threshold
     * 
     * @param int $year
     * @param int $month
     * @param float $threshold Percentage (e.g., 70)
     * @param int $limit Default 20
     * @return array Students below threshold
     */
    public function getStudentsBelowThreshold($year, $month, $threshold = 70, $limit = 20) {
        try {
            $sql = "SELECT 
                        ams.student_id,
                        s.full_name,
                        s.student_no,
                        s.level_name,
                        ams.attendance_percentage,
                        ams.days_attended,
                        ams.total_eligible_days,
                        ? - ams.attendance_percentage as points_short
                    FROM attendance_monthly_summary ams
                    JOIN sa_students s ON ams.student_id = s.id
                    WHERE ams.year = ? 
                    AND ams.month = ?
                    AND ams.attendance_percentage < ?
                    ORDER BY ams.attendance_percentage ASC
                    LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$threshold, $year, $month, $threshold, $limit]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->log("ERROR getting students below threshold: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check current promotion eligibility based on attendance
     * 
     * @param int $studentId
     * @return array ['eligible' => bool, 'current_level' => string, 'required_attendance' => float, 'current_attendance' => float, 'issues' => array]
     */
    public function checkPromotionEligibilityByAttendance($studentId) {
        try {
            // Get current level and requirement
            $studentSql = "SELECT s.id, s.full_name, s.level_name, pr.min_attendance_rate
                           FROM sa_students s
                           LEFT JOIN promotion_rules pr ON s.level_name = pr.level_name
                           WHERE s.id = ?
                           LIMIT 1";
            
            $stmt = $this->pdo->prepare($studentSql);
            $stmt->execute([$studentId]);
            $student = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$student) {
                throw new \Exception("Student not found");
            }

            // Get current month attendance
            $now = new \DateTime('now');
            $year = (int)$now->format('Y');
            $month = (int)$now->format('m');
            
            $metrics = $this->scorer->calculateMonthlyAttendance($studentId, $year, $month);
            $currentAttendance = $metrics['percentage'];
            $requiredAttendance = (float)($student['min_attendance_rate'] ?? 70);

            $issues = [];
            if ($currentAttendance < $requiredAttendance) {
                $issues[] = "Attendance {$currentAttendance}% is below required {$requiredAttendance}%";
            }

            return [
                'eligible' => $currentAttendance >= $requiredAttendance,
                'current_level' => $student['level_name'],
                'required_attendance' => $requiredAttendance,
                'current_attendance' => $currentAttendance,
                'issues' => $issues
            ];
        } catch (\Exception $e) {
            $this->log("ERROR checking promotion eligibility: " . $e->getMessage());
            return [
                'eligible' => false,
                'error' => $e->getMessage(),
                'issues' => ['Unable to determine eligibility']
            ];
        }
    }

    /**
     * Get attendance summary for a student
     * 
     * @param int $studentId
     * @param int $months How many months to include (default 6)
     * @return array Student data with attendance history
     */
    public function getStudentAttendanceSummary($studentId, $months = 6) {
        try {
            $sql = "SELECT 
                        s.id,
                        s.full_name,
                        s.student_no,
                        s.level_name,
                        s.city_name,
                        s.total_points,
                        s.monthly_attendance_percentage,
                        s.last_attendance_bonus_date
                    FROM sa_students s
                    WHERE s.id = ?
                    LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$studentId]);
            $student = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$student) {
                return null;
            }

            // Get monthly breakdown
            $student['monthly_breakdown'] = $this->scorer->getAttendanceTrend($studentId, $months);
            
            // Get bonus history
            $bonusSql = "SELECT * FROM attendance_bonus_history
                         WHERE student_id = ?
                         ORDER BY year DESC, month DESC
                         LIMIT ?";
            $bonusStmt = $this->pdo->prepare($bonusSql);
            $bonusStmt->execute([$studentId, $months]);
            $student['bonus_history'] = $bonusStmt->fetchAll(\PDO::FETCH_ASSOC);

            return $student;
        } catch (\Exception $e) {
            $this->log("ERROR getting student attendance summary: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Log debug messages
     */
    private function log($message) {
        if ($this->DEBUG) {
            error_log('[MonthlyAttendanceEngine] ' . $message);
        }
    }
}
