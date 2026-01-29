<?php
/**
 * Load Test Products Script
 *
 * This script loads test products into WordPress/WooCommerce.
 * Run it from WordPress root: wp eval {path-to-this-file}
 *
 * Usage:
 *   wp eval tests/fixtures/load-test-products.php
 *
 * @package TRB_Product_Search\Tests
 */

if (!defined('ABSPATH')) {
    // If running directly, load WordPress
    require_once dirname(__FILE__) . '/../../../../../../wp-load.php';
}

// Check if WooCommerce is active
if (!class_exists('WooCommerce')) {
    echo "Error: WooCommerce is not active.\n";
    echo "Please activate WooCommerce before running this script.\n";
    exit(1);
}

// Load test products data
$test_products = require __DIR__ . '/test-products.php';

echo "Loading " . count($test_products) . " test products...\n";

$created = 0;
$updated = 0;
$errors = 0;

foreach ($test_products as $index => $product_data) {
    try {
        // Check if product with SKU already exists
        $existing_id = wc_get_product_id_by_sku($product_data['sku']);

        if ($existing_id) {
            // Update existing product
            $product = wc_get_product($existing_id);
            if (!$product) {
                $product = new WC_Product_Simple();
                $product->set_id($existing_id);
            }
            $updated++;
        } else {
            // Create new product
            $product = new WC_Product_Simple();
            $created++;
        }

        // Set basic product data
        $product->set_name($product_data['name']);
        $product->set_sku($product_data['sku']);
        $product->set_regular_price($product_data['price']);
        $product->set_description($product_data['description']);
        $product->set_short_description(wp_trim_words($product_data['description'], 15));
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_stock_status('instock');

        // Set attributes
        if (!empty($product_data['attributes'])) {
            $product_attributes = array();
            foreach ($product_data['attributes'] as $key => $value) {
                $attribute = new WC_Product_Attribute();
                $attribute->set_id(0); // 0 for local attributes
                $attribute->set_name($key);
                $attribute->set_options(array($value));
                $attribute->set_position(0);
                $attribute->set_visible(true);
                $attribute->set_variation(false);
                $product_attributes[sanitize_title($key)] = $attribute;
            }
            $product->set_attributes($product_attributes);
        }

        // Save product
        $product_id = $product->save();

        // Set categories
        if (!empty($product_data['categories'])) {
            wp_set_object_terms($product_id, $product_data['categories'], 'product_cat', false);
        }

        echo "✓ Product {$index}: {$product_data['name']} (SKU: {$product_data['sku']})\n";

    } catch (Exception $e) {
        echo "✗ Error creating product {$index}: {$e->getMessage()}\n";
        $errors++;
    }
}

echo "\n";
echo "========================================\n";
echo "Summary:\n";
echo "  Created: {$created}\n";
echo "  Updated: {$updated}\n";
echo "  Errors:  {$errors}\n";
echo "========================================\n";

// Build search index after loading products
echo "\nBuilding search index...\n";
$corrector = \TRB_Product_Search\Typo_Corrector::get_instance();
$index = $corrector->build_index();
echo "✓ Search index built with " . count($index) . " words.\n";

echo "\nDone! You can now test the search functionality.\n";
echo "Try searching for: 'cami', 'hdmi', 'zapa', 'portatil', etc.\n";
