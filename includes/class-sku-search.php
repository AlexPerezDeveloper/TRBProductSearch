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
     * Build meta query for SKU search.
     *
     * @param string $term Search term.
     * @return array|null Meta query array or null if disabled.
     */
    public function build_meta_query($term)
    {
        if (!$this->is_enabled()) {
            return null;
        }

        return array(
            'sku_clause' => array(
                'key'     => '_sku',
                'value'   => $term,
                'compare' => 'LIKE',
            )
        );
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
