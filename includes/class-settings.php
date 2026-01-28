<?php
namespace TRB_Product_Search;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Settings
 *
 * Handles the plugin settings page.
 */
class Settings
{

    /**
     * Instance of the class.
     *
     * @var Settings
     */
    private static $instance = null;

    /**
     * Get the instance of the class.
     *
     * @return Settings
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
     * Initialize hooks.
     */
    public function init()
    {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add settings page.
     */
    public function add_settings_page()
    {
        add_options_page(
            __('TRB Product Search', 'trb-product-search'),
            __('TRB Search', 'trb-product-search'),
            'manage_options',
            'trb-product-search',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings.
     */
    public function register_settings()
    {
        register_setting('trb_product_search_options', 'trb_search_synonyms', array('sanitize_callback' => array($this, 'sanitize_synonyms')));

        add_settings_section(
            'trb_search_general',
            __('General Settings', 'trb-product-search'),
            null,
            'trb-product-search'
        );

        add_settings_field(
            'trb_search_synonyms',
            __('Synonyms', 'trb-product-search'),
            array($this, 'render_synonyms_field'),
            'trb-product-search',
            'trb_search_general'
        );
    }

    /**
     * Sanitize synonyms input.
     *
     * @param string $input Raw input.
     * @return string Sanitized input.
     */
    public function sanitize_synonyms($input)
    {
        return sanitize_textarea_field($input);
    }

    /**
     * Render synonyms textarea.
     */
    public function render_synonyms_field()
    {
        $synonyms = get_option('trb_search_synonyms', '');
        ?>
        <textarea name="trb_search_synonyms" rows="10" cols="50"
            class="large-text code"><?php echo esc_textarea($synonyms); ?></textarea>
        <p class="description">
            <?php esc_html_e('Enter each group of synonyms on a new line, separated by commas. Example: car, vehicle, auto', 'trb-product-search'); ?>
        </p>
        <?php
    }

    /**
     * Render settings page.
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>
                <?php echo esc_html(get_admin_page_title()); ?>
            </h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('trb_product_search_options');
                do_settings_sections('trb-product-search');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
