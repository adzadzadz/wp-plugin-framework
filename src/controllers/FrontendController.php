<?php

namespace adz\controllers;

use AdzWP\WordPressController as Controller;

class FrontendController extends Controller {

    public $actions = [
        'wp_enqueue_scripts' => 'enqueueFrontendAssets',
        'init' => 'init'
    ];

    protected function bootstrap()
    {
        
    }

    public function init()
    {
        
    }

    public function enqueueFrontendAssets()
    {
        
    }

}