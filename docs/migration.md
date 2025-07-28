# Migration Guide: From Traditional WordPress Plugins

This guide helps you migrate existing WordPress plugins to use the ADZ Plugin Framework. The migration can be done gradually, allowing you to modernize your plugin piece by piece while maintaining functionality.

## Migration Overview

The migration process involves:
1. **Structure Reorganization** - Moving code into the framework structure
2. **Hook Conversion** - Converting manual hooks to declarative arrays
3. **Security Implementation** - Adding validation, sanitization, and CSRF protection
4. **Error Handling** - Implementing proper logging and exception handling
5. **Configuration System** - Moving hardcoded values to configuration
6. **Database Modernization** - Using the query builder and migrations

## Before You Start

### Backup Everything
```bash
# Create complete backup
cp -r /path/to/your-plugin /path/to/your-plugin-backup

# Database backup
mysqldump -u username -p database_name > plugin_backup.sql
```

### Analyze Your Current Plugin
```bash
# Count lines of code
find . -name "*.php" | xargs wc -l

# List all hooks
grep -r "add_action\|add_filter" . --include="*.php"

# Find hardcoded values
grep -r "wp_options\|get_option\|update_option" . --include="*.php"
```

## Step-by-Step Migration

### Step 1: Install Framework

Install the framework alongside your existing plugin:

```bash
# In your plugin directory
composer require adz/wp-plugin-framework

# Or clone framework
git clone https://github.com/your-repo/wp-plugin-framework.git framework
```

Update your main plugin file:

```php
<?php
/**
 * Plugin Name: Your Plugin Name
 * Description: Your plugin description
 * Version: 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('YOUR_PLUGIN_VERSION', '2.0.0');
define('YOUR_PLUGIN_FILE', __FILE__);
define('YOUR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('YOUR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load framework
require_once __DIR__ . '/vendor/autoload.php';

// Load legacy code (temporary)
require_once __DIR__ . '/legacy/functions.php';

// Initialize plugin
new YourPlugin\Plugin();
```

### Step 2: Create Framework Structure

Create the new directory structure:

```bash
mkdir -p src/{controllers,models,views,services}
mkdir -p config
mkdir -p assets/{css,js,images}
mkdir -p docs
mkdir -p tests
mkdir -p legacy  # Temporary for old files
```

Move existing files to legacy folder:
```bash
mv *.php legacy/  # Except main plugin file
mv js legacy/
mv css legacy/
mv images legacy/
```

### Step 3: Migrate Configuration

#### Before (hardcoded values):
```php
// Old way - scattered throughout code
add_menu_page('My Plugin', 'My Plugin', 'manage_options', 'my-plugin', ...);
$api_url = 'https://api.example.com';
$cache_time = 3600;
```

#### After (configuration system):

Create `config/app.php`:
```php
<?php

return [
    'plugin' => [
        'name' => 'Your Plugin Name',
        'version' => '2.0.0',
        'text_domain' => 'your-plugin',
        'slug' => 'your-plugin'
    ],
    
    'admin' => [
        'menu_title' => 'My Plugin',
        'menu_slug' => 'my-plugin',
        'capability' => 'manage_options'
    ],
    
    'api' => [
        'base_url' => 'https://api.example.com',
        'timeout' => 30,
        'cache_time' => 3600
    ]
];
```

### Step 4: Convert Functions to Controllers

#### Before (procedural functions):
```php
// legacy/functions.php
function my_plugin_admin_menu() {
    add_menu_page('My Plugin', 'My Plugin', 'manage_options', 'my-plugin', 'my_plugin_admin_page');
}
add_action('admin_menu', 'my_plugin_admin_menu');

function my_plugin_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Access denied');
    }
    
    if ($_POST['submit']) {
        // Process form without validation
        update_option('my_plugin_setting', $_POST['setting']);
    }
    
    echo '<div class="wrap">';
    echo '<h1>My Plugin Settings</h1>';
    // More HTML output
    echo '</div>';
}

function my_plugin_enqueue_scripts() {
    wp_enqueue_script('my-plugin-js', plugin_dir_url(__FILE__) . 'js/script.js');
}
add_action('wp_enqueue_scripts', 'my_plugin_enqueue_scripts');
```

#### After (framework controller):

Create `src/controllers/AdminController.php`:
```php
<?php

namespace YourPlugin\Controllers;

use AdzWP\Controller;
use AdzHive\Security;
use AdzHive\Validator;
use AdzHive\ValidationException;
use AdzHive\Config;

class AdminController extends Controller 
{
    protected $security;
    protected $config;
    
    public $actions = [
        'admin_menu' => 'addAdminMenu',
        'admin_init' => 'initializeAdmin',
        'admin_enqueue_scripts' => 'enqueueAdminAssets',
        'admin_post_save_settings' => 'saveSettings'
    ];
    
    protected function bootstrap()
    {
        $this->security = Security::getInstance();
        $this->config = Config::getInstance();
    }
    
    public function addAdminMenu()
    {
        add_menu_page(
            $this->config->get('plugin.name'),
            $this->config->get('admin.menu_title'),
            $this->config->get('admin.capability'),
            $this->config->get('admin.menu_slug'),
            [$this, 'renderAdminPage']
        );
    }
    
    public function renderAdminPage()
    {
        try {
            $this->security->checkCapability($this->config->get('admin.capability'));
            
            $data = [
                'settings' => get_option('my_plugin_settings', []),
                'nonce' => $this->security->createNonce('save_settings')
            ];
            
            include plugin_dir_path(__FILE__) . '../views/admin/settings-page.php';
            
        } catch (Exception $e) {
            wp_die($e->getMessage());
        }
    }
    
    public function saveSettings()
    {
        try {
            // Security checks
            $this->security->checkCapability($this->config->get('admin.capability'));
            $this->security->verifyRequest('_settings_nonce', 'save_settings');
            
            // Validate input
            $validator = Validator::make($_POST, [
                'api_key' => 'required|string|min:10',
                'cache_time' => 'numeric|between:300,7200',
                'enable_feature' => 'boolean'
            ]);
            
            if ($validator->fails()) {
                throw new ValidationException('Invalid settings', $validator->errors());
            }
            
            // Sanitize data
            $settings = $this->security->sanitizeArray($_POST, [
                'api_key' => 'text',
                'cache_time' => 'int',
                'enable_feature' => 'bool'
            ]);
            
            // Save settings
            update_option('my_plugin_settings', $settings);
            
            // Log success
            adz_log_info('Settings saved', ['user_id' => get_current_user_id()]);
            
            // Redirect with success message
            wp_redirect(add_query_arg('message', 'settings_saved', 
                admin_url('admin.php?page=' . $this->config->get('admin.menu_slug'))));
            exit;
            
        } catch (ValidationException $e) {
            $errors = implode('<br>', array_flatten($e->getErrors()));
            wp_redirect(add_query_arg('error', urlencode($errors), wp_get_referer()));
            exit;
            
        } catch (Exception $e) {
            adz_log_error('Settings save failed', ['error' => $e->getMessage()]);
            wp_redirect(add_query_arg('error', 'save_failed', wp_get_referer()));
            exit;
        }
    }
    
    public function enqueueAdminAssets($hook)
    {
        if (strpos($hook, $this->config->get('admin.menu_slug')) !== false) {
            wp_enqueue_script(
                'my-plugin-admin',
                YOUR_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery'],
                YOUR_PLUGIN_VERSION,
                true
            );
            
            wp_localize_script('my-plugin-admin', 'myPluginAdmin', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('admin_ajax')
            ]);
        }
    }
}
```

### Step 5: Create Views

#### Before (mixed HTML and PHP):
```php
function my_plugin_admin_page() {
    echo '<div class="wrap">';
    echo '<h1>Settings</h1>';
    echo '<form method="post">';
    echo '<input name="setting" value="' . get_option('my_plugin_setting') . '">';
    echo '<input type="submit" name="submit" value="Save">';
    echo '</form>';
    echo '</div>';
}
```

#### After (separate view file):

Create `src/views/admin/settings-page.php`:
```php
<div class="wrap">
    <h1><?php echo esc_html($this->config->get('plugin.name')); ?> Settings</h1>
    
    <?php if (isset($_GET['message']) && $_GET['message'] === 'settings_saved'): ?>
        <div class="notice notice-success">
            <p>Settings saved successfully!</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="notice notice-error">
            <p><?php echo esc_html(urldecode($_GET['error'])); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php echo wp_nonce_field('save_settings', '_settings_nonce'); ?>
        <input type="hidden" name="action" value="save_settings">
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="api_key">API Key</label>
                </th>
                <td>
                    <input 
                        type="text" 
                        id="api_key" 
                        name="api_key" 
                        value="<?php echo esc_attr($settings['api_key'] ?? ''); ?>"
                        class="regular-text"
                        required
                    >
                    <p class="description">Enter your API key from the service provider.</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="cache_time">Cache Time (seconds)</label>
                </th>
                <td>
                    <input 
                        type="number" 
                        id="cache_time" 
                        name="cache_time" 
                        value="<?php echo esc_attr($settings['cache_time'] ?? 3600); ?>"
                        min="300"
                        max="7200"
                    >
                </td>
            </tr>
            
            <tr>
                <th scope="row">Enable Feature</th>
                <td>
                    <label>
                        <input 
                            type="checkbox" 
                            name="enable_feature" 
                            value="1"
                            <?php checked(!empty($settings['enable_feature'])); ?>
                        >
                        Enable advanced features
                    </label>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>
```

### Step 6: Migrate Database Operations

#### Before (direct SQL):
```php
function get_my_plugin_data($user_id) {
    global $wpdb;
    
    $results = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}my_plugin_data WHERE user_id = $user_id"
    ); // SQL injection vulnerability!
    
    return $results;
}
```

#### After (query builder):

Create `src/models/DataModel.php`:
```php
<?php

namespace YourPlugin\Models;

use AdzHive\Database;

class DataModel
{
    protected $db;
    protected $table = 'my_plugin_data';
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    public function findByUser($userId)
    {
        return $this->db->table($this->table)
            ->where('user_id', $userId)
            ->where('active', 1)
            ->orderBy('created_at', 'DESC')
            ->get();
    }
    
    public function create($data)
    {
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');
        
        return $this->db->table($this->table)->insert($data);
    }
    
    public function update($id, $data)
    {
        $data['updated_at'] = current_time('mysql');
        
        return $this->db->table($this->table)
            ->where('id', $id)
            ->update($data);
    }
    
    public function delete($id)
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->delete();
    }
}
```

### Step 7: Add Database Migrations

Create migration file using CLI:
```bash
./adz.sh make:migration create_my_plugin_data_table
```

Edit the migration file:
```php
<?php
// database/migrations/2023_12_01_000000_create_my_plugin_data_table.php

use AdzHive\Database;

$db = Database::getInstance();

$db->createTable('my_plugin_data', [
    'columns' => [
        'id' => [
            'type' => 'bigint',
            'length' => 20,
            'null' => false,
            'auto_increment' => true
        ],
        'user_id' => [
            'type' => 'bigint',
            'length' => 20,
            'null' => false
        ],
        'data_key' => [
            'type' => 'varchar',
            'length' => 255,
            'null' => false
        ],
        'data_value' => [
            'type' => 'longtext',
            'null' => true
        ],
        'active' => [
            'type' => 'tinyint',
            'length' => 1,
            'null' => false,
            'default' => '1'
        ],
        'created_at' => [
            'type' => 'datetime',
            'null' => false,
            'default' => 'CURRENT_TIMESTAMP'
        ],
        'updated_at' => [
            'type' => 'datetime',
            'null' => false,
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ]
    ],
    'indexes' => [
        'PRIMARY' => [
            'type' => 'PRIMARY',
            'columns' => 'id'
        ],
        'user_id_idx' => [
            'type' => 'KEY',
            'columns' => 'user_id'
        ],
        'data_key_idx' => [
            'type' => 'KEY',
            'columns' => 'data_key'
        ]
    ]
]);
```

Run the migration:
```bash
./adz.sh db:migrate
```

### Step 8: Update Main Plugin Class

Create `src/Plugin.php`:
```php
<?php

namespace YourPlugin;

use AdzHive\Config;
use YourPlugin\Controllers\AdminController;
use YourPlugin\Controllers\FrontendController;

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
        // Initialize controllers
        foreach ($this->controllers as $controller) {
            if (method_exists($controller, 'init')) {
                $controller->init();
            }
        }
        
        // Plugin lifecycle hooks
        register_activation_hook(YOUR_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(YOUR_PLUGIN_FILE, [$this, 'deactivate']);
        
        // Load text domain
        add_action('plugins_loaded', [$this, 'loadTextDomain']);
    }
    
    public function activate()
    {
        // Run migrations
        $this->runMigrations();
        
        // Set default options
        $this->setDefaultOptions();
        
        // Flush rewrite rules if needed
        flush_rewrite_rules();
    }
    
    public function deactivate()
    {
        // Clear caches
        wp_cache_flush();
        
        // Clear scheduled events
        wp_clear_scheduled_hook('my_plugin_cron_hook');
    }
    
    public function loadTextDomain()
    {
        load_plugin_textdomain(
            $this->config->get('plugin.text_domain'),
            false,
            dirname(plugin_basename(YOUR_PLUGIN_FILE)) . '/languages'
        );
    }
    
    protected function runMigrations()
    {
        // Simple migration runner
        $migrationsPath = YOUR_PLUGIN_PATH . 'database/migrations/';
        
        if (is_dir($migrationsPath)) {
            $files = glob($migrationsPath . '*.php');
            sort($files);
            
            foreach ($files as $file) {
                include_once $file;
            }
        }
    }
    
    protected function setDefaultOptions()
    {
        $defaults = [
            'cache_time' => 3600,
            'enable_feature' => false
        ];
        
        add_option('my_plugin_settings', $defaults);
    }
}
```

## Migration Checklist

### Phase 1: Foundation
- [ ] Install framework
- [ ] Create directory structure
- [ ] Move old files to legacy folder
- [ ] Create configuration files
- [ ] Update main plugin file

### Phase 2: Core Migration
- [ ] Convert functions to controller methods
- [ ] Implement hook arrays
- [ ] Create view templates
- [ ] Add security validation
- [ ] Implement error handling

### Phase 3: Database & Models
- [ ] Create database models
- [ ] Write migration files
- [ ] Convert direct SQL to query builder
- [ ] Add proper indexing
- [ ] Test database operations

### Phase 4: Security & Validation
- [ ] Add CSRF protection to forms
- [ ] Implement input validation
- [ ] Add data sanitization
- [ ] Implement rate limiting
- [ ] Add permission checks

### Phase 5: Polish & Testing
- [ ] Add logging throughout
- [ ] Create proper error messages
- [ ] Test all functionality
- [ ] Update documentation
- [ ] Remove legacy code

### Phase 6: Deployment
- [ ] Test on staging environment
- [ ] Backup production
- [ ] Deploy gradually
- [ ] Monitor for issues
- [ ] Update users

## Common Migration Challenges

### Challenge 1: Global Variables and Functions

**Problem**: Legacy code uses global variables and functions.

**Solution**: Encapsulate in classes and use dependency injection.

Before:
```php
global $my_plugin_data;
$my_plugin_data = get_plugin_data();

function process_data() {
    global $my_plugin_data;
    // Process data
}
```

After:
```php
class DataService {
    protected $data;
    
    public function __construct() {
        $this->data = $this->getPluginData();
    }
    
    public function processData() {
        // Process $this->data
    }
}
```

### Challenge 2: Mixed HTML and Logic

**Problem**: HTML output mixed with business logic.

**Solution**: Separate into controllers and views.

Before:
```php
function display_form() {
    if ($_POST['submit']) {
        // Process form
        echo '<div>Success!</div>';
    }
    echo '<form>...</form>';
}
```

After:
```php
// Controller handles logic
public function handleForm() {
    if ($_POST['submit']) {
        // Process form
        return $this->renderView('form-success');
    }
    return $this->renderView('form');
}

// View handles display
// views/form.php
<form>...</form>
```

### Challenge 3: Hardcoded Values

**Problem**: Configuration scattered throughout code.

**Solution**: Centralize in configuration system.

Before:
```php
wp_enqueue_script('script', plugins_url('js/script.js', __FILE__));
add_menu_page('My Plugin', 'My Plugin', 'manage_options', ...);
```

After:
```php
// config/app.php
return [
    'assets' => [
        'script_url' => 'js/script.js'
    ],
    'admin' => [
        'menu_title' => 'My Plugin',
        'capability' => 'manage_options'
    ]
];

// In controller
wp_enqueue_script('script', 
    plugins_url($this->config->get('assets.script_url'), YOUR_PLUGIN_FILE));
```

## Testing Your Migration

### Functionality Testing
1. **Feature parity**: Ensure all original features work
2. **Data integrity**: Verify no data loss during migration
3. **Performance**: Check for performance regressions
4. **Security**: Test security improvements are working

### Regression Testing
1. **User workflows**: Test all user-facing functionality
2. **Admin operations**: Test all admin functions
3. **API endpoints**: Test any REST API endpoints
4. **Third-party integrations**: Test plugin/theme compatibility

### Security Testing
1. **CSRF protection**: Test nonce verification
2. **Input validation**: Test with invalid data
3. **Permission checks**: Test unauthorized access
4. **SQL injection**: Test database operations

## Post-Migration Cleanup

### Remove Legacy Code
```bash
# After thorough testing, remove legacy files
rm -rf legacy/

# Update version number
# Update changelog
# Update documentation
```

### Optimize Performance
```bash
# Clear any caches
./adz.sh cache:clear

# Run performance tests
# Optimize slow queries
# Add caching where needed
```

### Update Documentation
- Update user documentation
- Create migration notes for users
- Document new features
- Update developer documentation

## Rollback Plan

Always have a rollback plan:

1. **Database backup**: Keep pre-migration database backup
2. **File backup**: Keep complete file backup
3. **Version control**: Tag pre-migration state
4. **Monitoring**: Monitor for issues post-deployment
5. **Rollback procedure**: Document exact rollback steps

Migration is a significant undertaking, but the benefits of modernized, secure, and maintainable code make it worthwhile. Take your time, test thoroughly, and migrate in phases to minimize risk.