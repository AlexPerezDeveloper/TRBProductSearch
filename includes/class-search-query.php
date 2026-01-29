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

        // Add SKU meta query if enabled
        $sku_meta_query = $this->sku_search->build_meta_query($term);
        if ($sku_meta_query) {
            $args['meta_query'] = $sku_meta_query;
        }

        // Add attributes tax query if enabled
        $attributes_tax_query = $this->attributes_search->build_tax_query($term);
        if ($attributes_tax_query) {
            $args['tax_query'] = $attributes_tax_query;
        }

        // Allow modifying args
        $args = apply_filters('trb_product_search_args', $args, $term);

        // Always use custom search filter for better partial matching
        $this->current_search_terms = $search_terms;
        add_filter('posts_search', array($this, 'custom_search_filter'), 10, 2);

        // Add join for SKU ordering (only if not already using meta_query for SKU)
        $use_sku_join = true;
        if (isset($args['meta_query']) && !empty($args['meta_query'])) {
            // Check if SKU is already in meta_query
            $has_sku_meta = false;
            if (isset($args['meta_query']['sku_clause'])) {
                $has_sku_meta = true;
            } elseif (isset($args['meta_query']['relation']) && is_array($args['meta_query'])) {
                foreach ($args['meta_query'] as $key => $value) {
                    if (is_numeric($key) && isset($value['key']) && $value['key'] === '_sku') {
                        $has_sku_meta = true;
                        break;
                    }
                }
            }
            $use_sku_join = !$has_sku_meta;
        }

        if ($use_sku_join) {
            add_filter('posts_join', array($this, 'join_postmeta_for_orderby'), 10, 2);
            add_filter('posts_orderby', array($this, 'priority_orderby'), 10, 2);
        }

        $args['s'] = $term; // Trigger search logic

        $query = new \WP_Query($args);

        // Cleanup filters
        remove_filter('posts_search', array($this, 'custom_search_filter'), 10);
        if ($use_sku_join) {
            remove_filter('posts_join', array($this, 'join_postmeta_for_orderby'), 10);
            remove_filter('posts_orderby', array($this, 'priority_orderby'), 10);
        }
        $this->current_search_terms = array();

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

        // Combine with OR - any term match is good enough
        $search = ' AND (' . implode(' OR ', $conditions) . ')';

        return $search;
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
}
