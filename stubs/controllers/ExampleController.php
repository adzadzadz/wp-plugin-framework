<?php

namespace App\Controllers;

use Adz\WP\WordPressController as Controller;

class ExampleController extends Controller
{
    public $actions = [
        'init' => 'initialize'
    ];

    public $filters = [];

    protected function bootstrap()
    {
        // Additional initialization if needed
    }

    public function initialize()
    {
        // WordPress initialization logic
        if ($this->isAdmin()) {
            // Admin-specific initialization
        }
        
        if ($this->isFrontend()) {
            // Frontend-specific initialization
        }
    }
}