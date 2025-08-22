<?php

namespace Tests\Unit;

use Tests\Helpers\FrameworkTestCase;
use AdzWP\Core\Config;

/**
 * Unit tests for the Config class
 */
class ConfigTest extends FrameworkTestCase
{
    protected $config;
    protected $tempConfigPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create temporary config directory
        $this->tempConfigPath = sys_get_temp_dir() . '/adz_config_test_' . uniqid();
        mkdir($this->tempConfigPath, 0755, true);
        
        $this->config = $this->createMockConfig();
    }

    protected function tearDown(): void
    {
        // Clean up temporary files
        if (is_dir($this->tempConfigPath)) {
            $this->rmdirRecursive($this->tempConfigPath);
        }
        
        parent::tearDown();
    }

    public function testConfigInstantiation()
    {
        $this->assertInstanceOf(Config::class, $this->config);
    }

    public function testGetConfigValue()
    {
        $value = $this->config->get('plugin.name');
        $this->assertEquals('Test Plugin', $value);
    }

    public function testGetConfigValueWithDefault()
    {
        $value = $this->config->get('non.existent.key', 'default_value');
        $this->assertEquals('default_value', $value);
    }

    public function testGetTopLevelConfigValue()
    {
        $value = $this->config->get('plugin');
        $this->assertIsArray($value);
        $this->assertArrayHasKey('name', $value);
    }

    public function testGetAllConfig()
    {
        $allConfig = $this->config->get();
        $this->assertIsArray($allConfig);
        $this->assertArrayHasKey('plugin', $allConfig);
        $this->assertArrayHasKey('admin', $allConfig);
    }

    public function testSetConfigValue()
    {
        $result = $this->config->set('test.key', 'test_value');
        $this->assertTrue($result);
    }

    public function testHasConfigValue()
    {
        $result = $this->config->has('plugin.name');
        $this->assertTrue($result);
    }

    public function testHasNonExistentConfigValue()
    {
        $result = $this->config->has('non.existent.key');
        $this->assertFalse($result);
    }

    public function testGetEnvironmentVariable()
    {
        // Set a test environment variable
        putenv('TEST_ENV_VAR=test_value');
        
        $value = $this->config->getEnv('TEST_ENV_VAR', 'default');
        $this->assertEquals('test_value', $value);
        
        // Clean up
        putenv('TEST_ENV_VAR');
    }

    public function testGetNonExistentEnvironmentVariable()
    {
        $value = $this->config->getEnv('NON_EXISTENT_VAR', 'default_value');
        $this->assertEquals('default_value', $value);
    }

    public function testParseEnvironmentBooleanTrue()
    {
        putenv('TEST_BOOL_TRUE=true');
        $value = $this->config->getEnv('TEST_BOOL_TRUE');
        $this->assertTrue($value);
        putenv('TEST_BOOL_TRUE');
    }

    public function testParseEnvironmentBooleanFalse()
    {
        putenv('TEST_BOOL_FALSE=false');
        $value = $this->config->getEnv('TEST_BOOL_FALSE');
        $this->assertFalse($value);
        putenv('TEST_BOOL_FALSE');
    }

    public function testParseEnvironmentInteger()
    {
        putenv('TEST_INT=123');
        $value = $this->config->getEnv('TEST_INT');
        $this->assertIsInt($value);
        $this->assertEquals(123, $value);
        putenv('TEST_INT');
    }

    public function testParseEnvironmentFloat()
    {
        putenv('TEST_FLOAT=123.45');
        $value = $this->config->getEnv('TEST_FLOAT');
        $this->assertIsFloat($value);
        $this->assertEquals(123.45, $value);
        putenv('TEST_FLOAT');
    }

    public function testParseEnvironmentQuotedString()
    {
        putenv('TEST_QUOTED="quoted value"');
        $value = $this->config->getEnv('TEST_QUOTED');
        $this->assertEquals('quoted value', $value);
        putenv('TEST_QUOTED');
    }

    protected function rmdirRecursive($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->rmdirRecursive($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
}