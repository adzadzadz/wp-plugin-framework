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

  public function init()
  {
    Dependency::monitor_status();
  }

  public static function install()
  {
    Dependency::install_required();
  }

  public static function uninstall()
  {
    // Uninstall 
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
    return $this->getDep( $did )->active == Self::STATUS_ACTIVE;
  }

  public function getDep( $did )
  {
    return ADZ::$conf->dependencies[ $did ];
  }

  public function setDep( $did, $option, $new_value )
  {
    ADZ::$conf->dependencies[ $did ][ $option ] = $new_value;
    return true;
  }

}