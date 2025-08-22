<?php 

namespace AdzWP\Core;

/**
 * Plugin Manager - Easy setup for WordPress plugin lifecycle
 * 
 * This class provides a simple interface for developers to register
 * install, uninstall, activate, and deactivate hooks for their plugins.
 * 
 * @author Adrian T. Saycon <adzbite@gmail.com>
 * @package adz/wp-plugin-framework
 * @version 2.0.0
 */
class PluginManager {
  
  protected static $instance = null;
  protected $plugin_file = '';
  protected $dependencies = [];
  
  /**
   * Get or create singleton instance
   */
  public static function getInstance( $plugin_file = '' ): self {
    if ( self::$instance === null ) {
      self::$instance = new self( $plugin_file );
    }
    return self::$instance;
  }
  
  /**
   * Constructor
   * 
   * @param string $plugin_file Main plugin file path
   */
  public function __construct( $plugin_file = '' ) {
    $this->plugin_file = $plugin_file;
    $this->registerWordPressHooks();
  }
  
  /**
   * Register WordPress plugin lifecycle hooks
   */
  protected function registerWordPressHooks() {
    if ( !empty( $this->plugin_file ) ) {
      register_activation_hook( $this->plugin_file, [ 'AdzWP\Core\Plugin', 'activate' ] );
      register_deactivation_hook( $this->plugin_file, [ 'AdzWP\Core\Plugin', 'deactivate' ] );
      register_uninstall_hook( $this->plugin_file, [ 'AdzWP\Core\Plugin', 'uninstall' ] );
    }
  }
  
  /**
   * Set plugin dependencies
   * 
   * @param array $dependencies Array of dependency configurations
   * @return self
   */
  public function setDependencies( array $dependencies ): self {
    $this->dependencies = $dependencies;
    return $this;
  }
  
  /**
   * Add a single dependency
   * 
   * @param array $dependency Dependency configuration
   * @return self
   */
  public function addDependency( array $dependency ): self {
    $this->dependencies[] = $dependency;
    return $this;
  }
  
  /**
   * Install dependencies automatically
   * 
   * @return array Installation results
   */
  public function installDependencies(): array {
    if ( empty( $this->dependencies ) ) {
      return [];
    }
    
    return Dependency::auto_install_dependencies( $this->dependencies );
  }
  
  /**
   * Register install hook - runs during plugin installation
   * 
   * @param callable $callback Function to execute
   * @param int $priority Priority (lower = earlier)
   * @return self
   */
  public function onInstall( $callback, int $priority = 10 ): self {
    Plugin::onInstall( $callback, $priority );
    return $this;
  }
  
  /**
   * Register uninstall hook - runs during plugin removal
   * 
   * @param callable $callback Function to execute
   * @param int $priority Priority (lower = earlier)
   * @return self
   */
  public function onUninstall( $callback, int $priority = 10 ): self {
    Plugin::onUninstall( $callback, $priority );
    return $this;
  }
  
  /**
   * Register activate hook - runs when plugin is activated
   * 
   * @param callable $callback Function to execute
   * @param int $priority Priority (lower = earlier)
   * @return self
   */
  public function onActivate( $callback, int $priority = 10 ): self {
    Plugin::onActivate( $callback, $priority );
    return $this;
  }
  
  /**
   * Register deactivate hook - runs when plugin is deactivated
   * 
   * @param callable $callback Function to execute
   * @param int $priority Priority (lower = earlier)
   * @return self
   */
  public function onDeactivate( $callback, int $priority = 10 ): self {
    Plugin::onDeactivate( $callback, $priority );
    return $this;
  }
  
  /**
   * Fluent interface for setting up common plugin tasks
   * 
   * @return self
   */
  public function setupDatabase(): self {
    $this->onActivate( function() {
      // Create database tables if needed
      $this->createTables();
    });
    
    $this->onUninstall( function() {
      // Drop database tables if needed  
      $this->dropTables();
    });
    
    return $this;
  }
  
  /**
   * Setup default options
   * 
   * @param array $default_options Default plugin options
   * @return self
   */
  public function setupOptions( array $default_options = [] ): self {
    $this->onActivate( function() use ( $default_options ) {
      foreach ( $default_options as $option_name => $default_value ) {
        if ( get_option( $option_name ) === false ) {
          update_option( $option_name, $default_value );
        }
      }
    });
    
    $this->onUninstall( function() use ( $default_options ) {
      foreach ( $default_options as $option_name => $default_value ) {
        delete_option( $option_name );
      }
    });
    
    return $this;
  }
  
  /**
   * Setup capabilities and roles
   * 
   * @param array $capabilities Array of capabilities to add
   * @param array $roles Array of roles to add capabilities to
   * @return self
   */
  public function setupCapabilities( array $capabilities = [], array $roles = [ 'administrator' ] ): self {
    $this->onActivate( function() use ( $capabilities, $roles ) {
      foreach ( $roles as $role_name ) {
        $role = get_role( $role_name );
        if ( $role ) {
          foreach ( $capabilities as $cap ) {
            $role->add_cap( $cap );
          }
        }
      }
    });
    
    $this->onDeactivate( function() use ( $capabilities, $roles ) {
      foreach ( $roles as $role_name ) {
        $role = get_role( $role_name );
        if ( $role ) {
          foreach ( $capabilities as $cap ) {
            $role->remove_cap( $cap );
          }
        }
      }
    });
    
    return $this;
  }
  
  /**
   * Create database tables (override in your plugin)
   */
  protected function createTables() {
    // Override this method in your plugin implementation
  }
  
  /**
   * Drop database tables (override in your plugin)
   */
  protected function dropTables() {
    // Override this method in your plugin implementation
  }
  
  /**
   * Get plugin file path
   */
  public function getPluginFile(): string {
    return $this->plugin_file;
  }
  
  /**
   * Get dependencies
   */
  public function getDependencies(): array {
    return $this->dependencies;
  }
}