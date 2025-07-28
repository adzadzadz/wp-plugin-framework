# Configuration Management

The ADZ Plugin Framework provides a powerful, flexible configuration system that supports multiple file formats, environment variables, and dot notation access for complex nested configurations.

## Overview

The configuration system allows you to:
- Define plugin settings in structured files
- Access nested values with dot notation
- Use environment variables for sensitive data
- Maintain different configurations for different environments
- Provide default values with easy overrides

## Configuration Files

### File Structure

Configuration files are stored in the `config/` directory:

```
config/
├── app.php          # Main application settings
├── database.php     # Database configuration
├── security.php     # Security settings
├── logging.php      # Logging configuration
├── cache.php        # Cache settings
└── custom.php       # Your custom settings
```

### Basic Configuration File

Create `config/app.php`:

```php
<?php

return [
    'plugin' => [
        'name' => 'My Awesome Plugin',
        'version' => '1.0.0',
        'text_domain' => 'my-plugin',
        'slug' => 'my-plugin',
        'namespace' => 'MyPlugin'
    ],
    
    'admin' => [
        'menu_title' => 'My Plugin',
        'menu_slug' => 'my-plugin-admin',
        'capability' => 'manage_options',
        'icon' => 'dashicons-admin-tools',
        'position' => 25
    ],
    
    'features' => [
        'contact_form' => true,
        'analytics' => false,
        'social_sharing' => true
    ]
];
```

## Accessing Configuration

### Basic Access

```php
use AdzHive\Config;

$config = Config::getInstance();

// Get a top-level value
$pluginName = $config->get('plugin');

// Get a nested value with dot notation
$menuTitle = $config->get('admin.menu_title');

// Get with default value
$apiTimeout = $config->get('api.timeout', 30);
```

### Checking if Configuration Exists

```php
// Check if a configuration key exists
if ($config->has('features.contact_form')) {
    // Feature is configured
}

// Check nested configuration
if ($config->has('database.connections.mysql')) {
    // MySQL connection is configured
}
```

### Getting All Configuration

```php
// Get all configuration
$allConfig = $config->all();

// Get all configuration for a section
$databaseConfig = $config->get('database');
```

## Setting Configuration Values

### Runtime Configuration Changes

```php
$config = Config::getInstance();

// Set a simple value
$config->set('features.new_feature', true);

// Set nested values
$config->set('api.endpoints.users', 'https://api.example.com/users');

// Set multiple values
$config->merge([
    'features' => [
        'analytics' => true,
        'reporting' => true
    ]
]);
```

### Array Operations

```php
// Add to an array
$config->push('features.enabled_modules', 'new_module');

// Remove a configuration key
$config->forget('features.old_feature');
```

## Environment Variables

### Using Environment Variables

The configuration system can read from environment variables:

```php
// In your configuration file
return [
    'database' => [
        'host' => $config->getEnv('DB_HOST', 'localhost'),
        'username' => $config->getEnv('DB_USERNAME', 'root'),
        'password' => $config->getEnv('DB_PASSWORD', ''),
        'name' => $config->getEnv('DB_NAME', 'wordpress')
    ],
    
    'api' => [
        'key' => $config->getEnv('API_KEY'),
        'secret' => $config->getEnv('API_SECRET'),
        'debug' => $config->getEnv('API_DEBUG', false)
    ]
];
```

### Environment Variable Types

The system automatically parses environment variable types:

```bash
# .env file or system environment
API_ENABLED=true          # Parsed as boolean true
API_TIMEOUT=30            # Parsed as integer 30
API_RATE_LIMIT=10.5       # Parsed as float 10.5
API_URL="https://api.com" # Parsed as string (quotes removed)
```

## Configuration Examples

### Database Configuration

`config/database.php`:

```php
<?php

return [
    'prefix' => 'mp_',
    'charset' => 'utf8mb4',
    'collate' => 'utf8mb4_unicode_ci',
    
    'connections' => [
        'default' => [
            'driver' => 'mysql',
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => getenv('DB_PORT') ?: 3306,
            'database' => getenv('DB_NAME') ?: 'wordpress',
            'username' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASSWORD') ?: '',
        ]
    ],
    
    'migrations' => [
        'table' => 'migrations',
        'path' => 'database/migrations'
    ]
];
```

### Security Configuration

`config/security.php`:

```php
<?php

return [
    'csrf' => [
        'enabled' => true,
        'token_name' => '_token',
        'header_name' => 'X-CSRF-TOKEN'
    ],
    
    'rate_limiting' => [
        'enabled' => true,
        'default_limit' => 60,
        'default_window' => 3600,
        'rules' => [
            'login' => ['limit' => 5, 'window' => 300],
            'contact_form' => ['limit' => 10, 'window' => 600],
            'api_calls' => ['limit' => 1000, 'window' => 3600]
        ]
    ],
    
    'validation' => [
        'strict_mode' => true,
        'custom_rules' => []
    ]
];
```

### Logging Configuration

`config/logging.php`:

```php
<?php

return [
    'enabled' => true,
    'level' => getenv('LOG_LEVEL') ?: 'info',
    'max_file_size' => 10 * 1024 * 1024, // 10MB
    'max_files' => 5,
    
    'channels' => [
        'default' => [
            'driver' => 'file',
            'path' => WP_CONTENT_DIR . '/logs/my-plugin.log'
        ],
        
        'error' => [
            'driver' => 'file',
            'path' => WP_CONTENT_DIR . '/logs/my-plugin-errors.log',
            'level' => 'error'
        ]
    ]
];
```

## Dynamic Configuration

### Loading Configuration Based on Environment

```php
// In your main plugin file or bootstrap
$environment = getenv('WP_ENV') ?: 'production';

$configPath = plugin_dir_path(__FILE__) . "config/{$environment}/";
$config = Config::getInstance($configPath);
```

Directory structure:
```
config/
├── production/
│   ├── app.php
│   └── database.php
├── staging/
│   ├── app.php
│   └── database.php
└── development/
    ├── app.php
    └── database.php
```

### Conditional Configuration

```php
// config/app.php
$isDevelopment = defined('WP_DEBUG') && WP_DEBUG;

return [
    'debug' => $isDevelopment,
    
    'features' => [
        'debug_toolbar' => $isDevelopment,
        'query_monitor' => $isDevelopment,
        'cache' => !$isDevelopment
    ],
    
    'api' => [
        'base_url' => $isDevelopment 
            ? 'https://api-dev.example.com' 
            : 'https://api.example.com'
    ]
];
```

## WordPress Integration

### Using WordPress Options

Combine configuration files with WordPress options:

```php
// config/app.php
return [
    'plugin' => [
        'name' => get_option('my_plugin_name', 'Default Name'),
        'enabled_features' => get_option('my_plugin_features', [])
    ]
];
```

### Filtering Configuration

Allow themes and other plugins to modify configuration:

```php
// In your configuration loading code
$config = apply_filters('my_plugin_config', [
    'features' => [
        'contact_form' => true,
        'analytics' => false
    ]
]);
```

## Configuration Validation

### Validating Configuration Values

```php
use AdzHive\Validator;

class ConfigValidator
{
    public static function validate($config)
    {
        $validator = Validator::make($config, [
            'plugin.name' => 'required|string|min:3',
            'plugin.version' => 'required|regex:/^\d+\.\d+\.\d+$/',
            'admin.capability' => 'required|string',
            'database.prefix' => 'required|string|max:10'
        ]);
        
        if ($validator->fails()) {
            throw new \InvalidArgumentException(
                'Invalid configuration: ' . implode(', ', $validator->errors())
            );
        }
        
        return true;
    }
}
```

### Default Configuration

Provide comprehensive defaults:

```php
// In Config class or configuration loader
$defaultConfig = [
    'plugin' => [
        'name' => 'Unnamed Plugin',
        'version' => '1.0.0',
        'text_domain' => 'unnamed-plugin'
    ],
    
    'admin' => [
        'capability' => 'manage_options',
        'menu_position' => null
    ],
    
    'security' => [
        'csrf_enabled' => true,
        'rate_limiting_enabled' => true
    ]
];

// Merge with user configuration
$config = array_merge_recursive($defaultConfig, $userConfig);
```

## CLI Configuration Management

### Generate Configuration Files

```bash
# Generate all configuration files
./adz.sh make:config

# Generate specific configuration
./adz.sh make:config --type=database
```

### Configuration Commands

```bash
# View current configuration
./adz.sh config:show

# Set configuration value
./adz.sh config:set features.analytics true

# Get configuration value
./adz.sh config:get admin.menu_title
```

## Best Practices

### 1. Use Meaningful Structure

```php
// Good: Clear, hierarchical structure
return [
    'features' => [
        'contact_form' => [
            'enabled' => true,
            'recipients' => ['admin@example.com'],
            'spam_protection' => true
        ]
    ]
];

// Avoid: Flat structure
return [
    'contact_form_enabled' => true,
    'contact_form_recipients' => ['admin@example.com'],
    'contact_form_spam_protection' => true
];
```

### 2. Provide Sensible Defaults

```php
// Always provide defaults for optional settings
$timeout = $config->get('api.timeout', 30);
$retries = $config->get('api.retries', 3);
$cacheEnabled = $config->get('cache.enabled', true);
```

### 3. Document Configuration Options

```php
<?php
/**
 * Plugin Configuration
 * 
 * plugin.name - Display name for the plugin
 * plugin.version - Current plugin version (semver format)
 * admin.capability - Required capability for admin access
 * features.* - Feature toggle flags
 */

return [
    'plugin' => [
        'name' => 'My Plugin',        // string: Plugin display name
        'version' => '1.0.0'          // string: Version (semver)
    ],
    
    'admin' => [
        'capability' => 'manage_options' // string: WP capability required
    ]
];
```

### 4. Separate Concerns

Keep different types of configuration in separate files:
- `app.php` - Application settings
- `database.php` - Database configuration
- `security.php` - Security settings
- `services.php` - External service configuration

### 5. Use Environment Variables for Secrets

```php
// config/services.php
return [
    'mailchimp' => [
        'api_key' => getenv('MAILCHIMP_API_KEY'), // From environment
        'list_id' => getenv('MAILCHIMP_LIST_ID')
    ],
    
    'stripe' => [
        'publishable_key' => getenv('STRIPE_PUBLISHABLE_KEY'),
        'secret_key' => getenv('STRIPE_SECRET_KEY')
    ]
];
```

## Migration and Upgrades

### Configuration Migration

When updating your plugin, migrate old configuration:

```php
class ConfigMigration
{
    public static function migrate($version)
    {
        $config = Config::getInstance();
        
        switch ($version) {
            case '1.0.0':
                // Migrate from old option names
                $oldValue = get_option('old_plugin_setting');
                if ($oldValue) {
                    $config->set('new.setting.path', $oldValue);
                    delete_option('old_plugin_setting');
                }
                break;
                
            case '2.0.0':
                // Restructure configuration
                $features = $config->get('features', []);
                if (isset($features['old_feature'])) {
                    $config->set('features.new_feature', $features['old_feature']);
                    $config->forget('features.old_feature');
                }
                break;
        }
    }
}
```

The configuration system provides a robust foundation for managing all aspects of your plugin's behavior while maintaining flexibility and ease of use.