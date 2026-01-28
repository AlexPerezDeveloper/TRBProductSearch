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
     * Execute the search.
     *
     * @param string $term Search term.
     * @return \WP_Query The query result.
     */
    public function search($term)
    {
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

        // Allow modifying args
        $args = apply_filters('trb_product_search_args', $args, $term);

        // If we have multiple terms (synonyms found), we need a custom search query
        if (count($search_terms) > 1) {
            $this->current_search_terms = $search_terms;
            add_filter('posts_search', array($this, 'synonym_search_filter'), 10, 2);
            $args['s'] = $term; // Trigger search logic

            $query = new \WP_Query($args);

            remove_filter('posts_search', array($this, 'synonym_search_filter'), 10);
            $this->current_search_terms = array();
        } else {
            $args['s'] = $term;
            $query = new \WP_Query($args);
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
}
