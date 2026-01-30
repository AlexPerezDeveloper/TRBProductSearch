<?php
namespace TRB_Product_Search;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Search_Form
 *
 * Handles the search form shortcode and display.
 */
class Search_Form
{

    /**
     * Instance of the class.
     *
     * @var Search_Form
     */
    private static $instance = null;

    /**
     * Get the instance of the class.
     *
     * @return Search_Form
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
     * Register the shortcode.
     */
    public function register_shortcode()
    {
        add_shortcode('trb_product_search', array($this, 'render_shortcode'));
    }

    /**
     * Render the shortcode content.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML content.
     */
    public function render_shortcode($atts)
    {
        $atts = shortcode_atts(
            array(
                'placeholder' => __('Search products...', 'trb-product-search'),
            ),
            $atts,
            'trb_product_search'
        );

        ob_start();
        $this->render_form($atts);

        return ob_get_clean();
    }

    /**
     * Render the search form HTML.
     *
     * The form redirects to the native WooCommerce search page (/s=term&post_type=product).
     *
     * @param array $atts Attributes.
     */
    private function render_form($atts)
    {
        // Get search term from the native WordPress 's' parameter for consistency
        $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        ?>
        <div class="trb-product-search-container">
            <form role="search" method="get" class="trb-product-search-form" action="<?php echo esc_url(home_url('/')); ?>">
                <label for="trb_search_field"
                    class="screen-reader-text"><?php esc_html_e('Search for:', 'trb-product-search'); ?></label>
                <input type="search" id="trb_search_field" class="search-field"
                    placeholder="<?php echo esc_attr($atts['placeholder']); ?>" value="<?php echo esc_attr($search_query); ?>"
                    name="s" />
                <input type="hidden" name="post_type" value="product" />
                <button type="submit" class="search-submit"><?php esc_html_e('Search', 'trb-product-search'); ?></button>
            </form>
        </div>
        <?php
    }
}
