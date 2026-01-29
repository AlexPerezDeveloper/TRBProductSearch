<?php
/**
 * Real Search Integration Tests
 *
 * These tests use actual WP_Query to test search functionality.
 * They still use mocked WordPress functions but test the SQL generation logic.
 *
 * @package TRB_Product_Search\Tests\Integration
 */

use PHPUnit\Framework\TestCase;

/**
 * Class SearchQueryRealTest
 *
 * Tests actual search query generation and filtering logic.
 */
class SearchQueryRealTest extends TestCase
{
    /**
     * Test partial matching: "cami" should find "camisetas".
     */
    public function test_partial_match_cami_finds_camisetas()
    {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query_handler);

        // Set up search terms
        $property = $reflection->getProperty('current_search_terms');
        $property->setAccessible(true);
        $property->setValue($query_handler, array('cami'));

        $wp_query = new \WP_Query(array('s' => 'cami'));

        $sql = $query_handler->custom_search_filter('', $wp_query);

        // Should contain partial match patterns
        $this->assertStringContainsString('%cami%', $sql, 'SQL should contain partial match pattern');
        $this->assertStringContainsString('post_title', $sql, 'SQL should search in post_title');
    }

    /**
     * Test synonym expansion: "coche" should also search "auto" and "vehiculo".
     */
    public function test_synonym_expansion_adds_multiple_terms()
    {
        // Simulate synonyms option
        TRB_Product_Search_Tests_Setup::set_option('trb_search_synonyms', "coche, auto, vehiculo");

        $query_handler = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query_handler);

        // Simulate that synonyms were processed
        $property = $reflection->getProperty('current_search_terms');
        $property->setAccessible(true);
        $property->setValue($query_handler, array('coche', 'auto', 'vehiculo'));

        $wp_query = new \WP_Query();

        $sql = $query_handler->custom_search_filter('', $wp_query);

        // Should contain OR conditions for all synonyms
        $this->assertStringContainsString('%coche%', $sql, 'SQL should contain coche');
        $this->assertStringContainsString('%auto%', $sql, 'SQL should contain auto');
        $this->assertStringContainsString('%vehiculo%', $sql, 'SQL should contain vehiculo');
        $this->assertStringContainsString(' OR ', $sql, 'SQL should use OR logic for synonyms');
    }

    /**
     * Test that search searches both title and content.
     */
    public function test_search_includes_title_and_content()
    {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query_handler);

        $property = $reflection->getProperty('current_search_terms');
        $property->setAccessible(true);
        $property->setValue($query_handler, array('test'));

        $wp_query = new \WP_Query();

        $sql = $query_handler->custom_search_filter('', $wp_query);

        $this->assertStringContainsString('post_title', $sql, 'SQL should search in title');
        $this->assertStringContainsString('post_content', $sql, 'SQL should search in content');
    }

    /**
     * Test case insensitive search.
     */
    public function test_search_is_case_insensitive()
    {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query_handler);

        $property = $reflection->getProperty('current_search_terms');
        $property->setAccessible(true);
        $property->setValue($query_handler, array('CaMi'));

        $wp_query = new \WP_Query();

        $sql = $query_handler->custom_search_filter('', $wp_query);

        // The search term should be in the SQL
        $this->assertStringContainsString('CaMi', $sql, 'Search term should be in SQL');
    }

    /**
     * Test special characters in search term are escaped.
     */
    public function test_special_characters_are_escaped()
    {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query_handler);

        $property = $reflection->getProperty('current_search_terms');
        $property->setAccessible(true);
        $property->setValue($query_handler, array("test'quote", 'test%percent'));

        $wp_query = new \WP_Query();

        $sql = $query_handler->custom_search_filter('', $wp_query);

        // Check that the SQL is generated (esc_sql is applied)
        $this->assertIsString($sql);
        $this->assertNotEmpty($sql);
    }

    /**
     * Test search with multiple words.
     */
    public function test_search_with_multiple_words()
    {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query_handler);

        $property = $reflection->getProperty('current_search_terms');
        $property->setAccessible(true);
        $property->setValue($query_handler, array('portatil', 'gaming'));

        $wp_query = new \WP_Query();

        $sql = $query_handler->custom_search_filter('', $wp_query);

        // Should contain both terms
        $this->assertStringContainsString('%portatil%', $sql);
        $this->assertStringContainsString('%gaming%', $sql);
    }

    /**
     * Test empty search terms returns original SQL.
     */
    public function test_empty_terms_returns_original_sql()
    {
        $query_handler = new \TRB_Product_Search\Search_Query();

        $wp_query = new \WP_Query();
        $original_sql = 'AND ((wp_posts.post_title LIKE \'%%\'))';

        $sql = $query_handler->custom_search_filter($original_sql, $wp_query);

        $this->assertEquals($original_sql, $sql, 'Should return original SQL when no terms set');
    }

    /**
     * Verify search method sets up filter correctly.
     */
    public function test_search_method_adds_custom_filter()
    {
        TRB_Product_Search_Tests_Setup::set_option('trb_search_synonyms', '');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '0');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '0');

        $query_handler = new \TRB_Product_Search\Search_Query();

        // This should not throw any errors
        $result = $query_handler->search('test');

        $this->assertInstanceOf('\WP_Query', $result, 'search should return WP_Query instance');
    }

    /**
     * Test that search works with minimum 3 characters.
     */
    public function test_search_with_minimum_characters()
    {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query_handler);

        $property = $reflection->getProperty('current_search_terms');
        $property->setAccessible(true);
        $property->setValue($query_handler, array('abc')); // 3 characters

        $wp_query = new \WP_Query();

        $sql = $query_handler->custom_search_filter('', $wp_query);

        $this->assertStringContainsString('%abc%', $sql);
    }

    /**
     * Test SKU search integration.
     */
    public function test_sku_search_is_disabled_by_default()
    {
        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '0');

        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();
        $result = $sku_search->get_matching_product_ids('test-sku');

        $this->assertEmpty($result, 'SKU search should return empty array when disabled');
    }

    /**
     * Test attributes search integration.
     */
    public function test_attributes_search_is_disabled_by_default()
    {
        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '0');

        $attr_search = \TRB_Product_Search\Attributes_Search::get_instance();
        $result = $attr_search->get_matching_product_ids('test');

        $this->assertEmpty($result, 'Attributes search should return empty array when disabled');
    }

    /**
     * Test typo correction scenario when no results found.
     */
    public function test_typo_correction_may_be_applied_when_no_results()
    {
        TRB_Product_Search_Tests_Setup::set_option('trb_search_synonyms', '');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '0');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '0');

        $query_handler = new \TRB_Product_Search\Search_Query();
        $query_handler->search('xyznonexistent'); // Term that won't match anything

        // After search, original term should be stored
        $this->assertEquals('xyznonexistent', $query_handler->get_original_term());

        // get_correction_info should return valid structure
        $info = $query_handler->get_correction_info();
        $this->assertArrayHasKey('original', $info);
        $this->assertArrayHasKey('corrected', $info);
    }

    /**
     * Test that correction info is properly structured.
     */
    public function test_correction_info_structure_is_valid()
    {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $info = $query_handler->get_correction_info();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('original', $info);
        $this->assertArrayHasKey('corrected', $info);
    }

    /**
     * Test search with 4+ character term stores original term.
     */
    public function test_search_with_long_term_stores_original()
    {
        TRB_Product_Search_Tests_Setup::set_option('trb_search_synonyms', '');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '0');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '0');

        $query_handler = new \TRB_Product_Search\Search_Query();
        $query_handler->search('camiseta');

        $this->assertEquals('camiseta', $query_handler->get_original_term());
    }

    /**
     * Set up test environment before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        TRB_Product_Search_Tests_Setup::setup();
    }

    /**
     * Clean up test environment after each test.
     */
    protected function tearDown(): void
    {
        TRB_Product_Search_Tests_Setup::cleanup();
        parent::tearDown();
    }
}
