# Real Integration Tests

These tests use the WordPress test suite (`WP_UnitTestCase`) to run integration tests against a real WordPress installation.

## Installation

### 1. Install WordPress Test Suite

```bash
bash tests/install-wp-tests.sh
```

Or manually:

```bash
# Set environment variables
export WP_TESTS_DIR=/path/to/wordpress-tests-lib
export WP_TESTS_DB_NAME=wordpress_test

# Download WordPress test suite
svn co https://develop.svn.wordpress.org/trunk/ $WP_TESTS_DIR

# Configure
cd $WP_TESTS_DIR
bash install.sh
```

### 2. Set Environment Variables

Add to your `~/.bashrc` or `~/.zshrc`:

```bash
export WP_TESTS_DIR=/path/to/wordpress-tests-lib
export WP_TESTS_DB_NAME=wordpress_test
```

Then reload: `source ~/.bashrc`

## Running Tests

### Run All Tests
```bash
composer test
```

### Run Only Real Integration Tests
```bash
WP_TESTS_DIR=$WP_TESTS_DIR phpunit --bootstrap tests/bootstrap-real.php tests/integration-real/
```

### Run Specific Test
```bash
WP_TESTS_DIR=$WP_TESTS_DIR phpunit --bootstrap tests/bootstrap-real.php tests/integration-real/RealSearchTest.php::test_partial_matching_finds_products
```

## What These Tests Verify

| Test | Description |
|------|-------------|
| `test_basic_wordpress_search_works` | WordPress native search finds products |
| `test_custom_search_filter_sql_generation` | SQL filter generates correct patterns |
| `test_search_query_class_search` | Search_Query class works with real WP_Query |
| `test_partial_matching_finds_products` | Partial matching ("cami" → "camiseta") |
| `test_sku_search_can_be_enabled` | SKU search can be enabled |
| `test_attributes_search_disabled_by_default` | Attributes search defaults to disabled |
| `test_synonym_expansion_logic` | Synonyms are stored correctly |
| `test_typo_corrector_class_exists` | Typo_Corrector class works |
| `test_sanitize_checkbox_handles_various_inputs` | Checkbox sanitization works |

## Difference from Mock Tests

| Aspect | Mock Tests (tests/integration/) | Real Tests (tests/integration-real/) |
|--------|--------------------------------|----------------------------------|
| WordPress | Mocked functions | Real WordPress core |
| Database | No database | Real MySQL database |
| WP_Query | Mock class | Real WP_Query with SQL execution |
| Speed | Fast (~30ms) | Slower (~100-200ms) |
| Fidelity | Tests code logic | Tests actual WordPress behavior |

## Troubleshooting

### "WordPress test suite not found"
```bash
# Install the test suite
bash tests/install-wp-tests.sh
```

### "Database connection failed"
```bash
# Create database manually
mysql -u root -p
CREATE DATABASE wordpress_test;
```

### "Class not found"
```bash
# Check WP_TESTS_DIR is set
echo $WP_TESTS_DIR

# Run with explicit path
WP_TESTS_DIR=/path/to/wordpress-tests-lib composer test
```

## Continuous Integration

Add to your CI/CD pipeline:

```yaml
- name: Run WordPress Integration Tests
  env:
    WP_TESTS_DIR: /tmp/wordpress-tests-lib
    WP_TESTS_DB_NAME: wordpress_test
  run: |
    bash tests/install-wp-tests.sh
    composer test
```
