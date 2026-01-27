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
            's' => $term,
            'posts_per_page' => 10, // Default limit, could be configurable
            //'tax_query'    => array(), // For future filtering
        );

        // Allow modifying args
        $args = apply_filters('trb_product_search_args', $args, $term);

        $query = new \WP_Query($args);

        return $query;
    }
}
