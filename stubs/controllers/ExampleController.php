<?php

namespace App\Controllers;

use AdzWP\Core\Controller;

class ExampleController extends Controller
{
    public $actions = [
        'init' => 'initialize',
        'wp_enqueue_scripts' => 'enqueueScripts'
    ];

    public $filters = [
        'the_content' => 'modifyContent'
    ];

    /**
     * Controller constructor - set up plugin lifecycle hooks
     */
    public function __construct()
    {
        parent::__construct();
        
        // Example: Register plugin lifecycle hooks from within a controller
        $this->setupPluginHooks();
    }

    protected function bootstrap()
    {
        // Additional initialization if needed
    }

    public function initialize()
    {
        // WordPress initialization logic
        if ($this->isAdmin()) {
            // Admin-specific initialization
        }
        
        if ($this->isFrontend()) {
            // Frontend-specific initialization
        }
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueueScripts()
    {
        // Enqueue your scripts and styles here
        wp_enqueue_script('example-script', plugin_dir_url(__FILE__) . '../assets/js/main.js', ['jquery'], '1.0.0', true);
        wp_enqueue_style('example-style', plugin_dir_url(__FILE__) . '../assets/css/main.css', [], '1.0.0');
    }

    /**
     * Modify post content
     */
    public function modifyContent($content)
    {
        // Example: Add custom content to posts
        return $content . '<p><!-- Powered by ADZ Framework --></p>';
    }
    
    /**
     * Set up plugin lifecycle hooks
     * 
     * This demonstrates how you can register hooks from within controllers
     * or any other part of your plugin code.
     */
    protected function setupPluginHooks()
    {
        // Register install hook
        \AdzWP\Core\Plugin::onInstall([$this, 'onPluginInstall']);
        
        // Register activate hook
        \AdzWP\Core\Plugin::onActivate([$this, 'onPluginActivate']);
        
        // Register deactivate hook  
        \AdzWP\Core\Plugin::onDeactivate([$this, 'onPluginDeactivate']);
        
        // Register uninstall hook
        \AdzWP\Core\Plugin::onUninstall([$this, 'onPluginUninstall']);
    }
    
    /**
     * Handle plugin installation
     */
    public function onPluginInstall()
    {
        // Example: Create custom database table
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'example_data';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            data text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
    
    /**
     * Handle plugin activation
     */
    public function onPluginActivate()
    {
        // Example: Set up default options
        add_option('example_plugin_version', '1.0.0');
        add_option('example_plugin_settings', [
            'enabled' => true,
            'api_key' => ''
        ]);
        
        // Example: Schedule cron job
        if (!wp_next_scheduled('example_daily_task')) {
            wp_schedule_event(time(), 'daily', 'example_daily_task');
        }
    }
    
    /**
     * Handle plugin deactivation
     */
    public function onPluginDeactivate()
    {
        // Example: Clear scheduled events
        wp_clear_scheduled_hook('example_daily_task');
        
        // Example: Clear caches
        wp_cache_flush();
    }
    
    /**
     * Handle plugin uninstall
     */
    public function onPluginUninstall()
    {
        // Example: Remove database table
        global $wpdb;
        $table_name = $wpdb->prefix . 'example_data';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        // Example: Remove options
        delete_option('example_plugin_version');
        delete_option('example_plugin_settings');
    }
}