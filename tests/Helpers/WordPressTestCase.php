<?php

namespace Tests\Helpers;

use Tests\Helpers\TestCase;

/**
 * WordPress-specific test case
 * 
 * Extends the base test case with WordPress-specific functionality
 */
abstract class WordPressTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up WordPress-specific mocks
        $this->setUpWordPressMocks();
    }

    /**
     * Set up common WordPress function mocks
     */
    protected function setUpWordPressMocks(): void
    {
        if (!class_exists('\Brain\Monkey\Functions')) {
            return;
        }

        // Mock common WordPress functions
        \Brain\Monkey\Functions\when('wp_die')->justReturn(false);
        \Brain\Monkey\Functions\when('__')->returnArg(1);
        \Brain\Monkey\Functions\when('esc_html')->returnArg(1);
        \Brain\Monkey\Functions\when('esc_attr')->returnArg(1);
        \Brain\Monkey\Functions\when('esc_url')->returnArg(1);
        \Brain\Monkey\Functions\when('sanitize_text_field')->returnArg(1);
        \Brain\Monkey\Functions\when('sanitize_email')->returnArg(1);
        \Brain\Monkey\Functions\when('absint')->returnArg(1);
        \Brain\Monkey\Functions\when('is_admin')->justReturn(false);
        \Brain\Monkey\Functions\when('current_user_can')->justReturn(true);
        \Brain\Monkey\Functions\when('get_current_user_id')->justReturn(1);
        \Brain\Monkey\Functions\when('wp_create_nonce')->justReturn('test-nonce');
        \Brain\Monkey\Functions\when('wp_verify_nonce')->justReturn(true);
        \Brain\Monkey\Functions\when('admin_url')->returnArg(1);
        \Brain\Monkey\Functions\when('plugin_dir_url')->justReturn('http://example.org/wp-content/plugins/test/');
        \Brain\Monkey\Functions\when('plugin_dir_path')->justReturn('/path/to/plugin/');
        
        // Mock WordPress hooks
        \Brain\Monkey\Functions\when('add_action')->justReturn(true);
        \Brain\Monkey\Functions\when('add_filter')->justReturn(true);
        \Brain\Monkey\Functions\when('remove_action')->justReturn(true);
        \Brain\Monkey\Functions\when('remove_filter')->justReturn(true);
        \Brain\Monkey\Functions\when('has_action')->justReturn(false);
        \Brain\Monkey\Functions\when('has_filter')->justReturn(false);
        \Brain\Monkey\Functions\when('do_action')->justReturn(null);
        \Brain\Monkey\Functions\when('apply_filters')->returnArg(2);
        
        // Mock option functions
        \Brain\Monkey\Functions\when('get_option')->justReturn('test-value');
        \Brain\Monkey\Functions\when('update_option')->justReturn(true);
        \Brain\Monkey\Functions\when('delete_option')->justReturn(true);
        
        // Mock transient functions
        \Brain\Monkey\Functions\when('get_transient')->justReturn(false);
        \Brain\Monkey\Functions\when('set_transient')->justReturn(true);
        \Brain\Monkey\Functions\when('delete_transient')->justReturn(true);
        
        // Mock user functions
        \Brain\Monkey\Functions\when('wp_get_current_user')->justReturn($this->createMockUser());
        
        // Mock enqueue functions
        \Brain\Monkey\Functions\when('wp_enqueue_script')->justReturn(true);
        \Brain\Monkey\Functions\when('wp_enqueue_style')->justReturn(true);
        \Brain\Monkey\Functions\when('wp_localize_script')->justReturn(true);
    }

    /**
     * Mock WordPress admin environment
     */
    protected function mockAdminEnvironment(): void
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\when('is_admin')->justReturn(true);
            \Brain\Monkey\Functions\when('current_user_can')->with('manage_options')->justReturn(true);
            
            $screen = (object) [
                'id' => 'test-admin-page',
                'base' => 'test-page'
            ];
            
            \Brain\Monkey\Functions\when('get_current_screen')->justReturn($screen);
        }
    }

    /**
     * Mock WordPress frontend environment
     */
    protected function mockFrontendEnvironment(): void
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\when('is_admin')->justReturn(false);
            \Brain\Monkey\Functions\when('is_front_page')->justReturn(true);
            \Brain\Monkey\Functions\when('is_home')->justReturn(false);
        }
    }

    /**
     * Mock AJAX environment
     */
    protected function mockAjaxEnvironment(): void
    {
        if (!defined('DOING_AJAX')) {
            define('DOING_AJAX', true);
        }
        
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\when('wp_die')->justReturn(false);
            \Brain\Monkey\Functions\when('wp_send_json_success')->justReturn(true);
            \Brain\Monkey\Functions\when('wp_send_json_error')->justReturn(true);
        }
        
        $_POST['action'] = 'test_ajax_action';
        $_POST['nonce'] = 'test-nonce';
    }

    /**
     * Mock REST API environment
     */
    protected function mockRestEnvironment(): void
    {
        if (!defined('REST_REQUEST')) {
            define('REST_REQUEST', true);
        }
    }

    /**
     * Assert that a hook was called
     */
    protected function assertHookCalled(string $hook, array $args = []): void
    {
        if (class_exists('\Brain\Monkey\Actions')) {
            \Brain\Monkey\Actions\expectDone($hook)->with(...$args);
        }
    }

    /**
     * Assert that a filter was applied
     */
    protected function assertFilterApplied(string $filter, $value, array $args = []): void
    {
        if (class_exists('\Brain\Monkey\Filters')) {
            \Brain\Monkey\Filters\expectApplied($filter)->with($value, ...$args);
        }
    }

    /**
     * Mock WordPress database global
     */
    protected function mockWpdb(): \stdClass
    {
        $wpdb = new \stdClass();
        $wpdb->prefix = 'wp_';
        $wpdb->posts = 'wp_posts';
        $wpdb->users = 'wp_users';
        $wpdb->options = 'wp_options';
        $wpdb->last_error = '';
        $wpdb->insert_id = 1;
        
        return $wpdb;
    }

    /**
     * Create mock WordPress error
     */
    protected function createWpError(string $code = 'test_error', string $message = 'Test error'): \stdClass
    {
        $error = new \stdClass();
        $error->errors = [$code => [$message]];
        $error->error_data = [];
        
        return $error;
    }

    /**
     * Assert that WordPress option was updated
     */
    protected function assertOptionUpdated(string $option, $value): void
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            \Brain\Monkey\Functions\expect('update_option')
                ->once()
                ->with($option, $value)
                ->andReturn(true);
        }
    }

    /**
     * Assert that transient was set
     */
    protected function assertTransientSet(string $transient, $value, int $expiration = null): void
    {
        if (class_exists('\Brain\Monkey\Functions')) {
            $expectation = \Brain\Monkey\Functions\expect('set_transient')
                ->once()
                ->with($transient, $value);
                
            if ($expiration !== null) {
                $expectation->with($transient, $value, $expiration);
            }
            
            $expectation->andReturn(true);
        }
    }
}