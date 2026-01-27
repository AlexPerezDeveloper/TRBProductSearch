<?php
/**
 * Template part for displaying search results.
 *
 * @package TRB_Product_Search
 */

if (!defined('ABSPATH')) {
    exit;
}

global $product;

// Ensure $product is valid
if (!$product || !is_a($product, 'WC_Product')) {
    return;
}
?>
<div class="trb-product-result">
    <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>" class="trb-product-link">
        <div class="trb-product-image">
            <?php echo $product->get_image('thumbnail'); ?>
        </div>
        <div class="trb-product-info">
            <h3 class="trb-product-title"><?php echo esc_html($product->get_name()); ?></h3>
            <span class="price"><?php echo $product->get_price_html(); ?></span>
            <span class="button"><?php esc_html_e('View Product', 'trb-product-search'); ?></span>
        </div>
    </a>
</div>