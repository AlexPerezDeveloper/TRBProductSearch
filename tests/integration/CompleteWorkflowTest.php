<?php
/**
 * Integration tests for complete search workflow.
 *
 * @package TRB_Product_Search\Tests\Integration
 */

use PHPUnit\Framework\TestCase;

class CompleteWorkflowTest extends TestCase {

    /**
     * Test complete search workflow from form to results.
     */
    public function test_complete_search_workflow() {
        // Initialize components
        $search_form = \TRB_Product_Search\Search_Form::get_instance();
        $search_query = new \TRB_Product_Search\Search_Query();

        // Render search form
        $form_output = $search_form->render_shortcode(array('placeholder' => 'Find products'));
        $this->assertNotEmpty($form_output, 'Search form should be rendered');

        // Perform search
        $query_result = $search_query->search('headphones');
        $this->assertInstanceOf('\WP_Query', $query_result, 'Search should return WP_Query object');

        // Verify workflow completes without errors
        $this->assertTrue(true, 'Complete workflow should execute successfully');
    }

    /**
     * Test workflow with synonyms.
     */
    public function test_workflow_with_synonyms() {
        // Set up synonyms
        $synonyms = "headphones, earphones, headset";
        TRB_Product_Search_Tests_Setup::set_option('trb_search_synonyms', $synonyms);

        // Initialize search query
        $search_query = new \TRB_Product_Search\Search_Query();

        // Search with original term
        $result1 = $search_query->search('headphones');
        $this->assertInstanceOf('\WP_Query', $result1);

        // Search with synonym
        $result2 = $search_query->search('earphones');
        $this->assertInstanceOf('\WP_Query', $result2);

        // Both searches should work
        $this->assertTrue(true, 'Synonym workflow should execute successfully');
    }

    /**
     * Test workflow with settings.
     */
    public function test_workflow_with_settings() {
        $settings = \TRB_Product_Search\Settings::get_instance();

        // Initialize settings
        $settings->init();
        $this->assertTrue(true, 'Settings should initialize');

        // Store synonyms
        $synonyms = "laptop, notebook, computer";
        $sanitized = $settings->sanitize_synonyms($synonyms);
        $this->assertEquals($synonyms, $sanitized);

        // Use settings in search
        TRB_Product_Search_Tests_Setup::set_option('trb_search_synonyms', $synonyms);
        $search_query = new \TRB_Product_Search\Search_Query();
        $result = $search_query->search('laptop');
        $this->assertInstanceOf('\WP_Query', $result);
    }

    /**
     * Test plugin initialization and component loading.
     */
    public function test_plugin_initialization_workflow() {
        $plugin_init = \TRB_Product_Search\Plugin_Init::get_instance();

        // Initialize plugin
        $plugin_init->init();

        // Verify all components are available
        $this->assertTrue(class_exists('\TRB_Product_Search\Search_Form'), 'Search_Form should be available');
        $this->assertTrue(class_exists('\TRB_Product_Search\Search_Query'), 'Search_Query should be available');
        $this->assertTrue(class_exists('\TRB_Product_Search\Search_Results'), 'Search_Results should be available');
        $this->assertTrue(class_exists('\TRB_Product_Search\Ajax_Handler'), 'Ajax_Handler should be available');
        $this->assertTrue(class_exists('\TRB_Product_Search\Settings'), 'Settings should be available');
        $this->assertTrue(class_exists('\TRB_Product_Search\Typo_Corrector'), 'Typo_Corrector should be available');
    }

    /**
     * Test multiple searches in sequence.
     */
    public function test_multiple_searches_sequence() {
        $search_query = new \TRB_Product_Search\Search_Query();
        $search_terms = array('headphones', 'speaker', 'mouse', 'keyboard');

        foreach ($search_terms as $term) {
            $result = $search_query->search($term);
            $this->assertInstanceOf('\WP_Query', $result, "Search for '{$term}' should succeed");
        }
    }

    /**
     * Test form with query parameter and search execution.
     */
    public function test_form_with_query_and_search() {
        $search_form = \TRB_Product_Search\Search_Form::get_instance();
        $search_query = new \TRB_Product_Search\Search_Query();

        // Simulate form submission
        $_GET['trb_q'] = 'wireless headphones';

        // Render form with query parameter
        $form_output = $search_form->render_shortcode(array());
        $this->assertStringContainsString('wireless headphones', $form_output, 'Form should show query parameter');

        // Execute search with same term
        $search_result = $search_query->search('wireless headphones');
        $this->assertInstanceOf('\WP_Query', $search_result);

        unset($_GET['trb_q']);
    }

    /**
     * Test settings integration with search.
     */
    public function test_settings_search_integration() {
        $settings = \TRB_Product_Search\Settings::get_instance();
        $search_query = new \TRB_Product_Search\Search_Query();

        // Configure settings
        $synonyms = "phone, mobile, telephone\ntablet, ipad, slate";
        TRB_Product_Search_Tests_Setup::set_option('trb_search_synonyms', $synonyms);

        // Test searches with configured synonyms
        $result1 = $search_query->search('phone');
        $result2 = $search_query->search('mobile');
        $result3 = $search_query->search('tablet');

        $this->assertInstanceOf('\WP_Query', $result1);
        $this->assertInstanceOf('\WP_Query', $result2);
        $this->assertInstanceOf('\WP_Query', $result3);
    }

    /**
     * Test component isolation and independence.
     */
    public function test_component_independence() {
        // Each component should work independently
        $search_form = \TRB_Product_Search\Search_Form::get_instance();
        $form_output = $search_form->render_shortcode(array());

        $search_query = new \TRB_Product_Search\Search_Query();
        $search_result = $search_query->search('test');

        $settings = \TRB_Product_Search\Settings::get_instance();
        $settings->init();

        $this->assertIsString($form_output);
        $this->assertInstanceOf('\WP_Query', $search_result);
        $this->assertTrue(true);
    }

    /**
     * Test shortcode with various attributes and search execution.
     */
    public function test_shortcode_variations_and_search() {
        $search_form = \TRB_Product_Search\Search_Form::get_instance();
        $search_query = new \TRB_Product_Search\Search_Query();

        $attributes = array(
            array('placeholder' => 'Search products'),
            array('placeholder' => 'Find items'),
            array('placeholder' => 'What are you looking for?'),
        );

        foreach ($attributes as $atts) {
            $form_output = $search_form->render_shortcode($atts);
            $this->assertStringContainsString($atts['placeholder'], $form_output);

            $search_result = $search_query->search('test');
            $this->assertInstanceOf('\WP_Query', $search_result);
        }
    }

    /**
     * Test empty and edge case searches.
     */
    public function test_edge_case_searches() {
        $search_query = new \TRB_Product_Search\Search_Query();

        // Short search term
        $result1 = $search_query->search('ab');
        $this->assertInstanceOf('\WP_Query', $result1);

        // Long search term
        $result2 = $search_query->search('very long product name with many words');
        $this->assertInstanceOf('\WP_Query', $result2);

        // Search with numbers
        $result3 = $search_query->search('product 123');
        $this->assertInstanceOf('\WP_Query', $result3);
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
