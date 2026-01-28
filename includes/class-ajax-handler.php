<?php
namespace TRB_Product_Search;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Ajax_Handler
 *
 * Handles AJAX search requests.
 */
class Ajax_Handler
{

    /**
     * Instance of the class.
     *
     * @var Ajax_Handler
     */
    private static $instance = null;

    /**
     * Get the instance of the class.
     *
     * @return Ajax_Handler
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
        add_action('wp_ajax_trb_search', array($this, 'handle_search'));
        add_action('wp_ajax_nopriv_trb_search', array($this, 'handle_search'));
    }

    /**
     * Handle the AJAX search request.
     */
    public function handle_search()
    {
        check_ajax_referer('trb_search_nonce', 'security');

        $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';

        if (empty($term) || strlen($term) < 3) {
            wp_send_json_error(array('message' => __('Term too short', 'trb-product-search')));
        }

        if (!class_exists('\TRB_Product_Search\Search_Query')) {
            wp_send_json_error(array('message' => __('Search Query class missing', 'trb-product-search')));
        }

        $query_handler = new Search_Query();
        $query = $query_handler->search($term);

        if (!$query->have_posts()) {
            // Check for typos
            $corrector = Typo_Corrector::get_instance();
            $suggestion = $corrector->correct($term);

            if ($suggestion) {
                // Perform search with suggestion
                $query = $query_handler->search($suggestion);
                $is_correction = true;
            } else {
                wp_send_json_error(array('message' => __('No products found', 'trb-product-search')));
            }
        } else {
            $is_correction = false;
        }

        // Double check if the suggestion found anything
        if ($is_correction && !$query->have_posts()) {
            wp_send_json_error(array('message' => __('No products found', 'trb-product-search')));
        }

        ob_start();
        echo '<ul class="trb-search-dropdown-list">';

        if ($is_correction) {
            echo '<li class="trb-search-suggestion">';
            printf(
                esc_html__('No results for "%s". Showing results for "%s"', 'trb-product-search'),
                esc_html($term),
                '<strong>' . esc_html($suggestion) . '</strong>'
            );
            echo '</li>';
        }

        while ($query->have_posts()) {
            $query->the_post();
            global $product;
            if (!$product) {
                $product = wc_get_product(get_the_ID());
            }

            // Simple list item specific for dropdown
            ?>
            <li class="trb-dropdown-item">
                <a href="<?php echo esc_url(get_permalink()); ?>">
                    <?php echo $product->get_image('thumbnail'); ?>
                    <span>
                        <?php echo esc_html($product->get_name()); ?>
                    </span>
                    <span class="price">
                        <?php echo $product->get_price_html(); ?>
                    </span>
                </a>
            </li>
            <?php
        }
        echo '</ul>';
        $html = ob_get_clean();

        wp_reset_postdata();

        wp_send_json_success(array('html' => $html));
    }
}
