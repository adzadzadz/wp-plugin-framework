<?php

namespace Tests\Unit;

use Tests\Helpers\FrameworkTestCase;
use AdzWP\Controller;

/**
 * Unit tests for the Controller class
 */
class ControllerTest extends FrameworkTestCase
{
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = $this->createMockController();
    }

    public function testControllerInstantiation()
    {
        $this->assertInstanceOf(Controller::class, $this->controller);
        $this->assertClassExtends(get_class($this->controller), Controller::class);
    }

    public function testControllerHasRequiredProperties()
    {
        $this->assertPropertyExists('actions', $this->controller);
        $this->assertPropertyExists('filters', $this->controller);
        
        $this->assertIsArray($this->controller->actions);
        $this->assertIsArray($this->controller->filters);
    }

    public function testControllerHasHookManagementMethods()
    {
        $this->assertHookRegistrationWorks($this->controller);
    }

    public function testAddActionMethod()
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\expect('add_action')
                ->once()
                ->with('test_hook', [$this->controller, 'testInit'], 10, 1)
                ->andReturn(true);
                
            $result = $this->controller->addAction('test_hook', 'testInit');
            $this->assertTrue($result);
        }
    }

    public function testAddFilterMethod()
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\expect('add_filter')
                ->once()
                ->with('test_filter', [$this->controller, 'testFilter'], 10, 1)
                ->andReturn(true);
                
            $result = $this->controller->addFilter('test_filter', 'testFilter');
            $this->assertTrue($result);
        }
    }

    public function testRemoveActionMethod()
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\expect('remove_action')
                ->once()
                ->with('test_hook', [$this->controller, 'testInit'], 10)
                ->andReturn(true);
                
            $result = $this->controller->removeAction('test_hook', 'testInit');
            $this->assertTrue($result);
        }
    }

    public function testRemoveFilterMethod()
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\expect('remove_filter')
                ->once()
                ->with('test_filter', [$this->controller, 'testFilter'], 10)
                ->andReturn(true);
                
            $result = $this->controller->removeFilter('test_filter', 'testFilter');
            $this->assertTrue($result);
        }
    }

    public function testDoActionMethod()
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\expect('do_action')
                ->once()
                ->with('test_action', 'arg1', 'arg2')
                ->andReturn(null);
                
            $this->controller->doAction('test_action', 'arg1', 'arg2');
        }
    }

    public function testApplyFiltersMethod()
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\expect('apply_filters')
                ->once()
                ->with('test_filter', 'value', 'arg1')
                ->andReturn('filtered_value');
                
            $result = $this->controller->applyFilters('test_filter', 'value', 'arg1');
            $this->assertEquals('filtered_value', $result);
        }
    }

    public function testHasActionMethod()
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\expect('has_action')
                ->once()
                ->with('test_hook')
                ->andReturn(10); // Priority when hook exists
                
            $result = $this->controller->hasAction('test_hook');
            $this->assertEquals(10, $result);
        }
    }

    public function testHasFilterMethod()
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\expect('has_filter')
                ->once()
                ->with('test_filter')
                ->andReturn(10); // Priority when filter exists
                
            $result = $this->controller->hasFilter('test_filter');
            $this->assertEquals(10, $result);
        }
    }

    public function testWordPressHelperMethods()
    {
        $this->assertMethodExists('getCurrentUserId', $this->controller);
        $this->assertMethodExists('currentUserCan', $this->controller);
        $this->assertMethodExists('isAdmin', $this->controller);
        $this->assertMethodExists('isFrontend', $this->controller);
        $this->assertMethodExists('wpDie', $this->controller);
        $this->assertMethodExists('sanitizeText', $this->controller);
        $this->assertMethodExists('escapeHtml', $this->controller);
        $this->assertMethodExists('escapeUrl', $this->controller);
        $this->assertMethodExists('verifyNonce', $this->controller);
        $this->assertMethodExists('createNonce', $this->controller);
    }

    public function testGetCurrentUserId()
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\expect('get_current_user_id')
                ->once()
                ->andReturn(123);
                
            $result = $this->controller->getCurrentUserId();
            $this->assertEquals(123, $result);
        }
    }

    public function testCurrentUserCan()
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\expect('current_user_can')
                ->once()
                ->with('manage_options')
                ->andReturn(true);
                
            $result = $this->controller->currentUserCan('manage_options');
            $this->assertTrue($result);
        }
    }

    public function testIsAdmin()
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\expect('is_admin')
                ->once()
                ->andReturn(true);
                
            $result = $this->controller->isAdmin();
            $this->assertTrue($result);
        }
    }

    public function testIsFrontend()
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\expect('is_admin')
                ->once()
                ->andReturn(false);
                
            $result = $this->controller->isFrontend();
            $this->assertTrue($result);
        }
    }

    public function testSanitizeText()
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\expect('sanitize_text_field')
                ->once()
                ->with('test <script>')
                ->andReturn('test ');
                
            $result = $this->controller->sanitizeText('test <script>');
            $this->assertEquals('test ', $result);
        }
    }

    public function testEscapeHtml()
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\expect('esc_html')
                ->once()
                ->with('<script>alert("xss")</script>')
                ->andReturn('&lt;script&gt;alert("xss")&lt;/script&gt;');
                
            $result = $this->controller->escapeHtml('<script>alert("xss")</script>');
            $this->assertEquals('&lt;script&gt;alert("xss")&lt;/script&gt;', $result);
        }
    }

    public function testEscapeUrl()
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\expect('esc_url')
                ->once()
                ->with('javascript:alert("xss")')
                ->andReturn('');
                
            $result = $this->controller->escapeUrl('javascript:alert("xss")');
            $this->assertEquals('', $result);
        }
    }

    public function testVerifyNonce()
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\expect('wp_verify_nonce')
                ->once()
                ->with('test-nonce', 'test-action')
                ->andReturn(1);
                
            $result = $this->controller->verifyNonce('test-nonce', 'test-action');
            $this->assertEquals(1, $result);
        }
    }

    public function testCreateNonce()
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\expect('wp_create_nonce')
                ->once()
                ->with('test-action')
                ->andReturn('generated-nonce');
                
            $result = $this->controller->createNonce('test-action');
            $this->assertEquals('generated-nonce', $result);
        }
    }
}