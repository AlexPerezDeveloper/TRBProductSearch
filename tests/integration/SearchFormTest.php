<?php
/**
 * Integration tests for Search Form functionality.
 *
 * @package TRB_Product_Search\Tests\Integration
 */

use PHPUnit\Framework\TestCase;

class SearchFormTest extends TestCase {

    /**
     * Test that Search_Form class exists and is loadable.
     */
    public function test_search_form_class_exists() {
        $this->assertTrue(class_exists('\TRB_Product_Search\Search_Form'), 'Search_Form class should exist');
    }

    /**
     * Test Search_Form singleton pattern.
     */
    public function test_search_form_singleton() {
        $instance1 = \TRB_Product_Search\Search_Form::get_instance();
        $instance2 = \TRB_Product_Search\Search_Form::get_instance();

        $this->assertSame($instance1, $instance2, 'get_instance should return the same instance');
    }

    /**
     * Test that register_shortcode method exists.
     */
    public function test_register_shortcode_method_exists() {
        $search_form = \TRB_Product_Search\Search_Form::get_instance();
        $this->assertTrue(method_exists($search_form, 'register_shortcode'), 'register_shortcode method should exist');
    }

    /**
     * Test that render_shortcode method exists.
     */
    public function test_render_shortcode_method_exists() {
        $search_form = \TRB_Product_Search\Search_Form::get_instance();
        $this->assertTrue(method_exists($search_form, 'render_shortcode'), 'render_shortcode method should exist');
    }

    /**
     * Test shortcode rendering with default attributes.
     */
    public function test_render_shortcode_default_attributes() {
        $search_form = \TRB_Product_Search\Search_Form::get_instance();
        $output = $search_form->render_shortcode(array());

        $this->assertIsString($output, 'Shortcode output should be a string');
        $this->assertNotEmpty($output, 'Shortcode output should not be empty');
    }

    /**
     * Test shortcode rendering with custom placeholder.
     */
    public function test_render_shortcode_custom_placeholder() {
        $search_form = \TRB_Product_Search\Search_Form::get_instance();
        $output = $search_form->render_shortcode(array('placeholder' => 'Search our products...'));

        $this->assertIsString($output, 'Shortcode output should be a string');
        $this->assertStringContainsString('Search our products...', $output, 'Custom placeholder should be in output');
    }

    /**
     * Test that search form HTML structure is correct.
     */
    public function test_search_form_html_structure() {
        $search_form = \TRB_Product_Search\Search_Form::get_instance();
        $output = $search_form->render_shortcode(array());

        // Check for required elements
        $this->assertStringContainsString('trb-product-search-container', $output, 'Container class should be present');
        $this->assertStringContainsString('trb-product-search-form', $output, 'Form class should be present');
        $this->assertStringContainsString('search-field', $output, 'Search field class should be present');
        $this->assertStringContainsString('search-submit', $output, 'Submit button class should be present');
        $this->assertStringContainsString('<form', $output, 'Form tag should be present');
        $this->assertStringContainsString('action="http://example.com/"', $output, 'Form action should point to home URL');
    }

    /**
     * Test search form input attributes.
     */
    public function test_search_form_input_attributes() {
        $search_form = \TRB_Product_Search\Search_Form::get_instance();
        $output = $search_form->render_shortcode(array());

        $this->assertStringContainsString('type="search"', $output, 'Input type should be search');
        $this->assertStringContainsString('name="s"', $output, 'Input name should be s (WordPress standard)');
        $this->assertStringContainsString('id="trb_search_field"', $output, 'Input ID should be trb_search_field');
        $this->assertStringContainsString('name="post_type"', $output, 'Post type hidden field should be present');
        $this->assertStringContainsString('value="product"', $output, 'Post type value should be product');
    }

    /**
     * Test search form with query parameter.
     */
    public function test_search_form_with_query_param() {
        $_GET['s'] = 'wireless headphones';
        $search_form = \TRB_Product_Search\Search_Form::get_instance();
        $output = $search_form->render_shortcode(array());

        $this->assertStringContainsString('wireless headphones', $output, 'Query value should be in output');
        unset($_GET['s']);
    }

    /**
     * Test accessibility features.
     */
    public function test_accessibility_features() {
        $search_form = \TRB_Product_Search\Search_Form::get_instance();
        $output = $search_form->render_shortcode(array());

        // Check for accessibility attributes
        $this->assertStringContainsString('role="search"', $output, 'Search role attribute should be present');
        $this->assertStringContainsString('screen-reader-text', $output, 'Screen reader text label should be present');
    }

    /**
     * Test shortcode with empty attributes.
     */
    public function test_shortcode_with_empty_attributes() {
        $search_form = \TRB_Product_Search\Search_Form::get_instance();
        $output = $search_form->render_shortcode('');

        $this->assertIsString($output, 'Shortcode should handle empty attributes');
        $this->assertNotEmpty($output, 'Output should not be empty with empty attributes');
    }

    /**
     * Test form button text.
     */
    public function test_form_button_text() {
        $search_form = \TRB_Product_Search\Search_Form::get_instance();
        $output = $search_form->render_shortcode(array());

        $this->assertStringContainsString('Search', $output, 'Submit button text should be "Search"');
    }

    /**
     * Test multiple shortcode instances don't conflict.
     */
    public function test_multiple_shortcode_instances() {
        $search_form = \TRB_Product_Search\Search_Form::get_instance();
        $output1 = $search_form->render_shortcode(array('placeholder' => 'First search'));
        $output2 = $search_form->render_shortcode(array('placeholder' => 'Second search'));

        $this->assertStringContainsString('First search', $output1, 'First instance should have its placeholder');
        $this->assertStringContainsString('Second search', $output2, 'Second instance should have its placeholder');
    }

    /**
     * Set up test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        TRB_Product_Search_Tests_Setup::setup();
        $_GET = array();
    }

    /**
     * Clean up test environment after each test.
     */
    protected function tearDown(): void {
        $_GET = array();
        TRB_Product_Search_Tests_Setup::cleanup();
        parent::tearDown();
    }
}
