<?php
namespace TRB_Product_Search;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Search_Results
 *
 * Handles the display of search results.
 */
class Search_Results
{

    /**
     * Render the results.
     *
     * @param \WP_Query $query The search query object.
     */
    public function render($query)
    {
        if (!$query->have_posts()) {
            echo '<p class="trb_product_search_no_results">' . esc_html__('No products found.', 'trb-product-search') . '</p>';
            return;
        }

        echo '<div class="trb-product-search-results">';

        while ($query->have_posts()) {
            $query->the_post();
            global $product;

            if (!$product) {
                // Ensure global product is set if standard loop doesn't adhere
                $product = wc_get_product(get_the_ID());
            }

            // Load the template
            $this->load_template();
        }

        echo '</div>'; // .trb-product-search-results

        // Pagination could go here
        wp_reset_postdata();
    }

    /**
     * Load the results template.
     * Allows overriding via theme.
     */
    private function load_template()
    {
        $template_name = 'results.php';

        // Check theme folder first: theme/trb-product-search/results.php
        $theme_template = locate_template(array('trb-product-search/' . $template_name));

        if ($theme_template) {
            include $theme_template;
        } else {
            // Load default plugin template
            include TRB_PRODUCT_SEARCH_PATH . 'templates/' . $template_name;
        }
    }
}
