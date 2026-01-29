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
     * Build the word index from product titles, SKUs, and attributes.
     *
     * @return array The built index.
     */
    public function build_index()
    {
        global $wpdb;

        // Check if WooCommerce is active
        if (!function_exists('wc_get_products')) {
            // Fallback to title-only indexing if WooCommerce is not available
            return $this->build_title_index($wpdb);
        }

        $words = array();
        $stop_words = $this->get_stop_words();

        // Step 1: Index product titles (using direct DB query for performance)
        $titles = $wpdb->get_col("SELECT post_title FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'");

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

        // Step 2: Index product SKUs
        $skus = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value != ''");

        if ($skus) {
            foreach ($skus as $sku) {
                $clean_sku = mb_strtolower(trim($sku));
                $clean_sku = preg_replace('/[^\p{L}\p{N}\s]/u', '', $clean_sku);

                // Tokenize SKU in case it contains multiple parts (e.g., "PROD-123-BLACK")
                $tokens = explode(' ', preg_replace('/[^\p{L}\p{N}]/u', ' ', $clean_sku));

                foreach ($tokens as $token) {
                    $token = trim($token);
                    if (!empty($token) && strlen($token) > 2 && !in_array($token, $stop_words)) {
                        $words[$token] = true;
                    }
                }

                // Also add the complete SKU as a single token if it's meaningful
                if (!empty($clean_sku) && strlen($clean_sku) > 2) {
                    $words[$clean_sku] = true;
                }
            }
        }

        // Step 3: Index product attribute terms
        // Get all product attribute taxonomies
        $attribute_taxonomies = $wpdb->get_col(
            "SELECT DISTINCT taxonomy
            FROM {$wpdb->term_taxonomy}
            WHERE taxonomy LIKE 'pa_%'"
        );

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
                    $clean_term = mb_strtolower(trim($term));
                    $clean_term = preg_replace('/[^\p{L}\p{N}\s]/u', '', $clean_term);
                    $tokens = explode(' ', $clean_term);

                    foreach ($tokens as $token) {
                        $token = trim($token);
                        if (!empty($token) && strlen($token) > 2 && !in_array($token, $stop_words)) {
                            $words[$token] = true;
                        }
                    }
                }
            }
        }

        $unique_words = array_keys($words);
        update_option(self::OPTION_NAME, $unique_words, 'no'); // 'no' autoload if large? Maybe user setting. For now default.

        return $unique_words;
    }

    /**
     * Build title-only index (fallback when WooCommerce is not active).
     *
     * @param wpdb $wpdb Database object.
     * @return array The built index.
     */
    private function build_title_index($wpdb)
    {
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
        update_option(self::OPTION_NAME, $unique_words, 'no');

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

            // Only consider matches that are reasonably close AND
            // prefer words that start with the same letter for better suggestions
            $distance_penalty = 0;
            if (mb_substr($term, 0, 1) !== mb_substr($word, 0, 1)) {
                $distance_penalty = 2; // Penalize words starting with different letter
            }

            $effective_distance = $distance + $distance_penalty;

            if ($effective_distance <= 3) { // Slightly higher threshold with penalty
                if ($shortest_distance < 0 || $effective_distance < $shortest_distance) {
                    $shortest_distance = $effective_distance;
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
            if (strlen($token) < 4) {
                // Don't correct very short tokens
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

                // Apply penalty for different starting letter
                $dist_penalty = 0;
                if (mb_substr($token, 0, 1) !== mb_substr($word, 0, 1)) {
                    $dist_penalty = 2;
                }

                $effective_dist = $dist + $dist_penalty;

                if ($effective_dist <= 3) {
                    if ($token_shortest_distance < 0 || $effective_dist < $token_shortest_distance) {
                        $token_shortest_distance = $effective_dist;
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

        return $best_match;
    }
}
