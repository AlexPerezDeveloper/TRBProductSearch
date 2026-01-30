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
        nocache_headers();
        error_log("TRB Search: AJAX handle_search called for term: " . (isset($_GET['term']) ? $_GET['term'] : 'EMPTY'));
        if (!check_ajax_referer('trb_search_nonce', 'security', false)) {
            wp_send_json_error(array('message' => __('Session expired. Please refresh.', 'trb-product-search')));
            return;
        }

        $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';

        // Validate search term
        $validation = $this->validate_search_term($term);
        if (is_wp_error($validation)) {
            wp_send_json_error(array('message' => $validation->get_error_message()));
            return;
        }

        if (!class_exists('\TRB_Product_Search\Search_Query')) {
            wp_send_json_error(array('message' => __('Search Query class missing', 'trb-product-search')));
            return;
        }

        $cache = Cache_Manager::get_instance();
        $orderby = get_option('trb_search_orderby', 'relevance');
        // Create a unique key for the final HTML output including ordering
        $cache_key = 'html_result_' . md5($term . $orderby);
        $cached_html = $cache->get($cache_key);

        /*
        if (false !== $cached_html) {
            $cache->debug("Ajax HTML Hit for term: $term");
            wp_send_json_success(array('html' => $cached_html));
            return;
        }
        */

        $cache->debug("Ajax HTML Miss for term: $term");

        $query_handler = new Search_Query();
        $query = $query_handler->search($term);

        // Check if correction was applied
        $is_correction = $query_handler->has_correction();
        $correction_info = $query_handler->get_correction_info();

        // Log search analytics
        $analytics = Search_Analytics::get_instance();
        $analytics->log_search($term, $query->post_count, $query->have_posts());

        if (!$query->have_posts()) {
            wp_send_json_error(array('message' => __('No products found', 'trb-product-search')));
        }

        ob_start();
        echo '<ul class="trb-search-dropdown-list">';

        if ($is_correction) {
            echo '<li class="trb-search-suggestion">';
            printf(
                esc_html__('No results for "%s". Showing results for "%s"', 'trb-product-search'),
                esc_html($correction_info['original']),
                '<strong>' . esc_html($correction_info['corrected']) . '</strong>'
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

        // Cache the valid HTML result
        /*
        if (isset($html) && !empty($html)) {
            // Cache for 1 hour
            $cache->set($cache_key, $html, 3600);
        }
        */

        wp_reset_postdata();

        wp_send_json_success(array('html' => $html));
    }

    /**
     * Validate search term and return appropriate error if invalid.
     *
     * Handles multi-word search validation including:
     * - Empty search terms
     * - Terms with only stop words (e.g., "el la de")
     * - Terms with only short words (e.g., "a b c")
     * - Single word validation (minimum 2 chars after tokenization)
     *
     * @param string $term Raw search term from user input.
     * @return true|\WP_Error True if valid, WP_Error with message if invalid.
     */
    private function validate_search_term($term)
    {
        // Check for empty term
        if (empty(trim($term))) {
            return new \WP_Error(
                'empty_term',
                __('Please enter a search term.', 'trb-product-search')
            );
        }

        // Use Search_Query to parse and validate tokens
        $query_handler = new Search_Query();
        $tokens = $query_handler->parse_search_terms($term);

        // Check if all words were filtered out (stop words or too short)
        if (empty($tokens)) {
            // Determine why tokens are empty for better error message
            $raw_tokens = preg_split('/\s+/', trim(mb_strtolower($term)), -1, PREG_SPLIT_NO_EMPTY);
            $stop_words = array('el', 'la', 'de', 'en', 'y', 'a', 'los', 'las', 'un', 'una', 'del', 'al', 'con', 'por', 'para');

            $all_stop_words = true;
            $all_short = true;

            foreach ($raw_tokens as $token) {
                if (!in_array($token, $stop_words, true)) {
                    $all_stop_words = false;
                }
                if (mb_strlen($token) >= 2) {
                    $all_short = false;
                }
            }

            // Check short words first (more fundamental issue)
            // Words that are both stop words AND short should report as short words
            if ($all_short && count($raw_tokens) > 0) {
                return new \WP_Error(
                    'only_short_words',
                    __('Search terms must be at least 2 characters long.', 'trb-product-search')
                );
            }

            if ($all_stop_words && count($raw_tokens) > 0) {
                return new \WP_Error(
                    'only_stop_words',
                    __('Please enter more specific search terms (common words like "el", "la", "de" are ignored).', 'trb-product-search')
                );
            }

            return new \WP_Error(
                'invalid_term',
                __('Please enter a valid search term.', 'trb-product-search')
            );
        }

        return true;
    }
}
