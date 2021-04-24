<?php 
/**
 * Plugin Name: The Toolbox
 * Plugin URI: https://www.adriansaycon.com/
 * Description: The client's helper tool. Tons of features to come.
 * Version: 1.0.0
 * Author: Adrian T Saycon <adzbite@gmail.com>
 * Text Domain: the-toolbox
 * Author URI: https://www.adriansaycon.com/
 */

if ( !defined( 'ABSPATH' ) ) {
  die( 'Do not open this file directly.' );
}
require_once 'vendor/autoload.php';

( \ADZ::pluginize( __FILE__, $env = 'default', ) )->load([
  'Admin',
  // 'WPIE',
  // 'ACF',
  'Frontend'
]);