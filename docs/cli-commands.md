# CLI Commands

The ADZ Framework includes a comprehensive command-line interface for scaffolding, asset management, and development tasks.

## Installation

The CLI tool is automatically available after framework installation:

```bash
# Via Composer installation
./adz command

# Or if framework is installed as dependency
./vendor/bin/adz command
```

## Available Commands

### 🚀 Project Setup

#### `adz init`
Initialize a new ADZ project in the current directory.

```bash
adz init
```

**What it does:**
- Creates directory structure (`src/`, `config/`, `assets/`, etc.)
- Copies template files (composer.json, package.json, phpunit.xml)
- Sets up example controllers, models, and views
- Creates build tools and configuration files
- Generates .gitignore and README

**Directory structure created:**
```
your-plugin/
├── src/
│   ├── controllers/
│   ├── services/
│   ├── models/
│   └── views/
│       └── layouts/
├── config/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── migrations/
├── tests/
└── tools/
```

### 📦 Code Generation

#### `adz make:controller <name>`
Create a new controller with automatic hook registration.

```bash
# Basic controller
adz make:controller PostController

# Namespaced controller
adz make:controller Admin/UserController

# API controller
adz make:controller Api/V1/AuthController
```

**Generated features:**
- WordPress action and filter hook arrays
- Automatic hook registration
- Context-aware initialization methods
- AJAX handling examples
- Admin menu integration
- Comprehensive documentation

#### `adz make:service <name>`
Create a new service class for business logic.

```bash
adz make:service EmailService
adz make:service User/AuthService
```

**Generated features:**
- Dependency injection support
- Service registration methods
- Validation helpers
- Error handling patterns

#### `adz make:model <name>`
Create a new model with optional database migration.

```bash
# Basic model
adz make:model User

# Model with migration
adz make:model Post --migration

# Complex model
adz make:model ProductCategory --migration
```

**Generated features:**
- Eloquent-style query methods
- Relationship definitions
- Validation rules
- Mass assignment protection
- Accessor and mutator examples

#### `adz make:view <name>`
Create a new view template.

```bash
# Basic view
adz make:view dashboard

# Nested view
adz make:view admin/users/index

# Partial template
adz make:view partials/header
```

**Generated features:**
- Security (output escaping)
- Bootstrap 5 components
- Form handling
- Responsive design
- WordPress integration

#### `adz make:layout <name>`
Create a new layout template.

```bash
# Admin layout
adz make:layout admin

# Dashboard layout
adz make:layout dashboard

# Simple layout
adz make:layout minimal
```

**Generated features:**
- Bootstrap 5 structure
- Breadcrumb navigation
- Alert system
- Action buttons
- Responsive containers
- Plugin context integration

#### `adz make:migration <name>`
Create a new database migration.

```bash
adz make:migration create_users_table
adz make:migration add_email_to_posts
adz make:migration update_user_permissions
```

**Generated features:**
- Up and down methods
- Table creation/modification
- Index management
- Foreign key constraints

### 🗄️ Database Management

#### `adz migrate`
Run all pending database migrations.

```bash
adz migrate
```

#### `adz migrate:rollback [steps]`
Rollback database migrations.

```bash
# Rollback last migration
adz migrate:rollback

# Rollback 3 steps
adz migrate:rollback 3
```

#### `adz migrate:reset`
Reset all migrations (rollback everything).

```bash
adz migrate:reset
```

#### `adz migrate:status`
Show migration status.

```bash
adz migrate:status
```

### 🎨 Asset Management

#### `adz assets:enable`
Enable Bootstrap 5 assets.

```bash
adz assets:enable
```

#### `adz assets:disable`
Disable Bootstrap 5 assets.

```bash
adz assets:disable
```

#### `adz assets:list`
List all registered assets.

```bash
adz assets:list
```

### 🧹 Maintenance

#### `adz cache:clear`
Clear framework cache (transients and object cache).

```bash
adz cache:clear
```

#### `adz logs:clear`
Clear application log files.

```bash
adz logs:clear
```

#### `adz config:cache`
Cache configuration files for performance.

```bash
adz config:cache
```

#### `adz config:clear`
Clear configuration cache.

```bash
adz config:clear
```

### 🔧 Framework Commands

#### `adz test`
Run all framework tests.

```bash
adz test
```

#### `adz test:unit`
Run unit tests only.

```bash
adz test:unit
```

#### `adz test:coverage`
Run tests with coverage report.

```bash
adz test:coverage
```

#### `adz health:check`
Run system health diagnostics.

```bash
adz health:check
```

**Checks:**
- Database connectivity
- File permissions
- Required PHP extensions
- WordPress compatibility
- Framework configuration

### 📦 Build & Distribution

#### `adz build`
Build and minify assets, create installable zip.

```bash
adz build
```

**Process:**
- Compiles and minifies CSS/JS
- Optimizes images
- Creates installable plugin zip
- Excludes development files

#### `adz build:assets`
Build and minify assets only.

```bash
adz build:assets
```

#### `adz build:watch`
Watch assets for changes and auto-build.

```bash
adz build:watch
```

#### `adz build:zip`
Create installable plugin zip package.

```bash
adz build:zip
```

## Usage Examples

### Complete Project Setup

```bash
# 1. Initialize project
adz init

# 2. Install dependencies
composer install
npm install

# 3. Create your components
adz make:controller MainController
adz make:service DataService
adz make:model User --migration
adz make:view dashboard/index
adz make:layout admin

# 4. Run migrations
adz migrate

# 5. Build assets
adz build:assets
```

### Development Workflow

```bash
# Create new feature
adz make:controller FeatureController
adz make:view features/index
adz make:service FeatureService

# Add database support
adz make:migration create_features_table
adz migrate

# Test changes
adz test
adz health:check

# Build for production
adz build
```

### View System Examples

```bash
# Create admin interface
adz make:layout admin
adz make:view admin/dashboard
adz make:view admin/settings
adz make:view partials/navigation

# Create public interface
adz make:layout public
adz make:view public/form
adz make:view public/results
```

## Configuration

### Custom Commands

You can extend the CLI by adding custom commands in your plugin:

```php
// In your plugin initialization
add_action('adz_cli_register_commands', function($cli) {
    $cli->registerCommand('my:command', function($args) {
        echo "Custom command executed!\n";
    });
});
```

### Environment Detection

Commands automatically detect the environment:

- **Development**: Uses unminified assets, enables debug mode
- **Production**: Uses minified assets, optimizes for performance
- **Testing**: Uses test configuration and database

## Troubleshooting

### Command Not Found

```bash
# Check if ADZ is properly installed
composer show adz/wp-plugin-framework

# Check if binary is executable
chmod +x ./adz

# Try full path
./vendor/bin/adz --help
```

### Permission Issues

```bash
# Fix permissions
chmod -R 755 src/
chmod -R 755 assets/

# Check WordPress file permissions
adz health:check
```

### Build Failures

```bash
# Check Node.js installation
node --version
npm --version

# Reinstall dependencies
rm -rf node_modules/
npm install

# Check build tools
ls -la tools/
```

## Integration with IDEs

### VS Code

Add to `.vscode/tasks.json`:

```json
{
    "version": "2.0.0",
    "tasks": [
        {
            "label": "ADZ: Build Assets",
            "type": "shell",
            "command": "./adz",
            "args": ["build:assets"],
            "group": "build"
        },
        {
            "label": "ADZ: Run Tests",
            "type": "shell", 
            "command": "./adz",
            "args": ["test"],
            "group": "test"
        }
    ]
}
```

### PHPStorm

Configure as External Tool:
- Program: `./adz`
- Arguments: `$Prompt$`
- Working directory: `$ProjectFileDir$`

## Best Practices

1. **Use Descriptive Names** - Clear controller and model names
2. **Follow Conventions** - Use PSR-4 namespacing
3. **Test Early** - Run tests after each major change
4. **Version Control** - Commit generated files appropriately  
5. **Documentation** - Update docs when adding custom commands
6. **Performance** - Use build commands for production deployments