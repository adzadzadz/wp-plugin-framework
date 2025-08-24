<?php

namespace App\Services;

use AdzWP\Core\Service;

/**
 * User Service - handles user-related business logic
 * 
 * Services contain reusable business logic that can be shared
 * across Controllers, Models, and other Services.
 */
class UserService extends Service
{
    /**
     * Get user display name with fallback
     */
    public function getDisplayName(int $userId): string
    {
        $user = get_userdata($userId);
        
        if (!$user) {
            return 'Unknown User';
        }
        
        return $user->display_name ?: $user->user_login;
    }

    /**
     * Check if user has specific capability
     */
    public function hasCapability(int $userId, string $capability): bool
    {
        $user = get_userdata($userId);
        
        if (!$user) {
            return false;
        }
        
        return user_can($user, $capability);
    }

    /**
     * Get user meta with caching
     */
    public function getUserMeta(int $userId, string $key, $default = null)
    {
        static $cache = [];
        
        $cacheKey = "{$userId}_{$key}";
        
        if (!isset($cache[$cacheKey])) {
            $value = get_user_meta($userId, $key, true);
            $cache[$cacheKey] = $value ?: $default;
        }
        
        return $cache[$cacheKey];
    }

    /**
     * Update user meta safely
     */
    public function updateUserMeta(int $userId, string $key, $value): bool
    {
        // Validate user exists
        if (!get_userdata($userId)) {
            return false;
        }
        
        // Sanitize based on key type
        $value = $this->sanitizeMetaValue($key, $value);
        
        return update_user_meta($userId, $key, $value) !== false;
    }

    /**
     * Get users by role with pagination
     */
    public function getUsersByRole(string $role, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        
        $users = get_users([
            'role' => $role,
            'number' => $perPage,
            'offset' => $offset,
            'orderby' => 'registered',
            'order' => 'DESC'
        ]);
        
        return [
            'users' => $users,
            'total' => count_users()['avail_roles'][$role] ?? 0,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil((count_users()['avail_roles'][$role] ?? 0) / $perPage)
        ];
    }

    /**
     * Send notification to user
     */
    public function sendNotification(int $userId, string $subject, string $message, string $type = 'info'): bool
    {
        $user = get_userdata($userId);
        
        if (!$user) {
            return false;
        }
        
        // Store notification in user meta
        $notifications = $this->getUserMeta($userId, 'notifications', []);
        
        $notifications[] = [
            'id' => uniqid(),
            'subject' => sanitize_text_field($subject),
            'message' => sanitize_textarea_field($message),
            'type' => sanitize_key($type),
            'created_at' => current_time('mysql'),
            'read' => false
        ];
        
        // Keep only last 50 notifications
        if (count($notifications) > 50) {
            $notifications = array_slice($notifications, -50);
        }
        
        return $this->updateUserMeta($userId, 'notifications', $notifications);
    }

    /**
     * Sanitize meta value based on key
     */
    private function sanitizeMetaValue(string $key, $value)
    {
        switch ($key) {
            case 'email':
                return sanitize_email($value);
            case 'url':
            case 'website':
                return esc_url_raw($value);
            case 'phone':
                return preg_replace('/[^0-9\-\+\(\)\s]/', '', $value);
            default:
                if (is_string($value)) {
                    return sanitize_text_field($value);
                }
                return $value;
        }
    }
}