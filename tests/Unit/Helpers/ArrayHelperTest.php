<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use AdzWP\Helpers\ArrayHelper;

/**
 * Unit tests for the ArrayHelper class
 */
class ArrayHelperTest extends TestCase
{
    private ArrayHelper $arrayHelper;
    
    protected function setUp(): void
    {
        $this->arrayHelper = new ArrayHelper();
    }
    
    public function testIsAssociativeWithAssociativeArray()
    {
        $array = ['name' => 'John', 'age' => 30, 'city' => 'New York'];
        $result = $this->arrayHelper->isAssociative($array);
        $this->assertTrue($result);
    }
    
    public function testIsAssociativeWithIndexedArray()
    {
        $array = [1, 2, 3, 4, 5];
        $result = $this->arrayHelper->isAssociative($array);
        $this->assertFalse($result);
    }
    
    public function testIsAssociativeWithMixedArray()
    {
        $array = [0 => 'first', 'name' => 'John', 1 => 'second'];
        $result = $this->arrayHelper->isAssociative($array);
        $this->assertTrue($result);
    }
    
    public function testIsAssociativeWithEmptyArray()
    {
        $array = [];
        $result = $this->arrayHelper->isAssociative($array);
        $this->assertFalse($result);
    }
    
    public function testKeysEqualWithSameKeys()
    {
        $array1 = ['name' => 'John', 'age' => 30];
        $array2 = ['name' => 'Jane', 'age' => 25];
        $result = $this->arrayHelper->keysEqual($array1, $array2);
        $this->assertTrue($result);
    }
    
    public function testKeysEqualWithDifferentKeys()
    {
        $array1 = ['name' => 'John', 'age' => 30];
        $array2 = ['name' => 'Jane', 'city' => 'Boston'];
        $result = $this->arrayHelper->keysEqual($array1, $array2);
        $this->assertFalse($result);
    }
    
    public function testKeysEqualWithDifferentKeyCount()
    {
        $array1 = ['name' => 'John', 'age' => 30];
        $array2 = ['name' => 'Jane'];
        $result = $this->arrayHelper->keysEqual($array1, $array2);
        $this->assertFalse($result);
    }
    
    public function testKeysEqualWithEmptyArrays()
    {
        $array1 = [];
        $array2 = [];
        $result = $this->arrayHelper->keysEqual($array1, $array2);
        $this->assertTrue($result);
    }
    
    public function testKeysEqualWithIndexedArrays()
    {
        $array1 = [1, 2, 3];
        $array2 = [4, 5, 6];
        $result = $this->arrayHelper->keysEqual($array1, $array2);
        $this->assertTrue($result);
    }
}