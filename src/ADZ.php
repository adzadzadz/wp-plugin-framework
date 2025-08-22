<?php 

namespace Adz\WP;

class ADZ {

  public static $env;

  public static $path;

  /**
   * Config Object
   *
   * @var \Adz\Core\Config
   */
  public static $conf;

  public static $assets_path;

  public static $js_path;

  public static $css_path;

  /**
   * The WP Plugin instance
   *
   * @var \Adz\Core\Plugin
   */
  public static $plugin;

  public static function pluginize( $path, $env = 'default' )
  {
    ADZ::$path = plugin_dir_path( $path );
    ADZ::$env = $env;
    ADZ::$conf = new \Adz\Core\Config();
    require_once ADZ::$path . "config/" . $env . ".php";
    return ADZ::$plugin = ( new \Adz\Core\Plugin() );
  }

  public static function build( String $class )
  {
    return ( new $class );
  }
  
}