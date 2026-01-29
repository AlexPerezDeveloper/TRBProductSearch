<?php
/**
 * Typo Correction Unit Tests
 *
 * @package TRB_Product_Search\Tests\Unit
 */

use PHPUnit\Framework\TestCase;

/**
 * Class TypoCorrectionTest
 *
 * Tests for typo correction functionality in Search_Query.
 */
class TypoCorrectionTest extends TestCase
{
    /**
     * Test that has_correction returns false by default.
     */
    public function test_has_correction_returns_false_by_default()
    {
        $query_handler = new \TRB_Product_Search\Search_Query();

        $this->assertFalse($query_handler->has_correction());
    }

    /**
     * Test that get_original_term returns null by default.
     */
    public function test_get_original_term_returns_null_by_default()
    {
        $query_handler = new \TRB_Product_Search\Search_Query();

        $this->assertNull($query_handler->get_original_term());
    }

    /**
     * Test that get_corrected_term returns null by default.
     */
    public function test_get_corrected_term_returns_null_by_default()
    {
        $query_handler = new \TRB_Product_Search\Search_Query();

        $this->assertNull($query_handler->get_corrected_term());
    }

    /**
     * Test that get_correction_info returns correct structure.
     */
    public function test_get_correction_info_returns_correct_structure()
    {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $info = $query_handler->get_correction_info();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('original', $info);
        $this->assertArrayHasKey('corrected', $info);
        $this->assertNull($info['original']);
        $this->assertNull($info['corrected']);
    }

    /**
     * Test that original term is stored after search.
     */
    public function test_original_term_is_stored_after_search()
    {
        TRB_Product_Search_Tests_Setup::set_option('trb_search_synonyms', '');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '0');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '0');

        $query_handler = new \TRB_Product_Search\Search_Query();
        $query_handler->search('test-term');

        $this->assertEquals('test-term', $query_handler->get_original_term());
    }

    /**
     * Test that correction is not triggered for short terms.
     */
    public function test_correction_not_triggered_for_short_terms()
    {
        TRB_Product_Search_Tests_Setup::set_option('trb_search_synonyms', '');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '0');
        TRB_Product_Search_Tests_Setup::set_option('trb_search_attributes_enabled', '0');

        $query_handler = new \TRB_Product_Search\Search_Query();
        $query_handler->search('cam'); // 3 characters

        $this->assertFalse($query_handler->has_correction());
        $this->assertNull($query_handler->get_corrected_term());
    }

    /**
     * Set up test environment before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        TRB_Product_Search_Tests_Setup::setup();
    }

    /**
     * Clean up test environment after each test.
     */
    protected function tearDown(): void
    {
        TRB_Product_Search_Tests_Setup::cleanup();
        parent::tearDown();
    }
}
