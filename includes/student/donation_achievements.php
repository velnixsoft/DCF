<?php
/**
 * Donation Achievement Engine
 * 
 * Manages donation-based milestone rewards and badge assignments.
 * Ensures no duplicate rewards via UNIQUE constraints and tracking tables.
 */

class DonationAchievementEngine {
    private $pdo;
    private $debug = false;

    public function __construct(PDO $pdo, $debug = false) {
        $this->pdo = $pdo;
        $this->debug = $debug;
    }

    /**
     * Process donation rewards for a specific student
     * 
     * @param int $studentId
     * @param int|null $donationId - Optional specific donation to process
     * @return array Result summary
     */
    public function processStudentRewards($studentId, $donationId = null) {
        try {
            $studentId = (int)$studentId;
            
            // Get student details
            $stmt = $this->pdo->prepare("SELECT id, total_points FROM sa_students WHERE id = ? LIMIT 1");
            $stmt->execute([$studentId]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                return ['success' => false, 'error' => 'Student not found', 'student_id' => $studentId];
            }
            
            // Process the points for this specific donation first if provided
            if ($donationId) {
                $this->processSingleDonationPoints($studentId, $donationId);
            }
            
            // Calculate cumulative verified donations for this student
            $totalDonated = $this->getStudentDonationTotal($studentId);
            
            if ($this->debug) {
                echo "[DEBUG] Student $studentId total donations: ₹$totalDonated\n";
            }
            
            // Get all active milestones
            $stmt = $this->pdo->query("SELECT * FROM donation_milestones WHERE is_active = 1 ORDER BY milestone_amount ASC");
            $milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $rewardsAwarded = [];
            
            foreach ($milestones as $milestone) {
                // Check if student has already achieved this milestone
                $stmt = $this->pdo->prepare("SELECT id FROM donation_achievements WHERE student_id = ? AND milestone_id = ? LIMIT 1");
                $stmt->execute([$studentId, $milestone['id']]);
                $alreadyAchieved = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // If total donations >= milestone amount and not already achieved, award reward
                if ($totalDonated >= $milestone['milestone_amount'] && !$alreadyAchieved) {
                    $rewardData = $this->awardMilestoneReward($studentId, $milestone, $totalDonated, $donationId);
                    if ($rewardData['success']) {
                        $rewardsAwarded[] = $rewardData;
                    }
                }
            }
            
            // Mark donation as processed if specified
            if ($donationId) {
                $stmt = $this->pdo->prepare("UPDATE donations SET achievement_processed = 1 WHERE id = ?");
                $stmt->execute([$donationId]);
            }
            
            return [
                'success' => true,
                'student_id' => $studentId,
                'total_donations' => $totalDonated,
                'rewards_awarded' => count($rewardsAwarded),
                'rewards' => $rewardsAwarded
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Award amount-proportional points for a specific verified donation.
     *
     * Base points formula:  max(1, floor(amount / 100))   → 1 pt per ₹100 donated, minimum 1 pt.
     * This ensures higher donation amounts always yield more points than many small donations.
     *
     * Per-donation tier bonuses (reward large individual contributions):
     *   ≥ ₹500   → +25 bonus pts  (DONATION_500_MILESTONE)
     *   ≥ ₹2,000 → +75 bonus pts  (DONATION_2000_MILESTONE)
     *   ≥ ₹5,000 → +150 bonus pts (DONATION_5000_MILESTONE)
     *
     * Cumulative milestone rewards (₹500 / ₹2,000 / ₹10,000 total) are handled
     * separately by processStudentRewards() via the donation_milestones table.
     *
     * @param int $studentId
     * @param int $donationId
     * @return array Result
     */
    public function processSingleDonationPoints($studentId, $donationId) {
        try {
            $studentId = (int)$studentId;
            $donationId = (int)$donationId;

            // Get donation details
            $stmt = $this->pdo->prepare("SELECT id, amount, payment_status FROM donations WHERE id = ? LIMIT 1");
            $stmt->execute([$donationId]);
            $donation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$donation || $donation['payment_status'] !== 'Success') {
                return ['success' => false, 'error' => 'Donation not found or not successful'];
            }

            $amount = (float)$donation['amount'];

            require_once __DIR__ . '/points.php';
            $pointEngine = new StudentPointEngine($this->pdo);

            // ─────────────────────────────────────────────────────────────────────
            // 1. BASE POINTS — Amount-proportional: 1 pt per ₹100, minimum 1 pt
            //    e.g. ₹1 → 1 pt | ₹500 → 5 pts | ₹2000 → 20 pts | ₹4000 → 40 pts
            //    This replaces the old flat 20 pts per donation (quantity-biased).
            // ─────────────────────────────────────────────────────────────────────
            $basePointsEarned = max(1, (int)floor($amount / 100));
            $baseDescription  = sprintf(
                'Donation ₹%s → %d pt%s (₹100/pt, min 1)',
                number_format($amount, 2),
                $basePointsEarned,
                $basePointsEarned === 1 ? '' : 's'
            );

            $resBase = $pointEngine->awardPoints($studentId, 'DONOR_GENERATED', [
                'source_type'  => 'donation',
                'source_id'    => $donationId,
                'base_points'  => $basePointsEarned,
                'points_delta' => $basePointsEarned,    // Override static DB default (was 20)
                'description'  => $baseDescription,
                'reference_code' => 'DON-BASE-' . $donationId,
                'idempotency_key' => 'don-base:' . $donationId,
            ]);

            // ─────────────────────────────────────────────────────────────────────
            // 2. TIER BONUS ≥ ₹500: DONATION_500_MILESTONE (+25 pts)
            //    Rewards the ambassador for securing a high single contribution.
            // ─────────────────────────────────────────────────────────────────────
            $resBonus500 = null;
            if ($amount >= 500) {
                $resBonus500 = $pointEngine->awardPoints($studentId, 'DONATION_500_MILESTONE', [
                    'source_type'    => 'donation',
                    'source_id'      => $donationId,
                    'description'    => sprintf('Single-donation tier bonus ≥ ₹500 (Amt: ₹%s)', number_format($amount, 2)),
                    'reference_code' => 'DON-BON500-' . $donationId,
                    'idempotency_key' => 'don-bon500:' . $donationId,
                ]);
            }

            // ─────────────────────────────────────────────────────────────────────
            // 3. TIER BONUS ≥ ₹2,000: DONATION_2000_MILESTONE (+75 pts)
            // ─────────────────────────────────────────────────────────────────────
            $resBonus2000 = null;
            if ($amount >= 2000) {
                $resBonus2000 = $pointEngine->awardPoints($studentId, 'DONATION_2000_MILESTONE', [
                    'source_type'    => 'donation',
                    'source_id'      => $donationId,
                    'description'    => sprintf('Single-donation tier bonus ≥ ₹2,000 (Amt: ₹%s)', number_format($amount, 2)),
                    'reference_code' => 'DON-BON2000-' . $donationId,
                    'idempotency_key' => 'don-bon2000:' . $donationId,
                ]);
            }

            // ─────────────────────────────────────────────────────────────────────
            // 4. TIER BONUS ≥ ₹5,000: DONATION_5000_MILESTONE (+150 pts)
            //    Rewards exceptional high-value donor contributions.
            //    Rule must exist in sa_point_rules with code 'DONATION_5000_MILESTONE'.
            // ─────────────────────────────────────────────────────────────────────
            $resBonus5000 = null;
            if ($amount >= 5000) {
                try {
                    $resBonus5000 = $pointEngine->awardPoints($studentId, 'DONATION_5000_MILESTONE', [
                        'source_type'    => 'donation',
                        'source_id'      => $donationId,
                        'description'    => sprintf('Single-donation tier bonus ≥ ₹5,000 (Amt: ₹%s)', number_format($amount, 2)),
                        'reference_code' => 'DON-BON5000-' . $donationId,
                        'idempotency_key' => 'don-bon5000:' . $donationId,
                    ]);
                } catch (RuntimeException $ex) {
                    // Rule may not yet exist in DB — log and continue gracefully
                    error_log('[DonationAchievements] DONATION_5000_MILESTONE rule missing: ' . $ex->getMessage());
                    $resBonus5000 = null;
                }
            }

            return [
                'success'     => true,
                'amount'      => $amount,
                'base_pts'    => $basePointsEarned,
                'base'        => $resBase,
                'bonus500'    => $resBonus500,
                'bonus2000'   => $resBonus2000,
                'bonus5000'   => $resBonus5000,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Award points/badge for milestone achievement
     * 
     * @param int $studentId
     * @param array $milestone Milestone data from donation_milestones table
     * @param float $cumulativeDonation Total donations by student
     * @param int|null $donationId Reference donation
     * @return array
     */
    private function awardMilestoneReward($studentId, $milestone, $cumulativeDonation, $donationId = null) {
        try {
            $this->pdo->beginTransaction();
            
            $pointsAwarded = 0;
            $badgeAwarded = null;
            
            // Award points if applicable
            if (in_array($milestone['reward_type'], ['points', 'both']) && $milestone['reward_points'] > 0) {
                $pointsAwarded = (int)$milestone['reward_points'];
                $ruleCode = $this->resolveMilestoneRuleCode($milestone);

                require_once __DIR__ . '/points.php';
                $pointEngine = new StudentPointEngine($this->pdo);
                $pointResult = $pointEngine->awardPoints((int)$studentId, $ruleCode, [
                    'source_type' => 'donation',
                    'source_id' => $donationId,
                    'base_points' => $pointsAwarded,
                    'points_delta' => $pointsAwarded,
                    'description' => 'Donation milestone: ' . ($milestone['milestone_name'] ?? $ruleCode),
                    'reference_code' => 'DON-MILE-' . (int)$milestone['id'] . '-' . $studentId,
                    'idempotency_key' => 'donation-milestone:' . (int)$milestone['id'] . ':' . $studentId,
                ]);

                if (empty($pointResult['success'])) {
                    throw new \Exception($pointResult['message'] ?? 'Failed to award donation milestone points');
                }

                $this->logActivity($studentId, "Donation milestone: {$milestone['milestone_name']}", $pointsAwarded, $donationId ?? 0, 'donation_milestone');
                
                if ($this->debug) {
                    echo "[DEBUG] Awarded $pointsAwarded points to student $studentId\n";
                }
            }
            
            // Award badge if applicable
            if (in_array($milestone['reward_type'], ['badge', 'both']) && !empty($milestone['badge_code'])) {
                $badgeAwarded = $this->awardBadge($studentId, $milestone['badge_code']);
                
                if ($this->debug) {
                    echo "[DEBUG] Awarded badge {$milestone['badge_code']} to student $studentId\n";
                }
            }
            
            // Record in achievement tracking table (duplicate prevention via UNIQUE constraint)
            $stmt = $this->pdo->prepare(
                "INSERT INTO donation_achievements 
                (student_id, milestone_id, milestone_amount, cumulative_donation_amount, reward_type, points_awarded, badge_awarded, reference_donation_id, processed_by_cron)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
                ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), processed_by_cron = 0"
            );
            $stmt->execute([
                $studentId,
                $milestone['id'],
                $milestone['milestone_amount'],
                $cumulativeDonation,
                $milestone['reward_type'],
                $pointsAwarded,
                $badgeAwarded,
                $donationId ?? null
            ]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'milestone_id' => $milestone['id'],
                'milestone_name' => $milestone['milestone_name'],
                'milestone_amount' => $milestone['milestone_amount'],
                'points_awarded' => $pointsAwarded,
                'badge_awarded' => $badgeAwarded
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Award badge to student
     * 
     * @param int $studentId
     * @param string $badgeCode
     * @return string|null Badge code or null
     */
    private function awardBadge($studentId, $badgeCode) {
        try {
            // Get badge by code
            $stmt = $this->pdo->prepare("SELECT id FROM sa_badges WHERE badge_code = ? LIMIT 1");
            $stmt->execute([$badgeCode]);
            $badge = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$badge) {
                if ($this->debug) {
                    echo "[DEBUG] Badge code not found: $badgeCode\n";
                }
                return null;
            }
            
            // Check if student already has this badge
            $stmt = $this->pdo->prepare(
                "SELECT id FROM sa_student_badges WHERE student_id = ? AND badge_id = ? LIMIT 1"
            );
            $stmt->execute([$studentId, $badge['id']]);
            
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                // Award badge
                $stmt = $this->pdo->prepare(
                    "INSERT INTO sa_student_badges (student_id, badge_id, assignment_type, badge_rule_id)
                     VALUES (?, ?, 'auto', NULL)"
                );
                $stmt->execute([$studentId, $badge['id']]);
            }
            
            return $badgeCode;
            
        } catch (Exception $e) {
            if ($this->debug) {
                echo "[DEBUG] Error awarding badge: " . $e->getMessage() . "\n";
            }
            return null;
        }
    }

    /**
     * Get cumulative verified donations for student
     * 
     * @param int $studentId
     * @return float Total verified donations amount
     */
    public function getStudentDonationTotal($studentId) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COALESCE(SUM(amount), 0) as total 
                 FROM donations 
                 WHERE sa_student_id = ? AND payment_status = 'Success'"
            );
            $stmt->execute([(int)$studentId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float)($result['total'] ?? 0);
        } catch (Exception $e) {
            if ($this->debug) {
                echo "[DEBUG] Error calculating donation total: " . $e->getMessage() . "\n";
            }
            return 0.0;
        }
    }

    /**
     * Log activity in sa_activity_logs
     * 
     * @param int $studentId
     * @param string $description
     * @param int $points
     * @param int $referenceId
     * @param string $referenceType
     */
    private function logActivity($studentId, $description, $points = 0, $referenceId = 0, $referenceType = 'donation_milestone') {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO sa_activity_logs (student_id, activity_type, description, points, reference_id, reference_type)
                 VALUES (?, 'reward', ?, ?, ?, ?)"
            );
            $stmt->execute([$studentId, $description, $points, $referenceId, $referenceType]);
        } catch (Exception $e) {
            if ($this->debug) {
                echo "[DEBUG] Error logging activity: " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * Process all pending donations for achievement rewards
     * 
     * @param int $limit Maximum donations to process in one batch
     * @return array Summary of processing
     */
    public function processBatchDonations($limit = 500) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT DISTINCT sa_student_id 
                 FROM donations 
                 WHERE sa_student_id IS NOT NULL 
                 AND achievement_processed = 0 
                 AND payment_status = 'Success'
                 LIMIT ?"
            );
            $stmt->execute([$limit]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $processed = 0;
            $totalRewards = 0;
            $errors = [];
            
            foreach ($students as $row) {
                $result = $this->processStudentRewards((int)$row['sa_student_id']);
                if ($result['success']) {
                    $processed++;
                    $totalRewards += $result['rewards_awarded'];
                } else {
                    $errors[] = $result;
                }
            }
            
            return [
                'success' => true,
                'students_processed' => $processed,
                'total_rewards_awarded' => $totalRewards,
                'errors' => count($errors),
                'error_details' => $errors
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get achievement history for a student
     * 
     * @param int $studentId
     * @return array Achievement records
     */
    public function getStudentAchievements($studentId) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT da.*, dm.milestone_name, dm.milestone_description, dm.milestone_amount
                 FROM donation_achievements da
                 JOIN donation_milestones dm ON da.milestone_id = dm.id
                 WHERE da.student_id = ?
                 ORDER BY da.achieved_at DESC"
            );
            $stmt->execute([(int)$studentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get donation progress towards next milestone for a student
     * 
     * @param int $studentId
     * @return array Progress info
     */
    public function getDonationProgress($studentId) {
        try {
            $totalDonated = $this->getStudentDonationTotal($studentId);
            
            // Get next unachieved milestone
            $stmt = $this->pdo->prepare(
                "SELECT dm.* FROM donation_milestones dm
                 WHERE dm.is_active = 1
                 AND dm.milestone_amount > ?
                 AND NOT EXISTS (
                     SELECT 1 FROM donation_achievements da 
                     WHERE da.student_id = ? AND da.milestone_id = dm.id
                 )
                 ORDER BY dm.milestone_amount ASC
                 LIMIT 1"
            );
            $stmt->execute([$totalDonated, (int)$studentId]);
            $nextMilestone = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get all achieved milestones for this student
            $stmt = $this->pdo->prepare(
                "SELECT dm.* FROM donation_milestones dm
                 WHERE dm.is_active = 1
                 AND EXISTS (
                     SELECT 1 FROM donation_achievements da 
                     WHERE da.student_id = ? AND da.milestone_id = dm.id
                 )
                 ORDER BY dm.milestone_amount ASC"
            );
            $stmt->execute([(int)$studentId]);
            $achievedMilestones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $progress = [
                'total_donated' => $totalDonated,
                'achieved_milestones' => $achievedMilestones,
                'next_milestone' => $nextMilestone
            ];
            
            if ($nextMilestone) {
                $progress['amount_needed'] = $nextMilestone['milestone_amount'] - $totalDonated;
                $progress['progress_percentage'] = round(($totalDonated / $nextMilestone['milestone_amount']) * 100);
            }
            
            return $progress;
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function resolveMilestoneRuleCode(array $milestone): string
    {
        $amount = (int)($milestone['milestone_amount'] ?? 0);
        if ($amount >= 2000) {
            return 'DONATION_2000_MILESTONE';
        }
        if ($amount >= 500) {
            return 'DONATION_500_MILESTONE';
        }
        return 'DONOR_GENERATED';
    }
}
