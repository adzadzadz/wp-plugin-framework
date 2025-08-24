<?php

namespace App\Services;

use AdzWP\Core\Service;

/**
 * Email Service - handles email functionality
 * 
 * Demonstrates service dependencies and business logic separation
 */
class EmailService extends Service
{
    /**
     * Service dependencies - automatically resolved
     */
    protected function dependencies(): array
    {
        return [
            'user' // UserService will be auto-injected
        ];
    }

    /**
     * Send email with template support
     */
    public function sendEmail(string $to, string $subject, string $template, array $data = []): bool
    {
        // Render email template
        $content = $this->renderTemplate($template, $data);
        
        // Set up email headers
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->getFromAddress()
        ];
        
        // Send email
        return wp_mail($to, $subject, $content, $headers);
    }

    /**
     * Send notification email to user
     */
    public function sendUserNotification(int $userId, string $template, array $data = []): bool
    {
        $user = get_userdata($userId);
        
        if (!$user) {
            return false;
        }
        
        // Add user data to template variables
        $data['user'] = [
            'id' => $user->ID,
            'name' => $this->userService->getDisplayName($userId),
            'email' => $user->user_email,
            'login' => $user->user_login
        ];
        
        $subject = $this->getTemplateSubject($template, $data);
        
        return $this->sendEmail($user->user_email, $subject, $template, $data);
    }

    /**
     * Send bulk emails with rate limiting
     */
    public function sendBulkEmails(array $recipients, string $subject, string $template, array $data = []): array
    {
        $results = [];
        $delay = 1; // 1 second delay between emails
        
        foreach ($recipients as $recipient) {
            $email = is_array($recipient) ? $recipient['email'] : $recipient;
            $recipientData = is_array($recipient) ? array_merge($data, $recipient) : $data;
            
            $success = $this->sendEmail($email, $subject, $template, $recipientData);
            
            $results[] = [
                'email' => $email,
                'success' => $success,
                'sent_at' => current_time('mysql')
            ];
            
            // Rate limiting
            if ($delay > 0) {
                sleep($delay);
            }
        }
        
        return $results;
    }

    /**
     * Queue email for later sending
     */
    public function queueEmail(string $to, string $subject, string $template, array $data = [], int $priority = 10): bool
    {
        $emailQueue = get_option('adz_email_queue', []);
        
        $emailQueue[] = [
            'id' => uniqid(),
            'to' => $to,
            'subject' => $subject,
            'template' => $template,
            'data' => $data,
            'priority' => $priority,
            'queued_at' => current_time('mysql'),
            'attempts' => 0,
            'status' => 'pending'
        ];
        
        // Sort by priority (lower number = higher priority)
        usort($emailQueue, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        
        return update_option('adz_email_queue', $emailQueue);
    }

    /**
     * Process email queue
     */
    public function processEmailQueue(int $batchSize = 10): array
    {
        $emailQueue = get_option('adz_email_queue', []);
        $processed = [];
        $remaining = [];
        $count = 0;
        
        foreach ($emailQueue as $email) {
            if ($count >= $batchSize) {
                $remaining[] = $email;
                continue;
            }
            
            if ($email['status'] !== 'pending') {
                $remaining[] = $email;
                continue;
            }
            
            $success = $this->sendEmail(
                $email['to'],
                $email['subject'],
                $email['template'],
                $email['data']
            );
            
            $email['attempts']++;
            $email['status'] = $success ? 'sent' : 'failed';
            $email['processed_at'] = current_time('mysql');
            
            $processed[] = $email;
            
            if (!$success && $email['attempts'] < 3) {
                $email['status'] = 'pending'; // Retry
                $remaining[] = $email;
            }
            
            $count++;
        }
        
        update_option('adz_email_queue', $remaining);
        
        return [
            'processed' => count($processed),
            'remaining' => count($remaining),
            'results' => $processed
        ];
    }

    /**
     * Render email template
     */
    private function renderTemplate(string $template, array $data): string
    {
        // Simple template rendering - replace {{variable}} with values
        $templatePath = $this->getTemplatePath($template);
        
        if (file_exists($templatePath)) {
            ob_start();
            extract($data);
            include $templatePath;
            return ob_get_clean();
        }
        
        // Fallback to simple string replacement
        $content = $this->getTemplateContent($template);
        
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $content = str_replace('{{' . $key . '}}', $value, $content);
            }
        }
        
        return $content;
    }

    /**
     * Get template file path
     */
    private function getTemplatePath(string $template): string
    {
        $pluginPath = \Adz::config()->get('plugin.path', '');
        return $pluginPath . '/templates/emails/' . $template . '.php';
    }

    /**
     * Get template content (fallback)
     */
    private function getTemplateContent(string $template): string
    {
        $templates = [
            'welcome' => '<h1>Welcome {{user.name}}!</h1><p>Thank you for joining our site.</p>',
            'notification' => '<h2>{{title}}</h2><p>{{message}}</p>',
            'password-reset' => '<h1>Password Reset</h1><p>Click <a href="{{reset_url}}">here</a> to reset your password.</p>'
        ];
        
        return $templates[$template] ?? '<p>{{message}}</p>';
    }

    /**
     * Get template subject
     */
    private function getTemplateSubject(string $template, array $data): string
    {
        $subjects = [
            'welcome' => 'Welcome to our site!',
            'notification' => $data['title'] ?? 'Notification',
            'password-reset' => 'Reset your password'
        ];
        
        return $subjects[$template] ?? 'Notification';
    }

    /**
     * Get from address for emails
     */
    private function getFromAddress(): string
    {
        $siteName = get_bloginfo('name');
        $adminEmail = get_option('admin_email');
        
        return "{$siteName} <{$adminEmail}>";
    }

    /**
     * Access UserService through dependency injection
     */
    public function __get(string $name)
    {
        if ($name === 'userService') {
            return $this->service('user');
        }
        
        return parent::__get($name);
    }
}