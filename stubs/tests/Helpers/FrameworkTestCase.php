<?php

namespace Tests\Helpers;

use Tests\Helpers\WordPressTestCase;

/**
 * Framework-specific test case
 * 
 * Provides testing utilities specific to the ADZ Framework
 */
abstract class FrameworkTestCase extends WordPressTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up framework-specific environment
        $this->setUpFrameworkEnvironment();
    }

    /**
     * Set up framework environment for testing
     */
    protected function setUpFrameworkEnvironment(): void
    {
        // Define framework constants
        if (!defined('ADZ_PLUGIN_PATH')) {
            define('ADZ_PLUGIN_PATH', dirname(__DIR__, 2) . '/');
        }
        
        if (!defined('ADZ_PLUGIN_URL')) {
            define('ADZ_PLUGIN_URL', 'http://example.org/wp-content/plugins/adz-framework/');
        }
        
        if (!defined('ADZ_PLUGIN_VERSION')) {
            define('ADZ_PLUGIN_VERSION', '1.0.0-test');
        }
        
        // Mock framework-specific functions
        $this->setUpFrameworkMocks();
    }

    /**
     * Set up framework-specific function mocks
     */
    protected function setUpFrameworkMocks(): void
    {
        if (!class_exists('\Brain\Monkey\Functions')) {
            return;
        }

        // Mock ADZ helper functions
        \Brain\Monkey\Functions\when('adz_log')->justReturn(true);
        \Brain\Monkey\Functions\when('adz_log_info')->justReturn(true);
        \Brain\Monkey\Functions\when('adz_log_error')->justReturn(true);
        \Brain\Monkey\Functions\when('adz_log_warning')->justReturn(true);
        \Brain\Monkey\Functions\when('adz_log_debug')->justReturn(true);
        \Brain\Monkey\Functions\when('adz_get_option')->returnArg(2); // Return default value
        \Brain\Monkey\Functions\when('adz_update_option')->justReturn(true);
        \Brain\Monkey\Functions\when('adz_sanitize_input')->returnArg(1);
        \Brain\Monkey\Functions\when('adz_verify_nonce')->justReturn(true);
        \Brain\Monkey\Functions\when('adz_is_admin_page')->justReturn(false);
        \Brain\Monkey\Functions\when('adz_get_current_user_role')->justReturn('subscriber');
        \Brain\Monkey\Functions\when('adz_array_get')->justReturn('test-value');
        \Brain\Monkey\Functions\when('adz_is_ajax')->justReturn(false);
        \Brain\Monkey\Functions\when('adz_is_rest')->justReturn(false);
        \Brain\Monkey\Functions\when('adz_format_bytes')->justReturn('1 KB');
    }

    /**
     * Create a mock controller instance
     */
    protected function createMockController(string $controllerClass = null): object
    {
        if ($controllerClass && class_exists($controllerClass)) {
            return new $controllerClass();
        }
        
        // Create a generic controller mock
        return new class extends \AdzWP\Controller {
            public $actions = [
                'init' => 'testInit'
            ];
            
            public $filters = [
                'the_content' => 'testFilter'
            ];
            
            public function testInit() {
                return true;
            }
            
            public function testFilter($content) {
                return $content . ' [filtered]';
            }
        };
    }

    /**
     * Create a mock model instance
     */
    protected function createMockModel(): object
    {
        return new class extends \AdzHive\Model {
            protected $data = [];
            
            public function getData($key = null) {
                return $key ? ($this->data[$key] ?? null) : $this->data;
            }
            
            public function setData($key, $value = null) {
                if (is_array($key)) {
                    $this->data = array_merge($this->data, $key);
                } else {
                    $this->data[$key] = $value;
                }
                return $this;
            }
        };
    }

    /**
     * Create a mock config instance
     */
    protected function createMockConfig(array $config = []): \AdzHive\Config
    {
        $defaultConfig = [
            'plugin' => [
                'name' => 'Test Plugin',
                'version' => '1.0.0-test',
                'text_domain' => 'test-plugin'
            ],
            'admin' => [
                'menu_title' => 'Test Plugin',
                'menu_slug' => 'test-plugin',
                'capability' => 'manage_options'
            ],
            'security' => [
                'enable_nonce' => true,
                'enable_csrf' => true
            ]
        ];
        
        $mockConfig = $this->getMockBuilder(\AdzHive\Config::class)
                          ->disableOriginalConstructor()
                          ->getMock();
                          
        $configData = array_merge($defaultConfig, $config);
        
        $mockConfig->method('get')
                   ->willReturnCallback(function($key, $default = null) use ($configData) {
                       $keys = explode('.', $key);
                       $value = $configData;
                       
                       foreach ($keys as $segment) {
                           if (is_array($value) && array_key_exists($segment, $value)) {
                               $value = $value[$segment];
                           } else {
                               return $default;
                           }
                       }
                       
                       return $value;
                   });
                   
        $mockConfig->method('set')
                   ->willReturn(true);
                   
        $mockConfig->method('has')
                   ->willReturn(true);
                   
        return $mockConfig;
    }

    /**
     * Assert that a controller has specific actions registered
     */
    protected function assertControllerHasActions(object $controller, array $expectedActions): void
    {
        $this->assertPropertyExists('actions', $controller);
        
        foreach ($expectedActions as $action) {
            $this->assertArrayHasKey($action, $controller->actions, "Controller missing action: {$action}");
        }
    }

    /**
     * Assert that a controller has specific filters registered
     */
    protected function assertControllerHasFilters(object $controller, array $expectedFilters): void
    {
        $this->assertPropertyExists('filters', $controller);
        
        foreach ($expectedFilters as $filter) {
            $this->assertArrayHasKey($filter, $controller->filters, "Controller missing filter: {$filter}");
        }
    }

    /**
     * Assert that helper function exists
     */
    protected function assertHelperFunctionExists(string $function): void
    {
        $this->assertTrue(
            function_exists($function),
            "Helper function '{$function}' does not exist"
        );
    }

    /**
     * Assert that config value can be retrieved
     */
    protected function assertConfigValueAccessible(\AdzHive\Config $config, string $key): void
    {
        $value = $config->get($key);
        $this->assertNotNull($value, "Config key '{$key}' returned null");
    }

    /**
     * Mock framework initialization
     */
    protected function mockFrameworkInitialization(): void
    {
        // Mock ADZ class if not available
        if (!class_exists('ADZ')) {
            $mockADZ = $this->getMockBuilder('stdClass')
                           ->addMethods(['pluginize'])
                           ->getMock();
                           
            $mockADZ->method('pluginize')
                   ->willReturn($this->createMockPlugin());
                   
            if (!defined('ADZ')) {
                define('ADZ', $mockADZ);
            }
        }
    }

    /**
     * Create a mock plugin instance
     */
    protected function createMockPlugin(): object
    {
        return new class {
            public function load(array $controllers = []) {
                return $this;
            }
            
            public function has(string $dependency) {
                return true;
            }
            
            public function getDep(string $dependency) {
                return (object) ['active' => true];
            }
        };
    }

    /**
     * Assert that a class extends another class
     */
    protected function assertClassExtends(string $class, string $parentClass): void
    {
        $this->assertTrue(
            is_subclass_of($class, $parentClass),
            "Class '{$class}' does not extend '{$parentClass}'"
        );
    }

    /**
     * Assert that hook registration methods work correctly
     */
    protected function assertHookRegistrationWorks(object $controller): void
    {
        $this->assertMethodExists('addAction', $controller);
        $this->assertMethodExists('removeAction', $controller);
        $this->assertMethodExists('addFilter', $controller);
        $this->assertMethodExists('removeFilter', $controller);
        $this->assertMethodExists('doAction', $controller);
        $this->assertMethodExists('applyFilters', $controller);
        $this->assertMethodExists('hasAction', $controller);
        $this->assertMethodExists('hasFilter', $controller);
    }

    /**
     * Create test fixture data
     */
    protected function createTestFixtures(): array
    {
        return [
            'user_data' => [
                'user_login' => 'testuser',
                'user_email' => 'test@example.com',
                'user_pass' => 'testpassword',
                'role' => 'subscriber'
            ],
            'post_data' => [
                'post_title' => 'Test Post',
                'post_content' => 'This is test content',
                'post_status' => 'publish',
                'post_type' => 'post'
            ],
            'option_data' => [
                'test_option' => 'test_value',
                'test_array' => ['key1' => 'value1', 'key2' => 'value2'],
                'test_boolean' => true,
                'test_number' => 123
            ]
        ];
    }
}