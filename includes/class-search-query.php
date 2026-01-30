<?php
namespace TRB_Product_Search;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Search_Query
 *
 * Handles the product search queries.
 */
class Search_Query
{

    /**
     * Search terms for the current query.
     *
     * @var array
     */
    private $current_search_terms = array();

    /**
     * Track if orderby join was added.
     *
     * @var bool
     */
    private $orderby_join_added = false;

    /**
     * SKU search instance.
     *
     * @var SKU_Search
     */
    private $sku_search;

    /**
     * Attributes search instance.
     *
     * @var Attributes_Search
     */
    private $attributes_search;

    /**
     * Matched product IDs from SKU and Attributes.
     *
     * @var array
     */
    private $matched_product_ids = array();

    /**
     * Original search term before correction.
     *
     * @var string|null
     */
    private $original_term = null;

    /**
     * Corrected search term (if correction was applied).
     *
     * @var string|null
     */
    private $corrected_term = null;

    /**
     * Flag to track if this is a multi-word search.
     *
     * @var bool
     */
    private $is_multi_word_search = false;

    /**
     * Execute the search.
     *
     * @param string $term Search term.
     * @return \WP_Query The query result.
     */
    public function search($term)
    {
        // Store the original search term
        $this->original_term = $term;

        // Initialize search instances using singleton pattern
        $this->sku_search = SKU_Search::get_instance();
        $this->attributes_search = Attributes_Search::get_instance();

        // Tokenize the search term
        $tokens = $this->parse_search_terms($term);

        // If single word (or empty after filtering), use existing single-word logic
        if (count($tokens) <= 1) {
            return $this->search_single_word($term);
        }

        // Multi-word search logic
        return $this->search_multi_word($term, $tokens);
    }

    /**
     * Search with single word logic (backward compatible).
     *
     * @param string $term Search term.
     * @return \WP_Query The query result.
     */
    private function search_single_word($term)
    {
        // Check cache
        $cache = Cache_Manager::get_instance();
        $cache_key = $cache->get_search_key($term);
        $cached_ids = $cache->get($cache_key);

        if (false !== $cached_ids) {
            $cache->debug("Hit for term: $term");
            // If we have cached IDs, we can potentially return early or construct query faster
            // BUT, WP_Query returns an object, not just IDs.
            // For now, let's cache the MATCHED IDs (expensive part), and let WP_Query run the final easy fetch.
            $this->matched_product_ids = $cached_ids;
            $from_cache = true;
        } else {
            $cache->debug("Miss for term: $term");
            $from_cache = false;
        }

        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 10,
        );

        // Check for synonyms
        $synonyms_option = get_option('trb_search_synonyms', '');
        $search_terms = array($term); // Default to just the original term

        if (!empty($synonyms_option)) {
            $synonym_groups = explode("\n", $synonyms_option);
            foreach ($synonym_groups as $group) {
                $group_terms = array_map('trim', explode(',', $group));

                // If the search term is in this group, include all terms from the group
                // Case-insensitive check
                $found = false;
                foreach ($group_terms as $group_term) {
                    if (mb_strtolower($group_term) === mb_strtolower($term)) {
                        $found = true;
                        break;
                    }
                }

                if ($found) {
                    $search_terms = array_unique(array_merge($search_terms, $group_terms));
                }
            }
        }

        if (!$from_cache) {
            // Get matching IDs from SKU (includes variations parents)
            $sku_ids = $this->sku_search->get_matching_product_ids($term);

            // Get matching IDs from Attributes
            $attr_ids = $this->attributes_search->get_matching_product_ids($term);

            // Merge and unique IDs
            $this->matched_product_ids = array_unique(array_merge($sku_ids, $attr_ids));

            // Cache the result
            $cache->set($cache_key, $this->matched_product_ids);
        }

        // Apply ordering based on settings
        $orderby_setting = get_option('trb_search_orderby', 'relevance');
        switch ($orderby_setting) {
            case 'popularity':
                $args['meta_key'] = '_total_sales';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'price_asc':
                $args['meta_key'] = '_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                break;
            case 'price_desc':
                $args['meta_key'] = '_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'date':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
            // relevance is handled by the priority_orderby filter
        }

        // Allow modifying args
        $args = apply_filters('trb_product_search_args', $args, $term);

        // Always use custom search filter for partial matching and ID inclusion
        $this->current_search_terms = $search_terms;
        add_filter('posts_search', array($this, 'custom_search_filter'), 10, 2);

        // Add join for SKU ordering if SKU search is enabled
        // We always add this to ensure we can sort by SKU match priority
        if ($this->sku_search->is_enabled()) {
            add_filter('posts_join', array($this, 'join_postmeta_for_orderby'), 10, 2);
            add_filter('posts_orderby', array($this, 'priority_orderby'), 10, 2);
        }

        $args['s'] = $term; // Trigger search logic

        $query = new \WP_Query($args);

        // Cleanup filters
        remove_filter('posts_search', array($this, 'custom_search_filter'), 10);
        if ($this->sku_search->is_enabled()) {
            remove_filter('posts_join', array($this, 'join_postmeta_for_orderby'), 10);
            remove_filter('posts_orderby', array($this, 'priority_orderby'), 10);
        }
        $this->current_search_terms = array();
        $this->matched_product_ids = array();

        // If no results and term is eligible for correction
        if (!$query->have_posts() && strlen($term) >= 4) {
            $corrector = \TRB_Product_Search\Typo_Corrector::get_instance();
            $suggestion = $corrector->correct($term);

            if ($suggestion) {
                $this->corrected_term = $suggestion;

                // Re-run search with corrected term
                // Need to rebuild args with the corrected term
                // Also need to re-apply synonyms, SKU, and attributes logic

                // Get synonyms for corrected term
                $corrected_search_terms = array($suggestion);

                if (!empty($synonyms_option)) {
                    $synonym_groups = explode("\n", $synonyms_option);
                    foreach ($synonym_groups as $group) {
                        $group_terms = array_map('trim', explode(',', $group));

                        // If the corrected term is in this group, include all terms from the group
                        $found = false;
                        foreach ($group_terms as $group_term) {
                            if (mb_strtolower($group_term) === mb_strtolower($suggestion)) {
                                $found = true;
                                break;
                            }
                        }

                        if ($found) {
                            $corrected_search_terms = array_unique(array_merge($corrected_search_terms, $group_terms));
                        }
                    }
                }

                // Get matching IDs from SKU and Attributes for corrected term
                $corrected_sku_ids = $this->sku_search->get_matching_product_ids($suggestion);
                $corrected_attr_ids = $this->attributes_search->get_matching_product_ids($suggestion);
                $this->matched_product_ids = array_unique(array_merge($corrected_sku_ids, $corrected_attr_ids));

                // Update args for corrected search
                $args['s'] = $suggestion;

                // Re-apply filters for the corrected search
                $this->current_search_terms = $corrected_search_terms;
                add_filter('posts_search', array($this, 'custom_search_filter'), 10, 2);

                if ($this->sku_search->is_enabled()) {
                    add_filter('posts_join', array($this, 'join_postmeta_for_orderby'), 10, 2);
                    add_filter('posts_orderby', array($this, 'priority_orderby'), 10, 2);
                }

                $query = new \WP_Query($args);

                // Cleanup filters again
                remove_filter('posts_search', array($this, 'custom_search_filter'), 10);
                if ($this->sku_search->is_enabled()) {
                    remove_filter('posts_join', array($this, 'join_postmeta_for_orderby'), 10);
                    remove_filter('posts_orderby', array($this, 'priority_orderby'), 10);
                }
                $this->current_search_terms = array();
                $this->matched_product_ids = array();
            }
        }

        return $query;
    }

    /**
     * Search with multi-word logic (AND or OR based on settings).
     *
     * @param string $term Original search term.
     * @param array  $tokens Array of parsed search tokens.
     * @return \WP_Query The query result.
     */
    private function search_multi_word($term, $tokens)
    {
        // Determine search logic from settings
        $search_logic = get_option('trb_search_logic', 'and');

        // Check cache with tokens and logic for multi-word queries
        $cache = Cache_Manager::get_instance();
        $cache_key = $cache->get_search_key($term . '|' . implode(',', $tokens) . '|' . $search_logic);
        $cached_ids = $cache->get($cache_key);

        if (false !== $cached_ids) {
            $cache->debug("Hit for multi-word term: $term (logic: $search_logic)");
            $this->matched_product_ids = $cached_ids;
            $from_cache = true;
        } else {
            $cache->debug("Miss for multi-word term: $term (logic: $search_logic)");
            $from_cache = false;
        }

        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 10,
        );

        if (!$from_cache) {
            // Get product IDs based on search logic
            if ($search_logic === 'or') {
                // OR logic: products matching ANY term
                $this->matched_product_ids = $this->get_union_product_ids($tokens);
            } else {
                // AND logic (default): products matching ALL terms
                $this->matched_product_ids = $this->get_intersecting_product_ids($tokens);
            }

            // Cache the result
            $cache->set($cache_key, $this->matched_product_ids);
        }

        // Apply ordering based on settings
        $orderby_setting = get_option('trb_search_orderby', 'relevance');
        switch ($orderby_setting) {
            case 'popularity':
                $args['meta_key'] = '_total_sales';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'price_asc':
                $args['meta_key'] = '_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                break;
            case 'price_desc':
                $args['meta_key'] = '_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'date':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
            // relevance is handled by the priority_orderby filter
        }

        // Allow modifying args
        $args = apply_filters('trb_product_search_args', $args, $term);

        // Use custom search filter with AND logic for multi-word
        $this->current_search_terms = $tokens;
        $this->is_multi_word_search = true;
        add_filter('posts_search', array($this, 'custom_search_filter'), 10, 2);

        // Add join for SKU ordering if SKU search is enabled
        if ($this->sku_search->is_enabled()) {
            add_filter('posts_join', array($this, 'join_postmeta_for_orderby'), 10, 2);
            add_filter('posts_orderby', array($this, 'priority_orderby'), 10, 2);
        }

        $args['s'] = $term; // Trigger search logic

        $query = new \WP_Query($args);

        // Cleanup filters
        remove_filter('posts_search', array($this, 'custom_search_filter'), 10);
        if ($this->sku_search->is_enabled()) {
            remove_filter('posts_join', array($this, 'join_postmeta_for_orderby'), 10);
            remove_filter('posts_orderby', array($this, 'priority_orderby'), 10);
        }
        $this->current_search_terms = array();
        $this->matched_product_ids = array();
        $this->is_multi_word_search = false;

        return $query;
    }

    /**
     * Get product IDs matching ALL terms (intersection from SKU and Attributes).
     *
     * @param array $terms Array of search terms.
     * @return array Array of product IDs matching all terms.
     */
    private function get_intersecting_product_ids($terms)
    {
        // Ensure search instances are initialized
        if (null === $this->sku_search) {
            $this->sku_search = SKU_Search::get_instance();
        }
        if (null === $this->attributes_search) {
            $this->attributes_search = Attributes_Search::get_instance();
        }

        // Get SKU matches (intersection handled by SKU_Search)
        $sku_ids = $this->sku_search->is_enabled() ? $this->sku_search->get_matching_product_ids($terms) : array();

        // Get attribute matches (intersection handled by Attributes_Search)
        $attr_ids = $this->attributes_search->is_enabled() ? $this->attributes_search->get_matching_product_ids($terms) : array();

        // Merge and return unique IDs
        return array_unique(array_merge($sku_ids, $attr_ids));
    }

    /**
     * Get product IDs matching ANY term (union from SKU and Attributes).
     *
     * @param array $terms Array of search terms.
     * @return array Array of product IDs matching any term.
     */
    private function get_union_product_ids($terms)
    {
        // Ensure search instances are initialized
        if (null === $this->sku_search) {
            $this->sku_search = SKU_Search::get_instance();
        }
        if (null === $this->attributes_search) {
            $this->attributes_search = Attributes_Search::get_instance();
        }

        $all_ids = array();

        // For OR logic, we collect IDs matching ANY term
        foreach ($terms as $term) {
            // Get SKU matches for this term
            if ($this->sku_search->is_enabled()) {
                $sku_ids = $this->sku_search->get_matching_product_ids($term);
                $all_ids = array_merge($all_ids, $sku_ids);
            }

            // Get attribute matches for this term
            if ($this->attributes_search->is_enabled()) {
                $attr_ids = $this->attributes_search->get_matching_product_ids($term);
                $all_ids = array_merge($all_ids, $attr_ids);
            }
        }

        // Return unique IDs
        return array_unique($all_ids);
    }

    /**
     * Modify the search SQL to include synonyms and better partial matching.
     *
     * @param string   $search   The generated search SQL.
     * @param \WP_Query $wp_query The WP_Query instance.
     * @return string Modified search SQL.
     */
    public function custom_search_filter($search, $wp_query)
    {
        global $wpdb;

        if (empty($this->current_search_terms)) {
            return $search;
        }

        $wildcard = '%';

        // For multi-word search, use AND logic between terms
        // For single-word with synonyms, use OR logic
        if ($this->is_multi_word_search) {
            $search = $this->build_multi_word_search_sql($wpdb, $wildcard);
        } else {
            $search = $this->build_single_word_search_sql($wpdb, $wildcard);
        }

        return $search;
    }

    /**
     * Build SQL for single-word search (with synonyms support - OR logic).
     *
     * @param object $wpdb     WordPress database object.
     * @param string $wildcard Wildcard character for LIKE.
     * @return string Search SQL.
     */
    private function build_single_word_search_sql($wpdb, $wildcard)
    {
        // Build OR conditions for all search terms (synonyms)
        $conditions = array();
        foreach ($this->current_search_terms as $t) {
            $term = esc_sql($wpdb->esc_like($t));

            $conditions[] = "({$wpdb->posts}.post_title LIKE '{$term}{$wildcard}')"; // Prefix
            $conditions[] = "({$wpdb->posts}.post_title LIKE '{$wildcard}{$term}{$wildcard}')"; // Contains
            $conditions[] = "({$wpdb->posts}.post_content LIKE '{$wildcard}{$term}{$wildcard}')";
        }

        // Add ID matches if any
        if (!empty($this->matched_product_ids)) {
            $ids_list = implode(',', array_map('intval', $this->matched_product_ids));
            $conditions[] = "({$wpdb->posts}.ID IN ($ids_list))";
        }

        // Combine with OR - any term match is good enough
        return ' AND (' . implode(' OR ', $conditions) . ')';
    }

    /**
     * Build SQL for multi-word search (AND or OR logic based on settings).
     *
     * @param object $wpdb     WordPress database object.
     * @param string $wildcard Wildcard character for LIKE.
     * @return string Search SQL.
     */
    private function build_multi_word_search_sql($wpdb, $wildcard)
    {
        // Get search logic from settings
        $search_logic = get_option('trb_search_logic', 'and');

        // Build conditions for each term
        // Each term can match in title (prefix or contains) OR content
        $term_conditions = array();

        foreach ($this->current_search_terms as $t) {
            $term = esc_sql($wpdb->esc_like($t));

            // For each term, it can match in title (prefix or contains) OR content
            $single_term_conditions = array(
                "({$wpdb->posts}.post_title LIKE '{$term}{$wildcard}')", // Prefix
                "({$wpdb->posts}.post_title LIKE '{$wildcard}{$term}{$wildcard}')", // Contains
                "({$wpdb->posts}.post_content LIKE '{$wildcard}{$term}{$wildcard}')",
            );

            $term_conditions[] = '(' . implode(' OR ', $single_term_conditions) . ')';
        }

        // Combine term conditions based on search logic
        if ($search_logic === 'or') {
            // OR logic: ANY term can match
            $where_clause = '(' . implode(' OR ', $term_conditions) . ')';
        } else {
            // AND logic (default): ALL terms must match
            $where_clause = '(' . implode(' AND ', $term_conditions) . ')';
        }

        // Add ID matches if any (OR with the term conditions)
        if (!empty($this->matched_product_ids)) {
            $ids_list = implode(',', array_map('intval', $this->matched_product_ids));
            $where_clause = "({$where_clause} OR {$wpdb->posts}.ID IN ($ids_list))";
        }

        return ' AND ' . $where_clause;
    }

    /**
     * Join postmeta for SKU ordering.
     *
     * @param string    $join     Current join clause.
     * @param \WP_Query $wp_query WP_Query instance.
     * @return string Modified join clause.
     */
    public function join_postmeta_for_orderby($join, $wp_query)
    {
        global $wpdb;

        // Ensure we only join once
        if (strpos($join, 'mt_sku') === false) {
            $join .= " LEFT JOIN {$wpdb->postmeta} AS mt_sku ON ({$wpdb->posts}.ID = mt_sku.post_id AND mt_sku.meta_key = '_sku') ";
        }

        return $join;
    }

    /**
     * Priority ordering for exact SKU matches.
     *
     * @param string    $orderby  Current orderby clause.
     * @param \WP_Query $wp_query WP_Query instance.
     * @return string Modified orderby clause.
     */
    public function priority_orderby($orderby, $wp_query)
    {
        global $wpdb;

        if (empty($wp_query->query_vars['s'])) {
            return $orderby;
        }

        $orderby_setting = get_option('trb_search_orderby', 'relevance');

        // For multi-word search with relevance ordering, use relevance scoring
        if ($this->is_multi_word_search && $orderby_setting === 'relevance' && !empty($this->current_search_terms)) {
            return $this->build_relevance_orderby($this->current_search_terms, $wpdb);
        }

        $term = esc_sql($wpdb->esc_like($wp_query->query_vars['s']));

        // Use the meta_value from the joined postmeta table
        // The JOIN is added in the search() method
        $sku_priority = "IF(mt_sku.meta_value = '{$term}', 1, 0) DESC";

        if ($orderby_setting === 'relevance') {
            return "{$sku_priority}, {$wpdb->posts}.post_title ASC";
        }

        // For other orderings, prepend SKU priority to the existing orderby string
        if (!empty($orderby)) {
            return "{$sku_priority}, {$orderby}";
        }

        return "{$sku_priority}, {$wpdb->posts}.post_title ASC";
    }

    /**
     * Build relevance scoring ORDER BY clause for multi-word search.
     *
     * Priority 1: Exact phrase match in title (score: 100)
     * Priority 2: All words in title (any order, score: 50)
     * Priority 3: Count of matching words in title (10 per word)
     * Priority 4: SKU exact match (score: 25)
     * Final: Alphabetical tie-breaker
     *
     * @param array  $terms Array of search terms.
     * @param object $wpdb  WordPress database object.
     * @return string ORDER BY clause.
     */
    private function build_relevance_orderby($terms, $wpdb)
    {
        $orderby_clauses = array();

        // Build the exact phrase for priority 1
        $exact_phrase = implode(' ', $terms);
        $safe_phrase = esc_sql($wpdb->esc_like($exact_phrase));

        // Priority 1: Exact phrase match in title (highest score: 100)
        $orderby_clauses[] = "CASE WHEN {$wpdb->posts}.post_title LIKE '%{$safe_phrase}%' THEN 100 ELSE 0 END DESC";

        // Priority 2: All words in title (any order, score: 50)
        $all_words_conditions = array();
        foreach ($terms as $t) {
            $safe_term = esc_sql($wpdb->esc_like($t));
            $all_words_conditions[] = "{$wpdb->posts}.post_title LIKE '%{$safe_term}%'";
        }
        $all_words_sql = implode(' AND ', $all_words_conditions);
        $orderby_clauses[] = "CASE WHEN ({$all_words_sql}) THEN 50 ELSE 0 END DESC";

        // Priority 3: Count of matching words in title (10 per word)
        $word_count_cases = array();
        foreach ($terms as $t) {
            $safe_term = esc_sql($wpdb->esc_like($t));
            $word_count_cases[] = "CASE WHEN {$wpdb->posts}.post_title LIKE '%{$safe_term}%' THEN 10 ELSE 0 END";
        }
        $word_count_sql = implode(' + ', $word_count_cases);
        $orderby_clauses[] = "({$word_count_sql}) DESC";

        // Priority 4: SKU exact match (score: 25)
        $orderby_clauses[] = "CASE WHEN mt_sku.meta_value = '{$safe_phrase}' THEN 25 ELSE 0 END DESC";

        // Final tie-breaker: alphabetical by title
        $orderby_clauses[] = "{$wpdb->posts}.post_title ASC";

        return implode(', ', $orderby_clauses);
    }

    /**
     * Get the original search term.
     *
     * @return string|null
     */
    public function get_original_term()
    {
        return $this->original_term;
    }

    /**
     * Get the corrected search term.
     *
     * @return string|null
     */
    public function get_corrected_term()
    {
        return $this->corrected_term;
    }

    /**
     * Check if a correction was applied.
     *
     * @return bool
     */
    public function has_correction()
    {
        return $this->corrected_term !== null;
    }

    /**
     * Get correction metadata (for template use).
     *
     * @return array{original: string|null, corrected: string|null}
     */
    public function get_correction_info()
    {
        return array(
            'original' => $this->original_term,
            'corrected' => $this->corrected_term,
        );
    }

    /**
     * Parse search terms into array of tokens.
     *
     * Normalizes input, splits on whitespace, filters short words,
     * removes Spanish stop words, and limits to 5 tokens max.
     *
     * @param string $term Raw search term.
     * @return array Array of tokens (min 2 chars, stop words removed).
     */
    public function parse_search_terms($term)
    {
        // Normalize: lowercase, trim
        $term = mb_strtolower(trim($term));

        // Split on whitespace
        $tokens = preg_split('/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY);

        // Filter: minimum 2 characters
        $tokens = array_filter($tokens, function ($t) {
            return mb_strlen($t) >= 2;
        });

        // Remove stop words (Spanish)
        $stop_words = array('el', 'la', 'de', 'en', 'y', 'a', 'los', 'las', 'un', 'una', 'del', 'al', 'con', 'por', 'para');
        $tokens = array_diff($tokens, $stop_words);

        // Limit to 5 words max (performance)
        $tokens = array_slice($tokens, 0, 5);

        return array_values($tokens);
    }
}
