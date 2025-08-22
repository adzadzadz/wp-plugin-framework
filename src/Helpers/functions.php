<?php

use AdzFramework\Core\Log;

if (!function_exists('adz_log')) {
    function adz_log($message, $level = 'info', array $context = [])
    {
        return Log::getInstance()->log($level, $message, $context);
    }
}

if (!function_exists('adz_log_error')) {
    function adz_log_error($message, array $context = [])
    {
        return Log::getInstance()->error($message, $context);
    }
}

if (!function_exists('adz_log_warning')) {
    function adz_log_warning($message, array $context = [])
    {
        return Log::getInstance()->warning($message, $context);
    }
}

if (!function_exists('adz_log_info')) {
    function adz_log_info($message, array $context = [])
    {
        return Log::getInstance()->info($message, $context);
    }
}

if (!function_exists('adz_log_debug')) {
    function adz_log_debug($message, array $context = [])
    {
        return Log::getInstance()->debug($message, $context);
    }
}

if (!function_exists('adz_handle_exception')) {
    function adz_handle_exception(\Throwable $exception)
    {
        if ($exception instanceof \AdzHive\Exception) {
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
}

if (!function_exists('adz_get_option')) {
    function adz_get_option($option, $default = false, $cache = true)
    {
        static $cache_store = [];
        
        if ($cache && isset($cache_store[$option])) {
            return $cache_store[$option];
        }
        
        $value = get_option($option, $default);
        
        if ($cache) {
            $cache_store[$option] = $value;
        }
        
        return $value;
    }
}

if (!function_exists('adz_update_option')) {
    function adz_update_option($option, $value, $autoload = null)
    {
        return update_option($option, $value, $autoload);
    }
}

if (!function_exists('adz_sanitize_input')) {
    function adz_sanitize_input($input, $type = 'text')
    {
        switch ($type) {
            case 'email':
                return sanitize_email($input);
            case 'url':
                return esc_url_raw($input);
            case 'int':
                return absint($input);
            case 'float':
                return (float) $input;
            case 'textarea':
                return sanitize_textarea_field($input);
            case 'html':
                return wp_kses_post($input);
            case 'key':
                return sanitize_key($input);
            case 'slug':
                return sanitize_title($input);
            case 'text':
            default:
                return sanitize_text_field($input);
        }
    }
}

if (!function_exists('adz_verify_nonce')) {
    function adz_verify_nonce($nonce, $action = -1, $die_on_fail = true)
    {
        $valid = wp_verify_nonce($nonce, $action);
        
        if (!$valid && $die_on_fail) {
            wp_die('Security check failed.', 'Error', ['response' => 403]);
        }
        
        return $valid;
    }
}

if (!function_exists('adz_is_admin_page')) {
    function adz_is_admin_page($page_slug = null)
    {
        if (!is_admin()) {
            return false;
        }
        
        if ($page_slug === null) {
            return true;
        }
        
        $current_screen = get_current_screen();
        
        if (!$current_screen) {
            return false;
        }
        
        return strpos($current_screen->id, $page_slug) !== false;
    }
}

if (!function_exists('adz_enqueue_asset')) {
    function adz_enqueue_asset($handle, $src, $type = 'script', $deps = [], $version = null, $args = [])
    {
        $plugin_url = defined('ADZ_PLUGIN_URL') ? ADZ_PLUGIN_URL : plugin_dir_url(__FILE__);
        $plugin_version = defined('ADZ_PLUGIN_VERSION') ? ADZ_PLUGIN_VERSION : '1.0.0';
        
        $version = $version ?? $plugin_version;
        
        if ($type === 'style') {
            wp_enqueue_style($handle, $plugin_url . $src, $deps, $version, $args['media'] ?? 'all');
        } else {
            $in_footer = $args['in_footer'] ?? true;
            wp_enqueue_script($handle, $plugin_url . $src, $deps, $version, $in_footer);
            
            if (isset($args['localize'])) {
                wp_localize_script($handle, $args['localize']['object'], $args['localize']['data']);
            }
        }
    }
}

if (!function_exists('adz_get_current_user_role')) {
    function adz_get_current_user_role()
    {
        $user = wp_get_current_user();
        
        if (!$user->exists()) {
            return null;
        }
        
        return $user->roles[0] ?? null;
    }
}

if (!function_exists('adz_array_get')) {
    function adz_array_get($array, $key, $default = null)
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
}

if (!function_exists('adz_array_set')) {
    function adz_array_set(&$array, $key, $value)
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
}

if (!function_exists('adz_redirect')) {
    function adz_redirect($location, $status = 302, $die = true)
    {
        wp_redirect($location, $status);
        
        if ($die) {
            exit;
        }
    }
}

if (!function_exists('adz_is_ajax')) {
    function adz_is_ajax()
    {
        return defined('DOING_AJAX') && DOING_AJAX;
    }
}

if (!function_exists('adz_is_rest')) {
    function adz_is_rest()
    {
        return defined('REST_REQUEST') && REST_REQUEST;
    }
}

if (!function_exists('adz_get_template')) {
    function adz_get_template($template_name, $variables = [], $load = true)
    {
        $template_path = apply_filters('adz_template_path', '');
        $default_path = defined('ADZ_PLUGIN_PATH') ? ADZ_PLUGIN_PATH . 'templates/' : '';
        
        $template = locate_template([$template_path . $template_name]);
        
        if (!$template) {
            $template = $default_path . $template_name;
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
}

if (!function_exists('adz_format_bytes')) {
    function adz_format_bytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}