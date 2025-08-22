<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Adz\WP\ADZ;

class CoreTest extends TestCase
{
    public function testADZClassExists()
    {
        $this->assertTrue(class_exists('Adz\Core\ADZ'));
    }
    
    public function testADZHasRequiredMethods()
    {
        $this->assertTrue(method_exists('Adz\Core\ADZ', 'pluginize'));
        $this->assertTrue(method_exists('Adz\Core\ADZ', 'build'));
    }
    
    public function testADZHasStaticProperties()
    {
        $reflection = new \ReflectionClass('Adz\Core\ADZ');
        
        $this->assertTrue($reflection->hasProperty('env'));
        $this->assertTrue($reflection->hasProperty('path'));
        $this->assertTrue($reflection->hasProperty('conf'));
        $this->assertTrue($reflection->hasProperty('plugin'));
    }
    
    public function testBuildMethodCreatesInstance()
    {
        $mockClass = new class {
            public function __construct() {}
        };
        
        $className = get_class($mockClass);
        $instance = ADZ::build($className);
        
        $this->assertInstanceOf($className, $instance);
    }
}