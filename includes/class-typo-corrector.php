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
    const OPTION_NAME = 'trb_search_word_index_v2';

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

        // Hook for the background action
        add_action('trb_rebuild_search_index', array($this, 'build_index'));
    }

    /**
     * Trigger index build asynchronously if possible.
     *
     * @param int|null $post_id Post ID (optional, passed by save_post hook).
     */
    public function background_build_index($post_id = null)
    {
        if (function_exists('as_schedule_single_action')) {
            // Check if there's already a pending action to avoid duplicates
            if (!as_next_scheduled_action('trb_rebuild_search_index')) {
                as_schedule_single_action(time() + 60, 'trb_rebuild_search_index', [], 'TRB_Search');
            }
        } else {
            $this->build_index(); // Fallback to synchronous execution
        }
    }

    /**
     * Build the word index from product titles, SKUs, and attributes.
     *
     * @return array The built index.
     */
    public function build_index()
    {
        global $wpdb;

        $words = array();
        $stop_words = $this->get_stop_words();

        // Step 1: Index product titles
        $titles = $wpdb->get_col("SELECT post_title FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'");

        if ($titles) {
            foreach ($titles as $title) {
                $this->tokenize_and_add($title, $words, $stop_words);
            }
        }

        // Step 2: Index SKUs
        $skus = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value != ''");

        if ($skus) {
            foreach ($skus as $sku) {
                // Add the full SKU as a word
                $clean_sku = mb_strtolower(trim($sku));
                if (strlen($clean_sku) > 2) {
                    $words[$clean_sku] = true;
                }
                // Tokenize parts of the SKU
                $this->tokenize_and_add($sku, $words, $stop_words);
            }
        }

        // Step 3: Index Attributes
        // Check if WooCommerce tables exist (safety check)
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_attribute_taxonomies'") === $wpdb->prefix . 'woocommerce_attribute_taxonomies') {
            $attribute_taxonomies = $wpdb->get_col("SELECT taxonomy FROM {$wpdb->term_taxonomy} WHERE taxonomy LIKE 'pa_%'");

            if ($attribute_taxonomies) {
                $placeholders = implode(',', array_fill(0, count($attribute_taxonomies), '%s'));
                $query = $wpdb->prepare(
                    "SELECT DISTINCT t.name
                    FROM {$wpdb->terms} t
                    INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                    WHERE tt.taxonomy IN ($placeholders)",
                    $attribute_taxonomies
                );

                $attribute_terms = $wpdb->get_col($query);

                if ($attribute_terms) {
                    foreach ($attribute_terms as $term) {
                        $this->tokenize_and_add($term, $words, $stop_words);
                    }
                }
            }
        }

        // Build the optimized structure
        $optimized_index = array();
        foreach (array_keys($words) as $word) {
            $len = mb_strlen($word);
            $first_char = mb_substr($word, 0, 1);

            if (!isset($optimized_index[$len])) {
                $optimized_index[$len] = array();
            }
            if (!isset($optimized_index[$len][$first_char])) {
                $optimized_index[$len][$first_char] = array();
            }

            $optimized_index[$len][$first_char][] = $word;
        }

        update_option(self::OPTION_NAME, $optimized_index, 'no');

        return $optimized_index;
    }

    /**
     * Helper to tokenize a string and add valid words to the list.
     *
     * @param string $text Text to tokenize.
     * @param array $words Reference to the words array.
     * @param array $stop_words Stop words list.
     */
    private function tokenize_and_add($text, &$words, $stop_words)
    {
        $clean_text = mb_strtolower($text);
        // Replace non-alphanumeric characters with spaces
        $clean_text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $clean_text);
        $tokens = explode(' ', $clean_text);

        foreach ($tokens as $token) {
            $token = trim($token);
            if (!empty($token) && mb_strlen($token) > 2 && !in_array($token, $stop_words)) {
                $words[$token] = true;
            }
        }
    }

    /**
     * Get list of stop words to ignore.
     *
     * @return array
     */
    private function get_stop_words()
    {
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
     * Attempt to correct a typo in the search term using the optimized index.
     *
     * @param string $term The search term.
     * @return string|false The corrected term or false.
     */
    public function correct($term)
    {
        $index = get_option(self::OPTION_NAME);

        // Build if missing
        if ($index === false) {
            $index = $this->build_index();
        }

        if (empty($index)) {
            return false;
        }

        $term = mb_strtolower(trim($term));
        $input_tokens = explode(' ', $term);

        // If it's a single word
        if (count($input_tokens) === 1) {
            return $this->find_best_match($term, $index);
        }

        // Multi-word phrase correction
        $corrected_tokens = array();
        $has_correction = false;

        foreach ($input_tokens as $token) {
            if (mb_strlen($token) < 3) {
                $corrected_tokens[] = $token;
                continue;
            }

            // check if word exists exactly (we need to flatten or search smart)
            // Ideally we check specific bucket
            if ($this->word_exists($token, $index)) {
                $corrected_tokens[] = $token;
                continue;
            }

            $match = $this->find_best_match($token, $index);
            if ($match) {
                $corrected_tokens[] = $match;
                $has_correction = true;
            } else {
                $corrected_tokens[] = $token;
            }
        }

        if ($has_correction) {
            return implode(' ', $corrected_tokens);
        }

        return false;
    }

    /**
     * Check if a word exists in the index.
     * 
     * @param string $word
     * @param array $index
     * @return bool
     */
    private function word_exists($word, $index)
    {
        $len = mb_strlen($word);
        $first_char = mb_substr($word, 0, 1);

        if (isset($index[$len][$first_char])) {
            return in_array($word, $index[$len][$first_char]);
        }
        return false;
    }

    /**
     * Find best match for a single word using optimized index.
     * 
     * @param string $word
     * @param array $index
     * @return string|false
     */
    private function find_best_match($word, $index)
    {
        $len = mb_strlen($word);
        $first_char = mb_substr($word, 0, 1);

        // Search mainly in same length, and length +/- 1
        $lengths_to_check = array($len, $len - 1, $len + 1);

        $candidates = array();

        foreach ($lengths_to_check as $l) {
            if (isset($index[$l])) {
                // If we have candidates with same first char, prioritize them? 
                // Currently we just gather all candidates from standard logic.
                // To be safe and fast, let's grab all words from these lengths.
                // Optimization: Filter by first letter? 
                // If I mistyped the first letter, filtering by it forces a miss.
                // But usually first letter is correct. 
                // Let's grab matching first letter buckets + maybe some adjacent keys if we want to be super thorough,
                // but for 80-90% speedup we should probably restrict search space significantly.
                // Proposal: check SAME first letter for all lengths.

                if (isset($index[$l][$first_char])) {
                    foreach ($index[$l][$first_char] as $candidate) {
                        $candidates[] = $candidate;
                    }
                }

                // Perhaps check other first letters if no candidates found? 
                // Or maybe checking ALL keys in these lengths is still O(m) where m << n
            }
        }

        // If strict first-char matching yields nothing or we want to be more robust:
        // For now, let's stick to the prompt implication: "Filtrar por primera letra para reducir espacio de búsqueda"

        if (empty($candidates)) {
            // Fallback: search all words in the length range if strict first-char failed?
            // Or just return false to keep it fast. 
            // Let's look at neighboring keys if empty.
            // But the prompt says "Filtrar por primera letra". I will trust that constraint for speed.
            return false;
        }

        $best_match = false;
        $shortest_distance = -1;

        $normalized_word = function_exists('remove_accents') ? remove_accents($word) : $word;

        foreach ($candidates as $candidate) {
            $normalized_candidate = function_exists('remove_accents') ? remove_accents($candidate) : $candidate;

            $distance = levenshtein($normalized_word, $normalized_candidate);

            if ($distance === 0) {
                return $candidate; // Exact match found (should be caught by word_exists but good safety)
            }

            if ($distance <= 2) { // strict threshold
                if ($shortest_distance < 0 || $distance < $shortest_distance) {
                    $shortest_distance = $distance;
                    $best_match = $candidate;
                }
            }
        }

        return $best_match;
    }
}
