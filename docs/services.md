# Services Guide

Services in the ADZ Framework provide a clean way to organize business logic and create reusable components that can be shared across Controllers, Models, and other Services.

## 🎯 **What are Services?**

Services are classes that:
- **Handle business logic** separated from Controllers and Models
- **Can be injected** into Controllers and other Services  
- **Are automatically registered** in a global service registry
- **Support dependency injection** between services
- **Follow singleton pattern** by default

## 📝 **Creating Services**

### Basic Service Example

```php
<?php
namespace App\Services;

use AdzWP\Core\Service;

class UserService extends Service
{
    /**
     * Get user display name with fallback
     */
    public function getDisplayName(int $userId): string
    {
        $user = get_userdata($userId);
        
        if (!$user) {
            return 'Unknown User';
        }
        
        return $user->display_name ?: $user->user_login;
    }

    /**
     * Update user meta safely
     */
    public function updateUserMeta(int $userId, string $key, $value): bool
    {
        if (!get_userdata($userId)) {
            return false;
        }
        
        $value = $this->sanitizeMetaValue($key, $value);
        return update_user_meta($userId, $key, $value) !== false;
    }

    private function sanitizeMetaValue(string $key, $value)
    {
        switch ($key) {
            case 'email':
                return sanitize_email($value);
            case 'url':
                return esc_url_raw($value);
            default:
                return sanitize_text_field($value);
        }
    }
}
```

## 🔗 **Service Dependencies**

Services can depend on other services using the `dependencies()` method:

```php
<?php
namespace App\Services;

use AdzWP\Core\Service;

class EmailService extends Service
{
    /**
     * Declare service dependencies
     */
    protected function dependencies(): array
    {
        return ['user']; // Depends on UserService
    }

    /**
     * Send notification email to user
     */
    public function sendUserNotification(int $userId, string $template, array $data = []): bool
    {
        $user = get_userdata($userId);
        if (!$user) {
            return false;
        }

        // Use UserService via dependency injection
        $displayName = $this->userService->getDisplayName($userId);
        
        $data['user'] = [
            'name' => $displayName,
            'email' => $user->user_email
        ];

        return $this->sendEmail($user->user_email, $template, $data);
    }

    private function sendEmail(string $to, string $template, array $data): bool
    {
        // Email sending logic...
        return wp_mail($to, 'Subject', $this->renderTemplate($template, $data));
    }
}
```

## 🎮 **Using Services in Controllers**

Controllers can access services through magic properties or the `service()` method:

```php
<?php
namespace App\Controllers;

use AdzWP\Core\Controller;
use App\Services\UserService;
use App\Services\EmailService;

class UserController extends Controller
{
    /**
     * Initialize services
     */
    public function actionWpInit()
    {
        // Create services (they auto-register)
        new UserService();
        new EmailService();
    }

    /**
     * Handle user registration
     */
    public function actionUserRegister($userId)
    {
        // Access services via magic properties
        $displayName = $this->userService->getDisplayName($userId);
        
        // Send welcome email
        $this->emailService->sendUserNotification($userId, 'welcome', [
            'message' => "Welcome {$displayName}!"
        ]);
        
        // Update user meta
        $this->userService->updateUserMeta($userId, 'welcome_sent', true);
    }

    /**
     * Alternative: Use service() method
     */
    public function actionSavePost($postId)
    {
        $userService = $this->service('user');
        $emailService = $this->service('email');
        
        // Use services...
    }
}
```

## 📋 **Service Registration**

Services are automatically registered when instantiated:

```php
// Create service - automatically registers as 'user' and 'UserService'
$userService = new UserService();

// Access registered service
$service = Service::getService('user');
$service = Service::getService('UserService');
```

### Manual Registration

```php
// Manual registration with custom name
Service::register('custom_user', $userService);

// Access with custom name
$service = Service::getService('custom_user');
```

### Service Factory

```php
// Create or get existing service (singleton pattern)
$userService = Service::make(UserService::class);
$sameInstance = Service::make(UserService::class); // Returns same instance
```

## 🔍 **Service Discovery**

### Check if Service Exists

```php
if (Service::has('user')) {
    $userService = Service::getService('user');
}
```

### Get All Services

```php
$allServices = Service::all();
foreach ($allServices as $name => $service) {
    echo "Service: {$name} -> " . get_class($service) . "\n";
}
```

## 🎨 **Magic Properties**

Access services using different naming conventions:

```php
class MyController extends Controller
{
    public function someMethod()
    {
        // All these access the same UserService:
        $this->user;           // Short name
        $this->userService;    // camelCase with suffix
        $this->user_service;   // snake_case with suffix
        
        // Access EmailService:
        $this->email;          // Short name  
        $this->emailService;   // camelCase with suffix
        $this->email_service;  // snake_case with suffix
    }
}
```

## 🏗️ **Advanced Usage**

### Service with Initialization

```php
class CacheService extends Service
{
    private $cache = [];

    /**
     * Called after service is registered
     */
    public function initialize(): void
    {
        // Custom initialization logic
        $this->cache = get_option('my_cache', []);
    }

    public function get(string $key, $default = null)
    {
        return $this->cache[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->cache[$key] = $value;
        update_option('my_cache', $this->cache);
    }
}
```

### Service with Complex Dependencies

```php
class NotificationService extends Service
{
    protected function dependencies(): array
    {
        return [
            'user',     // UserService
            'email',    // EmailService  
            'cache'     // CacheService
        ];
    }

    public function sendBulkNotifications(array $userIds, string $message): array
    {
        $results = [];
        
        foreach ($userIds as $userId) {
            // Check cache first
            $cacheKey = "notification_sent_{$userId}";
            if ($this->cacheService->get($cacheKey)) {
                continue; // Already sent
            }

            // Send notification
            $success = $this->emailService->sendUserNotification($userId, 'notification', [
                'message' => $message
            ]);

            if ($success) {
                $this->cacheService->set($cacheKey, true);
            }

            $results[$userId] = $success;
        }

        return $results;
    }
}
```

## 🧪 **Testing Services**

Services can be easily tested in isolation:

```php
use PHPUnit\Framework\TestCase;
use App\Services\UserService;

class UserServiceTest extends TestCase
{
    private $userService;

    protected function setUp(): void
    {
        Service::clearRegistry(); // Clear for clean tests
        $this->userService = new UserService();
    }

    public function testGetDisplayName()
    {
        // Mock WordPress function
        Functions\when('get_userdata')->justReturn((object)[
            'ID' => 1,
            'display_name' => 'John Doe',
            'user_login' => 'john'
        ]);

        $result = $this->userService->getDisplayName(1);
        $this->assertEquals('John Doe', $result);
    }
}
```

## 📚 **Best Practices**

1. **Single Responsibility**: Each service should have a focused purpose
2. **Dependency Injection**: Use the `dependencies()` method to declare dependencies
3. **Interface Segregation**: Keep service methods focused and cohesive
4. **Stateless When Possible**: Avoid storing request-specific state in services
5. **Error Handling**: Return meaningful results and handle edge cases gracefully

### Example: Well-Structured Service

```php
class OrderService extends Service
{
    protected function dependencies(): array
    {
        return ['user', 'email', 'payment'];
    }

    public function createOrder(int $userId, array $items): array
    {
        // Validate user
        if (!$this->userService->exists($userId)) {
            return ['success' => false, 'error' => 'Invalid user'];
        }

        // Calculate totals
        $total = $this->calculateTotal($items);
        
        // Create order record
        $orderId = $this->saveOrder($userId, $items, $total);
        
        if (!$orderId) {
            return ['success' => false, 'error' => 'Failed to create order'];
        }

        // Send confirmation email
        $this->emailService->sendUserNotification($userId, 'order_confirmation', [
            'order_id' => $orderId,
            'total' => $total,
            'items' => $items
        ]);

        return [
            'success' => true, 
            'order_id' => $orderId,
            'total' => $total
        ];
    }

    private function calculateTotal(array $items): float
    {
        return array_sum(array_column($items, 'price'));
    }

    private function saveOrder(int $userId, array $items, float $total): ?int
    {
        // Database logic here...
        return 123; // Return order ID
    }
}
```

Services provide a powerful way to organize your application's business logic while maintaining clean separation of concerns and enabling easy testing and reusability.