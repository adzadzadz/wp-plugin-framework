# Installation & Setup

This guide will walk you through installing and setting up the ADZ Plugin Framework for your WordPress plugin development.

## System Requirements

- **PHP**: 7.4 or higher (8.0+ recommended)
- **WordPress**: 5.0 or higher (6.0+ recommended)
- **Composer**: Latest version
- **MySQL**: 5.7 or higher / MariaDB 10.3 or higher

### Required PHP Extensions
- `curl` - For REST API functionality
- `json` - For configuration and data handling
- `mbstring` - For string manipulation
- `pdo_mysql` - For database operations

## Installation Methods

### Method 1: Git Clone (Recommended)

```bash
# Clone the framework
git clone https://github.com/your-repo/wp-plugin-framework.git my-plugin
cd my-plugin

# Install dependencies
composer install

# Initialize the framework
./adz.sh init
```

### Method 2: Download Release

1. Download the latest release from GitHub
2. Extract to your plugins directory
3. Rename the folder to your plugin name
4. Run `composer install`
5. Run `./adz.sh init`

### Method 3: Composer Create-Project

```bash
composer create-project adz/wp-plugin-framework my-plugin
cd my-plugin
./adz.sh init
```

## Initial Configuration

### 1. Update Plugin Information

Edit the main plugin file (`adz-plugin.php` or rename it):

```php
<?php
/**
 * Plugin Name: My Awesome Plugin
 * Plugin URI: https://example.com/my-plugin
 * Description: Description of your plugin
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: my-plugin
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MY_PLUGIN_VERSION', '1.0.0');
define('MY_PLUGIN_FILE', __FILE__);
define('MY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MY_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load the framework
require_once MY_PLUGIN_PATH . 'vendor/autoload.php';

// Initialize your plugin
new MyPlugin\Plugin();
```

### 2. Generate Configuration Files

```bash
./adz.sh make:config
```

This creates configuration files in `config/`:
- `app.php` - Main application settings
- `database.php` - Database configuration
- `security.php` - Security settings
- `logging.php` - Logging configuration
- `cache.php` - Cache settings

### 3. Update Configuration

Edit `config/app.php`:

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
    ]
];
```

## Directory Structure Setup

After installation, your plugin should have this structure:

```
my-plugin/
├── adz/                    # Framework core
│   ├── dev-tools/
│   └── composer.json
├── config/                 # Configuration files
│   ├── app.php
│   ├── database.php
│   └── ...
├── src/                    # Your plugin code
│   ├── controllers/
│   ├── models/
│   ├── views/
│   └── assets/
├── docs/                   # Documentation
├── vendor/                 # Composer dependencies
├── composer.json
├── adz.sh                  # CLI tool
└── my-plugin.php           # Main plugin file
```

## Framework Initialization

Create your main plugin class in `src/Plugin.php`:

```php
<?php

namespace MyPlugin;

use AdzHive\Config;
use MyPlugin\Controllers\AdminController;
use MyPlugin\Controllers\FrontendController;

class Plugin
{
    protected $config;
    protected $controllers = [];
    
    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->loadControllers();
        $this->init();
    }
    
    protected function loadControllers()
    {
        $this->controllers = [
            'admin' => new AdminController(),
            'frontend' => new FrontendController(),
        ];
    }
    
    protected function init()
    {
        // Initialize each controller
        foreach ($this->controllers as $controller) {
            if (method_exists($controller, 'init')) {
                $controller->init();
            }
        }
        
        // Register activation/deactivation hooks
        register_activation_hook(MY_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(MY_PLUGIN_FILE, [$this, 'deactivate']);
    }
    
    public function activate()
    {
        // Plugin activation logic
        $this->createTables();
        $this->setDefaultOptions();
    }
    
    public function deactivate()
    {
        // Plugin deactivation logic
        $this->clearCache();
    }
    
    protected function createTables()
    {
        // Create database tables if needed
        // See Database documentation for examples
    }
    
    protected function setDefaultOptions()
    {
        // Set default WordPress options
    }
    
    protected function clearCache()
    {
        // Clear any cached data
    }
}
```

## Verification

### 1. Check Installation

Run the health check command:

```bash
./adz.sh health:check
```

This will verify:
- Database connectivity
- File permissions
- Required PHP extensions
- Framework components

### 2. Test Basic Functionality

Create a simple controller to test the framework:

```bash
./adz.sh make:controller TestController
```

Edit `src/controllers/TestController.php`:

```php
<?php

namespace MyPlugin\Controllers;

use AdzWP\Controller;

class TestController extends Controller 
{
    public $actions = [
        'wp_footer' => 'addTestOutput'
    ];
    
    public function addTestOutput()
    {
        if (current_user_can('manage_options')) {
            echo '<!-- ADZ Framework is working! -->';
        }
    }
}
```

Add it to your Plugin class and check your site's footer source code.

## Next Steps

1. **Read the [Quick Start Guide](getting-started.md)** to build your first feature
2. **Review [Framework Architecture](architecture.md)** to understand the structure
3. **Explore [Examples](examples/)** for real-world implementations
4. **Check [CLI Tools](cli/overview.md)** for development commands

## Troubleshooting

### Common Issues

**Composer not found:**
```bash
# Install Composer globally
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

**Permission denied for adz.sh:**
```bash
chmod +x adz.sh
```

**Database connection issues:**
- Verify WordPress database credentials
- Check database user permissions
- Ensure MySQL/MariaDB is running

**Missing PHP extensions:**
```bash
# Ubuntu/Debian
sudo apt-get install php-curl php-json php-mbstring php-mysql

# CentOS/RHEL
sudo yum install php-curl php-json php-mbstring php-mysql
```

For more troubleshooting help, see the [Troubleshooting Guide](troubleshooting.md).

## Support

- **Documentation**: Check this documentation site
- **GitHub Issues**: Report bugs and request features
- **Examples**: Review working code in the `/examples` directory