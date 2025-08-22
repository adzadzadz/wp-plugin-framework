<?php

// Framework bootstrap for testing

require_once __DIR__ . '/../vendor/autoload.php';

// Define test constants
define('ADZ_TEST_MODE', true);
define('ADZ_TEST_DIR', __DIR__);
define('ADZ_FRAMEWORK_DIR', dirname(__DIR__));

// Mock WordPress functions for testing
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://example.com/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($data) {
        return strip_tags($data, '<p><a><strong><em><ul><ol><li><br><h1><h2><h3><h4><h5><h6>');
    }
}