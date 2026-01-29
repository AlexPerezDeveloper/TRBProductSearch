<?php
/**
 * PHPUnit Bootstrap for Real WordPress Integration Tests
 *
 * This bootstrap uses the WordPress test suite to run integration tests
 * against a real WordPress installation.
 *
 * Prerequisites:
 * - WordPress test suite installed via: bash bin/install-wp-tests.sh
 * - Test database configured
 *
 * @package TRB_Product_Search\Tests
 */

// Path to WordPress test suite
$_tests_dir = getenv('WP_TESTS_DIR');

if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '\\/') . '/wordpress-tests-lib';
}

// Check if WordPress test suite exists
if (!file_exists($_tests_dir . '/includes/functions.php')) {
    echo "\n";
    echo "ERROR: WordPress test suite not found!\n";
    echo "\n";
    echo "Please install it first:\n";
    echo "  cd /path/to/wordpress-develop\n";
    echo "  bash bin/install-wp-tests.sh\n";
    echo "\n";
    echo "Or set the WP_TESTS_DIR environment variable.\n";
    echo "\n";
    exit(1);
}

// Load WordPress test functions
require_once $_tests_dir . '/includes/functions.php';

// Load test framework
require $_tests_dir . '/includes/mock-mailer.php';
require $_tests_dir . '/includes/testcase.php';

// Load plugin
require_once dirname(__DIR__) . '/../trb-product-search.php';

// Activate plugin for tests
add_action('init', function() {
    // Load all plugin classes
    if (!defined('TRB_PRODUCT_SEARCH_PATH')) {
        define('TRB_PRODUCT_SEARCH_PATH', dirname(__DIR__) . '/../');
    }
    require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-plugin-init.php';
    require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-search-form.php';
    require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-search-query.php';
    require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-sku-search.php';
    require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-attributes-search.php';
    require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-typo-corrector.php';
    require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-ajax-handler.php';
    require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-settings.php';
    require_once TRB_PRODUCT_SEARCH_PATH . 'includes/class-search-results.php';
});

echo "WordPress integration tests bootstrapped successfully.\n";
