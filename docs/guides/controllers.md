# Creating Controllers

Controllers are the heart of your plugin's functionality in the ADZ Plugin Framework. They handle requests, process data, and coordinate between models and views. This guide will show you how to create and use controllers effectively.

## What are Controllers?

Controllers in the ADZ Framework:
- Handle WordPress hooks (actions and filters)
- Process form submissions and AJAX requests
- Coordinate business logic
- Manage security and validation
- Render views and responses

## Creating Your First Controller

### Step 1: Generate the Controller

Use the CLI to create a new controller:

```bash
./adz.sh make:controller UserController
```

This creates `src/controllers/UserController.php`:

```php
<?php

namespace MyPlugin\Controllers;

use AdzWP\Controller;

class UserController extends Controller 
{
    public $actions = [
        // 'init' => 'initialize',
    ];
    
    public $filters = [
        // 'the_content' => 'modifyContent',
    ];
    
    protected function bootstrap()
    {
        // Initialization code here
    }
    
    // Add your methods here
}
```

### Step 2: Add WordPress Hooks

Define the WordPress hooks your controller will handle:

```php
<?php

namespace MyPlugin\Controllers;

use AdzWP\Controller;
use AdzHive\Security;
use AdzHive\Config;

class UserController extends Controller 
{
    protected $security;
    protected $config;
    
    public $actions = [
        'init' => 'initialize',
        'admin_menu' => 'addAdminMenu',
        'admin_post_save_user_settings' => 'saveUserSettings',
        'wp_ajax_get_user_data' => 'getUserData',
        'wp_enqueue_scripts' => 'enqueueAssets'
    ];
    
    public $filters = [
        'user_profile_fields' => 'addCustomFields',
        'user_display_name' => 'customizeDisplayName'
    ];
    
    protected function bootstrap()
    {
        $this->security = Security::getInstance();
        $this->config = Config::getInstance();
    }
    
    // Methods will be implemented below
}
```

### Step 3: Implement Controller Methods

Add the methods referenced in your hooks:

```php
public function initialize()
{
    // Plugin initialization logic
    load_plugin_textdomain(
        $this->config->get('plugin.text_domain'),
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}

public function addAdminMenu()
{
    add_menu_page(
        'User Management',
        'Users',
        'manage_users',
        'user-management',
        [$this, 'renderUserManagementPage'],
        'dashicons-admin-users',
        30
    );
}

public function saveUserSettings()
{
    try {
        // Security checks
        $this->security->checkCapability('manage_users');
        $this->security->verifyRequest('_user_settings_nonce', 'save_user_settings');
        
        // Validate and process data
        $this->processUserSettings($_POST);
        
        // Redirect with success message
        wp_redirect(add_query_arg('message', 'saved', admin_url('admin.php?page=user-management')));
        exit;
        
    } catch (Exception $e) {
        wp_redirect(add_query_arg('error', urlencode($e->getMessage()), wp_get_referer()));
        exit;
    }
}

public function getUserData()
{
    try {
        $this->security->verifyAjaxRequest();
        $this->security->checkCapability('read');
        
        $userId = intval($_GET['user_id'] ?? 0);
        $userData = $this->fetchUserData($userId);
        
        wp_send_json_success($userData);
        
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}

public function enqueueAssets()
{
    if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'user-management') {
        wp_enqueue_script(
            'user-management-js',
            plugin_dir_url(__FILE__) . '../assets/js/user-management.js',
            ['jquery'],
            $this->config->get('plugin.version'),
            true
        );
        
        wp_localize_script('user-management-js', 'userManagement', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('user_management_ajax')
        ]);
    }
}
```

## Controller Patterns

### Admin Page Controller

```php
<?php

namespace MyPlugin\Controllers;

use AdzWP\Controller;
use AdzHive\Security;
use AdzHive\Validator;
use AdzHive\ValidationException;

class AdminController extends Controller 
{
    public $actions = [
        'admin_menu' => 'addAdminPages',
        'admin_init' => 'initializeAdmin',
        'admin_enqueue_scripts' => 'enqueueAdminAssets',
        'admin_post_save_settings' => 'saveSettings'
    ];
    
    public function addAdminPages()
    {
        // Main menu page
        add_menu_page(
            'My Plugin Settings',
            'My Plugin',
            'manage_options',
            'my-plugin',
            [$this, 'renderMainPage'],
            'dashicons-admin-settings',
            80
        );
        
        // Submenu pages
        add_submenu_page(
            'my-plugin',
            'General Settings',
            'General',
            'manage_options',
            'my-plugin',
            [$this, 'renderMainPage']
        );
        
        add_submenu_page(
            'my-plugin',
            'Advanced Settings',
            'Advanced',
            'manage_options',
            'my-plugin-advanced',
            [$this, 'renderAdvancedPage']
        );
    }
    
    public function renderMainPage()
    {
        $this->security->checkCapability('manage_options');
        
        $data = [
            'settings' => get_option('my_plugin_settings', []),
            'nonce' => wp_create_nonce('save_settings')
        ];
        
        echo $this->renderView('admin/main-page', $data);
    }
    
    public function saveSettings()
    {
        try {
            $this->security->checkCapability('manage_options');
            $this->security->verifyRequest('_settings_nonce', 'save_settings');
            
            // Validate settings
            $validator = Validator::make($_POST, [
                'api_key' => 'required|string|min:10',
                'cache_timeout' => 'numeric|between:60,3600',
                'features' => 'array',
                'features.*' => 'boolean'
            ]);
            
            if ($validator->fails()) {
                throw new ValidationException('Invalid settings', $validator->errors());
            }
            
            // Sanitize and save
            $settings = $this->security->sanitizeArray($_POST, [
                'api_key' => 'text',
                'cache_timeout' => 'int',
                'features' => 'array'
            ]);
            
            update_option('my_plugin_settings', $settings);
            
            wp_redirect(add_query_arg('message', 'settings_saved', admin_url('admin.php?page=my-plugin')));
            exit;
            
        } catch (Exception $e) {
            wp_redirect(add_query_arg('error', urlencode($e->getMessage()), wp_get_referer()));
            exit;
        }
    }
}
```

### Frontend Controller

```php
<?php

namespace MyPlugin\Controllers;

use AdzWP\Controller;
use AdzHive\Security;

class FrontendController extends Controller 
{
    public $actions = [
        'wp_enqueue_scripts' => 'enqueueFrontendAssets',
        'init' => 'registerShortcodes',
        'wp_ajax_frontend_action' => 'handleFrontendAjax',
        'wp_ajax_nopriv_frontend_action' => 'handleFrontendAjax'
    ];
    
    public $filters = [
        'the_content' => 'modifyPostContent',
        'wp_nav_menu_items' => 'addCustomMenuItems',
        'body_class' => 'addBodyClasses'
    ];
    
    public function enqueueFrontendAssets()
    {
        if (!is_admin()) {
            wp_enqueue_style(
                'my-plugin-frontend',
                plugin_dir_url(__FILE__) . '../assets/css/frontend.css',
                [],
                $this->config->get('plugin.version')
            );
            
            wp_enqueue_script(
                'my-plugin-frontend',
                plugin_dir_url(__FILE__) . '../assets/js/frontend.js',
                ['jquery'],
                $this->config->get('plugin.version'),
                true
            );
            
            wp_localize_script('my-plugin-frontend', 'myPlugin', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('frontend_nonce')
            ]);
        }
    }
    
    public function registerShortcodes()
    {
        add_shortcode('my_plugin_form', [$this, 'renderFormShortcode']);
        add_shortcode('my_plugin_data', [$this, 'renderDataShortcode']);
    }
    
    public function renderFormShortcode($atts, $content = '')
    {
        $atts = shortcode_atts([
            'type' => 'contact',
            'title' => 'Contact Form',
            'redirect' => ''
        ], $atts);
        
        return $this->renderView('shortcodes/form', [
            'attributes' => $atts,
            'nonce' => wp_create_nonce('form_submit'),
            'content' => $content
        ]);
    }
    
    public function modifyPostContent($content)
    {
        if (is_single() && in_the_loop() && is_main_query()) {
            $customContent = $this->renderView('post/additional-content', [
                'post_id' => get_the_ID()
            ]);
            
            return $content . $customContent;
        }
        
        return $content;
    }
    
    public function handleFrontendAjax()
    {
        try {
            $this->security->verifyAjaxRequest('frontend_nonce');
            
            $action = sanitize_text_field($_POST['frontend_action'] ?? '');
            
            switch ($action) {
                case 'submit_form':
                    $result = $this->handleFormSubmission();
                    break;
                    
                case 'load_data':
                    $result = $this->loadDynamicData();
                    break;
                    
                default:
                    throw new Exception('Invalid action');
            }
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
}
```

### API Controller

```php
<?php

namespace MyPlugin\Controllers;

use AdzWP\Controller;
use AdzHive\Security;
use AdzHive\Validator;

class ApiController extends Controller 
{
    public $actions = [
        'rest_api_init' => 'registerApiRoutes'
    ];
    
    public function registerApiRoutes()
    {
        register_rest_route('my-plugin/v1', '/users', [
            'methods' => 'GET',
            'callback' => [$this, 'getUsers'],
            'permission_callback' => [$this, 'checkApiPermission']
        ]);
        
        register_rest_route('my-plugin/v1', '/users/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'getUser'],
            'permission_callback' => [$this, 'checkApiPermission']
        ]);
        
        register_rest_route('my-plugin/v1', '/users', [
            'methods' => 'POST',
            'callback' => [$this, 'createUser'],
            'permission_callback' => [$this, 'checkApiPermission']
        ]);
    }
    
    public function checkApiPermission()
    {
        return current_user_can('read');
    }
    
    public function getUsers(WP_REST_Request $request)
    {
        try {
            $page = $request->get_param('page') ?: 1;
            $per_page = min(50, $request->get_param('per_page') ?: 10);
            
            $users = $this->fetchUsers($page, $per_page);
            
            return new WP_REST_Response([
                'users' => $users,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $per_page,
                    'total' => $this->getTotalUsers()
                ]
            ], 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(['error' => $e->getMessage()], 500);
        }
    }
    
    public function createUser(WP_REST_Request $request)
    {
        try {
            $this->security->checkCapability('create_users');
            
            $data = $request->get_json_params();
            
            $validator = Validator::make($data, [
                'username' => 'required|string|min:3|max:20',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8'
            ]);
            
            if ($validator->fails()) {
                return new WP_REST_Response([
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }
            
            $userId = $this->createNewUser($data);
            
            return new WP_REST_Response([
                'user_id' => $userId,
                'message' => 'User created successfully'
            ], 201);
            
        } catch (Exception $e) {
            return new WP_REST_Response(['error' => $e->getMessage()], 500);
        }
    }
}
```

## Advanced Controller Features

### Middleware Pattern

```php
<?php

namespace MyPlugin\Controllers;

use AdzWP\Controller;

class SecureController extends Controller 
{
    protected $middleware = [
        'auth' => ['adminOnly'],
        'csrf' => ['saveSettings', 'deleteData'],
        'rate_limit' => ['submitForm']
    ];
    
    public function adminOnly()
    {
        if (!is_admin()) {
            wp_die('Admin access required');
        }
    }
    
    protected function bootstrap()
    {
        $this->applyMiddleware();
    }
    
    protected function applyMiddleware()
    {
        foreach ($this->middleware as $middleware => $methods) {
            foreach ($methods as $method) {
                if (method_exists($this, $method)) {
                    add_action("before_{$method}", [$this, $middleware]);
                }
            }
        }
    }
}
```

### Controller Inheritance

```php
<?php

namespace MyPlugin\Controllers;

use AdzWP\Controller;
use AdzHive\Security;
use AdzHive\Config;

// Base controller with common functionality
abstract class BaseController extends Controller 
{
    protected $security;
    protected $config;
    
    protected function bootstrap()
    {
        $this->security = Security::getInstance();
        $this->config = Config::getInstance();
        
        $this->setupCommonHooks();
    }
    
    protected function setupCommonHooks()
    {
        // Add common hooks for all controllers
        $this->actions['wp_enqueue_scripts'] = 'enqueueCommonAssets';
    }
    
    public function enqueueCommonAssets()
    {
        wp_enqueue_style(
            'my-plugin-common',
            plugin_dir_url(__FILE__) . '../assets/css/common.css',
            [],
            $this->config->get('plugin.version')
        );
    }
    
    protected function renderView($template, $data = [])
    {
        extract($data);
        
        $templatePath = plugin_dir_path(__FILE__) . "../views/{$template}.php";
        
        if (!file_exists($templatePath)) {
            throw new Exception("Template not found: {$template}");
        }
        
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }
}

// Specific controller extending base
class PostController extends BaseController 
{
    public $actions = [
        'init' => 'registerPostTypes'
    ];
    
    public function registerPostTypes()
    {
        register_post_type('my_custom_post', [
            'public' => true,
            'label' => 'Custom Posts',
            'supports' => ['title', 'editor', 'thumbnail']
        ]);
    }
}
```

## Controller Testing

### Unit Testing Controllers

```php
<?php

use PHPUnit\Framework\TestCase;
use MyPlugin\Controllers\UserController;

class UserControllerTest extends TestCase 
{
    protected $controller;
    
    protected function setUp(): void
    {
        $this->controller = new UserController();
    }
    
    public function testInitialize()
    {
        // Mock WordPress functions
        $this->mockFunction('load_plugin_textdomain');
        
        $this->controller->initialize();
        
        // Assert that text domain was loaded
        $this->assertTrue($this->functionWasCalled('load_plugin_textdomain'));
    }
    
    public function testSaveUserSettingsWithValidData()
    {
        // Mock security checks
        $this->mockSecurityChecks();
        
        $_POST = [
            'user_name' => 'John Doe',
            'user_email' => 'john@example.com',
            '_user_settings_nonce' => 'valid_nonce'
        ];
        
        // Test should not throw exception
        $this->expectNotToPerformAssertions();
        $this->controller->saveUserSettings();
    }
    
    public function testSaveUserSettingsWithInvalidData()
    {
        $this->mockSecurityChecks();
        
        $_POST = [
            'user_name' => '', // Invalid: empty name
            'user_email' => 'invalid-email', // Invalid: bad email
            '_user_settings_nonce' => 'valid_nonce'
        ];
        
        $this->expectException(ValidationException::class);
        $this->controller->saveUserSettings();
    }
}
```

## Best Practices

### 1. Single Responsibility

Each controller should handle one area of functionality:

```php
// Good: Focused on user management
class UserController extends Controller { }

// Good: Focused on post management  
class PostController extends Controller { }

// Bad: Handles everything
class MainController extends Controller { }
```

### 2. Use Descriptive Method Names

```php
// Good: Clear what the method does
public function handleContactFormSubmission() { }
public function renderUserProfilePage() { }
public function validateUserRegistration() { }

// Bad: Unclear method names
public function process() { }
public function handle() { }
public function doStuff() { }
```

### 3. Handle Errors Gracefully

```php
public function saveData()
{
    try {
        $this->security->verifyRequest();
        
        // Process data
        $result = $this->processData($_POST);
        
        // Success response
        wp_redirect(add_query_arg('message', 'success', wp_get_referer()));
        
    } catch (ValidationException $e) {
        // Handle validation errors
        $this->redirectWithErrors($e->getErrors());
        
    } catch (SecurityException $e) {
        // Handle security issues
        adz_log_warning('Security violation', ['error' => $e->getMessage()]);
        wp_die('Access denied');
        
    } catch (Exception $e) {
        // Handle general errors
        adz_log_error('Unexpected error', ['error' => $e->getMessage()]);
        wp_redirect(add_query_arg('error', 'general', wp_get_referer()));
    }
}
```

### 4. Keep Controllers Thin

Move complex business logic to services or models:

```php
// Good: Controller delegates to service
class OrderController extends Controller 
{
    protected $orderService;
    
    protected function bootstrap()
    {
        $this->orderService = new OrderService();
    }
    
    public function createOrder()
    {
        try {
            $this->security->verifyRequest();
            
            $orderData = $this->validateOrderData($_POST);
            $order = $this->orderService->createOrder($orderData);
            
            wp_redirect(add_query_arg('order_id', $order->id, '/order-success/'));
            
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
}

// Bad: Controller does everything
class OrderController extends Controller 
{
    public function createOrder()
    {
        // 100+ lines of business logic here
    }
}
```

### 5. Use Dependency Injection

```php
class PaymentController extends Controller 
{
    protected $paymentGateway;
    protected $orderService;
    
    public function __construct(PaymentGateway $gateway, OrderService $service)
    {
        $this->paymentGateway = $gateway;
        $this->orderService = $service;
        
        parent::__construct();
    }
    
    public function processPayment()
    {
        // Use injected dependencies
        $result = $this->paymentGateway->charge($amount);
        $this->orderService->updateStatus($orderId, 'paid');
    }
}
```

Controllers are the backbone of your plugin's functionality. By following these patterns and best practices, you'll create maintainable, secure, and testable code that integrates seamlessly with WordPress.