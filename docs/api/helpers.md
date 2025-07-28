# Helper Functions

The ADZ Plugin Framework provides numerous helper functions to simplify common tasks. These functions are available globally once the framework is loaded.

## Logging Helpers

### adz_log($message, $level, $context)

Generic logging function that accepts any log level.

```php
adz_log('User performed action', 'info', ['user_id' => 123]);
adz_log('Database error occurred', 'error', ['query' => $sql]);
```

**Parameters:**
- `$message` (string) - The log message
- `$level` (string) - Log level (emergency, alert, critical, error, warning, notice, info, debug)
- `$context` (array) - Additional context data

### adz_log_emergency($message, $context)

Log emergency-level messages (system is unusable).

```php
adz_log_emergency('Database server is down', ['server' => 'main-db']);
```

### adz_log_alert($message, $context)

Log alert-level messages (immediate action required).

```php
adz_log_alert('Disk space critically low', ['available' => '100MB']);
```

### adz_log_critical($message, $context)

Log critical-level messages (critical conditions).

```php
adz_log_critical('Payment gateway connection failed', [
    'gateway' => 'stripe',
    'error_code' => 'connection_timeout'
]);
```

### adz_log_error($message, $context)

Log error-level messages (error conditions).

```php
adz_log_error('Failed to send email', [
    'recipient' => 'user@example.com',
    'error' => 'SMTP authentication failed'
]);
```

### adz_log_warning($message, $context)

Log warning-level messages (warning conditions).

```php
adz_log_warning('API rate limit approaching', [
    'current_usage' => 950,
    'limit' => 1000
]);
```

### adz_log_notice($message, $context)

Log notice-level messages (normal but significant conditions).

```php
adz_log_notice('User registered', [
    'user_id' => 456,
    'registration_source' => 'contact_form'
]);
```

### adz_log_info($message, $context)

Log info-level messages (informational messages).

```php
adz_log_info('Cache cleared successfully', ['cache_type' => 'page_cache']);
```

### adz_log_debug($message, $context)

Log debug-level messages (debug information).

```php
adz_log_debug('Processing user data', [
    'user_id' => 123,
    'step' => 'validation',
    'data_size' => count($userData)
]);
```

## Exception Handling

### adz_handle_exception($exception)

Handle exceptions with proper logging and user-friendly error display.

```php
try {
    // Risky operation
    $result = $api->makeRequest();
} catch (Exception $e) {
    adz_handle_exception($e);
}
```

**Features:**
- Automatically logs the exception with full context
- Shows user-friendly error messages in production
- Shows detailed debug information in development mode
- Integrates with WordPress error handling

**Parameters:**
- `$exception` (Throwable) - The exception to handle

## Security Helpers

### adz_verify_nonce($nonce, $action)

Verify a WordPress nonce for security.

```php
if (adz_verify_nonce($_POST['_nonce'], 'my_action')) {
    // Process secure request
}
```

### adz_sanitize($input, $type)

Sanitize input data based on type.

```php
$clean_email = adz_sanitize($_POST['email'], 'email');
$clean_text = adz_sanitize($_POST['name'], 'text');
$clean_html = adz_sanitize($_POST['content'], 'html');
```

**Supported Types:**
- `text` - Basic text sanitization
- `textarea` - Textarea content
- `email` - Email address
- `url` - URL
- `int` - Integer
- `float` - Float/decimal
- `bool` - Boolean
- `html` - Safe HTML
- `key` - WordPress key format
- `filename` - Safe filename

### adz_escape($output, $context)

Escape output for safe display.

```php
echo adz_escape($user_input, 'html');
echo '<a href="' . adz_escape($url, 'url') . '">';
echo '<div class="' . adz_escape($class, 'attr') . '">';
```

**Contexts:**
- `html` - HTML content
- `attr` - HTML attributes
- `url` - URLs
- `js` - JavaScript
- `textarea` - Textarea content

## Database Helpers

### adz_db()

Get the database instance.

```php
$db = adz_db();
$users = $db->table('users')->where('active', 1)->get();
```

### adz_query($sql, $params)

Execute a raw database query safely.

```php
$results = adz_query(
    "SELECT * FROM {$wpdb->prefix}posts WHERE post_status = %s AND post_date > %s",
    ['published', '2023-01-01']
);
```

### adz_get_table_name($table)

Get full table name with WordPress and plugin prefixes.

```php
$tableName = adz_get_table_name('users'); // Returns: wp_adz_users
```

## Configuration Helpers

### adz_config($key, $default)

Get configuration value with dot notation.

```php
$api_key = adz_config('services.api.key', 'default_key');
$debug_mode = adz_config('app.debug', false);
```

### adz_set_config($key, $value)

Set configuration value at runtime.

```php
adz_set_config('cache.enabled', true);
adz_set_config('features.new_feature', ['enabled' => true, 'beta' => false]);
```

## Validation Helpers

### adz_validate($data, $rules, $messages)

Validate data with rules and custom messages.

```php
$result = adz_validate($_POST, [
    'email' => 'required|email',
    'name' => 'required|string|min:2'
], [
    'email.required' => 'Please enter your email',
    'name.min' => 'Name must be at least 2 characters'
]);

if ($result['valid']) {
    $cleanData = $result['data'];
} else {
    $errors = $result['errors'];
}
```

### adz_is_valid_email($email)

Check if email address is valid.

```php
if (adz_is_valid_email($email)) {
    // Process email
}
```

### adz_is_valid_url($url)

Check if URL is valid.

```php
if (adz_is_valid_url($website)) {
    // Process URL
}
```

## File Helpers

### adz_upload_file($file, $options)

Handle file uploads securely.

```php
$result = adz_upload_file($_FILES['upload'], [
    'allowed_types' => ['jpg', 'png', 'pdf'],
    'max_size' => 5 * 1024 * 1024, // 5MB
    'destination' => 'custom-uploads'
]);

if ($result['success']) {
    $filePath = $result['file_path'];
    $fileUrl = $result['file_url'];
}
```

### adz_delete_file($file_path)

Delete a file safely with logging.

```php
if (adz_delete_file($old_file_path)) {
    adz_log_info('File deleted successfully', ['file' => $old_file_path]);
}
```

### adz_get_file_info($file_path)

Get detailed file information.

```php
$info = adz_get_file_info($file_path);
// Returns: ['size' => 1024, 'type' => 'image/jpeg', 'modified' => '2023-01-01 12:00:00']
```

## Cache Helpers

### adz_cache_get($key, $default)

Get value from cache.

```php
$data = adz_cache_get('user_stats_' . $user_id, []);
```

### adz_cache_set($key, $value, $expiration)

Set value in cache.

```php
adz_cache_set('user_stats_' . $user_id, $stats, HOUR_IN_SECONDS);
```

### adz_cache_delete($key)

Delete value from cache.

```php
adz_cache_delete('user_stats_' . $user_id);
```

### adz_cache_flush($group)

Clear cache group or all cache.

```php
adz_cache_flush('user_data');  // Clear specific group
adz_cache_flush();             // Clear all plugin cache
```

## HTTP Helpers

### adz_http_get($url, $args)

Make HTTP GET request.

```php
$response = adz_http_get('https://api.example.com/users', [
    'headers' => ['Authorization' => 'Bearer ' . $token],
    'timeout' => 30
]);

if ($response['success']) {
    $data = $response['data'];
} else {
    $error = $response['error'];
}
```

### adz_http_post($url, $data, $args)

Make HTTP POST request.

```php
$response = adz_http_post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
], [
    'headers' => ['Content-Type' => 'application/json']
]);
```

### adz_http_request($method, $url, $data, $args)

Make HTTP request with any method.

```php
$response = adz_http_request('PUT', 'https://api.example.com/users/123', $userData);
$response = adz_http_request('DELETE', 'https://api.example.com/users/123');
```

## Date/Time Helpers

### adz_now($format)

Get current date/time in specified format.

```php
$now = adz_now();           // MySQL format: 2023-01-01 12:00:00
$today = adz_now('Y-m-d');  // Date only: 2023-01-01
$time = adz_now('H:i:s');   // Time only: 12:00:00
```

### adz_format_date($date, $format)

Format date with WordPress timezone.

```php
$formatted = adz_format_date('2023-01-01 12:00:00', 'F j, Y g:i A');
// Returns: January 1, 2023 12:00 PM
```

### adz_time_ago($date)

Get human-readable time difference.

```php
echo adz_time_ago('2023-01-01 12:00:00');
// Returns: "2 hours ago", "3 days ago", etc.
```

## Array/Object Helpers

### adz_array_get($array, $key, $default)

Get array value with dot notation.

```php
$value = adz_array_get($data, 'user.profile.name', 'Unknown');
$nested = adz_array_get($config, 'database.connections.mysql.host', 'localhost');
```

### adz_array_set($array, $key, $value)

Set array value with dot notation.

```php
adz_array_set($data, 'user.profile.name', 'John Doe');
adz_array_set($config, 'cache.enabled', true);
```

### adz_array_forget($array, $key)

Remove array value with dot notation.

```php
adz_array_forget($data, 'user.temporary_data');
```

### adz_array_only($array, $keys)

Get only specified keys from array.

```php
$filtered = adz_array_only($_POST, ['name', 'email', 'message']);
```

### adz_array_except($array, $keys)

Get array without specified keys.

```php
$filtered = adz_array_except($_POST, ['password', 'password_confirmation']);
```

## String Helpers

### adz_str_limit($string, $limit, $end)

Limit string length with optional ending.

```php
$excerpt = adz_str_limit($content, 150, '...');
```

### adz_str_slug($string, $separator)

Convert string to URL-friendly slug.

```php
$slug = adz_str_slug('My Blog Post Title'); // my-blog-post-title
$slug = adz_str_slug('Special Characters!', '_'); // special_characters
```

### adz_str_random($length)

Generate random string.

```php
$token = adz_str_random(32);  // Random 32-character string
$code = adz_str_random(6);    // Random 6-character string
```

### adz_str_contains($haystack, $needle)

Check if string contains substring.

```php
if (adz_str_contains($email, '@gmail.com')) {
    // Gmail address
}
```

### adz_str_starts_with($haystack, $needle)

Check if string starts with substring.

```php
if (adz_str_starts_with($url, 'https://')) {
    // Secure URL
}
```

### adz_str_ends_with($haystack, $needle)

Check if string ends with substring.

```php
if (adz_str_ends_with($filename, '.pdf')) {
    // PDF file
}
```

## WordPress Integration Helpers

### adz_is_admin()

Check if current request is admin area.

```php
if (adz_is_admin()) {
    // Admin-specific logic
}
```

### adz_is_ajax()

Check if current request is AJAX.

```php
if (adz_is_ajax()) {
    // AJAX-specific logic
}
```

### adz_current_user()

Get current user with additional data.

```php
$user = adz_current_user();
// Returns: ['id' => 123, 'name' => 'John', 'email' => 'john@example.com', 'roles' => ['subscriber']]
```

### adz_can($capability, $user_id)

Check if user has capability.

```php
if (adz_can('manage_options')) {
    // User is admin
}

if (adz_can('edit_posts', $user_id)) {
    // User can edit posts
}
```

### adz_redirect($url, $status_code)

Safe redirect with logging.

```php
adz_redirect('/thank-you/', 302);
adz_redirect('https://example.com/external', 301);
```

### adz_get_option($key, $default)

Get plugin option with namespacing.

```php
$setting = adz_get_option('api_key', '');
$config = adz_get_option('features', []);
```

### adz_set_option($key, $value)

Set plugin option with namespacing.

```php
adz_set_option('api_key', $new_key);
adz_set_option('last_sync', adz_now());
```

## Development Helpers

### adz_dump($data)

Dump data for debugging (only in debug mode).

```php
adz_dump($user_data);  // Pretty prints data if WP_DEBUG is true
```

### adz_dd($data)

Dump data and die (only in debug mode).

```php
adz_dd($complex_array);  // Dumps data and stops execution
```

### adz_benchmark($callback, $iterations)

Benchmark code execution.

```php
$time = adz_benchmark(function() use ($data) {
    return expensive_operation($data);
}, 100);

adz_log_debug('Operation took ' . $time . 'ms');
```

### adz_memory_usage()

Get current memory usage.

```php
$memory = adz_memory_usage();
adz_log_debug('Memory usage: ' . $memory['current'] . ' / ' . $memory['peak']);
```

## Usage Examples

### Complete Form Processing

```php
function handle_contact_form() {
    try {
        // Verify security
        if (!adz_verify_nonce($_POST['_nonce'], 'contact_form')) {
            throw new Exception('Security check failed');
        }
        
        // Validate input
        $result = adz_validate($_POST, [
            'name' => 'required|string|min:2',
            'email' => 'required|email',
            'message' => 'required|string|min:10'
        ]);
        
        if (!$result['valid']) {
            throw new ValidationException('Validation failed', $result['errors']);
        }
        
        // Sanitize data
        $data = [
            'name' => adz_sanitize($result['data']['name'], 'text'),
            'email' => adz_sanitize($result['data']['email'], 'email'),
            'message' => adz_sanitize($result['data']['message'], 'textarea')
        ];
        
        // Save to database
        $db = adz_db();
        $contact_id = $db->table('contacts')->insert(array_merge($data, [
            'created_at' => adz_now(),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ]));
        
        // Send notification email
        $email_sent = wp_mail(
            adz_config('contact.recipient'),
            'New Contact Form Submission',
            "Name: {$data['name']}\nEmail: {$data['email']}\nMessage: {$data['message']}"
        );
        
        // Log success
        adz_log_info('Contact form submitted', [
            'contact_id' => $contact_id,
            'email_sent' => $email_sent
        ]);
        
        // Redirect with success
        adz_redirect(add_query_arg('message', 'success', wp_get_referer()));
        
    } catch (Exception $e) {
        adz_handle_exception($e);
    }
}
```

### API Integration with Caching

```php
function get_external_data($endpoint, $params = []) {
    $cache_key = 'api_data_' . md5($endpoint . serialize($params));
    
    // Try cache first
    $cached = adz_cache_get($cache_key);
    if ($cached !== null) {
        return $cached;
    }
    
    // Make API request
    $url = adz_config('api.base_url') . '/' . $endpoint;
    $response = adz_http_get($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . adz_config('api.token')
        ],
        'body' => $params,
        'timeout' => 30
    ]);
    
    if (!$response['success']) {
        adz_log_error('API request failed', [
            'endpoint' => $endpoint,
            'error' => $response['error']
        ]);
        return null;
    }
    
    // Cache for 1 hour
    adz_cache_set($cache_key, $response['data'], HOUR_IN_SECONDS);
    
    return $response['data'];
}
```

These helper functions provide a consistent, secure, and convenient way to perform common operations throughout your plugin. They integrate seamlessly with the framework's security, logging, and configuration systems.