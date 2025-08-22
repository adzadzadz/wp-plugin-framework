# Framework Architecture

The ADZ WordPress Plugin Framework follows modern software architecture principles, providing a clean, maintainable foundation for WordPress plugin development.

## Design Principles

### 1. MVC Pattern
The framework implements a clear Model-View-Controller pattern:
- **Models** handle data and business logic
- **Views** manage presentation and templates  
- **Controllers** coordinate between models and views

### 2. Separation of Concerns
Each component has a single, well-defined responsibility:
- Core classes handle framework functionality
- Database classes manage data persistence
- Helper classes provide utility functions
- Traits offer reusable behaviors

### 3. PSR-4 Autoloading
Modern PHP namespacing and autoloading standards:
```
AdzWP\Core\*     - Core framework classes
AdzWP\Db\*       - Database layer
AdzWP\Helpers\*  - Utility functions
AdzWP\Traits\*   - Reusable behaviors
```

## Directory Structure

```
src/
├── AdzMain.php              # Global framework access point
├── Core/                    # Core framework components
│   ├── Config.php          # Configuration management
│   ├── Controller.php      # Base controller class
│   ├── Core.php            # Base core functionality
│   ├── Dependency.php      # Plugin dependency management
│   ├── Plugin.php          # Plugin lifecycle management
│   ├── PluginManager.php   # Simplified plugin setup
│   ├── Security.php        # Security utilities
│   ├── View.php            # Template rendering
│   └── ...
├── Db/                      # Database abstraction layer
│   ├── Connection.php      # Database connections
│   ├── Model.php           # Base model class
│   ├── QueryBuilder.php    # Fluent query interface
│   ├── Schema.php          # Database schema management
│   └── ...
├── Helpers/                 # Utility classes
│   ├── ArrayHelper.php     # Array manipulation
│   ├── RESTHelper.php      # REST API utilities
│   └── FrameworkHelper.php # Framework utilities
└── Traits/                  # Reusable behaviors
    ├── Behavior.php        # Base behavior trait
    ├── Status.php          # Status management
    └── Scenario.php        # Scenario handling
```

## Core Components

### Global Access Point

The `\Adz` class provides global access to framework functionality:

```php
// Get configuration
$config = \Adz::config();

// Service resolution
$service = \Adz::resolve('my-service');

// Quick configuration access
$value = \Adz::get('app.name', 'Default');
```

### Core Classes

#### Base Core Class
All framework classes extend the `Core` base class:

```php
abstract class Core
{
    protected $container = [];  // Service container
    
    public function init() { }  // Initialization hook
    protected function registerHooks() { }  // WordPress hook registration
    protected function bootstrap() { }  // Additional setup
}
```

#### Controller System
Controllers handle WordPress hooks declaratively:

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
}
```

#### Plugin Lifecycle
Comprehensive plugin management:

```php
$manager = PluginManager::getInstance(__FILE__);
$manager
    ->onActivate(function() { /* activation logic */ })
    ->onDeactivate(function() { /* deactivation logic */ })
    ->setDependencies([/* auto-install dependencies */]);
```

### Database Layer

#### Query Builder
Fluent database query interface:

```php
$results = $this->queryBuilder()
    ->select(['id', 'name', 'email'])
    ->from('users')
    ->where('status', 'active')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();
```

#### Model System
ORM-style models for data management:

```php
class User extends Model
{
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'status'];
    
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
```

## Service Container

The framework includes a lightweight service container for dependency injection:

### Binding Services

```php
// Bind a value
\Adz::bind('mailer', new MailService());

// Bind a factory function
\Adz::bind('database', function() {
    return new DatabaseConnection();
});

// Singleton services
\Adz::singleton('cache', function() {
    return new CacheManager();
});
```

### Resolving Services

```php
// Resolve service
$mailer = \Adz::resolve('mailer');

// With default value
$cache = \Adz::resolve('cache', new DefaultCache());

// Get singleton
$cache = \Adz::service('cache');
```

## Configuration System

### Hierarchical Configuration

The configuration system supports dot notation for nested values:

```php
// Set configuration
\Adz::set('database.host', 'localhost');
\Adz::set('database.credentials.username', 'admin');

// Get configuration
$host = \Adz::get('database.host');
$username = \Adz::get('database.credentials.username', 'default_user');

// Check if exists
if (\Adz::config()->has('database.host')) {
    // Configuration exists
}
```

### Configuration Sources

Configuration can come from multiple sources:
- PHP configuration files
- WordPress options
- Environment variables
- Runtime settings

## Hook System

### Automatic Registration

Controllers automatically register WordPress hooks:

```php
class EventController extends Controller
{
    // These hooks are registered automatically
    public $actions = [
        'wp_loaded' => 'onWordPressLoaded',
        'admin_menu' => ['setupAdminMenu', 20, 1]  // Custom priority/args
    ];
    
    public $filters = [
        'post_class' => 'addCustomClasses',
        'the_content' => [
            'callback' => 'enhanceContent',
            'priority' => 5,
            'accepted_args' => 2
        ]
    ];
}
```

### Manual Registration

For dynamic hook registration:

```php
public function initialize()
{
    // Register additional hooks based on conditions
    if ($this->isAdmin()) {
        $this->registerAction('admin_notices', 'showNotices');
    }
    
    if (get_option('feature_enabled')) {
        $this->registerFilter('widget_text', 'processWidget');
    }
}
```

## Security Architecture

### Built-in Security Features

- Nonce verification utilities
- Capability checking helpers
- Input sanitization
- SQL injection prevention
- XSS protection

### Security Utilities

```php
// Verify capabilities
$this->verifyCap('manage_options');

// Verify nonces
$this->verifyNonce('my_action', $_POST['nonce']);

// Sanitize input
$clean_data = Security::sanitize($_POST['data']);

// Validate data
$errors = Security::validate($data, [
    'email' => 'required|email',
    'name' => 'required|min:3'
]);
```

## Testing Architecture

### Test Structure

```
tests/
├── bootstrap.php           # Test bootstrap
├── Unit/                   # Unit tests
│   ├── Core/              # Core class tests
│   ├── Db/                # Database layer tests
│   └── Helpers/           # Helper class tests
└── Integration/           # Integration tests
    └── FrameworkTest.php  # Full framework tests
```

### Test Base Classes

Framework provides test utilities:

```php
class MyPluginTest extends \AdzWP\Tests\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Test setup
    }
    
    public function testMyFeature()
    {
        // Test logic
    }
}
```

## Performance Considerations

### Lazy Loading

Framework components are loaded only when needed:
- Controllers are instantiated on demand
- Database connections are established when required
- Services are resolved on first access

### Optimizations

- Autoloader optimization for production
- Query result caching
- Hook registration optimization
- Minimal WordPress core dependencies

### Memory Management

- Efficient object creation
- Proper cleanup in destructors
- Minimal global state
- Service container lifecycle management

## Extension Points

### Custom Core Classes

Extend framework functionality:

```php
class MyCustomCore extends \AdzWP\Core\Core
{
    protected function bootstrap()
    {
        parent::bootstrap();
        // Custom initialization
    }
}
```

### Plugin Hooks

Framework provides hooks for extensions:

```php
// Framework initialization
do_action('adz_framework_init', $framework);

// Plugin lifecycle
do_action('adz_plugin_activated', $plugin);
do_action('adz_plugin_deactivated', $plugin);

// Configuration loaded
do_action('adz_config_loaded', $config);
```

### Service Providers

Register custom service providers:

```php
class MyServiceProvider
{
    public function register()
    {
        \Adz::bind('my-service', function() {
            return new MyService();
        });
    }
}
```

## Best Practices

### 1. Follow PSR Standards
- PSR-4 autoloading
- PSR-1 basic coding standard
- PSR-12 extended coding style

### 2. Use Dependency Injection
- Inject dependencies through constructor
- Use service container for complex dependencies
- Avoid global state when possible

### 3. Implement Proper Error Handling
- Use exceptions for error conditions
- Log errors appropriately
- Provide user-friendly error messages

### 4. Write Tests
- Unit tests for business logic
- Integration tests for component interaction
- Test coverage for critical paths

### 5. Follow WordPress Standards
- Use WordPress coding standards
- Respect WordPress hooks and filters
- Follow WordPress security practices

This architecture provides a solid foundation for building maintainable, scalable WordPress plugins while maintaining compatibility with WordPress standards and practices.