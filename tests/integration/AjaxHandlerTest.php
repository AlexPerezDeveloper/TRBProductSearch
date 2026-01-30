<?php
/**
 * Integration tests for Ajax_Handler class.
 *
 * @package TRB_Product_Search\Tests\Integration
 */

use PHPUnit\Framework\TestCase;

/**
 * Test class for Ajax_Handler multi-word search validation.
 *
 * Tests validation edge cases including:
 * - Multi-word search term validation
 * - All stop words handling
 * - All short words handling
 * - Empty search term handling
 * - Error messages are user-friendly
 */
class AjaxHandlerTest extends TestCase
{

    /**
     * Instance of Ajax_Handler class.
     *
     * @var \TRB_Product_Search\Ajax_Handler
     */
    private $ajax_handler;

    /**
     * Reflection method for validate_search_term.
     *
     * @var \ReflectionMethod
     */
    private $validate_method;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        TRB_Product_Search_Tests_Setup::setup();

        $this->ajax_handler = \TRB_Product_Search\Ajax_Handler::get_instance();

        // Use reflection to access private method
        $reflection = new ReflectionClass($this->ajax_handler);
        $this->validate_method = $reflection->getMethod('validate_search_term');
        $this->validate_method->setAccessible(true);
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
     * Helper to call the private validate_search_term method.
     *
     * @param string $term Raw search term.
     * @return true|\WP_Error True if valid, WP_Error if invalid.
     */
    private function validate($term)
    {
        return $this->validate_method->invoke($this->ajax_handler, $term);
    }

    /**
     * Test that empty string returns error.
     */
    public function test_empty_string_returns_error()
    {
        $result = $this->validate('');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('empty_term', $result->get_error_code());
        $this->assertStringContainsString('Please enter', $result->get_error_message());
    }

    /**
     * Test that whitespace-only string returns empty error.
     */
    public function test_whitespace_only_returns_empty_error()
    {
        $result = $this->validate('   ');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('empty_term', $result->get_error_code());
    }

    /**
     * Test that single valid word passes validation.
     */
    public function test_single_valid_word_passes()
    {
        $result = $this->validate('disco');

        $this->assertTrue($result);
    }

    /**
     * Test that multi-word search term passes validation.
     */
    public function test_multi_word_search_passes()
    {
        $result = $this->validate('disco duro ssd');

        $this->assertTrue($result);
    }

    /**
     * Test that search with only stop words returns appropriate error.
     */
    public function test_only_stop_words_returns_error()
    {
        $result = $this->validate('el la de');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('only_stop_words', $result->get_error_code());
        $this->assertStringContainsString('common words', $result->get_error_message());
        $this->assertStringContainsString('el', $result->get_error_message());
    }

    /**
     * Test that search with only short words returns appropriate error.
     */
    public function test_only_short_words_returns_error()
    {
        $result = $this->validate('a b c');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('only_short_words', $result->get_error_code());
        $this->assertStringContainsString('2 characters', $result->get_error_message());
    }

    /**
     * Test that mixed stop words and valid words passes.
     */
    public function test_mixed_stop_words_and_valid_passes()
    {
        $result = $this->validate('el disco de la casa');

        $this->assertTrue($result);
    }

    /**
     * Test that single character word fails validation.
     */
    public function test_single_character_fails()
    {
        $result = $this->validate('x');

        $this->assertInstanceOf(WP_Error::class, $result);
    }

    /**
     * Test that two character word passes validation.
     */
    public function test_two_character_word_passes()
    {
        $result = $this->validate('tv');

        $this->assertTrue($result);
    }

    /**
     * Test that all Spanish stop words return stop words error.
     */
    public function test_all_spanish_stop_words_return_error()
    {
        $stop_words = array('el', 'la', 'de', 'en', 'y', 'a', 'los', 'las', 'un', 'una', 'del', 'al', 'con', 'por', 'para');
        $search_term = implode(' ', $stop_words);

        $result = $this->validate($search_term);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('only_stop_words', $result->get_error_code());
    }

    /**
     * Test that error message for stop words is user-friendly.
     */
    public function test_stop_words_error_message_is_user_friendly()
    {
        $result = $this->validate('el la los');

        $this->assertInstanceOf(WP_Error::class, $result);
        $message = $result->get_error_message();

        // Should contain helpful guidance
        $this->assertStringContainsString('ignored', $message);
        $this->assertStringContainsString('more specific', $message);
    }

    /**
     * Test that error message for short words is user-friendly.
     */
    public function test_short_words_error_message_is_user_friendly()
    {
        $result = $this->validate('x y z');

        $this->assertInstanceOf(WP_Error::class, $result);
        $message = $result->get_error_message();

        // Should contain specific guidance
        $this->assertStringContainsString('at least 2 characters', $message);
    }

    /**
     * Test that error message for empty term is user-friendly.
     */
    public function test_empty_term_error_message_is_user_friendly()
    {
        $result = $this->validate('');

        $this->assertInstanceOf(WP_Error::class, $result);
        $message = $result->get_error_message();

        // Should prompt user to enter something
        $this->assertStringContainsString('Please enter', $message);
    }

    /**
     * Test that search with special characters still validates correctly.
     */
    public function test_search_with_special_characters_validates()
    {
        $result = $this->validate('disco-duro ssd');

        $this->assertTrue($result);
    }

    /**
     * Test that search with accents validates correctly.
     */
    public function test_search_with_accents_validates()
    {
        $result = $this->validate('camiseta algodón');

        $this->assertTrue($result);
    }

    /**
     * Test that mixed valid and invalid words passes (valid words remain).
     */
    public function test_mixed_valid_and_invalid_words_passes()
    {
        // 'a' and 'b' are too short, 'disco' is valid
        $result = $this->validate('a b disco');

        $this->assertTrue($result);
    }

    /**
     * Test that very long query with valid words passes.
     */
    public function test_long_query_with_valid_words_passes()
    {
        $result = $this->validate('uno dos tres cuatro cinco seis siete ocho');

        $this->assertTrue($result);
    }

    /**
     * Test that uppercase stop words are handled correctly.
     */
    public function test_uppercase_stop_words_handled()
    {
        $result = $this->validate('EL LA DE');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('only_stop_words', $result->get_error_code());
    }

    /**
     * Test that single stop word returns error.
     */
    public function test_single_stop_word_returns_error()
    {
        $result = $this->validate('el');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('only_stop_words', $result->get_error_code());
    }

    /**
     * Test that single short word returns error.
     */
    public function test_single_short_word_returns_error()
    {
        $result = $this->validate('a');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('only_short_words', $result->get_error_code());
    }
}
