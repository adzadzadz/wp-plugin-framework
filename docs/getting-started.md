# Getting Started with ADZ Plugin Framework

The ADZ Plugin Framework is a modern WordPress plugin development framework that provides a structured, object-oriented approach to building WordPress plugins.

## Features

- **Automatic Hook Registration**: Define actions and filters as arrays in your controllers
- **Modern Configuration Management**: Environment-based configuration with dot notation access
- **Comprehensive Security**: Built-in CSRF protection, validation, and sanitization
- **Advanced Error Handling**: PSR-3 compatible logging with custom exception handling
- **REST API Helper**: Complete HTTP client for external API integrations
- **Database Abstraction**: Coming soon - Query builder and migration system
- **Development Tools**: CLI commands and code generators

## Quick Start

### 1. Installation

Clone or download the framework:
```bash
git clone https://github.com/your-repo/wp-plugin-framework.git your-plugin-name
cd your-plugin-name
composer install
```

### 2. Basic Plugin Structure

```
your-plugin/
├── src/
│   ├── controllers/       # Your plugin controllers
│   ├── models/           # Data models
│   ├── views/            # Templates
│   └── assets/           # CSS/JS assets
├── adz/                  # Framework core
├── config/               # Configuration files
└── your-plugin.php       # Main plugin file
```

### 3. Creating Your First Controller

Create a new controller in `src/controllers/`:

```php
<?php

namespace YourPlugin\Controllers;

use AdzWP\Controller;

class MyController extends Controller 
{
    // Define WordPress hooks as arrays
    public $actions = [
        'init' => 'initialize',
        'wp_enqueue_scripts' => [
            'callback' => 'enqueueAssets',
            'priority' => 10
        ]
    ];
    
    public $filters = [
        'the_content' => 'modifyContent'
    ];
    
    public function initialize()
    {
        // Your initialization code
    }
    
    public function enqueueAssets()
    {
        wp_enqueue_script('my-script', plugin_dir_url(__FILE__) . '../assets/js/main.js');
    }
    
    public function modifyContent($content)
    {
        return $content . '<p>Added by my plugin!</p>';
    }
}
```

### 4. Using Configuration

Access configuration values using dot notation:

```php
use AdzHive\Config;

$config = Config::getInstance();

// Get a configuration value
$menuTitle = $config->get('admin.menu_title', 'Default Title');

// Set a configuration value
$config->set('custom.setting', 'value');

// Check if a configuration exists
if ($config->has('logging.enabled')) {
    // Logging is configured
}
```

### 5. Logging and Error Handling

Use the built-in logging system:

```php
// Simple logging
adz_log_info('User logged in', ['user_id' => 123]);
adz_log_error('Database connection failed');

// Using the logger directly
use AdzHive\Log;

$logger = Log::getInstance();
$logger->warning('This is a warning message', ['context' => 'data']);

// Custom exceptions
use AdzHive\ValidationException;

throw new ValidationException('Invalid input', [
    'email' => 'Email is required',
    'name' => 'Name must be at least 3 characters'
]);
```

### 6. Security and Validation

Validate and sanitize user input:

```php
use AdzHive\Validator;
use AdzHive\Security;

// Validate form data
$validator = Validator::make($_POST, [
    'email' => 'required|email',
    'name' => 'required|string|min:3',
    'age' => 'numeric|between:18,100'
]);

if ($validator->fails()) {
    throw new ValidationException('Validation failed', $validator->errors());
}

// Security helpers
$security = Security::getInstance();

// Verify nonce
$security->verifyRequest();

// Sanitize data
$cleanData = $security->sanitizeArray($_POST, [
    'email' => 'email',
    'name' => 'text',
    'description' => 'textarea'
]);
```

### 7. REST API Integration

Make HTTP requests easily:

```php
use AdzHive\helpers\RESTHelper;

// GET request
$api = new RESTHelper('https://api.example.com/users');
$response = $api->get();

if ($response->isSuccess()) {
    $users = $response->getResult();
}

// POST request with authentication
$api = new RESTHelper('https://api.example.com/users')
    ->setBearerToken('your-token')
    ->setHeader('Accept', 'application/json');

$newUser = $api->post(null, [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

## Next Steps

- Read the [Architecture Guide](architecture.md) to understand the framework structure
- Check out the [API Reference](api-reference.md) for detailed documentation
- Browse the [Examples](examples/) folder for more complex implementations
- Learn about [Configuration](configuration.md) options

## Need Help?

- Check the [Troubleshooting Guide](troubleshooting.md)
- Review the example implementations
- Submit issues on GitHub