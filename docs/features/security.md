# Security & Validation

The ADZ Plugin Framework provides comprehensive security features designed to protect your WordPress plugin from common vulnerabilities and attacks. The security system includes CSRF protection, input validation, sanitization, rate limiting, and more.

## Overview

Security features include:
- **CSRF Protection** - Prevent cross-site request forgery attacks
- **Input Validation** - Validate user input with 20+ rules
- **Data Sanitization** - Clean data before processing
- **Rate Limiting** - Prevent abuse and brute force attacks
- **Permission Checking** - WordPress capability integration
- **IP Detection** - Client identification and tracking

## CSRF Protection

### Basic Usage

```php
use AdzHive\Security;

$security = Security::getInstance();

// Generate nonce for forms
$nonce = $security->createNonce('my_form_action');

// In your form template
echo $security->getNonceField('my_form_action', '_my_nonce');

// Verify nonce on form submission
try {
    $security->verifyRequest('_my_nonce', 'my_form_action');
    // Process form data
} catch (UnauthorizedException $e) {
    // Handle security failure
    wp_die('Security check failed');
}
```

### AJAX Protection

```php
// In your controller
public $actions = [
    'wp_ajax_my_action' => 'handleAjaxRequest',
    'wp_ajax_nopriv_my_action' => 'handleAjaxRequest'
];

public function handleAjaxRequest()
{
    try {
        $security = Security::getInstance();
        $security->verifyAjaxRequest('_ajax_nonce', 'my_ajax_action');
        
        // Process AJAX request
        wp_send_json_success(['message' => 'Success']);
        
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
```

### Custom Nonce Actions

```php
// Create nonces for specific actions
$deleteNonce = $security->createNonce('delete_post_' . $postId);
$editNonce = $security->createNonce('edit_user_' . $userId);

// Verify with context
$security->verifyNonce($deleteNonce, 'delete_post_' . $postId);
```

## Input Validation

### Basic Validation

```php
use AdzHive\Validator;
use AdzHive\ValidationException;

// Validate form data
$validator = Validator::make($_POST, [
    'email' => 'required|email',
    'name' => 'required|string|min:3|max:50',
    'age' => 'numeric|between:18,100',
    'website' => 'url',
    'password' => 'required|min:8|confirmed'
]);

if ($validator->fails()) {
    throw new ValidationException('Validation failed', $validator->errors());
}

// Get validated data
$validatedData = $validator->validated();
```

### Available Validation Rules

#### Basic Rules
```php
'field' => 'required'           // Field must be present and not empty
'field' => 'email'              // Valid email address
'field' => 'url'                // Valid URL
'field' => 'numeric'            // Numeric value
'field' => 'integer'            // Integer value
'field' => 'boolean'            // Boolean value
'field' => 'string'             // String value
'field' => 'array'              // Array value
```

#### Size Rules
```php
'field' => 'min:3'              // Minimum length/value
'field' => 'max:50'             // Maximum length/value  
'field' => 'between:10,100'     // Between two values
```

#### Content Rules
```php
'field' => 'alpha'              // Only letters
'field' => 'alpha_num'          // Letters and numbers
'field' => 'alpha_dash'         // Letters, numbers, dashes, underscores
'field' => 'regex:/^[A-Z]+$/'   // Custom regex pattern
```

#### Comparison Rules
```php
'password' => 'confirmed'       // Must match password_confirmation
'field1' => 'same:field2'       // Must match another field
'field1' => 'different:field2'  // Must be different from another field
```

#### Database Rules
```php
'email' => 'unique:users,email' // Must be unique in database table
'user_id' => 'exists:users,id'  // Must exist in database table
```

#### Date Rules
```php
'date' => 'date'                // Valid date
'date' => 'date:Y-m-d'         // Valid date in specific format
'start_date' => 'before:end_date' // Before another date
'end_date' => 'after:start_date'  // After another date
```

### Custom Validation Rules

```php
// Create custom validator
class CustomValidator extends Validator
{
    protected function validateWordPressUsername($field, $value, $parameters)
    {
        return username_exists($value) !== false;
    }
    
    protected function validateStrongPassword($field, $value, $parameters)
    {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $value);
    }
}

// Use custom rules
$validator = CustomValidator::make($_POST, [
    'username' => 'required|wordpress_username',
    'password' => 'required|strong_password'
]);
```

### Nested Validation

```php
// Validate nested arrays
$validator = Validator::make($_POST, [
    'user.name' => 'required|string',
    'user.email' => 'required|email',
    'user.profile.bio' => 'string|max:500',
    'tags.*' => 'string|max:20'  // Validate each array item
]);
```

### Custom Error Messages

```php
$validator = Validator::make($_POST, [
    'email' => 'required|email|unique:users,email'
], [
    'email.required' => 'Please provide your email address.',
    'email.email' => 'Please provide a valid email address.',
    'email.unique' => 'This email is already registered.'
]);
```

## Data Sanitization

### Basic Sanitization

```php
$security = Security::getInstance();

// Sanitize single values
$cleanText = $security->sanitize($_POST['text'], 'text');
$cleanEmail = $security->sanitize($_POST['email'], 'email');
$cleanUrl = $security->sanitize($_POST['url'], 'url');
$cleanHtml = $security->sanitize($_POST['content'], 'html');

// Sanitize arrays
$cleanData = $security->sanitizeArray($_POST, [
    'name' => 'text',
    'email' => 'email',
    'website' => 'url',
    'bio' => 'textarea',
    'age' => 'int',
    'active' => 'bool',
    'content' => 'html'
]);
```

### Sanitization Types

```php
// Text sanitization
'text'      // Basic text field
'textarea'  // Textarea content
'email'     // Email address
'url'       // URL
'key'       // WordPress key format
'filename'  // Safe filename

// Numeric sanitization
'int'       // Integer
'float'     // Float/decimal
'bool'      // Boolean

// HTML sanitization
'html'      // Safe HTML (wp_kses_post)
'raw'       // No sanitization (use carefully)

// Array sanitization
'array'     // Recursively sanitize array
```

### Custom Sanitization

```php
// Custom sanitization function
function sanitizePhoneNumber($phone) {
    // Remove all non-numeric characters
    $clean = preg_replace('/[^0-9]/', '', $phone);
    
    // Format as (XXX) XXX-XXXX
    if (strlen($clean) == 10) {
        return sprintf('(%s) %s-%s', 
            substr($clean, 0, 3),
            substr($clean, 3, 3),
            substr($clean, 6, 4)
        );
    }
    
    return $clean;
}

// Use in sanitization array
$cleanData = $security->sanitizeArray($_POST, [
    'phone' => function($value) { return sanitizePhoneNumber($value); }
]);
```

## Output Escaping

### Context-Aware Escaping

```php
$security = Security::getInstance();

// HTML content
echo $security->escape($userContent, 'html');

// HTML attributes
echo '<div class="' . $security->escape($userClass, 'attr') . '">';

// URLs
echo '<a href="' . $security->escape($userUrl, 'url') . '">';

// JavaScript
echo '<script>var data = "' . $security->escape($userData, 'js') . '";</script>';

// Textarea content
echo '<textarea>' . $security->escape($userText, 'textarea') . '</textarea>';
```

## Rate Limiting

### Basic Rate Limiting

```php
$security = Security::getInstance();

try {
    // Check rate limit: 5 attempts per 5 minutes
    $security->checkRateLimit('contact_form', 5, 300);
    
    // Process the request
    processContactForm();
    
} catch (ForbiddenException $e) {
    wp_die('Too many requests. Please try again later.');
}
```

### Custom Rate Limiting Rules

```php
// Different limits for different actions
$security->checkRateLimit('login_attempt', 3, 900);     // 3 per 15 minutes
$security->checkRateLimit('password_reset', 2, 3600);   // 2 per hour
$security->checkRateLimit('api_call', 100, 3600);       // 100 per hour
$security->checkRateLimit('file_upload', 10, 300);      // 10 per 5 minutes
```

### User-Specific Rate Limiting

```php
// Rate limit per user
$userId = get_current_user_id();
$security->checkRateLimit('user_action_' . $userId, 20, 3600);

// Rate limit per IP
$clientIp = $security->getClientIP();
$security->checkRateLimit('ip_action_' . $clientIp, 50, 3600);
```

## Permission Checking

### WordPress Capabilities

```php
$security = Security::getInstance();

try {
    // Check if user has capability
    $security->checkCapability('manage_options');
    
    // Check specific capability for specific user
    $security->checkCapability('edit_posts', $userId);
    
    // Proceed with privileged operation
    
} catch (ForbiddenException $e) {
    wp_die('You do not have permission to perform this action.');
}
```

### Custom Permission Checks

```php
// Check multiple capabilities
function checkEditorPermissions($userId = null) {
    $security = Security::getInstance();
    
    $security->checkCapability('edit_posts', $userId);
    $security->checkCapability('upload_files', $userId);
    
    return true;
}

// Role-based permissions
function checkAuthorPermissions($postId, $userId = null) {
    $userId = $userId ?: get_current_user_id();
    $post = get_post($postId);
    
    if ($post->post_author != $userId && !current_user_can('edit_others_posts')) {
        throw new ForbiddenException('You can only edit your own posts.');
    }
    
    return true;
}
```

## Complete Security Implementation

### Secure Form Processing Example

```php
<?php

namespace MyPlugin\Controllers;

use AdzWP\Controller;
use AdzHive\Security;
use AdzHive\Validator;
use AdzHive\ValidationException;

class ContactController extends Controller 
{
    public $actions = [
        'admin_post_contact_form' => 'handleContactForm',
        'admin_post_nopriv_contact_form' => 'handleContactForm'
    ];
    
    public function handleContactForm()
    {
        try {
            $security = Security::getInstance();
            
            // 1. Check rate limiting
            $security->checkRateLimit('contact_form', 5, 300);
            
            // 2. Verify CSRF token
            $security->verifyRequest('_contact_nonce', 'contact_form_submit');
            
            // 3. Validate input
            $validator = Validator::make($_POST, [
                'name' => 'required|string|min:2|max:50',
                'email' => 'required|email',
                'subject' => 'required|string|max:100',
                'message' => 'required|string|min:10|max:1000',
                'website' => 'url'  // Optional honeypot field
            ]);
            
            if ($validator->fails()) {
                throw new ValidationException('Please check your input', $validator->errors());
            }
            
            // 4. Sanitize data
            $data = $security->sanitizeArray($_POST, [
                'name' => 'text',
                'email' => 'email',
                'subject' => 'text',
                'message' => 'textarea',
                'website' => 'url'
            ]);
            
            // 5. Honeypot check (bot detection)
            if (!empty($data['website'])) {
                adz_log_warning('Bot detected on contact form', [
                    'ip' => $security->getClientIP(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                // Silently fail for bots
                wp_redirect(add_query_arg('status', 'success', wp_get_referer()));
                exit;
            }
            
            // 6. Process the form
            $this->sendContactEmail($data);
            
            // 7. Log successful submission
            adz_log_info('Contact form submitted successfully', [
                'name' => $data['name'],
                'email' => $data['email'],
                'ip' => $security->getClientIP()
            ]);
            
            // 8. Redirect with success message
            wp_redirect(add_query_arg('status', 'success', wp_get_referer()));
            exit;
            
        } catch (ValidationException $e) {
            $this->handleValidationError($e);
        } catch (ForbiddenException $e) {
            $this->handleRateLimitError($e);
        } catch (Exception $e) {
            $this->handleGeneralError($e);
        }
    }
    
    protected function sendContactEmail($data)
    {
        $to = get_option('admin_email');
        $subject = '[Contact Form] ' . $data['subject'];
        
        $message = sprintf(
            "Name: %s\nEmail: %s\n\nMessage:\n%s",
            $data['name'],
            $data['email'],
            $data['message']
        );
        
        $headers = [
            'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>',
            'Content-Type: text/plain; charset=UTF-8'
        ];
        
        wp_mail($to, $subject, $message, $headers);
    }
    
    protected function handleValidationError(ValidationException $e)
    {
        $errors = http_build_query(['errors' => $e->getErrors()]);
        wp_redirect(add_query_arg('status', 'validation_error', wp_get_referer()) . '&' . $errors);
        exit;
    }
    
    protected function handleRateLimitError(ForbiddenException $e)
    {
        wp_redirect(add_query_arg('status', 'rate_limit', wp_get_referer()));
        exit;
    }
    
    protected function handleGeneralError(Exception $e)
    {
        adz_log_error('Contact form error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        wp_redirect(add_query_arg('status', 'error', wp_get_referer()));
        exit;
    }
}
```

## Security Best Practices

### 1. Defense in Depth

Implement multiple security layers:

```php
public function processUserData()
{
    // Layer 1: Authentication
    if (!is_user_logged_in()) {
        throw new UnauthorizedException();
    }
    
    // Layer 2: Authorization
    $security->checkCapability('edit_posts');
    
    // Layer 3: CSRF Protection
    $security->verifyRequest();
    
    // Layer 4: Rate Limiting
    $security->checkRateLimit('user_action', 10, 300);
    
    // Layer 5: Input Validation
    $validator = Validator::make($_POST, $rules);
    
    // Layer 6: Data Sanitization
    $cleanData = $security->sanitizeArray($_POST, $sanitizationRules);
    
    // Process secure data
}
```

### 2. Fail Securely

Always fail to a secure state:

```php
try {
    $security->verifyRequest();
    // Process request
} catch (Exception $e) {
    // Log the attempt
    adz_log_warning('Security check failed', [
        'ip' => $security->getClientIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'error' => $e->getMessage()
    ]);
    
    // Fail securely - don't reveal why it failed
    wp_die('Access denied');
}
```

### 3. Log Security Events

```php
// Log successful authentications
adz_log_info('User authenticated', ['user_id' => $userId]);

// Log failed attempts
adz_log_warning('Failed authentication attempt', [
    'username' => $username,
    'ip' => $security->getClientIP()
]);

// Log privilege escalations
adz_log_notice('Admin capability used', [
    'user_id' => $userId,
    'action' => $action,
    'capability' => $capability
]);
```

### 4. Regular Security Audits

Use the framework's security checking features:

```bash
# Run security health check
./adz.sh health:check

# Check for common security issues
./adz.sh security:audit

# Review security logs
./adz.sh log:security
```

The security system provides comprehensive protection while maintaining ease of use and WordPress compatibility. Always combine multiple security measures for maximum protection.