<?php
/**
 * Unit tests for Typo Corrector.
 *
 * @package TRB_Product_Search\Tests\Unit
 */

use PHPUnit\Framework\TestCase;

class TypoCorrectorTest extends TestCase {

    /**
     * Test correction with accents.
     */
    public function test_correction_with_accents() {
        $corrector = \TRB_Product_Search\Typo_Corrector::get_instance();
        
        // Mock dictionary with "atlético"
        // Note: We need to set the option that the class reads
        TRB_Product_Search_Tests_Setup::set_option('trb_search_word_index', array('atlético', 'balón', 'fútbol'));

        // "atlethic" -> "atlético"
        // Levenshtein byte distance:
        // atlethic (8)
        // atlético (9 bytes in UTF-8)
        // This is tricky for standard levenshtein.
        
        $result = $corrector->correct('atlethic');

        // We expect it to find "atlético" if the distance logic permits
        $this->assertEquals('atlético', $result, 'Should correct atlethic to atlético');
    }

    /**
     * Test simple typo.
     */
    public function test_simple_typo() {
        $corrector = \TRB_Product_Search\Typo_Corrector::get_instance();
        TRB_Product_Search_Tests_Setup::set_option('trb_search_word_index', array('iphone', 'samsung'));

        $result = $corrector->correct('ipone');
        $this->assertEquals('iphone', $result);
    }

    protected function setUp(): void {
        parent::setUp();
        TRB_Product_Search_Tests_Setup::setup();
    }

    protected function tearDown(): void {
        TRB_Product_Search_Tests_Setup::cleanup();
        parent::tearDown();
    }
}
