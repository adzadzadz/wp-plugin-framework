<?php 

namespace AdzHive;

use ADZ;

/**
 * Configuration class to be used globally
 * Config files can be one of [php, json]
 * 
 * @author Adrian T. Saycon <adzbite@gmail.com>
 * @package adz/dev-tools
 * @subpackage wp
 * @version 1.0.0
 */
class Config {

  /**
   * The project ID
   *
   * @var String $id
   */
  public $id;

  /**
   * The project name
   *
   * @var String $name
   */
  public $name;

  /**
   * The dir name of the project
   *
   * @var String $textDomain
   */
  public $textDomain;

  /**
   * The plugin's slug includes the dir name and the php entry file name 
   *
   * @var String $slug
   */
  public $slug;

  /**
   * The plugin's slug includes the dir name and the php entry file name 
   *
   * @var Array<\AdzWP\Dependency> $dependencies
   */
  public $dependencies;

  /**
   * The original unmodified config data on first entry
   * 
   * @var Array $original
   */
  public $original;

  function __construct() 
  {
    try {
      $filepath = ADZ::$path . "project/" . \ADZ::$env . "/";
      $config = ( $output = file_get_contents(  $filepath . "config.json" ) ) 
        ? $output 
        : file_get_contents(  \ADZ::$env . "config.php" );
      
      $config = \json_decode( $config, true );
      
      $this->original = $config;

      $this->id    = $config['id'];
      $this->name  = $config['name'];
      $this->textDomain = $config['text-domain'];
      $this->slug  = $config['slug'];
      $this->dependencies = $this->loadDependencies( $config['dependencies'] );

    } catch ( \Exception $e ) {
      return [
        'info' => 'Config format invalid. Validate required data.',
        'error_message' => $e->getMessage()
      ];
    }
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
      return  [
        'info' => 'Invalid dependencies data',
        'error_message' => $e->getMessage()
      ];
    }
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