<?php
/**
 * Unit tests for Attributes_Search multi-word search functionality.
 *
 * @package TRB_Product_Search\Tests\Unit
 */

use PHPUnit\Framework\TestCase;

/**
 * Class AttributesSearchMultiWordTest
 *
 * Tests the Attributes_Search class multi-word search functionality.
 */
class AttributesSearchMultiWordTest extends TestCase {

    /**
     * Test that single word search still works (backward compatibility).
     */
    public function test_single_word_search_backward_compatibility() {
        $attributes_search = \TRB_Product_Search\Attributes_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array('pa_color'));

        global $wpdb;
        // Mock get_col result for single word
        $wpdb->mock_results['get_col'] = array(
            array(101, 102, 103)
        );

        $result = $attributes_search->get_matching_product_ids('red');

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContains(101, $result);
        $this->assertContains(102, $result);
        $this->assertContains(103, $result);

        unset($wpdb->mock_results);
    }

    /**
     * Test that two words return intersection of results.
     */
    public function test_two_words_returns_intersection() {
        $attributes_search = \TRB_Product_Search\Attributes_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array('pa_color', 'pa_size'));

        global $wpdb;
        // Mock get_col results for two terms
        // First term returns products 1, 2, 3
        // Second term returns products 2, 3, 4
        // Intersection should be 2, 3
        $wpdb->mock_results['get_col'] = array(
            array(1, 2, 3),  // First term: "red"
            array(2, 3, 4),  // Second term: "large"
        );

        $result = $attributes_search->get_matching_product_ids(array('red', 'large'));

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains(2, $result);
        $this->assertContains(3, $result);
        $this->assertNotContains(1, $result);
        $this->assertNotContains(4, $result);

        unset($wpdb->mock_results);
    }

    /**
     * Test that three words return intersection of results.
     */
    public function test_three_words_returns_intersection() {
        $attributes_search = \TRB_Product_Search\Attributes_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array('pa_color', 'pa_size', 'pa_material'));

        global $wpdb;
        // Mock get_col results for three terms
        // First term returns products 1, 2, 3, 4
        // Second term returns products 2, 3, 4, 5
        // Third term returns products 3, 4, 5, 6
        // Intersection should be 3, 4
        $wpdb->mock_results['get_col'] = array(
            array(1, 2, 3, 4),  // First term: "red"
            array(2, 3, 4, 5),  // Second term: "large"
            array(3, 4, 5, 6),  // Third term: "cotton"
        );

        $result = $attributes_search->get_matching_product_ids(array('red', 'large', 'cotton'));

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains(3, $result);
        $this->assertContains(4, $result);
        $this->assertNotContains(1, $result);
        $this->assertNotContains(2, $result);
        $this->assertNotContains(5, $result);
        $this->assertNotContains(6, $result);

        unset($wpdb->mock_results);
    }

    /**
     * Test that no common results returns empty array.
     */
    public function test_no_common_results_returns_empty_array() {
        $attributes_search = \TRB_Product_Search\Attributes_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array('pa_color', 'pa_size'));

        global $wpdb;
        // Mock get_col results with no intersection
        // First term returns products 1, 2
        // Second term returns products 3, 4
        // Intersection should be empty
        $wpdb->mock_results['get_col'] = array(
            array(1, 2),  // First term: "red"
            array(3, 4),  // Second term: "blue"
        );

        $result = $attributes_search->get_matching_product_ids(array('red', 'blue'));

        $this->assertIsArray($result);
        $this->assertEmpty($result);

        unset($wpdb->mock_results);
    }

    /**
     * Test that empty terms array returns empty array.
     */
    public function test_empty_terms_returns_empty_array() {
        $attributes_search = \TRB_Product_Search\Attributes_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array('pa_color'));

        $result = $attributes_search->get_matching_product_ids(array());

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test that single item array works correctly.
     */
    public function test_single_item_array_works() {
        $attributes_search = \TRB_Product_Search\Attributes_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array('pa_color'));

        global $wpdb;
        // Mock get_col result for single item array
        $wpdb->mock_results['get_col'] = array(
            array(101, 102)
        );

        $result = $attributes_search->get_matching_product_ids(array('red'));

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains(101, $result);
        $this->assertContains(102, $result);

        unset($wpdb->mock_results);
    }

    /**
     * Test that method returns empty array when disabled even with array input.
     */
    public function test_returns_empty_when_disabled_with_array() {
        $attributes_search = \TRB_Product_Search\Attributes_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '0');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array('pa_color'));

        $result = $attributes_search->get_matching_product_ids(array('red', 'blue'));

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test that method returns empty array when no attributes selected with array input.
     */
    public function test_returns_empty_when_no_attributes_with_array() {
        $attributes_search = \TRB_Product_Search\Attributes_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array());

        $result = $attributes_search->get_matching_product_ids(array('red', 'blue'));

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
