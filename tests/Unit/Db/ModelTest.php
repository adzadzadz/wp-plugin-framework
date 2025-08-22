<?php

namespace Tests\Unit\Db;

use PHPUnit\Framework\TestCase;
use AdzWP\Db\Model;

/**
 * Unit tests for the Db Model class
 */
class ModelTest extends TestCase
{
    private $modelInstance;
    
    protected function setUp(): void
    {
        // Create a concrete implementation for testing
        $this->modelInstance = new class extends Model {
            protected $table = 'test_table';
            protected $primaryKey = 'id';
            protected $fillable = ['name', 'email', 'status'];
            protected $guarded = ['id', 'created_at'];
            
            // Override abstract method
            public function save(): bool {
                return true;
            }
        };
    }
    
    public function testModelTableProperty()
    {
        $reflection = new \ReflectionClass($this->modelInstance);
        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);
        
        $this->assertEquals('test_table', $tableProperty->getValue($this->modelInstance));
    }
    
    public function testModelPrimaryKeyProperty()
    {
        $reflection = new \ReflectionClass($this->modelInstance);
        $primaryKeyProperty = $reflection->getProperty('primaryKey');
        $primaryKeyProperty->setAccessible(true);
        
        $this->assertEquals('id', $primaryKeyProperty->getValue($this->modelInstance));
    }
    
    public function testModelFillableProperty()
    {
        $reflection = new \ReflectionClass($this->modelInstance);
        $fillableProperty = $reflection->getProperty('fillable');
        $fillableProperty->setAccessible(true);
        
        $fillable = $fillableProperty->getValue($this->modelInstance);
        $this->assertIsArray($fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('status', $fillable);
    }
    
    public function testModelGuardedProperty()
    {
        $reflection = new \ReflectionClass($this->modelInstance);
        $guardedProperty = $reflection->getProperty('guarded');
        $guardedProperty->setAccessible(true);
        
        $guarded = $guardedProperty->getValue($this->modelInstance);
        $this->assertIsArray($guarded);
        $this->assertContains('id', $guarded);
        $this->assertContains('created_at', $guarded);
    }
    
    public function testModelHasAttributesProperty()
    {
        $reflection = new \ReflectionClass($this->modelInstance);
        $this->assertTrue($reflection->hasProperty('attributes'));
        
        $attributesProperty = $reflection->getProperty('attributes');
        $attributesProperty->setAccessible(true);
        $this->assertIsArray($attributesProperty->getValue($this->modelInstance));
    }
    
    public function testSaveMethod()
    {
        $result = $this->modelInstance->save();
        $this->assertTrue($result);
    }
    
    public function testModelInheritsFromDbModel()
    {
        $this->assertInstanceOf(Model::class, $this->modelInstance);
    }
}