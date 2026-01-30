<?php
/**
 * Integration tests for Multi-Word Search functionality.
 *
 * @package TRB_Product_Search\Tests\Integration
 */

use PHPUnit\Framework\TestCase;

/**
 * Test multi-word search with AND logic.
 */
class MultiWordSearchTest extends TestCase {

    /**
     * Test that parse_search_terms splits multi-word queries correctly.
     */
    public function test_parse_search_terms_splits_multi_word() {
        $query = new \TRB_Product_Search\Search_Query();

        $terms = $query->parse_search_terms('disco duro ssd');

        $this->assertIsArray($terms);
        $this->assertCount(3, $terms);
        $this->assertContains('disco', $terms);
        $this->assertContains('duro', $terms);
        $this->assertContains('ssd', $terms);
    }

    /**
     * Test that parse_search_terms filters out stop words.
     */
    public function test_parse_search_terms_removes_stop_words() {
        $query = new \TRB_Product_Search\Search_Query();

        $terms = $query->parse_search_terms('camiseta de algodon');

        $this->assertIsArray($terms);
        $this->assertContains('camiseta', $terms);
        $this->assertContains('algodon', $terms);
        $this->assertNotContains('de', $terms);
    }

    /**
     * Test that parse_search_terms filters short words.
     */
    public function test_parse_search_terms_filters_short_words() {
        $query = new \TRB_Product_Search\Search_Query();

        $terms = $query->parse_search_terms('la x y z');

        // All words are either stop words or too short
        $this->assertIsArray($terms);
        $this->assertEmpty($terms);
    }

    /**
     * Test that parse_search_terms limits to 5 words max.
     */
    public function test_parse_search_terms_limits_to_five_words() {
        $query = new \TRB_Product_Search\Search_Query();

        $terms = $query->parse_search_terms('uno dos tres cuatro cinco seis siete');

        $this->assertIsArray($terms);
        $this->assertCount(5, $terms);
    }

    /**
     * Test single word search still works (backward compatibility).
     */
    public function test_single_word_search_backward_compatible() {
        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '0');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '0');

        $query = new \TRB_Product_Search\Search_Query();
        $result = $query->search('camiseta');

        $this->assertInstanceOf('\WP_Query', $result);
    }

    /**
     * Test multi-word search returns WP_Query.
     */
    public function test_multi_word_search_returns_wp_query() {
        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '0');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '0');

        $query = new \TRB_Product_Search\Search_Query();
        $result = $query->search('disco duro ssd');

        $this->assertInstanceOf('\WP_Query', $result);
    }

    /**
     * Test custom_search_filter uses AND logic for multi-word search.
     */
    public function test_custom_search_filter_uses_and_logic_for_multi_word() {
        $query = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query);

        // Set current search terms
        $prop_terms = $reflection->getProperty('current_search_terms');
        $prop_terms->setAccessible(true);
        $prop_terms->setValue($query, array('disco', 'duro', 'ssd'));

        // Set multi-word flag
        $prop_multi = $reflection->getProperty('is_multi_word_search');
        $prop_multi->setAccessible(true);
        $prop_multi->setValue($query, true);

        $wp_query = new \WP_Query();
        $sql = $query->custom_search_filter('', $wp_query);

        // Should contain AND between term groups
        $this->assertStringContainsString('AND', $sql);

        // Should contain OR within each term group (title OR content)
        $this->assertStringContainsString('OR', $sql);

        // Should contain all three terms
        $this->assertStringContainsString('disco', $sql);
        $this->assertStringContainsString('duro', $sql);
        $this->assertStringContainsString('ssd', $sql);
    }

    /**
     * Test custom_search_filter uses OR logic for single-word search.
     */
    public function test_custom_search_filter_uses_or_logic_for_single_word() {
        $query = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query);

        // Set current search terms (single word with synonyms)
        $prop_terms = $reflection->getProperty('current_search_terms');
        $prop_terms->setAccessible(true);
        $prop_terms->setValue($query, array('car', 'auto', 'vehicle'));

        // Set multi-word flag to false
        $prop_multi = $reflection->getProperty('is_multi_word_search');
        $prop_multi->setAccessible(true);
        $prop_multi->setValue($query, false);

        $wp_query = new \WP_Query();
        $sql = $query->custom_search_filter('', $wp_query);

        // Should contain OR between terms (synonyms)
        $this->assertStringContainsString('OR', $sql);

        // Single word search should NOT have ') AND (' which indicates AND logic between term groups
        // Multi-word search pattern: ((term1 conditions)) AND ((term2 conditions))
        // Single-word search pattern: ((term1 conditions) OR (term2 conditions) OR (term3 conditions))
        $this->assertStringNotContainsString(') AND (', $sql, 'Single word search should use OR logic between terms, not AND');
    }

    /**
     * Test get_intersecting_product_ids method exists.
     */
    public function test_get_intersecting_product_ids_method_exists() {
        $query = new \TRB_Product_Search\Search_Query();
        $this->assertTrue(
            method_exists($query, 'get_intersecting_product_ids'),
            'get_intersecting_product_ids method should exist'
        );
    }

    /**
     * Test search_multi_word method exists.
     */
    public function test_search_multi_word_method_exists() {
        $query = new \TRB_Product_Search\Search_Query();
        $this->assertTrue(
            method_exists($query, 'search_multi_word'),
            'search_multi_word method should exist'
        );
    }

    /**
     * Test search_single_word method exists.
     */
    public function test_search_single_word_method_exists() {
        $query = new \TRB_Product_Search\Search_Query();
        $this->assertTrue(
            method_exists($query, 'search_single_word'),
            'search_single_word method should exist'
        );
    }

    /**
     * Test multi-word search includes matched product IDs from SKU.
     */
    public function test_multi_word_search_includes_sku_matches() {
        global $wpdb;

        // Enable SKU search
        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

        // Mock SKU search results - product that matches all terms
        $wpdb->mock_results = array(
            'get_col' => array(
                array(101), // First term matches
                array(101), // Second term matches (intersection: 101)
            ),
        );

        $query = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query);

        // Call get_intersecting_product_ids via reflection
        $method = $reflection->getMethod('get_intersecting_product_ids');
        $method->setAccessible(true);

        $result = $method->invoke($query, array('disco', 'duro'));

        $this->assertIsArray($result);
    }

    /**
     * Test multi-word search includes matched product IDs from attributes.
     */
    public function test_multi_word_search_includes_attribute_matches() {
        global $wpdb;

        // Enable attributes search
        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array('color', 'size'));

        // Mock attribute search results
        $wpdb->mock_results = array(
            'get_col' => array(
                array(201), // First term matches
                array(201), // Second term matches (intersection: 201)
            ),
        );

        $query = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query);

        // Call get_intersecting_product_ids via reflection
        $method = $reflection->getMethod('get_intersecting_product_ids');
        $method->setAccessible(true);

        $result = $method->invoke($query, array('rojo', 'grande'));

        $this->assertIsArray($result);
    }

    /**
     * Test that empty search term returns no results gracefully.
     */
    public function test_empty_search_term() {
        $query = new \TRB_Product_Search\Search_Query();
        $result = $query->search('');

        $this->assertInstanceOf('\WP_Query', $result);
    }

    /**
     * Test that search with only stop words returns no results.
     */
    public function test_search_with_only_stop_words() {
        $query = new \TRB_Product_Search\Search_Query();
        $result = $query->search('el la de');

        $this->assertInstanceOf('\WP_Query', $result);
    }

    /**
     * Test multi-word search with special characters.
     */
    public function test_multi_word_search_with_special_characters() {
        $query = new \TRB_Product_Search\Search_Query();

        $terms = $query->parse_search_terms('usb-c cable 2.0');

        $this->assertIsArray($terms);
        // Should handle special characters gracefully
        $this->assertNotEmpty($terms);
    }

    /**
     * Test multi-word search SQL construction with ID inclusion.
     */
    public function test_multi_word_search_sql_with_matched_ids() {
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

        // Set matched product IDs
        $prop_ids = $reflection->getProperty('matched_product_ids');
        $prop_ids->setAccessible(true);
        $prop_ids->setValue($query, array(101, 102));

        $wp_query = new \WP_Query();
        $sql = $query->custom_search_filter('', $wp_query);

        // Should contain ID IN clause
        $this->assertStringContainsString('ID IN (101,102)', $sql);

        // Should contain AND logic for terms
        $this->assertStringContainsString('AND', $sql);
    }

    /**
     * Test that single word search still uses OR logic for synonyms.
     */
    public function test_single_word_synonym_search_uses_or_logic() {
        $synonyms = "laptop, notebook, computer";
        TRB_Product_Search_Tests_Setup::set_option('trb_search_synonyms', $synonyms);

        $query = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query);

        // Set current search terms (synonyms)
        $prop_terms = $reflection->getProperty('current_search_terms');
        $prop_terms->setAccessible(true);
        $prop_terms->setValue($query, array('laptop', 'notebook', 'computer'));

        // Set multi-word flag to false
        $prop_multi = $reflection->getProperty('is_multi_word_search');
        $prop_multi->setAccessible(true);
        $prop_multi->setValue($query, false);

        $wp_query = new \WP_Query();
        $sql = $query->custom_search_filter('', $wp_query);

        // Should contain all synonym terms
        $this->assertStringContainsString('laptop', $sql);
        $this->assertStringContainsString('notebook', $sql);
        $this->assertStringContainsString('computer', $sql);

        // Should use OR logic between synonym terms (not AND between term groups)
        // Single word with synonyms: (title LIKE 'laptop%') OR (title LIKE 'notebook%') OR ...
        // Multi-word: ((title LIKE 'term1%') OR ...) AND ((title LIKE 'term2%') OR ...)
        $this->assertStringContainsString('OR', $sql);
        // Single word search should NOT have AND between the main term conditions
        // (it only has OR between title/content/prefix conditions for each term)
        $this->assertStringNotContainsString(') AND (', $sql);
    }

    /**
     * Test multi-word search with mixed case.
     */
    public function test_multi_word_search_case_insensitive() {
        $query = new \TRB_Product_Search\Search_Query();

        $terms_lower = $query->parse_search_terms('DISCO DURO SSD');
        $terms_mixed = $query->parse_search_terms('Disco Duro Ssd');

        $this->assertEquals($terms_lower, $terms_mixed);
    }

    /**
     * Test that build_relevance_orderby method exists.
     */
    public function test_build_relevance_orderby_method_exists() {
        $query = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query);

        $this->assertTrue(
            $reflection->hasMethod('build_relevance_orderby'),
            'build_relevance_orderby method should exist'
        );
    }

    /**
     * Test priority_orderby uses relevance scoring for multi-word search.
     */
    public function test_priority_orderby_uses_relevance_for_multi_word() {
        global $wpdb;

        // Set relevance ordering
        TRB_Product_Search_Tests_Setup::set_option('trb_search_orderby', 'relevance');

        $query = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query);

        // Set multi-word flag
        $prop_multi = $reflection->getProperty('is_multi_word_search');
        $prop_multi->setAccessible(true);
        $prop_multi->setValue($query, true);

        // Set current search terms
        $prop_terms = $reflection->getProperty('current_search_terms');
        $prop_terms->setAccessible(true);
        $prop_terms->setValue($query, array('disco', 'duro', 'ssd'));

        $wp_query = new \WP_Query();
        $wp_query->query_vars['s'] = 'disco duro ssd';

        $orderby = $query->priority_orderby('', $wp_query);

        // Should contain exact phrase match (Priority 1)
        $this->assertStringContainsString("post_title LIKE '%disco duro ssd%'", $orderby);
        $this->assertStringContainsString('THEN 100', $orderby);

        // Should contain all words match (Priority 2)
        $this->assertStringContainsString('THEN 50', $orderby);

        // Should contain word count scoring (Priority 3)
        $this->assertStringContainsString('THEN 10', $orderby);

        // Should contain SKU match (Priority 4)
        $this->assertStringContainsString("mt_sku.meta_value = 'disco duro ssd'", $orderby);
        $this->assertStringContainsString('THEN 25', $orderby);

        // Should contain alphabetical tie-breaker
        $this->assertStringContainsString('post_title ASC', $orderby);
    }

    /**
     * Test priority_orderby maintains backward compatibility for single-word search.
     */
    public function test_priority_orderby_backward_compatible_for_single_word() {
        global $wpdb;

        // Set relevance ordering
        TRB_Product_Search_Tests_Setup::set_option('trb_search_orderby', 'relevance');

        $query = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query);

        // Set multi-word flag to false
        $prop_multi = $reflection->getProperty('is_multi_word_search');
        $prop_multi->setAccessible(true);
        $prop_multi->setValue($query, false);

        $wp_query = new \WP_Query();
        $wp_query->query_vars['s'] = 'camiseta';

        $orderby = $query->priority_orderby('', $wp_query);

        // Single word should use simple SKU priority
        $this->assertStringContainsString('mt_sku.meta_value', $orderby);
        $this->assertStringContainsString('post_title ASC', $orderby);
    }

    /**
     * Test relevance scoring SQL structure for two-word search.
     */
    public function test_relevance_orderby_sql_structure_two_words() {
        global $wpdb;

        $query = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query);

        $method = $reflection->getMethod('build_relevance_orderby');
        $method->setAccessible(true);

        $terms = array('zapatillas', 'running');
        $orderby = $method->invoke($query, $terms, $wpdb);

        // Should contain exact phrase match
        $this->assertStringContainsString("post_title LIKE '%zapatillas running%'", $orderby);
        $this->assertStringContainsString('THEN 100', $orderby);

        // Should contain all words condition with AND
        $this->assertStringContainsString("post_title LIKE '%zapatillas%'", $orderby);
        $this->assertStringContainsString("post_title LIKE '%running%'", $orderby);
        $this->assertStringContainsString(' AND ', $orderby);

        // Should contain word count scoring with +
        $this->assertStringContainsString('+', $orderby);

        // Should contain SKU match
        $this->assertStringContainsString("mt_sku.meta_value = 'zapatillas running'", $orderby);
        $this->assertStringContainsString('THEN 25', $orderby);
    }

    /**
     * Test relevance scoring escapes special characters properly.
     */
    public function test_relevance_orderby_escapes_special_characters() {
        global $wpdb;

        $query = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query);

        $method = $reflection->getMethod('build_relevance_orderby');
        $method->setAccessible(true);

        // Terms with SQL special characters
        $terms = array("test'", 'word%');
        $orderby = $method->invoke($query, $terms, $wpdb);

        // Should escape single quotes (esc_like adds \, then esc_sql doubles to \\)
        // In PHP string: test\\' represents test\' in the actual output
        $this->assertStringContainsString("test\\'", $orderby);

        // Should escape percent signs (esc_like adds \, then esc_sql doubles to \\)
        // In PHP string: word\\% represents word\% in the actual output
        $this->assertStringContainsString('word\\\\%', $orderby);
    }

    /**
     * Test that single word search does not use multi-word relevance scoring.
     */
    public function test_single_word_search_does_not_use_relevance_scoring() {
        global $wpdb;

        TRB_Product_Search_Tests_Setup::set_option('trb_search_orderby', 'relevance');

        $query = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query);

        // Set multi-word flag to false
        $prop_multi = $reflection->getProperty('is_multi_word_search');
        $prop_multi->setAccessible(true);
        $prop_multi->setValue($query, false);

        // Set empty current_search_terms (as would be for single word)
        $prop_terms = $reflection->getProperty('current_search_terms');
        $prop_terms->setAccessible(true);
        $prop_terms->setValue($query, array());

        $wp_query = new \WP_Query();
        $wp_query->query_vars['s'] = 'laptop';

        $orderby = $query->priority_orderby('', $wp_query);

        // Should NOT contain multi-word specific patterns
        $this->assertStringNotContainsString('THEN 100', $orderby, 'Single word should not have exact phrase scoring');
        $this->assertStringNotContainsString('THEN 50', $orderby, 'Single word should not have all words scoring');
    }

    /**
     * Test multi-word search with OR logic returns products with any word.
     */
    public function test_multi_word_search_or_logic_returns_products_with_any_word() {
        global $wpdb;

        // Set OR logic
        TRB_Product_Search_Tests_Setup::set_option('trb_search_logic', 'or');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

        // Mock SKU search results - union (product matching ANY term)
        $wpdb->mock_results = array(
            'get_col' => array(
                array(101, 102), // First term matches
                array(103),      // Second term matches (union: 101, 102, 103)
            ),
        );

        $query = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query);

        // Call get_union_product_ids via reflection
        $method = $reflection->getMethod('get_union_product_ids');
        $method->setAccessible(true);

        $result = $method->invoke($query, array('disco', 'duro'));

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContains(101, $result);
        $this->assertContains(102, $result);
        $this->assertContains(103, $result);

        unset($wpdb->mock_results);
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
     * Test multi-word search with synonyms uses AND logic between distinct words.
     */
    public function test_multi_word_search_with_synonyms_uses_and_logic() {
        $synonyms = "laptop, notebook, computer\ncamiseta, polo, shirt";
        TRB_Product_Search_Tests_Setup::set_option('trb_search_synonyms', $synonyms);

        $query = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query);

        // Simulate multi-word search with synonyms for each word
        // Search: "laptop camiseta" -> synonyms expand but AND logic applies
        $prop_terms = $reflection->getProperty('current_search_terms');
        $prop_terms->setAccessible(true);
        $prop_terms->setValue($query, array('laptop', 'camiseta'));

        // Set multi-word flag
        $prop_multi = $reflection->getProperty('is_multi_word_search');
        $prop_multi->setAccessible(true);
        $prop_multi->setValue($query, true);

        $wp_query = new \WP_Query();
        $sql = $query->custom_search_filter('', $wp_query);

        // Should use AND logic between the two words
        $this->assertStringContainsString(') AND (', $sql);
    }

    /**
     * Test multi-word search with typo correction enabled.
     */
    public function test_multi_word_search_with_typo_correction() {
        // Typo correction is handled at a higher level, but verify
        // that the search query handles the corrected term correctly
        $query = new \TRB_Product_Search\Search_Query();

        // Test that parse_search_terms normalizes the input
        $terms = $query->parse_search_terms('  DISCO   DURO  ');

        $this->assertIsArray($terms);
        $this->assertCount(2, $terms);
        $this->assertContains('disco', $terms);
        $this->assertContains('duro', $terms);
    }

    /**
     * Test multi-word search across title and content with AND logic.
     */
    public function test_multi_word_search_across_title_and_content() {
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

        // Should contain conditions for both title and content
        $this->assertStringContainsString('post_title', $sql);
        $this->assertStringContainsString('post_content', $sql);

        // Should use AND logic between terms
        $this->assertStringContainsString(') AND (', $sql);

        // Each term should have OR within (title OR content)
        $this->assertStringContainsString('OR', $sql);
    }

    /**
     * Test multi-word search with mixed special characters and spaces.
     */
    public function test_multi_word_search_with_mixed_special_characters() {
        $query = new \TRB_Product_Search\Search_Query();

        // Test various special character combinations
        $terms1 = $query->parse_search_terms('usb-c cable');
        $this->assertIsArray($terms1);
        $this->assertContains('usb-c', $terms1);
        $this->assertContains('cable', $terms1);

        $terms2 = $query->parse_search_terms('disco_duro ssd');
        $this->assertIsArray($terms2);
        $this->assertContains('disco_duro', $terms2);

        $terms3 = $query->parse_search_terms('product v2.0');
        $this->assertIsArray($terms3);
        $this->assertContains('product', $terms3);
        $this->assertContains('v2.0', $terms3);
    }

    /**
     * Test multi-word search with very long query (7+ words) limits to 5.
     */
    public function test_multi_word_search_limits_long_queries() {
        $query = new \TRB_Product_Search\Search_Query();

        // 7 words query
        $terms = $query->parse_search_terms('uno dos tres cuatro cinco seis siete');

        $this->assertIsArray($terms);
        $this->assertCount(5, $terms);
        $this->assertContains('uno', $terms);
        $this->assertContains('dos', $terms);
        $this->assertContains('tres', $terms);
        $this->assertContains('cuatro', $terms);
        $this->assertContains('cinco', $terms);
        // Last two should be excluded
        $this->assertNotContains('seis', $terms);
        $this->assertNotContains('siete', $terms);
    }

    /**
     * Test multi-word search with accented characters.
     */
    public function test_multi_word_search_with_accented_characters() {
        $query = new \TRB_Product_Search\Search_Query();

        $terms = $query->parse_search_terms('camiseta algodón roja');

        $this->assertIsArray($terms);
        $this->assertCount(3, $terms);
        $this->assertContains('camiseta', $terms);
        $this->assertContains('algodón', $terms);
        $this->assertContains('roja', $terms);
    }

    /**
     * Test that search_multi_word method handles OR logic correctly.
     */
    public function test_search_multi_word_handles_or_logic() {
        global $wpdb;

        // Set OR logic
        TRB_Product_Search_Tests_Setup::set_option('trb_search_logic', 'or');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');

        // Mock results for union
        $wpdb->mock_results = array(
            'get_col' => array(
                array(101, 102),
                array(103),
            ),
        );

        $query = new \TRB_Product_Search\Search_Query();
        $result = $query->search('disco duro');

        $this->assertInstanceOf('\WP_Query', $result);

        unset($wpdb->mock_results);
    }

    /**
     * Test that search_multi_word method handles AND logic correctly.
     */
    public function test_search_multi_word_handles_and_logic() {
        global $wpdb;

        // Set AND logic (default)
        TRB_Product_Search_Tests_Setup::set_option('trb_search_logic', 'and');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');

        // Mock results for intersection
        $wpdb->mock_results = array(
            'get_col' => array(
                array(101, 102),
                array(101),
            ),
        );

        $query = new \TRB_Product_Search\Search_Query();
        $result = $query->search('disco duro');

        $this->assertInstanceOf('\WP_Query', $result);

        unset($wpdb->mock_results);
    }

    /**
     * Test multi-word search with numeric terms.
     */
    public function test_multi_word_search_with_numeric_terms() {
        $query = new \TRB_Product_Search\Search_Query();

        $terms = $query->parse_search_terms('iphone 15 pro');

        $this->assertIsArray($terms);
        $this->assertCount(3, $terms);
        $this->assertContains('iphone', $terms);
        $this->assertContains('15', $terms);
        $this->assertContains('pro', $terms);
    }

    /**
     * Test multi-word search with single character words filtered.
     */
    public function test_multi_word_search_filters_single_character_words() {
        $query = new \TRB_Product_Search\Search_Query();

        // Single characters should be filtered out
        $terms = $query->parse_search_terms('a b c test');

        $this->assertIsArray($terms);
        // Only 'test' should remain (3+ chars, not a stop word)
        $this->assertCount(1, $terms);
        $this->assertContains('test', $terms);
        $this->assertNotContains('a', $terms);
        $this->assertNotContains('b', $terms);
        $this->assertNotContains('c', $terms);
    }

    /**
     * Test multi-word search with Spanish stop words.
     */
    public function test_multi_word_search_filters_spanish_stop_words() {
        $query = new \TRB_Product_Search\Search_Query();

        $terms = $query->parse_search_terms('el la los las un una camiseta');

        $this->assertIsArray($terms);
        // Only 'camiseta' should remain
        $this->assertCount(1, $terms);
        $this->assertContains('camiseta', $terms);
        $this->assertNotContains('el', $terms);
        $this->assertNotContains('la', $terms);
        $this->assertNotContains('los', $terms);
        $this->assertNotContains('las', $terms);
        $this->assertNotContains('un', $terms);
        $this->assertNotContains('una', $terms);
    }

    /**
     * Test multi-word search preserves word order in relevance scoring.
     */
    public function test_multi_word_search_preserves_word_order_in_relevance() {
        global $wpdb;

        TRB_Product_Search_Tests_Setup::set_option('trb_search_orderby', 'relevance');

        $query = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query);

        $method = $reflection->getMethod('build_relevance_orderby');
        $method->setAccessible(true);

        $terms = array('disco', 'duro', 'ssd');
        $orderby = $method->invoke($query, $terms, $wpdb);

        // Should contain exact phrase with words in original order
        $this->assertStringContainsString("post_title LIKE '%disco duro ssd%'", $orderby);

        // Should NOT contain scrambled order as exact match
        $this->assertStringNotContainsString("post_title LIKE '%ssd duro disco%'", $orderby);
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
