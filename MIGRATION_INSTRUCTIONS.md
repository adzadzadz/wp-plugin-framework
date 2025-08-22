# WordPress Plugin Framework Migration Instructions

## Overview
This guide will help you restructure your framework to follow professional PHP/Composer standards where the framework is distributed as a separate package.

## Step 1: Create Framework Package Repository

### 1.1 Create New Repository
```bash
# Create new repository: adz-wp-framework
mkdir ../adz-wp-framework
cd ../adz-wp-framework
git init
```

### 1.2 Setup Framework Package Structure
```bash
# Create directory structure
mkdir -p src/{Core,Database,Helpers,Traits,WordPress,Providers}
mkdir -p tests/{Unit,Integration}
mkdir -p docs

# Copy framework files from current project
cp -r /path/to/current/adz/wp/* src/

# Create package files
```

**composer.json** for framework package:
```json
{
  "name": "adz/wp-framework",
  "description": "Advanced WordPress Plugin Development Framework",
  "type": "library",
  "license": "MIT",
  "keywords": ["wordpress", "plugin", "framework", "mvc", "php"],
  "homepage": "https://github.com/adz/wp-framework",
  "authors": [
    {
      "name": "Adrian T. Saycon",
      "email": "adzbite@gmail.com",
      "role": "Developer"
    }
  ],
  "require": {
    "php": ">=7.4.0",
    "ext-json": "*"
  },
  "require-dev": {
    "phpunit/phpunit": "^9.0|^10.0|^11.0",
    "squizlabs/php_codesniffer": "^3.7",
    "phpstan/phpstan": "^1.8"
  },
  "autoload": {
    "psr-4": {
      "AdzWP\\": "src/"
    },
    "files": [
      "src/helpers/functions.php"
    ]
  },
  "autoload-dev": {
    "psr-4": {
      "AdzWP\\Tests\\": "tests/"
    }
  },
  "config": {
    "optimize-autoloader": true,
    "sort-packages": true
  },
  "minimum-stability": "stable",
  "prefer-stable": true
}
```

**README.md** for framework:
```markdown
# ADZ WordPress Framework

Advanced WordPress plugin development framework with MVC architecture.

## Installation

```bash
composer require adz/wp-framework
```

## Usage

```php
<?php
use AdzWP\Controller;

class MyController extends Controller {
    public $actions = [
        'init' => 'initialize'
    ];
    
    public function initialize() {
        // Your plugin logic
    }
}
```

See [documentation](docs/) for complete guide.
```

### 1.3 Publish Framework Package

#### Option A: Packagist (Public, Recommended)
1. Push to GitHub: `https://github.com/adz/wp-framework`
2. Submit to Packagist: https://packagist.org/packages/submit
3. Framework becomes available via `composer require adz/wp-framework`

#### Option B: Private Repository
1. Push to private GitHub/GitLab repo
2. Use in projects with repository configuration

## Step 2: Update Plugin Template

### 2.1 Update Main composer.json
Replace current composer.json with:
```json
{
  "name": "adz/wp-plugin-template",
  "description": "WordPress Plugin Development Template using ADZ Framework",
  "type": "wordpress-plugin",
  "license": "MIT",
  "require": {
    "php": ">=7.4",
    "adz/wp-framework": "^3.0"
  },
  "require-dev": {
    "phpunit/phpunit": "^9.0|^10.0|^11.0",
    "brain/monkey": "^2.6",
    "mockery/mockery": "^1.4"
  },
  "autoload": {
    "psr-4": {
      "YourPlugin\\": "src/"
    }
  },
  "repositories": [
    {
      "type": "vcs", 
      "url": "https://github.com/adz/wp-framework"
    }
  ]
}
```

### 2.2 Remove Framework Files
```bash
# Remove framework files from plugin template
rm -rf adz/
```

### 2.3 Update Plugin Template Structure
```
wp-plugin-template/
├── src/                      # Plugin-specific code
│   ├── Controllers/
│   │   └── ExampleController.php
│   ├── Models/
│   │   └── ExampleModel.php
│   └── Views/
│       └── example.php
├── vendor/                   # Composer dependencies
│   └── adz/wp-framework/    # Framework installed here
├── tests/
├── composer.json
├── your-plugin.php
├── adz.sh                   # CLI tool
└── README.md
```

### 2.4 Update Plugin Bootstrap File
**your-plugin.php:**
```php
<?php
/**
 * Plugin Name: Your Plugin Name
 * Description: Plugin description
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Initialize plugin
use AdzWP\Core\Plugin;
use YourPlugin\Controllers\ExampleController;

$plugin = new Plugin(__FILE__);
$plugin->addController(ExampleController::class);
$plugin->boot();
```

## Step 3: Update CLI Tool (adz.sh)

### 3.1 Update Framework Detection
```bash
# In adz.sh, update framework path detection
FRAMEWORK_VENDOR_DIR="$FRAMEWORK_DIR/vendor/adz/wp-framework"

check_framework_installation() {
    if [[ ! -d "$FRAMEWORK_VENDOR_DIR" ]]; then
        log_error "ADZ Framework not found. Run 'composer install' first."
        exit 1
    fi
}
```

### 3.2 Update Template Paths
```bash
# Update template generation to use proper namespaces
make_controller() {
    local name="$1"
    local template="<?php

namespace YourPlugin\Controllers;

use AdzWP\Controller;

class ${name} extends Controller 
{
    public \$actions = [
        // 'init' => 'initialize',
    ];
    
    protected function bootstrap()
    {
        // Initialization code here
    }
}
"
    echo "$template" > "src/Controllers/${name}.php"
    log_success "Controller created: src/Controllers/${name}.php"
}
```

## Step 4: Documentation Updates

### 4.1 Update Getting Started Guide
```markdown
# Getting Started

## Installation

1. Create new plugin project:
```bash
git clone https://github.com/adz/wp-plugin-template my-plugin
cd my-plugin
```

2. Install framework and dependencies:
```bash
composer install
```

3. Generate your first controller:
```bash
./adz.sh make:controller HomeController
```

The framework is now installed in `vendor/adz/wp-framework/`.
```

## Step 5: Testing the Migration

### 5.1 Test Installation
```bash
# Remove old framework
rm -rf adz/

# Install via Composer
composer install

# Verify framework is in vendor/
ls -la vendor/adz/wp-framework/
```

### 5.2 Test Framework Usage
```php
<?php
// Test autoloading works
use AdzWP\Controller;
use AdzWP\Model;

// Should work without errors
class TestController extends Controller {}
```

### 5.3 Test CLI Tool
```bash
# Test CLI still works
./adz.sh make:controller TestController
./adz.sh build
```

## Benefits After Migration

✅ **Professional Structure**: Follows PHP/Composer conventions  
✅ **Clean Separation**: Framework vs plugin code separated  
✅ **Easy Distribution**: Share framework with other developers  
✅ **Version Management**: Framework versions independent of plugins  
✅ **Standard Installation**: `composer require adz/wp-framework`  
✅ **Better Testing**: Framework and plugin tested separately  

## Timeline

- **Phase 1** (1-2 hours): Create framework package repository
- **Phase 2** (30 minutes): Update plugin template  
- **Phase 3** (15 minutes): Update CLI tools
- **Phase 4** (30 minutes): Update documentation
- **Phase 5** (15 minutes): Test and verify

Total estimated time: ~3 hours for complete migration.