<?php

namespace AdzWP\Examples;

use AdzWP\Core\Controller;

class TestController extends Controller
{
    /**
     * This creates an admin page automatically
     * @page_title My Custom Settings
     * @menu_title Settings
     * @capability manage_options
     * @icon_url dashicons-admin-settings
     * @position 25
     */
    public function adminPageSettings()
    {
        echo '<div class="wrap">';
        echo '<h1>My Custom Settings</h1>';
        echo '<p>This admin page was created automatically!</p>';
        echo '</div>';
    }

    /**
     * This creates a submenu page under Settings
     * @page_title Advanced Options
     * @menu_title Advanced
     * @parent options-general.php
     */
    public function adminPageAdvanced()
    {
        echo '<div class="wrap">';
        echo '<h1>Advanced Options</h1>';
        echo '<p>This is a submenu page under Settings!</p>';
        echo '</div>';
    }

    /**
     * This runs only in admin area
     */
    public function adminInitialize()
    {
        // This code only runs in admin
        error_log('Admin initialize method called');
    }

    /**
     * This runs only on frontend
     */
    public function frontendEnqueue()
    {
        // This code only runs on frontend
        wp_enqueue_script('my-frontend-script', 'path/to/script.js');
    }

    /**
     * Regular action hook (existing functionality)
     */
    public function actionWpInit()
    {
        // This runs on wp_init as before
    }
}