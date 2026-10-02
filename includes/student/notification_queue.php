<?php
/**
 * NotificationQueue - Process and send queued notifications
 * 
 * Responsibilities:
 * - Find pending notifications to send
 * - Send emails via existing mm_send_email()
 * - Track delivery status
 * - Handle retries
 * - Log failures
 */

class NotificationQueue {
    private $pdo;
    private $notificationTemplate;
    private $DEBUG = false;

    public function __construct(\PDO $pdo, $debug = false) {
        $this->pdo = $pdo;
        $this->DEBUG = $debug;
        require_once __DIR__ . '/notification_template.php';
        $this->notificationTemplate = new NotificationTemplate($pdo, $debug);
    }

    /**
     * Process pending notifications and send emails
     * 
     * @param array $settings Settings with email config (from mm_load_settings)
     * @param int $limit Max notifications to process
     * @return array Results
     */
    public function processPendingNotifications($settings, $limit = 100) {
        try {
            $results = [
                'success' => true,
                'total_pending' => 0,
                'sent' => 0,
                'failed' => 0,
                'skipped' => 0,
                'errors' => []
            ];

            // Get pending notifications from queue view
            $notifications = $this->getPendingNotifications($limit);
            $results['total_pending'] = count($notifications);

            foreach ($notifications as $notification) {
                $sendResult = $this->sendNotificationEmail($notification, $settings);

                if ($sendResult['success']) {
                    $results['sent']++;
                } else {
                    if ($sendResult['reason'] === 'skipped') {
                        $results['skipped']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = [
                            'notification_id' => $notification['id'],
                            'student_id' => $notification['student_id'],
                            'error' => $sendResult['error']
                        ];
                    }
                }
            }

            return $results;
        } catch (\Exception $e) {
            $this->log("ERROR processing pending notifications: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'sent' => 0,
                'failed' => 0
            ];
        }
    }

    /**
     * Get pending notifications from queue
     * 
     * @param int $limit Max notifications
     * @return array Notifications ready to send
     */
    private function getPendingNotifications($limit = 100) {
        try {
            $sql = "SELECT * FROM v_notification_email_queue LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$limit]);
            
            $notifications = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($notifications as &$notif) {
                if (!empty($notif['data'])) {
                    $notif['data'] = json_decode($notif['data'], true);
                }
            }

            return $notifications;
        } catch (\Exception $e) {
            $this->log("ERROR getting pending notifications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Send a single notification email
     * 
     * @param array $notification Notification record
     * @param array $settings Email settings
     * @return array Result with success, reason, error
     */
    private function sendNotificationEmail($notification, $settings) {
        try {
            $studentId = $notification['student_id'];
            $notificationType = $notification['notification_type'];
            $email = $notification['email'];
            $fullName = $notification['full_name'];
            $data = $notification['data'] ?? [];

            // Check user preferences
            if (!$this->checkUserPreference($notification, $notificationType)) {
                $this->log("Email disabled in preferences for student {$studentId}");
                return [
                    'success' => true,
                    'reason' => 'skipped',
                    'message' => 'Email disabled in preferences'
                ];
            }

            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->recordFailure($notification['id'], "Invalid email address: {$email}", true);
                return [
                    'success' => false,
                    'reason' => 'invalid_email',
                    'error' => 'Invalid email address'
                ];
            }

            // Render email content
            $subject = $this->notificationTemplate->renderEmailSubject($notificationType, $data);
            $htmlBody = $this->notificationTemplate->renderEmailBody($notificationType, $data);

            // Send email (requires mm_send_email function to be available)
            if (!function_exists('mm_send_email')) {
                throw new \Exception("mm_send_email function not available");
            }

            $emailSent = mm_send_email($settings, $email, $fullName, $subject, $htmlBody);

            if ($emailSent) {
                $this->markEmailAsSent($notification['id']);
                $this->log("Email sent for notification {$notification['id']} to student {$studentId}");
                return [
                    'success' => true,
                    'reason' => 'sent',
                    'message' => 'Email sent'
                ];
            } else {
                $this->recordFailure($notification['id'], "mm_send_email returned false");
                return [
                    'success' => false,
                    'reason' => 'send_failed',
                    'error' => 'Email send failed'
                ];
            }
        } catch (\Exception $e) {
            $this->recordFailure($notification['id'], $e->getMessage());
            $this->log("ERROR sending email for notification: " . $e->getMessage());
            return [
                'success' => false,
                'reason' => 'exception',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if user has email enabled for this type
     * 
     * @param array $notification
     * @param string $type
     * @return bool Preference enabled
     */
    private function checkUserPreference($notification, $type) {
        // Map notification type to preference column
        $preferenceMap = [
            'task_assigned' => 'email_task_assigned',
            'submission_approved' => 'email_submission_approved',
            'promotion_achieved' => 'email_promotion_achieved',
            'badge_earned' => 'email_badge_earned',
            'penalty_applied' => 'email_penalty_applied',
            'certificate_issued' => 'email_certificate_issued'
        ];

        if (!isset($preferenceMap[$type])) {
            return true; // Default to send if unknown type
        }

        $prefColumn = $preferenceMap[$type];
        
        // Check preference value from notification record (from view)
        return !empty($notification[$prefColumn]);
    }

    /**
     * Mark notification email as sent
     * 
     * @param int $notificationId
     * @return bool Success
     */
    private function markEmailAsSent($notificationId) {
        try {
            $sql = "UPDATE sa_notifications 
                    SET email_sent = 1, 
                        email_sent_at = NOW(),
                        email_retry_count = 0,
                        email_last_error = NULL
                    WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$notificationId]);
        } catch (\Exception $e) {
            $this->log("ERROR marking email as sent: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record email failure
     * 
     * @param int $notificationId
     * @param string $error Error message
     * @param bool $permanent Permanent failure (don't retry)
     * @return bool Success
     */
    private function recordFailure($notificationId, $error = '', $permanent = false) {
        try {
            if ($permanent) {
                // Mark as sent to avoid infinite retries
                $sql = "UPDATE sa_notifications 
                        SET email_sent = 1, 
                            email_last_error = ?,
                            email_retry_count = 999
                        WHERE id = ?";
            } else {
                // Increment retry counter
                $sql = "UPDATE sa_notifications 
                        SET email_retry_count = email_retry_count + 1,
                            email_last_error = ?
                        WHERE id = ?";
            }
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$error, $notificationId]);
        } catch (\Exception $e) {
            $this->log("ERROR recording failure: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retry failed emails
     * 
     * @param array $settings Email settings
     * @param int $limit Max to retry
     * @return array Results
     */
    public function retryFailedEmails($settings, $limit = 50) {
        try {
            $results = [
                'success' => true,
                'retried' => 0,
                'sent' => 0,
                'failed' => 0
            ];

            // Get failed notifications with retry count < 3
            $sql = "SELECT * FROM v_notification_email_queue WHERE email_retry_count < 3 LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$limit]);
            
            $notifications = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($notifications as $notification) {
                $results['retried']++;
                
                $sendResult = $this->sendNotificationEmail($notification, $settings);
                if ($sendResult['success']) {
                    $results['sent']++;
                } else {
                    $results['failed']++;
                }
            }

            return $results;
        } catch (\Exception $e) {
            $this->log("ERROR retrying failed emails: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get queue statistics
     * 
     * @return array Queue stats
     */
    public function getQueueStats() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN email_sent = 0 THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN email_sent = 1 THEN 1 ELSE 0 END) as sent,
                        SUM(CASE WHEN email_retry_count > 0 AND email_sent = 0 THEN 1 ELSE 0 END) as failed_retrying
                    FROM sa_notifications";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->log("ERROR getting queue stats: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get queue statistics by type
     * 
     * @return array Stats by notification type
     */
    public function getQueueStatsByType() {
        try {
            $sql = "SELECT 
                        notification_type,
                        COUNT(*) as total,
                        SUM(CASE WHEN email_sent = 0 THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN email_sent = 1 THEN 1 ELSE 0 END) as sent
                    FROM sa_notifications
                    GROUP BY notification_type
                    ORDER BY total DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->log("ERROR getting queue stats by type: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clean stuck notifications (not sent after 7 days)
     * 
     * @param int $daysOld Default 7 days
     * @return int Marked as sent (to prevent indefinite retries)
     */
    public function cleanStuckNotifications($daysOld = 7) {
        try {
            $sql = "UPDATE sa_notifications 
                    SET email_sent = 1, 
                        email_retry_count = 999
                    WHERE email_sent = 0 
                    AND email_retry_count >= 3
                    AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$daysOld]);
            return $stmt->rowCount();
        } catch (\Exception $e) {
            $this->log("ERROR cleaning stuck notifications: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Log debug messages
     */
    private function log($message) {
        if ($this->DEBUG) {
            error_log('[NotificationQueue] ' . $message);
        }
    }
}
