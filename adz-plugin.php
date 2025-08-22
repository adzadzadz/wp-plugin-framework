<?php 
/**
 * Plugin Name: Your Plugin Name
 * Plugin URI: https://yoursite.com/
 * Description: Your plugin description here.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: your-plugin-textdomain
 * Author URI: https://yoursite.com/
 * Requires at least: 5.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 */

if ( !defined( 'ABSPATH' ) ) {
    die( 'Do not open this file directly.' );
}

require_once 'vendor/autoload.php';

( \ADZ::pluginize( __FILE__, $env = 'default' ) )->load([
    'Admin',
    'Frontend'
]);