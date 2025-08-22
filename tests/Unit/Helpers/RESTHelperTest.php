<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use AdzWP\Helpers\RESTHelper;

/**
 * Unit tests for the RESTHelper class
 */
class RESTHelperTest extends TestCase
{
    private RESTHelper $restHelper;
    
    protected function setUp(): void
    {
        $this->restHelper = new RESTHelper();
    }
    
    public function testDefaultType()
    {
        $this->assertEquals('json', $this->restHelper->type);
    }
    
    public function testDefaultData()
    {
        $this->assertIsArray($this->restHelper->data);
        $this->assertEmpty($this->restHelper->data);
    }
    
    public function testClientProperty()
    {
        $this->assertNull($this->restHelper->client);
    }
    
    public function testUrlProperty()
    {
        $this->assertNull($this->restHelper->url);
    }
    
    public function testSetType()
    {
        $this->restHelper->type = 'xml';
        $this->assertEquals('xml', $this->restHelper->type);
    }
    
    public function testSetUrl()
    {
        $testUrl = 'https://api.example.com';
        $this->restHelper->url = $testUrl;
        $this->assertEquals($testUrl, $this->restHelper->url);
    }
    
    public function testSetData()
    {
        $testData = ['key1' => 'value1', 'key2' => 'value2'];
        $this->restHelper->data = $testData;
        $this->assertEquals($testData, $this->restHelper->data);
    }
    
    public function testAddDataItem()
    {
        $this->restHelper->data['new_key'] = 'new_value';
        $this->assertArrayHasKey('new_key', $this->restHelper->data);
        $this->assertEquals('new_value', $this->restHelper->data['new_key']);
    }
    
    public function testMultipleDataItems()
    {
        $this->restHelper->data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30
        ];
        
        $this->assertCount(3, $this->restHelper->data);
        $this->assertEquals('John Doe', $this->restHelper->data['name']);
        $this->assertEquals('john@example.com', $this->restHelper->data['email']);
        $this->assertEquals(30, $this->restHelper->data['age']);
    }
}