<?php
/**
 * Test helper functions and setup utilities.
 *
 * @package TRB_Product_Search\Tests
 */

use PHPUnit\Framework\TestCase;

/**
 * Test setup class.
 */
class TRB_Product_Search_Tests_Setup {

    /**
     * Set up test environment.
     */
    public static function setup() {
        // Reset options
        global $_test_options;
        $_test_options = array();
    }

    /**
     * Clean up test environment.
     */
    public static function cleanup() {
        // Reset options
        global $_test_options;
        $_test_options = array();
    }

    /**
     * Set test option value.
     *
     * @param string $option Option name.
     * @param mixed  $value Option value.
     */
    public static function set_option($option, $value) {
        global $_test_options;
        $_test_options[$option] = $value;
    }

    /**
     * Get test option value.
     *
     * @param string $option Option name.
     * @param mixed  $default Default value.
     * @return mixed Option value.
     */
    public static function get_option($option, $default = false) {
        global $_test_options;
        return isset($_test_options[$option]) ? $_test_options[$option] : $default;
    }
}

/**
 * Create a mock WP_Query for testing.
 *
 * @param array $args Query arguments.
 * @return WP_Query Mock query object.
 */
function trb_create_mock_query($args = array()) {
    $query = new WP_Query($args);
    return $query;
}

/**
 * Create mock products for testing.
 *
 * @param int $count Number of products to create.
 * @return array Array of mock product data.
 */
function trb_create_mock_products($count = 3) {
    $products = array();

    for ($i = 1; $i <= $count; $i++) {
        $product = new WC_Product_Simple($i);
        $product->set_name("Test Product {$i}");
        $product->set_price((float) ($i * 10) + 9.99);
        $product->set_sku("TEST-00{$i}");
        $product->set_description("This is test product {$i} with some description text for search testing.");
        $products[] = $product;
    }

    return $products;
}

/**
 * Get test product data.
 *
 * @return array Test product data.
 */
function trb_get_test_product_data() {
    return array(
        array(
            'id' => 1,
            'name' => 'Wireless Headphones',
            'description' => 'High-quality wireless headphones with noise cancellation',
            'price' => 79.99,
            'sku' => 'WH-001',
            'stock_status' => 'instock',
        ),
        array(
            'id' => 2,
            'name' => 'Bluetooth Speaker',
            'description' => 'Portable bluetooth speaker with excellent sound quality',
            'price' => 49.99,
            'sku' => 'BS-002',
            'stock_status' => 'instock',
        ),
        array(
            'id' => 3,
            'name' => 'USB-C Cable',
            'description' => 'Fast charging USB-C cable, 2 meters long',
            'price' => 12.99,
            'sku' => 'UC-003',
            'stock_status' => 'instock',
        ),
        array(
            'id' => 4,
            'name' => 'Laptop Stand',
            'description' => 'Ergonomic aluminum laptop stand for better posture',
            'price' => 39.99,
            'sku' => 'LS-004',
            'stock_status' => 'outofstock',
        ),
        array(
            'id' => 5,
            'name' => 'Wireless Mouse',
            'description' => 'Ergonomic wireless mouse with precision tracking',
            'price' => 24.99,
            'sku' => 'WM-005',
            'stock_status' => 'instock',
        ),
    );
}

/**
 * Assert that a string contains a substring.
 *
 * @param TestCase $test The test case instance.
 * @param string   $haystack The string to search in.
 * @param string   $needle The substring to search for.
 * @param string   $message Optional message.
 */
function trb_assert_contains(TestCase $test, $haystack, $needle, $message = '') {
    $test->assertStringContainsString($needle, $haystack, $message);
}

/**
 * Assert that HTML contains an element with specific attributes.
 *
 * @param TestCase $test The test case instance.
 * @param string   $html The HTML string.
 * @param string   $tag The HTML tag to look for.
 * @param array    $attributes Optional attributes to check.
 */
function trb_assert_html_element(TestCase $test, $html, $tag, $attributes = array()) {
    // Check if tag exists
    $pattern = "/<{$tag}[^>]*>/i";
    $test->assertMatchesRegularExpression($pattern, $html, "Tag {$tag} should exist in HTML");

    // Check attributes if provided
    foreach ($attributes as $name => $value) {
        $pattern = "/<{$tag}[^>]*{$name}=[\"']{$value}[\"'][^>]*>/i";
        $test->assertMatchesRegularExpression($pattern, $html, "Attribute {$name}='{$value}' should exist on tag {$tag}");
    }
}

/**
 * Parse HTML from string to DOMDocument.
 *
 * @param string $html HTML string.
 * @return DOMDocument DOMDocument instance.
 */
function trb_parse_html($html) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();
    return $dom;
}

/**
 * Get all elements by tag name from HTML.
 *
 * @param string $html HTML string.
 * @param string $tag Tag name.
 * @return array Array of DOMElement instances.
 */
function trb_get_elements_by_tag($html, $tag) {
    $dom = trb_parse_html($html);
    return $dom->getElementsByTagName($tag);
}
