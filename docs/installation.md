# Installation

This guide covers different methods to install and set up the ADZ WordPress Plugin Framework.

## Installation Methods

### 1. Via Composer (Recommended)

The fastest way to get started with a new plugin project:

```bash
composer create-project adzadzadz/wp-plugin-framework my-awesome-plugin
cd my-awesome-plugin
```

This creates a complete plugin structure with:
- Framework core files
- Example controllers and models
- Test suite setup
- Build tools and configuration

### 2. Adding to Existing Plugin

If you want to add the framework to an existing plugin:

```bash
composer require adzadzadz/wp-plugin-framework
```

Then update your main plugin file:

```php
<?php
// Load Composer autoloader
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// Initialize framework
$framework = \Adz::config();
```

### 3. Manual Installation

Download the framework files and include them manually:

1. Download the latest release from GitHub
2. Extract to your plugin directory
3. Include the framework:

```php
<?php
require_once plugin_dir_path(__FILE__) . 'adz-framework/src/AdzMain.php';
```

## System Requirements

### Minimum Requirements

- **PHP 7.4+** - Modern PHP features required
- **WordPress 5.0+** - Latest WordPress APIs
- **Composer** - For dependency management (recommended)

### Recommended Environment

- **PHP 8.0+** - Better performance and features
- **WordPress 6.0+** - Latest features and security
- **MySQL 5.7+ / MariaDB 10.2+** - Database compatibility

## Verification

After installation, verify the framework is working:

```php
// In your plugin file
if (class_exists('\\Adz')) {
    echo 'ADZ Framework loaded successfully!';
    echo 'Framework version: ' . \Adz::version();
} else {
    echo 'Framework not found - check installation';
}
```

## Next Steps

1. **Read the [Getting Started Guide](getting-started.md)**
2. **Follow the [First Plugin Tutorial](examples/first-plugin.md)**
3. **Explore the [Controllers Guide](controllers.md)**
4. **Check out [Plugin Lifecycle Management](PLUGIN_LIFECYCLE.md)**

## Common Installation Issues

### Composer Not Found

If Composer is not installed:

```bash
# Install Composer globally
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Or use the installer script
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
```

### Autoloader Issues

If classes are not found, ensure the autoloader is included:

```php
// Check if autoloader exists
if (file_exists(plugin_dir_path(__FILE__) . 'vendor/autoload.php')) {
    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
} else {
    throw new Exception('Composer autoloader not found. Run: composer install');
}
```

### Memory Limit Issues

For large projects, you might need to increase PHP memory:

```bash
# Temporary increase
php -d memory_limit=512M composer install

# Or set in php.ini
memory_limit = 512M
```

### Permission Issues

Ensure proper file permissions:

```bash
# Set correct permissions
chmod -R 755 /path/to/plugin
chown -R www-data:www-data /path/to/plugin

# For development
chmod -R 775 /path/to/plugin
```

## Development Setup

For development work, also install dev dependencies:

```bash
composer install --dev
```

This includes:
- PHPUnit for testing
- Development tools
- Code quality tools

Run the test suite to verify everything works:

```bash
composer test
```

## Production Deployment

For production, install without dev dependencies:

```bash
composer install --no-dev --optimize-autoloader
```

This creates an optimized autoloader and excludes development packages.

## Troubleshooting

### Framework Classes Not Found

1. Verify Composer autoloader is included
2. Check namespace spelling: `AdzWP\Core\*`
3. Ensure `composer install` was run
4. Check file permissions

### Plugin Activation Errors

1. Enable WordPress debug mode:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```
2. Check error logs in `/wp-content/debug.log`
3. Verify PHP version compatibility
4. Check for plugin conflicts

### Performance Issues

1. Enable object caching
2. Use production Composer autoloader
3. Optimize database queries
4. Consider caching strategies

## Support

- [GitHub Issues](https://github.com/adzadzadz/wp-plugin-framework/issues)
- [Documentation](README.md)
- [Community Forum](https://community.example.com)