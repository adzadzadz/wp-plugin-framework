# Auto-Hook Registration Guide

The ADZ Framework automatically registers WordPress hooks based on your controller method names. No more manual `add_action()` or `add_filter()` calls!

## 🎯 **How It Works**

When you extend the `Controller` class, methods are automatically scanned and registered as WordPress hooks based on their naming convention:

- **`action*`** methods → WordPress actions
- **`filter*`** methods → WordPress filters

## 📝 **Basic Examples**

### Actions
```php
<?php
namespace App\Controllers;

use AdzWP\Core\Controller;

class ExampleController extends Controller
{
    // ✅ Becomes: add_action('wp_init', [$this, 'actionWpInit'], 10, 0)
    public function actionWpInit()
    {
        // Your initialization code here
    }

    // ✅ Becomes: add_action('admin_menu', [$this, 'actionAdminMenu'], 10, 0)
    public function actionAdminMenu()
    {
        add_menu_page('My Plugin', 'My Plugin', 'manage_options', 'my-plugin', [$this, 'adminPage']);
    }

    // ✅ Becomes: add_action('wp_enqueue_scripts', [$this, 'actionWpEnqueueScripts'], 10, 0)
    public function actionWpEnqueueScripts()
    {
        wp_enqueue_script('my-plugin-js', plugin_dir_url(__FILE__) . 'script.js');
    }

    // ✅ Becomes: add_action('wp_ajax_my_custom_action', [$this, 'actionWpAjaxMyCustomAction'], 10, 0)
    public function actionWpAjaxMyCustomAction()
    {
        wp_send_json_success(['message' => 'AJAX response']);
    }
}
```

### Filters
```php
<?php
class ExampleController extends Controller
{
    // ✅ Becomes: add_filter('the_title', [$this, 'filterTheTitle'], 10, 2)
    public function filterTheTitle($title, $post_id)
    {
        return $title . ' (Modified)';
    }

    // ✅ Becomes: add_filter('the_content', [$this, 'filterTheContent'], 10, 1)
    public function filterTheContent($content)
    {
        return '<div class="my-wrapper">' . $content . '</div>';
    }

    // ✅ Becomes: add_filter('wp_mail', [$this, 'filterWpMail'], 10, 1)
    public function filterWpMail($args)
    {
        $args['headers'][] = 'X-Mailer: My Plugin';
        return $args;
    }
}
```

## ⚙️ **Advanced Configuration**

### Custom Priority
Use `@priority` annotation to set custom hook priorities:

```php
/**
 * @priority 5
 */
public function actionWpInit()
{
    // This runs with priority 5 instead of default 10
}

/**
 * @priority 20
 */
public function filterTheTitle($title, $post_id)
{
    // This filter runs with priority 20
    return $title;
}
```

### Custom Argument Count
Use `@args` annotation to specify how many arguments the hook passes:

```php
/**
 * @args 3
 */
public function filterWpMail($args, $atts, $phpmailer)
{
    // Explicitly handle 3 arguments
    return $args;
}

/**
 * @priority 5
 * @args 2
 */
public function actionSavePost($post_id, $post)
{
    // Custom priority AND argument count
}
```

## 🔄 **Method Name Conversion**

CamelCase method names are automatically converted to WordPress hook format:

| Method Name | WordPress Hook |
|-------------|----------------|
| `actionWpInit` | `wp_init` |
| `actionAdminMenu` | `admin_menu` |
| `actionWpEnqueueScripts` | `wp_enqueue_scripts` |
| `filterTheTitle` | `the_title` |
| `filterTheContent` | `the_content` |
| `actionWpAjaxMyCustomAction` | `wp_ajax_my_custom_action` |

## 🚫 **What Doesn't Get Registered**

These method types are **ignored** by auto-registration:
- Magic methods (`__construct`, `__destruct`, etc.)
- Methods not starting with `action` or `filter`
- Methods with names too short (`action`, `filter`)
- Private/protected methods

```php
class ExampleController extends Controller
{
    // ❌ Not registered - doesn't start with action/filter
    public function regularMethod() { }

    // ❌ Not registered - too short
    public function action() { }

    // ❌ Not registered - private method
    private function actionPrivate() { }

    // ✅ This WILL be registered
    public function actionWpInit() { }
}
```

## 🔧 **Backwards Compatibility**

The old `$actions` and `$filters` array approach still works:

```php
class MixedController extends Controller
{
    // Old way still works
    public $actions = [
        'init' => 'oldStyleInit'
    ];

    // New auto-registration also works
    public function actionWpInit() { }

    public function oldStyleInit() {
        // This still gets registered via $actions array
    }
}
```

## 💡 **Best Practices**

1. **Use descriptive method names** that match WordPress hook names
2. **Add docblock annotations** for custom priority/args when needed
3. **Keep method names consistent** with WordPress conventions
4. **Group related functionality** in the same controller
5. **Use parameter type hints** for better IDE support

```php
/**
 * Handle user registration
 * @priority 5
 * @args 1
 */
public function actionUserRegister(int $user_id): void
{
    // Type hints improve IDE experience
    $user = get_userdata($user_id);
    // ... your logic
}
```

## 🚀 **Quick Start**

1. **Extend Controller**:
   ```php
   use AdzWP\Core\Controller;
   class MyController extends Controller { }
   ```

2. **Add hook methods**:
   ```php
   public function actionWpInit() { /* your code */ }
   public function filterTheTitle($title, $id) { return $title; }
   ```

3. **Instantiate**:
   ```php
   new MyController(); // Hooks registered automatically!
   ```

That's it! No manual hook registration needed. The framework handles everything automatically based on your method names.