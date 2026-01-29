<?php
/**
 * Unit tests for Typo Corrector.
 *
 * @package TRB_Product_Search\Tests\Unit
 */

use PHPUnit\Framework\TestCase;

class TypoCorrectorTest extends TestCase
{

    /**
     * Test correction with accents.
     */
    public function test_correction_with_accents()
    {
        $corrector = \TRB_Product_Search\Typo_Corrector::get_instance();

        // Mock dictionary with "atlético"
        // V2 Structure: [length][first_char][]
        $index = array(
            8 => array('a' => array('atlético')),
            5 => array('b' => array('balón')),
            6 => array('f' => array('fútbol'))
        );
        TRB_Product_Search_Tests_Setup::set_option('trb_search_word_index_v2', $index);

        // "atlethico" -> "atlético" (len 9 vs 8)
        // "atletico" -> "atlético" (len 8 vs 8)

        $result = $corrector->correct('atletico');

        $this->assertEquals('atlético', $result, 'Should correct atletico to atlético');
    }

    /**
     * Test simple typo.
     */
    public function test_simple_typo()
    {
        $corrector = \TRB_Product_Search\Typo_Corrector::get_instance();

        $index = array(
            6 => array('i' => array('iphone')),
            7 => array('s' => array('samsung'))
        );
        TRB_Product_Search_Tests_Setup::set_option('trb_search_word_index_v2', $index);

        $result = $corrector->correct('ipone');
        $this->assertEquals('iphone', $result);
    }

    protected function setUp(): void
    {
        parent::setUp();
        TRB_Product_Search_Tests_Setup::setup();
    }

    protected function tearDown(): void
    {
        TRB_Product_Search_Tests_Setup::cleanup();
        parent::tearDown();
    }
}
