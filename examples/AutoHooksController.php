<?php

namespace App\Controllers;

use AdzWP\Core\Controller;

/**
 * Example controller demonstrating automatic hook registration
 * 
 * This controller shows how methods are automatically registered as WordPress
 * actions and filters based on their naming convention:
 * - Methods starting with 'action' become WordPress actions
 * - Methods starting with 'filter' become WordPress filters
 */
class AutoHooksController extends Controller
{
    /**
     * Automatically registered as 'wp_init' action with priority 20
     * Using priority parameter (recommended approach)
     */
    public function actionWpInit($priority = 20)
    {
        // This will be called on wp_init hook with priority 20
        // The $priority parameter is excluded from WordPress arguments
        if ($this->isAdmin()) {
            $this->setupAdminFeatures();
        }
    }

    /**
     * Automatically registered as 'admin_menu' action
     */
    public function actionAdminMenu()
    {
        add_menu_page(
            'My Plugin',
            'My Plugin',
            'manage_options',
            'my-plugin',
            [$this, 'renderAdminPage']
        );
    }

    /**
     * Automatically registered as 'wp_enqueue_scripts' action
     */
    public function actionWpEnqueueScripts()
    {
        wp_enqueue_script(
            'my-plugin-script',
            plugin_dir_url(__FILE__) . 'assets/script.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }

    /**
     * Automatically registered as 'wp_ajax_my_custom_action' action
     */
    public function actionWpAjaxMyCustomAction()
    {
        // Handle AJAX request
        $data = [
            'message' => 'Hello from AJAX!',
            'timestamp' => current_time('timestamp')
        ];
        
        wp_send_json_success($data);
    }

    /**
     * Automatically registered as 'the_title' filter with custom priority
     * Using priority parameter for better IDE support
     */
    public function filterTheTitle($title, $post_id, $priority = 15)
    {
        // Modify the title for specific post types
        // WordPress receives $title and $post_id (2 arguments)
        // $priority parameter is automatically excluded
        if (get_post_type($post_id) === 'my_custom_post_type') {
            return '[CUSTOM] ' . $title;
        }
        
        return $title;
    }

    /**
     * Automatically registered as 'the_content' filter
     */
    public function filterTheContent($content)
    {
        // Add custom content to posts
        if (is_single() && in_the_loop() && is_main_query()) {
            $custom_content = '<div class="my-plugin-notice">Enhanced by My Plugin</div>';
            return $content . $custom_content;
        }
        
        return $content;
    }

    /**
     * Automatically registered as 'wp_mail' filter with custom priority
     * Demonstrates priority parameter with complex WordPress arguments
     */
    public function filterWpMail($args, $priority = 5)
    {
        // Modify email parameters before sending
        // WordPress receives only $args (1 argument)
        // $priority parameter is automatically excluded
        if (!isset($args['headers'])) {
            $args['headers'] = [];
        }
        
        $args['headers'][] = 'X-Mailer: My Plugin v1.0';
        
        return $args;
    }

    /**
     * Regular method - NOT automatically registered as a hook
     */
    public function setupAdminFeatures()
    {
        // This is just a regular method called by actionWpInit
        add_action('admin_notices', [$this, 'showAdminNotice']);
    }

    /**
     * Regular method for rendering admin page
     */
    public function renderAdminPage()
    {
        echo '<div class="wrap">';
        echo '<h1>My Plugin Settings</h1>';
        echo '<p>This page was created automatically!</p>';
        echo '</div>';
    }

    /**
     * Regular method for showing admin notices
     */
    public function showAdminNotice()
    {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>My Plugin is active and working!</p>';
        echo '</div>';
    }
}

/**
 * Usage Example:
 * 
 * // Simply instantiate the controller - hooks are registered automatically!
 * new AutoHooksController();
 * 
 * // The controller will automatically register these hooks:
 * // - add_action('wp_init', [controller, 'actionWpInit'], 20, 0)
 * // - add_action('admin_menu', [controller, 'actionAdminMenu'], 10, 0) 
 * // - add_action('wp_enqueue_scripts', [controller, 'actionWpEnqueueScripts'], 10, 0)
 * // - add_action('wp_ajax_my_custom_action', [controller, 'actionWpAjaxMyCustomAction'], 10, 0)
 * // - add_filter('the_title', [controller, 'filterTheTitle'], 10, 2)
 * // - add_filter('the_content', [controller, 'filterTheContent'], 10, 1)
 * // - add_filter('wp_mail', [controller, 'filterWpMail'], 5, 1)
 * 
 * No manual hook registration required!
 */