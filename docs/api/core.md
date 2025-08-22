# Core Classes API Reference

This document provides a comprehensive reference for the core classes in the ADZ WordPress Plugin Framework.

## Table of Contents

- [Core](#core)
- [Plugin](#plugin)
- [PluginManager](#pluginmanager)
- [Controller](#controller)
- [Dependency](#dependency)
- [Config](#config)

## Core

Base class that provides common functionality for all framework classes.

### Properties

```php
protected $container = [];  // Service container
public $pluginPath;         // Plugin path
```

### Methods

#### `__construct(array $args = [])`

Constructor that accepts arguments to set properties.

```php
$instance = new SomeClass([
    'pluginPath' => '/path/to/plugin'
]);
```

#### `init()`

Initialization method called during construction. Override in child classes.

#### `registerHooks()`

Automatically registers actions and filters defined in `$actions` and `$filters` properties.

#### `registerAction($hook, $callback)`

Register a WordPress action hook.

**Parameters:**
- `$hook` (string): WordPress action name
- `$callback` (string|array|callable): Callback function

#### `registerFilter($hook, $callback)`

Register a WordPress filter hook.

**Parameters:**
- `$hook` (string): WordPress filter name  
- `$callback` (string|array|callable): Callback function

#### `bootstrap()`

Override this method in child classes for additional initialization.

#### `bind($key, $value)`

Bind a value to the service container.

#### `get($key, $default = null)`

Get a value from the service container.

---

## Plugin

Main plugin class that handles plugin lifecycle and dependency management.

### Constants

```php
const _ROLE = 'administrator';
const _CAP = [
    'main_tools' => 'adz_manage_tools'
];
```

### Static Methods

#### `onInstall($callback, $priority = 10)`

Register a callback to run during plugin installation.

**Parameters:**
- `$callback` (callable): Function to execute
- `$priority` (int): Hook priority (lower = earlier)

**Example:**
```php
Plugin::onInstall(function() {
    // Create database tables
}, 5);
```

#### `onUninstall($callback, $priority = 10)`

Register a callback to run during plugin uninstallation.

#### `onActivate($callback, $priority = 10)`

Register a callback to run during plugin activation.

#### `onDeactivate($callback, $priority = 10)`

Register a callback to run during plugin deactivation.

#### `install()`

Execute installation hooks and dependencies.

#### `uninstall()`

Execute uninstallation hooks and cleanup.

#### `activate()`

Execute activation hooks.

#### `deactivate()`

Execute deactivation hooks.

### Instance Methods

#### `load($controllers = [])`

Load and initialize controller classes.

**Parameters:**
- `$controllers` (array): Array of controller class names

#### `has($did)`

Check if a dependency is active.

#### `getDep($did)`

Get dependency configuration.

#### `setDep($did, $option, $new_value)`

Set dependency option value.

---

## PluginManager

Simplified interface for managing plugin lifecycle and dependencies.

### Static Methods

#### `getInstance($plugin_file = '')`

Get or create singleton instance.

**Parameters:**
- `$plugin_file` (string): Main plugin file path

**Returns:** PluginManager instance

### Instance Methods

#### `setDependencies(array $dependencies)`

Set plugin dependencies.

**Parameters:**
- `$dependencies` (array): Array of dependency configurations

**Returns:** self (for method chaining)

#### `addDependency(array $dependency)`

Add a single dependency.

**Parameters:**
- `$dependency` (array): Dependency configuration

**Returns:** self

#### `installDependencies()`

Install all configured dependencies.

**Returns:** array - Installation results

#### `onInstall($callback, $priority = 10)`

Register install hook.

**Returns:** self

#### `onUninstall($callback, $priority = 10)`

Register uninstall hook.

**Returns:** self

#### `onActivate($callback, $priority = 10)`

Register activate hook.

**Returns:** self

#### `onDeactivate($callback, $priority = 10)`

Register deactivate hook.

**Returns:** self

#### `setupDatabase()`

Set up database-related hooks.

**Returns:** self

#### `setupOptions(array $default_options = [])`

Set up default plugin options.

**Parameters:**
- `$default_options` (array): Default option values

**Returns:** self

#### `setupCapabilities(array $capabilities = [], array $roles = ['administrator'])`

Set up user capabilities.

**Parameters:**
- `$capabilities` (array): Capabilities to add
- `$roles` (array): Roles to add capabilities to

**Returns:** self

---

## Controller

Base controller class for handling WordPress hooks and functionality.

### Properties

```php
public $actions = [];   // WordPress actions to register
public $filters = [];   // WordPress filters to register
```

### Methods

#### `isAdmin()`

Check if current request is in admin area.

**Returns:** bool

#### `isFrontend()`

Check if current request is frontend.

**Returns:** bool

#### `isAjax()`

Check if current request is AJAX.

**Returns:** bool

---

## Dependency

Static class for managing plugin dependencies.

### Static Methods

#### `auto_install_dependencies(array $dependencies = [])`

Automatically install and activate dependencies.

**Parameters:**
- `$dependencies` (array): Array of dependency configurations

**Returns:** array - Installation results

**Example:**
```php
$results = Dependency::auto_install_dependencies([
    [
        'slug' => 'woocommerce/woocommerce.php',
        'name' => 'WooCommerce',
        'source' => 'repo'
    ]
]);
```

#### `install_from_repo($slug)`

Install plugin from WordPress.org repository.

**Parameters:**
- `$slug` (string): Plugin slug

**Returns:** bool - Success status

#### `install_plugin($zip_url)`

Install plugin from URL.

**Parameters:**
- `$zip_url` (string): Plugin ZIP download URL

**Returns:** bool - Success status

#### `is_active($slug)`

Check if plugin is active.

**Parameters:**
- `$slug` (string): Plugin slug

**Returns:** int - Status constant

#### `is_installed($slug)`

Check if plugin is installed.

**Parameters:**
- `$slug` (string): Plugin slug

**Returns:** int - Status constant

#### `is_ready($className)`

Check if required class exists.

**Parameters:**
- `$className` (string): Class name to check

**Returns:** bool

#### `monitor_status()`

Monitor dependency status and show admin notices.

#### `addAdminNotice($depName)`

Add admin notice for missing dependency.

---

## Config

Configuration management class.

### Static Methods

#### `getInstance()`

Get singleton configuration instance.

**Returns:** Config instance

### Instance Methods

#### `get($key, $default = null)`

Get configuration value.

**Parameters:**
- `$key` (string): Configuration key (supports dot notation)
- `$default` (mixed): Default value if not found

**Returns:** mixed

#### `set($key, $value)`

Set configuration value.

**Parameters:**
- `$key` (string): Configuration key (supports dot notation)
- `$value` (mixed): Value to set

#### `has($key)`

Check if configuration key exists.

**Parameters:**
- `$key` (string): Configuration key

**Returns:** bool

#### `all()`

Get all configuration values.

**Returns:** array

## Status Constants

Used throughout the framework to represent plugin/dependency status:

```php
const STATUS_ACTIVE = 1;
const STATUS_INACTIVE = 0;
const STATUS_INSTALLED = 1;
const STATUS_UNINSTALLED = 0;
const STATUS_INSTALL_FAILED = -1;
```

## Usage Examples

### Basic Plugin Setup

```php
// Initialize plugin manager
$manager = PluginManager::getInstance(__FILE__);

// Set up lifecycle
$manager
    ->onActivate(function() {
        // Activation logic
    })
    ->setupOptions(['my_option' => 'default'])
    ->setDependencies([
        [
            'slug' => 'woocommerce/woocommerce.php',
            'name' => 'WooCommerce',
            'source' => 'repo'
        ]
    ]);

// Load controllers
$plugin = new Plugin();
$plugin->load(['Main', 'Admin']);
```

### Controller with Hooks

```php
class MyController extends Controller
{
    public $actions = [
        'init' => 'initialize',
        'wp_enqueue_scripts' => 'enqueueAssets'
    ];
    
    public $filters = [
        'the_content' => 'modifyContent'
    ];
    
    public function initialize()
    {
        if ($this->isAdmin()) {
            // Admin logic
        }
    }
}
```