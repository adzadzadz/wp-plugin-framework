<?php

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use AdzWP\Core\Core;

/**
 * Unit tests for the Core abstract class
 */
class CoreTest extends TestCase
{
    private $coreInstance;
    
    protected function setUp(): void
    {
        // Create a concrete implementation for testing
        $this->coreInstance = new class extends Core {
            public $actions = [
                'init' => 'initCallback',
                'wp_loaded' => [
                    'callback' => 'loadedCallback',
                    'priority' => 20,
                    'accepted_args' => 2
                ]
            ];
            
            public $filters = [
                'the_content' => 'contentFilter'
            ];
            
            public function initCallback() {
                return 'init called';
            }
            
            public function loadedCallback() {
                return 'loaded called';
            }
            
            public function contentFilter($content) {
                return $content . ' [filtered]';
            }
        };
    }
    
    public function testConstructorWithArgs()
    {
        $testCore = new class(['pluginPath' => '/test/path']) extends Core {
            // Empty implementation for testing
        };
        
        $this->assertEquals('/test/path', $testCore->pluginPath);
    }
    
    public function testBindAndGet()
    {
        $this->coreInstance->bind('test_key', 'test_value');
        $value = $this->coreInstance->get('test_key');
        
        $this->assertEquals('test_value', $value);
    }
    
    public function testGetWithDefault()
    {
        $value = $this->coreInstance->get('non_existent', 'default');
        $this->assertEquals('default', $value);
    }
    
    public function testActionRegistration()
    {
        // Test that actions property exists and has expected structure
        $this->assertIsArray($this->coreInstance->actions);
        $this->assertArrayHasKey('init', $this->coreInstance->actions);
        $this->assertArrayHasKey('wp_loaded', $this->coreInstance->actions);
    }
    
    public function testFilterRegistration()
    {
        // Test that filters property exists and has expected structure
        $this->assertIsArray($this->coreInstance->filters);
        $this->assertArrayHasKey('the_content', $this->coreInstance->filters);
    }
    
    public function testCallbackMethods()
    {
        $this->assertEquals('init called', $this->coreInstance->initCallback());
        $this->assertEquals('loaded called', $this->coreInstance->loadedCallback());
        $this->assertEquals('test content [filtered]', $this->coreInstance->contentFilter('test content'));
    }
}