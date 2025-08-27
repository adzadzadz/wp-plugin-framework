<?php 

namespace AdzWP\Core;

class Controller extends Core {

    public $filters = [];
    public $actions = [];
    
    public function __construct()
    {
        parent::__construct();
        $this->autoRegisterHooks();
    }

    /**
     * Get a service instance
     * 
     * @param string $serviceName Service name or full class name
     * @return Service|null
     */
    protected function service(string $serviceName): ?Service
    {
        return Service::getService($serviceName);
    }

    /**
     * Magic method to get services as properties
     * Example: $this->user_service or $this->userService
     */
    public function __get(string $name)
    {
        // Convert camelCase to snake_case
        $serviceName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
        
        // Remove _service suffix if present for lookup
        $lookupName = $serviceName;
        if (substr($serviceName, -8) === '_service') {
            $lookupName = substr($serviceName, 0, -8);
        }
        
        $service = Service::getService($lookupName);
        
        if ($service) {
            return $service;
        }
        
        // Try original name
        return Service::getService($serviceName);
    }

    public function addAction($hook, $callback = null, $priority = 10, $accepted_args = 1)
    {
        $callback = $callback ?? $hook;
        
        if (is_string($callback) && method_exists($this, $callback)) {
            $callback = [$this, $callback];
        }
        
        return add_action($hook, $callback, $priority, $accepted_args);
    }

    public function removeAction($hook, $callback = null, $priority = 10)
    {
        $callback = $callback ?? $hook;
        
        if (is_string($callback) && method_exists($this, $callback)) {
            $callback = [$this, $callback];
        }
        
        return remove_action($hook, $callback, $priority);
    }

    public function addFilter($hook, $callback = null, $priority = 10, $accepted_args = 1)
    {
        $callback = $callback ?? $hook;
        
        if (is_string($callback) && method_exists($this, $callback)) {
            $callback = [$this, $callback];
        }
        
        return add_filter($hook, $callback, $priority, $accepted_args);
    }

    public function removeFilter($hook, $callback = null, $priority = 10)
    {
        $callback = $callback ?? $hook;
        
        if (is_string($callback) && method_exists($this, $callback)) {
            $callback = [$this, $callback];
        }
        
        return remove_filter($hook, $callback, $priority);
    }

    public function doAction($hook, ...$args)
    {
        return do_action($hook, ...$args);
    }

    public function applyFilters($hook, $value, ...$args)
    {
        return apply_filters($hook, $value, ...$args);
    }

    public function hasAction($hook)
    {
        return has_action($hook);
    }

    public function hasFilter($hook)
    {
        return has_filter($hook);
    }

    protected function getCurrentUserId()
    {
        return get_current_user_id();
    }

    protected function currentUserCan($capability)
    {
        return current_user_can($capability);
    }

    protected function isAdmin()
    {
        return is_admin();
    }

    protected function isFrontend()
    {
        return !is_admin();
    }

    protected function wpDie($message, $title = '', $args = [])
    {
        wp_die($message, $title, $args);
    }

    protected function sanitizeText($text)
    {
        return sanitize_text_field($text);
    }

    protected function escapeHtml($text)
    {
        return esc_html($text);
    }

    protected function escapeUrl($url)
    {
        return esc_url($url);
    }

    protected function verifyNonce($nonce, $action = -1)
    {
        return wp_verify_nonce($nonce, $action);
    }

    protected function createNonce($action = -1)
    {
        return wp_create_nonce($action);
    }

    /**
     * Automatically register hooks based on method naming conventions
     * Methods starting with 'action' will be registered as actions
     * Methods starting with 'filter' will be registered as filters
     * Methods starting with 'adminPage' will create admin pages
     * Methods starting with 'admin' will run only in admin
     * Methods starting with 'frontend' will run only in frontend
     */
    protected function autoRegisterHooks()
    {
        $reflection = new \ReflectionClass($this);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            $methodName = $method->getName();
            
            // Skip magic methods and constructor
            if (strpos($methodName, '__') === 0 || $methodName === 'autoRegisterHooks') {
                continue;
            }
            
            // Handle adminPage methods: adminPageSettings, adminPageUsers, etc.
            if (strpos($methodName, 'adminPage') === 0 && strlen($methodName) > 9) {
                $this->registerAdminPage($method);
                continue;
            }
            
            // Handle admin methods: adminSettings, adminUsers, etc.
            if (strpos($methodName, 'admin') === 0 && strlen($methodName) > 5 && strpos($methodName, 'adminPage') !== 0) {
                $this->registerAdminMethod($method);
                continue;
            }
            
            // Handle frontend methods: frontendSettings, frontendUsers, etc.
            if (strpos($methodName, 'frontend') === 0 && strlen($methodName) > 8) {
                $this->registerFrontendMethod($method);
                continue;
            }
            
            // Handle action methods: actionWpInit, actionAdminMenu, etc.
            if (strpos($methodName, 'action') === 0 && strlen($methodName) > 6) {
                $hookName = $this->convertMethodNameToHook(substr($methodName, 6));
                $priority = $this->getMethodPriority($method);
                $acceptedArgs = $this->getMethodAcceptedArgs($method);
                
                add_action($hookName, [$this, $methodName], $priority, $acceptedArgs);
                continue;
            }
            
            // Handle filter methods: filterTheTitle, filterTheContent, etc.
            if (strpos($methodName, 'filter') === 0 && strlen($methodName) > 6) {
                $hookName = $this->convertMethodNameToHook(substr($methodName, 6));
                $priority = $this->getMethodPriority($method);
                $acceptedArgs = $this->getMethodAcceptedArgs($method);
                
                add_filter($hookName, [$this, $methodName], $priority, $acceptedArgs);
                continue;
            }
        }
    }

    /**
     * Convert CamelCase method name to WordPress hook format
     * Examples: WpInit -> wp_init, AdminMenu -> admin_menu
     */
    protected function convertMethodNameToHook($methodName)
    {
        // Convert CamelCase to snake_case
        $hookName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $methodName));
        
        return $hookName;
    }

    /**
     * Get priority from method parameter, docblock, or return default
     */
    protected function getMethodPriority(\ReflectionMethod $method)
    {
        // First check for priority parameter
        $paramPriority = $this->getMethodPriorityFromParams($method, $this);
        if ($paramPriority !== null) {
            return $paramPriority;
        }
        
        // Then check docblock
        $docComment = $method->getDocComment();
        if ($docComment && preg_match('/@priority\s+(\d+)/', $docComment, $matches)) {
            return (int) $matches[1];
        }
        
        return 10; // Default priority
    }

    /**
     * Get accepted args from method docblock or count parameters
     * Excludes priority parameter if present
     */
    protected function getMethodAcceptedArgs(\ReflectionMethod $method)
    {
        $docComment = $method->getDocComment();
        
        // Check for explicit @args annotation
        if ($docComment && preg_match('/@args\s+(\d+)/', $docComment, $matches)) {
            return (int) $matches[1];
        }
        
        // Count method parameters, excluding priority if present
        $paramCount = $method->getNumberOfParameters();
        
        // Check if last parameter is named 'priority' - if so, exclude it from count
        $parameters = $method->getParameters();
        if (!empty($parameters)) {
            $lastParam = end($parameters);
            if ($lastParam->getName() === 'priority') {
                $paramCount--;
            }
        }
        
        return $paramCount;
    }

    /**
     * Get priority from method parameter, docblock, or return default
     */
    protected function getMethodPriorityFromParams(\ReflectionMethod $method, $instance)
    {
        $parameters = $method->getParameters();
        
        // Check if last parameter is named 'priority'
        if (!empty($parameters)) {
            $lastParam = end($parameters);
            if ($lastParam->getName() === 'priority') {
                // Get default value if available
                if ($lastParam->isDefaultValueAvailable()) {
                    return $lastParam->getDefaultValue();
                }
                // If no default, return standard default
                return 10;
            }
        }
        
        return null; // No priority parameter found
    }

    /**
     * Register an admin page method
     * Creates admin menu page and handles display automatically
     */
    protected function registerAdminPage(\ReflectionMethod $method)
    {
        $methodName = $method->getName();
        $pageName = substr($methodName, 9); // Remove 'adminPage' prefix
        
        // Register the admin_menu action to create the page
        add_action('admin_menu', function() use ($method, $methodName, $pageName) {
            $pageConfig = $this->getAdminPageConfig($method, $pageName);
            
            if ($pageConfig['parent']) {
                add_submenu_page(
                    $pageConfig['parent'],
                    $pageConfig['page_title'],
                    $pageConfig['menu_title'],
                    $pageConfig['capability'],
                    $pageConfig['menu_slug'],
                    [$this, $methodName]
                );
            } else {
                add_menu_page(
                    $pageConfig['page_title'],
                    $pageConfig['menu_title'],
                    $pageConfig['capability'],
                    $pageConfig['menu_slug'],
                    [$this, $methodName],
                    $pageConfig['icon_url'],
                    $pageConfig['position']
                );
            }
        });
    }

    /**
     * Get admin page configuration from method docblock or defaults
     */
    protected function getAdminPageConfig(\ReflectionMethod $method, $pageName)
    {
        $docComment = $method->getDocComment() ?: '';
        
        // Default configuration
        $config = [
            'page_title' => ucwords(str_replace(['_', '-'], ' ', $this->convertMethodNameToHook($pageName))),
            'menu_title' => ucwords(str_replace(['_', '-'], ' ', $this->convertMethodNameToHook($pageName))),
            'capability' => 'manage_options',
            'menu_slug' => $this->convertMethodNameToHook($pageName),
            'icon_url' => 'dashicons-admin-generic',
            'position' => null,
            'parent' => null
        ];
        
        // Parse docblock for configuration
        if (preg_match('/@page_title\s+(.+)/', $docComment, $matches)) {
            $config['page_title'] = trim($matches[1]);
        }
        
        if (preg_match('/@menu_title\s+(.+)/', $docComment, $matches)) {
            $config['menu_title'] = trim($matches[1]);
        }
        
        if (preg_match('/@capability\s+(.+)/', $docComment, $matches)) {
            $config['capability'] = trim($matches[1]);
        }
        
        if (preg_match('/@menu_slug\s+(.+)/', $docComment, $matches)) {
            $config['menu_slug'] = trim($matches[1]);
        }
        
        if (preg_match('/@icon_url\s+(.+)/', $docComment, $matches)) {
            $config['icon_url'] = trim($matches[1]);
        }
        
        if (preg_match('/@position\s+(\d+)/', $docComment, $matches)) {
            $config['position'] = (int) $matches[1];
        }
        
        if (preg_match('/@parent\s+(.+)/', $docComment, $matches)) {
            $config['parent'] = trim($matches[1]);
        }
        
        return $config;
    }

    /**
     * Register an admin-only method
     * Method will only run when in admin area
     */
    protected function registerAdminMethod(\ReflectionMethod $method)
    {
        $methodName = $method->getName();
        
        // Wrap the method call in admin check
        add_action('wp_loaded', function() use ($methodName) {
            if (is_admin()) {
                $this->$methodName();
            }
        });
    }

    /**
     * Register a frontend-only method
     * Method will only run when not in admin area
     */
    protected function registerFrontendMethod(\ReflectionMethod $method)
    {
        $methodName = $method->getName();
        
        // Wrap the method call in frontend check
        add_action('wp_loaded', function() use ($methodName) {
            if (!is_admin()) {
                $this->$methodName();
            }
        });
    }

}