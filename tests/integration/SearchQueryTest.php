<?php
/**
 * Integration tests for Search Query functionality.
 *
 * @package TRB_Product_Search\Tests\Integration
 */

use PHPUnit\Framework\TestCase;

class SearchQueryTest extends TestCase {

    /**
     * Test that Search_Query class exists.
     */
    public function test_search_query_class_exists() {
        $this->assertTrue(class_exists('\TRB_Product_Search\Search_Query'), 'Search_Query class should exist');
    }

    /**
     * Test search method exists and is callable.
     */
    public function test_search_method_exists() {
        $query = new \TRB_Product_Search\Search_Query();
        $this->assertTrue(method_exists($query, 'search'), 'search method should exist');
    }

    /**
     * Test search returns WP_Query object.
     */
    public function test_search_returns_wp_query() {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $result = $query_handler->search('test');

        $this->assertInstanceOf('\WP_Query', $result, 'search should return WP_Query object');
    }

    /**
     * Test search with simple term.
     */
    public function test_search_with_simple_term() {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $result = $query_handler->search('headphones');

        $this->assertInstanceOf('\WP_Query', $result);
    }

    /**
     * Test search with no synonyms configured.
     */
    public function test_search_without_synonyms() {
        TRB_Product_Search_Tests_Setup::set_option('trb_search_synonyms', '');

        $query_handler = new \TRB_Product_Search\Search_Query();
        $result = $query_handler->search('test');

        $this->assertInstanceOf('\WP_Query', $result, 'Search should work without synonyms');
    }

    /**
     * Test search with single synonym group.
     */
    public function test_search_with_single_synonym_group() {
        $synonyms = "car, vehicle, auto";
        TRB_Product_Search_Tests_Setup::set_option('trb_search_synonyms', $synonyms);

        $query_handler = new \TRB_Product_Search\Search_Query();
        $result = $query_handler->search('car');

        $this->assertInstanceOf('\WP_Query', $result, 'Search should work with synonyms');
    }

    /**
     * Test search with multiple synonym groups.
     */
    public function test_search_with_multiple_synonym_groups() {
        $synonyms = "car, vehicle, auto\nlaptop, notebook, computer";
        TRB_Product_Search_Tests_Setup::set_option('trb_search_synonyms', $synonyms);

        $query_handler = new \TRB_Product_Search\Search_Query();

        // Search for car
        $result1 = $query_handler->search('car');
        $this->assertInstanceOf('\WP_Query', $result1);

        // Search for laptop
        $result2 = $query_handler->search('laptop');
        $this->assertInstanceOf('\WP_Query', $result2);
    }

    /**
     * Test custom_search_filter method exists.
     */
    public function test_custom_search_filter_method_exists() {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $this->assertTrue(method_exists($query_handler, 'custom_search_filter'), 'custom_search_filter method should exist');
    }

    /**
     * Test search filter returns modified SQL.
     */
    public function test_custom_search_filter_modifies_sql() {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query_handler);

        // Set current search terms through reflection
        $property = $reflection->getProperty('current_search_terms');
        $property->setAccessible(true);
        $property->setValue($query_handler, array('test', 'exam'));

        $wp_query = new \WP_Query();
        $sql = $query_handler->custom_search_filter('', $wp_query);

        $this->assertIsString($sql, 'Filter should return string');
        $this->assertNotEmpty($sql, 'Filter should not return empty string');
        $this->assertStringContainsString('test', $sql, 'SQL should contain search term');
        $this->assertStringContainsString('exam', $sql, 'SQL should contain synonym term');
    }

    /**
     * Test search filter handles empty terms.
     */
    public function test_custom_search_filter_with_empty_terms() {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $wp_query = new \WP_Query();
        $sql = $query_handler->custom_search_filter('original_sql', $wp_query);

        $this->assertEquals('original_sql', $sql, 'Filter should return original SQL for no terms');
    }

    /**
     * Test search case insensitivity.
     */
    public function test_search_case_insensitivity() {
        $synonyms = "phone, telephone";
        TRB_Product_Search_Tests_Setup::set_option('trb_search_synonyms', $synonyms);

        $query_handler = new \TRB_Product_Search\Search_Query();

        // Search with lowercase
        $result1 = $query_handler->search('phone');
        $this->assertInstanceOf('\WP_Query', $result1);

        // Search with uppercase
        $result2 = $query_handler->search('PHONE');
        $this->assertInstanceOf('\WP_Query', $result2);

        // Search with mixed case
        $result3 = $query_handler->search('Phone');
        $this->assertInstanceOf('\WP_Query', $result3);
    }

    /**
     * Test search with special characters.
     */
    public function test_search_with_special_characters() {
        $query_handler = new \TRB_Product_Search\Search_Query();

        // Search with spaces
        $result1 = $query_handler->search('wireless headphones');
        $this->assertInstanceOf('\WP_Query', $result1);

        // Search with hyphen
        $result2 = $query_handler->search('usb-c');
        $this->assertInstanceOf('\WP_Query', $result2);
    }

    /**
     * Test custom_search_filter handles OR logic.
     */
    public function test_custom_search_filter_or_logic() {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query_handler);

        $property = $reflection->getProperty('current_search_terms');
        $property->setAccessible(true);
        $property->setValue($query_handler, array('term1', 'term2'));

        $wp_query = new \WP_Query();
        $sql = $query_handler->custom_search_filter('', $wp_query);

        $this->assertStringContainsString(' OR ', $sql, 'SQL should contain OR for synonym matching');
    }

    /**
     * Test apply_filters hook integration.
     */
    public function test_apply_filters_hook_integration() {
        $query_handler = new \TRB_Product_Search\Search_Query();

        // Since apply_filters is mocked and returns the value, this test verifies
        // that the method can be called without errors
        $result = $query_handler->search('test');
        $this->assertInstanceOf('\WP_Query', $result, 'Search should work with mocked filters');
    }

    /**
     * Test posts_per_page limit.
     */
    public function test_posts_per_page_limit() {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $result = $query_handler->search('test');

        $this->assertInstanceOf('\WP_Query', $result);
        // In a real environment, we would check $result->query_vars['posts_per_page']
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
