# Testing Framework Documentation

## Overview

The ADZ WordPress Plugin Framework includes comprehensive testing capabilities using PHPUnit, Brain Monkey for WordPress function mocking, and custom test helpers designed specifically for WordPress plugin development.

## Testing Structure

```
tests/
├── bootstrap.php              # Test bootstrap file
├── phpunit.xml               # PHPUnit configuration
├── Helpers/                  # Test helper classes
│   ├── TestCase.php         # Base test case
│   ├── WordPressTestCase.php # WordPress-specific test case
│   └── FrameworkTestCase.php # Framework-specific test case
├── Unit/                    # Unit tests
│   ├── ControllerTest.php
│   ├── ConfigTest.php
│   └── HelperFunctionsTest.php
├── Integration/             # Integration tests
│   └── FrameworkIntegrationTest.php
├── WordPress/               # WordPress-specific tests
│   └── WordPressPluginTest.php
├── fixtures/                # Test fixtures and data
└── results/                 # Test results and coverage reports
```

## Setup and Installation

### 1. Install Testing Dependencies

```bash
# Install PHP dependencies including PHPUnit
composer install --dev

# Install WordPress test environment (optional)
composer run install-wp-tests
```

### 2. Configure Environment

Set up environment variables in your shell or phpunit.xml:

```bash
export WP_TESTS_DIR="/tmp/wordpress-tests-lib"
export WP_CORE_DIR="/tmp/wordpress/"
```

## Running Tests

### Basic Test Commands

```bash
# Run all tests
composer test

# Run only unit tests
composer run test:unit

# Run only integration tests  
composer run test:integration

# Run with coverage report
composer run test:coverage
```

### Advanced PHPUnit Commands

```bash
# Run specific test class
vendor/bin/phpunit tests/Unit/ControllerTest.php

# Run specific test method
vendor/bin/phpunit --filter testControllerInstantiation

# Run tests with verbose output
vendor/bin/phpunit --verbose

# Run tests and stop on first failure
vendor/bin/phpunit --stop-on-failure

# Run tests with debugging output
vendor/bin/phpunit --debug
```

## Test Types

### 1. Unit Tests

Unit tests focus on testing individual components in isolation:

```php
<?php
namespace Tests\Unit;

use Tests\Helpers\FrameworkTestCase;
use AdzWP\Controller;

class MyControllerTest extends FrameworkTestCase
{
    public function testControllerMethod()
    {
        $controller = $this->createMockController();
        $result = $controller->someMethod();
        
        $this->assertEquals('expected', $result);
    }
}
```

### 2. Integration Tests

Integration tests verify that different components work together:

```php
<?php
namespace Tests\Integration;

use Tests\Helpers\FrameworkTestCase;

class FrameworkIntegrationTest extends FrameworkTestCase
{
    public function testCompleteWorkflow()
    {
        $controller = $this->createMockController();
        $config = $this->createMockConfig();
        
        // Test components working together
        $this->assertInstanceOf('AdzWP\Controller', $controller);
    }
}
```

### 3. WordPress Tests

WordPress-specific tests that require WordPress environment:

```php
<?php
namespace Tests\WordPress;

use Tests\Helpers\WordPressTestCase;

class WordPressPluginTest extends WordPressTestCase
{
    public function testWordPressHooks()
    {
        add_action('init', function() {
            // Test WordPress functionality
        });
        
        $this->assertTrue(has_action('init') !== false);
    }
}
```

## Test Helper Classes

### TestCase (Base Class)

Provides common testing utilities:

```php
// Mock WordPress functions
$this->mockWordPressFunction('get_option', 'test-value');

// Create temporary files
$tempFile = $this->createTempFile('content');

// Assert array has keys
$this->assertArrayHasKeys(['key1', 'key2'], $array);

// Assert method exists
$this->assertMethodExists('methodName', $object);
```

### WordPressTestCase

WordPress-specific testing utilities:

```php
// Mock admin environment
$this->mockAdminEnvironment();

// Mock frontend environment
$this->mockFrontendEnvironment();

// Mock AJAX environment
$this->mockAjaxEnvironment();

// Assert hook was called
$this->assertHookCalled('init', ['arg1', 'arg2']);

// Assert option was updated
$this->assertOptionUpdated('option_name', 'value');
```

### FrameworkTestCase

Framework-specific testing utilities:

```php
// Create mock controller
$controller = $this->createMockController();

// Create mock config
$config = $this->createMockConfig(['key' => 'value']);

// Assert controller has actions
$this->assertControllerHasActions($controller, ['init', 'admin_menu']);

// Assert helper function exists
$this->assertHelperFunctionExists('adz_log');

// Test hook registration
$this->assertHookRegistrationWorks($controller);
```

## Mocking WordPress Functions

The framework uses Brain Monkey for WordPress function mocking:

```php
// Mock a function to return a value
\Brain\Monkey\Functions\when('get_option')->justReturn('test-value');

// Mock a function with expectations
\Brain\Monkey\Functions\expect('update_option')
    ->once()
    ->with('option_name', 'value')
    ->andReturn(true);

// Mock WordPress hooks
\Brain\Monkey\Actions\expectDone('init')->once();
\Brain\Monkey\Filters\expectApplied('the_content')->once();
```

## Writing Custom Tests

### 1. Create a Test Class

```php
<?php
namespace Tests\Unit;

use Tests\Helpers\FrameworkTestCase;

class MyFeatureTest extends FrameworkTestCase
{
    protected $myObject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->myObject = new MyFeature();
    }

    public function testMyFeature()
    {
        $result = $this->myObject->doSomething();
        $this->assertSame('expected', $result);
    }
}
```

### 2. Test WordPress Integration

```php
public function testWordPressIntegration()
{
    // Mock WordPress functions
    \Brain\Monkey\Functions\expect('add_action')
        ->once()
        ->with('init', 'my_callback')
        ->andReturn(true);

    // Test your code
    $result = add_action('init', 'my_callback');
    $this->assertTrue($result);
}
```

### 3. Test Controller Functionality

```php
public function testControllerActions()
{
    $controller = $this->createMockController();
    
    // Test action registration
    $this->assertControllerHasActions($controller, ['init']);
    
    // Test hook methods work
    $this->assertHookRegistrationWorks($controller);
}
```

## Code Coverage

Generate code coverage reports:

```bash
# HTML coverage report
composer run test:coverage

# View coverage report
open coverage/html/index.html
```

Coverage configuration in phpunit.xml:

```xml
<coverage includeUncoveredFiles="true">
    <include>
        <directory>src</directory>
        <directory>adz/dev-tools</directory>
    </include>
    <exclude>
        <file>adz/dev-tools/hive/helpers/functions.php</file>
    </exclude>
</coverage>
```

## Quality Assurance

### Code Standards

```bash
# Check code standards
composer run cs:check

# Fix code standards
composer run cs:fix
```

### Static Analysis

```bash
# Run PHPStan analysis
composer run analyse
```

## Continuous Integration

Example GitHub Actions configuration:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.0'
        
    - name: Install dependencies
      run: composer install
      
    - name: Run tests
      run: composer test
      
    - name: Upload coverage
      uses: codecov/codecov-action@v1
```

## Best Practices

### 1. Test Organization

- **Unit tests**: Test single classes/methods in isolation
- **Integration tests**: Test component interactions
- **WordPress tests**: Test WordPress-specific functionality

### 2. Test Naming

```php
// Good test names
public function testGetUserReturnsUserObject()
public function testCreatePostWithInvalidDataThrowsException()
public function testHookRegistrationWorksCorrectly()

// Poor test names
public function testUser()
public function testPost()
public function testHooks()
```

### 3. Arrange, Act, Assert

```php
public function testSomeFeature()
{
    // Arrange - Set up test data
    $input = 'test data';
    $expected = 'expected result';
    
    // Act - Execute the code being tested
    $result = $this->object->processData($input);
    
    // Assert - Verify the result
    $this->assertEquals($expected, $result);
}
```

### 4. Mock External Dependencies

```php
public function testExternalApiCall()
{
    // Mock the external dependency
    \Brain\Monkey\Functions\when('wp_remote_get')->justReturn([
        'response' => ['code' => 200],
        'body' => json_encode(['data' => 'test'])
    ]);
    
    // Test your code that uses the external dependency
    $result = $this->apiService->fetchData();
    $this->assertNotEmpty($result);
}
```

## Troubleshooting

### Common Issues

1. **Brain Monkey not working**: Ensure `Brain\Monkey\setUp()` is called in `setUp()`
2. **WordPress functions not mocked**: Check that mocks are defined before calling functions
3. **Tests failing randomly**: Use `setUp()` and `tearDown()` to reset state between tests
4. **Coverage not working**: Ensure Xdebug is installed and enabled

### Debug Mode

Enable debug output in phpunit.xml:

```xml
<php>
    <ini name="display_errors" value="On" />
    <ini name="error_reporting" value="E_ALL" />
</php>
```

Run tests with verbose output:

```bash
vendor/bin/phpunit --verbose --debug
```

## Contributing

When adding new features:

1. Write tests first (TDD approach)
2. Ensure all tests pass
3. Maintain code coverage above 80%
4. Follow existing test patterns
5. Document complex test scenarios

For more information, see the framework documentation and examples in the `tests/` directory.