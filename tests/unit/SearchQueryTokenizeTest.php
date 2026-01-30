<?php
/**
 * Unit tests for Search Query tokenization.
 *
 * @package TRB_Product_Search\Tests\Unit
 */

use PHPUnit\Framework\TestCase;

/**
 * Test class for Search_Query tokenization.
 *
 * Tests the parse_search_terms method which is private.
 * We use ReflectionClass to access it for testing.
 */
class SearchQueryTokenizeTest extends TestCase
{

    /**
     * Instance of Search_Query class.
     *
     * @var \TRB_Product_Search\Search_Query
     */
    private $search_query;

    /**
     * Reflection method for parse_search_terms.
     *
     * @var \ReflectionMethod
     */
    private $parse_method;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        TRB_Product_Search_Tests_Setup::setup();

        $this->search_query = new \TRB_Product_Search\Search_Query();

        // Use reflection to access private method
        $reflection = new ReflectionClass($this->search_query);
        $this->parse_method = $reflection->getMethod('parse_search_terms');
        $this->parse_method->setAccessible(true);
    }

    /**
     * Clean up test environment.
     */
    protected function tearDown(): void
    {
        TRB_Product_Search_Tests_Setup::cleanup();
        parent::tearDown();
    }

    /**
     * Helper to call the private parse_search_terms method.
     *
     * @param string $term Raw search term.
     * @return array Parsed tokens.
     */
    private function parse($term)
    {
        return $this->parse_method->invoke($this->search_query, $term);
    }

    /**
     * Test that empty string returns empty array.
     */
    public function test_empty_string_returns_empty_array()
    {
        $result = $this->parse('');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test that single word returns array with one element.
     */
    public function test_single_word_returns_array_with_one_element()
    {
        $result = $this->parse('disco');
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('disco', $result[0]);
    }

    /**
     * Test that multiple words are split correctly.
     */
    public function test_multiple_words_split_correctly()
    {
        $result = $this->parse('disco duro ssd');
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals('disco', $result[0]);
        $this->assertEquals('duro', $result[1]);
        $this->assertEquals('ssd', $result[2]);
    }

    /**
     * Test that extra whitespace is handled correctly.
     */
    public function test_extra_whitespace_handled()
    {
        $result = $this->parse('  disco   duro  ssd  ');
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals('disco', $result[0]);
        $this->assertEquals('duro', $result[1]);
        $this->assertEquals('ssd', $result[2]);
    }

    /**
     * Test that stop words are removed.
     */
    public function test_stop_words_removed()
    {
        $result = $this->parse('el disco de la casa');
        $this->assertIsArray($result);
        // Should only have 'disco' and 'casa' (el, de, la are stop words)
        $this->assertCount(2, $result);
        $this->assertEquals('disco', $result[0]);
        $this->assertEquals('casa', $result[1]);
    }

    /**
     * Test that all Spanish stop words are removed.
     */
    public function test_all_spanish_stop_words_removed()
    {
        $stop_words = array('el', 'la', 'de', 'en', 'y', 'a', 'los', 'las', 'un', 'una', 'del', 'al', 'con', 'por', 'para');
        $search_term = implode(' ', $stop_words);

        $result = $this->parse($search_term);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test that more than 5 words are truncated to 5.
     */
    public function test_more_than_five_words_truncated()
    {
        $result = $this->parse('uno dos tres cuatro cinco seis siete');
        $this->assertIsArray($result);
        $this->assertCount(5, $result);
        $this->assertEquals('uno', $result[0]);
        $this->assertEquals('dos', $result[1]);
        $this->assertEquals('tres', $result[2]);
        $this->assertEquals('cuatro', $result[3]);
        $this->assertEquals('cinco', $result[4]);
    }

    /**
     * Test that exactly 5 words are not truncated.
     */
    public function test_exactly_five_words_not_truncated()
    {
        $result = $this->parse('uno dos tres cuatro cinco');
        $this->assertIsArray($result);
        $this->assertCount(5, $result);
        $this->assertEquals('uno', $result[0]);
        $this->assertEquals('dos', $result[1]);
        $this->assertEquals('tres', $result[2]);
        $this->assertEquals('cuatro', $result[3]);
        $this->assertEquals('cinco', $result[4]);
    }

    /**
     * Test that words with less than 2 characters are filtered out.
     */
    public function test_short_words_filtered_out()
    {
        $result = $this->parse('a b cd ef ghi');
        $this->assertIsArray($result);
        // 'a' and 'b' should be filtered out (less than 2 chars)
        $this->assertCount(3, $result);
        $this->assertEquals('cd', $result[0]);
        $this->assertEquals('ef', $result[1]);
        $this->assertEquals('ghi', $result[2]);
    }

    /**
     * Test that words with exactly 2 characters are kept.
     */
    public function test_exactly_two_char_words_kept()
    {
        $result = $this->parse('tv pc');
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('tv', $result[0]);
        $this->assertEquals('pc', $result[1]);
    }

    /**
     * Test that input is normalized to lowercase.
     */
    public function test_input_normalized_to_lowercase()
    {
        $result = $this->parse('DISCO DURO SSD');
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals('disco', $result[0]);
        $this->assertEquals('duro', $result[1]);
        $this->assertEquals('ssd', $result[2]);
    }

    /**
     * Test mixed case with accents.
     */
    public function test_mixed_case_with_accents()
    {
        $result = $this->parse('Camiseta de Algodón');
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('camiseta', $result[0]);
        $this->assertEquals('algodón', $result[1]);
    }

    /**
     * Test that stop words filtering works with mixed case.
     */
    public function test_stop_words_case_insensitive()
    {
        $result = $this->parse('EL Disco DE La Casa');
        $this->assertIsArray($result);
        // EL, DE, LA should be removed (case insensitive due to normalization)
        $this->assertCount(2, $result);
        $this->assertEquals('disco', $result[0]);
        $this->assertEquals('casa', $result[1]);
    }

    /**
     * Test combination of stop words and truncation.
     */
    public function test_stop_words_and_truncation_combined()
    {
        // 6 content words + stop words, should return 5 content words
        $result = $this->parse('el disco duro de la ssd para un ordenador');
        $this->assertIsArray($result);
        // Stop words: el, de, la, para, un
        // Content words: disco, duro, ssd, ordenador (only 4 after filtering)
        $this->assertCount(4, $result);
        $this->assertEquals('disco', $result[0]);
        $this->assertEquals('duro', $result[1]);
        $this->assertEquals('ssd', $result[2]);
        $this->assertEquals('ordenador', $result[3]);
    }

    /**
     * Test that result array has sequential integer keys.
     */
    public function test_result_has_sequential_keys()
    {
        $result = $this->parse('disco duro ssd');
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
        $this->assertArrayNotHasKey(3, $result);
    }
}
