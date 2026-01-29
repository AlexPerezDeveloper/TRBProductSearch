<?php
/**
 * Correction Notice Template
 *
 * Displays a notice when search term was corrected.
 *
 * @package TRB_Product_Search
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="trb-search-correction-notice" role="status" aria-live="polite">
    <p class="trb-correction-message">
        <?php
        printf(
            /* translators: %1$s: original search term, %2$s: corrected search term */
            esc_html__('No se encontraron resultados para %1$s. Mostrando resultados para %2$s', 'trb-product-search'),
            '<span class="trb-original-term">' . esc_html($original_term) . '</span>',
            '<strong class="trb-corrected-term">' . esc_html($corrected_term) . '</strong>'
        );
        ?>
    </p>
</div>
