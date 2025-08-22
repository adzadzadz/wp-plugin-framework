# Controllers

Controllers in the ADZ WordPress Plugin Framework provide a structured way to organize your plugin's logic following the MVC (Model-View-Controller) pattern. They handle user interactions, process data, and coordinate between models and views.

## Table of Contents

- [Basic Controller Structure](#basic-controller-structure)
- [Hook Registration](#hook-registration)
- [Controller Lifecycle](#controller-lifecycle)
- [Advanced Features](#advanced-features)
- [Best Practices](#best-practices)
- [Examples](#examples)

## Basic Controller Structure

All controllers extend the base `Controller` class and can define WordPress actions and filters declaratively:

```php
<?php

namespace App\Controllers;

use AdzWP\Core\Controller;

class ExampleController extends Controller
{
    // Define WordPress actions to hook into
    public $actions = [
        'init' => 'initialize',
        'wp_enqueue_scripts' => 'enqueueScripts',
        'admin_menu' => 'addAdminMenu'
    ];

    // Define WordPress filters to hook into
    public $filters = [
        'the_content' => 'modifyContent',
        'post_class' => 'addCustomClasses'
    ];

    public function initialize()
    {
        // Controller initialization logic
    }
}
```

## Hook Registration

### Actions

Actions are WordPress hooks that allow you to execute code at specific points:

```php
public $actions = [
    'init' => 'initialize',                    // Simple method call
    'wp_enqueue_scripts' => [                 // With priority and args
        'callback' => 'enqueueScripts',
        'priority' => 20,
        'accepted_args' => 1
    ],
    'admin_menu' => ['addAdminMenu', 10, 0]   // Array format
];
```

### Filters

Filters allow you to modify data as it passes through WordPress:

```php
public $filters = [
    'the_content' => 'modifyContent',
    'post_class' => [
        'callback' => 'addCustomClasses',
        'priority' => 10,
        'accepted_args' => 2
    ]
];
```

### Dynamic Hook Registration

You can also register hooks dynamically in your methods:

```php
public function initialize()
{
    // Register additional hooks based on conditions
    if ($this->isAdmin()) {
        $this->registerAction('admin_notices', 'showAdminNotices');
    }
    
    if (get_option('my_plugin_feature_enabled')) {
        $this->registerFilter('wp_nav_menu_items', 'addMenuItems');
    }
}
```

## Controller Lifecycle

### Bootstrap Method

Override the `bootstrap()` method for additional initialization:

```php
protected function bootstrap()
{
    // Called after hooks are registered but before WordPress init
    $this->setupCustomCapabilities();
    $this->loadTranslations();
}
```

### Constructor

Use the constructor for dependency injection and early setup:

```php
public function __construct()
{
    parent::__construct();
    
    // Set up dependencies
    $this->api = new ApiService();
    $this->cache = new CacheManager();
    
    // Register plugin lifecycle hooks
    $this->setupPluginHooks();
}
```

## Advanced Features

### Conditional Loading

Load controllers based on context:

```php
public function initialize()
{
    if ($this->isAdmin()) {
        // Admin-specific logic
        $this->setupAdminInterface();
    } elseif ($this->isFrontend()) {
        // Frontend-specific logic
        $this->setupFrontendFeatures();
    } elseif ($this->isAjax()) {
        // AJAX-specific logic
        $this->setupAjaxHandlers();
    }
}
```

### Helper Methods

The base controller provides useful helper methods:

```php
public function someMethod()
{
    // Context checks
    if ($this->isAdmin()) { /* admin logic */ }
    if ($this->isFrontend()) { /* frontend logic */ }
    if ($this->isAjax()) { /* ajax logic */ }
    
    // Security helpers
    $this->verifyCap('manage_options');
    $this->verifyNonce('my_action', $_POST['nonce']);
    
    // Data handling
    $clean_data = $this->sanitize($_POST['data']);
    $validated = $this->validate($data, $rules);
}
```

### Service Container Integration

Access services through the container:

```php
public function initialize()
{
    // Bind services
    $this->bind('mailer', function() {
        return new MailService();
    });
    
    // Use services
    $mailer = $this->get('mailer');
    $mailer->send($email);
}
```

## Best Practices

### 1. Single Responsibility

Each controller should handle a specific area of functionality:

```php
// Good - specific purpose
class UserProfileController extends Controller { }
class ProductCatalogController extends Controller { }

// Avoid - too generic
class MainController extends Controller { }
```

### 2. Method Naming

Use descriptive method names that clearly indicate their purpose:

```php
// Good
public function handleContactFormSubmission() { }
public function displayProductGallery() { }

// Avoid
public function doStuff() { }
public function handle() { }
```

### 3. Hook Organization

Group related hooks together and use consistent naming:

```php
public $actions = [
    // Initialization hooks
    'init' => 'initialize',
    'admin_init' => 'initializeAdmin',
    
    // Asset hooks
    'wp_enqueue_scripts' => 'enqueueFrontendAssets',
    'admin_enqueue_scripts' => 'enqueueAdminAssets',
    
    // Menu hooks
    'admin_menu' => 'registerAdminMenus',
];
```

### 4. Error Handling

Implement proper error handling:

```php
public function processFormData()
{
    try {
        $this->validateFormData($_POST);
        $this->saveFormData($_POST);
        $this->addSuccessMessage('Data saved successfully');
    } catch (ValidationException $e) {
        $this->addErrorMessage($e->getMessage());
    } catch (Exception $e) {
        $this->logError($e);
        $this->addErrorMessage('An error occurred. Please try again.');
    }
}
```

## Examples

### Admin Settings Controller

```php
<?php

namespace App\Controllers;

use AdzWP\Core\Controller;

class AdminSettingsController extends Controller
{
    public $actions = [
        'admin_menu' => 'addSettingsPage',
        'admin_init' => 'registerSettings'
    ];

    public function addSettingsPage()
    {
        add_options_page(
            'My Plugin Settings',
            'My Plugin',
            'manage_options',
            'my-plugin-settings',
            [$this, 'renderSettingsPage']
        );
    }

    public function registerSettings()
    {
        register_setting('my_plugin_settings', 'my_plugin_options', [
            'sanitize_callback' => [$this, 'sanitizeOptions']
        ]);

        add_settings_section(
            'general',
            'General Settings',
            [$this, 'renderGeneralSection'],
            'my-plugin-settings'
        );

        add_settings_field(
            'enable_feature',
            'Enable Feature',
            [$this, 'renderEnableField'],
            'my-plugin-settings',
            'general'
        );
    }

    public function renderSettingsPage()
    {
        echo '<div class="wrap">';
        echo '<h1>My Plugin Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('my_plugin_settings');
        do_settings_sections('my-plugin-settings');
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    public function sanitizeOptions($input)
    {
        $sanitized = [];
        $sanitized['enable_feature'] = !empty($input['enable_feature']);
        return $sanitized;
    }
}
```

### AJAX Controller

```php
<?php

namespace App\Controllers;

use AdzWP\Core\Controller;

class AjaxController extends Controller
{
    public $actions = [
        'wp_ajax_load_posts' => 'handleLoadPosts',
        'wp_ajax_nopriv_load_posts' => 'handleLoadPosts',
        'wp_ajax_save_settings' => 'handleSaveSettings'
    ];

    public function handleLoadPosts()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'load_posts_nonce')) {
            wp_send_json_error('Security check failed');
        }

        $page = intval($_POST['page'] ?? 1);
        $posts_per_page = 10;

        $posts = get_posts([
            'posts_per_page' => $posts_per_page,
            'paged' => $page,
            'post_status' => 'publish'
        ]);

        $html = '';
        foreach ($posts as $post) {
            $html .= sprintf(
                '<div class="post-item"><h3>%s</h3><p>%s</p></div>',
                esc_html($post->post_title),
                esc_html(wp_trim_words($post->post_content, 20))
            );
        }

        wp_send_json_success([
            'html' => $html,
            'has_more' => count($posts) === $posts_per_page
        ]);
    }

    public function handleSaveSettings()
    {
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'save_settings_nonce')) {
            wp_send_json_error('Security check failed');
        }

        $settings = $_POST['settings'] ?? [];
        $sanitized_settings = $this->sanitizeSettings($settings);

        update_option('my_plugin_settings', $sanitized_settings);

        wp_send_json_success('Settings saved successfully');
    }

    private function sanitizeSettings($settings)
    {
        return [
            'option1' => sanitize_text_field($settings['option1'] ?? ''),
            'option2' => intval($settings['option2'] ?? 0),
            'option3' => !empty($settings['option3'])
        ];
    }
}
```

### Custom Post Type Controller

```php
<?php

namespace App\Controllers;

use AdzWP\Core\Controller;

class ProductController extends Controller
{
    public $actions = [
        'init' => 'registerPostType',
        'add_meta_boxes' => 'addMetaBoxes',
        'save_post' => 'saveMetaData'
    ];

    public $filters = [
        'manage_product_posts_columns' => 'addCustomColumns',
        'manage_product_posts_custom_column' => 'renderCustomColumns'
    ];

    public function registerPostType()
    {
        register_post_type('product', [
            'labels' => [
                'name' => 'Products',
                'singular_name' => 'Product',
                'add_new_item' => 'Add New Product'
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'show_in_rest' => true
        ]);
    }

    public function addMetaBoxes()
    {
        add_meta_box(
            'product_details',
            'Product Details',
            [$this, 'renderProductMetaBox'],
            'product',
            'normal',
            'high'
        );
    }

    public function renderProductMetaBox($post)
    {
        wp_nonce_field('save_product_meta', 'product_meta_nonce');
        
        $price = get_post_meta($post->ID, '_product_price', true);
        $sku = get_post_meta($post->ID, '_product_sku', true);

        echo '<table class="form-table">';
        echo '<tr><th><label for="product_price">Price</label></th>';
        echo '<td><input type="number" id="product_price" name="product_price" value="' . esc_attr($price) . '" step="0.01" /></td></tr>';
        echo '<tr><th><label for="product_sku">SKU</label></th>';
        echo '<td><input type="text" id="product_sku" name="product_sku" value="' . esc_attr($sku) . '" /></td></tr>';
        echo '</table>';
    }

    public function saveMetaData($post_id)
    {
        if (!isset($_POST['product_meta_nonce']) || 
            !wp_verify_nonce($_POST['product_meta_nonce'], 'save_product_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['product_price'])) {
            update_post_meta($post_id, '_product_price', floatval($_POST['product_price']));
        }

        if (isset($_POST['product_sku'])) {
            update_post_meta($post_id, '_product_sku', sanitize_text_field($_POST['product_sku']));
        }
    }
}
```

## Related Documentation

- [Plugin Lifecycle Management](PLUGIN_LIFECYCLE.md)
- [Models & Database](models-database.md)
- [Views & Templates](views.md)
- [Security](security.md)