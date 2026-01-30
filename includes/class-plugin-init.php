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
        register_activation_hook(TRB_PRODUCT_SEARCH_FILE, array($this, 'activation_routine'));
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
