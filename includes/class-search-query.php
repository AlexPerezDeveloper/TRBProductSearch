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
        // Initialize search instances
        $this->sku_search = new SKU_Search();
        $this->attributes_search = new Attributes_Search();

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

        // If we have multiple terms (synonyms found), we need a custom search query
        if (count($search_terms) > 1) {
            $this->current_search_terms = $search_terms;
            add_filter('posts_search', array($this, 'synonym_search_filter'), 10, 2);

            // Add priority ordering for exact SKU matches
            add_filter('posts_orderby', array($this, 'priority_orderby'), 10, 2);

            $args['s'] = $term; // Trigger search logic

            $query = new \WP_Query($args);

            remove_filter('posts_search', array($this, 'synonym_search_filter'), 10);
            remove_filter('posts_orderby', array($this, 'priority_orderby'), 10);
            $this->current_search_terms = array();
        } else {
            // Add priority ordering for exact SKU matches
            add_filter('posts_orderby', array($this, 'priority_orderby'), 10, 2);

            $args['s'] = $term;
            $query = new \WP_Query($args);

            remove_filter('posts_orderby', array($this, 'priority_orderby'), 10);
        }

        return $query;
    }

    /**
     * Modify the search SQL to include synonyms (OR logic).
     *
     * @param string   $search   The generated search SQL.
     * @param \WP_Query $wp_query The WP_Query instance.
     * @return string Modified search SQL.
     */
    public function synonym_search_filter($search, $wp_query)
    {
        global $wpdb;

        if (empty($this->current_search_terms)) {
            return $search;
        }

        $search = '';
        $n = !empty($wp_query->query_vars['exact']) ? '' : '%';

        $search .= " AND (";

        $first = true;
        foreach ($this->current_search_terms as $t) {
            $term = esc_sql($wpdb->esc_like($t));

            if (!$first) {
                $search .= " OR ";
            }

            $search .= "({$wpdb->posts}.post_title LIKE '{$n}{$term}{$n}') OR ({$wpdb->posts}.post_content LIKE '{$n}{$term}{$n}')";
            $first = false;
        }

        $search .= ")";

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

        // Prioritize exact SKU matches first, then by title
        $sku_priority = "IF(pm.meta_key = '_sku' AND pm.meta_value = '{$term}', 1, 0) DESC";

        return "{$sku_priority}, {$wpdb->posts}.post_title ASC";
    }
}
