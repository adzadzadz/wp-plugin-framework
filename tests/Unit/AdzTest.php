<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the main Adz class
 */
class AdzTest extends TestCase
{
    public function testAdzClassExists()
    {
        $this->assertTrue(class_exists('\Adz'));
    }
    
    public function testAdzVersionMethod()
    {
        $version = \Adz::version();
        $this->assertIsString($version);
        $this->assertEquals('2.0.0', $version);
    }
    
    public function testAdzBindAndResolve()
    {
        $testValue = 'test_value';
        \Adz::bind('test_key', $testValue);
        
        $resolved = \Adz::resolve('test_key');
        $this->assertEquals($testValue, $resolved);
    }
    
    public function testAdzResolveWithDefault()
    {
        $default = 'default_value';
        $resolved = \Adz::resolve('non_existent_key', $default);
        $this->assertEquals($default, $resolved);
    }
    
    public function testAdzBindWithCallable()
    {
        \Adz::bind('callable_test', function() {
            return 'callable_result';
        });
        
        $resolved = \Adz::resolve('callable_test');
        $this->assertEquals('callable_result', $resolved);
    }
    
    public function testAdzSingleton()
    {
        \Adz::singleton('singleton_test', function() {
            return new \stdClass();
        });
        
        $instance1 = \Adz::service('singleton_test');
        $instance2 = \Adz::service('singleton_test');
        
        $this->assertSame($instance1, $instance2);
    }
    
    public function testAdzMakeClass()
    {
        $instance = \Adz::make('stdClass');
        $this->assertInstanceOf('stdClass', $instance);
    }
    
    public function testAdzMakeNonExistentClass()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class NonExistentClass not found');
        \Adz::make('NonExistentClass');
    }
    
    public function testAdzConfig()
    {
        $config = \Adz::config();
        $this->assertInstanceOf('AdzWP\Core\Config', $config);
    }
    
    public function testAdzGetAndSet()
    {
        \Adz::set('test_config', 'config_value');
        $value = \Adz::get('test_config');
        $this->assertEquals('config_value', $value);
    }
    
    public function testAdzGetWithDefault()
    {
        $value = \Adz::get('non_existent_config', 'default_config');
        $this->assertEquals('default_config', $value);
    }
}