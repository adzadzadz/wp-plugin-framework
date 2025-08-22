# ADZ WordPress Plugin Framework

**🚀 The Intuitive MVC Framework for WordPress Plugin Development**

> **Perfect for developers who love working with MVC architecture and want to build WordPress plugins right away without extensive setup or configuration.**

## Why ADZ Framework?

✨ **Built for MVC Lovers** - Clean separation of Models, Views, and Controllers  
⚡ **Zero Configuration** - Start building immediately with sensible defaults  
🎯 **WordPress Native** - Seamless integration with WordPress hooks, actions, and filters  
🔧 **Database Ready** - Built-in query builder, migrations, and ORM-like features  
🧪 **Testing First** - Comprehensive testing framework with WordPress mocking  
📦 **Code Generation** - Built-in generators for controllers, models, and views  

## Quick Start

### 1. Get Started in 30 Seconds

```bash
# Clone the framework
git clone https://github.com/your-username/adz-wp-framework.git my-plugin
cd my-plugin

# Install dependencies
composer install

# Generate your first controller
./adz.sh make:controller PostController

# You're ready to build! 🎉
```

### 2. Your First Controller

```php
<?php
namespace adz\controllers;

use AdzWP\WordPressController as Controller;

class PostController extends Controller 
{
    // WordPress hooks are automatically registered!
    public $actions = [
        'init' => 'initialize',
        'wp_enqueue_scripts' => 'enqueueAssets'
    ];

    public $filters = [
        'the_content' => 'enhanceContent'
    ];

    public function initialize() 
    {
        // WordPress hooks, actions, and filters are accessible as methods
        if ($this->currentUserCan('manage_options')) {
            $this->doAction('my_custom_action', 'Hello World');
        }
    }

    public function enhanceContent($content) 
    {
        return $this->applyFilters('my_content_filter', $content . ' [Enhanced]');
    }

    public function enqueueAssets() 
    {
        adz_enqueue_asset('my-script', 'assets/js/frontend.js');
    }
}
```

## MVC Architecture Made Simple

### Controllers 📋
Handle requests, manage business logic, and coordinate between Models and Views.

### Models 📊
Interact with the database using the built-in query builder and ORM features.

### Views 🎨
Clean, reusable templates with data binding.

## Database Management

### Query Builder - Eloquent-Style for WordPress

```php
use AdzWP\QueryBuilder;

// Simple queries
$users = QueryBuilder::table('users')
    ->where('status', 'active')
    ->orderBy('created_at', 'DESC')
    ->paginate(20);

// Complex queries with joins
$posts = QueryBuilder::table('posts')
    ->leftJoin('users', 'posts.author_id', '=', 'users.id')
    ->where('posts.status', 'published')
    ->get();
```

## WordPress Integration Features

### 🔌 Hook Management
All WordPress hooks accessible as methods in your controllers.

### 🛡️ Security First
Built-in sanitization, nonce verification, and capability checking.

### ⚙️ Configuration Management
Simple configuration access with dot notation and environment variables.

## Helper Functions

15+ helper functions for common WordPress tasks including options, sanitization, assets, and more.

## Testing Framework

Built-in PHPUnit testing with WordPress function mocking and comprehensive test utilities.

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Composer

## License

MIT License - Build amazing WordPress plugins with confidence!

---

**Ready to build your next WordPress plugin with clean MVC architecture?**

*Built with ❤️ for WordPress developers who value clean code and rapid development.*
