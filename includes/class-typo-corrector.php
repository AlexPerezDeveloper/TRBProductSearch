<?php
namespace TRB_Product_Search;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Typo_Corrector
 *
 * Handles building a dictionary of words and providing suggestions for typos.
 */
class Typo_Corrector
{

    /**
     * Option name for the word index.
     */
    const OPTION_NAME = 'trb_search_word_index';

    /**
     * Instance of the class.
     *
     * @var Typo_Corrector
     */
    private static $instance = null;

    /**
     * Get the instance of the class.
     *
     * @return Typo_Corrector
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
        // Private constructor.
    }

    /**
     * Initialize hooks.
     */
    public function init()
    {
        // Rebuild index when a product is saved
        add_action('save_post_product', array($this, 'background_build_index'));

        // Add a setting to rebuild manually could be useful, but for now we rely on save_post or first run.
    }

    /**
     * Build the word index from product titles.
     * 
     * @return array The built index.
     */
    public function build_index()
    {
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1, // Warning on large catalogs
            'fields' => 'ids', // Performance optimization, we loop later to get titles? No, better get titles directly is cleaner but more memory. 
            // Actually 'fields' => 'ids' is safer for memory, then get_the_title(id).
        );

        // Better: Use direct DB query for speed on large catalogs to avoid WP object overhead
        global $wpdb;
        $titles = $wpdb->get_col("SELECT post_title FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'");

        $words = array();
        $stop_words = $this->get_stop_words();

        if ($titles) {
            foreach ($titles as $title) {
                // Tokenize: remove punctuation, lowercase, split by space
                $clean_title = mb_strtolower($title);
                $clean_title = preg_replace('/[^\p{L}\p{N}\s]/u', '', $clean_title); // Keep letters, numbers, spaces
                $tokens = explode(' ', $clean_title);

                foreach ($tokens as $token) {
                    $token = trim($token);
                    if (!empty($token) && strlen($token) > 2 && !in_array($token, $stop_words)) {
                        $words[$token] = true; // Use keys for uniqueness
                    }
                }
            }
        }

        $unique_words = array_keys($words);
        update_option(self::OPTION_NAME, $unique_words, 'no'); // 'no' autoload if large? Maybe user setting. For now default.

        return $unique_words;
    }

    /**
     * Trigger index build (could be improved to be async).
     */
    public function background_build_index()
    {
        // For V1 simple direct call. In future, use Action Scheduler.
        $this->build_index();
    }

    /**
     * Get list of stop words to ignore.
     *
     * @return array
     */
    private function get_stop_words()
    {
        // Basic list for Spanish/English. Could be extensible.
        return array(
            'de',
            'la',
            'el',
            'en',
            'y',
            'a',
            'los',
            'del',
            'las',
            'un',
            'una',
            'para',
            'por',
            'con',
            'no',
            'si',
            'su',
            'the',
            'and',
            'or',
            'of',
            'in',
            'to',
            'for',
            'with',
            'on',
            'at',
            'by',
            'from'
        );
    }

    /**
     * Attempt to correct a typo in the search term.
     *
     * @param string $term The search term.
     * @return string|false The corrected term or false if no good match.
     */
    public function correct($term)
    {
        $words = get_option(self::OPTION_NAME);

        // If index doesn't exist, try to build it once
        if ($words === false) {
            $words = $this->build_index();
        }

        if (empty($words)) {
            return false;
        }

        $term = mb_strtolower(trim($term));
        $best_match = false;
        $shortest_distance = -1;

        // Simple Levenshtein on each indexed word
        foreach ($words as $word) {
            $distance = levenshtein($term, $word);

            // Exact match (distance 0) means no typo, but maybe user searched a substring? 
            // If the term is present, search should have found it. 
            // We assume correct() is called when NO results found.

            if ($distance === 0) {
                return false; // It matches a real word, so not a typo (unless meaning is different, but out of scope)
            }

            if ($distance <= 2) { // Threshold for "leves" errors
                if ($shortest_distance < 0 || $distance < $shortest_distance) {
                    $shortest_distance = $distance;
                    $best_match = $word;
                }
            }
        }

        // Logic for multi-word phrases? 
        // V1: Only correcting single word typos or picking the best match for the whole string if it matches a single token?
        // Actually, if user types "zaptilla roja" and "zapatilla" is in index.
        // levenshtein("zaptilla roja", "zapatilla") is huge.
        // We should tokenize input too. 

        // Improved V1 Logic: Tokenize input, correct individual words.
        $input_tokens = explode(' ', $term);
        $corrected_tokens = array();
        $has_correction = false;

        foreach ($input_tokens as $token) {
            if (strlen($token) < 3) {
                $corrected_tokens[] = $token;
                continue;
            }

            // Check if token exists in dictionary
            if (in_array($token, $words)) {
                $corrected_tokens[] = $token;
                continue;
            }

            // Find best match for token
            $token_best_match = $token;
            $token_shortest_distance = -1;

            foreach ($words as $word) {
                $dist = levenshtein($token, $word);
                if ($dist <= 2) {
                    if ($token_shortest_distance < 0 || $dist < $token_shortest_distance) {
                        $token_shortest_distance = $dist;
                        $token_best_match = $word;
                    }
                }
            }

            if ($token_best_match !== $token) {
                $has_correction = true;
                $corrected_tokens[] = $token_best_match;
            } else {
                $corrected_tokens[] = $token;
            }
        }

        if ($has_correction) {
            return implode(' ', $corrected_tokens);
        }

        return false;
    }
}
