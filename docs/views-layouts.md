# Views & Layouts

The ADZ Framework provides a powerful view system with layout support and Bootstrap 5 integration for building modern WordPress plugin interfaces.

## Overview

The view system consists of two main components:

- **Views** - Content templates that contain your actual page content
- **Layouts** - Wrapper templates that provide structure and common elements

## Quick Start

### Basic View Rendering

```php
// Render a view with the default layout
echo View::render('dashboard', [
    'title' => 'Dashboard',
    'data' => $dashboardData
]);
```

### Disable Layout

```php
// Render view without any layout wrapper
echo View::render('dashboard', $data, true, false);
```

### Custom Layout

```php
// Use a specific layout
echo View::render('dashboard', $data, true, 'layouts/admin');
```

## Directory Structure

```
src/views/
├── layouts/           # Layout templates
│   ├── main.php      # Default layout
│   ├── admin.php     # Admin layout
│   └── simple.php    # Simple layout
├── dashboard/
│   ├── index.php     # Dashboard view
│   └── settings.php  # Settings view
└── partials/          # Partial templates
    ├── header.php    
    └── footer.php    
```

## Creating Views

### Using CLI

```bash
# Create a new view
adz make:view dashboard/settings

# Create a nested view
adz make:view admin/users/index

# Create a partial
adz make:view partials/navigation
```

### Manual Creation

Create a PHP file in `src/views/`:

```php
<?php
// src/views/dashboard.php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="dashboard-content">
    <h1><?php echo esc_html($title); ?></h1>
    
    <?php if (isset($stats)): ?>
        <div class="row">
            <?php foreach ($stats as $stat): ?>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 class="text-primary"><?php echo esc_html($stat['value']); ?></h2>
                            <p class="text-muted"><?php echo esc_html($stat['label']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
```

## Creating Layouts

### Using CLI

```bash
# Create a new layout
adz make:layout admin

# Create a specialized layout
adz make:layout dashboard
```

### Layout Structure

Layouts wrap your view content and provide common structure:

```php
<?php
// src/views/layouts/admin.php

// Get plugin configuration
$config = \AdzWP\Core\Config::getInstance();
$plugin_slug = $config->get('plugin.slug', 'adz-plugin');
?>

<section id="<?php echo esc_attr($plugin_slug . '-admin'); ?>" class="adz-template adz-template--admin">
    <?php if (isset($title)): ?>
        <header class="template-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="template-title h4 mb-0"><?php echo esc_html($title); ?></h2>
                
                <?php if (isset($breadcrumbs)): ?>
                    <!-- Breadcrumb navigation -->
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <?php foreach ($breadcrumbs as $crumb): ?>
                                <li class="breadcrumb-item">
                                    <?php if (isset($crumb['url'])): ?>
                                        <a href="<?php echo esc_url($crumb['url']); ?>">
                                            <?php echo esc_html($crumb['label']); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo esc_html($crumb['label']); ?>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                <?php endif; ?>
            </div>
        </header>
    <?php endif; ?>
    
    <main class="template-content">
        <?php echo $content; ?>
    </main>
    
    <?php if (isset($actions)): ?>
        <footer class="template-actions mt-4">
            <div class="d-flex gap-2">
                <?php foreach ($actions as $action): ?>
                    <a href="<?php echo esc_url($action['url']); ?>" 
                       class="btn <?php echo esc_attr($action['class'] ?? 'btn-secondary'); ?>">
                        <?php echo esc_html($action['label']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </footer>
    <?php endif; ?>
</section>
```

## Bootstrap 5 Integration

The framework automatically includes Bootstrap 5 in admin and plugin contexts.

### Available Classes

```php
<!-- Cards -->
<div class="card">
    <div class="card-body">
        <h5 class="card-title">Title</h5>
        <p class="card-text">Content</p>
    </div>
</div>

<!-- Alerts -->
<div class="alert alert-success alert-dismissible fade show">
    Success message
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

<!-- Forms -->
<form class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" class="form-control">
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-primary">Submit</button>
    </div>
</form>

<!-- Grid System -->
<div class="row">
    <div class="col-lg-8">Main content</div>
    <div class="col-lg-4">Sidebar</div>
</div>
```

## Advanced Features

### Template Variables

Common variables available in layouts:

```php
// Layout-specific variables
$title          // Page title
$subtitle       // Page subtitle  
$content        // Rendered view content
$breadcrumbs    // Navigation breadcrumbs
$alerts         // Alert messages
$actions        // Action buttons
$sidebar_content // Sidebar content

// Example usage in controller
View::render('dashboard', [
    'title' => 'Dashboard',
    'subtitle' => 'Welcome back!',
    'breadcrumbs' => [
        ['label' => 'Home', 'url' => admin_url()],
        ['label' => 'Dashboard']
    ],
    'alerts' => [
        [
            'type' => 'success', 
            'message' => 'Settings saved!',
            'dismissible' => true
        ]
    ],
    'actions' => [
        [
            'label' => 'Settings',
            'url' => admin_url('admin.php?page=settings'),
            'class' => 'btn-primary'
        ]
    ]
]);
```

### Partial Templates

Include reusable template parts:

```php
// In your view or layout
<?php View::include('partials/header', ['title' => 'My Header']); ?>
```

### Template Paths

The framework searches for templates in this order:

1. `src/views/` (plugin views)
2. `wp-content/themes/your-theme/adz-templates/` (theme overrides)
3. `wp-content/themes/parent-theme/adz-templates/` (parent theme overrides)

### Security

Always escape output in templates:

```php
<!-- Text content -->
<?php echo esc_html($user_input); ?>

<!-- HTML attributes -->
<div class="<?php echo esc_attr($css_class); ?>">

<!-- URLs -->
<a href="<?php echo esc_url($link); ?>">

<!-- HTML content (when needed) -->
<?php echo wp_kses_post($html_content); ?>
```

## Examples

### Admin Settings Page

```php
// Controller
public function renderSettingsPage()
{
    $data = [
        'title' => 'Plugin Settings',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => admin_url()],
            ['label' => 'Settings']
        ],
        'form_fields' => [
            [
                'name' => 'api_key',
                'label' => 'API Key',
                'type' => 'text',
                'required' => true,
                'value' => get_option('my_api_key', '')
            ]
        ]
    ];
    
    echo View::render('admin/settings', $data, true, 'layouts/admin');
}
```

### Dashboard Widget

```php
// Controller  
public function renderDashboard()
{
    $stats = [
        ['label' => 'Total Users', 'value' => 150],
        ['label' => 'Active Sessions', 'value' => 23],
        ['label' => 'Revenue', 'value' => '$1,234']
    ];
    
    echo View::render('dashboard/widget', [
        'title' => 'Dashboard Overview',
        'stats' => $stats
    ], true, false); // No layout for widgets
}
```

## Best Practices

1. **Separate Logic from Presentation** - Keep business logic in controllers
2. **Use Semantic HTML** - Leverage Bootstrap classes appropriately  
3. **Escape All Output** - Always sanitize user data
4. **Consistent Naming** - Use clear, descriptive file names
5. **Responsive Design** - Use Bootstrap's responsive utilities
6. **Accessibility** - Include proper ARIA labels and semantic markup