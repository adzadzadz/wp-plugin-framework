<?php

namespace AdzWP\Core;

/**
 * Base Service class for the ADZ WordPress Plugin Framework
 * 
 * Services handle business logic and can be injected into Controllers, Models, and other Services.
 * They provide a clean separation between data access (Models) and request handling (Controllers).
 * 
 * @package AdzWP\Core
 */
abstract class Service extends Core
{
    /**
     * Service registry for dependency injection
     */
    protected static $services = [];

    /**
     * Service constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        // Auto-register this service instance
        $this->registerService();
    }

    /**
     * Register this service in the global registry
     */
    protected function registerService()
    {
        $className = get_class($this);
        $serviceName = $this->getServiceName($className);
        
        static::$services[$serviceName] = $this;
        static::$services[$className] = $this;
    }

    /**
     * Get service name from class name
     * Example: App\Services\UserService -> user
     */
    protected function getServiceName(string $className): string
    {
        // Extract class name without namespace
        $parts = explode('\\', $className);
        $className = end($parts);
        
        // Remove 'Service' suffix if present
        if (substr($className, -7) === 'Service') {
            $className = substr($className, 0, -7);
        }
        
        // Convert to snake_case
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
    }

    /**
     * Get a registered service instance
     * 
     * @param string $serviceName Service name or full class name
     * @return Service|null
     */
    public static function getService(string $serviceName): ?Service
    {
        return static::$services[$serviceName] ?? null;
    }

    /**
     * Check if a service is registered
     * 
     * @param string $serviceName Service name or full class name
     * @return bool
     */
    public static function has(string $serviceName): bool
    {
        return isset(static::$services[$serviceName]);
    }

    /**
     * Get all registered services
     * 
     * @return array
     */
    public static function all(): array
    {
        return static::$services;
    }

    /**
     * Clear all registered services (useful for testing)
     */
    public static function clearRegistry(): void
    {
        static::$services = [];
    }

    /**
     * Manually register a service instance
     * 
     * @param string $name Service name
     * @param Service $service Service instance
     */
    public static function register(string $name, Service $service): void
    {
        static::$services[$name] = $service;
        static::$services[get_class($service)] = $service;
    }

    /**
     * Create or get a service instance (singleton pattern)
     * 
     * @param string $className Full service class name
     * @param mixed ...$args Constructor arguments
     * @return Service
     */
    public static function make(string $className, ...$args): Service
    {
        // Return existing instance if already registered
        if (isset(static::$services[$className])) {
            return static::$services[$className];
        }
        
        // Create new instance
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Service class {$className} does not exist");
        }
        
        $instance = new $className(...$args);
        
        if (!($instance instanceof Service)) {
            throw new \InvalidArgumentException("Class {$className} must extend Service");
        }
        
        return $instance;
    }

    /**
     * Inject dependencies into the service
     * Override this method to specify service dependencies
     * 
     * @return array Array of service names this service depends on
     */
    protected function dependencies(): array
    {
        return [];
    }

    /**
     * Resolve and inject dependencies
     */
    protected function resolveDependencies(): void
    {
        foreach ($this->dependencies() as $dependency) {
            if (!static::has($dependency)) {
                // Try to auto-create the dependency if it's a class name
                if (class_exists($dependency)) {
                    static::make($dependency);
                } else {
                    throw new \RuntimeException("Service dependency '{$dependency}' not found");
                }
            }
        }
    }

    /**
     * Get dependency service
     * 
     * @param string $serviceName
     * @return Service
     * @throws \RuntimeException
     */
    protected function service(string $serviceName): Service
    {
        $service = static::getService($serviceName);
        
        if (!$service) {
            throw new \RuntimeException("Service '{$serviceName}' not found");
        }
        
        return $service;
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
        
        $service = static::getService($lookupName);
        
        if ($service) {
            return $service;
        }
        
        // Try original name
        return static::getService($serviceName);
    }

    /**
     * Initialize service after all dependencies are resolved
     * Override this method for custom initialization logic
     */
    public function initialize(): void
    {
        // Override in child classes
    }
}