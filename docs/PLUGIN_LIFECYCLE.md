# Plugin Lifecycle & Dependency Management

The ADZ WordPress Plugin Framework now includes comprehensive plugin lifecycle management and automatic dependency installation features.

## Features

### 🔄 Plugin Lifecycle Hooks
- **Install Hook**: Runs when plugin is first installed
- **Activate Hook**: Runs when plugin is activated
- **Deactivate Hook**: Runs when plugin is deactivated  
- **Uninstall Hook**: Runs when plugin is completely removed

### 📦 Automatic Dependency Management
- Install plugins from WordPress.org repository
- Install plugins from custom URLs
- Automatic activation after installation
- Dependency status monitoring

## Quick Start

### Basic Setup

```php
// In your main plugin file
$pluginManager = \AdzWP\Core\PluginManager::getInstance(__FILE__);

$pluginManager
    ->onInstall(function() {
        // One-time setup tasks
        create_custom_tables();
    })
    ->onActivate(function() {
        // Run on every activation
        update_rewrite_rules();
    })
    ->onDeactivate(function() {
        // Temporary cleanup
        clear_caches();
    })
    ->onUninstall(function() {
        // Complete removal
        remove_all_data();
    });
```

### Dependency Management

```php
// Set up dependencies that will be auto-installed
$pluginManager->setDependencies([
    [
        'slug' => 'woocommerce/woocommerce.php',
        'name' => 'WooCommerce',
        'source' => 'repo' // WordPress.org repository
    ],
    [
        'slug' => 'custom-plugin/custom-plugin.php', 
        'name' => 'Custom Plugin',
        'source' => 'url',
        'url' => 'https://example.com/plugin.zip'
    ]
]);

// Install dependencies manually if needed
$results = $pluginManager->installDependencies();
```

### Helper Methods

```php
$pluginManager
    // Set up default options
    ->setupOptions([
        'my_plugin_setting' => 'default_value',
        'my_plugin_enabled' => true
    ])
    
    // Set up user capabilities
    ->setupCapabilities([
        'manage_my_plugin',
        'edit_my_plugin_data'
    ], ['administrator', 'editor'])
    
    // Set up database tables (override createTables method)
    ->setupDatabase();
```

## Advanced Usage

### Using Hooks in Controllers

```php
class MyController extends \AdzWP\Core\Controller 
{
    public function __construct() 
    {
        parent::__construct();
        
        // Register hooks from within controller
        \AdzWP\Core\Plugin::onActivate([$this, 'onActivate']);
        \AdzWP\Core\Plugin::onDeactivate([$this, 'onDeactivate']);
    }
    
    public function onActivate() 
    {
        // Controller-specific activation logic
    }
    
    public function onDeactivate() 
    {
        // Controller-specific deactivation logic
    }
}
```

### Hook Priorities

```php
// Higher priority (runs first)
\AdzWP\Core\Plugin::onInstall($callback1, 5);

// Lower priority (runs later)  
\AdzWP\Core\Plugin::onInstall($callback2, 15);
```

### Manual Dependency Installation

```php
use AdzWP\Core\Dependency;

// Install from WordPress.org
$result = Dependency::install_from_repo('woocommerce');

// Install from URL
$result = Dependency::install_plugin('https://example.com/plugin.zip');

// Auto-install multiple dependencies
$dependencies = [
    ['slug' => 'contact-form-7/wp-contact-form-7.php', 'name' => 'Contact Form 7', 'source' => 'repo']
];
$results = Dependency::auto_install_dependencies($dependencies);
```

## Hook Execution Order

1. **Installation** (first time only)
   - Install dependencies
   - Run install hooks
   - Set installation flags

2. **Activation** (every time activated)
   - Check if installed (run install if needed)
   - Run activate hooks
   - Update activation timestamp

3. **Deactivation**
   - Run deactivate hooks
   - Update deactivation timestamp

4. **Uninstallation** (complete removal)
   - Run uninstall hooks
   - Remove installation flags
   - Clear caches

## Best Practices

### Install Hook
- Create database tables
- Set up default options
- One-time configuration

### Activate Hook  
- Check system requirements
- Update database schema
- Set up cron jobs
- Clear caches

### Deactivate Hook
- Clear cron jobs
- Clear caches
- Temporary cleanup (keep user data)

### Uninstall Hook
- Remove database tables
- Remove options
- Complete data removal

## Dependency Configuration

```php
[
    'slug' => 'plugin-folder/plugin-file.php',  // Required
    'name' => 'Human Readable Name',           // Required
    'source' => 'repo',                        // 'repo' or 'url'
    'url' => 'https://example.com/plugin.zip' // Required if source is 'url'
]
```

## Error Handling

Hooks are executed with error handling - if one hook fails, others will still execute. Errors are logged to WordPress error log.

```php
\AdzWP\Core\Plugin::onInstall(function() {
    try {
        // Your installation code
    } catch (Exception $e) {
        error_log('Installation failed: ' . $e->getMessage());
        // Handle gracefully
    }
});
```