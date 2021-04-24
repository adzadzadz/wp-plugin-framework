<?php 

namespace AdzHive;

use \ADZ;

class View extends Core {

  public static function render($path, $attr = [])
  {    
    ob_start();
    extract($attr);
    include( ADZ::$path . "/src/views/" . $path . ".php");
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
  }

}