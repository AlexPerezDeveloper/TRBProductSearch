<?php
/**
 * Integration tests for Search Settings functionality.
 *
 * @package TRB_Product_Search\Tests\Integration
 */

use PHPUnit\Framework\TestCase;

/**
 * Class SearchSettingsTest
 *
 * Tests the Settings class functionality including sanitization.
 */
class SearchSettingsTest extends TestCase {

    /**
     * Test that Settings class exists.
     */
    public function test_settings_class_exists() {
        $this->assertTrue(class_exists('\TRB_Product_Search\Settings'), 'Settings class should exist');
    }

    /**
     * Test SKU option can be enabled.
     */
    public function test_sku_option_can_be_enabled() {
        $settings = new \TRB_Product_Search\Settings();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '1');

        $result = get_option('trb_search_sku_enabled', '0');

        $this->assertEquals('1', $result, 'SKU option should be enabled when set to "1"');
    }

    /**
     * Test SKU option can be disabled.
     */
    public function test_sku_option_can_be_disabled() {
        $settings = new \TRB_Product_Search\Settings();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '0');

        $result = get_option('trb_search_sku_enabled', '0');

        $this->assertEquals('0', $result, 'SKU option should be disabled when set to "0"');
    }

    /**
     * Test attributes option can be enabled.
     */
    public function test_attributes_option_can_be_enabled() {
        $settings = new \TRB_Product_Search\Settings();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');

        $result = get_option('trb_search_attributes_enabled', '0');

        $this->assertEquals('1', $result, 'Attributes option should be enabled when set to "1"');
    }

    /**
     * Test attributes option can be disabled.
     */
    public function test_attributes_option_can_be_disabled() {
        $settings = new \TRB_Product_Search\Settings();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '0');

        $result = get_option('trb_search_attributes_enabled', '0');

        $this->assertEquals('0', $result, 'Attributes option should be disabled when set to "0"');
    }

    /**
     * Test selected attributes can be saved.
     */
    public function test_selected_attributes_can_be_saved() {
        $settings = new \TRB_Product_Search\Settings();
        $expected_attributes = array('pa_color', 'pa_size', 'pa_material');

        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', $expected_attributes);

        $result = get_option('trb_search_selected_attributes', array());

        $this->assertEquals($expected_attributes, $result, 'Selected attributes should be saved and retrieved correctly');
    }

    /**
     * Test selected attributes returns empty array by default.
     */
    public function test_selected_attributes_returns_empty_array_by_default() {
        $settings = new \TRB_Product_Search\Settings();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array());

        $result = get_option('trb_search_selected_attributes', array());

        $this->assertIsArray($result, 'Selected attributes should be an array');
        $this->assertEmpty($result, 'Selected attributes should be empty by default');
    }

    /**
     * Test sanitize_checkbox returns '1' for truthy values.
     */
    public function test_sanitize_checkbox_returns_one_for_truthy() {
        $settings = new \TRB_Product_Search\Settings();

        $truthy_values = array('1', 1, true, 'true');

        foreach ($truthy_values as $value) {
            $result = $settings->sanitize_checkbox($value);
            $this->assertEquals('1', $result, "sanitize_checkbox should return '1' for truthy value: " . var_export($value, true));
        }
    }

    /**
     * Test sanitize_checkbox returns '0' for falsy values.
     */
    public function test_sanitize_checkbox_returns_zero_for_falsy() {
        $settings = new \TRB_Product_Search\Settings();

        $falsy_values = array('0', 0, false, 'false', '', 'anything', '2', 'yes');

        foreach ($falsy_values as $value) {
            $result = $settings->sanitize_checkbox($value);
            $this->assertEquals('0', $result, "sanitize_checkbox should return '0' for falsy value: " . var_export($value, true));
        }
    }

    /**
     * Test sanitize_attributes returns array for valid input.
     */
    public function test_sanitize_attributes_returns_array() {
        $settings = new \TRB_Product_Search\Settings();
        $input = array('pa_color', 'pa_size', 'pa_material');

        $result = $settings->sanitize_attributes($input);

        $this->assertIsArray($result, 'sanitize_attributes should return an array');
        $this->assertEquals($input, $result, 'sanitize_attributes should preserve valid attributes');
    }

    /**
     * Test sanitize_attributes returns empty array for non-array input.
     */
    public function test_sanitize_attributes_returns_empty_for_non_array() {
        $settings = new \TRB_Product_Search\Settings();

        $non_array_inputs = array('string', 123, null, false, true);

        foreach ($non_array_inputs as $input) {
            $result = $settings->sanitize_attributes($input);
            $this->assertIsArray($result, 'sanitize_attributes should return array for non-array input: ' . var_export($input, true));
            $this->assertEmpty($result, 'sanitize_attributes should return empty array for non-array input');
        }
    }

    /**
     * Test sanitize_attributes sanitizes individual attribute names.
     */
    public function test_sanitize_attributes_sanitizes_names() {
        $settings = new \TRB_Product_Search\Settings();
        $input = array('pa_color', 'pa_size<script>', 'pa_material<style>');

        $result = $settings->sanitize_attributes($input);

        $this->assertIsArray($result);
        $this->assertContains('pa_color', $result, 'Valid attribute should be preserved');
        $this->assertNotContains('pa_size<script>', $result, 'Attribute with scripts should be sanitized');
    }

    /**
     * Test sanitize_attributes filters empty values.
     */
    public function test_sanitize_attributes_filters_empty_values() {
        $settings = new \TRB_Product_Search\Settings();
        $input = array('pa_color', '', 'pa_size', null, '0', 'pa_material');

        $result = $settings->sanitize_attributes($input);

        $this->assertIsArray($result);
        $this->assertContains('pa_color', $result);
        $this->assertContains('pa_size', $result);
        $this->assertContains('pa_material', $result);
        $this->assertNotContains('', $result, 'Empty strings should be filtered out');
        $this->assertNotContains(null, $result, 'Null values should be filtered out');
    }

    /**
     * Test sanitize_synonyms returns sanitized string.
     */
    public function test_sanitize_synonyms_returns_sanitized_string() {
        $settings = new \TRB_Product_Search\Settings();
        $input = "car, vehicle, auto\nlaptop, notebook, computer";

        $result = $settings->sanitize_synonyms($input);

        $this->assertIsString($result, 'sanitize_synonyms should return a string');
        $this->assertNotEmpty($result, 'sanitize_synonyms should not return empty string for valid input');
    }

    /**
     * Test sanitize_synonyms handles HTML tags.
     */
    public function test_sanitize_synonyms_handles_html() {
        $settings = new \TRB_Product_Search\Settings();
        $input = "car, <script>alert('xss')</script>vehicle, auto";

        $result = $settings->sanitize_synonyms($input);

        $this->assertIsString($result);
        $this->assertStringNotContainsString('<script>', $result, 'sanitize_synonyms should remove script tags');
    }

    /**
     * Test Settings uses singleton pattern.
     */
    public function test_settings_uses_singleton_pattern() {
        $instance1 = \TRB_Product_Search\Settings::get_instance();
        $instance2 = \TRB_Product_Search\Settings::get_instance();

        $this->assertSame($instance1, $instance2, 'get_instance should return the same instance');
    }

    /**
     * Test init method exists and is callable.
     */
    public function test_init_method_exists() {
        $settings = new \TRB_Product_Search\Settings();

        $this->assertTrue(method_exists($settings, 'init'), 'Settings class should have init method');
        $this->assertTrue(is_callable(array($settings, 'init')), 'init method should be callable');
    }

    /**
     * Test register_settings method exists and is callable.
     */
    public function test_register_settings_method_exists() {
        $settings = new \TRB_Product_Search\Settings();

        $this->assertTrue(method_exists($settings, 'register_settings'), 'Settings class should have register_settings method');
        $this->assertTrue(is_callable(array($settings, 'register_settings')), 'register_settings method should be callable');
    }

    /**
     * Test add_settings_page method exists and is callable.
     */
    public function test_add_settings_page_method_exists() {
        $settings = new \TRB_Product_Search\Settings();

        $this->assertTrue(method_exists($settings, 'add_settings_page'), 'Settings class should have add_settings_page method');
        $this->assertTrue(is_callable(array($settings, 'add_settings_page')), 'add_settings_page method should be callable');
    }

    /**
     * Test render_settings_page method exists and is callable.
     */
    public function test_render_settings_page_method_exists() {
        $settings = new \TRB_Product_Search\Settings();

        $this->assertTrue(method_exists($settings, 'render_settings_page'), 'Settings class should have render_settings_page method');
        $this->assertTrue(is_callable(array($settings, 'render_settings_page')), 'render_settings_page method should be callable');
    }

    /**
     * Test sanitize_checkbox with edge case values.
     */
    public function test_sanitize_checkbox_edge_cases() {
        $settings = new \TRB_Product_Search\Settings();

        // Test with string '1' (expected truthy)
        $result1 = $settings->sanitize_checkbox('1');
        $this->assertEquals('1', $result1, 'sanitize_checkbox should return "1" for string "1"');

        // Test with integer 1 (expected truthy)
        $result2 = $settings->sanitize_checkbox(1);
        $this->assertEquals('1', $result2, 'sanitize_checkbox should return "1" for integer 1');

        // Test with string '0' (expected falsy)
        $result3 = $settings->sanitize_checkbox('0');
        $this->assertEquals('0', $result3, 'sanitize_checkbox should return "0" for string "0"');

        // Test with empty string (expected falsy)
        $result4 = $settings->sanitize_checkbox('');
        $this->assertEquals('0', $result4, 'sanitize_checkbox should return "0" for empty string');
    }

    /**
     * Test selected attributes with single attribute.
     */
    public function test_selected_attributes_with_single_attribute() {
        $settings = new \TRB_Product_Search\Settings();
        $single_attribute = array('pa_color');

        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', $single_attribute);

        $result = get_option('trb_search_selected_attributes', array());

        $this->assertCount(1, $result, 'Should have exactly one attribute');
        $this->assertEquals('pa_color', $result[0], 'Should contain the color attribute');
    }

    /**
     * Test selected attributes with many attributes.
     */
    public function test_selected_attributes_with_many_attributes() {
        $settings = new \TRB_Product_Search\Settings();
        $many_attributes = array('pa_color', 'pa_size', 'pa_material', 'pa_brand', 'pa_style');

        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', $many_attributes);

        $result = get_option('trb_search_selected_attributes', array());

        $this->assertCount(5, $result, 'Should have exactly five attributes');
        $this->assertEquals($many_attributes, $result, 'Should contain all five attributes');
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
