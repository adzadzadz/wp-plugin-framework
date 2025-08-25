<?php

use PHPUnit\Framework\TestCase;
use AdzWP\Core\Controller;

class TestAutoHooksController extends Controller
{
    public $actionsCalled = [];
    public $filtersCalled = [];

    /**
     * @priority 20
     */
    public function actionWpInit()
    {
        $this->actionsCalled[] = 'wp_init';
    }

    public function actionAdminMenu()
    {
        $this->actionsCalled[] = 'admin_menu';
    }

    /**
     * @args 2
     */
    public function filterTheTitle($title, $id)
    {
        $this->filtersCalled[] = 'the_title';
        return $title . ' (Modified)';
    }

    public function filterTheContent($content)
    {
        $this->filtersCalled[] = 'the_content';
        return $content;
    }

    /**
     * Test priority via parameter
     */
    public function actionCustomPriority($priority = 15)
    {
        $this->actionsCalled[] = 'custom_priority';
    }

    /**
     * Test priority with normal parameters
     */
    public function filterWithPriority($content, $post_id, $priority = 25)
    {
        $this->filtersCalled[] = 'with_priority';
        return $content;
    }

    // This should NOT be registered (doesn't start with action/filter)
    public function regularMethod()
    {
        return 'regular';
    }

    // This should NOT be registered (too short)
    public function action()
    {
        return 'short';
    }
}

class ControllerAutoHooksTest extends TestCase
{
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock WordPress functions
        if (!function_exists('add_action')) {
            function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
                global $wp_actions;
                $wp_actions[] = compact('hook', 'callback', 'priority', 'accepted_args');
                return true;
            }
        }

        if (!function_exists('add_filter')) {
            function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
                global $wp_filters;
                $wp_filters[] = compact('hook', 'callback', 'priority', 'accepted_args');
                return true;
            }
        }

        // Reset globals
        global $wp_actions, $wp_filters;
        $wp_actions = [];
        $wp_filters = [];
        
        $this->controller = new TestAutoHooksController();
    }

    public function testAutoRegistersActions()
    {
        global $wp_actions;

        $this->assertCount(3, $wp_actions);

        // Check wp_init action with custom priority
        $wpInitAction = array_filter($wp_actions, function($action) {
            return $action['hook'] === 'wp_init';
        });
        $this->assertCount(1, $wpInitAction);
        $wpInitAction = array_values($wpInitAction)[0];
        $this->assertEquals(20, $wpInitAction['priority']); // Custom priority from docblock
        $this->assertEquals(0, $wpInitAction['accepted_args']); // No parameters

        // Check admin_menu action with default priority
        $adminMenuAction = array_filter($wp_actions, function($action) {
            return $action['hook'] === 'admin_menu';
        });
        $this->assertCount(1, $adminMenuAction);
        $adminMenuAction = array_values($adminMenuAction)[0];
        $this->assertEquals(10, $adminMenuAction['priority']); // Default priority
        $this->assertEquals(0, $adminMenuAction['accepted_args']); // No parameters

        // Check custom_priority action with parameter-based priority
        $customPriorityAction = array_filter($wp_actions, function($action) {
            return $action['hook'] === 'custom_priority';
        });
        $this->assertCount(1, $customPriorityAction);
        $customPriorityAction = array_values($customPriorityAction)[0];
        $this->assertEquals(15, $customPriorityAction['priority']); // Priority from parameter default
        $this->assertEquals(0, $customPriorityAction['accepted_args']); // Priority param excluded from count
    }

    public function testAutoRegistersFilters()
    {
        global $wp_filters;

        $this->assertCount(3, $wp_filters);

        // Check the_title filter with custom args
        $theTitleFilter = array_filter($wp_filters, function($filter) {
            return $filter['hook'] === 'the_title';
        });
        $this->assertCount(1, $theTitleFilter);
        $theTitleFilter = array_values($theTitleFilter)[0];
        $this->assertEquals(10, $theTitleFilter['priority']); // Default priority
        $this->assertEquals(2, $theTitleFilter['accepted_args']); // Custom args from docblock

        // Check the_content filter
        $theContentFilter = array_filter($wp_filters, function($filter) {
            return $filter['hook'] === 'the_content';
        });
        $this->assertCount(1, $theContentFilter);
        $theContentFilter = array_values($theContentFilter)[0];
        $this->assertEquals(10, $theContentFilter['priority']); // Default priority
        $this->assertEquals(1, $theContentFilter['accepted_args']); // Parameter count

        // Check with_priority filter with parameter-based priority
        $withPriorityFilter = array_filter($wp_filters, function($filter) {
            return $filter['hook'] === 'with_priority';
        });
        $this->assertCount(1, $withPriorityFilter);
        $withPriorityFilter = array_values($withPriorityFilter)[0];
        $this->assertEquals(25, $withPriorityFilter['priority']); // Priority from parameter default
        $this->assertEquals(2, $withPriorityFilter['accepted_args']); // Priority param excluded from count
    }

    public function testConvertMethodNameToHook()
    {
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('convertMethodNameToHook');
        $method->setAccessible(true);

        $this->assertEquals('wp_init', $method->invokeArgs($this->controller, ['WpInit']));
        $this->assertEquals('admin_menu', $method->invokeArgs($this->controller, ['AdminMenu']));
        $this->assertEquals('the_title', $method->invokeArgs($this->controller, ['TheTitle']));
        $this->assertEquals('custom_hook_name', $method->invokeArgs($this->controller, ['CustomHookName']));
    }

    public function testCallbacksAreCorrect()
    {
        global $wp_actions, $wp_filters;

        // Test action callback
        $wpInitAction = array_filter($wp_actions, function($action) {
            return $action['hook'] === 'wp_init';
        });
        $wpInitAction = array_values($wpInitAction)[0];
        $this->assertEquals([$this->controller, 'actionWpInit'], $wpInitAction['callback']);

        // Test filter callback
        $theTitleFilter = array_filter($wp_filters, function($filter) {
            return $filter['hook'] === 'the_title';
        });
        $theTitleFilter = array_values($theTitleFilter)[0];
        $this->assertEquals([$this->controller, 'filterTheTitle'], $theTitleFilter['callback']);
    }

    protected function tearDown(): void
    {
        global $wp_actions, $wp_filters;
        $wp_actions = [];
        $wp_filters = [];
        parent::tearDown();
    }
}