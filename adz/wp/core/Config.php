<?php 

namespace AdzWP;

use ADZ;

/**
 * Enhanced Configuration class with modern features
 * Supports PHP, JSON config files and environment variables
 * 
 * @author Adrian T. Saycon <adzbite@gmail.com>
 * @package adz/dev-tools
 * @subpackage wp
 * @version 2.0.0
 */
class Config {

  protected static $instance = null;
  protected $config = [];
  protected $loaded = false;
  protected $configPath;
  
  // Legacy properties maintained for backward compatibility
  public $id;
  public $name;
  public $textDomain;
  public $slug;
  public $dependencies;
  public $original;
  
  protected $defaultConfig = [
    'plugin' => [
      'name' => 'ADZ Plugin Framework',
      'version' => '1.0.0',
      'text_domain' => 'adz-plugin',
      'slug' => 'adz-plugin'
    ],
    'admin' => [
      'menu_title' => 'ADZ Plugin',
      'menu_slug' => 'adz-toolbox-menu',
      'capability' => 'manage_options',
      'icon' => 'dashicons-admin-tools',
      'position' => 2
    ],
    'security' => [
      'enable_nonce' => true,
      'enable_csrf' => true,
      'enable_rate_limiting' => true,
      'rate_limit_attempts' => 60,
      'rate_limit_window' => 3600
    ],
    'logging' => [
      'enabled' => true,
      'level' => 'info',
      'max_file_size' => 10485760,
      'max_files' => 5
    ],
    'database' => [
      'prefix' => 'adz_',
      'charset' => 'utf8mb4',
      'collate' => 'utf8mb4_unicode_ci'
    ],
    'cache' => [
      'enabled' => true,
      'default_ttl' => 3600,
      'driver' => 'transient'
    ],
    'api' => [
      'version' => 'v1',
      'namespace' => 'adz/v1',
      'enable_auth' => true
    ],
    'assets' => [
      'version' => null,
      'cache_bust' => true,
      'minify' => false
    ]
  ];

  function __construct($configPath = null) 
  {
    $this->configPath = $configPath ?: $this->getDefaultConfigPath();
    $this->loadLegacyConfig();
    $this->loadModernConfig();
  }
  
  public static function getInstance($configPath = null)
  {
    if (self::$instance === null) {
      self::$instance = new self($configPath);
    }
    
    return self::$instance;
  }
  
  protected function getDefaultConfigPath()
  {
    if (defined('ADZ_CONFIG_PATH')) {
      return ADZ_CONFIG_PATH;
    }
    
    return WP_CONTENT_DIR . '/adz-config/';
  }
  
  protected function loadLegacyConfig()
  {
    try {
      if (class_exists('ADZ') && property_exists('ADZ', 'path') && property_exists('ADZ', 'env')) {
        $filepath = ADZ::$path . "project/" . ADZ::$env . "/";
        $configContent = null;
        
        if (file_exists($filepath . "config.json")) {
          $configContent = file_get_contents($filepath . "config.json");
        } elseif (file_exists($filepath . "config.php")) {
          $configContent = file_get_contents($filepath . "config.php");
        }
        
        if ($configContent) {
          $config = json_decode($configContent, true);
          
          if ($config && is_array($config)) {
            $this->original = $config;
            $this->id = $config['id'] ?? null;
            $this->name = $config['name'] ?? null;
            $this->textDomain = $config['text-domain'] ?? null;
            $this->slug = $config['slug'] ?? null;
            $this->dependencies = isset($config['dependencies']) ? $this->loadDependencies($config['dependencies']) : [];
          }
        }
      }
    } catch (\Exception $e) {
      adz_log_warning('Failed to load legacy config: ' . $e->getMessage());
    }
  }
  
  protected function loadModernConfig()
  {
    if ($this->loaded) {
      return;
    }
    
    $this->config = $this->defaultConfig;
    
    if (!file_exists($this->configPath)) {
      wp_mkdir_p($this->configPath);
    }
    
    $configFiles = [
      'app.php',
      'database.php',
      'logging.php',
      'security.php',
      'cache.php'
    ];
    
    foreach ($configFiles as $file) {
      $filePath = $this->configPath . $file;
      
      if (file_exists($filePath)) {
        $fileConfig = include $filePath;
        
        if (is_array($fileConfig)) {
          $this->config = array_merge_recursive($this->config, $fileConfig);
        }
      }
    }
    
    $this->config = apply_filters('adz_config', $this->config);
    $this->loaded = true;
  }

  /**
   * Load the dependencies data from the config file as Objects
   *
   * @param [type] $dependencies
   * @param array $data
   * @return Mixed
   */
  public function loadDependencies( $dependencies, $data = [] ) 
  {
    try {
      foreach ( $dependencies as $id => $info ) {
        $data[$id] = ( new Dependency( $id, $info ) )->info;
      }
      return $data;
    } catch ( \Exception $e ) {
      adz_log_error('Failed to load dependencies: ' . $e->getMessage());
      return  [
        'info' => 'Invalid dependencies data',
        'error_message' => $e->getMessage()
      ];
    }
  }
  
  // Modern configuration methods
  public function get($key = null, $default = null)
  {
    if ($key === null) {
      return $this->config;
    }
    
    if (strpos($key, '.') === false) {
      return $this->config[$key] ?? $default;
    }
    
    $keys = explode('.', $key);
    $value = $this->config;
    
    foreach ($keys as $segment) {
      if (is_array($value) && array_key_exists($segment, $value)) {
        $value = $value[$segment];
      } else {
        return $default;
      }
    }
    
    return $value;
  }
  
  public function set($key, $value)
  {
    if (strpos($key, '.') === false) {
      $this->config[$key] = $value;
      return;
    }
    
    $keys = explode('.', $key);
    $config = &$this->config;
    
    foreach ($keys as $segment) {
      if (!is_array($config)) {
        $config = [];
      }
      
      if (!array_key_exists($segment, $config)) {
        $config[$segment] = [];
      }
      
      $config = &$config[$segment];
    }
    
    $config = $value;
  }
  
  public function has($key)
  {
    return $this->get($key) !== null;
  }
  
  public function all()
  {
    return $this->config;
  }
  
  public function getEnv($key, $default = null)
  {
    $value = getenv($key);
    
    if ($value === false) {
      return $default;
    }
    
    return $this->parseEnvValue($value);
  }
  
  protected function parseEnvValue($value)
  {
    $value = trim($value);
    
    if (preg_match('/^"(.*)"$/', $value, $matches)) {
      return $matches[1];
    }
    
    if (in_array(strtolower($value), ['true', 'false'])) {
      return strtolower($value) === 'true';
    }
    
    if (is_numeric($value)) {
      return strpos($value, '.') !== false ? (float) $value : (int) $value;
    }
    
    return $value;
  }
  
  public function reload()
  {
    $this->loaded = false;
    $this->loadModernConfig();
  }

}

class Dependency {

  public $id;

  public $info;

  function __construct( String $id, Array $info )
  {
    try {
      $this->id = $id;
      $this->info = new DependencyInfo( 
        $info['text-domain'], 
        $info['name'],
        $info['slug'],
        $info['zip'],
        isset( $info['main-class-name'] ) ? $info['main-class-name'] : null
      );
    } catch ( \Exception $e) {
      return  [
        'info' => 'Invalid dependency data.',
        'error_message' => $e->getMessage()
      ];
    }
  }

}

class DependencyInfo extends StatusConstants {

  public $textDomain;

  public $name;

  public $slug;

  public $zip;

  public $mainClassName;

  public $installed = Self::STATUS_UNINSTALLED;

  public $active = Self::STATUS_INACTIVE;

  /**
   * Custom data Array for whatever use, if any
   *
   * @var Array
   */
  private $custom = [];

  function __construct( $textDomain, $name, $slug, $zip, $mainClassName = null, $installed = null, $active = null ) 
  {
    try {
      $this->textDomain = $textDomain;
      $this->name = $name;
      $this->slug = $slug;
      $this->zip = $zip;
      $this->mainClassName = $mainClassName;
      $this->installed = $installed ? $installed : $this->installed;
      $this->active = $active ? $active : $this->active;
    } catch ( \Exception $e) {
      return  [
        'info' => 'Invalid dependency info format.',
        'error_message' => $e->getMessage()
      ];
    }
  }

  public function setCustom( $key, $value )
  {
    $this->custom[$key] = $value;
    return true;
  }

  public function getCustom( $key )
  {
    return array_key_exists( $key, $this->custom ) 
      ? $this->custom[ 'key' ]
      : false;
  }

}