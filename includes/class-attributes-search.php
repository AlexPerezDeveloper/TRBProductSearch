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

        // 1. Find matching Term IDs
        // We look for terms in the selected taxonomies where the name matches the search term
        $taxonomies_placeholder = "'" . implode("','", array_map('esc_sql', $selected_taxonomies)) . "'";
        $wildcard = '%';
        $like_term = $wpdb->esc_like($term);

        $term_ids_sql = $wpdb->prepare(
            "SELECT DISTINCT t.term_id 
            FROM {$wpdb->terms} t 
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
            WHERE tt.taxonomy IN ($taxonomies_placeholder) 
            AND t.name LIKE %s",
            $wildcard . $like_term . $wildcard
        );

        $term_ids = $wpdb->get_col($term_ids_sql);

        if (empty($term_ids)) {
            return array();
        }

        // 2. Find Object IDs (Products) for these terms
        // term_taxonomy_id is usually same as term_id for 1:1, but strictly we should join or use term_taxonomy_ids.
        // WP term_relationships links object_id to term_taxonomy_id.
        // We need matching term_taxonomy_ids.
        
        $term_ids_placeholder = implode(',', array_map('intval', $term_ids));
        
        // Get tt_ids for these term_ids and taxonomies
        $tt_ids_sql = "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id IN ($term_ids_placeholder)";
        $tt_ids = $wpdb->get_col($tt_ids_sql);

        if (empty($tt_ids)) {
            return array();
        }

        $tt_ids_placeholder = implode(',', array_map('intval', $tt_ids));

        $objects_sql = "SELECT DISTINCT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ($tt_ids_placeholder)";
        $product_ids = $wpdb->get_col($objects_sql);

        return array_map('intval', $product_ids);
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
