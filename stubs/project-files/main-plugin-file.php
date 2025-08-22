<?php
/**
 * Plugin Name: Your Plugin Name
 * Plugin URI: https://yourwebsite.com
 * Description: A WordPress plugin built with ADZ Framework
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: your-plugin-textdomain
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('YOUR_PLUGIN_VERSION', '1.0.0');
define('YOUR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('YOUR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load Composer autoloader
require_once YOUR_PLUGIN_PATH . 'vendor/autoload.php';

// Initialize the plugin
// Using the new framework structure:
// 1. Get the main Adz framework instance
$framework = \Adz::config();

// 2. Set up your plugin configuration
$framework->set('plugin.path', YOUR_PLUGIN_PATH);
$framework->set('plugin.url', YOUR_PLUGIN_URL);
$framework->set('plugin.version', YOUR_PLUGIN_VERSION);

// 3. Initialize plugin manager with lifecycle hooks
$pluginManager = \AdzWP\Core\PluginManager::getInstance(__FILE__);

// 4. Set up plugin dependencies (optional)
$pluginManager->setDependencies([
    [
        'slug' => 'woocommerce/woocommerce.php',
        'name' => 'WooCommerce',
        'source' => 'repo'
    ]
    // Add more dependencies as needed
]);

// 5. Set up plugin lifecycle hooks
$pluginManager
    // Install hook - runs when plugin is first installed
    ->onInstall(function() {
        // Create custom database tables
        // Set up default options
        // Any one-time setup tasks
    })
    
    // Activate hook - runs when plugin is activated
    ->onActivate(function() {
        // Check system requirements
        // Create/update database schema
        // Set up cron jobs
        // Clear caches
    })
    
    // Deactivate hook - runs when plugin is deactivated
    ->onDeactivate(function() {
        // Clear cron jobs
        // Clear caches
        // Temporary cleanup (don't delete data)
    })
    
    // Uninstall hook - runs when plugin is deleted
    ->onUninstall(function() {
        // Remove database tables
        // Remove options
        // Complete cleanup
    })
    
    // Helper methods for common tasks
    ->setupOptions([
        'your_plugin_option' => 'default_value',
        'your_plugin_settings' => []
    ])
    ->setupCapabilities([
        'manage_your_plugin',
        'edit_your_plugin_items'
    ]);

// 6. Bootstrap your plugin components
// Example: Initialize your controllers
// new App\Controllers\ExampleController();