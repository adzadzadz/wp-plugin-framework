<?php 

namespace adz\controllers;

use AdzWP\Controller;
use AdzWP\View;

class AdminController extends Controller {

  public $actions = [
    'admin_menu' => [
      'callback' => 'setMenu',
      'priority' => 9999
    ],
    'admin_enqueue_scripts' => 'enqueueAdminAssets'
  ];
  
  protected function bootstrap()
  {
    // Additional initialization if needed
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
  
  public function actionHelper()
  {
    if ( !current_user_can( 'manage_options' ) )  {
      wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    
    echo View::render('admin/dashboard');
  }
  
  public function enqueueAdminAssets($hook)
  {
    if (strpos($hook, 'adz-toolbox-menu') !== false) {
      wp_enqueue_style('adz-admin-css', ADZ_PLUGIN_URL . 'src/assets/css/main.css', [], ADZ_PLUGIN_VERSION);
      wp_enqueue_script('adz-admin-js', ADZ_PLUGIN_URL . 'src/assets/js/main.js', ['jquery'], ADZ_PLUGIN_VERSION, true);
      
      wp_localize_script('adz-admin-js', 'adz_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('adz-ajax-nonce')
      ]);
    }
  }

}