<?php

use PHPUnit\Framework\TestCase;
use AdzWP\Core\Service;

class TestService extends Service
{
    public $initialized = false;

    public function initialize(): void
    {
        $this->initialized = true;
    }

    public function doSomething(): string
    {
        return 'service_result';
    }
}

class TestServiceWithDependencies extends Service
{
    protected function dependencies(): array
    {
        return ['test']; // Depends on TestService
    }

    public function useTestService(): string
    {
        return $this->service('test')->doSomething();
    }
}

class AnotherTestService extends Service
{
    public function getValue(): int
    {
        return 42;
    }
}

class ServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear service registry before each test
        Service::clearRegistry();
    }

    protected function tearDown(): void
    {
        Service::clearRegistry();
        parent::tearDown();
    }

    public function testServiceRegistration()
    {
        $service = new TestService();
        
        // Check service is registered by class name
        $this->assertSame($service, Service::getService(TestService::class));
        
        // Check service is registered by snake_case name
        $this->assertSame($service, Service::getService('test'));
        
        // Check service exists
        $this->assertTrue(Service::has('test'));
        $this->assertTrue(Service::has(TestService::class));
    }

    public function testServiceNameGeneration()
    {
        $service = new TestService();
        
        // Service should be registered as 'test' (TestService -> test)
        $this->assertTrue(Service::has('test'));
        
        $anotherService = new AnotherTestService();
        
        // Should be registered as 'another_test' (AnotherTestService -> another_test)
        $this->assertTrue(Service::has('another_test'));
    }

    public function testServiceMake()
    {
        // Make should create and register service
        $service = Service::make(TestService::class);
        
        $this->assertInstanceOf(TestService::class, $service);
        $this->assertTrue(Service::has('test'));
        
        // Making same service again should return existing instance (singleton)
        $sameService = Service::make(TestService::class);
        $this->assertSame($service, $sameService);
    }

    public function testServiceMakeWithInvalidClass()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Service class NonExistentService does not exist');
        
        Service::make('NonExistentService');
    }

    public function testManualRegistration()
    {
        $service = new TestService();
        
        Service::register('custom_name', $service);
        
        $this->assertSame($service, Service::getService('custom_name'));
        $this->assertTrue(Service::has('custom_name'));
    }

    public function testServiceDependencies()
    {
        // Create the dependency first
        new TestService();
        
        // Create service with dependencies
        $serviceWithDeps = new TestServiceWithDependencies();
        
        // Should be able to use the dependency
        $result = $serviceWithDeps->useTestService();
        $this->assertEquals('service_result', $result);
    }

    public function testServicePropertyAccess()
    {
        $testService = new TestService();
        $anotherService = new AnotherTestService();
        
        // Test magic property access with snake_case
        $this->assertSame($testService, $anotherService->test_service);
        $this->assertSame($testService, $anotherService->test);
        
        // Test magic property access with camelCase
        $this->assertSame($testService, $anotherService->testService);
    }

    public function testGetAllServices()
    {
        $service1 = new TestService();
        $service2 = new AnotherTestService();
        
        $allServices = Service::all();
        
        // Should contain both services registered by different names
        $this->assertArrayHasKey('test', $allServices);
        $this->assertArrayHasKey('another_test', $allServices);
        $this->assertArrayHasKey(TestService::class, $allServices);
        $this->assertArrayHasKey(AnotherTestService::class, $allServices);
        
        $this->assertSame($service1, $allServices['test']);
        $this->assertSame($service2, $allServices['another_test']);
    }

    public function testServiceNotFound()
    {
        $this->assertNull(Service::getService('nonexistent'));
        $this->assertFalse(Service::has('nonexistent'));
    }

    public function testClearRegistry()
    {
        new TestService();
        new AnotherTestService();
        
        $this->assertTrue(Service::has('test'));
        $this->assertTrue(Service::has('another_test'));
        
        Service::clearRegistry();
        
        $this->assertFalse(Service::has('test'));
        $this->assertFalse(Service::has('another_test'));
        $this->assertEmpty(Service::all());
    }

    public function testServiceMethodAccess()
    {
        $service = new TestService();
        
        // Test calling service method
        $result = $service->doSomething();
        $this->assertEquals('service_result', $result);
    }

    public function testServiceWithSuffixRemoval()
    {
        // Create a service class that ends with 'Service'
        $service = new TestService(); // TestService -> test
        
        $this->assertTrue(Service::has('test'));
        $this->assertFalse(Service::has('test_service')); // Suffix should be removed
    }

    public function testServiceNotFoundThrowsException()
    {
        $testService = new class extends Service {
            public function testServiceCall(string $serviceName): Service
            {
                return $this->service($serviceName);
            }
        };
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Service 'nonexistent' not found");
        
        $testService->testServiceCall('nonexistent');
    }
}