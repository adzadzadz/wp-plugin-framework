<?php 

/**
 * Main Adz framework class - accessible as \Adz
 * This will be autoloaded as the root Adz class
 * 
 * @author Adrian T. Saycon <adzbite@gmail.com>
 * @package adz/wp-plugin-framework
 * @version 2.0.0
 */
class Adz {
  
  /**
   * Framework version
   */
  const VERSION = '2.0.0';
  
  /**
   * Configuration instance
   */
  protected static $config = null;
  
  /**
   * Container for dependency injection
   */
  protected static $container = [];
  
  /**
   * Registered services
   */
  protected static $services = [];
  
  /**
   * Get framework version
   */
  public static function version(): string
  {
    return self::VERSION;
  }
  
  /**
   * Bind a service to the container
   */
  public static function bind(string $key, $value): void
  {
    self::$container[$key] = $value;
  }
  
  /**
   * Resolve a service from the container
   */
  public static function resolve(string $key, $default = null)
  {
    if (isset(self::$container[$key])) {
      $service = self::$container[$key];
      
      if (is_callable($service)) {
        return $service();
      }
      
      return $service;
    }
    
    return $default;
  }
  
  /**
   * Register a singleton service
   */
  public static function singleton(string $key, callable $factory): void
  {
    if (!isset(self::$services[$key])) {
      self::$services[$key] = $factory();
    }
  }
  
  /**
   * Get a singleton service
   */
  public static function service(string $key)
  {
    return self::$services[$key] ?? null;
  }
  
  /**
   * Create an instance of a class
   */
  public static function make(string $class, array $args = [])
  {
    if (class_exists($class)) {
      return new $class($args);
    }
    
    throw new \InvalidArgumentException("Class {$class} not found");
  }
  
  /**
   * Get configuration instance
   */
  public static function config(): \AdzWP\Core\Config
  {
    if (self::$config === null) {
      self::$config = \AdzWP\Core\Config::getInstance();
    }
    
    return self::$config;
  }
  
  /**
   * Quick access to configuration values
   */
  public static function get(string $key, $default = null)
  {
    return self::config()->get($key, $default);
  }
  
  /**
   * Set configuration value
   */
  public static function set(string $key, $value): void
  {
    self::config()->set($key, $value);
  }
}