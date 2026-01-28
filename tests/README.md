# PHPUnit Integration Tests for TRB Product Search

This WordPress plugin includes comprehensive integration tests to ensure the search functionality works correctly across different components.

## Setup

### Prerequisites

- PHP 7.4 or higher
- Composer

### Installation

1. Install dependencies:
   ```bash
   composer install
   ```

## Running Tests

### Run all integration tests:
```bash
composer test-integration
```

Or directly with PHPUnit:
```bash
phpunit
```

### Run tests with coverage report:
```bash
composer test-coverage
```

## Test Structure

- `tests/integration/PluginInitTest.php` - Plugin initialization and component loading
- `tests/integration/SearchFormTest.php` - Search form shortcode and HTML rendering
- `tests/integration/SearchQueryTest.php` - Search query logic and synonym functionality
- `tests/integration/SettingsTest.php` - Settings page and options management
- `tests/integration/CompleteWorkflowTest.php` - End-to-end workflow testing
- `tests/bootstrap.php` - Test environment setup with WordPress mocks
- `tests/helpers.php` - Test helper functions and utilities
- `phpunit.xml` - PHPUnit configuration

## Test Coverage

The test suite covers:

1. **Plugin Initialization**
   - Constants definition
   - Class loading
   - Singleton pattern
   - Dependency checking
   - Component integration

2. **Search Form**
   - Shortcode rendering
   - HTML structure validation
   - Input attributes
   - Accessibility features
   - Multiple instances
   - Query parameter handling

3. **Search Query**
   - Basic search functionality
   - Synonym support
   - Case insensitivity
   - Special characters
   - SQL generation
   - Filter hooks integration

4. **Settings**
   - Options management
   - Input sanitization
   - Field rendering
   - Form validation
   - Synonym storage

5. **Complete Workflow**
   - End-to-end search process
   - Component integration
   - Multiple searches
   - Edge cases
   - Component isolation

## Writing New Tests

When adding new tests:

1. Create a test file in `tests/integration/` with name `*Test.php`
2. Extend `PHPUnit\Framework\TestCase`
3. Implement `setUp()` and `tearDown()` methods
4. Call `TRB_Product_Search_Tests_Setup::setup()` and `TRB_Product_Search_Tests_Setup::cleanup()`
5. Write descriptive test methods with `test_*` prefix
6. Use assertions to verify behavior

Example:
```php
<?php
use PHPUnit\Framework\TestCase;

class MyFeatureTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        TRB_Product_Search_Tests_Setup::setup();
    }

    protected function tearDown(): void {
        TRB_Product_Search_Tests_Setup::cleanup();
        parent::tearDown();
    }

    public function test_my_feature() {
        // Test implementation
        $this->assertTrue(true);
    }
}
```

## Test Environment

The test suite uses mocked WordPress and WooCommerce functions to run without requiring a full WordPress installation. This allows for fast, isolated tests that can run in any environment.

### Mocked Functions

- WordPress core functions (add_action, add_filter, etc.)
- WooCommerce classes and functions
- Database operations
- HTTP requests

## Continuous Integration

Tests can be integrated into CI/CD pipelines:

```yaml
# Example GitHub Actions workflow
- name: Run tests
  run: |
    composer install
    composer test-integration
```

## Coverage Goals

- Minimum code coverage: 70%
- Target code coverage: 80%

Coverage reports are generated in:
- `tests/coverage/html/index.html` - HTML report
- `tests/coverage/coverage.txt` - Text report
- `tests/coverage/coverage.xml` - Clover XML

## Troubleshooting

### Tests fail with "Class not found"
Ensure all plugin classes are properly loaded in `tests/bootstrap.php`.

### Mock functions not working
Check that all WordPress/WooCommerce functions used in tests are mocked in `tests/bootstrap.php`.

### Test database errors
Tests use an in-memory database; no real database connection is needed.

## Best Practices

1. Keep tests independent and isolated
2. Use descriptive test names
3. One assertion per test when possible
4. Test both success and failure cases
5. Mock external dependencies
6. Clean up after each test

## Contributing

When contributing new features:

1. Write tests before or alongside implementation
2. Ensure all existing tests pass
3. Maintain or improve test coverage
4. Document complex test scenarios
5. Update this README if needed
