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
    }

    /**
     * Load necessary files.
     */
    private function load_includes()
    {
        require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-search-form.php';
        require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-search-query.php';
        require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-search-results.php';
    }

    /**
     * Register hooks.
     */
    private function register_hooks()
    {
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
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
