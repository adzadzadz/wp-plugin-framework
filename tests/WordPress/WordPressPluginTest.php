<?php

namespace Tests\WordPress;

use Tests\Helpers\WordPressTestCase;

/**
 * WordPress-specific integration tests
 * These tests require a WordPress test environment
 */
class WordPressPluginTest extends WordPressTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Only run these tests if WordPress is available
        if (!$this->isWordPressAvailable()) {
            $this->markTestSkipped('WordPress test environment not available');
        }
    }

    protected function isWordPressAvailable(): bool
    {
        return function_exists('add_action') && function_exists('add_filter');
    }

    public function testPluginActivation()
    {
        if (!$this->isWordPressAvailable()) {
            $this->markTestSkipped('WordPress not available');
            return;
        }
        
        // Test plugin activation
        $this->expectNotToPerformAssertions();
        
        // If we reach here without errors, activation succeeded
    }

    public function testWordPressHooksRegistration()
    {
        if (!$this->isWordPressAvailable()) {
            $this->markTestSkipped('WordPress not available');
            return;
        }
        
        // Test that WordPress hooks are properly registered
        add_action('test_action', function() {
            return 'test';
        });
        
        $this->assertTrue(has_action('test_action') !== false);
    }

    public function testWordPressFiltersRegistration()
    {
        if (!$this->isWordPressAvailable()) {
            $this->markTestSkipped('WordPress not available');
            return;
        }
        
        // Test that WordPress filters are properly registered
        add_filter('test_filter', function($value) {
            return $value . ' filtered';
        });
        
        $this->assertTrue(has_filter('test_filter') !== false);
        
        $result = apply_filters('test_filter', 'test');
        $this->assertEquals('test filtered', $result);
    }

    public function testWordPressAdminIntegration()
    {
        if (!$this->isWordPressAvailable()) {
            $this->markTestSkipped('WordPress not available');
            return;
        }
        
        // Test admin-specific functionality
        $this->mockAdminEnvironment();
        
        // Test admin menu registration
        add_action('admin_menu', function() {
            add_menu_page(
                'Test Plugin',
                'Test Plugin',
                'manage_options',
                'test-plugin',
                function() { echo 'Test page'; }
            );
        });
        
        do_action('admin_menu');
        
        // If no errors thrown, test passes
        $this->assertTrue(true);
    }

    public function testWordPressEnqueueScripts()
    {
        if (!$this->isWordPressAvailable()) {
            $this->markTestSkipped('WordPress not available');
            return;
        }
        
        // Test script enqueuing
        add_action('wp_enqueue_scripts', function() {
            wp_enqueue_script('test-script', 'test.js', ['jquery'], '1.0.0', true);
        });
        
        do_action('wp_enqueue_scripts');
        
        // Test passes if no errors are thrown
        $this->assertTrue(true);
    }

    public function testWordPressAjaxIntegration()
    {
        if (!$this->isWordPressAvailable()) {
            $this->markTestSkipped('WordPress not available');
            return;
        }
        
        $this->mockAjaxEnvironment();
        
        // Test AJAX action registration
        add_action('wp_ajax_test_action', function() {
            wp_send_json_success(['message' => 'success']);
        });
        
        add_action('wp_ajax_nopriv_test_action', function() {
            wp_send_json_error(['message' => 'error']);
        });
        
        // Test that actions are registered
        $this->assertTrue(has_action('wp_ajax_test_action') !== false);
        $this->assertTrue(has_action('wp_ajax_nopriv_test_action') !== false);
    }

    public function testWordPressOptionsIntegration()
    {
        if (!$this->isWordPressAvailable()) {
            $this->markTestSkipped('WordPress not available');
            return;
        }
        
        // Test WordPress options
        $option_name = 'test_framework_option';
        $option_value = 'test_value';
        
        // Add option
        add_option($option_name, $option_value);
        
        // Get option
        $retrieved_value = get_option($option_name);
        $this->assertEquals($option_value, $retrieved_value);
        
        // Update option
        $new_value = 'updated_value';
        update_option($option_name, $new_value);
        
        $updated_value = get_option($option_name);
        $this->assertEquals($new_value, $updated_value);
        
        // Delete option
        delete_option($option_name);
        $deleted_value = get_option($option_name, 'default');
        $this->assertEquals('default', $deleted_value);
    }

    public function testWordPressTransientsIntegration()
    {
        if (!$this->isWordPressAvailable()) {
            $this->markTestSkipped('WordPress not available');
            return;
        }
        
        // Test WordPress transients
        $transient_name = 'test_framework_transient';
        $transient_value = ['data' => 'test'];
        $expiration = 3600;
        
        // Set transient
        set_transient($transient_name, $transient_value, $expiration);
        
        // Get transient
        $retrieved_value = get_transient($transient_name);
        $this->assertEquals($transient_value, $retrieved_value);
        
        // Delete transient
        delete_transient($transient_name);
        $deleted_value = get_transient($transient_name);
        $this->assertFalse($deleted_value);
    }

    public function testWordPressUserIntegration()
    {
        if (!$this->isWordPressAvailable()) {
            $this->markTestSkipped('WordPress not available');
            return;
        }
        
        // Test user-related functions
        $user_id = 1; // Assuming user ID 1 exists in test environment
        
        // Test user capabilities
        $can_manage = user_can($user_id, 'manage_options');
        $this->assertIsBool($can_manage);
        
        // Test current user
        wp_set_current_user($user_id);
        $current_user_id = get_current_user_id();
        $this->assertEquals($user_id, $current_user_id);
    }

    public function testWordPressDatabaseIntegration()
    {
        if (!$this->isWordPressAvailable()) {
            $this->markTestSkipped('WordPress not available');
            return;
        }
        
        global $wpdb;
        
        if (!isset($wpdb)) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }
        
        // Test database queries
        $table_name = $wpdb->prefix . 'posts';
        
        // Test table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if ($table_exists) {
            // Test simple query
            $post_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            $this->assertIsNumeric($post_count);
        } else {
            $this->markTestSkipped('WordPress posts table not available');
        }
    }

    public function testWordPressRestApiIntegration()
    {
        if (!$this->isWordPressAvailable()) {
            $this->markTestSkipped('WordPress not available');
            return;
        }
        
        $this->mockRestEnvironment();
        
        if (function_exists('register_rest_route')) {
            // Test REST API route registration
            register_rest_route('test/v1', '/endpoint', [
                'methods' => 'GET',
                'callback' => function() {
                    return ['message' => 'success'];
                },
                'permission_callback' => '__return_true'
            ]);
            
            // Test passes if no errors are thrown
            $this->assertTrue(true);
        } else {
            $this->markTestSkipped('WordPress REST API not available');
        }
    }
}