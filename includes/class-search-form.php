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

        // Check if a search was performed
        if (isset($_GET['trb_q']) && !empty($_GET['trb_q'])) {
            $search_term = sanitize_text_field($_GET['trb_q']);
            $this->handle_search($search_term);
        }

        return ob_get_clean();
    }

    /**
     * Render the search form HTML.
     *
     * @param array $atts Attributes.
     */
    private function render_form($atts)
    {
        $search_query = isset($_GET['trb_q']) ? sanitize_text_field($_GET['trb_q']) : ''; // Keep query param same for backward compatibility or change it? User didn't specify. Keeping wc_q is fine, but maybe trb_q? Let's stick to wc_q to minimize url changes if user shared links, or change it for consistency. I'll change it for consistency: trb_q
        ?>
        <div class="trb-product-search-container">
            <form role="search" method="get" class="trb-product-search-form" action="">
                <label for="trb_search_field"
                    class="screen-reader-text"><?php esc_html_e('Search for:', 'trb-product-search'); ?></label>
                <input type="search" id="trb_search_field" class="search-field"
                    placeholder="<?php echo esc_attr($atts['placeholder']); ?>" value="<?php echo esc_attr($search_query); ?>"
                    name="trb_q" />
                <button type="submit" class="search-submit"><?php esc_html_e('Search', 'trb-product-search'); ?></button>
            </form>
        </div>
        <?php
    }

    /**
     * Handle the search request and display results.
     *
     * @param string $term Search term.
     */
    private function handle_search($term)
    {
        // Ensure classes are loaded before using them (though Plugin_Init should handle this, specific order matters if not using autoloader)
        if (class_exists('\TRB_Product_Search\Search_Query') && class_exists('\TRB_Product_Search\Search_Results')) {
            $query_handler = new Search_Query();
            $results = $query_handler->search($term);

            // Log search analytics
            if (class_exists('\TRB_Product_Search\Search_Analytics')) {
                $analytics = Search_Analytics::get_instance();
                $analytics->log_search($term, $results->post_count, $results->have_posts());
            }

            // Get correction info if any
            $correction_info = $query_handler->get_correction_info();

            $results_renderer = new Search_Results();
            $results_renderer->render($results, $correction_info);
        }
    }
}
