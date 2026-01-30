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
     * @param string|array $term Search term or array of terms.
     * @return array Array of product IDs.
     */
    public function get_matching_product_ids($term)
    {
        if (!$this->is_enabled()) {
            return array();
        }

        // If array passed, delegate to multi-word method
        if (is_array($term)) {
            return $this->get_matching_product_ids_for_terms($term);
        }

        global $wpdb;

        $wildcard = '%';
        $like_term = $wpdb->esc_like($term);

        // Query única con CASE para resolver variaciones en SQL
        $sql = $wpdb->prepare(
            "SELECT DISTINCT CASE
                WHEN p.post_type = 'product_variation' AND p.post_parent > 0
                THEN p.post_parent
                ELSE p.ID
            END as product_id
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_sku'
            AND pm.meta_value LIKE %s",
            $wildcard . $like_term . $wildcard
        );

        $product_ids = $wpdb->get_col($sql);

        return array_map('intval', $product_ids ?: array());
    }

    /**
     * Get product IDs matching ALL terms (intersection).
     *
     * @param array $terms Array of search terms.
     * @return array Array of product IDs matching all terms.
     */
    private function get_matching_product_ids_for_terms($terms)
    {
        if (empty($terms)) {
            return array();
        }

        // Get matches for each term
        $term_results = array();
        foreach ($terms as $term) {
            $term_results[] = $this->get_matching_product_ids($term);
        }

        // Intersection: IDs present in ALL results
        $intersection = $term_results[0];
        for ($i = 1; $i < count($term_results); $i++) {
            $intersection = array_intersect($intersection, $term_results[$i]);
        }

        return array_values($intersection);
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
