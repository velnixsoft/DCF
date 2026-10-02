<?php
/**
 * NotificationTemplate - Template rendering and variable substitution
 * 
 * Responsibilities:
 * - Load templates from database
 * - Render templates with variable substitution
 * - Cache templates in memory
 * - Manage template variables
 */

class NotificationTemplate {
    private $pdo;
    private $templates = []; // Cache
    private $DEBUG = false;

    public function __construct(\PDO $pdo, $debug = false) {
        $this->pdo = $pdo;
        $this->DEBUG = $debug;
    }

    /**
     * Get template by type
     * 
     * @param string $type Notification type
     * @return array|null Template record
     */
    public function getTemplate($type) {
        // Check cache first
        if (isset($this->templates[$type])) {
            return $this->templates[$type];
        }

        try {
            $sql = "SELECT * FROM notification_templates 
                    WHERE notification_type = ? 
                    LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$type]);
            $template = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($template) {
                $this->templates[$type] = $template;
            }

            return $template;
        } catch (\Exception $e) {
            $this->log("ERROR loading template: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Render email subject with variable substitution
     * 
     * @param string $type Notification type
     * @param array $variables Variable mapping
     * @return string Rendered subject
     */
    public function renderEmailSubject($type, $variables = []) {
        try {
            $template = $this->getTemplate($type);
            if (!$template) {
                return "Notification from " . ($variables['site_name'] ?? 'Our Platform');
            }

            return $this->substituteVariables($template['email_subject_template'], $variables);
        } catch (\Exception $e) {
            $this->log("ERROR rendering email subject: " . $e->getMessage());
            return "Notification";
        }
    }

    /**
     * Render email body with variable substitution
     * 
     * @param string $type Notification type
     * @param array $variables Variable mapping
     * @return string Rendered HTML body
     */
    public function renderEmailBody($type, $variables = []) {
        try {
            $template = $this->getTemplate($type);
            if (!$template) {
                return "<p>You have a new notification.</p>";
            }

            $html = $this->substituteVariables($template['email_body_template'], $variables);
            
            // Wrap in email-safe HTML
            return $this->wrapEmailTemplate($html, $variables);
        } catch (\Exception $e) {
            $this->log("ERROR rendering email body: " . $e->getMessage());
            return "<p>You have a new notification.</p>";
        }
    }

    /**
     * Render dashboard message with variable substitution
     * 
     * @param string $type Notification type
     * @param array $variables Variable mapping
     * @return string Rendered message
     */
    public function renderDashboardMessage($type, $variables = []) {
        try {
            $template = $this->getTemplate($type);
            if (!$template) {
                return "You have a new notification";
            }

            return $this->substituteVariables($template['dashboard_message_template'], $variables);
        } catch (\Exception $e) {
            $this->log("ERROR rendering dashboard message: " . $e->getMessage());
            return "You have a new notification";
        }
    }

    /**
     * Render dashboard title with variable substitution
     * 
     * @param string $type Notification type
     * @param array $variables Variable mapping
     * @return string Rendered title
     */
    public function renderDashboardTitle($type, $variables = []) {
        try {
            $template = $this->getTemplate($type);
            if (!$template) {
                return "New Notification";
            }

            return $this->substituteVariables($template['dashboard_title_template'], $variables);
        } catch (\Exception $e) {
            $this->log("ERROR rendering dashboard title: " . $e->getMessage());
            return "New Notification";
        }
    }

    /**
     * Get template metadata (icon, color, etc)
     * 
     * @param string $type Notification type
     * @return array Metadata
     */
    public function getTemplateMetadata($type) {
        try {
            $template = $this->getTemplate($type);
            if (!$template) {
                return [
                    'icon' => 'bell',
                    'color_class' => 'info',
                    'is_enabled' => false
                ];
            }

            return [
                'icon' => $template['icon'] ?? 'bell',
                'color_class' => $template['color_class'] ?? 'info',
                'is_enabled' => $template['is_enabled'] ?? false
            ];
        } catch (\Exception $e) {
            return ['icon' => 'bell', 'color_class' => 'info', 'is_enabled' => false];
        }
    }

    /**
     * Get all templates
     * 
     * @return array All templates
     */
    public function getAllTemplates() {
        try {
            $sql = "SELECT * FROM notification_templates ORDER BY notification_type";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->log("ERROR getting all templates: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update template
     * 
     * @param string $type
     * @param array $updates
     * @return bool Success
     */
    public function updateTemplate($type, $updates) {
        try {
            $allowed = ['email_subject_template', 'email_body_template', 'dashboard_title_template', 'dashboard_message_template', 'icon', 'color_class', 'is_enabled'];
            
            $setClauses = [];
            $values = [];
            
            foreach ($updates as $key => $value) {
                if (!in_array($key, $allowed)) {
                    continue;
                }
                $setClauses[] = "{$key} = ?";
                $values[] = $value;
            }

            if (empty($setClauses)) {
                return false;
            }

            $values[] = $type;
            $sql = "UPDATE notification_templates SET " . implode(", ", $setClauses) . " WHERE notification_type = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute($values);

            // Clear cache
            unset($this->templates[$type]);

            return $success;
        } catch (\Exception $e) {
            $this->log("ERROR updating template: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Substitute variables in template string
     * 
     * Variables are in {variable_name} format
     * 
     * @param string $template Template string
     * @param array $variables Variable mapping
     * @return string Rendered template
     */
    private function substituteVariables($template, $variables = []) {
        // Default variables
        $defaults = [
            'site_name' => 'Our Platform',
            'current_year' => date('Y'),
            'current_date' => date('Y-m-d')
        ];

        $vars = array_merge($defaults, $variables);
        
        // Replace {variable_name} with value
        foreach ($vars as $key => $value) {
            $placeholder = '{' . $key . '}';
            $template = str_replace($placeholder, (string)$value, $template);
        }

        // Remove any unreplaced placeholders
        $template = preg_replace('/\{[a-zA-Z_][a-zA-Z0-9_]*\}/', '', $template);

        return $template;
    }

    /**
     * Wrap content in email-safe HTML template
     * 
     * @param string $content Content HTML
     * @param array $variables For site name, etc
     * @return string Complete email HTML
     */
    private function wrapEmailTemplate($content, $variables = []) {
        $siteName = $variables['site_name'] ?? 'Our Platform';
        $year = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .email-container { max-width: 600px; margin: 0 auto; background: #f9f9f9; }
        .email-header { background: #1e40af; color: white; padding: 20px; text-align: center; }
        .email-content { background: white; padding: 30px; }
        .email-footer { background: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        a { color: #1e40af; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>{$siteName}</h1>
        </div>
        <div class="email-content">
            {$content}
        </div>
        <div class="email-footer">
            <p>&copy; {$year} {$siteName}. All rights reserved.</p>
            <p>Manage your notification preferences in your account settings.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get sample data for a notification type
     * 
     * @param string $type Notification type
     * @return array Sample variables
     */
    public static function getSampleData($type) {
        $samples = [
            'task_assigned' => [
                'student_name' => 'John Doe',
                'task_title' => 'Community Cleanup Project',
                'task_description' => 'Organize and participate in a community cleanup event',
                'due_date' => date('Y-m-d', strtotime('+7 days')),
                'points_available' => '50'
            ],
            'submission_approved' => [
                'student_name' => 'John Doe',
                'task_title' => 'Community Cleanup Project',
                'points_earned' => '50',
                'feedback' => 'Great work! You organized a successful cleanup event.'
            ],
            'promotion_achieved' => [
                'student_name' => 'John Doe',
                'old_level' => 'Senior Intern',
                'new_level' => 'Campus Ambassador',
                'benefits' => 'Leadership opportunities, increased points multiplier (1.5x)'
            ],
            'badge_earned' => [
                'student_name' => 'John Doe',
                'badge_name' => 'Volunteer Champion',
                'badge_description' => 'Volunteered for 10+ events',
                'points_earned' => '25'
            ],
            'penalty_applied' => [
                'student_name' => 'John Doe',
                'penalty_type' => 'Warning',
                'reason' => 'Missed event without notice',
                'points_deducted' => '10'
            ],
            'certificate_issued' => [
                'student_name' => 'John Doe',
                'certificate_type' => 'Volunteer Certificate',
                'issue_date' => date('Y-m-d')
            ]
        ];

        return $samples[$type] ?? [];
    }

    /**
     * Log debug messages
     */
    private function log($message) {
        if ($this->DEBUG) {
            error_log('[NotificationTemplate] ' . $message);
        }
    }
}
