<?php
namespace TRB_Product_Search;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Attributes_Search
 *
 * Handles product attribute search.
 */
class Attributes_Search
{

    /**
     * Instance of the class.
     *
     * @var Attributes_Search
     */
    private static $instance = null;

    /**
     * Get the instance of the class.
     *
     * @return Attributes_Search
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
     * Check if attributes search is enabled.
     *
     * @return bool
     */
    public function is_enabled()
    {
        return '1' === get_option('trb_search_attributes_enabled', '0');
    }

    /**
     * Get selected attributes for search.
     *
     * @return array Selected attribute slugs.
     */
    public function get_selected_attributes()
    {
        $selected = get_option('trb_search_selected_attributes', array());
        return is_array($selected) ? $selected : array();
    }

    /**
     * Get available product attributes.
     *
     * @return array Available attributes with taxonomy => label.
     */
    public function get_available_attributes()
    {
        if (!function_exists('wc_get_attribute_taxonomies')) {
            return array();
        }

        $attributes = wc_get_attribute_taxonomies();
        $result = array();

        foreach ($attributes as $attribute) {
            $taxonomy = 'pa_' . $attribute->attribute_name;
            $result[$taxonomy] = $attribute->attribute_label;
        }

        return $result;
    }

    /**
     * Get matching product IDs for attributes search.
     *
     * @param string $term Search term.
     * @return array Array of product IDs.
     */
    public function get_matching_product_ids($term)
    {
        if (!$this->is_enabled()) {
            return array();
        }

        $selected_taxonomies = $this->get_selected_attributes();
        if (empty($selected_taxonomies)) {
            return array();
        }

        global $wpdb;

        // Escapar taxonomías para uso seguro en IN clause
        $taxonomies = array_map('esc_sql', $selected_taxonomies);
        $taxonomies_placeholder = "'" . implode("','", $taxonomies) . "'";
        $wildcard = '%';
        $like_term = $wpdb->esc_like($term);

        // Query única con 3 JOINs
        $sql = $wpdb->prepare(
            "SELECT DISTINCT tr.object_id
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
            WHERE tt.taxonomy IN ($taxonomies_placeholder)
            AND t.name LIKE %s",
            $wildcard . $like_term . $wildcard
        );

        $product_ids = $wpdb->get_col($sql);

        return array_map('intval', $product_ids ?: array());
    }

    /**
     * Search for matching attribute terms.
     *
     * @param string $term Search term.
     * @return array Matching term objects.
     */
    public function search_attribute_terms($term)
    {
        $selected = $this->get_selected_attributes();
        if (empty($selected)) {
            return array();
        }

        global $wpdb;
        $placeholders = implode(',', array_fill(0, count($selected), '%s'));
        $query = $wpdb->prepare(
            "SELECT DISTINCT t.term_id, t.name, tt.taxonomy
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy IN ($placeholders)
            AND t.name LIKE %s",
            array_merge($selected, array('%' . $wpdb->esc_like($term) . '%'))
        );

        return $wpdb->get_results($query);
    }
}
