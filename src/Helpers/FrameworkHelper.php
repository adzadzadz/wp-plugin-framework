<?php

namespace AdzWP\Helpers;

use AdzWP\Log;
use AdzWP\Exception;
use AdzWP\Security;

/**
 * Framework Helper Class
 * Provides utility methods for common WordPress plugin operations
 * 
 * @package AdzWP\Helpers
 */
class FrameworkHelper
{
    protected static $instance = null;
    protected static $optionCache = [];

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }

    /**
     * Log a message
     */
    public static function log($message, $level = 'info', array $context = [])
    {
        return Log::getInstance()->log($level, $message, $context);
    }

    /**
     * Handle exceptions with proper rendering
     */
    public static function handleException(\Throwable $exception)
    {
        if ($exception instanceof Exception) {
            $exception->render();
        } else {
            Log::getInstance()->critical($exception->getMessage(), [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                wp_die(
                    $exception->getMessage() . '<br><br><pre>' . $exception->getTraceAsString() . '</pre>',
                    'Critical Error',
                    ['response' => 500]
                );
            } else {
                wp_die(
                    'An error occurred. Please try again later.',
                    'Error',
                    ['response' => 500]
                );
            }
        }
    }

    /**
     * Get option with caching
     */
    public static function getOption($option, $default = false, $cache = true)
    {
        if ($cache && isset(self::$optionCache[$option])) {
            return self::$optionCache[$option];
        }
        
        $value = get_option($option, $default);
        
        if ($cache) {
            self::$optionCache[$option] = $value;
        }
        
        return $value;
    }

    /**
     * Update option
     */
    public static function updateOption($option, $value, $autoload = null)
    {
        // Clear cache
        unset(self::$optionCache[$option]);
        
        return update_option($option, $value, $autoload);
    }

    /**
     * Sanitize input using Security class
     */
    public static function sanitizeInput($input, $type = 'text')
    {
        return Security::getInstance()->sanitize($input, $type);
    }

    /**
     * Verify nonce
     */
    public static function verifyNonce($nonce, $action = -1, $dieOnFail = true)
    {
        $valid = wp_verify_nonce($nonce, $action);
        
        if (!$valid && $dieOnFail) {
            wp_die('Security check failed.', 'Error', ['response' => 403]);
        }
        
        return $valid;
    }

    /**
     * Check if current page is admin page
     */
    public static function isAdminPage($pageSlug = null)
    {
        if (!is_admin()) {
            return false;
        }
        
        if ($pageSlug === null) {
            return true;
        }
        
        $currentScreen = get_current_screen();
        
        if (!$currentScreen) {
            return false;
        }
        
        return strpos($currentScreen->id, $pageSlug) !== false;
    }

    /**
     * Enqueue asset with proper versioning
     */
    public static function enqueueAsset($handle, $src, $type = 'script', $deps = [], $version = null, $args = [])
    {
        $pluginUrl = defined('ADZ_PLUGIN_URL') ? ADZ_PLUGIN_URL : plugin_dir_url(__FILE__);
        $pluginVersion = defined('ADZ_PLUGIN_VERSION') ? ADZ_PLUGIN_VERSION : '1.0.0';
        
        $version = $version ?? $pluginVersion;
        
        if ($type === 'style') {
            wp_enqueue_style($handle, $pluginUrl . $src, $deps, $version, $args['media'] ?? 'all');
        } else {
            $inFooter = $args['in_footer'] ?? true;
            wp_enqueue_script($handle, $pluginUrl . $src, $deps, $version, $inFooter);
            
            if (isset($args['localize'])) {
                wp_localize_script($handle, $args['localize']['object'], $args['localize']['data']);
            }
        }
    }

    /**
     * Get current user role
     */
    public static function getCurrentUserRole()
    {
        $user = wp_get_current_user();
        
        if (!$user->exists()) {
            return null;
        }
        
        return $user->roles[0] ?? null;
    }

    /**
     * Array get with dot notation
     */
    public static function arrayGet($array, $key, $default = null)
    {
        if (!is_array($array)) {
            return $default;
        }
        
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        
        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }
        
        return $array;
    }

    /**
     * Array set with dot notation
     */
    public static function arraySet(&$array, $key, $value)
    {
        $keys = explode('.', $key);
        
        while (count($keys) > 1) {
            $key = array_shift($keys);
            
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            
            $array = &$array[$key];
        }
        
        $array[array_shift($keys)] = $value;
    }

    /**
     * Safe redirect
     */
    public static function redirect($location, $status = 302, $die = true)
    {
        wp_redirect($location, $status);
        
        if ($die) {
            exit;
        }
    }

    /**
     * Check if AJAX request
     */
    public static function isAjax()
    {
        return wp_doing_ajax();
    }

    /**
     * Check if REST request
     */
    public static function isRest()
    {
        return defined('REST_REQUEST') && REST_REQUEST;
    }

    /**
     * Get template with variable extraction
     */
    public static function getTemplate($templateName, $variables = [], $load = true)
    {
        $templatePath = apply_filters('adz_template_path', '');
        $defaultPath = defined('ADZ_PLUGIN_PATH') ? ADZ_PLUGIN_PATH . 'templates/' : '';
        
        $template = locate_template([$templatePath . $templateName]);
        
        if (!$template) {
            $template = $defaultPath . $templateName;
        }
        
        if (!file_exists($template)) {
            return false;
        }
        
        if (!empty($variables) && is_array($variables)) {
            extract($variables);
        }
        
        if ($load) {
            include $template;
            return true;
        }
        
        return $template;
    }

    /**
     * Format bytes to human readable format
     */
    public static function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Generate secure token
     */
    public static function generateToken($length = 32)
    {
        return Security::getInstance()->generateToken($length);
    }

    /**
     * Check user capability
     */
    public static function checkCapability($capability = 'manage_options', $userId = null)
    {
        return Security::getInstance()->checkCapability($capability, $userId);
    }
}