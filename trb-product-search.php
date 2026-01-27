<?php
/**
 * Plugin Name: TRB Product Search
 * Plugin URI: https://example.com/
 * Description: A basic product search plugin for WooCommerce.
 * Version: 1.0.0
 * Author: Antigravity
 * Text Domain: trb-product-search
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define constants.
define('TRB_PRODUCT_SEARCH_VERSION', '1.0.0');
define('TRB_PRODUCT_SEARCH_PATH', plugin_dir_path(__FILE__));
define('TRB_PRODUCT_SEARCH_URL', plugin_dir_url(__FILE__));

// Require the initialization class.
require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-plugin-init.php';

// Initialize the plugin.
function trb_product_search_init()
{
    \TRB_Product_Search\Plugin_Init::get_instance()->init();
}
add_action('plugins_loaded', 'trb_product_search_init', 9); // Priority 9 to be ready for other plugins at 10 if needed, though check happens inside init.