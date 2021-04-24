<?php 
namespace AdzHive;

/**
 * Base class for adz/dev-tools
 * 
 * @author Adrian T. Saycon <adzbite@gmail.com>
 * @package adz/dev-tools
 * @subpackage wp
 * @version 1.0.0
 */
class Adz extends StatusConstants {
  
  function __construct( Array $args = [] ) 
  {
    foreach ( $args as $prop => $v)  {
      if ( property_exists($this, $prop) ) $this->$prop = $v;
    }
    $this->init();
  }

  public function init()
  {
    return 'Init Interface';
  }

}