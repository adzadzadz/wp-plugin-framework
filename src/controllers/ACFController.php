<?php 

namespace adz\controllers;

use AdzWP\Controller;
use Adz\View;
use wpie\core\WPIE_General;

class ACFController extends Controller {

  public function init()
  {
    if ( !\ADZ::$plugin->has('acf') ) return;
  }

}