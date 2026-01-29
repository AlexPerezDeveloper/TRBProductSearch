<?php
/**
 * Integration tests for Attributes Search functionality.
 *
 * @package TRB_Product_Search\Tests\Integration
 */

use PHPUnit\Framework\TestCase;

/**
 * Class AttributesSearchTest
 *
 * Tests the Attributes_Search class functionality.
 */
class AttributesSearchTest extends TestCase {

    /**
     * Test that Attributes_Search class exists.
     */
    public function test_attributes_search_class_exists() {
        $this->assertTrue(class_exists('\TRB_Product_Search\Attributes_Search'), 'Attributes_Search class should exist');
    }

    /**
     * Test is_enabled returns false by default.
     */
    public function test_is_enabled_returns_false_by_default() {
        $attributes_search = \TRB_Product_Search\Attributes_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '0');

        $result = $attributes_search->is_enabled();

        $this->assertFalse($result, 'Attributes search should be disabled by default');
    }

    /**
     * Test is_enabled returns true when enabled.
     */
    public function test_is_enabled_returns_true_when_enabled() {
        $attributes_search = \TRB_Product_Search\Attributes_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');

        $result = $attributes_search->is_enabled();

        $this->assertTrue($result, 'Attributes search should be enabled when option is set to "1"');
    }

    /**
     * Test get_selected_attributes returns empty array by default.
     */
    public function test_get_selected_attributes_returns_empty_array_by_default() {
        $attributes_search = \TRB_Product_Search\Attributes_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array());

        $result = $attributes_search->get_selected_attributes();

        $this->assertIsArray($result, 'get_selected_attributes should return an array');
        $this->assertEmpty($result, 'get_selected_attributes should return empty array by default');
    }

    /**
     * Test get_matching_product_ids returns empty array when disabled.
     */
    public function test_get_matching_product_ids_returns_empty_when_disabled() {
        $attributes_search = \TRB_Product_Search\Attributes_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '0');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array('pa_color'));

        $result = $attributes_search->get_matching_product_ids('red');

        $this->assertIsArray($result, 'Should return array');
        $this->assertEmpty($result, 'Should return empty array when disabled');
    }

    /**
     * Test get_matching_product_ids returns empty when no attributes selected.
     */
    public function test_get_matching_product_ids_returns_empty_when_no_attributes() {
        $attributes_search = \TRB_Product_Search\Attributes_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array());

        $result = $attributes_search->get_matching_product_ids('red');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test get_matching_product_ids returns IDs when terms match.
     */
    public function test_get_matching_product_ids_returns_ids_when_match_found() {
        $attributes_search = \TRB_Product_Search\Attributes_Search::get_instance();
        
        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array('pa_color'));

        global $wpdb;
        // Mock get_col results sequence
        // 1. Term IDs
        // 2. Term Taxonomy IDs
        // 3. Object IDs
        $wpdb->mock_results['get_col'] = array(
            array(101), // Term IDs
            array(202), // Term Taxonomy IDs
            array(303, 404) // Product IDs
        );

        $result = $attributes_search->get_matching_product_ids('red');

        $this->assertIsArray($result);
        $this->assertContains(303, $result);
        $this->assertContains(404, $result);

        unset($wpdb->mock_results);
    }

    /**
     * Test Attributes_Search uses singleton pattern.
     */
    public function test_attributes_search_uses_singleton_pattern() {
        $instance1 = \TRB_Product_Search\Attributes_Search::get_instance();
        $instance2 = \TRB_Product_Search\Attributes_Search::get_instance();

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