<?php
/**
 * Integration tests for Attributes Search functionality.
 *
 * @package TRB_Product_Search\Tests\Integration
 */

use PHPUnit\Framework\TestCase;

/**
 * Class AttributesSearchTest
 *
 * Tests the Attributes_Search class functionality.
 */
class AttributesSearchTest extends TestCase {

    /**
     * Test that Attributes_Search class exists.
     */
    public function test_attributes_search_class_exists() {
        $this->assertTrue(class_exists('\TRB_Product_Search\Attributes_Search'), 'Attributes_Search class should exist');
    }

    /**
     * Test is_enabled returns false by default.
     */
    public function test_is_enabled_returns_false_by_default() {
        $attributes_search = new \TRB_Product_Search\Attributes_Search();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '0');

        $result = $attributes_search->is_enabled();

        $this->assertFalse($result, 'Attributes search should be disabled by default');
    }

    /**
     * Test is_enabled returns true when enabled.
     */
    public function test_is_enabled_returns_true_when_enabled() {
        $attributes_search = new \TRB_Product_Search\Attributes_Search();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');

        $result = $attributes_search->is_enabled();

        $this->assertTrue($result, 'Attributes search should be enabled when option is set to "1"');
    }

    /**
     * Test get_selected_attributes returns empty array by default.
     */
    public function test_get_selected_attributes_returns_empty_array_by_default() {
        $attributes_search = new \TRB_Product_Search\Attributes_Search();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array());

        $result = $attributes_search->get_selected_attributes();

        $this->assertIsArray($result, 'get_selected_attributes should return an array');
        $this->assertEmpty($result, 'get_selected_attributes should return empty array by default');
    }

    /**
     * Test get_selected_attributes returns configured attributes.
     */
    public function test_get_selected_attributes_returns_configured_attributes() {
        $attributes_search = new \TRB_Product_Search\Attributes_Search();
        $expected_attributes = array('pa_color', 'pa_size', 'pa_material');

        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', $expected_attributes);

        $result = $attributes_search->get_selected_attributes();

        $this->assertEquals($expected_attributes, $result, 'get_selected_attributes should return configured attributes');
    }

    /**
     * Test get_selected_attributes handles non-array values.
     */
    public function test_get_selected_attributes_handles_non_array_values() {
        $attributes_search = new \TRB_Product_Search\Attributes_Search();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', 'invalid-string-value');

        $result = $attributes_search->get_selected_attributes();

        $this->assertIsArray($result, 'get_selected_attributes should return array even when option is not an array');
        $this->assertEmpty($result, 'get_selected_attributes should return empty array for invalid values');
    }

    /**
     * Test get_available_attributes returns empty when WooCommerce is inactive.
     *
     * Note: This test verifies behavior when wc_get_attribute_taxonomies function doesn't exist.
     */
    public function test_get_available_attributes_returns_empty_when_wc_inactive() {
        $attributes_search = new \TRB_Product_Search\Attributes_Search();

        // When WooCommerce is not active, wc_get_attribute_taxonomies won't exist
        // The method should return an empty array
        $result = $attributes_search->get_available_attributes();

        $this->assertIsArray($result, 'get_available_attributes should return an array');
        $this->assertEmpty($result, 'get_available_attributes should return empty array when WC is inactive');
    }

    /**
     * Test build_tax_query returns null when disabled.
     */
    public function test_build_tax_query_returns_null_when_disabled() {
        $attributes_search = new \TRB_Product_Search\Attributes_Search();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '0');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array('pa_color'));

        $result = $attributes_search->build_tax_query('red');

        $this->assertNull($result, 'build_tax_query should return null when attributes search is disabled');
    }

    /**
     * Test build_tax_query returns array when enabled.
     */
    public function test_build_tax_query_returns_array_when_enabled() {
        $attributes_search = new \TRB_Product_Search\Attributes_Search();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array('pa_color'));

        $result = $attributes_search->build_tax_query('red');

        $this->assertIsArray($result, 'build_tax_query should return an array when enabled');
        $this->assertArrayHasKey('relation', $result, 'Tax query should contain relation key');
        $this->assertEquals('OR', $result['relation'], 'Relation should be OR');
    }

    /**
     * Test build_tax_query returns null when no attributes selected.
     */
    public function test_build_tax_query_returns_null_when_no_attributes_selected() {
        $attributes_search = new \TRB_Product_Search\Attributes_Search();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array());

        $result = $attributes_search->build_tax_query('red');

        $this->assertNull($result, 'build_tax_query should return null when no attributes are selected');
    }

    /**
     * Test build_tax_query contains correct tax query structure.
     */
    public function test_build_tax_query_contains_correct_structure() {
        $attributes_search = new \TRB_Product_Search\Attributes_Search();
        $selected_attributes = array('pa_color', 'pa_size');
        $search_term = 'red';

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', $selected_attributes);

        $result = $attributes_search->build_tax_query($search_term);

        $this->assertIsArray($result);
        $this->assertEquals('OR', $result['relation']);

        // Check that each selected attribute has a corresponding tax query
        foreach ($selected_attributes as $index => $attribute) {
            $tax_query_item = $result[$index];
            $this->assertEquals($attribute, $tax_query_item['taxonomy'], "Taxonomy should be {$attribute}");
            $this->assertEquals('name', $tax_query_item['field'], 'Field should be name');
            $this->assertEquals($search_term, $tax_query_item['terms'], 'Terms should match search term');
            $this->assertEquals('LIKE', $tax_query_item['operator'], 'Operator should be LIKE');
        }
    }

    /**
     * Test search_attribute_terms returns empty when disabled.
     */
    public function test_search_attribute_terms_returns_empty_when_disabled() {
        $attributes_search = new \TRB_Product_Search\Attributes_Search();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array());

        $result = $attributes_search->search_attribute_terms('red');

        $this->assertIsArray($result, 'search_attribute_terms should return an array');
        $this->assertEmpty($result, 'search_attribute_terms should return empty array when no attributes selected');
    }

    /**
     * Test search_attribute_terms returns array structure.
     *
     * Note: This test verifies the method is callable and returns correct type.
     * Full integration testing would require database with actual terms.
     */
    public function test_search_attribute_terms_returns_array_structure() {
        $attributes_search = new \TRB_Product_Search\Attributes_Search();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array('pa_color'));

        $result = $attributes_search->search_attribute_terms('red');

        $this->assertIsArray($result, 'search_attribute_terms should return an array');
    }

    /**
     * Test build_tax_query handles multiple attributes.
     */
    public function test_build_tax_query_handles_multiple_attributes() {
        $attributes_search = new \TRB_Product_Search\Attributes_Search();
        $selected_attributes = array('pa_color', 'pa_size', 'pa_material');

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', $selected_attributes);

        $result = $attributes_search->build_tax_query('test');

        $this->assertIsArray($result);
        $this->assertEquals('OR', $result['relation']);

        // Verify we have all attributes plus the relation key
        $this->assertCount(count($selected_attributes) + 1, $result, 'Tax query should contain relation + all attributes');
    }

    /**
     * Test Attributes_Search uses singleton pattern.
     */
    public function test_attributes_search_uses_singleton_pattern() {
        $instance1 = \TRB_Product_Search\Attributes_Search::get_instance();
        $instance2 = \TRB_Product_Search\Attributes_Search::get_instance();

        $this->assertSame($instance1, $instance2, 'get_instance should return the same instance');
    }

    /**
     * Test build_tax_query handles special characters in search term.
     */
    public function test_build_tax_query_handles_special_characters() {
        $attributes_search = new \TRB_Product_Search\Attributes_Search();

        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '1');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_selected_attributes', array('pa_color'));

        $special_terms = array('red-blue', 'dark blue', 'light/green');

        foreach ($special_terms as $term) {
            $result = $attributes_search->build_tax_query($term);

            $this->assertIsArray($result);
            $this->assertEquals($term, $result[0]['terms'], "Value should match term: {$term}");
        }
    }

    /**
     * Test get_available_attributes returns array with correct structure.
     *
     * Note: When WooCommerce is active and attributes exist,
     * this should return an array of taxonomy => label pairs.
     */
    public function test_get_available_attributes_structure_when_wc_active() {
        $attributes_search = new \TRB_Product_Search\Attributes_Search();

        $result = $attributes_search->get_available_attributes();

        $this->assertIsArray($result, 'get_available_attributes should return an array');
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
