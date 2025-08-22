<?php

namespace Tests\Unit;

use Tests\Helpers\FrameworkTestCase;

/**
 * Unit tests for helper functions
 */
class HelperFunctionsTest extends FrameworkTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Load helper functions
        if (file_exists(ADZ_PLUGIN_PATH . 'adz/dev-tools/hive/helpers/functions.php')) {
            require_once ADZ_PLUGIN_PATH . 'adz/dev-tools/hive/helpers/functions.php';
        }
    }

    public function testAdzLogFunctionExists()
    {
        $this->assertHelperFunctionExists('adz_log');
    }

    public function testAdzLogInfoFunctionExists()
    {
        $this->assertHelperFunctionExists('adz_log_info');
    }

    public function testAdzLogErrorFunctionExists()
    {
        $this->assertHelperFunctionExists('adz_log_error');
    }

    public function testAdzLogWarningFunctionExists()
    {
        $this->assertHelperFunctionExists('adz_log_warning');
    }

    public function testAdzLogDebugFunctionExists()
    {
        $this->assertHelperFunctionExists('adz_log_debug');
    }

    public function testAdzGetOptionFunctionExists()
    {
        $this->assertHelperFunctionExists('adz_get_option');
    }

    public function testAdzUpdateOptionFunctionExists()
    {
        $this->assertHelperFunctionExists('adz_update_option');
    }

    public function testAdzSanitizeInputFunctionExists()
    {
        $this->assertHelperFunctionExists('adz_sanitize_input');
    }

    public function testAdzVerifyNonceFunctionExists()
    {
        $this->assertHelperFunctionExists('adz_verify_nonce');
    }

    public function testAdzIsAdminPageFunctionExists()
    {
        $this->assertHelperFunctionExists('adz_is_admin_page');
    }

    public function testAdzEnqueueAssetFunctionExists()
    {
        $this->assertHelperFunctionExists('adz_enqueue_asset');
    }

    public function testAdzGetCurrentUserRoleFunctionExists()
    {
        $this->assertHelperFunctionExists('adz_get_current_user_role');
    }

    public function testAdzArrayGetFunctionExists()
    {
        $this->assertHelperFunctionExists('adz_array_get');
    }

    public function testAdzArraySetFunctionExists()
    {
        $this->assertHelperFunctionExists('adz_array_set');
    }

    public function testAdzRedirectFunctionExists()
    {
        $this->assertHelperFunctionExists('adz_redirect');
    }

    public function testAdzIsAjaxFunctionExists()
    {
        $this->assertHelperFunctionExists('adz_is_ajax');
    }

    public function testAdzIsRestFunctionExists()
    {
        $this->assertHelperFunctionExists('adz_is_rest');
    }

    public function testAdzGetTemplateFunctionExists()
    {
        $this->assertHelperFunctionExists('adz_get_template');
    }

    public function testAdzFormatBytesFunctionExists()
    {
        $this->assertHelperFunctionExists('adz_format_bytes');
    }

    public function testAdzArrayGetWithSimpleKey()
    {
        if (function_exists('adz_array_get')) {
            $array = ['key' => 'value'];
            $result = adz_array_get($array, 'key', 'default');
            $this->assertEquals('value', $result);
        }
    }

    public function testAdzArrayGetWithDotNotation()
    {
        if (function_exists('adz_array_get')) {
            $array = ['level1' => ['level2' => 'value']];
            $result = adz_array_get($array, 'level1.level2', 'default');
            $this->assertEquals('value', $result);
        }
    }

    public function testAdzArrayGetWithNonExistentKey()
    {
        if (function_exists('adz_array_get')) {
            $array = ['key' => 'value'];
            $result = adz_array_get($array, 'nonexistent', 'default');
            $this->assertEquals('default', $result);
        }
    }

    public function testAdzArrayGetWithNonArrayInput()
    {
        if (function_exists('adz_array_get')) {
            $result = adz_array_get('not_array', 'key', 'default');
            $this->assertEquals('default', $result);
        }
    }

    public function testAdzArraySet()
    {
        if (function_exists('adz_array_set')) {
            $array = [];
            adz_array_set($array, 'level1.level2', 'value');
            
            $this->assertArrayHasKey('level1', $array);
            $this->assertArrayHasKey('level2', $array['level1']);
            $this->assertEquals('value', $array['level1']['level2']);
        }
    }

    public function testAdzFormatBytes()
    {
        if (function_exists('adz_format_bytes')) {
            $this->assertEquals('1 KB', adz_format_bytes(1024));
            $this->assertEquals('1 MB', adz_format_bytes(1024 * 1024));
            $this->assertEquals('500 B', adz_format_bytes(500));
        }
    }

    public function testAdzIsAjaxWithAjaxConstant()
    {
        if (function_exists('adz_is_ajax')) {
            // Define DOING_AJAX constant temporarily
            if (!defined('DOING_AJAX')) {
                define('DOING_AJAX', true);
            }
            
            $result = adz_is_ajax();
            $this->assertTrue($result);
        }
    }

    public function testAdzIsRestWithRestConstant()
    {
        if (function_exists('adz_is_rest')) {
            // Define REST_REQUEST constant temporarily
            if (!defined('REST_REQUEST')) {
                define('REST_REQUEST', true);
            }
            
            $result = adz_is_rest();
            $this->assertTrue($result);
        }
    }
}