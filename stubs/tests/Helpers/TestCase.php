<?php

namespace Tests\Helpers;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Mockery;

/**
 * Base test case for all framework tests
 * 
 * Provides common functionality and utilities for testing
 */
abstract class TestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        if (class_exists('\Brain\Monkey')) {
            \Brain\Monkey\setUp();
        }
        
        // Set up any common test fixtures
        $this->setUpTestEnvironment();
    }

    protected function tearDown(): void
    {
        if (class_exists('\Brain\Monkey')) {
            \Brain\Monkey\tearDown();
        }
        
        Mockery::close();
        
        parent::tearDown();
    }

    /**
     * Set up test environment
     */
    protected function setUpTestEnvironment(): void
    {
        // Define common WordPress constants if not already defined
        if (!defined('WP_DEBUG')) {
            define('WP_DEBUG', true);
        }
        
        if (!defined('WP_DEBUG_LOG')) {
            define('WP_DEBUG_LOG', false);
        }
        
        if (!defined('ABSPATH')) {
            define('ABSPATH', '/tmp/wordpress/');
        }
    }

    /**
     * Create a mock WordPress user
     */
    protected function createMockUser(array $userData = []): \stdClass
    {
        $defaultUser = [
            'ID' => 1,
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => 'password',
            'user_nicename' => 'testuser',
            'user_url' => '',
            'user_registered' => '2023-01-01 00:00:00',
            'user_activation_key' => '',
            'user_status' => 0,
            'display_name' => 'Test User',
            'roles' => ['subscriber']
        ];

        $user = (object) array_merge($defaultUser, $userData);
        
        return $user;
    }

    /**
     * Create a mock WordPress post
     */
    protected function createMockPost(array $postData = []): \stdClass
    {
        $defaultPost = [
            'ID' => 1,
            'post_author' => '1',
            'post_date' => '2023-01-01 00:00:00',
            'post_date_gmt' => '2023-01-01 00:00:00',
            'post_content' => 'Test post content',
            'post_title' => 'Test Post',
            'post_excerpt' => '',
            'post_status' => 'publish',
            'comment_status' => 'open',
            'ping_status' => 'open',
            'post_password' => '',
            'post_name' => 'test-post',
            'to_ping' => '',
            'pinged' => '',
            'post_modified' => '2023-01-01 00:00:00',
            'post_modified_gmt' => '2023-01-01 00:00:00',
            'post_content_filtered' => '',
            'post_parent' => 0,
            'guid' => 'http://example.org/?p=1',
            'menu_order' => 0,
            'post_type' => 'post',
            'post_mime_type' => '',
            'comment_count' => '0'
        ];

        $post = (object) array_merge($defaultPost, $postData);
        
        return $post;
    }

    /**
     * Assert that a WordPress hook is registered
     */
    protected function assertHookRegistered(string $hook, $callback, int $priority = 10): void
    {
        if (function_exists('has_action')) {
            $this->assertTrue(has_action($hook, $callback) !== false, "Hook '{$hook}' is not registered");
        } else {
            $this->markTestSkipped('WordPress functions not available');
        }
    }

    /**
     * Assert that a WordPress filter is registered
     */
    protected function assertFilterRegistered(string $filter, $callback, int $priority = 10): void
    {
        if (function_exists('has_filter')) {
            $this->assertTrue(has_filter($filter, $callback) !== false, "Filter '{$filter}' is not registered");
        } else {
            $this->markTestSkipped('WordPress functions not available');
        }
    }

    /**
     * Mock WordPress function calls
     */
    protected function mockWordPressFunction(string $function, $returnValue = null): void
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\when($function)->justReturn($returnValue);
        }
    }

    /**
     * Mock WordPress function calls with expectations
     */
    protected function expectWordPressFunction(string $function, $returnValue = null): void
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\expect($function)->andReturn($returnValue);
        }
    }

    /**
     * Create a temporary file for testing
     */
    protected function createTempFile(string $content = '', string $suffix = '.tmp'): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'adz_test_') . $suffix;
        file_put_contents($tempFile, $content);
        
        return $tempFile;
    }

    /**
     * Clean up temporary files
     */
    protected function cleanupTempFiles(array $files): void
    {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Get fixture content
     */
    protected function getFixture(string $filename): string
    {
        $fixturePath = __DIR__ . '/../fixtures/' . $filename;
        
        if (!file_exists($fixturePath)) {
            throw new \RuntimeException("Fixture file not found: {$fixturePath}");
        }
        
        return file_get_contents($fixturePath);
    }

    /**
     * Assert array contains all expected keys
     */
    protected function assertArrayHasKeys(array $expectedKeys, array $array, string $message = ''): void
    {
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $array, $message ?: "Array missing key: {$key}");
        }
    }

    /**
     * Assert that a class implements an interface
     */
    protected function assertImplementsInterface(string $interface, $object): void
    {
        $this->assertInstanceOf($interface, $object);
    }

    /**
     * Assert that a method exists on a class
     */
    protected function assertMethodExists(string $method, $object): void
    {
        $this->assertTrue(
            method_exists($object, $method),
            sprintf('Method %s does not exist on %s', $method, get_class($object))
        );
    }

    /**
     * Assert that a property exists on a class
     */
    protected function assertPropertyExists(string $property, $object): void
    {
        $this->assertTrue(
            property_exists($object, $property),
            sprintf('Property %s does not exist on %s', $property, get_class($object))
        );
    }
}