<?php
namespace TRB_Product_Search;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Plugin_Init
 *
 * Handles the initialization of the plugin.
 */
class Plugin_Init
{

    /**
     * Instance of the class.
     *
     * @var Plugin_Init
     */
    private static $instance = null;

    /**
     * Get the instance of the class.
     *
     * @return Plugin_Init
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
        // Private constructor to enforce singleton pattern.
    }

    /**
     * Initialize the plugin.
     */
    public function init()
    {
        // Check dependencies effectively after plugins_loaded
        add_action('plugins_loaded', array($this, 'check_dependencies'));
    }

    /**
     * Check if WooCommerce is active.
     */
    public function check_dependencies()
    {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        $this->load_includes();
        $this->register_hooks();
        \TRB_Product_Search\Ajax_Handler::get_instance()->init();
        \TRB_Product_Search\Settings::get_instance()->init();
        \TRB_Product_Search\Typo_Corrector::get_instance()->init();
        \TRB_Product_Search\Cache_Manager::get_instance()->init();
        \TRB_Product_Search\Search_Analytics::get_instance()->init();

        if (is_admin()) {
            \TRB_Product_Search\Admin\Analytics_Dashboard::get_instance()->init();
        }
    }

    /**
     * Load necessary files.
     */
    private function load_includes()
    {
        require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-search-form.php';
        require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-search-query.php';
        require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-search-results.php';
        require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-ajax-handler.php';
        require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-settings.php';
        require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-typo-corrector.php';
        require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-sku-search.php';
        require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-attributes-search.php';
        require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-cache-manager.php';
        require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-search-analytics.php';

        // Admin classes
        if (is_admin()) {
            require_once TRB_PRODUCT_SEARCH_PATH . 'includes/admin/class-analytics-dashboard.php';
        }
    }

    /**
     * Register hooks.
     */
    private function register_hooks()
    {
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('pre_get_posts', array($this, 'extend_product_search'));
        register_activation_hook(TRB_PRODUCT_SEARCH_FILE, array($this, 'activation_routine'));
    }

    /**
     * Extend the native WooCommerce product search to include SKU and attributes.
     *
     * Hooks into pre_get_posts to modify the search query on product search pages.
     *
     * @param \WP_Query $query The WordPress query object.
     */
    public function extend_product_search($query)
    {
        // Only modify frontend search queries for products
        if (is_admin() || !$query->is_search() || !$query->is_main_query()) {
            return;
        }

        // Only modify product searches
        if ($query->get('post_type') !== 'product') {
            return;
        }

        $search_term = $query->get('s');
        if (empty($search_term)) {
            return;
        }

        // Get additional product IDs from SKU and attributes search
        $product_ids = $this->get_extended_search_results($search_term);

        if (!empty($product_ids)) {
            // Store the product IDs to use in posts_where filter
            $this->extended_product_ids = $product_ids;
            add_filter('posts_where', array($this, 'extend_search_where_clause'), 10, 2);
        }
    }

    /**
     * Store extended product IDs for use in filters.
     *
     * @var array
     */
    private $extended_product_ids = array();

    /**
     * Get product IDs from extended search (SKU and attributes).
     *
     * @param string $term Search term.
     * @return array Array of product IDs.
     */
    private function get_extended_search_results($term)
    {
        $product_ids = array();

        // Search by SKU if enabled
        if (class_exists('\TRB_Product_Search\SKU_Search')) {
            $sku_search = SKU_Search::get_instance();
            if ($sku_search->is_enabled()) {
                $sku_products = $sku_search->get_matching_product_ids($term);
                $product_ids = array_merge($product_ids, $sku_products);
            }
        }

        // Search by attributes if enabled
        if (class_exists('\TRB_Product_Search\Attributes_Search')) {
            $attr_search = Attributes_Search::get_instance();
            if ($attr_search->is_enabled()) {
                $attr_products = $attr_search->get_matching_product_ids($term);
                $product_ids = array_merge($product_ids, $attr_products);
            }
        }

        return array_unique($product_ids);
    }

    /**
     * Extend the WHERE clause to include products by SKU and attributes.
     *
     * This adds an OR condition to include products that match by SKU or attributes
     * in addition to the default search (title, content, excerpt).
     *
     * @param string $where The WHERE clause of the query.
     * @param \WP_Query $query The WordPress query object.
     * @return string Modified WHERE clause.
     */
    public function extend_search_where_clause($where, $query)
    {
        // Remove this filter immediately to prevent affecting other queries
        remove_filter('posts_where', array($this, 'extend_search_where_clause'), 10);

        // Only modify if we have extended product IDs and this is our target query
        if (empty($this->extended_product_ids) || !$query->is_search()) {
            return $where;
        }

        global $wpdb;

        // Sanitize product IDs
        $product_ids = array_map('intval', $this->extended_product_ids);
        $ids_string = implode(',', $product_ids);

        // Add OR condition to include products by ID
        // This extends the search without replacing the original conditions
        $where .= " OR ({$wpdb->posts}.ID IN ($ids_string) AND {$wpdb->posts}.post_type = 'product')";

        // Clear the stored IDs
        $this->extended_product_ids = array();

        return $where;
    }

    /**
     * Plugin activation routine to add database indexes.
     */
    public function activation_routine()
    {
        global $wpdb;

        // Add index to wp_postmeta for SKU searches if not exists
        // Note: meta_value is LONGTEXT, so we must specify length.
        $index_name = 'trb_sku_value_index';
        $meta_table = $wpdb->postmeta;

        // Check if index exists
        $index_exists = $wpdb->get_results("SHOW INDEX FROM $meta_table WHERE Key_name = '$index_name'");

        if (empty($index_exists)) {
            $wpdb->query("ALTER TABLE $meta_table ADD INDEX $index_name (meta_key(20), meta_value(50))");
        }

        // Add index for term relationships if needed (usually covered by core, but for specific optimization)
        // Core has (object_id, term_taxonomy_id) as PRIMARY and (term_taxonomy_id) as INDEX.
        // If we query by term_taxonomy_id AND object_id often, the primary key covers it.
        // So no need to add standard index there unless we have a different access pattern.

        // Create analytics table
        \TRB_Product_Search\Search_Analytics::create_table();

        // Set default options
        add_option('trb_analytics_enabled', true);
        add_option('trb_analytics_retention_days', 90);
        add_option('trb_analytics_track_guests', true);
    }

    /**
     * Register shortcodes.
     */
    public function register_shortcodes()
    {
        \TRB_Product_Search\Search_Form::get_instance()->register_shortcode();
    }

    /**
     * Enqueue scripts and styles.
     */
    public function enqueue_scripts()
    {
        wp_enqueue_style('trb-product-search-style', TRB_PRODUCT_SEARCH_URL . 'assets/css/search.css', array(), TRB_PRODUCT_SEARCH_VERSION);
        wp_enqueue_script('trb-product-search-js', TRB_PRODUCT_SEARCH_URL . 'assets/js/search.js', array('jquery'), TRB_PRODUCT_SEARCH_VERSION, true);

        wp_localize_script('trb-product-search-js', 'trb_search_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('trb_search_nonce'),
            'home_url' => home_url('/'),
            'strings' => array(
                'loading' => __('Loading...', 'trb-product-search'),
                'min_chars' => __('Minimum %d characters required', 'trb-product-search'),
                'view_all' => __('View all results', 'trb-product-search'),
                'error' => __('Error fetching results. Please try again.', 'trb-product-search'),
            ),
        ));
    }

    /**
     * Display a notice if WooCommerce is not active.
     */
    public function woocommerce_missing_notice()
    {
        ?>
        <div class="error">
            <p><?php esc_html_e('TRB Product Search requires WooCommerce to be installed and active.', 'trb-product-search'); ?>
            </p>
        </div>
        <?php
    }
}
