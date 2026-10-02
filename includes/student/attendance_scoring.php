<?php
/**
 * AttendanceScorer - Calculate attendance metrics for students
 * 
 * Responsibilities:
 * - Calculate monthly attendance percentages
 * - Count eligible and attended days
 * - Manage attendance thresholds
 * - Support multiple threshold configurations
 */

class AttendanceScorer {
    private $pdo;
    private $DEBUG = false;

    public function __construct(\PDO $pdo, $debug = false) {
        $this->pdo = $pdo;
        $this->DEBUG = $debug;
    }

    /**
     * Calculate complete monthly attendance metrics for a student
     * 
     * @param int $studentId Student ID
     * @param int $year Year (YYYY)
     * @param int $month Month (1-12)
     * @return array ['attended' => int, 'eligible' => int, 'percentage' => float, 'status' => string]
     */
    public function calculateMonthlyAttendance($studentId, $year, $month) {
        try {
            $attended = $this->getAttendedDaysInMonth($studentId, $year, $month);
            $eligible = $this->getEligibleDaysInMonth($year, $month);
            
            if ($eligible <= 0) {
                return [
                    'attended' => 0,
                    'eligible' => 0,
                    'percentage' => 0.00,
                    'status' => 'no_data'
                ];
            }

            $percentage = ($attended / $eligible) * 100;
            
            return [
                'attended' => $attended,
                'eligible' => $eligible,
                'percentage' => round($percentage, 2),
                'status' => 'success'
            ];
        } catch (\Exception $e) {
            $this->log("ERROR calculating attendance for student {$studentId}: " . $e->getMessage());
            return [
                'attended' => 0,
                'eligible' => 0,
                'percentage' => 0.00,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get count of days attended in a month
     * 
     * @param int $studentId
     * @param int $year
     * @param int $month
     * @return int Number of attendance records (days attended)
     */
    public function getAttendedDaysInMonth($studentId, $year, $month) {
        try {
            // Count distinct dates where student marked attendance
            $sql = "SELECT COUNT(DISTINCT DATE(attendance_date)) as days_attended
                    FROM sa_attendance_logs
                    WHERE student_id = ?
                    AND YEAR(attendance_date) = ?
                    AND MONTH(attendance_date) = ?
                    AND status = 'Present'";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$studentId, $year, $month]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return (int)($result['days_attended'] ?? 0);
        } catch (\Exception $e) {
            $this->log("ERROR getting attended days: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get eligible working days in a month (Fridays for weekly check-ins)
     * 
     * Algorithm:
     * - Count all Fridays in the month (primary check-in day)
     * - Add optional weekday events if configured
     * 
     * @param int $year
     * @param int $month
     * @return int Number of eligible days (typically 4-5 Fridays)
     */
    public function getEligibleDaysInMonth($year, $month) {
        try {
            // Count Fridays in the month (assuming Friday is check-in day)
            // Date calculations for number of each weekday in month
            $firstDay = new \DateTime("{$year}-{$month}-01");
            $lastDay = new \DateTime("{$year}-{$month}-" . date('t', mktime(0, 0, 0, $month, 1, $year)));
            
            $fridayCount = 0;
            $currentDate = clone $firstDay;
            
            while ($currentDate <= $lastDay) {
                // 5 = Friday in PHP DateTime (0=Monday, 1=Tuesday, ..., 4=Friday, ...)
                if ($currentDate->format('w') == 5) { // 5 = Friday
                    $fridayCount++;
                }
                $currentDate->modify('+1 day');
            }
            
            // Minimum 4 Fridays per month (worst case)
            // Maximum 5 Fridays per month
            return max($fridayCount, 4); // Ensure at least 4
        } catch (\Exception $e) {
            $this->log("ERROR calculating eligible days: " . $e->getMessage());
            return 4; // Default fallback
        }
    }

    /**
     * Check if attendance percentage meets a specific threshold
     * 
     * @param float $percentage Attendance percentage (0-100)
     * @param float $threshold Threshold percentage (e.g., 90.00)
     * @return bool True if percentage >= threshold
     */
    public function checkAttendanceThreshold($percentage, $threshold) {
        return (float)$percentage >= (float)$threshold;
    }

    /**
     * Get the applicable bonus for a given attendance percentage
     * Returns the highest threshold that the percentage meets
     * 
     * @param float $percentage Attendance percentage
     * @return array|null Threshold row from DB or null if no match
     */
    public function getApplicableBonus($percentage) {
        try {
            // Get highest applicable threshold (ORDER BY DESC LIMIT 1)
            $sql = "SELECT id, threshold_name, percentage_required, bonus_points, min_eligible_days
                    FROM attendance_thresholds
                    WHERE is_active = 1
                    AND ? >= percentage_required
                    ORDER BY percentage_required DESC
                    LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$percentage]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (\Exception $e) {
            $this->log("ERROR getting applicable bonus: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all active thresholds sorted by percentage
     * 
     * @return array Array of threshold configurations
     */
    public function getAllThresholds() {
        try {
            $sql = "SELECT id, threshold_name, percentage_required, bonus_points, min_eligible_days, is_active
                    FROM attendance_thresholds
                    WHERE is_active = 1
                    ORDER BY percentage_required ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->log("ERROR getting thresholds: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate attendance data quality for a month
     * Checks for reasonable attendance patterns
     * 
     * @param array $metrics Result from calculateMonthlyAttendance()
     * @return array ['is_valid' => bool, 'warnings' => array, 'issues' => array]
     */
    public function validateAttendanceData($metrics) {
        $validation = [
            'is_valid' => true,
            'warnings' => [],
            'issues' => []
        ];

        // Check if any attendance was recorded
        if ($metrics['eligible'] <= 0) {
            $validation['issues'][] = 'No eligible days in month';
            $validation['is_valid'] = false;
        }

        // Check for suspicious 100% if too few days
        if ($metrics['percentage'] == 100 && $metrics['attended'] < 2) {
            $validation['warnings'][] = 'Perfect attendance with very few days recorded';
        }

        // Check for impossible values
        if ($metrics['attended'] > $metrics['eligible']) {
            $validation['issues'][] = 'Days attended exceeds eligible days (data error)';
            $validation['is_valid'] = false;
        }

        return $validation;
    }

    /**
     * Get current (this month) attendance for a student
     * 
     * @param int $studentId
     * @return array Same format as calculateMonthlyAttendance()
     */
    public function getCurrentMonthAttendance($studentId) {
        $now = new \DateTime('now');
        return $this->calculateMonthlyAttendance($studentId, (int)$now->format('Y'), (int)$now->format('m'));
    }

    /**
     * Get previous month attendance for a student
     * 
     * @param int $studentId
     * @return array Same format as calculateMonthlyAttendance()
     */
    public function getPreviousMonthAttendance($studentId) {
        $date = new \DateTime('first day of last month');
        return $this->calculateMonthlyAttendance($studentId, (int)$date->format('Y'), (int)$date->format('m'));
    }

    /**
     * Get attendance trend for last N months
     * 
     * @param int $studentId
     * @param int $months Number of months to retrieve (default 6)
     * @return array Array of attendance metrics by month
     */
    public function getAttendanceTrend($studentId, $months = 6) {
        $trend = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = new \DateTime("-{$i} months");
            $year = (int)$date->format('Y');
            $month = (int)$date->format('m');
            
            $metrics = $this->calculateMonthlyAttendance($studentId, $year, $month);
            $metrics['year_month'] = $date->format('Y-m');
            $metrics['display'] = $date->format('M Y');
            
            $trend[] = $metrics;
        }
        
        return $trend;
    }

    /**
     * Calculate days remaining to reach a target attendance percentage for current month
     * 
     * @param int $studentId
     * @param float $targetPercentage Target attendance % (e.g., 90)
     * @return array ['days_needed' => int, 'days_remaining' => int, 'achievable' => bool]
     */
    public function daysNeededForTargetAttendance($studentId, $targetPercentage = 90) {
        try {
            $current = $this->getCurrentMonthAttendance($studentId);
            $attended = $current['attended'];
            $eligible = $current['eligible'];
            
            // Days remaining in month (Fridays from today to end of month)
            $now = new \DateTime('now');
            $monthEnd = new \DateTime('last day of this month');
            $daysRemaining = 0;
            
            while ($now <= $monthEnd) {
                if ($now->format('w') == 5) { // Friday
                    $daysRemaining++;
                }
                $now->modify('+1 day');
            }
            
            // Calculate days needed
            // Target: (attended + needed) / eligible >= target%
            // needed >= (target% * eligible - attended)
            $daysNeeded = ceil(($targetPercentage / 100 * $eligible) - $attended);
            $daysNeeded = max(0, $daysNeeded);
            
            return [
                'days_needed' => $daysNeeded,
                'days_remaining' => $daysRemaining,
                'achievable' => $daysNeeded <= $daysRemaining,
                'target_percentage' => $targetPercentage
            ];
        } catch (\Exception $e) {
            $this->log("ERROR calculating days needed: " . $e->getMessage());
            return [
                'days_needed' => 0,
                'days_remaining' => 0,
                'achievable' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Log debug messages
     */
    private function log($message) {
        if ($this->DEBUG) {
            error_log('[AttendanceScorer] ' . $message);
        }
    }
}
