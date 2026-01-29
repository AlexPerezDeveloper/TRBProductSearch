<?php
/**
 * Integration tests for SKU Search functionality.
 *
 * @package TRB_Product_Search\Tests\Integration
 */

use PHPUnit\Framework\TestCase;

/**
 * Class SkuSearchTest
 *
 * Tests the SKU_Search class functionality.
 */
class SkuSearchTest extends TestCase {

    /**
     * Test that SKU_Search class exists.
     */
    public function test_sku_search_class_exists() {
        $this->assertTrue(class_exists('\TRB_Product_Search\SKU_Search'), 'SKU_Search class should exist');
    }

    /**
     * Test is_enabled returns false by default.
     */
    public function test_is_enabled_returns_false_by_default() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '0');

        $result = $sku_search->is_enabled();

        $this->assertFalse($result, 'SKU search should be disabled by default');
    }

    /**
     * Test is_enabled returns true when enabled.
     */
    public function test_is_enabled_returns_true_when_enabled() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

        $result = $sku_search->is_enabled();

        $this->assertTrue($result, 'SKU search should be enabled when option is set to "1"');
    }

    /**
     * Test get_matching_product_ids returns empty array when disabled.
     */
    public function test_get_matching_product_ids_returns_empty_when_disabled() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '0');

        $result = $sku_search->get_matching_product_ids('TEST-SKU');

        $this->assertIsArray($result, 'Should return array');
        $this->assertEmpty($result, 'Should return empty array when disabled');
    }

    /**
     * Test get_matching_product_ids returns IDs when enabled.
     */
    public function test_get_matching_product_ids_returns_ids_when_enabled() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();
        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

        // Mock global wpdb
        global $wpdb;
        // Mock get_col to return some post IDs from postmeta
        $wpdb->mock_results['get_col'] = array(10, 20);
        
        // Mock get_results to return post objects for those IDs
        $wpdb->mock_results['get_results'] = array(
            (object) array('ID' => 10, 'post_type' => 'product', 'post_parent' => 0),
            (object) array('ID' => 20, 'post_type' => 'product', 'post_parent' => 0)
        );

        $result = $sku_search->get_matching_product_ids('TEST-SKU');

        $this->assertIsArray($result);
        $this->assertContains(10, $result);
        $this->assertContains(20, $result);
        
        // Clean up mock
        unset($wpdb->mock_results);
    }

    /**
     * Test get_matching_product_ids handles variable products.
     */
    public function test_get_matching_product_ids_resolves_parents() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();
        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

        global $wpdb;
        // Mock get_col to return a variation ID
        $wpdb->mock_results['get_col'] = array(15);
        
        // Mock get_results to return the variation post object with parent
        $wpdb->mock_results['get_results'] = array(
            (object) array('ID' => 15, 'post_type' => 'product_variation', 'post_parent' => 5)
        );

        $result = $sku_search->get_matching_product_ids('VAR-SKU');

        $this->assertIsArray($result);
        $this->assertContains(5, $result, 'Should return parent ID for variation match');
        $this->assertNotContains(15, $result, 'Should not return variation ID');
        
        unset($wpdb->mock_results);
    }

    /**
     * Test get_exact_sku_match returns null when disabled.
     */
    public function test_get_exact_sku_match_returns_null_when_disabled() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '0');

        $result = $sku_search->get_exact_sku_match('WH-001');

        $this->assertNull($result, 'get_exact_sku_match should return null when SKU search is disabled');
    }

    /**
     * Test get_exact_sku_match returns product ID when found.
     */
    public function test_get_exact_sku_match_returns_product_id_when_found() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

        global $wpdb;
        $wpdb->mock_results['get_var'] = 123;

        $result = $sku_search->get_exact_sku_match('WH-001');

        $this->assertEquals(123, $result);
        
        unset($wpdb->mock_results);
    }

    /**
     * Test get_exact_sku_match returns null when not found.
     */
    public function test_get_exact_sku_match_returns_null_when_not_found() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');
        
        global $wpdb;
        $wpdb->mock_results['get_var'] = null;

        $result = $sku_search->get_exact_sku_match('NONEXISTENT-SKU');

        $this->assertNull($result, 'get_exact_sku_match should return null for non-existent SKU');
        
        unset($wpdb->mock_results);
    }

    /**
     * Test SKU_Search uses singleton pattern.
     */
    public function test_sku_search_uses_singleton_pattern() {
        $instance1 = \TRB_Product_Search\SKU_Search::get_instance();
        $instance2 = \TRB_Product_Search\SKU_Search::get_instance();

        $this->assertSame($instance1, $instance2, 'get_instance should return the same instance');
    }

    /**
     * Set up test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        TRB_Product_Search_Tests_Setup::setup();
    }

    /**
     * Clean up test environment after each test.
     */
    protected function tearDown(): void {
        TRB_Product_Search_Tests_Setup::cleanup();
        parent::tearDown();
    }
}