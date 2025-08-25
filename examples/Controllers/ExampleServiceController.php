<?php

namespace App\Controllers;

use AdzWP\Core\Controller;
use App\Services\UserService;
use App\Services\EmailService;

/**
 * Example Controller demonstrating Service usage
 * 
 * Shows how to use Services in Controllers for clean separation of concerns
 */
class ExampleServiceController extends Controller
{
    /**
     * Initialize services and register hooks
     */
    public function actionWpInit()
    {
        // Initialize services
        new UserService();
        new EmailService();
    }

    /**
     * Handle user registration - demonstrates service usage
     */
    public function actionUserRegister($userId)
    {
        // Access UserService via magic property
        $displayName = $this->userService->getDisplayName($userId);
        
        // Access EmailService and send welcome email
        $this->emailService->sendUserNotification($userId, 'welcome', [
            'welcome_message' => "Welcome to our site, {$displayName}!"
        ]);
        
        // Store welcome flag using UserService
        $this->userService->updateUserMeta($userId, 'welcome_sent', true);
    }

    /**
     * Admin menu - shows service integration
     */
    public function actionAdminMenu()
    {
        add_menu_page(
            'User Management',
            'Users',
            'manage_options',
            'user-management',
            [$this, 'renderUserManagementPage']
        );
    }

    /**
     * Handle AJAX request for user notifications
     */
    public function actionWpAjaxSendUserNotification()
    {
        // Verify nonce
        if (!$this->verifyNonce($_POST['nonce'] ?? '', 'send_notification')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!$this->currentUserCan('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $userId = intval($_POST['user_id'] ?? 0);
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        
        if (!$userId || !$message) {
            wp_send_json_error('Missing required fields');
        }
        
        // Use services for business logic
        $success = $this->userService->sendNotification($userId, 'Admin Notification', $message);
        
        if ($success) {
            wp_send_json_success('Notification sent successfully');
        } else {
            wp_send_json_error('Failed to send notification');
        }
    }

    /**
     * Filter user display name - shows service usage in filters
     */
    public function filterDisplayName($displayName, $userId, $originalUser)
    {
        // Use UserService to enhance display name
        if ($this->userService->hasCapability($userId, 'administrator')) {
            return '[Admin] ' . $displayName;
        }
        
        return $displayName;
    }

    /**
     * Process email queue via cron
     */
    public function actionProcessEmailQueue()
    {
        // Only run if we have the email service
        if (!$this->emailService) {
            return;
        }
        
        $result = $this->emailService->processEmailQueue(5);
        
        // Log results
        error_log("Email queue processed: {$result['processed']} sent, {$result['remaining']} remaining");
    }

    /**
     * Render user management page
     */
    public function renderUserManagementPage()
    {
        $currentPage = intval($_GET['paged'] ?? 1);
        $usersData = $this->userService->getUsersByRole('subscriber', $currentPage, 20);
        
        echo '<div class="wrap">';
        echo '<h1>User Management</h1>';
        
        echo '<div class="tablenav top">';
        echo '<div class="alignleft actions">';
        echo '<button type="button" class="button" onclick="sendBulkNotification()">Send Bulk Notification</button>';
        echo '</div>';
        echo '</div>';
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Name</th>';
        echo '<th>Email</th>';
        echo '<th>Registered</th>';
        echo '<th>Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($usersData['users'] as $user) {
            $displayName = $this->userService->getDisplayName($user->ID);
            echo '<tr>';
            echo '<td>' . $user->ID . '</td>';
            echo '<td>' . esc_html($displayName) . '</td>';
            echo '<td>' . esc_html($user->user_email) . '</td>';
            echo '<td>' . esc_html($user->user_registered) . '</td>';
            echo '<td>';
            echo '<button type="button" class="button button-small" onclick="sendNotification(' . $user->ID . ')">Send Notification</button>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        
        // Pagination
        $totalPages = $usersData['total_pages'];
        if ($totalPages > 1) {
            echo '<div class="tablenav bottom">';
            echo '<div class="tablenav-pages">';
            
            for ($i = 1; $i <= $totalPages; $i++) {
                $class = ($i === $currentPage) ? 'current' : '';
                $url = add_query_arg('paged', $i);
                echo '<a class="page-numbers ' . $class . '" href="' . esc_url($url) . '">' . $i . '</a>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Add JavaScript for AJAX functionality
        echo '<script>
            function sendNotification(userId) {
                const message = prompt("Enter notification message:");
                if (!message) return;
                
                const data = {
                    action: "send_user_notification",
                    user_id: userId,
                    message: message,
                    nonce: "' . $this->createNonce('send_notification') . '"
                };
                
                fetch(ajaxurl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: new URLSearchParams(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert("Notification sent successfully!");
                    } else {
                        alert("Error: " + result.data);
                    }
                })
                .catch(error => {
                    alert("Network error: " + error);
                });
            }
            
            function sendBulkNotification() {
                alert("Bulk notification feature - implement as needed");
            }
        </script>';
    }
}