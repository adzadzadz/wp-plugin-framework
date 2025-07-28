# Framework Architecture

Understanding the ADZ Plugin Framework architecture will help you build better, more maintainable WordPress plugins. This guide explains the core concepts, design patterns, and structure.

## Core Philosophy

The framework is built on these architectural principles:

1. **MVC Pattern** - Separation of concerns with Controllers, Models, and Views
2. **Dependency Injection** - Loose coupling and better testability
3. **Configuration-Driven** - Behavior controlled through configuration files
4. **Security-First** - Built-in protection mechanisms
5. **WordPress Native** - Full integration with WordPress hooks and conventions

## Framework Structure

### Directory Layout

```
wp-plugin-framework/
├── adz/                        # Framework Core
│   ├── dev-tools/
│   │   ├── hive/              # Core Framework Classes
│   │   │   ├── Core.php       # Base core class
│   │   │   ├── Config.php     # Configuration management
│   │   │   ├── Security.php   # Security utilities
│   │   │   ├── Database.php   # Database abstraction
│   │   │   ├── Log.php        # Logging system
│   │   │   ├── Exception.php  # Custom exceptions
│   │   │   ├── Validator.php  # Input validation
│   │   │   ├── Console.php    # CLI commands
│   │   │   └── helpers/       # Helper classes and functions
│   │   └── wp/                # WordPress Integration
│   │       ├── Controller.php # Base controller
│   │       ├── Model.php      # Base model
│   │       └── View.php       # View rendering
├── src/                       # Your Plugin Code
│   ├── controllers/           # Application controllers
│   ├── models/               # Data models
│   ├── views/                # Templates and views
│   └── assets/               # CSS, JS, images
├── config/                   # Configuration files
├── docs/                     # Documentation
└── vendor/                   # Composer dependencies
```

## Core Components

### 1. Core Classes (`AdzHive` Namespace)

#### Core.php
The foundation class that provides:
- Automatic hook registration
- Container for dependency injection
- Bootstrap functionality
- Base functionality for all framework components

```php
abstract class Core extends Adz {
    protected $container = [];
    
    public function init() {
        $this->registerHooks();
        $this->bootstrap();
    }
    
    protected function registerHooks() {
        // Automatically registers $actions and $filters
    }
}
```

#### Config.php
Configuration management system:
- Dot notation access (`config.get('database.prefix')`)
- Environment variable support
- Multiple configuration file loading
- Default values and validation

#### Security.php
Security utilities including:
- CSRF protection with nonces
- Input validation and sanitization
- Rate limiting
- Capability checking
- IP detection and client identification

#### Validator.php
Comprehensive input validation:
- 20+ validation rules
- Custom error messages
- Database validation (unique, exists)
- Nested validation with dot notation

### 2. WordPress Integration (`AdzWP` Namespace)

#### Controller.php
Base controller class for WordPress integration:
```php
class Controller extends \AdzHive\Controller {
    public $filters = [];  // WordPress filters
    public $actions = [];  // WordPress actions
}
```

#### Model.php
Base model class for data operations:
- Database integration
- WordPress-specific functionality
- Caching support

#### View.php
Template rendering system:
- WordPress template hierarchy integration
- Variable passing and escaping
- Asset management

## Design Patterns

### 1. Hook Registration Pattern

Instead of manually calling `add_action()` and `add_filter()`, define hooks declaratively:

```php
class MyController extends Controller {
    public $actions = [
        'init' => 'initialize',
        'admin_menu' => [
            'callback' => 'addMenu',
            'priority' => 10,
            'accepted_args' => 1
        ]
    ];
    
    public $filters = [
        'the_content' => 'modifyContent'
    ];
}
```

### 2. Configuration Pattern

Centralized configuration management:

```php
// Access configuration
$menuTitle = Config::getInstance()->get('admin.menu_title', 'Default');

// Set configuration
$config->set('custom.feature', true);

// Environment variables
$apiKey = $config->getEnv('API_KEY', 'default-key');
```

### 3. Validation Pattern

Consistent input validation:

```php
$validator = Validator::make($_POST, [
    'email' => 'required|email',
    'name' => 'required|string|min:3',
    'age' => 'numeric|between:18,100'
]);

if ($validator->fails()) {
    throw new ValidationException('Invalid input', $validator->errors());
}
```

### 4. Database Pattern

Fluent database operations:

```php
$users = Database::getInstance()
    ->table('users')
    ->where('status', 'active')
    ->where('created_at', '>', '2023-01-01')
    ->orderBy('name')
    ->limit(10)
    ->get();
```

### 5. Security Pattern

Built-in security checks:

```php
// CSRF protection
$security = Security::getInstance();
$security->verifyRequest('_my_nonce', 'my_action');

// Input sanitization
$cleanData = $security->sanitizeArray($_POST, [
    'email' => 'email',
    'name' => 'text',
    'content' => 'html'
]);

// Rate limiting
$security->checkRateLimit('contact_form', 5, 300); // 5 attempts per 5 minutes
```

## Request Lifecycle

### 1. Plugin Initialization
```
Plugin Loaded → Framework Bootstrap → Controllers Loaded → Hooks Registered
```

### 2. Request Processing
```
WordPress Init → Controller Actions → Validation → Business Logic → Response
```

### 3. Error Handling
```
Exception Thrown → Logger Called → User Notification → Graceful Degradation
```

## Component Communication

### 1. Event System
Controllers communicate through WordPress hooks:

```php
// Trigger an event
do_action('my_plugin_user_created', $userId, $userData);

// Listen for events
public $actions = [
    'my_plugin_user_created' => 'handleUserCreated'
];
```

### 2. Configuration Sharing
Shared configuration across components:

```php
// Set in one component
Config::getInstance()->set('feature.enabled', true);

// Access in another
if (Config::getInstance()->get('feature.enabled')) {
    // Feature logic
}
```

### 3. Service Container
Dependency injection for loose coupling:

```php
// Register service
$core->bind('email_service', new EmailService());

// Use service
$emailService = $core->get('email_service');
```

## Security Architecture

### 1. Input Validation
All user input is validated before processing:
- Validation rules applied automatically
- Custom validation messages
- Multi-level validation (client, server, database)

### 2. Output Escaping
All output is escaped based on context:
- HTML escaping for content
- Attribute escaping for HTML attributes
- URL escaping for links
- JavaScript escaping for JS context

### 3. Permission Checking
Capability-based access control:
- User capabilities checked for all admin functions
- Role-based permissions
- Custom capability support

### 4. CSRF Protection
All forms protected against CSRF:
- Automatic nonce generation
- Nonce verification on form submission
- AJAX request protection

## Database Design

### 1. Table Naming
- Prefix: `{wp_prefix}{plugin_prefix}_`
- Example: `wp_adz_users`, `wp_adz_settings`

### 2. Schema Management
- Migration system for version control
- Automatic table creation
- Schema validation

### 3. Query Builder
- Fluent interface for complex queries
- Automatic SQL injection prevention
- Performance optimization

## Performance Considerations

### 1. Caching Strategy
- Configuration caching
- Query result caching
- Template caching
- Transient API integration

### 2. Lazy Loading
- Controllers loaded only when needed
- Database connections on demand
- Asset loading optimization

### 3. Memory Management
- Singleton patterns for shared resources
- Proper object cleanup
- Memory-efficient data structures

## Extensibility

### 1. Hook System
Rich hook system for customization:
- Action hooks for events
- Filter hooks for data modification
- Priority system for execution order

### 2. Plugin Extensions
Framework supports plugin extensions:
- Additional validation rules
- Custom security checks
- New CLI commands
- Database adapters

### 3. Theme Integration
Seamless theme integration:
- Template override system
- Asset enqueueing
- CSS/JS customization

## Testing Architecture

### 1. Unit Testing
- PHPUnit integration
- Mock objects for WordPress functions
- Isolated component testing

### 2. Integration Testing
- WordPress test environment
- Database testing with fixtures
- API endpoint testing

### 3. Security Testing
- Input validation testing
- CSRF protection testing
- Permission checking verification

## Best Practices

### 1. Code Organization
- One class per file
- Namespace usage
- Clear naming conventions
- Proper documentation

### 2. Error Handling
- Graceful error degradation
- Comprehensive logging
- User-friendly error messages
- Development vs production modes

### 3. Security
- Validate all input
- Escape all output
- Check permissions
- Use prepared statements

### 4. Performance
- Cache when possible
- Minimize database queries
- Optimize asset loading
- Use WordPress best practices

## Migration Path

### From Standard WordPress Plugins
1. Move functions to controller methods
2. Convert hooks to array declarations
3. Add validation and security
4. Implement proper error handling
5. Use configuration system

### Backward Compatibility
The framework maintains compatibility with:
- Existing WordPress hooks
- Standard plugin patterns
- Theme integration
- Third-party plugins

This architecture provides a solid foundation for building modern, secure, and maintainable WordPress plugins while maintaining full compatibility with the WordPress ecosystem.