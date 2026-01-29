# Manual Tests

This directory contains standalone scripts for testing the plugin functionality without needing a full WordPress installation.

## Available Scripts

### search-test.php
Tests the search functionality with mock product data.

**Usage:**
```bash
php tests/manual/search-test.php
```

**What it tests:**
1. **Partial Match Search**: Verifies "cami" finds "camiseta", "camisa", etc.
2. **SQL Filter Generation**: Validates the generated SQL contains correct patterns
3. **LIKE Pattern Matching**: Tests various search terms (cami, hdmi, zapa, portatil)
4. **Synonym Expansion**: Verifies synonym logic (coche → coche, auto, vehiculo)
5. **Case Insensitivity**: Ensures CAMI, cami, CaMi all work the same
6. **Minimum Character Limit**: Validates 3-character minimum requirement

**Mock Products Included:**
- Clothing: camisetas, camisas (for partial matching tests)
- Electronics: cables HDMI, auriculares
- Footwear: zapatillas
- Computers: portátiles, notebooks
- Toys: coches, vehículos
- Home: sartenes, teclados, mochilas

## Running the Tests

### Quick Test
```bash
php tests/manual/search-test.php
```

### Expected Output
```
✅ ALL TESTS PASSED!

The search functionality is working correctly:
- Partial matching works (cami → camiseta, camisa)
- SQL generation is correct
- LIKE patterns find matching products
- Synonym expansion works
- Case insensitive search works
- Minimum character limit enforced
```

## Adding New Test Cases

To add a new test case to `search-test.php`:

1. Add the test product to the `$test_products` array in the `Testwpdb` class
2. Add a new test section in the main execution block
3. Run the script to verify

Example:
```php
/**
 * TEST CASE: Your New Test
 */
echo "TEST: Your Test Description\n";
echo "----------------------------\n";

// Your test logic here
$results = $GLOBALS['wpdb']->simulateSearch('your-term');

if (count($results) > 0) {
    echo "✅ Passed\n";
} else {
    echo "❌ Failed\n";
}
echo "\n";
```

## Benefits of Manual Testing

- **Fast**: No need to set up WordPress/WooCommerce
- **Isolated**: Tests only the search logic, not the full stack
- **Instant Feedback**: See results immediately
- **Debuggable**: Easy to add var_dump() or print statements

## Limitations

- Uses mock data, not real WordPress database
- Does not test AJAX endpoint
- Does not test frontend JavaScript
- Does not test WooCommerce integration

For full integration testing, use the fixtures in `tests/fixtures/`.
