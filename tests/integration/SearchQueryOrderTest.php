<?php
/**
 * Integration tests for Search Query Order functionality.
 *
 * @package TRB_Product_Search\Tests\Integration
 */

use PHPUnit\Framework\TestCase;

class SearchQueryOrderTest extends TestCase {

    /**
     * Test join_postmeta_for_orderby method exists.
     */
    public function test_join_postmeta_for_orderby_exists() {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $this->assertTrue(method_exists($query_handler, 'join_postmeta_for_orderby'), 'join_postmeta_for_orderby method should exist');
    }

    /**
     * Test join_postmeta_for_orderby modifies SQL.
     */
    public function test_join_postmeta_for_orderby_adds_join() {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $wp_query = new \WP_Query();
        
        $join_sql = '';
        $modified_join = $query_handler->join_postmeta_for_orderby($join_sql, $wp_query);

        $this->assertStringContainsString('JOIN', $modified_join, 'Should add JOIN clause');
        $this->assertStringContainsString('postmeta', $modified_join, 'Should join postmeta table');
        $this->assertStringContainsString('mt_sku', $modified_join, 'Should use mt_sku alias');
        $this->assertStringContainsString('_sku', $modified_join, 'Should filter by _sku meta key');
    }

    /**
     * Test join_postmeta_for_orderby does not duplicate join.
     */
    public function test_join_postmeta_for_orderby_prevents_duplication() {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $wp_query = new \WP_Query();
        
        $join_sql = "LEFT JOIN wp_postmeta AS mt_sku ON (wp_posts.ID = mt_sku.post_id AND mt_sku.meta_key = '_sku')";
        $modified_join = $query_handler->join_postmeta_for_orderby($join_sql, $wp_query);

        // Should be identical to input since mt_sku is already present
        $this->assertEquals($join_sql, $modified_join, 'Should not duplicate join if alias exists');
    }

    /**
     * Test priority_orderby uses the joined table alias.
     */
    public function test_priority_orderby_uses_alias() {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $wp_query = new \WP_Query();
        
        // Mock query vars
        $wp_query->query_vars['s'] = 'testsku';
        
        $orderby = 'post_date DESC';
        $modified_orderby = $query_handler->priority_orderby($orderby, $wp_query);
        
        $this->assertStringContainsString('mt_sku.meta_value', $modified_orderby, 'Should use mt_sku alias in orderby');
        $this->assertStringContainsString("= 'testsku'", $modified_orderby, 'Should check for exact SKU match');
    }
}
