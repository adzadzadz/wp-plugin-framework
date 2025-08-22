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
\Adz\Core\ADZ::pluginize(__FILE__, 'default');