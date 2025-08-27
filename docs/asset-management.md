# Asset Management

The ADZ Framework includes a comprehensive asset management system with Bootstrap 5 integration, context-aware loading, and WordPress best practices.

## Overview

The AssetManager provides:

- **Bootstrap 5 Integration** - Automatic CDN loading in admin contexts
- **Context-Aware Loading** - Assets load only where needed
- **Plugin Detection** - Smart detection of plugin-related pages
- **Asset Registration** - Easy CSS/JS registration and management

## Quick Start

### Automatic Bootstrap 5

Bootstrap 5 is automatically loaded in admin and plugin contexts:

```php
// Framework initialization (automatic)
\AdzWP\Core\AssetManager::init();
```

### Register Custom Assets

```php
// Register a custom stylesheet
AssetManager::registerStyle('my-styles', [
    'url' => plugin_dir_url(__FILE__) . 'assets/css/style.css',
    'contexts' => ['admin', 'plugin'],
    'version' => '1.0.0'
]);

// Register a custom script
AssetManager::registerScript('my-script', [
    'url' => plugin_dir_url(__FILE__) . 'assets/js/script.js',
    'dependencies' => ['jquery'],
    'contexts' => ['admin'],
    'version' => '1.0.0'
]);
```

## Asset Registration

### CSS Registration

```php
AssetManager::registerStyle('handle', [
    'url' => 'https://example.com/style.css',    // Asset URL
    'dependencies' => ['bootstrap-css'],         // Dependencies
    'version' => '1.0.0',                       // Version
    'media' => 'all',                           // Media type
    'contexts' => ['admin', 'plugin'],          // Where to load
    'priority' => 10,                           // Load priority
    'conditional' => function() {               // Conditional loading
        return current_user_can('manage_options');
    }
]);
```

### JavaScript Registration

```php
AssetManager::registerScript('handle', [
    'url' => 'https://example.com/script.js',
    'dependencies' => ['jquery', 'bootstrap-js'],
    'version' => '1.0.0',
    'in_footer' => true,                        // Load in footer
    'contexts' => ['admin'],
    'localize' => [                             // Localization data
        'object_name' => 'myAjaxObject',
        'data' => [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('my_nonce')
        ]
    ]
]);
```

## Context System

### Available Contexts

- `admin` - WordPress admin pages
- `frontend` - Public website pages  
- `plugin` - Plugin-specific pages
- `all` - All contexts

### Context Detection

The system automatically detects plugin contexts:

```php
// Plugin pages detected by:
// - Hook suffix containing plugin slug
// - Screen ID containing plugin slug  
// - Query parameter 'page' containing plugin slug
```

### Custom Context Rules

```php
AssetManager::registerStyle('custom-style', [
    'url' => '/path/to/style.css',
    'conditional' => function() {
        // Only load on specific pages
        return is_admin() && get_current_screen()->id === 'my-plugin-page';
    }
]);
```

## Bootstrap 5 Management

### Enable/Disable Bootstrap

```php
// Enable Bootstrap in specific contexts
AssetManager::setBootstrap(true, ['admin', 'plugin']);

// Disable Bootstrap entirely
AssetManager::setBootstrap(false);

// Enable for all contexts
AssetManager::setBootstrap(true, ['all']);
```

### Bootstrap Components

Bootstrap 5 includes:

- **CSS Framework** - Complete utility classes and components
- **JavaScript Bundle** - All Bootstrap JS components
- **Responsive Grid** - Mobile-first grid system
- **Components** - Cards, alerts, forms, modals, etc.

## Inline Assets

### Inline CSS

```php
// Add custom CSS to existing stylesheet
AssetManager::addInlineStyle('my-handle', '
    .custom-class {
        background: #f0f0f0;
        padding: 1rem;
    }
');
```

### Inline JavaScript

```php
// Add custom JS to existing script
AssetManager::addInlineScript('my-handle', '
    jQuery(document).ready(function($) {
        console.log("Script loaded");
    });
', 'after');
```

## Asset Management

### List Assets

```php
// Get all registered assets
$assets = AssetManager::getAssets();

// Check styles
foreach ($assets['styles'] as $handle => $config) {
    echo "Style: {$handle} - {$config['url']}\n";
}

// Check scripts  
foreach ($assets['scripts'] as $handle => $config) {
    echo "Script: {$handle} - {$config['url']}\n";
}
```

### Remove Assets

```php
// Remove specific asset
AssetManager::deregister('my-handle', 'style');
AssetManager::deregister('my-handle', 'script');
AssetManager::deregister('my-handle', 'both');

// Clear all assets
AssetManager::clear();
```

## Plugin Integration

### Controller Integration

```php
<?php
namespace App\Controllers;

use AdzWP\Core\Controller;
use AdzWP\Core\AssetManager;

class AdminController extends Controller
{
    protected function bootstrap()
    {
        // Register plugin-specific assets
        AssetManager::registerStyle('admin-styles', [
            'url' => plugin_dir_url(__FILE__) . '../assets/admin.css',
            'contexts' => ['plugin'],
            'dependencies' => ['bootstrap-css']
        ]);
        
        AssetManager::registerScript('admin-scripts', [
            'url' => plugin_dir_url(__FILE__) . '../assets/admin.js',
            'contexts' => ['plugin'], 
            'dependencies' => ['jquery', 'bootstrap-js'],
            'localize' => [
                'object_name' => 'adminAjax',
                'data' => [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('admin_action')
                ]
            ]
        ]);
    }
}
```

### Theme Override

```php
// In your theme's functions.php
add_action('init', function() {
    // Disable framework Bootstrap
    \AdzWP\Core\AssetManager::setBootstrap(false);
    
    // Register theme's custom styles
    \AdzWP\Core\AssetManager::registerStyle('theme-bootstrap', [
        'url' => get_theme_file_uri('css/custom-bootstrap.css'),
        'contexts' => ['all']
    ]);
});
```

## Advanced Usage

### Priority Loading

```php
// Critical CSS (loads first)
AssetManager::registerStyle('critical-css', [
    'url' => '/path/to/critical.css',
    'priority' => 1
]);

// Normal CSS
AssetManager::registerStyle('normal-css', [
    'url' => '/path/to/normal.css', 
    'priority' => 10
]);

// Enhancement CSS (loads last)
AssetManager::registerStyle('enhancement-css', [
    'url' => '/path/to/enhancement.css',
    'priority' => 20
]);
```

### Conditional Loading

```php
AssetManager::registerScript('feature-script', [
    'url' => '/path/to/feature.js',
    'conditional' => function() {
        // Only load if feature is enabled
        return get_option('my_feature_enabled', false);
    }
]);
```

### Development vs Production

```php
$is_dev = defined('WP_DEBUG') && WP_DEBUG;

AssetManager::registerScript('my-script', [
    'url' => $is_dev 
        ? plugin_dir_url(__FILE__) . 'src/script.js'    // Development
        : plugin_dir_url(__FILE__) . 'dist/script.min.js', // Production
    'version' => $is_dev ? time() : '1.0.0' // Bust cache in dev
]);
```

## Best Practices

1. **Context-Aware Loading** - Only load assets where needed
2. **Dependency Management** - Declare dependencies properly
3. **Version Control** - Use proper versioning for cache busting
4. **CDN Usage** - Use CDNs for common libraries
5. **Minification** - Serve minified assets in production
6. **Conditional Loading** - Use conditionals for optional features
7. **Performance** - Load non-critical assets in footer

## CLI Commands

```bash
# View asset status (future feature)
adz assets:list

# Enable Bootstrap assets
adz assets:enable

# Disable Bootstrap assets  
adz assets:disable
```

## Troubleshooting

### Assets Not Loading

1. Check context configuration
2. Verify plugin page detection
3. Check conditional functions
4. Verify file URLs and paths

### Bootstrap Conflicts

1. Disable framework Bootstrap if theme includes it
2. Use specific contexts to avoid conflicts
3. Check CSS specificity issues
4. Verify JavaScript conflicts

### Performance Issues

1. Use conditional loading
2. Optimize asset contexts
3. Consider lazy loading for non-critical assets
4. Minimize dependencies