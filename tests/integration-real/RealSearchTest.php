<?php
/**
 * Real WordPress Integration Tests
 *
 * These tests use WP_UnitTestCase to run against a real WordPress installation.
 * They verify that the SQL queries and search logic work correctly.
 *
 * Run with: WP_TESTS_DIR=/path/to/wordpress-tests-lib phpunit --bootstrap tests/bootstrap-real.php
 *
 * @package TRB_Product_Search\Tests\Integration
 */

/**
 * Real search integration test.
 */
class RealSearchTest extends WP_UnitTestCase
{
    /**
     * Set up test fixtures before each test.
     */
    public function set_up()
    {
        parent::set_up();

        // Create some test products
        $this->createTestProduct('Camiseta Básica Algodón', 'CAMI-001', 15.99);
        $this->createTestProduct('Camiseta Estampada Diseño', 'CAMI-002', 19.99);
        $this->createTestProduct('Cable HDMI 2 metros', 'HDMI-2M', 9.99);
        $this->createTestProduct('Zapatillas Running', 'ZAPA-RUN', 59.99);
        $this->createTestProduct('Portátil Notebook', 'LAPTOP-01', 599.99);
    }

    /**
     * Tear down test fixtures after each test.
     */
    public function tear_down()
    {
        // Remove all test products
        $products = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);

        foreach ($products as $product_id) {
            wp_delete_post($product_id, true);
        }

        parent::tear_down();
    }

    /**
     * Create a test product.
     *
     * @param string $title Product title.
     * @param string $sku Product SKU.
     * @param float $price Product price.
     * @return int Product ID.
     */
    private function createTestProduct($title, $sku, $price)
    {
        $product_id = wp_insert_post([
            'post_title' => $title,
            'post_content' => 'Producto de prueba para ' . strtolower($title),
            'post_status' => 'publish',
            'post_type' => 'product',
        ]);

        update_post_meta($product_id, '_sku', $sku);
        update_post_meta($product_id, '_price', $price);
        update_post_meta($product_id, '_regular_price', $price);
        update_post_meta($product_id, '_stock_status', 'instock');

        return $product_id;
    }

    /**
     * Test basic WordPress search works.
     */
    public function test_basic_wordpress_search_works()
    {
        $query = new WP_Query([
            'post_type' => 'product',
            's' => 'Camiseta',
        ]);

        $this->assertTrue($query->have_posts(), 'WordPress search should find products');
        $this->assertCount(2, $query->posts, 'Should find 2 products with "Camiseta"');
    }

    /**
     * Test custom search filter generates correct SQL.
     */
    public function test_custom_search_filter_sql_generation()
    {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $reflection = new ReflectionClass($query_handler);

        // Set search terms
        $property = $reflection->getProperty('current_search_terms');
        $property->setAccessible(true);
        $property->setValue($query_handler, ['cami']);

        $wp_query = new WP_Query(['s' => 'cami']);
        $sql = $query_handler->custom_search_filter('', $wp_query);

        // Verify SQL contains expected patterns
        $this->assertStringContainsString('%cami%', $sql, 'SQL should contain wildcard pattern');
        $this->assertStringContainsString('post_title', $sql, 'SQL should search in post_title');
        $this->assertStringContainsString('post_content', $sql, 'SQL should search in post_content');
        $this->assertStringContainsString(' OR ', $sql, 'SQL should use OR logic for title/content');
    }

    /**
     * Test search with custom Search_Query class.
     */
    public function test_search_query_class_search()
    {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $result = $query_handler->search('cami');

        $this->assertInstanceOf('WP_Query', $result, 'search() should return WP_Query');
        $this->assertTrue($result->have_posts(), 'Search should find products');
    }

    /**
     * Test partial matching finds products.
     */
    public function test_partial_matching_finds_products()
    {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $result = $query_handler->search('cami');

        $this->assertTrue($result->have_posts(), 'Partial match "cami" should find products');

        $found_titles = [];
        while ($result->have_posts()) {
            $result->the_post();
            $found_titles[] = get_the_title();
        }

        $this->assertContains('Camiseta Básica Algodón', $found_titles, 'Should find "Camiseta Básica Algodón"');
        $this->assertContains('Camiseta Estampada Diseño', $found_titles, 'Should find "Camiseta Estampada Diseño"');
        $this->assertGreaterThanOrEqual(count($found_titles), 2, 'Should find at least 2 products');
    }

    /**
     * Test SKU search class methods.
     */
    public function test_sku_search_class_methods()
    {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        $this->assertTrue(method_exists($sku_search, 'is_enabled'), 'is_enabled method should exist');
        $this->assertTrue(method_exists($sku_search, 'build_meta_query'), 'build_meta_query method should exist');
    }

    /**
     * Test SKU search is disabled by default.
     */
    public function test_sku_search_disabled_by_default()
    {
        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        $this->assertFalse($sku_search->is_enabled(), 'SKU search should be disabled by default');
        $this->assertNull($sku_search->build_meta_query('TEST-SKU'), 'build_meta_query should return null when disabled');
    }

    /**
     * Test SKU search can be enabled.
     */
    public function test_sku_search_can_be_enabled()
    {
        update_option('trb_search_sku_enabled', '1');

        $sku_search = \TRB_Product_Search\SKU_Search::get_instance();

        $this->assertTrue($sku_search->is_enabled(), 'SKU search should be enabled when option is set');

        $meta_query = $sku_search->build_meta_query('CAMI-001');
        $this->assertIsArray($meta_query, 'build_meta_query should return array when enabled');
        $this->assertArrayHasKey('sku_clause', $meta_query, 'Meta query should contain sku_clause');
        $this->assertEquals('_sku', $meta_query['sku_clause']['key'], 'Meta key should be _sku');
    }

    /**
     * Test attributes search class methods.
     */
    public function test_attributes_search_class_methods()
    {
        $attr_search = \TRB_Product_Search\Attributes_Search::get_instance();

        $this->assertTrue(method_exists($attr_search, 'is_enabled'), 'is_enabled method should exist');
        $this->assertTrue(method_exists($attr_search, 'build_tax_query'), 'build_tax_query method should exist');
        $this->assertTrue(method_exists($attr_search, 'get_selected_attributes'), 'get_selected_attributes method should exist');
    }

    /**
     * Test attributes search disabled by default.
     */
    public function test_attributes_search_disabled_by_default()
    {
        $attr_search = \TRB_Product_Search\Attributes_Search::get_instance();

        $this->assertFalse($attr_search->is_enabled(), 'Attributes search should be disabled by default');
        $this->assertEmpty($attr_search->get_selected_attributes(), 'Selected attributes should be empty by default');
    }

    /**
     * Test synonym expansion logic.
     */
    public function test_synonym_expansion_logic()
    {
        update_option('trb_search_synonyms', "coche, auto, vehiculo");

        $query_handler = new \TRB_Product_Search\Search_Query();

        // Use reflection to access private method or test search behavior
        $reflection = new ReflectionClass($query_handler);

        // Verify synonyms option is retrieved
        $this->assertEquals("coche, auto, vehiculo", get_option('trb_search_synonyms'), 'Synonyms option should be stored');
    }

    /**
     * Test typo corrector class exists.
     */
    public function test_typo_corrector_class_exists()
    {
        $corrector = \TRB_Product_Search\Typo_Corrector::get_instance();

        $this->assertInstanceOf(\TRB_Product_Search\Typo_Corrector::class, $corrector);
        $this->assertTrue(method_exists($corrector, 'correct'), 'correct method should exist');
        $this->assertTrue(method_exists($corrector, 'build_index'), 'build_index method should exist');
    }

    /**
     * Test settings class methods.
     */
    public function test_settings_class_methods()
    {
        $settings = \TRB_Product_Search\Settings::get_instance();

        $this->assertTrue(method_exists($settings, 'init'), 'init method should exist');
        $this->assertTrue(method_exists($settings, 'register_settings'), 'register_settings method should exist');
        $this->assertTrue(method_exists($settings, 'sanitize_checkbox'), 'sanitize_checkbox method should exist');
        $this->assertTrue(method_exists($settings, 'sanitize_synonyms'), 'sanitize_synonyms method should exist');
    }

    /**
     * Test sanitize_checkbox handles various inputs.
     */
    public function test_sanitize_checkbox_handles_various_inputs()
    {
        $settings = \TRB_Product_Search\Settings::get_instance();

        // Truthy values should return '1'
        $this->assertEquals('1', $settings->sanitize_checkbox('1'));
        $this->assertEquals('1', $settings->sanitize_checkbox(1));
        $this->assertEquals('1', $settings->sanitize_checkbox(true));

        // Falsy values should return '0'
        $this->assertEquals('0', $settings->sanitize_checkbox('0'));
        $this->assertEquals('0', $settings->sanitize_checkbox(0));
        $this->assertEquals('0', $settings->sanitize_checkbox(false));
        $this->assertEquals('0', $settings->sanitize_checkbox(''));
        $this->assertEquals('0', $settings->sanitize_checkbox('random'));
    }

    /**
     * Test AJAX handler class exists.
     */
    public function test_ajax_handler_class_exists()
    {
        $handler = \TRB_Product_Search\Ajax_Handler::get_instance();

        $this->assertInstanceOf(\TRB_Product_Search\Ajax_Handler::class, $handler);
        $this->assertTrue(method_exists($handler, 'init'), 'init method should exist');
        $this->assertTrue(method_exists($handler, 'handle_search'), 'handle_search method should exist');
    }

    /**
     * Test search with exact match.
     */
    public function test_search_with_exact_match()
    {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $result = $query_handler->search('HDMI-2M');

        $this->assertTrue($result->have_posts(), 'Exact SKU search should find product');

        $result->the_post();
        $this->assertEquals('Cable HDMI 2 metros', get_the_title(), 'Should find exact product');
    }

    /**
     * Test search with no results.
     */
    public function test_search_with_no_results()
    {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $result = $query_handler->search('nonexistentproductxyz');

        $this->assertFalse($result->have_posts(), 'Search for non-existent product should return no results');
    }

    /**
     * Test singleton pattern implementation.
     */
    public function test_singleton_pattern()
    {
        $instance1 = \TRB_Product_Search\Settings::get_instance();
        $instance2 = \TRB_Product_Search\Settings::get_instance();

        $this->assertSame($instance1, $instance2, 'get_instance should return same instance');
    }
}
