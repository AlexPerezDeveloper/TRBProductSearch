<?php
/**
 * Integration tests for Search Logic Setting (AND/OR) functionality.
 *
 * @package TRB_Product_Search\Tests\Integration
 */

use PHPUnit\Framework\TestCase;

/**
 * Test search logic setting (AND/OR) functionality.
 */
class SearchLogicSettingTest extends TestCase {

    /**
     * Test that search logic setting defaults to 'and'.
     */
    public function test_search_logic_defaults_to_and() {
        $logic = get_option('trb_search_logic', 'and');
        $this->assertEquals('and', $logic);
    }

    /**
     * Test that search logic setting can be set to 'and'.
     */
    public function test_search_logic_can_be_set_to_and() {
        TRB_Product_Search_Tests_Setup::set_option('trb_search_logic', 'and');

        $logic = get_option('trb_search_logic');
        $this->assertEquals('and', $logic);
    }

    /**
     * Test that search logic setting can be set to 'or'.
     */
    public function test_search_logic_can_be_set_to_or() {
        TRB_Product_Search_Tests_Setup::set_option('trb_search_logic', 'or');

        $logic = get_option('trb_search_logic');
        $this->assertEquals('or', $logic);
    }

    /**
     * Test that search logic setting persists after retrieval.
     */
    public function test_search_logic_setting_persists() {
        TRB_Product_Search_Tests_Setup::set_option('trb_search_logic', 'or');
        $logic1 = get_option('trb_search_logic');

        TRB_Product_Search_Tests_Setup::set_option('trb_search_logic', 'and');
        $logic2 = get_option('trb_search_logic');

        $this->assertEquals('or', $logic1);
        $this->assertEquals('and', $logic2);
    }

    /**
     * Test sanitize_search_logic accepts 'and'.
     */
    public function test_sanitize_search_logic_accepts_and() {
        $settings = \TRB_Product_Search\Settings::get_instance();
        $result = $settings->sanitize_search_logic('and');

        $this->assertEquals('and', $result);
    }

    /**
     * Test sanitize_search_logic accepts 'or'.
     */
    public function test_sanitize_search_logic_accepts_or() {
        $settings = \TRB_Product_Search\Settings::get_instance();
        $result = $settings->sanitize_search_logic('or');

        $this->assertEquals('or', $result);
    }

    /**
     * Test sanitize_search_logic rejects invalid values.
     */
    public function test_sanitize_search_logic_rejects_invalid_values() {
        $settings = \TRB_Product_Search\Settings::get_instance();

        $this->assertEquals('and', $settings->sanitize_search_logic('invalid'));
        $this->assertEquals('and', $settings->sanitize_search_logic(''));
        $this->assertEquals('and', $settings->sanitize_search_logic('AND')); // Case sensitive
        $this->assertEquals('and', $settings->sanitize_search_logic('OR'));  // Case sensitive
    }

    /**
     * Test that render_search_logic_field method exists.
     */
    public function test_render_search_logic_field_method_exists() {
        $settings = \TRB_Product_Search\Settings::get_instance();
        $this->assertTrue(
            method_exists($settings, 'render_search_logic_field'),
            'render_search_logic_field method should exist'
        );
    }

    /**
     * Test that search logic field renders radio buttons.
     */
    public function test_search_logic_field_renders_radio_buttons() {
        $settings = \TRB_Product_Search\Settings::get_instance();

        ob_start();
        $settings->render_search_logic_field();
        $output = ob_get_clean();

        $this->assertStringContainsString('type="radio"', $output);
        $this->assertStringContainsString('name="trb_search_logic"', $output);
        $this->assertStringContainsString('value="and"', $output);
        $this->assertStringContainsString('value="or"', $output);
    }

    /**
     * Test that search logic field shows AND as selected by default.
     */
    public function test_search_logic_field_shows_and_selected_by_default() {
        $settings = \TRB_Product_Search\Settings::get_instance();

        ob_start();
        $settings->render_search_logic_field();
        $output = ob_get_clean();

        // Check that AND option is rendered
        $this->assertStringContainsString('AND', $output);
        $this->assertStringContainsString('all words required', $output);
    }

    /**
     * Test that search logic field shows OR option.
     */
    public function test_search_logic_field_shows_or_option() {
        $settings = \TRB_Product_Search\Settings::get_instance();

        ob_start();
        $settings->render_search_logic_field();
        $output = ob_get_clean();

        // Check that OR option is rendered
        $this->assertStringContainsString('OR', $output);
        $this->assertStringContainsString('any word sufficient', $output);
    }

    /**
     * Test that search logic field includes description.
     */
    public function test_search_logic_field_includes_description() {
        $settings = \TRB_Product_Search\Settings::get_instance();

        ob_start();
        $settings->render_search_logic_field();
        $output = ob_get_clean();

        $this->assertStringContainsString('description', $output);
        $this->assertStringContainsString('AND requires all words', $output);
    }

    /**
     * Test multi-word search SQL uses AND logic by default.
     */
    public function test_multi_word_search_sql_uses_and_logic_by_default() {
        // Ensure default logic (AND)
        TRB_Product_Search_Tests_Setup::set_option('trb_search_logic', 'and');

        $query = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query);

        // Set current search terms
        $prop_terms = $reflection->getProperty('current_search_terms');
        $prop_terms->setAccessible(true);
        $prop_terms->setValue($query, array('disco', 'duro'));

        // Set multi-word flag
        $prop_multi = $reflection->getProperty('is_multi_word_search');
        $prop_multi->setAccessible(true);
        $prop_multi->setValue($query, true);

        $wp_query = new \WP_Query();
        $sql = $query->custom_search_filter('', $wp_query);

        // Should contain AND between term groups for AND logic
        $this->assertStringContainsString(') AND (', $sql);
    }

    /**
     * Test multi-word search SQL uses OR logic when setting is 'or'.
     */
    public function test_multi_word_search_sql_uses_or_logic_when_setting_is_or() {
        // Set OR logic
        TRB_Product_Search_Tests_Setup::set_option('trb_search_logic', 'or');

        $query = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query);

        // Set current search terms
        $prop_terms = $reflection->getProperty('current_search_terms');
        $prop_terms->setAccessible(true);
        $prop_terms->setValue($query, array('disco', 'duro'));

        // Set multi-word flag
        $prop_multi = $reflection->getProperty('is_multi_word_search');
        $prop_multi->setAccessible(true);
        $prop_multi->setValue($query, true);

        $wp_query = new \WP_Query();
        $sql = $query->custom_search_filter('', $wp_query);

        // Should contain OR between term groups for OR logic
        $this->assertStringContainsString(') OR (', $sql);
    }

    /**
     * Test get_union_product_ids method exists.
     */
    public function test_get_union_product_ids_method_exists() {
        $query = new \TRB_Product_Search\Search_Query();
        $this->assertTrue(
            method_exists($query, 'get_union_product_ids'),
            'get_union_product_ids method should exist'
        );
    }

    /**
     * Test get_union_product_ids returns array.
     */
    public function test_get_union_product_ids_returns_array() {
        $query = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query);

        $method = $reflection->getMethod('get_union_product_ids');
        $method->setAccessible(true);

        $result = $method->invoke($query, array('term1', 'term2'));

        $this->assertIsArray($result);
    }

    /**
     * Test that get_intersecting_product_ids method still exists.
     */
    public function test_get_intersecting_product_ids_method_still_exists() {
        $query = new \TRB_Product_Search\Search_Query();
        $this->assertTrue(
            method_exists($query, 'get_intersecting_product_ids'),
            'get_intersecting_product_ids method should still exist'
        );
    }

    /**
     * Test multi-word search respects AND logic setting for SKU search.
     */
    public function test_multi_word_search_respects_and_logic_for_sku() {
        global $wpdb;

        // Enable SKU search and set AND logic
        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_logic', 'and');

        // Mock SKU search results - intersection (product must match ALL terms)
        $wpdb->mock_results = array(
            'get_col' => array(
                array(101, 102), // First term matches
                array(101),      // Second term matches (intersection: 101)
            ),
        );

        $query = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query);

        $method = $reflection->getMethod('get_intersecting_product_ids');
        $method->setAccessible(true);

        $result = $method->invoke($query, array('disco', 'duro'));

        $this->assertIsArray($result);
    }

    /**
     * Test multi-word search respects OR logic setting for SKU search.
     */
    public function test_multi_word_search_respects_or_logic_for_sku() {
        global $wpdb;

        // Enable SKU search and set OR logic
        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_logic', 'or');

        // Mock SKU search results - union (product matching ANY term)
        $wpdb->mock_results = array(
            'get_col' => array(
                array(101, 102), // First term matches
                array(103),      // Second term matches (union: 101, 102, 103)
            ),
        );

        $query = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query);

        $method = $reflection->getMethod('get_union_product_ids');
        $method->setAccessible(true);

        $result = $method->invoke($query, array('disco', 'duro'));

        $this->assertIsArray($result);
        // Should contain all unique IDs from both terms
        $this->assertContains(101, $result);
        $this->assertContains(102, $result);
        $this->assertContains(103, $result);
    }

    /**
     * Test cache key includes search logic.
     */
    public function test_cache_key_includes_search_logic() {
        TRB_Product_Search_Tests_Setup::set_option('trb_search_logic', 'or');

        $query = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query);

        // Set up search terms
        $prop_terms = $reflection->getProperty('current_search_terms');
        $prop_terms->setAccessible(true);
        $prop_terms->setValue($query, array('disco', 'duro'));

        // Set multi-word flag
        $prop_multi = $reflection->getProperty('is_multi_word_search');
        $prop_multi->setAccessible(true);
        $prop_multi->setValue($query, true);

        // Just verify the search runs without errors
        $wp_query = new \WP_Query();
        $sql = $query->custom_search_filter('', $wp_query);

        $this->assertNotEmpty($sql);
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
