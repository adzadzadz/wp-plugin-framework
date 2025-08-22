# Getting Started with ADZ WordPress Plugin Framework

This guide will help you get started with the ADZ WordPress Plugin Framework quickly and efficiently.

## Installation

### Via Composer (Recommended)

```bash
composer require adzadzadz/wp-plugin-framework
```

### Manual Installation

1. Download the framework files
2. Place them in your plugin directory
3. Include the autoloader in your main plugin file

## Creating Your First Plugin

### 1. Initialize Your Plugin

Create your main plugin file (e.g., `my-awesome-plugin.php`):

```php
<?php
/**
 * Plugin Name: My Awesome Plugin
 * Description: A WordPress plugin built with ADZ Framework
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('MY_PLUGIN_VERSION', '1.0.0');
define('MY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MY_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load Composer autoloader
require_once MY_PLUGIN_PATH . 'vendor/autoload.php';

// Initialize the framework
$framework = \Adz::config();
$framework->set('plugin.path', MY_PLUGIN_PATH);
$framework->set('plugin.url', MY_PLUGIN_URL);
$framework->set('plugin.version', MY_PLUGIN_VERSION);

// Set up plugin lifecycle
$pluginManager = \AdzWP\Core\PluginManager::getInstance(__FILE__);

$pluginManager
    ->onActivate(function() {
        // Plugin activation logic
        flush_rewrite_rules();
    })
    ->onDeactivate(function() {
        // Plugin deactivation logic
        flush_rewrite_rules();
    })
    ->setupOptions([
        'my_plugin_enabled' => true,
        'my_plugin_api_key' => ''
    ]);

// Initialize your controllers
new App\Controllers\MainController();
```

### 2. Create Your First Controller

Create `src/Controllers/MainController.php`:

```php
<?php

namespace App\Controllers;

use AdzWP\Core\Controller;

class MainController extends Controller
{
    // WordPress actions to hook into
    public $actions = [
        'init' => 'initialize',
        'wp_enqueue_scripts' => 'enqueueAssets'
    ];

    // WordPress filters to hook into
    public $filters = [
        'the_content' => 'enhanceContent'
    ];

    public function initialize()
    {
        // Initialize your plugin logic
        if ($this->isAdmin()) {
            // Admin-specific initialization
            add_action('admin_menu', [$this, 'addAdminMenu']);
        }
    }

    public function enqueueAssets()
    {
        // Enqueue scripts and styles
        wp_enqueue_script(
            'my-plugin-script',
            plugin_dir_url(__FILE__) . '../../assets/js/main.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }

    public function enhanceContent($content)
    {
        // Modify post content
        if (is_single() && get_option('my_plugin_enabled')) {
            $content .= '<p>Enhanced by My Awesome Plugin!</p>';
        }
        return $content;
    }

    public function addAdminMenu()
    {
        add_options_page(
            'My Plugin Settings',
            'My Plugin',
            'manage_options',
            'my-plugin-settings',
            [$this, 'settingsPage']
        );
    }

    public function settingsPage()
    {
        echo '<div class="wrap">';
        echo '<h1>My Plugin Settings</h1>';
        echo '<p>Plugin settings go here...</p>';
        echo '</div>';
    }
}
```

### 3. Set Up Dependencies (Optional)

If your plugin requires other plugins, you can set up automatic dependency management:

```php
$pluginManager->setDependencies([
    [
        'slug' => 'woocommerce/woocommerce.php',
        'name' => 'WooCommerce',
        'source' => 'repo'
    ],
    [
        'slug' => 'custom-plugin/custom-plugin.php',
        'name' => 'Custom Plugin',
        'source' => 'url',
        'url' => 'https://example.com/custom-plugin.zip'
    ]
]);
```

### 4. Directory Structure

Your plugin should follow this structure:

```
my-awesome-plugin/
├── my-awesome-plugin.php          # Main plugin file
├── composer.json                  # Composer dependencies
├── src/
│   └── Controllers/
│       └── MainController.php     # Your controllers
├── assets/
│   ├── css/
│   │   └── main.css
│   └── js/
│       └── main.js
├── views/
│   └── admin/
│       └── settings.php           # Template files
└── tests/
    └── Unit/
        └── MainControllerTest.php  # Unit tests
```

## Next Steps

1. **Learn about Controllers**: Read the [Controllers Guide](controllers.md)
2. **Explore Plugin Lifecycle**: Check out [Plugin Lifecycle Management](PLUGIN_LIFECYCLE.md)
3. **Add Database Functionality**: Learn about [Models & Database](models-database.md)
4. **Set Up Testing**: Follow the [Testing Guide](testing.md)

## Common Patterns

### Adding Custom Post Types

```php
public $actions = [
    'init' => 'registerCustomPostTypes'
];

public function registerCustomPostTypes()
{
    register_post_type('my_custom_type', [
        'public' => true,
        'label' => 'My Custom Type',
        'supports' => ['title', 'editor', 'thumbnail']
    ]);
}
```

### Adding Settings Pages

```php
public function addAdminMenu()
{
    add_menu_page(
        'My Plugin',
        'My Plugin',
        'manage_options',
        'my-plugin',
        [$this, 'adminPage'],
        'dashicons-admin-plugins'
    );
}
```

### Handling AJAX Requests

```php
public $actions = [
    'wp_ajax_my_action' => 'handleAjaxRequest',
    'wp_ajax_nopriv_my_action' => 'handleAjaxRequest'
];

public function handleAjaxRequest()
{
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'my_action_nonce')) {
        wp_die('Security check failed');
    }

    // Process request
    $result = ['success' => true, 'data' => 'Hello World'];
    wp_send_json($result);
}
```

## Troubleshooting

### Common Issues

1. **Autoloader not found**: Make sure you've run `composer install`
2. **Class not found**: Check your namespace and file structure
3. **Hooks not firing**: Verify your controller is properly instantiated

### Debug Mode

Enable debug mode in your plugin:

```php
$framework->set('debug', true);
```

This will provide more detailed error messages and logging.

## Support

- [Framework Documentation](README.md)
- [GitHub Issues](https://github.com/adzadzadz/wp-plugin-framework/issues)
- [Community Forum](https://community.example.com)