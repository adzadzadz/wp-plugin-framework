# Dependency Management

The ADZ WordPress Plugin Framework includes a powerful dependency management system that can automatically install and activate required plugins for your WordPress plugin. This ensures your plugin has all necessary dependencies without manual intervention.

## Table of Contents

- [Overview](#overview)
- [Configuration](#configuration)
- [Installation Sources](#installation-sources)
- [Usage Examples](#usage-examples)
- [Advanced Features](#advanced-features)
- [Error Handling](#error-handling)
- [Best Practices](#best-practices)

## Overview

The dependency management system can:

- ✅ Install plugins from WordPress.org repository
- ✅ Install plugins from custom URLs (ZIP files)
- ✅ Automatically activate plugins after installation
- ✅ Check plugin status and show admin notices
- ✅ Handle batch dependency installation
- ✅ Provide detailed installation results

## Configuration

### Basic Setup

Configure dependencies in your main plugin file:

```php
$pluginManager = \AdzWP\Core\PluginManager::getInstance(__FILE__);

$pluginManager->setDependencies([
    [
        'slug' => 'woocommerce/woocommerce.php',
        'name' => 'WooCommerce',
        'source' => 'repo'
    ],
    [
        'slug' => 'contact-form-7/wp-contact-form-7.php',
        'name' => 'Contact Form 7', 
        'source' => 'repo'
    ]
]);
```

### Dependency Configuration Options

Each dependency requires the following configuration:

```php
[
    'slug' => 'plugin-folder/plugin-file.php',  // Required: Plugin identifier
    'name' => 'Human Readable Name',           // Required: Display name
    'source' => 'repo|url',                    // Required: Installation source
    'url' => 'https://example.com/plugin.zip' // Required if source is 'url'
]
```

## Installation Sources

### WordPress.org Repository

For plugins available on WordPress.org:

```php
[
    'slug' => 'woocommerce/woocommerce.php',
    'name' => 'WooCommerce',
    'source' => 'repo'
]
```

The system will:
1. Query the WordPress.org API for plugin information
2. Download the latest version
3. Install and activate the plugin

### Custom URL

For premium or custom plugins:

```php
[
    'slug' => 'my-premium-plugin/my-premium-plugin.php',
    'name' => 'My Premium Plugin',
    'source' => 'url',
    'url' => 'https://mysite.com/downloads/my-premium-plugin.zip'
]
```

The system will:
1. Download the ZIP file from the provided URL
2. Extract and install the plugin
3. Activate the plugin

## Usage Examples

### Using PluginManager

The easiest way to manage dependencies:

```php
// Set dependencies
$pluginManager = \AdzWP\Core\PluginManager::getInstance(__FILE__);
$pluginManager->setDependencies([
    [
        'slug' => 'woocommerce/woocommerce.php',
        'name' => 'WooCommerce',
        'source' => 'repo'
    ],
    [
        'slug' => 'advanced-custom-fields/acf.php',
        'name' => 'Advanced Custom Fields',
        'source' => 'repo'
    ]
]);

// Install dependencies manually (optional)
$results = $pluginManager->installDependencies();
```

### Using Dependency Class Directly

For more control over the installation process:

```php
use AdzWP\Core\Dependency;

// Install single plugin from repository
$installed = Dependency::install_from_repo('woocommerce');

// Install single plugin from URL
$installed = Dependency::install_plugin('https://example.com/plugin.zip');

// Batch install multiple dependencies
$dependencies = [
    [
        'slug' => 'woocommerce/woocommerce.php',
        'name' => 'WooCommerce',
        'source' => 'repo'
    ]
];
$results = Dependency::auto_install_dependencies($dependencies);
```

### Checking Dependency Status

```php
// Check if plugin is active
$is_active = Dependency::is_active('woocommerce/woocommerce.php');

// Check if plugin is installed
$is_installed = Dependency::is_installed('woocommerce/woocommerce.php');

// Check if required class exists
$is_ready = Dependency::is_ready('WooCommerce');
```

## Advanced Features

### Conditional Dependency Loading

Load dependencies based on conditions:

```php
$dependencies = [];

// Add WooCommerce dependency only if e-commerce features are enabled
if (get_option('my_plugin_ecommerce_enabled')) {
    $dependencies[] = [
        'slug' => 'woocommerce/woocommerce.php',
        'name' => 'WooCommerce',
        'source' => 'repo'
    ];
}

// Add form dependency only if contact forms are used
if (get_option('my_plugin_forms_enabled')) {
    $dependencies[] = [
        'slug' => 'contact-form-7/wp-contact-form-7.php',
        'name' => 'Contact Form 7',
        'source' => 'repo'
    ];
}

$pluginManager->setDependencies($dependencies);
```

### Adding Dependencies Dynamically

```php
$pluginManager = \AdzWP\Core\PluginManager::getInstance(__FILE__);

// Add dependencies one by one
$pluginManager->addDependency([
    'slug' => 'woocommerce/woocommerce.php',
    'name' => 'WooCommerce',
    'source' => 'repo'
]);

$pluginManager->addDependency([
    'slug' => 'jetpack/jetpack.php',
    'name' => 'Jetpack',
    'source' => 'repo'
]);
```

### Installing Dependencies During Plugin Lifecycle

```php
$pluginManager
    ->onActivate(function() use ($pluginManager) {
        // Install dependencies on plugin activation
        $results = $pluginManager->installDependencies();
        
        // Handle results
        foreach ($results as $name => $result) {
            if ($result['status'] !== 'success' && $result['status'] !== 'already_active') {
                // Log or handle failed installations
                error_log("Failed to install dependency: {$name} - {$result['message']}");
            }
        }
    })
    ->onUninstall(function() {
        // Optionally remove dependencies on uninstall
        // Note: Be careful as other plugins might depend on them
    });
```

## Error Handling

### Installation Results

The `auto_install_dependencies()` method returns detailed results:

```php
$results = $pluginManager->installDependencies();

foreach ($results as $plugin_name => $result) {
    switch ($result['status']) {
        case 'success':
            // Plugin installed and activated successfully
            break;
            
        case 'already_active':
            // Plugin was already installed and active
            break;
            
        case 'installed_not_activated':
            // Plugin installed but couldn't be activated
            error_log("Activation failed: " . $result['message']);
            break;
            
        case 'install_failed':
            // Plugin installation failed
            error_log("Installation failed: " . $result['message']);
            break;
            
        case 'error':
            // Configuration error
            error_log("Configuration error: " . $result['message']);
            break;
    }
}
```

### Permission Checks

The system includes automatic permission checks:

```php
// Install functions check for proper capabilities
if (!current_user_can('install_plugins')) {
    // Installation will fail gracefully
}

if (!current_user_can('activate_plugins')) {
    // Activation will fail gracefully
}
```

### Admin Notices

Missing dependencies automatically show admin notices:

```php
// Dependency monitoring shows notices for missing plugins
Dependency::monitor_status();
```

## Best Practices

### 1. Use Stable Versions

When linking to external URLs, ensure you're pointing to stable releases:

```php
// Good - specific version
'url' => 'https://releases.example.com/plugin-v1.2.3.zip'

// Avoid - unstable versions
'url' => 'https://github.com/user/plugin/archive/main.zip'
```

### 2. Minimize Dependencies

Only include dependencies that are absolutely necessary:

```php
// Good - essential dependencies only
$dependencies = [
    ['slug' => 'woocommerce/woocommerce.php', 'name' => 'WooCommerce', 'source' => 'repo']
];

// Avoid - too many dependencies
$dependencies = [
    // 10+ different plugins
];
```

### 3. Graceful Degradation

Design your plugin to work even if some dependencies fail to install:

```php
public function initialize()
{
    if (class_exists('WooCommerce')) {
        // Full WooCommerce integration
        $this->initWooCommerceFeatures();
    } else {
        // Basic functionality without WooCommerce
        $this->initBasicFeatures();
    }
}
```

### 4. Check Before Using

Always verify dependencies are available before using them:

```php
public function processOrder()
{
    if (!class_exists('WC_Order')) {
        throw new Exception('WooCommerce is required for order processing');
    }
    
    $order = new WC_Order();
    // ... process order
}
```

### 5. Handle Network Sites

For multisite installations, consider network-wide dependencies:

```php
if (is_multisite()) {
    // Handle network-wide plugin installation
    $network_dependencies = [
        // Network-required plugins
    ];
}
```

### 6. Provide Fallbacks

Offer manual installation instructions if automatic installation fails:

```php
public function showDependencyNotice()
{
    echo '<div class="notice notice-warning">';
    echo '<p><strong>Missing Dependency:</strong> This plugin requires WooCommerce.</p>';
    echo '<p><a href="' . admin_url('plugin-install.php?s=woocommerce&tab=search&type=term') . '">Install WooCommerce</a></p>';
    echo '</div>';
}
```

## Security Considerations

### URL Validation

When using custom URLs, validate them carefully:

```php
// Validate URL before installation
$url = 'https://example.com/plugin.zip';
if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('/^https:\/\//', $url)) {
    throw new Exception('Invalid or insecure URL');
}
```

### File Verification

Consider adding file verification for security:

```php
// Verify file integrity (you would implement hash checking)
$expected_hash = 'sha256_hash_of_file';
$downloaded_file = '/path/to/downloaded/plugin.zip';
if (hash_file('sha256', $downloaded_file) !== $expected_hash) {
    throw new Exception('File integrity check failed');
}
```

## Related Documentation

- [Plugin Lifecycle Management](PLUGIN_LIFECYCLE.md)
- [Security](security.md)
- [Configuration](configuration.md)