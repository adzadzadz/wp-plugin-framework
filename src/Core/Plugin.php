<?php 
namespace AdzWP\Core;


Class Plugin extends Core {

  private $_cNamespace = "\\App\\Controllers\\";

  private $_installed = false;

  public static $controllers;
  
  const _ROLE = 'administrator';
  
  const _CAP = [
    'main_tools' => 'adz_manage_tools'
  ];

  protected static $install_hooks = [];
  protected static $uninstall_hooks = [];
  protected static $activate_hooks = [];
  protected static $deactivate_hooks = [];

  public function init()
  {
    Dependency::monitor_status();
  }

  /**
   * Register a custom install hook
   * 
   * @param callable $callback Function to run during plugin installation
   * @param int $priority Priority for hook execution
   */
  public static function onInstall( $callback, $priority = 10 ) {
    if ( !isset( self::$install_hooks[ $priority ] ) ) {
      self::$install_hooks[ $priority ] = [];
    }
    self::$install_hooks[ $priority ][] = $callback;
  }

  /**
   * Register a custom uninstall hook
   * 
   * @param callable $callback Function to run during plugin uninstallation
   * @param int $priority Priority for hook execution
   */
  public static function onUninstall( $callback, $priority = 10 ) {
    if ( !isset( self::$uninstall_hooks[ $priority ] ) ) {
      self::$uninstall_hooks[ $priority ] = [];
    }
    self::$uninstall_hooks[ $priority ][] = $callback;
  }

  /**
   * Register a custom activate hook
   * 
   * @param callable $callback Function to run during plugin activation
   * @param int $priority Priority for hook execution
   */
  public static function onActivate( $callback, $priority = 10 ) {
    if ( !isset( self::$activate_hooks[ $priority ] ) ) {
      self::$activate_hooks[ $priority ] = [];
    }
    self::$activate_hooks[ $priority ][] = $callback;
  }

  /**
   * Register a custom deactivate hook
   * 
   * @param callable $callback Function to run during plugin deactivation
   * @param int $priority Priority for hook execution
   */
  public static function onDeactivate( $callback, $priority = 10 ) {
    if ( !isset( self::$deactivate_hooks[ $priority ] ) ) {
      self::$deactivate_hooks[ $priority ] = [];
    }
    self::$deactivate_hooks[ $priority ][] = $callback;
  }

  /**
   * Execute install hooks and handle dependencies
   */
  public static function install()
  {
    // Install required dependencies first
    Dependency::install_required();
    
    // Execute custom install hooks
    self::executeHooks( self::$install_hooks );
    
    // Set installation flag
    update_option( 'adz_framework_installed', true );
    update_option( 'adz_framework_install_time', current_time( 'timestamp' ) );
  }

  /**
   * Execute uninstall hooks and cleanup
   */
  public static function uninstall()
  {
    // Execute custom uninstall hooks
    self::executeHooks( self::$uninstall_hooks );
    
    // Cleanup framework options
    delete_option( 'adz_framework_installed' );
    delete_option( 'adz_framework_install_time' );
    delete_option( 'adz_framework_version' );
    
    // Clear any cached data
    wp_cache_flush();
  }

  /**
   * Execute activate hooks
   */
  public static function activate()
  {
    // Check if plugin was previously installed
    if ( !get_option( 'adz_framework_installed' ) ) {
      self::install();
    }
    
    // Execute custom activate hooks
    self::executeHooks( self::$activate_hooks );
    
    // Update activation time
    update_option( 'adz_framework_last_activated', current_time( 'timestamp' ) );
  }

  /**
   * Execute deactivate hooks
   */
  public static function deactivate()
  {
    // Execute custom deactivate hooks
    self::executeHooks( self::$deactivate_hooks );
    
    // Update deactivation time
    update_option( 'adz_framework_last_deactivated', current_time( 'timestamp' ) );
  }

  /**
   * Execute hooks in priority order
   * 
   * @param array $hooks Array of hooks organized by priority
   */
  private static function executeHooks( $hooks ) {
    if ( empty( $hooks ) ) {
      return;
    }
    
    // Sort by priority (lower numbers = higher priority)
    ksort( $hooks );
    
    foreach ( $hooks as $priority => $callbacks ) {
      foreach ( $callbacks as $callback ) {
        if ( is_callable( $callback ) ) {
          try {
            call_user_func( $callback );
          } catch ( Exception $e ) {
            // Log error but continue execution
            error_log( 'ADZ Framework Hook Error: ' . $e->getMessage() );
          }
        }
      }
    }
  }

  public function load( $controllers = [] ) 
  {
    foreach ( $controllers as $c ) {
      $c = $this->_cNamespace . $c . 'Controller';
      $instance = new $c();
      if (method_exists($instance, 'init')) {
        $instance->init();
      }
    }
  }

  public function has( $did )
  {
    $dep = $this->getDep( $did );
    if ( is_array( $dep ) ) {
      return isset( $dep['active'] ) && $dep['active'] == Self::STATUS_ACTIVE;
    }
    return $dep && isset( $dep->active ) && $dep->active == Self::STATUS_ACTIVE;
  }

  public function getDep( $did )
  {
    $dependencies = \Adz::get('dependencies', []);
    return $dependencies[ $did ] ?? null;
  }

  public function setDep( $did, $option, $new_value )
  {
    $dependencies = \Adz::get('dependencies', []);
    if ( isset( $dependencies[ $did ] ) ) {
      $dependencies[ $did ][ $option ] = $new_value;
      \Adz::set('dependencies', $dependencies);
      return true;
    }
    return false;
  }

}