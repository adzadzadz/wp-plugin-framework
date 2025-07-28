# Form Handling

Forms are a crucial part of most WordPress plugins. The ADZ Plugin Framework provides comprehensive tools for creating, validating, and processing forms securely. This guide covers everything from basic forms to advanced implementations.

## Form Security Overview

The framework automatically handles:
- **CSRF Protection** - Prevents cross-site request forgery
- **Input Validation** - Validates data before processing
- **Data Sanitization** - Cleans input to prevent XSS
- **Rate Limiting** - Prevents form abuse
- **Permission Checking** - Ensures user authorization

## Basic Form Implementation

### Step 1: Create the Form View

Create `src/views/forms/contact-form.php`:

```php
<?php
use AdzHive\Security;

$security = Security::getInstance();
?>

<div class="adz-contact-form">
    <h3><?php echo esc_html($title ?? 'Contact Us'); ?></h3>
    
    <?php if (isset($_GET['message']) && $_GET['message'] === 'success'): ?>
        <div class="notice notice-success">
            <p>Thank you! Your message has been sent successfully.</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="notice notice-error">
            <p>There was an error processing your form. Please try again.</p>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="contact-form">
        <?php echo $security->getNonceField('contact_form_submit', '_contact_nonce'); ?>
        <input type="hidden" name="action" value="contact_form">
        
        <div class="form-group">
            <label for="contact_name">Name *</label>
            <input 
                type="text" 
                id="contact_name" 
                name="name" 
                required
                value="<?php echo esc_attr($_POST['name'] ?? ''); ?>"
            >
        </div>
        
        <div class="form-group">
            <label for="contact_email">Email *</label>
            <input 
                type="email" 
                id="contact_email" 
                name="email" 
                required
                value="<?php echo esc_attr($_POST['email'] ?? ''); ?>"
            >
        </div>
        
        <div class="form-group">
            <label for="contact_subject">Subject *</label>
            <input 
                type="text" 
                id="contact_subject" 
                name="subject" 
                required
                value="<?php echo esc_attr($_POST['subject'] ?? ''); ?>"
            >
        </div>
        
        <div class="form-group">
            <label for="contact_message">Message *</label>
            <textarea 
                id="contact_message" 
                name="message" 
                rows="5" 
                required
            ><?php echo esc_textarea($_POST['message'] ?? ''); ?></textarea>
        </div>
        
        <!-- Honeypot field for bot detection -->
        <div style="display: none;">
            <label for="contact_website">Website</label>
            <input type="url" id="contact_website" name="website" tabindex="-1">
        </div>
        
        <div class="form-group">
            <button type="submit" class="button button-primary">Send Message</button>
        </div>
    </form>
</div>

<style>
.adz-contact-form {
    max-width: 600px;
    margin: 20px 0;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #0073aa;
    outline: none;
    box-shadow: 0 0 0 1px #0073aa;
}

.notice {
    padding: 12px;
    margin: 15px 0;
    border-left: 4px solid;
    background: #fff;
}

.notice-success {
    border-left-color: #46b450;
    color: #155724;
    background-color: #d4edda;
}

.notice-error {
    border-left-color: #dc3232;
    color: #721c24;
    background-color: #f8d7da;
}
</style>
```

### Step 2: Create the Controller

Create `src/controllers/ContactController.php`:

```php
<?php

namespace MyPlugin\Controllers;

use AdzWP\Controller;
use AdzHive\Security;
use AdzHive\Validator;
use AdzHive\ValidationException;
use AdzHive\ForbiddenException;

class ContactController extends Controller 
{
    protected $security;
    
    public $actions = [
        'admin_post_contact_form' => 'handleContactForm',
        'admin_post_nopriv_contact_form' => 'handleContactForm',
        'init' => 'registerShortcode'
    ];
    
    protected function bootstrap()
    {
        $this->security = Security::getInstance();
    }
    
    public function registerShortcode()
    {
        add_shortcode('contact_form', [$this, 'renderContactForm']);
    }
    
    public function renderContactForm($atts = [])
    {
        $atts = shortcode_atts([
            'title' => 'Contact Us',
            'recipient' => get_option('admin_email'),
            'success_message' => 'Thank you! Your message has been sent.'
        ], $atts);
        
        ob_start();
        include plugin_dir_path(__FILE__) . '../views/forms/contact-form.php';
        return ob_get_clean();
    }
    
    public function handleContactForm()
    {
        try {
            // Step 1: Rate limiting (5 submissions per 5 minutes)
            $this->security->checkRateLimit('contact_form', 5, 300);
            
            // Step 2: CSRF protection
            $this->security->verifyRequest('_contact_nonce', 'contact_form_submit');
            
            // Step 3: Validate input
            $validator = Validator::make($_POST, [
                'name' => 'required|string|min:2|max:50',
                'email' => 'required|email',
                'subject' => 'required|string|min:5|max:100',
                'message' => 'required|string|min:10|max:1000',
                'website' => 'url'  // Honeypot field
            ], [
                'name.required' => 'Please enter your name.',
                'name.min' => 'Name must be at least 2 characters.',
                'email.required' => 'Please enter your email address.',
                'email.email' => 'Please enter a valid email address.',
                'subject.required' => 'Please enter a subject.',
                'message.required' => 'Please enter your message.',
                'message.min' => 'Message must be at least 10 characters.'
            ]);
            
            if ($validator->fails()) {
                throw new ValidationException('Please check your input', $validator->errors());
            }
            
            // Step 4: Sanitize data
            $data = $this->security->sanitizeArray($_POST, [
                'name' => 'text',
                'email' => 'email',
                'subject' => 'text',
                'message' => 'textarea',
                'website' => 'url'
            ]);
            
            // Step 5: Bot detection (honeypot)
            if (!empty($data['website'])) {
                // Log bot attempt but don't show error to user
                adz_log_warning('Bot detected on contact form', [
                    'ip' => $this->security->getClientIP(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                // Fake success for bots
                wp_redirect(add_query_arg('message', 'success', wp_get_referer()));
                exit;
            }
            
            // Step 6: Send email
            $emailSent = $this->sendContactEmail($data);
            
            if (!$emailSent) {
                throw new \Exception('Failed to send email');
            }
            
            // Step 7: Log successful submission
            adz_log_info('Contact form submitted successfully', [
                'name' => $data['name'],
                'email' => $data['email'],
                'subject' => $data['subject'],
                'ip' => $this->security->getClientIP()
            ]);
            
            // Step 8: Redirect with success message
            wp_redirect(add_query_arg('message', 'success', wp_get_referer()));
            exit;
            
        } catch (ValidationException $e) {
            $this->handleValidationErrors($e);
        } catch (ForbiddenException $e) {
            $this->handleRateLimitExceeded();
        } catch (\Exception $e) {
            $this->handleGeneralError($e);
        }
    }
    
    protected function sendContactEmail($data)
    {
        $to = get_option('admin_email');
        $subject = '[Contact Form] ' . $data['subject'];
        
        $message = sprintf(
            "New contact form submission:\n\n" .
            "Name: %s\n" .
            "Email: %s\n" .
            "Subject: %s\n\n" .
            "Message:\n%s\n\n" .
            "---\n" .
            "Submitted from: %s\n" .
            "IP Address: %s\n" .
            "Time: %s",
            $data['name'],
            $data['email'],
            $data['subject'],
            $data['message'],
            home_url(),
            $this->security->getClientIP(),
            current_time('mysql')
        );
        
        $headers = [
            'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>',
            'Content-Type: text/plain; charset=UTF-8'
        ];
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    protected function handleValidationErrors(ValidationException $e)
    {
        $errors = $e->getErrors();
        $errorMessages = [];
        
        foreach ($errors as $field => $fieldErrors) {
            $errorMessages = array_merge($errorMessages, $fieldErrors);
        }
        
        $errorString = implode(' ', $errorMessages);
        
        wp_redirect(add_query_arg([
            'error' => 'validation',
            'message' => urlencode($errorString)
        ], wp_get_referer()));
        exit;
    }
    
    protected function handleRateLimitExceeded()
    {
        wp_redirect(add_query_arg([
            'error' => 'rate_limit',
            'message' => urlencode('Too many submissions. Please wait before trying again.')
        ], wp_get_referer()));
        exit;
    }
    
    protected function handleGeneralError(\Exception $e)
    {
        adz_log_error('Contact form error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'post_data' => $_POST
        ]);
        
        wp_redirect(add_query_arg([
            'error' => 'general',
            'message' => urlencode('An error occurred. Please try again later.')
        ], wp_get_referer()));
        exit;
    }
}
```

### Step 3: Register the Controller

In your main plugin file or bootstrap:

```php
use MyPlugin\Controllers\ContactController;

// Initialize controller
$contactController = new ContactController();
$contactController->init();
```

### Step 4: Use the Form

Add the shortcode to any post or page:

```
[contact_form title="Get in Touch" recipient="contact@example.com"]
```

## Advanced Form Features

### Multi-Step Forms

```php
<?php

namespace MyPlugin\Controllers;

use AdzWP\Controller;
use AdzHive\Security;
use AdzHive\Validator;

class MultiStepFormController extends Controller 
{
    public $actions = [
        'admin_post_multistep_form' => 'handleFormStep',
        'admin_post_nopriv_multistep_form' => 'handleFormStep'
    ];
    
    public function handleFormStep()
    {
        try {
            $this->security->verifyRequest();
            
            $step = intval($_POST['current_step'] ?? 1);
            $formData = $_POST['form_data'] ?? [];
            
            switch ($step) {
                case 1:
                    $result = $this->processStep1($formData);
                    break;
                case 2:
                    $result = $this->processStep2($formData);
                    break;
                case 3:
                    $result = $this->processStep3($formData);
                    break;
                default:
                    throw new \Exception('Invalid step');
            }
            
            if ($result['complete']) {
                $this->finalizeForm($result['data']);
                wp_redirect(add_query_arg('success', '1', $result['redirect_url']));
            } else {
                // Store form data in session/transient
                $this->storeFormProgress($result['data'], $result['next_step']);
                wp_redirect(add_query_arg('step', $result['next_step'], wp_get_referer()));
            }
            
        } catch (\Exception $e) {
            wp_redirect(add_query_arg('error', urlencode($e->getMessage()), wp_get_referer()));
        }
        
        exit;
    }
    
    protected function processStep1($data)
    {
        $validator = Validator::make($data, [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email'
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException('Step 1 validation failed', $validator->errors());
        }
        
        return [
            'complete' => false,
            'next_step' => 2,
            'data' => $validator->validated()
        ];
    }
    
    protected function processStep2($data)
    {
        $validator = Validator::make($data, [
            'company' => 'required|string|max:100',
            'job_title' => 'required|string|max:100',
            'industry' => 'required|string'
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException('Step 2 validation failed', $validator->errors());
        }
        
        return [
            'complete' => false,
            'next_step' => 3,
            'data' => array_merge($this->getStoredData(), $validator->validated())
        ];
    }
    
    protected function processStep3($data)
    {
        $validator = Validator::make($data, [
            'interests' => 'array|min:1',
            'budget_range' => 'required|string',
            'timeline' => 'required|string'
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException('Step 3 validation failed', $validator->errors());
        }
        
        return [
            'complete' => true,
            'data' => array_merge($this->getStoredData(), $validator->validated()),
            'redirect_url' => home_url('/thank-you/')
        ];
    }
}
```

### File Upload Forms

```php
<?php

namespace MyPlugin\Controllers;

use AdzWP\Controller;
use AdzHive\Security;
use AdzHive\Validator;

class FileUploadController extends Controller 
{
    public $actions = [
        'admin_post_file_upload' => 'handleFileUpload',
        'admin_post_nopriv_file_upload' => 'handleFileUpload'
    ];
    
    public function handleFileUpload()
    {
        try {
            $this->security->verifyRequest();
            $this->security->checkRateLimit('file_upload', 5, 300);
            
            // Validate form data
            $validator = Validator::make($_POST, [
                'description' => 'required|string|max:500',
                'category' => 'required|string'
            ]);
            
            if ($validator->fails()) {
                throw new ValidationException('Form validation failed', $validator->errors());
            }
            
            // Validate file upload
            if (empty($_FILES['upload_file']['name'])) {
                throw new ValidationException('File is required');
            }
            
            $file = $_FILES['upload_file'];
            $this->validateUploadedFile($file);
            
            // Process file upload
            $uploadResult = $this->processFileUpload($file);
            
            if ($uploadResult['error']) {
                throw new \Exception($uploadResult['error']);
            }
            
            // Save file information
            $this->saveFileRecord([
                'filename' => $uploadResult['filename'],
                'original_name' => $file['name'],
                'file_path' => $uploadResult['file_path'],
                'file_url' => $uploadResult['file_url'],
                'description' => sanitize_textarea_field($_POST['description']),
                'category' => sanitize_text_field($_POST['category']),
                'uploaded_by' => get_current_user_id() ?: null,
                'upload_date' => current_time('mysql')
            ]);
            
            wp_redirect(add_query_arg('upload', 'success', wp_get_referer()));
            
        } catch (\Exception $e) {
            wp_redirect(add_query_arg('error', urlencode($e->getMessage()), wp_get_referer()));
        }
        
        exit;
    }
    
    protected function validateUploadedFile($file)
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'File is too large (server limit)',
                UPLOAD_ERR_FORM_SIZE => 'File is too large (form limit)',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            
            throw new \Exception($errors[$file['error']] ?? 'Unknown upload error');
        }
        
        // Check file size (5MB limit)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            throw new \Exception('File is too large. Maximum size is 5MB.');
        }
        
        // Check file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new \Exception('File type not allowed. Allowed types: JPEG, PNG, GIF, PDF, TXT.');
        }
        
        // Check for malicious content
        if ($this->containsMaliciousContent($file['tmp_name'])) {
            throw new \Exception('File contains potentially malicious content.');
        }
    }
    
    protected function processFileUpload($file)
    {
        $uploadDir = wp_upload_dir();
        $pluginUploadDir = $uploadDir['basedir'] . '/my-plugin-uploads/';
        
        // Create directory if it doesn't exist
        if (!file_exists($pluginUploadDir)) {
            wp_mkdir_p($pluginUploadDir);
            
            // Add security files
            file_put_contents($pluginUploadDir . '.htaccess', 'Options -Indexes');
            file_put_contents($pluginUploadDir . 'index.php', '<?php // Silence is golden');
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = wp_unique_filename($pluginUploadDir, 
            sanitize_file_name(pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $extension
        );
        
        $filePath = $pluginUploadDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['error' => 'Failed to save uploaded file'];
        }
        
        // Set proper permissions
        chmod($filePath, 0644);
        
        return [
            'error' => false,
            'filename' => $filename,
            'file_path' => $filePath,
            'file_url' => $uploadDir['baseurl'] . '/my-plugin-uploads/' . $filename
        ];
    }
    
    protected function containsMaliciousContent($filePath)
    {
        $content = file_get_contents($filePath, false, null, 0, 1024); // Read first 1KB
        
        // Check for common malicious patterns
        $maliciousPatterns = [
            '/<\?php/',
            '/<script/',
            '/eval\s*\(/i',
            '/base64_decode/i',
            '/shell_exec/i',
            '/system\s*\(/i',
            '/exec\s*\(/i'
        ];
        
        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
}
```

### AJAX Forms

```php
<?php

namespace MyPlugin\Controllers;

use AdzWP\Controller;
use AdzHive\Security;
use AdzHive\Validator;

class AjaxFormController extends Controller 
{
    public $actions = [
        'wp_ajax_submit_ajax_form' => 'handleAjaxForm',
        'wp_ajax_nopriv_submit_ajax_form' => 'handleAjaxForm',
        'wp_enqueue_scripts' => 'enqueueAjaxScript'
    ];
    
    public function enqueueAjaxScript()
    {
        wp_enqueue_script(
            'ajax-form-handler',
            plugin_dir_url(__FILE__) . '../assets/js/ajax-form.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('ajax-form-handler', 'ajaxForm', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ajax_form_nonce')
        ]);
    }
    
    public function handleAjaxForm()
    {
        try {
            $this->security->verifyAjaxRequest('ajax_form_nonce');
            $this->security->checkRateLimit('ajax_form', 10, 300);
            
            $validator = Validator::make($_POST, [
                'name' => 'required|string|min:2|max:50',
                'email' => 'required|email',
                'message' => 'required|string|min:10|max:500'
            ]);
            
            if ($validator->fails()) {
                wp_send_json_error([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ]);
            }
            
            $data = $this->security->sanitizeArray($_POST, [
                'name' => 'text',
                'email' => 'email',
                'message' => 'textarea'
            ]);
            
            // Process the form data
            $result = $this->processAjaxFormData($data);
            
            wp_send_json_success([
                'message' => 'Form submitted successfully!',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            adz_log_error('Ajax form error', [
                'error' => $e->getMessage(),
                'post_data' => $_POST
            ]);
            
            wp_send_json_error([
                'message' => 'An error occurred. Please try again.',
                'debug' => WP_DEBUG ? $e->getMessage() : null
            ]);
        }
    }
    
    protected function processAjaxFormData($data)
    {
        // Your processing logic here
        return [
            'id' => uniqid(),
            'timestamp' => current_time('mysql'),
            'processed' => true
        ];
    }
}
```

JavaScript for AJAX form:

```javascript
// assets/js/ajax-form.js
jQuery(document).ready(function($) {
    $('#ajax-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submit = $form.find('button[type="submit"]');
        var originalText = $submit.text();
        
        // Show loading state
        $submit.prop('disabled', true).text('Submitting...');
        
        // Clear previous messages
        $('.form-message').remove();
        
        $.ajax({
            url: ajaxForm.ajax_url,
            type: 'POST',
            data: {
                action: 'submit_ajax_form',
                nonce: ajaxForm.nonce,
                name: $form.find('input[name="name"]').val(),
                email: $form.find('input[name="email"]').val(),
                message: $form.find('textarea[name="message"]').val()
            },
            success: function(response) {
                if (response.success) {
                    $form.before('<div class="form-message success">' + response.data.message + '</div>');
                    $form[0].reset();
                } else {
                    var errorMessage = response.data.message;
                    if (response.data.errors) {
                        var errors = [];
                        $.each(response.data.errors, function(field, fieldErrors) {
                            errors = errors.concat(fieldErrors);
                        });
                        errorMessage += '<br>' + errors.join('<br>');
                    }
                    $form.before('<div class="form-message error">' + errorMessage + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $form.before('<div class="form-message error">Network error. Please try again.</div>');
            },
            complete: function() {
                // Restore button state
                $submit.prop('disabled', false).text(originalText);
            }
        });
    });
});
```

## Form Security Best Practices

### 1. Always Use Nonces

```php
// Generate nonce
echo $security->getNonceField('my_form_action', '_my_nonce');

// Verify nonce
$security->verifyRequest('_my_nonce', 'my_form_action');
```

### 2. Implement Rate Limiting

```php
// Limit form submissions
$security->checkRateLimit('contact_form', 5, 300); // 5 per 5 minutes
$security->checkRateLimit('registration', 3, 3600); // 3 per hour
$security->checkRateLimit('password_reset', 2, 1800); // 2 per 30 minutes
```

### 3. Validate All Input

```php
$validator = Validator::make($_POST, [
    'email' => 'required|email|max:255',
    'password' => 'required|min:8|confirmed',
    'age' => 'numeric|between:13,120',
    'terms' => 'required|boolean'
]);
```

### 4. Sanitize Output

```php
// Sanitize data before saving
$data = $security->sanitizeArray($_POST, [
    'name' => 'text',
    'email' => 'email',
    'website' => 'url',
    'bio' => 'textarea'
]);

// Escape output in templates
echo esc_html($user_input);
echo esc_attr($attribute_value);
echo esc_url($url_value);
```

### 5. Use Honeypot Fields

```php
<!-- Hidden field to catch bots -->
<div style="display: none;">
    <input type="text" name="website" tabindex="-1">
</div>
```

```php
// Check honeypot in controller
if (!empty($_POST['website'])) {
    // Bot detected - log and fake success
    adz_log_warning('Bot detected', ['ip' => $security->getClientIP()]);
    wp_redirect(add_query_arg('message', 'success', wp_get_referer()));
    exit;
}
```

### 6. Log Security Events

```php
// Log successful submissions
adz_log_info('Form submitted', [
    'form' => 'contact',
    'user_ip' => $security->getClientIP(),
    'user_id' => get_current_user_id()
]);

// Log failed attempts
adz_log_warning('Form validation failed', [
    'errors' => $validator->errors(),
    'ip' => $security->getClientIP()
]);
```

Forms are critical components of user interaction. By following these patterns and security practices, you'll create forms that are both user-friendly and secure, protecting your site from common vulnerabilities while providing a smooth user experience.