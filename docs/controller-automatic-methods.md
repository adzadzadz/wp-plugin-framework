# Controller Automatic Methods

The framework controller provides automatic method registration based on naming conventions for admin pages, admin-only code, and frontend-only code.

## Admin Page Methods (`adminPage{FunctionName}()`)

Methods starting with `adminPage` automatically create WordPress admin pages and handle menu creation.

### Basic Usage

```php
public function adminPageSettings()
{
    echo '<div class="wrap">';
    echo '<h1>Settings</h1>';
    echo '<p>Your settings page content here</p>';
    echo '</div>';
}
```

### Configuration via DocBlock

Control page creation with docblock annotations:

```php
/**
 * @page_title My Custom Settings
 * @menu_title Settings  
 * @capability manage_options
 * @icon_url dashicons-admin-settings
 * @position 25
 */
public function adminPageSettings()
{
    // Page content
}
```

### Submenu Pages

Create submenu pages by specifying a parent:

```php
/**
 * @page_title Advanced Options
 * @menu_title Advanced
 * @parent options-general.php
 */
public function adminPageAdvanced()
{
    // Submenu page content
}
```

### Available Configuration Options

- `@page_title` - Page title shown in browser tab
- `@menu_title` - Menu item text
- `@capability` - Required user capability (default: `manage_options`)
- `@menu_slug` - Menu slug (auto-generated from method name)
- `@icon_url` - Menu icon (default: `dashicons-admin-generic`)
- `@position` - Menu position number
- `@parent` - Parent page for submenu (e.g., `options-general.php`)

## Admin-Only Methods (`admin{FunctionName}()`)

Methods starting with `admin` (but not `adminPage`) run only in the WordPress admin area.

```php
public function adminInitialize()
{
    // This code only runs in admin
    add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
}

public function adminUsers()
{
    // Admin-only user management code
}
```

## Frontend-Only Methods (`frontend{FunctionName}()`)

Methods starting with `frontend` run only on the frontend (not in admin).

```php
public function frontendEnqueue()
{
    // This code only runs on frontend
    wp_enqueue_script('my-frontend-script', 'path/to/script.js');
}

public function frontendShortcodes()
{
    // Register frontend shortcodes
    add_shortcode('my_shortcode', [$this, 'renderShortcode']);
}
```

## Execution Timing

- **adminPage** methods: Registered on `admin_menu` hook
- **admin** methods: Execute on `wp_loaded` hook when `is_admin()` is true
- **frontend** methods: Execute on `wp_loaded` hook when `is_admin()` is false

## Complete Example

```php
<?php

namespace MyPlugin;

use AdzWP\Core\Controller;

class SettingsController extends Controller
{
    /**
     * Main settings page
     * @page_title My Plugin Settings
     * @menu_title My Plugin
     * @icon_url dashicons-admin-settings
     */
    public function adminPageSettings()
    {
        echo '<div class="wrap">';
        echo '<h1>My Plugin Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('my_plugin_settings');
        do_settings_sections('my_plugin_settings');
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    /**
     * Advanced settings submenu
     * @parent my_plugin_settings
     * @page_title Advanced Settings
     * @menu_title Advanced
     */
    public function adminPageAdvanced()
    {
        echo '<div class="wrap">';
        echo '<h1>Advanced Settings</h1>';
        echo '<p>Advanced configuration options</p>';
        echo '</div>';
    }

    /**
     * Initialize admin-specific functionality
     */
    public function adminInitialize()
    {
        register_setting('my_plugin_settings', 'my_plugin_option');
        add_settings_section('main', 'Main Settings', null, 'my_plugin_settings');
    }

    /**
     * Frontend initialization
     */
    public function frontendInit()
    {
        wp_enqueue_style('my-plugin-style', 'path/to/style.css');
    }
}
```