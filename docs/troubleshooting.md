# Troubleshooting Guide

This guide helps you diagnose and fix common issues when using the ADZ Plugin Framework. Most problems fall into a few categories and have straightforward solutions.

## Quick Diagnostics

### Run Health Check

The first step in troubleshooting should always be running the health check:

```bash
./adz.sh health:check
```

This command checks:
- Database connectivity
- File permissions
- Required PHP extensions
- Framework component status
- Configuration validity

### Check Logs

View recent log entries to identify issues:

```bash
# View all logs
tail -f wp-content/uploads/adz-logs/adz-plugin.log

# View only errors
grep "ERROR" wp-content/uploads/adz-logs/adz-plugin.log

# Clear logs and start fresh
./adz.sh log:clear
```

## Common Issues

### 1. Plugin Not Loading / White Screen

**Symptoms:**
- Plugin activation fails
- White screen when accessing plugin pages
- Fatal error messages

**Causes & Solutions:**

#### Missing Dependencies
```bash
# Check if Composer dependencies are installed
ls vendor/

# If vendor directory is missing or empty:
composer install

# If composer is not found:
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### PHP Version Incompatibility
```bash
# Check PHP version
php -v

# Framework requires PHP 7.4+
# If using older version, upgrade PHP or use compatibility mode
```

#### Namespace/Autoloading Issues
```php
// Check your main plugin file has correct autoloader
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// Verify namespace declarations match directory structure
namespace MyPlugin\Controllers;  // Should be in src/controllers/
```

#### Memory Limit
```php
// Add to wp-config.php or .htaccess
ini_set('memory_limit', '256M');

// Or increase in php.ini
memory_limit = 256M
```

### 2. Database Errors

**Symptoms:**
- "Database connection error"
- "Table doesn't exist" errors
- Failed queries

**Solutions:**

#### Run Migrations
```bash
# Create missing tables
./adz.sh db:migrate

# Check migration status
./adz.sh db:status
```

#### Database Permissions
```sql
-- Grant necessary permissions
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER ON database_name.* TO 'username'@'localhost';
FLUSH PRIVILEGES;
```

#### Table Prefix Issues
```php
// Check configuration in config/database.php
return [
    'prefix' => 'adz_',  // Should match your setup
    'charset' => 'utf8mb4',
    'collate' => 'utf8mb4_unicode_ci'
];
```

#### Connection Timeouts
```php
// Increase timeout in wp-config.php
define('DB_TIMEOUT', 60);

// Or in database configuration
'connections' => [
    'default' => [
        'timeout' => 60
    ]
];
```

### 3. Security/Permission Errors

**Symptoms:**
- "Security check failed"
- "Access denied" messages
- CSRF token errors

**Solutions:**

#### Nonce Issues
```php
// Make sure nonce field is present in forms
echo Security::getInstance()->getNonceField('my_action', '_my_nonce');

// Verify nonce in controller
Security::getInstance()->verifyRequest('_my_nonce', 'my_action');

// Check nonce expiration (default 24 hours)
// Users may need to refresh page if nonce is old
```

#### User Capabilities
```php
// Check required capabilities
if (!current_user_can('manage_options')) {
    wp_die('Insufficient permissions');
}

// Or use framework method
Security::getInstance()->checkCapability('manage_options');
```

#### Rate Limiting
```php
// Check if rate limit is too restrictive
Security::getInstance()->checkRateLimit('action_name', 10, 300); // 10 per 5 minutes

// Clear rate limit for testing
delete_transient('adz_rate_limit_' . md5('action_name_' . $identifier));
```

### 4. Asset Loading Issues

**Symptoms:**
- CSS/JS files not loading
- 404 errors for assets
- Styling/functionality missing

**Solutions:**

#### File Paths
```php
// Use correct asset URLs
wp_enqueue_style(
    'my-plugin-css',
    plugin_dir_url(__FILE__) . 'assets/css/style.css',  // Correct
    [],
    '1.0.0'
);

// Avoid relative paths
// Wrong: '../assets/css/style.css'
```

#### Asset Management
```bash
# Check if assets exist
ls src/assets/css/
ls src/assets/js/

# Use asset management tool
./adz.sh asset
```

#### WordPress Hook Issues
```php
// Enqueue assets on correct hook
public $actions = [
    'wp_enqueue_scripts' => 'enqueueAssets',      // Frontend
    'admin_enqueue_scripts' => 'enqueueAdminAssets' // Admin
];

// Not: 'init' => 'enqueueAssets'  // Too early
```

### 5. Configuration Problems

**Symptoms:**
- Default values being used
- Configuration not saving
- Environment variables not working

**Solutions:**

#### Generate Config Files
```bash
# Create missing configuration files
./adz.sh make:config

# Check config directory exists and is writable
ls -la config/
```

#### File Permissions
```bash
# Make config directory writable
chmod 755 config/
chmod 644 config/*.php
```

#### Environment Variables
```php
// Check if environment variables are available
var_dump(getenv('DB_HOST'));
var_dump($_ENV['API_KEY']);

// Use fallbacks in configuration
'api_key' => getenv('API_KEY') ?: 'default_key'
```

### 6. AJAX Issues

**Symptoms:**
- AJAX requests returning 0 or -1
- "Admin AJAX not working" errors
- Console errors in browser

**Solutions:**

#### Action Registration
```php
// Register both logged-in and non-logged-in actions
public $actions = [
    'wp_ajax_my_action' => 'handleAjax',          // Logged in users
    'wp_ajax_nopriv_my_action' => 'handleAjax'    // Non-logged in users
];
```

#### JavaScript Configuration
```php
// Localize script with AJAX URL
wp_localize_script('my-script', 'myAjax', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('my_ajax_nonce')
]);
```

```javascript
// Use correct AJAX URL in JavaScript
$.ajax({
    url: myAjax.ajax_url,  // Not: '/wp-admin/admin-ajax.php'
    data: {
        action: 'my_action',
        nonce: myAjax.nonce
    }
});
```

#### Nonce Verification
```php
// Verify AJAX nonce
public function handleAjax() {
    Security::getInstance()->verifyAjaxRequest('my_ajax_nonce');
    // Process request
}
```

### 7. Validation Errors

**Symptoms:**
- Unexpected validation failures
- Custom rules not working
- Error messages not displaying

**Solutions:**

#### Rule Syntax
```php
// Correct rule syntax
$validator = Validator::make($data, [
    'email' => 'required|email|max:255',
    'age' => 'numeric|between:13,120',
    'tags' => 'array|min:1',
    'tags.*' => 'string|max:50'  // Validate each array item
]);
```

#### Custom Messages
```php
// Provide custom error messages
$validator = Validator::make($data, $rules, [
    'email.required' => 'Please enter your email address.',
    'email.email' => 'Please enter a valid email address.',
    'age.between' => 'Age must be between 13 and 120.'
]);
```

#### Database Validation
```php
// Ensure table exists for unique/exists rules
'email' => 'unique:users,email',    // Table 'users' must exist
'category_id' => 'exists:categories,id'  // Table 'categories' must exist
```

## Performance Issues

### Slow Loading Times

#### Enable Query Logging
```php
// Add to wp-config.php for debugging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('SAVEQUERIES', true);

// Log slow queries
add_action('shutdown', function() {
    global $wpdb;
    if (defined('WP_DEBUG') && WP_DEBUG) {
        foreach ($wpdb->queries as $query) {
            if ($query[1] > 0.1) {  // Queries slower than 0.1 seconds
                adz_log_warning('Slow query detected', [
                    'query' => $query[0],
                    'time' => $query[1],
                    'stack' => $query[2]
                ]);
            }
        }
    }
});
```

#### Optimize Database Queries
```php
// Use indexes effectively
$db->table('posts')
    ->where('status', 'published')  // Should have index on status
    ->where('author_id', $authorId)  // Should have index on author_id
    ->limit(10)  // Always limit results
    ->get();

// Cache expensive queries
$cacheKey = 'popular_posts_' . $limit;
$posts = adz_cache_get($cacheKey);
if ($posts === null) {
    $posts = $db->table('posts')
        ->where('status', 'published')
        ->orderBy('views', 'DESC')
        ->limit($limit)
        ->get();
    adz_cache_set($cacheKey, $posts, HOUR_IN_SECONDS);
}
```

### Memory Issues

#### Monitor Memory Usage
```php
// Log memory usage
function log_memory_usage($context = '') {
    adz_log_debug('Memory usage' . ($context ? " ({$context})" : ''), [
        'current' => memory_get_usage(true),
        'peak' => memory_get_peak_usage(true),
        'limit' => ini_get('memory_limit')
    ]);
}

// Use throughout your code
log_memory_usage('before processing');
// ... processing code ...
log_memory_usage('after processing');
```

#### Optimize Memory Usage
```php
// Process large datasets in chunks
$totalUsers = $db->table('users')->count();
$chunkSize = 100;

for ($offset = 0; $offset < $totalUsers; $offset += $chunkSize) {
    $users = $db->table('users')
        ->limit($chunkSize)
        ->offset($offset)
        ->get();
    
    foreach ($users as $user) {
        // Process user
    }
    
    // Free memory
    unset($users);
}
```

## Development Environment Issues

### Debug Mode Setup

```php
// wp-config.php settings for development
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true);
define('SCRIPT_DEBUG', true);

// Plugin-specific debug settings
define('ADZ_DEBUG', true);
define('ADZ_LOG_LEVEL', 'debug');
```

### File Permissions

```bash
# Set correct permissions
find /path/to/plugin -type f -exec chmod 644 {} \;
find /path/to/plugin -type d -exec chmod 755 {} \;

# Make CLI script executable
chmod +x adz.sh

# Ensure log directory is writable
chmod 755 wp-content/uploads/
mkdir -p wp-content/uploads/adz-logs/
chmod 755 wp-content/uploads/adz-logs/
```

## Error Messages Reference

### Common Error Codes

| Error | Meaning | Solution |
|-------|---------|----------|
| `Class not found` | Autoloading issue | Run `composer install` |
| `Call to undefined function` | Missing dependency | Check required PHP extensions |
| `Table doesn't exist` | Database migration needed | Run `./adz.sh db:migrate` |
| `Permission denied` | File permissions | Fix file/directory permissions |
| `Security check failed` | CSRF token issue | Verify nonce implementation |
| `Rate limit exceeded` | Too many requests | Wait or adjust rate limits |
| `Validation failed` | Input validation error | Check validation rules |
| `Database connection error` | DB connectivity issue | Check database credentials |

### Framework-Specific Errors

#### `AdzHive\Config not found`
```bash
composer install
# or
composer dump-autoload
```

#### `Unable to create log directory`
```bash
chmod 755 wp-content/uploads/
mkdir -p wp-content/uploads/adz-logs/
chmod 755 wp-content/uploads/adz-logs/
```

#### `Controller method not found`
```php
// Ensure method exists and is public
public function myMethod() {
    // Method implementation
}

// Check action/filter registration
public $actions = [
    'init' => 'myMethod'  // Method name must match exactly
];
```

## Getting Help

### Debug Information

When reporting issues, include this debug information:

```bash
# System information
php -v
mysql --version
wp --version  # If WP-CLI is installed

# Framework status
./adz.sh health:check

# Recent logs
tail -20 wp-content/uploads/adz-logs/adz-plugin.log

# Configuration
cat config/app.php
```

### Create Minimal Test Case

```php
// Create a minimal test to isolate the issue
class TestController extends \AdzWP\Controller {
    public $actions = ['init' => 'test'];
    
    public function test() {
        adz_log_info('Test controller is working');
        echo '<!-- Test output -->';
    }
}

$test = new TestController();
$test->init();
```

### Enable Verbose Logging

```php
// Temporarily increase log level
// In config/logging.php
return [
    'level' => 'debug',  // Show all log messages
    'enabled' => true
];
```

## Prevention Tips

1. **Always use version control** - Track changes to identify when issues were introduced
2. **Test on staging first** - Never deploy directly to production
3. **Keep backups** - Regular database and file backups
4. **Monitor logs** - Regular log review helps catch issues early
5. **Use health checks** - Run `./adz.sh health:check` regularly
6. **Keep dependencies updated** - Regular `composer update`
7. **Follow security practices** - Always validate input and escape output

Remember: Most issues are caused by configuration problems, missing dependencies, or file permission issues. The health check command solves 80% of common problems.