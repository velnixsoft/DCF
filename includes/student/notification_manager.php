<?php
/**
 * NotificationManager - Core notification service
 * 
 * Responsibilities:
 * - Create notifications
 * - Mark as read/unread
 * - Retrieve notification history
 * - Get unread counts
 * - Delete notifications
 */

class NotificationManager {
    private $pdo;
    private $DEBUG = false;

    public function __construct(\PDO $pdo, $debug = false) {
        $this->pdo = $pdo;
        $this->DEBUG = $debug;
    }

    /**
     * Create a new notification
     * 
     * @param int $studentId
     * @param string $type (task_assigned, submission_approved, promotion_achieved, badge_earned, penalty_applied, certificate_issued)
     * @param string $title
     * @param string $message
     * @param array $data Context data for template variables
     * @return array Created notification or error
     */
    public function createNotification($studentId, $type, $title, $message, $data = []) {
        try {
            // Validate student exists
            $checkSql = "SELECT id FROM sa_students WHERE id = ? LIMIT 1";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$studentId]);
            if ($checkStmt->rowCount() === 0) {
                throw new \Exception("Student not found");
            }

            // Validate notification type exists
            $typeSql = "SELECT id FROM notification_templates WHERE notification_type = ? AND is_enabled = 1 LIMIT 1";
            $typeStmt = $this->pdo->prepare($typeSql);
            $typeStmt->execute([$type]);
            if ($typeStmt->rowCount() === 0) {
                throw new \Exception("Notification type not enabled or not found");
            }

            // Create notification
            $sql = "INSERT INTO sa_notifications 
                    (student_id, notification_type, title, message, data, is_read, email_sent, created_at)
                    VALUES (?, ?, ?, ?, ?, 0, 0, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $dataJson = empty($data) ? NULL : json_encode($data);
            $stmt->execute([$studentId, $type, $title, $message, $dataJson]);
            
            $notificationId = $this->pdo->lastInsertId();
            $this->log("Created notification {$notificationId} for student {$studentId}");

            return [
                'success' => true,
                'notification_id' => $notificationId,
                'message' => 'Notification created'
            ];
        } catch (\Exception $e) {
            $this->log("ERROR creating notification: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Mark notification as read
     * 
     * @param int $notificationId
     * @param int $studentId (for ownership validation)
     * @return bool Success
     */
    public function markAsRead($notificationId, $studentId = null) {
        try {
            // Verify ownership if studentId provided
            if ($studentId !== null) {
                $checkSql = "SELECT id FROM sa_notifications WHERE id = ? AND student_id = ? LIMIT 1";
                $checkStmt = $this->pdo->prepare($checkSql);
                $checkStmt->execute([$notificationId, $studentId]);
                if ($checkStmt->rowCount() === 0) {
                    throw new \Exception("Unauthorized or notification not found");
                }
            }

            $sql = "UPDATE sa_notifications 
                    SET is_read = 1, read_at = NOW() 
                    WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$notificationId]);
        } catch (\Exception $e) {
            $this->log("ERROR marking as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all notifications as read for a student
     * 
     * @param int $studentId
     * @return int Number of affected rows
     */
    public function markAllAsRead($studentId) {
        try {
            $sql = "UPDATE sa_notifications 
                    SET is_read = 1, read_at = NOW() 
                    WHERE student_id = ? AND is_read = 0";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$studentId]);
            return $stmt->rowCount();
        } catch (\Exception $e) {
            $this->log("ERROR marking all as read: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mark notification as unread
     * 
     * @param int $notificationId
     * @param int $studentId (for ownership validation)
     * @return bool Success
     */
    public function markAsUnread($notificationId, $studentId = null) {
        try {
            if ($studentId !== null) {
                $checkSql = "SELECT id FROM sa_notifications WHERE id = ? AND student_id = ? LIMIT 1";
                $checkStmt = $this->pdo->prepare($checkSql);
                $checkStmt->execute([$notificationId, $studentId]);
                if ($checkStmt->rowCount() === 0) {
                    throw new \Exception("Unauthorized or notification not found");
                }
            }

            $sql = "UPDATE sa_notifications 
                    SET is_read = 0, read_at = NULL 
                    WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$notificationId]);
        } catch (\Exception $e) {
            $this->log("ERROR marking as unread: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get notifications for a student
     * 
     * @param int $studentId
     * @param int $limit
     * @param int $offset
     * @param string $filter 'all', 'unread', 'read'
     * @return array Notifications
     */
    public function getNotifications($studentId, $limit = 20, $offset = 0, $filter = 'all') {
        try {
            $whereClause = "WHERE student_id = ?";
            $params = [$studentId];

            if ($filter === 'unread') {
                $whereClause .= " AND is_read = 0";
            } elseif ($filter === 'read') {
                $whereClause .= " AND is_read = 1";
            }

            $sql = "SELECT id, student_id, notification_type, title, message, data, 
                           is_read, read_at, email_sent, created_at
                    FROM sa_notifications 
                    {$whereClause}
                    ORDER BY created_at DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->pdo->prepare($sql);
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            
            $notifications = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Parse JSON data
            foreach ($notifications as &$notif) {
                if (!empty($notif['data'])) {
                    $notif['data'] = json_decode($notif['data'], true);
                }
            }

            return $notifications;
        } catch (\Exception $e) {
            $this->log("ERROR getting notifications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get unread notification count
     * 
     * @param int $studentId
     * @return int Unread count
     */
    public function getUnreadCount($studentId) {
        try {
            $sql = "SELECT COUNT(*) as unread_count 
                    FROM sa_notifications 
                    WHERE student_id = ? AND is_read = 0";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$studentId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return (int)($result['unread_count'] ?? 0);
        } catch (\Exception $e) {
            $this->log("ERROR getting unread count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get unread notifications summary
     * 
     * @param int $studentId
     * @return array Summary with unread count and latest notification
     */
    public function getUnreadSummary($studentId) {
        try {
            $sql = "SELECT 
                        COUNT(*) as unread_count,
                        COUNT(DISTINCT notification_type) as notification_types,
                        MAX(created_at) as latest_notification_at
                    FROM sa_notifications 
                    WHERE student_id = ? AND is_read = 0";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$studentId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return [
                'unread_count' => (int)($result['unread_count'] ?? 0),
                'notification_types' => (int)($result['notification_types'] ?? 0),
                'latest_at' => $result['latest_notification_at'] ?? null
            ];
        } catch (\Exception $e) {
            $this->log("ERROR getting unread summary: " . $e->getMessage());
            return ['unread_count' => 0, 'notification_types' => 0, 'latest_at' => null];
        }
    }

    /**
     * Get recent notifications for dashboard
     * 
     * @param int $studentId
     * @param int $limit Default 5
     * @return array Recent notifications
     */
    public function getRecentNotifications($studentId, $limit = 5) {
        return $this->getNotifications($studentId, $limit, 0, 'all');
    }

    /**
     * Delete a notification
     * 
     * @param int $notificationId
     * @param int $studentId (for ownership validation)
     * @return bool Success
     */
    public function deleteNotification($notificationId, $studentId = null) {
        try {
            if ($studentId !== null) {
                $checkSql = "SELECT id FROM sa_notifications WHERE id = ? AND student_id = ? LIMIT 1";
                $checkStmt = $this->pdo->prepare($checkSql);
                $checkStmt->execute([$notificationId, $studentId]);
                if ($checkStmt->rowCount() === 0) {
                    throw new \Exception("Unauthorized or notification not found");
                }
            }

            $sql = "DELETE FROM sa_notifications WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$notificationId]);
        } catch (\Exception $e) {
            $this->log("ERROR deleting notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete all notifications for a student
     * 
     * @param int $studentId
     * @return int Rows deleted
     */
    public function deleteAllNotifications($studentId) {
        try {
            $sql = "DELETE FROM sa_notifications WHERE student_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$studentId]);
            return $stmt->rowCount();
        } catch (\Exception $e) {
            $this->log("ERROR deleting all notifications: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get notification statistics
     * 
     * @param int $studentId
     * @return array Statistics
     */
    public function getNotificationStats($studentId) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_notifications,
                        SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count,
                        SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_count,
                        SUM(CASE WHEN email_sent = 1 THEN 1 ELSE 0 END) as emailed_count,
                        MIN(created_at) as oldest_notification,
                        MAX(created_at) as newest_notification
                    FROM sa_notifications 
                    WHERE student_id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$studentId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->log("ERROR getting stats: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get notifications by type
     * 
     * @param int $studentId
     * @param string $type
     * @param int $limit
     * @return array Notifications of specific type
     */
    public function getNotificationsByType($studentId, $type, $limit = 10) {
        try {
            $sql = "SELECT id, student_id, notification_type, title, message, data, 
                           is_read, read_at, email_sent, created_at
                    FROM sa_notifications 
                    WHERE student_id = ? AND notification_type = ?
                    ORDER BY created_at DESC
                    LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$studentId, $type, $limit]);
            
            $notifications = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($notifications as &$notif) {
                if (!empty($notif['data'])) {
                    $notif['data'] = json_decode($notif['data'], true);
                }
            }

            return $notifications;
        } catch (\Exception $e) {
            $this->log("ERROR getting notifications by type: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get notification by ID
     * 
     * @param int $notificationId
     * @param int $studentId (for ownership validation)
     * @return array|null Notification or null
     */
    public function getNotification($notificationId, $studentId = null) {
        try {
            $sql = "SELECT id, student_id, notification_type, title, message, data, 
                           is_read, read_at, email_sent, created_at
                    FROM sa_notifications 
                    WHERE id = ?";
            
            $params = [$notificationId];
            
            if ($studentId !== null) {
                $sql .= " AND student_id = ?";
                $params[] = $studentId;
            }

            $sql .= " LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $notif = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($notif && !empty($notif['data'])) {
                $notif['data'] = json_decode($notif['data'], true);
            }

            return $notif;
        } catch (\Exception $e) {
            $this->log("ERROR getting notification: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Clean old read notifications (archive)
     * 
     * @param int $daysOld Default 90 days
     * @return int Deleted count
     */
    public function cleanOldNotifications($daysOld = 90) {
        try {
            $sql = "DELETE FROM sa_notifications 
                    WHERE is_read = 1 
                    AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                    AND email_sent = 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$daysOld]);
            return $stmt->rowCount();
        } catch (\Exception $e) {
            $this->log("ERROR cleaning old notifications: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Log debug messages
     */
    private function log($message) {
        if ($this->DEBUG) {
            error_log('[NotificationManager] ' . $message);
        }
    }
}
