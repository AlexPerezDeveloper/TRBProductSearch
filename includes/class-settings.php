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
        register_setting('trb_product_search_options', 'trb_search_sku_enabled', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => '0'));
        register_setting('trb_product_search_options', 'trb_search_attributes_enabled', array('sanitize_callback' => array($this, 'sanitize_checkbox'), 'default' => '0'));
        register_setting('trb_product_search_options', 'trb_search_selected_attributes', array('sanitize_callback' => array($this, 'sanitize_attributes'), 'default' => array()));

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

        add_settings_field(
            'trb_search_sku_enabled',
            __('Search by SKU', 'trb-product-search'),
            array($this, 'render_sku_checkbox'),
            'trb-product-search',
            'trb_search_general'
        );

        add_settings_field(
            'trb_search_attributes_enabled',
            __('Search by Attributes', 'trb-product-search'),
            array($this, 'render_attributes_field'),
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
     * Sanitize checkbox input.
     *
     * @param string $input Raw input.
     * @return string Sanitized input ('1' or '0').
     */
    public function sanitize_checkbox($input)
    {
        return '1' === $input || 1 === $input || true === $input || 'true' === $input ? '1' : '0';
    }

    /**
     * Sanitize attributes array.
     *
     * @param array $input Raw input.
     * @return array Sanitized array of attribute names.
     */
    public function sanitize_attributes($input)
    {
        if (!is_array($input)) {
            return array();
        }

        $sanitized = array();
        foreach ($input as $attribute_name) {
            $sanitized[] = sanitize_text_field($attribute_name);
        }

        return array_filter($sanitized);
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
     * Render SKU search checkbox.
     */
    public function render_sku_checkbox()
    {
        $enabled = get_option('trb_search_sku_enabled', '0');
        ?>
        <label>
            <input type="checkbox" name="trb_search_sku_enabled" value="1" <?php checked('1', $enabled); ?>>
            <?php esc_html_e('Enable product SKU search', 'trb-product-search'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('When enabled, the search will also look for matches in product SKUs.', 'trb-product-search'); ?>
        </p>
        <?php
    }

    /**
     * Render attributes checkboxes field.
     */
    public function render_attributes_field()
    {
        $attributes_enabled = get_option('trb_search_attributes_enabled', '0');
        $selected_attributes = get_option('trb_search_selected_attributes', array());
        $available_attributes = $this->get_available_attributes();

        if (empty($available_attributes)) {
            ?>
            <p>
                <?php esc_html_e('No product attributes found. Create attributes in WooCommerce first.', 'trb-product-search'); ?>
            </p>
            <?php
            return;
        }

        ?>
        <label>
            <input type="checkbox" id="trb_enable_attributes" name="trb_search_attributes_enabled" value="1" <?php checked('1', $attributes_enabled); ?>>
            <?php esc_html_e('Enable attribute search', 'trb-product-search'); ?>
        </label>

        <fieldset id="trb_attributes_fieldset" style="margin-top: 10px; <?php echo '1' === $attributes_enabled ? '' : 'display: none;'; ?>">
            <legend class="screen-reader-text"><?php esc_html_e('Select attributes to search', 'trb-product-search'); ?></legend>
            <?php foreach ($available_attributes as $attribute) : ?>
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox" name="trb_search_selected_attributes[]" value="<?php echo esc_attr($attribute->attribute_name); ?>" <?php checked(in_array($attribute->attribute_name, $selected_attributes), true); ?>>
                    <?php echo esc_html($attribute->attribute_label); ?> (<?php echo esc_html($attribute->attribute_name); ?>)
                </label>
            <?php endforeach; ?>
        </fieldset>

        <p class="description">
            <?php esc_html_e('Select which product attributes should be included in the search.', 'trb-product-search'); ?>
        </p>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#trb_enable_attributes').on('change', function() {
                    $('#trb_attributes_fieldset').toggle($(this).is(':checked'));
                });
            });
        </script>
        <?php
    }

    /**
     * Get available product attributes from WooCommerce.
     *
     * @return array List of attribute taxonomies.
     */
    private function get_available_attributes()
    {
        if (!class_exists('WooCommerce')) {
            return array();
        }

        return wc_get_attribute_taxonomies();
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
