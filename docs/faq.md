# Frequently Asked Questions

## General Questions

### Q: What is the ADZ Plugin Framework?

**A:** The ADZ Plugin Framework is a modern, security-focused WordPress plugin development framework that provides a structured, object-oriented approach to building WordPress plugins. It includes automatic hook registration, comprehensive security features, database abstraction, validation, logging, and more.

### Q: Do I need to know object-oriented PHP to use this framework?

**A:** Basic object-oriented PHP knowledge is recommended, but the framework is designed to be beginner-friendly. The documentation includes plenty of examples, and the patterns are straightforward to follow. If you can understand WordPress hooks and basic PHP, you can learn to use this framework.

### Q: Is this framework compatible with existing WordPress plugins and themes?

**A:** Yes, absolutely. The framework is built on top of WordPress core functionality and doesn't interfere with other plugins or themes. It uses standard WordPress hooks, database functions, and conventions.

### Q: What PHP version is required?

**A:** PHP 7.4 or higher is required, with PHP 8.0+ recommended for optimal performance and security.

## Installation & Setup

### Q: How do I install the framework?

**A:** You can install via Git clone, download release, or Composer:

```bash
# Git clone
git clone https://github.com/your-repo/wp-plugin-framework.git my-plugin
cd my-plugin
composer install
./adz.sh init

# Composer
composer create-project adz/wp-plugin-framework my-plugin
```

See the [Installation Guide](installation.md) for detailed instructions.

### Q: What happens if Composer is not available on my server?

**A:** While Composer is recommended for dependency management, you can:
1. Run `composer install` locally and upload the entire plugin including `vendor/` directory
2. Use the framework without external dependencies (some features may be limited)
3. Install Composer on your server following [getcomposer.org](https://getcomposer.org) instructions

### Q: Can I use this framework on shared hosting?

**A:** Yes, the framework works on most shared hosting providers that meet the requirements (PHP 7.4+, MySQL 5.7+). You may need to run Composer locally and upload the complete plugin.

## Development

### Q: How do I create my first controller?

**A:** Use the CLI command:

```bash
./adz.sh make:controller MyController
```

This creates a controller template in `src/controllers/MyController.php`. See the [Controllers Guide](guides/controllers.md) for details.

### Q: Why use the hook registration arrays instead of add_action()?

**A:** The declarative approach offers several benefits:
- **Cleaner code**: All hooks are visible at the top of your controller
- **Better organization**: Easy to see what hooks a controller handles
- **Advanced features**: Priority and argument configuration in one place
- **Consistency**: Standardized approach across all controllers
- **Debugging**: Easier to debug hook-related issues

### Q: Can I still use add_action() and add_filter() manually?

**A:** Yes, you can mix both approaches. The framework's automatic registration doesn't prevent manual hook registration:

```php
public function bootstrap() {
    // Manual registration
    add_action('custom_hook', [$this, 'handleCustomHook']);
}
```

### Q: How do I handle database operations?

**A:** The framework provides a fluent query builder:

```php
use AdzHive\Database;

$db = Database::getInstance();

// Simple queries
$users = $db->table('users')->where('active', 1)->get();

// Complex queries
$posts = $db->table('posts')
    ->join('users', 'posts.author_id', '=', 'users.id')
    ->where('posts.status', 'published')
    ->orderBy('posts.created_at', 'DESC')
    ->limit(10)
    ->get();
```

See the [Database Guide](features/database.md) for comprehensive examples.

## Security

### Q: How does the framework handle security?

**A:** The framework includes multiple security layers:
- **CSRF Protection**: Automatic nonce generation and verification
- **Input Validation**: 20+ validation rules with custom messages
- **Data Sanitization**: Context-aware cleaning of user input
- **Rate Limiting**: Prevent abuse and brute force attacks
- **Permission Checking**: WordPress capability integration
- **SQL Injection Prevention**: Prepared statements and query builder

### Q: Do I need to implement security manually?

**A:** Basic security is handled automatically, but you should:
- Use the validation system for user input
- Call security verification methods in controllers
- Sanitize data before saving
- Escape output in templates

Example:
```php
// In controller
$this->security->verifyRequest();
$validator = Validator::make($_POST, $rules);
$cleanData = $this->security->sanitizeArray($_POST, $types);
```

### Q: How do I validate form data?

**A:** Use the built-in Validator class:

```php
$validator = Validator::make($_POST, [
    'email' => 'required|email|unique:users,email',
    'name' => 'required|string|min:2|max:50',
    'age' => 'numeric|between:13,120'
]);

if ($validator->fails()) {
    $errors = $validator->errors();
} else {
    $validData = $validator->validated();
}
```

## Configuration

### Q: How do I configure the framework?

**A:** Generate configuration files with:

```bash
./adz.sh make:config
```

This creates files in the `config/` directory. You can then edit:
- `app.php` - Main application settings
- `database.php` - Database configuration
- `security.php` - Security settings
- `logging.php` - Logging configuration

### Q: Can I use environment variables?

**A:** Yes, the configuration system supports environment variables:

```php
// config/database.php
return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: ''
];
```

### Q: How do I access configuration values?

**A:** Use dot notation:

```php
use AdzHive\Config;

$config = Config::getInstance();
$apiKey = $config->get('services.api.key', 'default_key');
$debugMode = $config->get('app.debug', false);
```

## Troubleshooting

### Q: My plugin shows a white screen or fatal error

**A:** Common causes and solutions:

1. **Missing dependencies**: Run `composer install`
2. **PHP version**: Ensure PHP 7.4+
3. **Autoloading issues**: Check namespace declarations
4. **Memory limit**: Increase PHP memory limit
5. **File permissions**: Ensure proper permissions

Run the health check for diagnosis:
```bash
./adz.sh health:check
```

### Q: Database operations are failing

**A:** Check these common issues:

1. **Missing tables**: Run `./adz.sh db:migrate`
2. **Database permissions**: Ensure user has CREATE, ALTER privileges
3. **Table prefix**: Verify configuration matches your setup
4. **Connection timeout**: Increase timeout in configuration

### Q: AJAX requests return 0 or -1

**A:** Ensure you've registered both logged-in and non-logged-in actions:

```php
public $actions = [
    'wp_ajax_my_action' => 'handleAjax',
    'wp_ajax_nopriv_my_action' => 'handleAjax'
];
```

And verify nonce in JavaScript:
```javascript
$.ajax({
    url: ajaxurl,
    data: {
        action: 'my_action',
        nonce: my_nonce
    }
});
```

### Q: Assets (CSS/JS) are not loading

**A:** Common fixes:

1. **Check file paths**: Use `plugin_dir_url(__FILE__)`
2. **Verify files exist**: Ensure assets are in correct location  
3. **Enqueue on correct hook**: Use `wp_enqueue_scripts` for frontend
4. **Cache issues**: Clear any caching plugins

## Performance

### Q: How can I optimize database queries?

**A:** Follow these practices:

1. **Use indexes**: Ensure WHERE clauses use indexed columns
2. **Limit results**: Always use `limit()` for large datasets
3. **Select specific columns**: Don't use `SELECT *`
4. **Cache expensive queries**: Use transients or object cache
5. **Batch operations**: Process large datasets in chunks

### Q: How do I enable caching?

**A:** The framework supports multiple caching strategies:

```php
// Simple caching
$data = adz_cache_get('expensive_data');
if ($data === null) {
    $data = expensive_operation();
    adz_cache_set('expensive_data', $data, HOUR_IN_SECONDS);
}

// Configuration-based caching
// In config/cache.php
return [
    'enabled' => true,
    'default_ttl' => 3600,
    'driver' => 'transient'
];
```

## Advanced Usage

### Q: Can I extend the framework with custom functionality?

**A:** Yes, the framework is designed for extensibility:

1. **Custom validation rules**: Extend the Validator class
2. **Custom middleware**: Add before/after hooks to controllers
3. **Custom CLI commands**: Register new commands in Console class
4. **Custom security checks**: Extend Security class

### Q: How do I create custom validation rules?

**A:** Extend the Validator class:

```php
class CustomValidator extends \AdzHive\Validator {
    protected function validateWordPressUsername($field, $value, $parameters) {
        return username_exists($value) !== false;
    }
}

// Use custom validator
$validator = CustomValidator::make($data, [
    'username' => 'required|wordpress_username'
]);
```

### Q: Can I use this framework for REST API development?

**A:** Yes, the framework works great for REST API development:

```php
class ApiController extends Controller {
    public $actions = [
        'rest_api_init' => 'registerRoutes'
    ];
    
    public function registerRoutes() {
        register_rest_route('my-plugin/v1', '/users', [
            'methods' => 'GET',
            'callback' => [$this, 'getUsers'],
            'permission_callback' => [$this, 'checkPermission']
        ]);
    }
}
```

## Compatibility

### Q: Does this work with WordPress multisite?

**A:** Yes, the framework is multisite compatible. Database operations respect the current site's table prefix, and configuration can be site-specific.

### Q: Is it compatible with popular plugins like WooCommerce, ACF, etc.?

**A:** Yes, the framework doesn't interfere with other plugins. It uses standard WordPress APIs and follows WordPress coding standards.

### Q: Can I use this with page builders like Elementor or Gutenberg?

**A:** Absolutely. The framework provides shortcodes, widgets, and blocks that integrate seamlessly with page builders.

### Q: What about theme compatibility?

**A:** The framework is theme-agnostic and works with any properly coded WordPress theme. It follows WordPress template hierarchy and styling conventions.

## Migration & Upgrade

### Q: How do I migrate from a traditional WordPress plugin?

**A:** See the [Migration Guide](migration.md) for step-by-step instructions. The general process is:

1. Move functions into controller methods
2. Convert manual hooks to array declarations
3. Add validation and security features
4. Implement proper error handling
5. Use the configuration system

### Q: Can I migrate gradually?

**A:** Yes, you can migrate piece by piece. The framework coexists with traditional WordPress plugin code, allowing gradual migration of features.

### Q: How do I update the framework?

**A:** For framework updates:

```bash
composer update adz/wp-plugin-framework
```

Always test updates on staging before production and review the changelog for breaking changes.

## Support & Community

### Q: Where can I get help?

**A:** Support resources:

1. **Documentation**: Comprehensive guides and examples
2. **GitHub Issues**: Bug reports and feature requests  
3. **Examples**: Working code samples in `/examples` directory
4. **Health Check**: Built-in diagnostic tools

### Q: How do I report bugs or request features?

**A:** Use the GitHub repository:

1. **Bugs**: Provide steps to reproduce, environment details, and error messages
2. **Features**: Describe the use case and expected behavior
3. **Include**: Debug information from `./adz.sh health:check`

### Q: Can I contribute to the framework?

**A:** Yes! Contributions are welcome:

1. **Code**: Bug fixes, features, optimizations
2. **Documentation**: Improvements, examples, translations
3. **Testing**: Report issues, test edge cases
4. **Community**: Help other users, share examples

### Q: Is there a roadmap for future features?

**A:** Check the GitHub repository for:
- Open issues tagged as "enhancement"
- Milestones for upcoming releases
- Discussion topics for major features

## Best Practices

### Q: What are the recommended coding standards?

**A:** Follow these practices:

1. **PSR-12**: PHP coding standards
2. **WordPress**: WordPress coding standards for templates
3. **Security**: Always validate input, escape output
4. **Documentation**: PHPDoc for all methods
5. **Testing**: Unit tests for business logic

### Q: How should I structure my plugin?

**A:** Recommended structure:

```
my-plugin/
├── src/
│   ├── controllers/     # Application logic
│   ├── models/         # Data operations  
│   ├── views/          # Templates
│   └── services/       # Business logic
├── config/             # Configuration
├── assets/             # CSS/JS/images
├── tests/              # Unit tests
└── docs/               # Documentation
```

### Q: Should I use models or direct database calls?

**A:** Use models for complex data operations and direct database calls for simple queries:

```php
// Simple query - direct call
$posts = $db->table('posts')->where('status', 'published')->get();

// Complex operations - use model
class PostModel {
    public function getPopularPostsWithAuthors($limit = 10) {
        // Complex query logic
    }
}
```

This FAQ covers the most common questions developers have when using the ADZ Plugin Framework. For additional help, refer to the comprehensive documentation or reach out through the support channels.