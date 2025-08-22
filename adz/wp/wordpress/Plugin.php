<?php 
namespace AdzWP;

use ADZ;

Class Plugin extends Core {

  private $_cNamespace = "\\adz\\controllers\\";

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
      ADZ::build( $c );
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