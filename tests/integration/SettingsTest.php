<?php
/**
 * Integration tests for Settings functionality.
 *
 * @package TRB_Product_Search\Tests\Integration
 */

use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase {

    /**
     * Test that Settings class exists.
     */
    public function test_settings_class_exists() {
        $this->assertTrue(class_exists('\TRB_Product_Search\Settings'), 'Settings class should exist');
    }

    /**
     * Test Settings singleton pattern.
     */
    public function test_settings_singleton() {
        $instance1 = \TRB_Product_Search\Settings::get_instance();
        $instance2 = \TRB_Product_Search\Settings::get_instance();

        $this->assertSame($instance1, $instance2, 'get_instance should return same instance');
    }

    /**
     * Test that init method exists.
     */
    public function test_init_method_exists() {
        $settings = \TRB_Product_Search\Settings::get_instance();
        $this->assertTrue(method_exists($settings, 'init'), 'init method should exist');
    }

    /**
     * Test that add_settings_page method exists.
     */
    public function test_add_settings_page_method_exists() {
        $settings = \TRB_Product_Search\Settings::get_instance();
        $this->assertTrue(method_exists($settings, 'add_settings_page'), 'add_settings_page method should exist');
    }

    /**
     * Test that register_settings method exists.
     */
    public function test_register_settings_method_exists() {
        $settings = \TRB_Product_Search\Settings::get_instance();
        $this->assertTrue(method_exists($settings, 'register_settings'), 'register_settings method should exist');
    }

    /**
     * Test that render_synonyms_field method exists.
     */
    public function test_render_synonyms_field_method_exists() {
        $settings = \TRB_Product_Search\Settings::get_instance();
        $this->assertTrue(method_exists($settings, 'render_synonyms_field'), 'render_synonyms_field method should exist');
    }

    /**
     * Test that render_settings_page method exists.
     */
    public function test_render_settings_page_method_exists() {
        $settings = \TRB_Product_Search\Settings::get_instance();
        $this->assertTrue(method_exists($settings, 'render_settings_page'), 'render_settings_page method should exist');
    }

    /**
     * Test settings initialization.
     */
    public function test_settings_initialization() {
        $settings = \TRB_Product_Search\Settings::get_instance();

        // Test that init can be called without errors
        try {
            $settings->init();
            $this->assertTrue(true, 'Settings init should execute without errors');
        } catch (Exception $e) {
            $this->fail('Settings init should not throw exceptions: ' . $e->getMessage());
        }
    }

    /**
     * Test synonym sanitization.
     */
    public function test_synonym_sanitization() {
        $settings = \TRB_Product_Search\Settings::get_instance();

        $input = "car, vehicle, auto";
        $sanitized = $settings->sanitize_synonyms($input);

        $this->assertEquals($input, $sanitized, 'Valid synonyms should be preserved');
    }

    /**
     * Test synonym sanitization with HTML tags.
     */
    public function test_synonym_sanitization_with_html() {
        $settings = \TRB_Product_Search\Settings::get_instance();

        $input = "<script>alert('test')</script>car, vehicle";
        $sanitized = $settings->sanitize_synonyms($input);

        $this->assertStringNotContainsString('<script>', $sanitized, 'HTML tags should be removed');
        $this->assertStringContainsString('car', $sanitized, 'Valid text should be preserved');
    }

    /**
     * Test synonym field rendering.
     */
    public function test_render_synonyms_field() {
        $settings = \TRB_Product_Search\Settings::get_instance();

        ob_start();
        $settings->render_synonyms_field();
        $output = ob_get_clean();

        $this->assertIsString($output, 'Field rendering should return string');
        $this->assertNotEmpty($output, 'Field rendering should not be empty');
    }

    /**
     * Test synonym field contains textarea.
     */
    public function test_synonym_field_has_textarea() {
        $settings = \TRB_Product_Search\Settings::get_instance();

        ob_start();
        $settings->render_synonyms_field();
        $output = ob_get_clean();

        $this->assertStringContainsString('<textarea', $output, 'Field should contain textarea element');
        $this->assertStringContainsString('name="trb_search_synonyms"', $output, 'Field should have correct name attribute');
    }

    /**
     * Test synonym field includes description.
     */
    public function test_synonym_field_includes_description() {
        $settings = \TRB_Product_Search\Settings::get_instance();

        ob_start();
        $settings->render_synonyms_field();
        $output = ob_get_clean();

        $this->assertStringContainsString('description', $output, 'Field should include description text');
    }

    /**
     * Test settings page rendering.
     */
    public function test_render_settings_page() {
        $settings = \TRB_Product_Search\Settings::get_instance();

        ob_start();
        $settings->render_settings_page();
        $output = ob_get_clean();

        $this->assertIsString($output, 'Page rendering should return string');
        $this->assertNotEmpty($output, 'Page rendering should not be empty');
    }

    /**
     * Test settings page contains form.
     */
    public function test_settings_page_has_form() {
        $settings = \TRB_Product_Search\Settings::get_instance();

        ob_start();
        $settings->render_settings_page();
        $output = ob_get_clean();

        $this->assertStringContainsString('<form', $output, 'Settings page should contain form element');
    }

    /**
     * Test synonym storage in options.
     */
    public function test_synonym_storage() {
        $synonyms = "test, exam, trial";
        TRB_Product_Search_Tests_Setup::set_option('trb_search_synonyms', $synonyms);

        $retrieved = get_option('trb_search_synonyms', '');
        $this->assertEquals($synonyms, $retrieved, 'Synonyms should be stored in options');
    }

    /**
     * Test multiple synonym groups parsing.
     */
    public function test_multiple_synonym_groups() {
        $input = "car, vehicle, auto\nphone, mobile, telephone";
        TRB_Product_Search_Tests_Setup::set_option('trb_search_synonyms', $input);

        $retrieved = get_option('trb_search_synonyms', '');
        $this->assertEquals($input, $retrieved, 'Multiple synonym groups should be preserved');
    }

    /**
     * Test singleton prevents multiple instances.
     */
    public function test_singleton_prevents_multiple_instances() {
        $reflection = new ReflectionClass('\TRB_Product_Search\Settings');
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
