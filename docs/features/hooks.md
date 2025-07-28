# Hook Management System

The ADZ Plugin Framework provides an elegant, declarative way to work with WordPress hooks, eliminating the need for manual `add_action()` and `add_filter()` calls.

## Overview

Instead of cluttering your code with hook registrations, define them as arrays in your controllers. The framework automatically processes these arrays and registers the hooks with WordPress.

## Basic Usage

### Actions

Define WordPress actions in your controller:

```php
<?php

namespace MyPlugin\Controllers;

use AdzWP\Controller;

class MyController extends Controller 
{
    public $actions = [
        'init' => 'initialize',
        'wp_enqueue_scripts' => 'enqueueAssets',
        'admin_menu' => 'addAdminMenu'
    ];
    
    public function initialize()
    {
        // Runs on WordPress init
    }
    
    public function enqueueAssets()
    {
        // Enqueue CSS/JS files
    }
    
    public function addAdminMenu()
    {
        // Add admin menu items
    }
}
```

### Filters

Define WordPress filters in your controller:

```php
public $filters = [
    'the_content' => 'modifyContent',
    'wp_title' => 'customizeTitle',
    'body_class' => 'addBodyClasses'
];

public function modifyContent($content)
{
    return $content . '<p>Added by my plugin!</p>';
}

public function customizeTitle($title)
{
    return 'Custom: ' . $title;
}

public function addBodyClasses($classes)
{
    $classes[] = 'my-plugin-active';
    return $classes;
}
```

## Advanced Configuration

### Priority and Arguments

Use array syntax for advanced hook configuration:

```php
public $actions = [
    'init' => [
        'callback' => 'initialize',
        'priority' => 5,
        'accepted_args' => 1
    ],
    'wp_enqueue_scripts' => [
        'callback' => 'enqueueAssets',
        'priority' => 20
    ]
];

public $filters = [
    'the_content' => [
        'callback' => 'modifyContent',
        'priority' => 10,
        'accepted_args' => 1
    ],
    'wp_query_vars' => [
        'callback' => 'addQueryVars',
        'priority' => 10,
        'accepted_args' => 1
    ]
];
```

### Multiple Hooks

Register the same callback for multiple hooks:

```php
public $actions = [
    'wp_enqueue_scripts' => 'enqueueAssets',
    'admin_enqueue_scripts' => 'enqueueAssets',
    'login_enqueue_scripts' => 'enqueueAssets'
];

public function enqueueAssets()
{
    // Determine context and enqueue appropriate assets
    if (is_admin()) {
        wp_enqueue_script('my-admin-script', ...);
    } else {
        wp_enqueue_script('my-frontend-script', ...);
    }
}
```

### Conditional Registration

Register hooks conditionally:

```php
protected function bootstrap()
{
    // Only register admin hooks if in admin area
    if (is_admin()) {
        $this->actions['admin_menu'] = 'addAdminMenu';
        $this->actions['admin_init'] = 'initializeAdmin';
    }
    
    // Only register frontend hooks if not in admin
    if (!is_admin()) {
        $this->filters['the_content'] = 'modifyContent';
        $this->actions['wp_footer'] = 'addFooterContent';
    }
}
```

## Hook Registration Process

### Automatic Registration

The framework automatically registers hooks during initialization:

1. Controller is instantiated
2. `init()` method is called
3. `registerHooks()` processes `$actions` and `$filters` arrays
4. Each hook is registered with WordPress
5. `bootstrap()` method is called for additional setup

### Manual Registration

You can also register hooks manually if needed:

```php
protected function bootstrap()
{
    // Manual hook registration
    add_action('custom_hook', [$this, 'handleCustomHook']);
    
    // Or using the built-in methods
    $this->registerAction('another_hook', 'handleAnotherHook');
    $this->registerFilter('custom_filter', 'handleCustomFilter', 15, 2);
}
```

## Common Hook Patterns

### Admin Hooks

```php
public $actions = [
    'admin_menu' => 'addAdminMenu',
    'admin_init' => 'initializeAdmin',
    'admin_enqueue_scripts' => 'enqueueAdminAssets',
    'admin_post_my_action' => 'handleAdminPost',
    'wp_ajax_my_ajax_action' => 'handleAjaxRequest',
    'wp_ajax_nopriv_my_ajax_action' => 'handleAjaxRequest'
];
```

### Frontend Hooks

```php
public $actions = [
    'wp_enqueue_scripts' => 'enqueueFrontendAssets',
    'wp_footer' => 'addFooterContent',
    'template_redirect' => 'handleTemplateRedirect'
];

public $filters = [
    'the_content' => 'modifyPostContent',
    'wp_nav_menu_items' => 'addMenuItems',
    'body_class' => 'addBodyClasses'
];
```

### Custom Post Type Hooks

```php
public $actions = [
    'init' => 'registerPostTypes',
    'add_meta_boxes' => 'addMetaBoxes',
    'save_post' => [
        'callback' => 'savePostMeta',
        'priority' => 10,
        'accepted_args' => 2
    ]
];

public function registerPostTypes()
{
    register_post_type('my_custom_type', [
        'public' => true,
        'label' => 'My Custom Type'
    ]);
}

public function savePostMeta($postId, $post)
{
    if ($post->post_type === 'my_custom_type') {
        // Save custom meta data
    }
}
```

### Plugin Lifecycle Hooks

```php
public $actions = [
    'plugins_loaded' => 'onPluginsLoaded',
    'init' => 'initialize',
    'wp_loaded' => 'onWordPressLoaded'
];

public function onPluginsLoaded()
{
    // All plugins have been loaded
    load_plugin_textdomain('my-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

public function initialize()
{
    // WordPress has initialized
    $this->setupCustomTables();
}

public function onWordPressLoaded()
{
    // WordPress is fully loaded
    $this->initializeServices();
}
```

## Error Handling

### Hook Validation

The framework validates hooks before registration:

```php
// Invalid callback - will log warning
public $actions = [
    'init' => 'nonExistentMethod'  // Method doesn't exist
];

// Valid callback variations
public $actions = [
    'init' => 'existingMethod',           // String method name
    'init' => [$this, 'existingMethod'], // Array callback
    'init' => function() { ... }         // Anonymous function
];
```

### Debug Information

Enable debug mode to see hook registration details:

```php
// In config/app.php
return [
    'debug' => [
        'enabled' => true,
        'log_hooks' => true
    ]
];
```

This will log information about each hook registration.

## Best Practices

### 1. Organize by Context

Group related hooks together:

```php
// Admin-specific hooks
public $adminActions = [
    'admin_menu' => 'addAdminMenu',
    'admin_init' => 'initializeAdmin'
];

// Frontend-specific hooks  
public $frontendActions = [
    'wp_enqueue_scripts' => 'enqueueFrontendAssets',
    'wp_footer' => 'addFooterContent'
];

protected function bootstrap()
{
    if (is_admin()) {
        $this->actions = array_merge($this->actions, $this->adminActions);
    } else {
        $this->actions = array_merge($this->actions, $this->frontendActions);
    }
}
```

### 2. Use Descriptive Method Names

```php
public $actions = [
    'init' => 'initializeCustomPostTypes',
    'wp_enqueue_scripts' => 'enqueueContactFormAssets',
    'admin_menu' => 'addSettingsMenuPage'
];
```

### 3. Handle Hook Arguments Properly

```php
public $actions = [
    'save_post' => [
        'callback' => 'handlePostSave',
        'accepted_args' => 3  // $post_id, $post, $update
    ]
];

public function handlePostSave($postId, $post, $update)
{
    // Handle all three arguments
    if ($update && $post->post_type === 'my_type') {
        // Update existing post
    }
}
```

### 4. Use Early/Late Priorities When Needed

```php
public $actions = [
    'init' => [
        'callback' => 'earlyInitialization',
        'priority' => 1  // Run early
    ],
    'wp_footer' => [
        'callback' => 'lateFooterContent',
        'priority' => 999  // Run late
    ]
];
```

### 5. Document Your Hooks

```php
/**
 * WordPress hooks for the Contact Form controller
 */
public $actions = [
    'init' => 'registerShortcodes',        // Register [contact-form] shortcode
    'wp_enqueue_scripts' => 'enqueueAssets', // Load CSS/JS for frontend
    'wp_ajax_submit_contact' => 'handleSubmission', // Handle AJAX form submission
    'wp_ajax_nopriv_submit_contact' => 'handleSubmission' // Handle for non-logged users
];
```

## Integration with WordPress

The hook management system is fully compatible with WordPress core:

- Uses standard WordPress hook registration functions
- Maintains hook priority and argument handling
- Supports all WordPress action and filter hooks
- Compatible with third-party plugins and themes

## Migration from Manual Hooks

Converting existing hook registrations is straightforward:

### Before (Manual)
```php
class OldController {
    public function __construct() {
        add_action('init', [$this, 'initialize']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets'], 20);
        add_filter('the_content', [$this, 'modifyContent'], 10, 1);
    }
}
```

### After (Framework)
```php
class NewController extends Controller {
    public $actions = [
        'init' => 'initialize',
        'wp_enqueue_scripts' => [
            'callback' => 'enqueueAssets',
            'priority' => 20
        ]
    ];
    
    public $filters = [
        'the_content' => 'modifyContent'
    ];
}
```

The declarative approach makes your code cleaner, more maintainable, and easier to understand at a glance.