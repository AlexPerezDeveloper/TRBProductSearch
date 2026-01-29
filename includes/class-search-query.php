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
     * Execute the search.
     *
     * @param string $term Search term.
     * @return \WP_Query The query result.
     */
    public function search($term)
    {
        // Initialize search instances using singleton pattern
        $this->sku_search = SKU_Search::get_instance();
        $this->attributes_search = Attributes_Search::get_instance();

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

        // Build OR conditions for all search terms (synonyms)
        $conditions = array();
        foreach ($this->current_search_terms as $t) {
            $term = esc_sql($wpdb->esc_like($t));
            // Search in title and content for partial matches
            $conditions[] = "({$wpdb->posts}.post_title LIKE '{$wildcard}{$term}{$wildcard}')";
            $conditions[] = "({$wpdb->posts}.post_content LIKE '{$wildcard}{$term}{$wildcard}')";
        }

        // Add ID matches if any
        if (!empty($this->matched_product_ids)) {
            $ids_list = implode(',', array_map('intval', $this->matched_product_ids));
            $conditions[] = "({$wpdb->posts}.ID IN ($ids_list))";
        }

        // Combine with OR - any term match is good enough
        $search = ' AND (' . implode(' OR ', $conditions) . ')';

        return $search;
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

        $term = esc_sql($wpdb->esc_like($wp_query->query_vars['s']));

        // Use the meta_value from the joined postmeta table
        // The JOIN is added in the search() method
        $sku_priority = "IF(mt_sku.meta_value = '{$term}', 1, 0) DESC";

        return "{$sku_priority}, {$wpdb->posts}.post_title ASC";
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
}
