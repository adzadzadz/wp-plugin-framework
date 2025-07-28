<?php

use AdzHive\Log;

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