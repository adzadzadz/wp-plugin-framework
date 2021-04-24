<?php 

class ADZ {

  public static $env;

  public static $path;

  /**
   * Mod Config Object
   *
   * @var [type]
   */
  public static $conf;

  public static $assets_path;

  public static $js_path;

  public static $css_path;

  /**
   * THe WP Plugin instance
   *
   * @var [type]
   */
  public static $plugin;

  public static function pluginize( $path, $env = 'default' )
  {
    ADZ::$path = plugin_dir_path( $path );
    ADZ::$env = $env;
    ADZ::$conf = new \AdzHive\Config();
    require_once ADZ::$path . "project/" . ADZ::$env . "/bootstrap.php";
    return ADZ::$plugin = ( new \AdzWP\Plugin() );
  }

  public static function build( String $class )
  {
    return ( new $class );
  }
  
}