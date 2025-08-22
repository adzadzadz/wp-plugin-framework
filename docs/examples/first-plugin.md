# Building Your First Plugin with ADZ Framework

This tutorial will guide you through creating a complete WordPress plugin using the ADZ Framework. We'll build a simple "Staff Directory" plugin that demonstrates the core framework features.

## What We'll Build

A staff directory plugin that:
- Adds a custom post type for staff members
- Includes custom fields for contact information
- Displays staff in a directory page
- Has an admin settings page
- Automatically installs Advanced Custom Fields as a dependency

## Step 1: Project Setup

### Create Plugin Structure

```
staff-directory/
├── staff-directory.php          # Main plugin file
├── composer.json               # Dependencies
├── src/
│   └── Controllers/
│       ├── StaffController.php
│       └── AdminController.php
├── assets/
│   ├── css/
│   │   └── staff-directory.css
│   └── js/
│       └── staff-directory.js
└── views/
    ├── admin/
    │   └── settings.php
    └── frontend/
        └── staff-directory.php
```

### Composer Configuration

Create `composer.json`:

```json
{
    "name": "yourname/staff-directory",
    "description": "A staff directory plugin built with ADZ Framework",
    "type": "wordpress-plugin",
    "require": {
        "adzadzadz/wp-plugin-framework": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "StaffDirectory\\": "src/"
        }
    }
}
```

Run `composer install` to install dependencies.

## Step 2: Main Plugin File

Create `staff-directory.php`:

```php
<?php
/**
 * Plugin Name: Staff Directory
 * Plugin URI: https://example.com/staff-directory
 * Description: A professional staff directory plugin built with ADZ Framework
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * Text Domain: staff-directory
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('STAFF_DIRECTORY_VERSION', '1.0.0');
define('STAFF_DIRECTORY_PATH', plugin_dir_path(__FILE__));
define('STAFF_DIRECTORY_URL', plugin_dir_url(__FILE__));
define('STAFF_DIRECTORY_BASENAME', plugin_basename(__FILE__));

// Load Composer autoloader
require_once STAFF_DIRECTORY_PATH . 'vendor/autoload.php';

// Initialize the framework
$framework = \Adz::config();
$framework->set('plugin.path', STAFF_DIRECTORY_PATH);
$framework->set('plugin.url', STAFF_DIRECTORY_URL);
$framework->set('plugin.version', STAFF_DIRECTORY_VERSION);
$framework->set('plugin.basename', STAFF_DIRECTORY_BASENAME);

// Initialize plugin manager
$pluginManager = \AdzWP\Core\PluginManager::getInstance(__FILE__);

// Set up dependencies
$pluginManager->setDependencies([
    [
        'slug' => 'advanced-custom-fields/acf.php',
        'name' => 'Advanced Custom Fields',
        'source' => 'repo'
    ]
]);

// Set up plugin lifecycle
$pluginManager
    ->onActivate(function() {
        // Create database tables if needed
        createStaffDirectoryTables();
        
        // Set default options
        add_option('staff_directory_per_page', 12);
        add_option('staff_directory_show_email', true);
        add_option('staff_directory_show_phone', true);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    })
    ->onDeactivate(function() {
        // Flush rewrite rules
        flush_rewrite_rules();
    })
    ->onUninstall(function() {
        // Remove options
        delete_option('staff_directory_per_page');
        delete_option('staff_directory_show_email');
        delete_option('staff_directory_show_phone');
        
        // Remove custom posts (optional)
        $staff_posts = get_posts([
            'post_type' => 'staff_member',
            'numberposts' => -1,
            'post_status' => 'any'
        ]);
        
        foreach ($staff_posts as $post) {
            wp_delete_post($post->ID, true);
        }
    })
    ->setupOptions([
        'staff_directory_per_page' => 12,
        'staff_directory_show_email' => true,
        'staff_directory_show_phone' => true
    ])
    ->setupCapabilities([
        'manage_staff_directory'
    ]);

// Helper function for database setup
function createStaffDirectoryTables() {
    // In this example, we're using custom post types
    // so no additional tables are needed
}

// Initialize controllers
new StaffDirectory\Controllers\StaffController();
new StaffDirectory\Controllers\AdminController();
```

## Step 3: Staff Controller

Create `src/Controllers/StaffController.php`:

```php
<?php

namespace StaffDirectory\Controllers;

use AdzWP\Core\Controller;

class StaffController extends Controller
{
    public $actions = [
        'init' => 'initialize',
        'wp_enqueue_scripts' => 'enqueueAssets',
        'acf/init' => 'setupCustomFields'
    ];

    public $filters = [
        'manage_staff_member_posts_columns' => 'addCustomColumns',
        'manage_staff_member_posts_custom_column' => 'renderCustomColumns'
    ];

    public function initialize()
    {
        $this->registerPostType();
        $this->registerShortcode();
        
        // Add meta boxes if ACF is not available
        if (!function_exists('acf_add_local_field_group')) {
            add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
            add_action('save_post', [$this, 'saveMetaData']);
        }
    }

    public function registerPostType()
    {
        register_post_type('staff_member', [
            'labels' => [
                'name' => __('Staff Members', 'staff-directory'),
                'singular_name' => __('Staff Member', 'staff-directory'),
                'add_new_item' => __('Add New Staff Member', 'staff-directory'),
                'edit_item' => __('Edit Staff Member', 'staff-directory'),
                'view_item' => __('View Staff Member', 'staff-directory'),
                'search_items' => __('Search Staff Members', 'staff-directory'),
                'not_found' => __('No staff members found', 'staff-directory'),
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
            'menu_icon' => 'dashicons-groups',
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'staff'],
            'capability_type' => 'post',
            'menu_position' => 20
        ]);
    }

    public function registerShortcode()
    {
        add_shortcode('staff_directory', [$this, 'renderStaffDirectory']);
    }

    public function enqueueAssets()
    {
        wp_enqueue_style(
            'staff-directory-css',
            STAFF_DIRECTORY_URL . 'assets/css/staff-directory.css',
            [],
            STAFF_DIRECTORY_VERSION
        );

        wp_enqueue_script(
            'staff-directory-js',
            STAFF_DIRECTORY_URL . 'assets/js/staff-directory.js',
            ['jquery'],
            STAFF_DIRECTORY_VERSION,
            true
        );
    }

    public function setupCustomFields()
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_staff_details',
            'title' => 'Staff Details',
            'fields' => [
                [
                    'key' => 'field_staff_position',
                    'label' => 'Position',
                    'name' => 'staff_position',
                    'type' => 'text',
                    'required' => 1,
                ],
                [
                    'key' => 'field_staff_department',
                    'label' => 'Department',
                    'name' => 'staff_department',
                    'type' => 'text',
                ],
                [
                    'key' => 'field_staff_email',
                    'label' => 'Email',
                    'name' => 'staff_email',
                    'type' => 'email',
                ],
                [
                    'key' => 'field_staff_phone',
                    'label' => 'Phone',
                    'name' => 'staff_phone',
                    'type' => 'text',
                ],
                [
                    'key' => 'field_staff_bio',
                    'label' => 'Biography',
                    'name' => 'staff_bio',
                    'type' => 'textarea',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'staff_member',
                    ],
                ],
            ],
        ]);
    }

    public function addCustomColumns($columns)
    {
        $new_columns = [];
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['staff_position'] = __('Position', 'staff-directory');
        $new_columns['staff_department'] = __('Department', 'staff-directory');
        $new_columns['staff_email'] = __('Email', 'staff-directory');
        $new_columns['date'] = $columns['date'];

        return $new_columns;
    }

    public function renderCustomColumns($column, $post_id)
    {
        switch ($column) {
            case 'staff_position':
                echo esc_html(get_post_meta($post_id, 'staff_position', true));
                break;
            case 'staff_department':
                echo esc_html(get_post_meta($post_id, 'staff_department', true));
                break;
            case 'staff_email':
                $email = get_post_meta($post_id, 'staff_email', true);
                if ($email) {
                    echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
                }
                break;
        }
    }

    public function renderStaffDirectory($atts)
    {
        $atts = shortcode_atts([
            'per_page' => get_option('staff_directory_per_page', 12),
            'department' => '',
            'show_email' => get_option('staff_directory_show_email', true),
            'show_phone' => get_option('staff_directory_show_phone', true),
        ], $atts);

        $args = [
            'post_type' => 'staff_member',
            'posts_per_page' => intval($atts['per_page']),
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ];

        if (!empty($atts['department'])) {
            $args['meta_query'] = [
                [
                    'key' => 'staff_department',
                    'value' => $atts['department'],
                    'compare' => '='
                ]
            ];
        }

        $staff_query = new \WP_Query($args);

        ob_start();
        
        if ($staff_query->have_posts()) {
            echo '<div class="staff-directory">';
            
            while ($staff_query->have_posts()) {
                $staff_query->the_post();
                $this->renderStaffCard($atts);
            }
            
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p>' . __('No staff members found.', 'staff-directory') . '</p>';
        }

        return ob_get_clean();
    }

    private function renderStaffCard($atts)
    {
        $post_id = get_the_ID();
        $position = get_post_meta($post_id, 'staff_position', true);
        $department = get_post_meta($post_id, 'staff_department', true);
        $email = get_post_meta($post_id, 'staff_email', true);
        $phone = get_post_meta($post_id, 'staff_phone', true);
        $bio = get_post_meta($post_id, 'staff_bio', true);

        echo '<div class="staff-card">';
        
        if (has_post_thumbnail()) {
            echo '<div class="staff-photo">';
            the_post_thumbnail('medium');
            echo '</div>';
        }
        
        echo '<div class="staff-info">';
        echo '<h3 class="staff-name">' . get_the_title() . '</h3>';
        
        if ($position) {
            echo '<p class="staff-position">' . esc_html($position) . '</p>';
        }
        
        if ($department) {
            echo '<p class="staff-department">' . esc_html($department) . '</p>';
        }
        
        if ($email && $atts['show_email']) {
            echo '<p class="staff-email"><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></p>';
        }
        
        if ($phone && $atts['show_phone']) {
            echo '<p class="staff-phone">' . esc_html($phone) . '</p>';
        }
        
        if ($bio) {
            echo '<div class="staff-bio">' . wpautop(esc_html($bio)) . '</div>';
        }
        
        echo '</div></div>';
    }

    // Fallback meta boxes if ACF is not available
    public function addMetaBoxes()
    {
        add_meta_box(
            'staff_details',
            __('Staff Details', 'staff-directory'),
            [$this, 'renderMetaBox'],
            'staff_member',
            'normal',
            'high'
        );
    }

    public function renderMetaBox($post)
    {
        wp_nonce_field('save_staff_meta', 'staff_meta_nonce');
        
        $position = get_post_meta($post->ID, 'staff_position', true);
        $department = get_post_meta($post->ID, 'staff_department', true);
        $email = get_post_meta($post->ID, 'staff_email', true);
        $phone = get_post_meta($post->ID, 'staff_phone', true);
        $bio = get_post_meta($post->ID, 'staff_bio', true);

        echo '<table class="form-table">';
        echo '<tr><th><label for="staff_position">' . __('Position', 'staff-directory') . '</label></th>';
        echo '<td><input type="text" id="staff_position" name="staff_position" value="' . esc_attr($position) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th><label for="staff_department">' . __('Department', 'staff-directory') . '</label></th>';
        echo '<td><input type="text" id="staff_department" name="staff_department" value="' . esc_attr($department) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th><label for="staff_email">' . __('Email', 'staff-directory') . '</label></th>';
        echo '<td><input type="email" id="staff_email" name="staff_email" value="' . esc_attr($email) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th><label for="staff_phone">' . __('Phone', 'staff-directory') . '</label></th>';
        echo '<td><input type="text" id="staff_phone" name="staff_phone" value="' . esc_attr($phone) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th><label for="staff_bio">' . __('Biography', 'staff-directory') . '</label></th>';
        echo '<td><textarea id="staff_bio" name="staff_bio" rows="4" class="large-text">' . esc_textarea($bio) . '</textarea></td></tr>';
        echo '</table>';
    }

    public function saveMetaData($post_id)
    {
        if (!isset($_POST['staff_meta_nonce']) || 
            !wp_verify_nonce($_POST['staff_meta_nonce'], 'save_staff_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = ['staff_position', 'staff_department', 'staff_email', 'staff_phone', 'staff_bio'];
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
}
```

## Step 4: Admin Controller

Create `src/Controllers/AdminController.php`:

```php
<?php

namespace StaffDirectory\Controllers;

use AdzWP\Core\Controller;

class AdminController extends Controller
{
    public $actions = [
        'admin_menu' => 'addAdminMenu',
        'admin_init' => 'registerSettings'
    ];

    public function addAdminMenu()
    {
        add_submenu_page(
            'edit.php?post_type=staff_member',
            __('Staff Directory Settings', 'staff-directory'),
            __('Settings', 'staff-directory'),
            'manage_options',
            'staff-directory-settings',
            [$this, 'renderSettingsPage']
        );
    }

    public function registerSettings()
    {
        register_setting('staff_directory_settings', 'staff_directory_per_page', [
            'type' => 'integer',
            'default' => 12,
            'sanitize_callback' => 'absint'
        ]);

        register_setting('staff_directory_settings', 'staff_directory_show_email', [
            'type' => 'boolean',
            'default' => true
        ]);

        register_setting('staff_directory_settings', 'staff_directory_show_phone', [
            'type' => 'boolean', 
            'default' => true
        ]);

        add_settings_section(
            'display_settings',
            __('Display Settings', 'staff-directory'),
            [$this, 'renderDisplaySection'],
            'staff-directory-settings'
        );

        add_settings_field(
            'per_page',
            __('Staff Members Per Page', 'staff-directory'),
            [$this, 'renderPerPageField'],
            'staff-directory-settings',
            'display_settings'
        );

        add_settings_field(
            'show_email',
            __('Show Email Addresses', 'staff-directory'),
            [$this, 'renderShowEmailField'],
            'staff-directory-settings',
            'display_settings'
        );

        add_settings_field(
            'show_phone',
            __('Show Phone Numbers', 'staff-directory'),
            [$this, 'renderShowPhoneField'],
            'staff-directory-settings',
            'display_settings'
        );
    }

    public function renderSettingsPage()
    {
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'staff_directory_messages',
                'staff_directory_message',
                __('Settings saved successfully.', 'staff-directory'),
                'updated'
            );
        }

        settings_errors('staff_directory_messages');

        echo '<div class="wrap">';
        echo '<h1>' . get_admin_page_title() . '</h1>';
        echo '<form method="post" action="options.php">';
        
        settings_fields('staff_directory_settings');
        do_settings_sections('staff-directory-settings');
        submit_button();
        
        echo '</form>';
        echo '<hr>';
        echo '<h2>' . __('Usage', 'staff-directory') . '</h2>';
        echo '<p>' . __('Use the shortcode <code>[staff_directory]</code> to display the staff directory on any page or post.', 'staff-directory') . '</p>';
        echo '<h3>' . __('Shortcode Parameters', 'staff-directory') . '</h3>';
        echo '<ul>';
        echo '<li><code>per_page</code> - Number of staff members to show (default: ' . get_option('staff_directory_per_page', 12) . ')</li>';
        echo '<li><code>department</code> - Filter by department</li>';
        echo '<li><code>show_email</code> - Show email addresses (true/false)</li>';
        echo '<li><code>show_phone</code> - Show phone numbers (true/false)</li>';
        echo '</ul>';
        echo '<p><strong>' . __('Example:', 'staff-directory') . '</strong> <code>[staff_directory per_page="8" department="Marketing" show_email="false"]</code></p>';
        echo '</div>';
    }

    public function renderDisplaySection()
    {
        echo '<p>' . __('Configure how the staff directory is displayed on your website.', 'staff-directory') . '</p>';
    }

    public function renderPerPageField()
    {
        $value = get_option('staff_directory_per_page', 12);
        echo '<input type="number" name="staff_directory_per_page" value="' . esc_attr($value) . '" min="1" max="100" />';
        echo '<p class="description">' . __('Number of staff members to show per page in the directory.', 'staff-directory') . '</p>';
    }

    public function renderShowEmailField()
    {
        $value = get_option('staff_directory_show_email', true);
        echo '<label><input type="checkbox" name="staff_directory_show_email" value="1" ' . checked(1, $value, false) . ' /> ';
        echo __('Display email addresses in the staff directory', 'staff-directory') . '</label>';
    }

    public function renderShowPhoneField()
    {
        $value = get_option('staff_directory_show_phone', true);
        echo '<label><input type="checkbox" name="staff_directory_show_phone" value="1" ' . checked(1, $value, false) . ' /> ';
        echo __('Display phone numbers in the staff directory', 'staff-directory') . '</label>';
    }
}
```

## Step 5: Add Styles

Create `assets/css/staff-directory.css`:

```css
.staff-directory {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.staff-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease;
}

.staff-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.staff-photo img {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 15px;
}

.staff-name {
    margin: 0 0 10px 0;
    font-size: 1.2em;
    color: #333;
}

.staff-position {
    font-weight: bold;
    color: #666;
    margin: 5px 0;
}

.staff-department {
    color: #888;
    font-style: italic;
    margin: 5px 0;
}

.staff-email a,
.staff-phone {
    color: #0073aa;
    text-decoration: none;
    margin: 5px 0;
}

.staff-email a:hover {
    text-decoration: underline;
}

.staff-bio {
    margin-top: 15px;
    text-align: left;
    color: #555;
    line-height: 1.6;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .staff-directory {
        grid-template-columns: 1fr;
    }
    
    .staff-card {
        margin: 10px 0;
    }
}
```

## Step 6: Testing Your Plugin

1. **Activate the Plugin**: Go to your WordPress admin and activate the "Staff Directory" plugin.

2. **Check Dependencies**: The plugin should automatically install Advanced Custom Fields if it's not already installed.

3. **Add Staff Members**: 
   - Go to "Staff Members" in your admin menu
   - Add a few staff members with photos and details

4. **Display the Directory**:
   - Create a new page
   - Add the shortcode `[staff_directory]`
   - View the page to see your staff directory

5. **Configure Settings**:
   - Go to "Staff Members" > "Settings"
   - Adjust display options as needed

## Next Steps

This basic plugin demonstrates:
- ✅ Plugin lifecycle management
- ✅ Automatic dependency installation  
- ✅ Custom post types and fields
- ✅ Admin settings pages
- ✅ Shortcode functionality
- ✅ Responsive design

### Enhancements You Could Add

1. **Search and Filtering**: Add search functionality and department filters
2. **Email Integration**: Add contact forms for each staff member
3. **Import/Export**: Bulk import staff from CSV files
4. **Advanced Layouts**: Multiple display layouts and grid options
5. **Performance**: Add caching for large staff directories

### Framework Features Used

- `PluginManager` for lifecycle management
- `Controller` base class with automatic hook registration
- `Dependency` management for ACF installation
- Configuration management with `\Adz::config()`
- Security best practices with nonces and sanitization

This example shows how the ADZ Framework simplifies WordPress plugin development while maintaining professional standards and best practices.