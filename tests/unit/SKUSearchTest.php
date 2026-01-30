<?php
/**
 * Unit tests for SKU Search multi-word functionality.
 *
 * @package TRB_Product_Search\Tests\Unit
 */

use PHPUnit\Framework\TestCase;

/**
 * Class SKUSearchTest
 *
 * Tests the SKU_Search class multi-word functionality.
 */
class SKUSearchTest extends TestCase {

    /**
     * Test single word search still works (backward compatibility).
     */
    public function test_single_word_search_backward_compatibility() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

        global $wpdb;
        $wpdb->mock_results['get_col'] = array(10, 20, 30);

        $result = $sku_search->get_matching_product_ids('SSD');

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContains(10, $result);
        $this->assertContains(20, $result);
        $this->assertContains(30, $result);

        unset($wpdb->mock_results);
    }

    /**
     * Test two words returns intersection of results.
     */
    public function test_two_words_returns_intersection() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

        global $wpdb;
        // First call returns products matching 'disco'
        // Second call returns products matching 'duro'
        // Intersection should be products matching both
        $wpdb->mock_results['get_col'] = array(
            array(1, 2, 3),    // 'disco' matches products 1, 2, 3
            array(2, 3, 4),    // 'duro' matches products 2, 3, 4
        );

        $result = $sku_search->get_matching_product_ids(array('disco', 'duro'));

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains(2, $result);
        $this->assertContains(3, $result);
        $this->assertNotContains(1, $result);
        $this->assertNotContains(4, $result);

        unset($wpdb->mock_results);
    }

    /**
     * Test three words returns intersection of all three.
     */
    public function test_three_words_returns_intersection() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

        global $wpdb;
        // Three terms - intersection of all three
        $wpdb->mock_results['get_col'] = array(
            array(1, 2, 3, 4),    // 'disco' matches
            array(2, 3, 4, 5),    // 'duro' matches
            array(3, 4, 5, 6),    // 'ssd' matches
        );

        $result = $sku_search->get_matching_product_ids(array('disco', 'duro', 'ssd'));

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains(3, $result);
        $this->assertContains(4, $result);

        unset($wpdb->mock_results);
    }

    /**
     * Test no common results returns empty array.
     */
    public function test_no_common_results_returns_empty_array() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

        global $wpdb;
        // No intersection between results
        $wpdb->mock_results['get_col'] = array(
            array(1, 2, 3),    // 'abc' matches
            array(4, 5, 6),    // 'xyz' matches - no overlap
        );

        $result = $sku_search->get_matching_product_ids(array('abc', 'xyz'));

        $this->assertIsArray($result);
        $this->assertEmpty($result);

        unset($wpdb->mock_results);
    }

    /**
     * Test empty terms array returns empty array.
     */
    public function test_empty_terms_returns_empty_array() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

        $result = $sku_search->get_matching_product_ids(array());

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test single element array works like single word.
     */
    public function test_single_element_array_works() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

        global $wpdb;
        $wpdb->mock_results['get_col'] = array(10, 20);

        $result = $sku_search->get_matching_product_ids(array('SSD'));

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains(10, $result);
        $this->assertContains(20, $result);

        unset($wpdb->mock_results);
    }

    /**
     * Test disabled SKU search returns empty array for array input.
     */
    public function test_disabled_search_returns_empty_for_array_input() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '0');

        $result = $sku_search->get_matching_product_ids(array('disco', 'duro'));

        $this->assertIsArray($result);
        $this->assertEmpty($result);
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
