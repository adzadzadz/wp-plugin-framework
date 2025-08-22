<?php

namespace adz\controllers;

use AdzWP\WordPressController as Controller;

class AdminController extends Controller {

    public $actions = [
        'admin_menu' => 'setupAdminMenu',
        'admin_enqueue_scripts' => 'enqueueAdminAssets'
    ];

    protected function bootstrap()
    {
        
    }

    public function setupAdminMenu()
    {
        
    }

    public function enqueueAdminAssets($hook = null)
    {
        
    }

}