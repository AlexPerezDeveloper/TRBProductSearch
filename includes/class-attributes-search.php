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
     * @param string|array $term Search term or array of terms.
     * @return array Array of product IDs.
     */
    public function get_matching_product_ids($term)
    {
        // If array passed, delegate to multi-word method
        if (is_array($term)) {
            return $this->get_matching_product_ids_for_terms($term);
        }

        if (!$this->is_enabled()) {
            return array();
        }

        $selected_attributes = $this->get_selected_attributes();
        if (empty($selected_attributes)) {
            return array();
        }

        global $wpdb;

        // Ensure taxonomies have pa_ prefix
        $taxonomies = array_map(function ($attr) {
            return (strpos($attr, 'pa_') === 0) ? $attr : 'pa_' . $attr;
        }, $selected_attributes);

        // Escapar taxonomías para uso seguro en IN clause
        $taxonomies_escaped = array_map('esc_sql', $taxonomies);
        $taxonomies_placeholder = "'" . implode("','", $taxonomies_escaped) . "'";
        $wildcard = '%';
        $like_term = $wpdb->esc_like($term);

        // Query para encontrar IDs de productos y resolver variaciones a padres
        $sql = $wpdb->prepare(
            "SELECT DISTINCT IF(p.post_type = 'product_variation', p.post_parent, p.ID) as product_id
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
            INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
            WHERE tt.taxonomy IN ($taxonomies_placeholder)
            AND t.name LIKE %s
            AND p.post_status = 'publish'",
            $wildcard . $like_term . $wildcard
        );

        $product_ids = $wpdb->get_col($sql);

        return array_unique(array_map('intval', $product_ids ?: array()));
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

        // Ensure taxonomies have pa_ prefix
        $taxonomies = array_map(function ($attr) {
            return (strpos($attr, 'pa_') === 0) ? $attr : 'pa_' . $attr;
        }, $selected);

        global $wpdb;
        $placeholders = implode(',', array_fill(0, count($taxonomies), '%s'));
        $query = $wpdb->prepare(
            "SELECT DISTINCT t.term_id, t.name, tt.taxonomy
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy IN ($placeholders)
            AND t.name LIKE %s",
            array_merge($taxonomies, array('%' . $wpdb->esc_like($term) . '%'))
        );

        return $wpdb->get_results($query);
    }
}
