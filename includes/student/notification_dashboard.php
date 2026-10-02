<?php
/**
 * Notification Dashboard Widget
 * 
 * Displays recent notifications with unread count and quick actions
 */

// This file should be included from student-dashboard.php
if (!isset($pdo) || !isset($_SESSION['student_id'])) {
    http_response_code(403);
    die('Unauthorized access');
}

$studentId = $_SESSION['student_id'];

try {
    require_once __DIR__ . '/notification_manager.php';
    require_once __DIR__ . '/notification_template.php';
    
    $notificationManager = new NotificationManager($pdo);
    $notificationTemplate = new NotificationTemplate($pdo);
    
    // Get unread summary
    $unreadSummary = $notificationManager->getUnreadSummary($studentId);
    
    // Get recent notifications
    $recentNotifications = $notificationManager->getRecentNotifications($studentId, 5);
    
    // Get stats
    $stats = $notificationManager->getNotificationStats($studentId);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

?>

<!-- Notifications Dashboard Widget -->
<div class="notifications-widget" style="margin-top: 20px;">
    
    <!-- Quick Stats -->
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px;">
        <!-- Unread Count -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 32px; font-weight: bold;">
                <?php echo $unreadSummary['unread_count']; ?>
            </div>
            <div style="font-size: 12px; opacity: 0.9;">Unread Notifications</div>
        </div>
        
        <!-- Total Notifications -->
        <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 15px; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 32px; font-weight: bold;">
                <?php echo $stats['total_notifications'] ?? 0; ?>
            </div>
            <div style="font-size: 12px; opacity: 0.9;">Total Notifications</div>
        </div>
        
        <!-- Emailed -->
        <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 15px; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 32px; font-weight: bold;">
                <?php echo $stats['emailed_count'] ?? 0; ?>
            </div>
            <div style="font-size: 12px; opacity: 0.9;">Emailed</div>
        </div>
    </div>
    
    <!-- Recent Notifications -->
    <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0; font-size: 18px; color: #333;">Recent Notifications</h3>
            <a href="/notifications/index.php" style="color: #667eea; text-decoration: none; font-size: 12px;">View All</a>
        </div>
        
        <?php if (!empty($recentNotifications)): ?>
        <div style="border-top: 1px solid #eee; padding-top: 15px;">
            <?php foreach ($recentNotifications as $notification): ?>
            <div style="display: flex; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f0f0f0;" data-notification-id="<?php echo $notification['id']; ?>">
                <!-- Icon -->
                <div style="flex-shrink: 0;">
                    <i class="fas fa-bell" style="color: #667eea; font-size: 16px;"></i>
                </div>
                
                <!-- Content -->
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 600; color: #333; font-size: 14px;">
                        <?php echo htmlspecialchars($notification['title']); ?>
                    </div>
                    <div style="color: #666; font-size: 13px; margin-top: 3px;">
                        <?php echo htmlspecialchars(substr($notification['message'], 0, 100)); ?>...
                    </div>
                    <div style="color: #999; font-size: 11px; margin-top: 3px;">
                        <?php echo date('M d, H:i', strtotime($notification['created_at'])); ?>
                    </div>
                </div>
                
                <!-- Status Badge -->
                <div style="flex-shrink: 0; text-align: right;">
                    <?php if (!$notification['is_read']): ?>
                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #667eea; margin-right: 5px;"></span>
                    <span style="font-size: 11px; color: #999;">New</span>
                    <?php else: ?>
                    <span style="font-size: 11px; color: #999;">Read</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 30px; color: #999;">
            <p>No notifications yet. You're all caught up!</p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Action Buttons -->
    <div style="display: flex; gap: 10px; margin-top: 15px;">
        <?php if ($unreadSummary['unread_count'] > 0): ?>
        <button onclick="markAllNotificationsRead()" style="flex: 1; padding: 10px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600;">
            Mark All as Read
        </button>
        <?php endif; ?>
        <a href="/notifications/preferences.php" style="flex: 1; padding: 10px; text-align: center; background: #f0f0f0; color: #333; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none;">
            Preferences
        </a>
    </div>
</div>

<script>
function markAllNotificationsRead() {
    fetch('/notifications/mark-as-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ mark_all: true })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>

<style>
    .notifications-widget {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    }
    
    @media (max-width: 768px) {
        .notifications-widget > div:first-child {
            grid-template-columns: 1fr !important;
        }
    }
</style>
