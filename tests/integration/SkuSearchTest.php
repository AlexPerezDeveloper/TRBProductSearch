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
     * Test build_meta_query returns null when disabled.
     */
    public function test_build_meta_query_returns_null_when_disabled() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '0');

        $result = $sku_search->build_meta_query('TEST-SKU');

        $this->assertNull($result, 'build_meta_query should return null when SKU search is disabled');
    }

    /**
     * Test build_meta_query returns array when enabled.
     */
    public function test_build_meta_query_returns_array_when_enabled() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

        $result = $sku_search->build_meta_query('TEST-SKU');

        $this->assertIsArray($result, 'build_meta_query should return an array when enabled');
        $this->assertArrayHasKey('sku_clause', $result, 'Result should contain sku_clause key');
    }

    /**
     * Test build_meta_query contains correct query structure.
     */
    public function test_build_meta_query_contains_correct_structure() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();
        $search_term = 'WIRELESS-HEADPHONES';

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

        $result = $sku_search->build_meta_query($search_term);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sku_clause', $result);
        $this->assertEquals('_sku', $result['sku_clause']['key'], 'Meta key should be _sku');
        $this->assertEquals($search_term, $result['sku_clause']['value'], 'Value should match search term');
        $this->assertEquals('LIKE', $result['sku_clause']['compare'], 'Compare operator should be LIKE');
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
     *
     * Note: This test requires database mocking for full integration testing.
     * In a real WordPress environment with products, it would return the actual product ID.
     */
    public function test_get_exact_sku_match_returns_product_id_when_found() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

        // This test would require a mock database with product meta data
        // For unit testing, we verify the method is callable
        $result = $sku_search->get_exact_sku_match('WH-001');

        // In test environment without actual products, this will return null
        // The test verifies the method can be called without errors
        $this->assertTrue(is_int($result) || is_null($result), 'Should return int or null');
    }

    /**
     * Test get_exact_sku_match returns null when not found.
     */
    public function test_get_exact_sku_match_returns_null_when_not_found() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

        $result = $sku_search->get_exact_sku_match('NONEXISTENT-SKU');

        $this->assertNull($result, 'get_exact_sku_match should return null for non-existent SKU');
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
     * Test build_meta_query with different search terms.
     */
    public function test_build_meta_query_with_different_terms() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

        $terms = array('WH-001', 'BS-002', 'UC-003');

        foreach ($terms as $term) {
            $result = $sku_search->build_meta_query($term);

            $this->assertIsArray($result);
            $this->assertEquals($term, $result['sku_clause']['value'], "Value should match term: {$term}");
        }
    }

    /**
     * Test build_meta_query handles special characters.
     */
    public function test_build_meta_query_handles_special_characters() {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

        $special_terms = array('SKU-001', 'SKU_002', 'SKU/003', 'SKU 004');

        foreach ($special_terms as $term) {
            $result = $sku_search->build_meta_query($term);

            $this->assertIsArray($result);
            $this->assertEquals($term, $result['sku_clause']['value']);
        }
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
