<?php
namespace TRB_Product_Search;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SKU_Search
 *
 * Handles SKU-based product search.
 */
class SKU_Search
{

    /**
     * Instance of the class.
     *
     * @var SKU_Search
     */
    private static $instance = null;

    /**
     * Get the instance of the class.
     *
     * @return SKU_Search
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
        // Private constructor.
    }

    /**
     * Check if SKU search is enabled.
     *
     * @return bool
     */
    public function is_enabled()
    {
        return '1' === get_option('trb_search_sku_enabled', '0');
    }

    /**
     * Get matching product IDs for SKU search.
     *
     * @param string $term Search term.
     * @return array Array of product IDs.
     */
    public function get_matching_product_ids($term)
    {
        if (!$this->is_enabled()) {
            return array();
        }

        global $wpdb;

        // Search for SKU in postmeta
        $wildcard = '%';
        $like_term = $wpdb->esc_like($term);
        
        $sql = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_sku' 
            AND meta_value LIKE %s",
            $wildcard . $like_term . $wildcard
        );

        $results = $wpdb->get_col($sql);

        if (empty($results)) {
            return array();
        }

        // Resolve parents for variations
        $product_ids = array();
        $variation_ids = array();

        // Separate IDs to check types/parents
        // We can do this with a single query to wp_posts to check post_type and post_parent
        $ids_placeholder = implode(',', array_map('intval', $results));
        
        $posts_sql = "SELECT ID, post_parent, post_type FROM {$wpdb->posts} WHERE ID IN ($ids_placeholder)";
        $posts = $wpdb->get_results($posts_sql);

        foreach ($posts as $post) {
            if ($post->post_type === 'product_variation' && $post->post_parent > 0) {
                $product_ids[] = $post->post_parent;
            } else {
                $product_ids[] = $post->ID;
            }
        }

        return array_unique($product_ids);
    }

    /**
     * Get exact SKU match product ID.
     *
     * @param string $sku SKU to search.
     * @return int|null Product ID or null if not found.
     */
    public function get_exact_sku_match($sku)
    {
        if (!$this->is_enabled()) {
            return null;
        }

        global $wpdb;
        $product_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = '_sku'
                AND meta_value = %s
                LIMIT 1",
                $sku
            )
        );

        return $product_id ? (int) $product_id : null;
    }
}
