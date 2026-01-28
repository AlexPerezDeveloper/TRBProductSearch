<?php
/**
 * Integration tests for Plugin Initialization.
 *
 * @package TRB_Product_Search\Tests\Integration
 */

use PHPUnit\Framework\TestCase;

class PluginInitTest extends TestCase {

    /**
     * Test that plugin constants are defined.
     */
    public function test_plugin_constants_defined() {
        $this->assertTrue(defined('TRB_PRODUCT_SEARCH_VERSION'), 'TRB_PRODUCT_SEARCH_VERSION should be defined');
        $this->assertTrue(defined('TRB_PRODUCT_SEARCH_PATH'), 'TRB_PRODUCT_SEARCH_PATH should be defined');
        $this->assertTrue(defined('TRB_PRODUCT_SEARCH_URL'), 'TRB_PRODUCT_SEARCH_URL should be defined');
    }

    /**
     * Test plugin version constant.
     */
    public function test_plugin_version() {
        $this->assertEquals('1.0.0', TRB_PRODUCT_SEARCH_VERSION, 'Plugin version should be 1.0.0');
    }

    /**
     * Test that Plugin_Init class exists.
     */
    public function test_plugin_init_class_exists() {
        $this->assertTrue(class_exists('\TRB_Product_Search\Plugin_Init'), 'Plugin_Init class should exist');
    }

    /**
     * Test Plugin_Init singleton pattern.
     */
    public function test_plugin_init_singleton() {
        $instance1 = \TRB_Product_Search\Plugin_Init::get_instance();
        $instance2 = \TRB_Product_Search\Plugin_Init::get_instance();

        $this->assertSame($instance1, $instance2, 'get_instance should return the same instance');
    }

    /**
     * Test that init method exists and is callable.
     */
    public function test_init_method_exists() {
        $plugin_init = \TRB_Product_Search\Plugin_Init::get_instance();
        $this->assertTrue(method_exists($plugin_init, 'init'), 'init method should exist');
        $this->assertIsCallable(array($plugin_init, 'init'), 'init should be callable');
    }

    /**
     * Test that check_dependencies method exists.
     */
    public function test_check_dependencies_method_exists() {
        $plugin_init = \TRB_Product_Search\Plugin_Init::get_instance();
        $this->assertTrue(method_exists($plugin_init, 'check_dependencies'), 'check_dependencies method should exist');
    }

    /**
     * Test that WooCommerce dependency is properly checked.
     */
    public function test_woocommerce_dependency() {
        // Since WooCommerce is mocked, class should exist
        $this->assertTrue(class_exists('WooCommerce'), 'WooCommerce class should exist in test environment');
    }

    /**
     * Test plugin initialization workflow.
     */
    public function test_initialization_workflow() {
        $plugin_init = \TRB_Product_Search\Plugin_Init::get_instance();

        // Test that init can be called without errors
        try {
            $plugin_init->init();
            $this->assertTrue(true, 'Plugin init should execute without errors');
        } catch (Exception $e) {
            $this->fail('Plugin init should not throw exceptions: ' . $e->getMessage());
        }
    }

    /**
     * Test that all required classes can be loaded.
     */
    public function test_required_classes_loadable() {
        $required_classes = array(
            '\TRB_Product_Search\Plugin_Init',
            '\TRB_Product_Search\Search_Form',
            '\TRB_Product_Search\Search_Query',
            '\TRB_Product_Search\Search_Results',
            '\TRB_Product_Search\Ajax_Handler',
            '\TRB_Product_Search\Settings',
            '\TRB_Product_Search\Typo_Corrector',
        );

        foreach ($required_classes as $class) {
            $this->assertTrue(class_exists($class), "Class {$class} should be loadable");
        }
    }

    /**
     * Test that singleton pattern prevents multiple instances.
     */
    public function test_singleton_prevents_multiple_instances() {
        $reflection = new ReflectionClass('\TRB_Product_Search\Plugin_Init');
        $constructor = $reflection->getConstructor();

        $this->assertTrue($constructor->isPrivate(), 'Constructor should be private');
    }

    /**
     * Set up test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        TRB_Product_Search_Tests_Setup::setup();
    }

    /**
     * Clean up test environment after each test.
     */
    protected function tearDown(): void {
        TRB_Product_Search_Tests_Setup::cleanup();
        parent::tearDown();
    }
}
