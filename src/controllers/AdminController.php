<?php 

namespace adz\controllers;

use AdzWP\Controller;
use AdzWP\View;

class AdminController extends Controller {

  public function init()
  {
    add_action( 'admin_menu', [ $this, 'setMenu' ], 9999 );
  }

  public function setMenu()
  {
    add_menu_page('Adz Plugin', 'Santec', 'manage_options', 'adz-toolbox-menu', [ $this, 'actionHelper' ], 'dashicons-welcome-widgets-menus', 2 );
    add_submenu_page( 'adz-toolbox-menu', 'Site Helper Tools', 'Helper', 'manage_options', 'adz-toolbox-menu', [ $this, 'actionHelper' ] );
  }

  public function actionDashboard()
  {
    if ( !current_user_can( 'manage_options' ) )  {
      wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    echo View::render('admin/dashboard');
  }

}