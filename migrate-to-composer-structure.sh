#!/bin/bash

# ADZ WordPress Framework - Migration to Composer Structure
# This script restructures the project to follow professional PHP/Composer standards

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

log_success() {
    echo -e "${GREEN}✓${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

log_error() {
    echo -e "${RED}✗${NC} $1"
}

show_header() {
    echo -e "${BLUE}🔄 ADZ Framework Migration Tool${NC}"
    echo -e "${YELLOW}   Migrating to Professional Composer Structure${NC}"
    echo ""
}

main() {
    show_header
    
    # Check if we're in the right directory
    if [[ ! -f "adz.sh" ]]; then
        log_error "Please run this script from the framework root directory"
        exit 1
    fi
    
    log_info "Starting migration to professional Composer structure..."
    echo ""
    
    # Step 1: Create framework package directory
    log_info "Step 1: Creating framework package structure..."
    
    if [[ ! -d "../adz-wp-framework" ]]; then
        mkdir -p ../adz-wp-framework/{src,tests,docs}
        log_success "Created framework package directory"
    else
        log_warning "Framework package directory already exists"
    fi
    
    # Step 2: Copy framework files
    log_info "Step 2: Copying framework files..."
    
    if [[ -d "adz/wp" ]]; then
        cp -r adz/wp/* ../adz-wp-framework/src/
        log_success "Copied framework source files"
        
        # Copy framework composer.json
        cp adz-wp-framework-composer.json ../adz-wp-framework/composer.json
        log_success "Created framework composer.json"
        
        # Copy LICENSE
        cp LICENSE ../adz-wp-framework/
        log_success "Copied LICENSE to framework"
        
    else
        log_error "Framework source directory not found"
        exit 1
    fi
    
    # Step 3: Create framework README
    log_info "Step 3: Creating framework documentation..."
    
    cat > ../adz-wp-framework/README.md << 'EOF'
# ADZ WordPress Framework

Advanced WordPress plugin development framework with MVC architecture and modern PHP practices.

## Features

- 🏗️ **MVC Architecture**: Clean separation of concerns
- 🔒 **Security First**: Built-in nonce handling, input sanitization, and capability checks
- 🗄️ **Database Management**: Query builder, migrations, and ORM-style models  
- 🧪 **Testing Ready**: PHPUnit integration with WordPress test suite
- ⚡ **Performance Optimized**: Caching, lazy loading, and optimized queries
- 🎨 **Asset Management**: Smart enqueuing with versioning and minification
- 🔧 **CLI Tools**: Code generation and development utilities

## Installation

```bash
composer require adz/wp-framework
```

## Quick Start

### 1. Create a Controller

```php
<?php
namespace YourPlugin\Controllers;

use AdzWP\Controller;

class HomeController extends Controller 
{
    public $actions = [
        'init' => 'initialize'
    ];
    
    public function initialize() {
        add_shortcode('my_shortcode', [$this, 'renderShortcode']);
    }
    
    public function renderShortcode($atts) {
        return $this->renderView('shortcode-template', $atts);
    }
}
```

### 2. Create a Model

```php
<?php
namespace YourPlugin\Models;

use AdzWP\Model;

class Post extends Model 
{
    protected $table = 'posts';
    protected $primaryKey = 'ID';
    
    public function getPublished() {
        return $this->where('post_status', 'publish')->get();
    }
}
```

### 3. Use in Your Plugin

```php
<?php
// your-plugin.php

require_once __DIR__ . '/vendor/autoload.php';

use AdzWP\Core\Plugin;
use YourPlugin\Controllers\HomeController;

$plugin = new Plugin(__FILE__);
$plugin->addController(HomeController::class);
$plugin->boot();
```

## Documentation

- [Getting Started Guide](docs/getting-started.md)
- [Controllers](docs/controllers.md)
- [Models & Database](docs/models.md)
- [Views & Templates](docs/views.md)
- [Security Features](docs/security.md)
- [Testing](docs/testing.md)

## Requirements

- PHP 7.4+
- WordPress 5.0+

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please read our [Contributing Guide](CONTRIBUTING.md) for details.
EOF
    
    log_success "Created framework README"
    
    # Step 4: Initialize framework git repository
    log_info "Step 4: Initializing framework git repository..."
    
    cd ../adz-wp-framework
    if [[ ! -d ".git" ]]; then
        git init
        git add .
        git commit -m "Initial commit: ADZ WordPress Framework package"
        log_success "Initialized framework git repository"
    else
        log_warning "Framework git repository already exists"
    fi
    cd - > /dev/null
    
    # Step 5: Update plugin template
    log_info "Step 5: Updating plugin template structure..."
    
    # Backup original composer.json
    if [[ -f "composer.json" ]]; then
        cp composer.json composer.json.backup
        log_success "Backed up original composer.json"
    fi
    
    # Update composer.json
    cp composer-updated.json composer.json
    log_success "Updated plugin composer.json"
    
    # Create example plugin bootstrap
    cat > example-plugin.php << 'EOF'
<?php
/**
 * Plugin Name: ADZ Framework Example Plugin
 * Plugin URI: https://github.com/adz/wp-plugin-template
 * Description: Example WordPress plugin using the ADZ Framework
 * Version: 1.0.0
 * Author: Adrian T. Saycon
 * License: MIT
 * Text Domain: adz-example
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ADZ_EXAMPLE_VERSION', '1.0.0');
define('ADZ_EXAMPLE_PLUGIN_FILE', __FILE__);
define('ADZ_EXAMPLE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ADZ_EXAMPLE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load Composer autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>ADZ Example Plugin:</strong> Please run <code>composer install</code> to install dependencies.';
        echo '</p></div>';
    });
    return;
}

// Initialize plugin
use AdzWP\Core\Plugin;
use YourPlugin\Controllers\ExampleController;

try {
    $plugin = new Plugin(__FILE__);
    $plugin->addController(ExampleController::class);
    $plugin->boot();
} catch (Exception $e) {
    add_action('admin_notices', function() use ($e) {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>ADZ Example Plugin Error:</strong> ' . esc_html($e->getMessage());
        echo '</p></div>';
    });
}
EOF
    
    log_success "Created example plugin bootstrap file"
    
    # Step 6: Create example controller
    log_info "Step 6: Creating example plugin files..."
    
    mkdir -p src/Controllers src/Models src/Views
    
    cat > src/Controllers/ExampleController.php << 'EOF'
<?php

namespace YourPlugin\Controllers;

use AdzWP\Controller;

class ExampleController extends Controller 
{
    public $actions = [
        'init' => 'initialize',
        'wp_enqueue_scripts' => 'enqueueAssets'
    ];
    
    public $filters = [
        'the_content' => 'modifyContent'
    ];
    
    protected function bootstrap()
    {
        // Controller initialization
    }
    
    public function initialize()
    {
        // Add shortcode
        add_shortcode('adz_example', [$this, 'renderExampleShortcode']);
    }
    
    public function enqueueAssets()
    {
        if (!is_admin()) {
            wp_enqueue_style(
                'adz-example-style',
                ADZ_EXAMPLE_PLUGIN_URL . 'assets/css/frontend.css',
                [],
                ADZ_EXAMPLE_VERSION
            );
        }
    }
    
    public function renderExampleShortcode($atts)
    {
        $atts = shortcode_atts([
            'title' => 'Hello ADZ Framework!',
            'message' => 'This is an example shortcode.'
        ], $atts);
        
        return $this->renderView('example-shortcode', $atts);
    }
    
    public function modifyContent($content)
    {
        if (is_single() && in_the_loop()) {
            $content .= '<p><em>Powered by ADZ Framework</em></p>';
        }
        
        return $content;
    }
}
EOF
    
    log_success "Created example controller"
    
    # Step 7: Show next steps
    echo ""
    log_success "Migration completed successfully!"
    echo ""
    log_info "Next steps:"
    echo "  1. cd ../adz-wp-framework"
    echo "  2. Create GitHub repository and push framework"
    echo "  3. Submit to Packagist (optional) or use as private package"
    echo "  4. cd back to plugin directory"
    echo "  5. Update composer.json repository URL to your framework repo"
    echo "  6. Run: composer install"
    echo ""
    log_info "Framework structure:"
    echo "  📦 ../adz-wp-framework/     - Framework package (publish this)"
    echo "  📁 ./                       - Plugin template (use this for projects)"
    echo ""
    log_warning "Don't forget to:"
    echo "  - Update repository URLs in composer.json files"
    echo "  - Create proper GitHub repositories"
    echo "  - Update documentation with your actual URLs"
}

main "$@"