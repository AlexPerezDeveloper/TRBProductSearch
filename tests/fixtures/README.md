# Test Fixtures

This directory contains test data and scripts for testing the TRB Product Search plugin with real data.

## Files

### test-products.php
Contains an array of 30+ test products covering various categories:
- **Clothing**: camisetas, camisas, zapatillas (for testing partial matches like "cami" → "camisetas")
- **Electronics**: cables HDMI, auriculares, cargadores, teclados
- **Home & Kitchen**: sartenes, cuchillos, lámparas
- **Accessories**: mochilas, fundas
- **Products with attributes**: different colors (rojo, azul, verde), sizes (S, M, L, XL)
- **Synonym test cases**: coche/auto/vehiculo, portatil/notebook/computer

### load-test-products.php
Script to load the test products into your WordPress/WooCommerce installation.

**Usage:**
```bash
# From WordPress root directory
wp eval tests/fixtures/load-test-products.php

# Or if plugin is in wp-content/plugins/
wp eval wp-content/plugins/trb-product-search/tests/fixtures/load-test-products.php
```

**What it does:**
- Creates or updates all test products
- Sets proper attributes (color, talla, material, etc.)
- Assigns categories
- Builds the search index automatically

## How to Test Real Search Functionality

### 1. Load Test Products
```bash
wp eval tests/fixtures/load-test-products.php
```

### 2. Test Search Scenarios

Once products are loaded, you can test:

| Search Term | Should Find | Notes |
|-------------|-------------|-------|
| `cami` | Camiseta Básica, Camiseta Estampada, Camisa Formal | Partial match test |
| `hdmi` | Cable HDMI 2.1, Cable HDMI 4K, Adaptador HDMI | Electronics test |
| `zapa` | Zapatillas Deportivas, Zapatillas Urbanas | Partial match test |
| `portatil` | Portátil Notebook, Ordenador Portátil Gaming | Synonym test |
| `rojo` | Products with color: Rojo | Attribute search |
| `azul` | Products with color: Azul | Attribute search |

### 3. Run Test Suite
```bash
composer test
```

This will run 119 tests including the new real search integration tests in `RealSearchTest.php`.

## Creating Custom Test Products

To add your own test products, edit `test-products.php` and add a new array element:

```php
[
    'name' => 'Your Product Name',
    'sku' => 'UNIQUE-SKU-001',
    'price' => '99.99',
    'description' => 'Product description here.',
    'attributes' => ['color' => 'Value', 'other' => 'Value'],
    'categories' => ['category-slug'],
],
```

Then run the load script again to create them.

## Cleaning Up Test Products

To remove all test products:

```bash
# Using WP-CLI
wp post delete $(wp post list --post_type=product --format=ids) --force

# Or delete by SKU pattern (safer - only deletes test products)
wp eval '
$skus = ["CAMI-001", "CAMI-002", "HDMI-2M-001"]; // add all test SKUs
foreach ($skus as $sku) {
    $id = wc_get_product_id_by_sku($sku);
    if ($id) {
        wp_delete_post($id, true);
    }
}
'
```
