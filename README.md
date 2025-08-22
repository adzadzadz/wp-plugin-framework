# ADZ WordPress Plugin Framework

> **Operation CWAL (Can't Wait Any Longer)** - Start building WordPress plugins RIGHT NOW with modern MVC architecture.

## 🚀 Why This Framework?

**You're a developer. You love MVC. You hate WordPress's procedural chaos.**

This framework is for developers who:
- ✅ **Can't Wait Any Longer** to start building professional WordPress plugins
- ✅ **Love working with MVC architecture** instead of procedural spaghetti code
- ✅ **Want to start writing plugins immediately** without setup hassle
- ✅ **Demand modern PHP practices** in WordPress development
- ✅ **Need enterprise-grade structure** for scalable plugin development

## ⚡ Operation CWAL - Instant Plugin Development

```bash
# Install via Composer (FASTEST)
composer create-project adz/wp-plugin-framework my-awesome-plugin

# Start coding IMMEDIATELY
cd my-awesome-plugin
```

**That's it. You're ready to build.**

## 🎯 Built for MVC Lovers

### Clean Controller Structure
```php
<?php
namespace App\Controllers;

use AdzWP\Core\Controller;

class MyAwesomeController extends Controller
{
    public $actions = [
        'init' => 'initialize',
        'admin_menu' => 'setupAdminMenu'
    ];

    public function initialize()
    {
        // Your plugin logic here - NO HOOKS MESS!
    }
}
```

### Elegant Database Models
```php
<?php
namespace App\Models;

use AdzWP\Db\Model;

class Post extends Model
{
    protected $table = 'posts';
    protected $fillable = ['title', 'content', 'status'];
    
    // ORM-style relationships and queries
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
```

### Fluent Query Builder
```php
$posts = $this->queryBuilder()
    ->select(['id', 'title', 'content'])
    ->from('posts')
    ->where('status', 'published')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();
```

## 🏗️ Framework Architecture

```
src/
├── AdzMain.php          # Global \Adz framework access
├── Core/                # Core framework components
│   ├── Controller.php   # MVC Controller base class
│   ├── Model.php        # MVC Model base class
│   ├── Config.php       # Configuration management
│   └── View.php         # Template rendering
├── Db/                  # Database layer
│   ├── Model.php        # ORM-style database models
│   ├── QueryBuilder.php # Fluent query interface
│   └── Connection.php   # Database connection
└── Helpers/             # Utility classes
    ├── ArrayHelper.php  # Array manipulation utilities
    └── RESTHelper.php   # REST API utilities
```

## 🎪 Features That Make You Productive

### 🔥 Instant Setup
- **Zero configuration** - Works out of the box
- **PSR-4 autoloading** - Modern PHP standards
- **Composer ready** - Professional dependency management

### 🏢 Enterprise Architecture  
- **MVC pattern** - Separate concerns like a pro
- **ORM-style models** - Database interactions made easy
- **Dependency injection** - Clean, testable code
- **Event system** - WordPress hooks without the mess

### 🧪 Testing Ready
- **61 unit tests included** - Quality guaranteed
- **PHPUnit integration** - Professional testing workflow
- **WordPress mocks** - Test without WordPress overhead

### 📦 Developer Experience
- **IDE friendly** - Full autocompletion and navigation
- **Modern namespaces** - `AdzWP\Core\*`, `AdzWP\Db\*`
- **Global framework access** - Simple `\Adz::config()` calls

## 🚀 Quick Start Example

### 1. Create Your Plugin Structure
```php
// my-plugin.php
<?php
/**
 * Plugin Name: My Awesome Plugin
 * Description: Built with ADZ Framework - Operation CWAL!
 */

require_once 'vendor/autoload.php';

// Initialize framework
$framework = \Adz::config();
$framework->set('plugin.path', __DIR__);

// Load your controllers
new App\Controllers\MyAwesomeController();
```

### 2. Build Your Controller
```php
// src/Controllers/MyAwesomeController.php
<?php
namespace App\Controllers;

use AdzWP\Core\Controller;

class MyAwesomeController extends Controller
{
    public $actions = [
        'init' => 'handleInit',
        'wp_ajax_my_action' => 'handleAjax'
    ];

    public function handleInit()
    {
        if ($this->isAdmin()) {
            $this->setupAdminInterface();
        }
    }

    public function handleAjax()
    {
        $data = ['message' => 'Hello from ADZ Framework!'];
        wp_send_json_success($data);
    }
}
```

### 3. Create Your Models
```php
// src/Models/CustomPost.php
<?php
namespace App\Models;

use AdzWP\Db\Model;

class CustomPost extends Model
{
    protected $table = 'custom_posts';
    protected $fillable = ['title', 'content', 'meta'];

    public function save(): bool
    {
        // Your save logic here
        return true;
    }
}
```

## 📊 Why Developers Choose ADZ Framework

| Traditional WordPress | ADZ Framework |
|----------------------|---------------|
| 🤮 Procedural spaghetti | 🎯 Clean MVC architecture |
| 🐌 Manual hook management | ⚡ Automatic hook registration |
| 😵 Global function chaos | 🏗️ Organized namespaces |
| 🔧 Manual setup hell | 🚀 Instant productivity |
| 🐛 Hard to test | 🧪 Test-driven development |

## 🎯 Operation CWAL Targets

- ✅ **Plugin MVP in under 30 minutes**
- ✅ **Enterprise plugin architecture from day 1**
- ✅ **Zero WordPress procedural code**
- ✅ **Testable, maintainable, scalable**
- ✅ **Modern PHP development experience**

## 📚 Documentation & Support

- **[Complete Documentation](docs/)** - Comprehensive framework guide
- **[Quick Start Guide](docs/getting-started.md)** - Get building immediately
- **[API Reference](docs/api/core.md)** - Full framework reference
- **[Plugin Lifecycle](docs/PLUGIN_LIFECYCLE.md)** - Install/uninstall hooks
- **[Dependency Management](docs/dependency-management.md)** - Automatic plugin installation
- **[Controllers Guide](docs/controllers.md)** - MVC controller patterns
- **[First Plugin Tutorial](docs/examples/first-plugin.md)** - Complete working example
- **[Unit Tests](tests/)** - 61 tests, 85 assertions, 100% pass rate

## 🔧 Requirements

- **PHP 7.4+** (Modern PHP features)
- **WordPress 5.0+** (Latest WordPress APIs)
- **Composer** (Dependency management)

## 📈 Framework Stats

- ✅ **61 Unit Tests** - Quality guaranteed
- ✅ **85 Assertions** - Thoroughly tested
- ✅ **100% Pass Rate** - Production ready
- ✅ **PSR-4 Compliant** - Modern standards
- ✅ **Zero Dependencies** - Lightweight core

## 🎪 Get Started NOW

```bash
# Operation CWAL - Deploy immediately!
composer create-project adz/wp-plugin-framework my-plugin
cd my-plugin
# Start building your WordPress plugin empire! 🚀
```

---

**Built for developers who Can't Wait Any Longer to create amazing WordPress plugins with professional MVC architecture.**

*ADZ Framework - Because WordPress development should be enjoyable, not painful.*