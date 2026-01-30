<?php
/**
 * PHPUnit bootstrap for TRB Product Search plugin.
 *
 * @package TRB_Product_Search\Tests
 */

// Define test environment constants
define('TRB_PRODUCT_SEARCH_TESTS', true);

// Set error reporting for tests
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

// Define ABSPATH if not defined
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

// Define plugin path constants
if (!defined('TRB_PRODUCT_SEARCH_PATH')) {
    define('TRB_PRODUCT_SEARCH_PATH', dirname(__DIR__) . '/');
}

if (!defined('TRB_PRODUCT_SEARCH_URL')) {
    define('TRB_PRODUCT_SEARCH_URL', 'https://example.com/wp-content/plugins/wp-wc-searcher/');
}

if (!defined('TRB_PRODUCT_SEARCH_VERSION')) {
    define('TRB_PRODUCT_SEARCH_VERSION', '1.0.0');
}

// Load Composer autoloader if available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Mock WordPress core functions for testing
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo $text;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html_e')) {
    function esc_html_e($text, $domain = 'default') {
        echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('esc_textarea')) {
    function esc_textarea($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return strip_tags((string) $str);
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return strip_tags((string) $str);
    }
}

if (!function_exists('esc_sql')) {
    function esc_sql($sql) {
        return addslashes($sql);
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = array()) {
        if (is_object($args)) {
            $args = get_object_vars($args);
        }
        if (is_array($args)) {
            return array_merge($defaults, $args);
        }
        return $defaults;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('remove_filter')) {
    function remove_filter($hook, $callback, $priority = 10) {
        return true;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value, ...$args) {
        return $value;
    }
}

if (!function_exists('do_action')) {
    function do_action($hook, ...$args) {
        return;
    }
}

if (!function_exists('get_option')) {
    global $_test_options;
    if (!isset($_test_options)) {
        $_test_options = array();
    }
    function get_option($option, $default = false) {
        global $_test_options;
        return isset($_test_options[$option]) ? $_test_options[$option] : $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        global $_test_options;
        $_test_options[$option] = $value;
        return true;
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {
        return true;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {
        return true;
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $data) {
        return true;
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        return 'test_nonce_123456';
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        return 'http://example.com/wp-admin/' . ltrim($path, '/');
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true;
    }
}

if (!function_exists('get_admin_page_title')) {
    function get_admin_page_title() {
        return 'TRB Product Search';
    }
}

if (!function_exists('add_options_page')) {
    function add_options_page($page_title, $menu_title, $capability, $menu_slug, $callback) {
        return true;
    }
}

if (!function_exists('register_setting')) {
    function register_setting($option_group, $option_name, $args = array()) {
        return true;
    }
}

if (!function_exists('add_settings_section')) {
    function add_settings_section($id, $title, $callback, $page) {
        return true;
    }
}

if (!function_exists('add_settings_field')) {
    function add_settings_field($id, $title, $callback, $page, $section = 'default', $args = array()) {
        return true;
    }
}

if (!function_exists('settings_fields')) {
    function settings_fields($option_group) {
        echo '';
    }
}

if (!function_exists('do_settings_sections')) {
    function do_settings_sections($page) {
        echo '';
    }
}

if (!function_exists('submit_button')) {
    function submit_button($text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = '') {
        echo '<button type="submit">' . ($text ?: 'Save Changes') . '</button>';
    }
}

if (!function_exists('checked')) {
    function checked($checked, $current = true, $echo = true) {
        $result = $checked === $current ? ' checked="checked"' : '';
        if ($echo) {
            echo $result;
        }
        return $result;
    }
}

if (!function_exists('selected')) {
    function selected($selected, $current = true, $echo = true) {
        $result = $selected === $current ? ' selected="selected"' : '';
        if ($echo) {
            echo $result;
        }
        return $result;
    }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback) {
        return true;
    }
}

if (!function_exists('shortcode_atts')) {
    function shortcode_atts($pairs, $atts, $shortcode = '') {
        $atts = (array) $atts;
        $out = array();
        foreach ($pairs as $name => $default) {
            if (array_key_exists($name, $atts)) {
                $out[$name] = $atts[$name];
            } else {
                $out[$name] = $default;
            }
        }
        return $out;
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null, $status_code = null) {
        echo json_encode(array('success' => true, 'data' => $data));
        exit;
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null, $status_code = null) {
        echo json_encode(array('success' => false, 'data' => $data));
        exit;
    }
}

if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer($action = -1, $query_arg = '_wpnonce', $die = true) {
        return true;
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        private $code;
        private $message;
        private $data;

        public function __construct($code = '', $message = '', $data = '') {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }

        public function get_error_code() {
            return $this->code;
        }

        public function get_error_message() {
            return $this->message;
        }

        public function get_error_data() {
            return $this->data;
        }
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return $thing instanceof WP_Error;
    }
}

if (!function_exists('remove_accents')) {
    function remove_accents($str) {
        if (!preg_match('/[\x80-\xff]/', $str)) {
            return $str;
        }
        // Simple mapping for common accents
        $chars = array(
            'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u',
            'Á'=>'A', 'É'=>'E', 'Í'=>'I', 'Ó'=>'O', 'Ú'=>'U',
            'ñ'=>'n', 'Ñ'=>'N',
            'à'=>'a', 'è'=>'e', 'ì'=>'i', 'ò'=>'o', 'ù'=>'u',
            'ä'=>'a', 'ë'=>'e', 'ï'=>'i', 'ö'=>'o', 'ü'=>'u',
        );
        return strtr($str, $chars);
    }
}

// Mock cache-related functions
if (!function_exists('wp_using_ext_object_cache')) {
    function wp_using_ext_object_cache() {
        return false;
    }
}

if (!function_exists('get_transient')) {
    function get_transient($transient) {
        global $_test_transients;
        if (!isset($_test_transients)) {
            $_test_transients = array();
        }
        return isset($_test_transients[$transient]) ? $_test_transients[$transient] : false;
    }
}

if (!function_exists('set_transient')) {
    function set_transient($transient, $value, $expiration = 0) {
        global $_test_transients;
        if (!isset($_test_transients)) {
            $_test_transients = array();
        }
        $_test_transients[$transient] = $value;
        return true;
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient($transient) {
        global $_test_transients;
        if (isset($_test_transients[$transient])) {
            unset($_test_transients[$transient]);
            return true;
        }
        return false;
    }
}

// Mock WooCommerce
if (!class_exists('WooCommerce')) {
    class WooCommerce {
        public function version() {
            return '8.0.0';
        }
    }
}

if (!function_exists('WC')) {
    function WC() {
        return new WooCommerce();
    }
}

if (!class_exists('WC_Product')) {
    class WC_Product {
        private $id;
        private $name;
        private $price;
        private $description;
        private $sku;
        private $stock_status;

        public function __construct($product = 0) {
            $this->id = $product ?: 1;
            $this->name = 'Test Product';
            $this->price = '19.99';
            $this->description = 'Test description';
            $this->sku = 'TEST-001';
            $this->stock_status = 'instock';
        }

        public function get_id() {
            return $this->id;
        }

        public function get_name() {
            return $this->name;
        }

        public function set_name($name) {
            $this->name = $name;
        }

        public function get_price() {
            return $this->price;
        }

        public function set_price($price) {
            $this->price = $price;
        }

        public function get_price_html() {
            return '<span class="amount">$' . $this->price . '</span>';
        }

        public function get_description() {
            return $this->description;
        }

        public function set_description($description) {
            $this->description = $description;
        }

        public function get_sku() {
            return $this->sku;
        }

        public function set_sku($sku) {
            $this->sku = $sku;
        }

        public function get_stock_status() {
            return $this->stock_status;
        }

        public function set_stock_status($status) {
            $this->stock_status = $status;
        }

        public function get_image($size = 'thumbnail') {
            return '<img src="http://example.com/product.jpg" alt="Product Image">';
        }

        public function save() {
            return $this->id;
        }
    }
}

if (!class_exists('WC_Product_Simple')) {
    class WC_Product_Simple extends WC_Product {
    }
}

if (!function_exists('wc_get_product')) {
    function wc_get_product($product) {
        if (is_numeric($product)) {
            return new WC_Product($product);
        }
        return $product;
    }
}

// Mock WordPress Query
if (!class_exists('WP_Query')) {
    class WP_Query {
        private $posts = array();
        private $post_count = 0;
        private $current_post = -1;
        private $in_the_loop = false;
        public $query_vars = array();

        public function __construct($args = array()) {
            $this->parse_query($args);
        }

        public function parse_query($args) {
            // Mock query parsing
            $this->post_count = rand(0, 10);
        }

        public function have_posts() {
            if ($this->current_post + 1 < $this->post_count) {
                $this->current_post++;
                $this->in_the_loop = true;
                return true;
            }
            $this->in_the_loop = false;
            $this->current_post = -1;
            return false;
        }

        public function the_post() {
            global $post;
            $post = new stdClass();
            $post->ID = $this->current_post + 1;
            $post->post_title = 'Test Product ' . $this->current_post;
            $post->post_content = 'Test content';
            $post->post_type = 'product';
        }

        public function get_posts() {
            return $this->posts;
        }
    }
}

// Load test helpers
require_once __DIR__ . '/helpers.php';

// Mock $wpdb global
if (!isset($GLOBALS['wpdb'])) {
    $GLOBALS['wpdb'] = new class {
        public $posts = 'wp_posts';
        public $postmeta = 'wp_postmeta';
        public $terms = 'wp_terms';
        public $term_taxonomy = 'wp_term_taxonomy';
        public $term_relationships = 'wp_term_relationships';
        public $prefix = 'wp_';
        
        public $mock_results = array();

        public function prepare($query, ...$args) {
            // Handle array argument (when args contain an array)
            if (count($args) === 1 && is_array($args[0])) {
                $args = $args[0];
            }
            foreach ($args as $arg) {
                if (is_numeric($arg)) {
                    $query = preg_replace('/(%d|%f)/', $arg, $query, 1);
                } else {
                    $arg = addslashes((string) $arg);
                    $query = preg_replace('/(%s)/', "'{$arg}'", $query, 1);
                }
            }
            return $query;
        }

        public function get_col($query) {
            if (isset($this->mock_results['get_col'])) {
                $mock = $this->mock_results['get_col'];
                if (is_array($mock) && !empty($mock) && isset($mock[0]) && is_array($mock[0])) {
                    return array_shift($this->mock_results['get_col']);
                }
                return $mock;
            }
            return array();
        }

        public function get_var($query) {
            if (isset($this->mock_results['get_var'])) {
                 return $this->mock_results['get_var'];
            }
            return null;
        }

        public function get_results($query) {
             if (isset($this->mock_results['get_results'])) {
                $mock = $this->mock_results['get_results'];
                // Check if it's a sequence of results (array of arrays)
                if (is_array($mock) && !empty($mock) && isset($mock[0]) && is_array($mock[0]) && isset($mock[0][0]) && is_object($mock[0][0])) {
                     return array_shift($this->mock_results['get_results']);
                }
                return $mock;
            }
            return array();
        }

        public function esc_like($text) {
            return addcslashes($text, '_%\\');
        }
    };
}

// Mock get_the_ID function
if (!function_exists('get_the_ID')) {
    function get_the_ID() {
        return 1;
    }
}

// Mock locate_template function
if (!function_exists('locate_template')) {
    function locate_template($template_names, $load = false, $require_once = true) {
        return '';
    }
}

// Mock load_template function
if (!function_exists('load_template')) {
    function load_template($_template_file, $require_once = true) {
        return;
    }
}

// Mock wp_reset_postdata function
if (!function_exists('wp_reset_postdata')) {
    function wp_reset_postdata() {
        return;
    }
}

// Mock get_permalink function
if (!function_exists('get_permalink')) {
    function get_permalink($post = 0) {
        return 'http://example.com/product/';
    }
}

// Load plugin files for testing
if (file_exists(__DIR__ . '/../includes/class-plugin-init.php')) {
    require_once __DIR__ . '/../includes/class-plugin-init.php';
    require_once __DIR__ . '/../includes/class-search-form.php';
    require_once __DIR__ . '/../includes/class-search-query.php';
    require_once __DIR__ . '/../includes/class-search-results.php';
    require_once __DIR__ . '/../includes/class-ajax-handler.php';
    require_once __DIR__ . '/../includes/class-settings.php';
    require_once __DIR__ . '/../includes/class-typo-corrector.php';
    require_once __DIR__ . '/../includes/class-sku-search.php';
    require_once __DIR__ . '/../includes/class-attributes-search.php';
    require_once __DIR__ . '/../includes/class-cache-manager.php';
    require_once __DIR__ . '/../includes/class-search-analytics.php';
}

echo "TRB Product Search integration tests bootstrapped successfully." . PHP_EOL;
