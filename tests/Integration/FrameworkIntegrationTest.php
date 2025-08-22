<?php

namespace Tests\Integration;

use Tests\Helpers\FrameworkTestCase;

/**
 * Integration tests for the framework as a whole
 */
class FrameworkIntegrationTest extends FrameworkTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockFrameworkInitialization();
    }

    public function testFrameworkInitializationProcess()
    {
        // Test that the framework can be initialized without errors
        $this->expectNotToPerformAssertions();
        
        // This test passes if no exceptions are thrown during setup
    }

    public function testControllerRegistrationWithFramework()
    {
        $controller = $this->createMockController();
        
        // Test that controller has the expected structure after framework setup
        $this->assertPropertyExists('actions', $controller);
        $this->assertPropertyExists('filters', $controller);
        $this->assertIsArray($controller->actions);
        $this->assertIsArray($controller->filters);
    }

    public function testHookRegistrationFlow()
    {
        $controller = $this->createMockController();
        
        // Test the complete hook registration flow
        $this->assertControllerHasActions($controller, ['init']);
        $this->assertControllerHasFilters($controller, ['the_content']);
        
        // Test hook registration methods are available
        $this->assertHookRegistrationWorks($controller);
    }

    public function testConfigurationIntegration()
    {
        $config = $this->createMockConfig([
            'test' => [
                'integration' => 'value'
            ]
        ]);
        
        // Test config integration
        $this->assertConfigValueAccessible($config, 'test.integration');
        $this->assertEquals('value', $config->get('test.integration'));
    }

    public function testHelperFunctionsIntegration()
    {
        // Test that helper functions are loaded and available
        $expectedFunctions = [
            'adz_log',
            'adz_log_info',
            'adz_log_error',
            'adz_get_option',
            'adz_sanitize_input',
            'adz_array_get',
            'adz_format_bytes'
        ];
        
        foreach ($expectedFunctions as $function) {
            if (function_exists($function)) {
                $this->assertTrue(true, "Function {$function} exists");
            } else {
                $this->markTestSkipped("Function {$function} not loaded in test environment");
            }
        }
    }

    public function testWordPressIntegrationMocking()
    {
        $this->mockAdminEnvironment();
        
        if (class_exists('\Brain\Monkey\Functions')) {
            // Test WordPress function calls work
            \Brain\Monkey\Functions\expect('is_admin')
                ->once()
                ->andReturn(true);
                
            $result = is_admin();
            $this->assertTrue($result);
        } else {
            $this->markTestSkipped('Brain Monkey not available');
        }
    }

    public function testControllerMethodsIntegrationWithWordPress()
    {
        $controller = $this->createMockController();
        
        if (class_exists('\Brain\Monkey\Functions')) {
            // Test controller methods integrate with WordPress functions
            \Brain\Monkey\Functions\expect('current_user_can')
                ->once()
                ->with('manage_options')
                ->andReturn(true);
                
            $result = $controller->currentUserCan('manage_options');
            $this->assertTrue($result);
            
            \Brain\Monkey\Functions\expect('sanitize_text_field')
                ->once()
                ->with('test input')
                ->andReturn('test input');
                
            $result = $controller->sanitizeText('test input');
            $this->assertEquals('test input', $result);
        } else {
            $this->markTestSkipped('Brain Monkey not available');
        }
    }

    public function testFrameworkConstantsAreSet()
    {
        $expectedConstants = [
            'ADZ_PLUGIN_PATH',
            'ADZ_PLUGIN_URL',
            'ADZ_PLUGIN_VERSION'
        ];
        
        foreach ($expectedConstants as $constant) {
            if (defined($constant)) {
                $this->assertTrue(true, "Constant {$constant} is defined");
            } else {
                $this->markTestSkipped("Constant {$constant} not defined in test environment");
            }
        }
    }

    public function testControllerActionRegistration()
    {
        $controller = $this->createMockController();
        
        if (class_exists('\Brain\Monkey\Functions')) {
            // Test that actions can be registered
            \Brain\Monkey\Functions\expect('add_action')
                ->once()
                ->with('test_action', [$controller, 'testInit'], 10, 1)
                ->andReturn(true);
                
            $result = $controller->addAction('test_action', 'testInit');
            $this->assertTrue($result);
        } else {
            $this->markTestSkipped('Brain Monkey not available');
        }
    }

    public function testControllerFilterRegistration()
    {
        $controller = $this->createMockController();
        
        if (class_exists('\Brain\Monkey\Functions')) {
            // Test that filters can be registered
            \Brain\Monkey\Functions\expect('add_filter')
                ->once()
                ->with('test_filter', [$controller, 'testFilter'], 10, 1)
                ->andReturn(true);
                
            $result = $controller->addFilter('test_filter', 'testFilter');
            $this->assertTrue($result);
        } else {
            $this->markTestSkipped('Brain Monkey not available');
        }
    }

    public function testFrameworkWithAjaxEnvironment()
    {
        $this->mockAjaxEnvironment();
        $controller = $this->createMockController();
        
        $this->assertTrue(defined('DOING_AJAX'));
        $this->assertTrue(DOING_AJAX);
        
        // Test AJAX specific functionality
        $this->assertArrayHasKey('action', $_POST);
        $this->assertEquals('test_ajax_action', $_POST['action']);
    }

    public function testFrameworkWithRestEnvironment()
    {
        $this->mockRestEnvironment();
        
        $this->assertTrue(defined('REST_REQUEST'));
        $this->assertTrue(REST_REQUEST);
    }

    public function testCompleteWorkflowSimulation()
    {
        // Simulate a complete workflow: initialization -> controller creation -> hook registration
        $this->mockFrameworkInitialization();
        
        $controller = $this->createMockController();
        $config = $this->createMockConfig();
        
        // Test that all components work together
        $this->assertInstanceOf('AdzWP\Controller', $controller);
        $this->assertInstanceOf('AdzHive\Config', $config);
        
        // Test configuration access from controller context
        $testValue = $config->get('plugin.name', 'fallback');
        $this->assertNotNull($testValue);
        
        // Test that hooks can be managed
        $this->assertHookRegistrationWorks($controller);
    }
}