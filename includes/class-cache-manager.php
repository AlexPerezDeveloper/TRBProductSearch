<?php
namespace TRB_Product_Search;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Cache_Manager
 *
 * Handles centralized cache management for the plugin.
 */
class Cache_Manager
{
    /**
     * Cache group name.
     */
    const CACHE_GROUP = 'trb_search';

    /**
     * Instance of the class.
     *
     * @var Cache_Manager
     */
    private static $instance = null;

    /**
     * Get the instance of the class.
     *
     * @return Cache_Manager
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
        // Invalidate cache when a product is saved or updated
        add_action('save_post_product', array($this, 'invalidate_cache'));
        add_action('woocommerce_product_import_inserted_product_object', array($this, 'invalidate_cache'));
    }

    /**
     * Get a value from the cache.
     *
     * @param string $key   Cache key.
     * @param string $group Cache group (optional).
     * @return mixed|false The cached value or false if not found.
     */
    public function get($key, $group = self::CACHE_GROUP)
    {
        return wp_cache_get($key, $group);
    }

    /**
     * Set a value in the cache.
     *
     * @param string $key        Cache key.
     * @param mixed  $value      Value to cache.
     * @param int    $expiration Expiration in seconds (0 for no expiration).
     * @param string $group      Cache group (optional).
     * @return bool True on success, false on failure.
     */
    public function set($key, $value, $expiration = 3600, $group = self::CACHE_GROUP)
    {
        return wp_cache_set($key, $value, $group, $expiration);
    }

    /**
     * Delete a value from the cache.
     *
     * @param string $key   Cache key.
     * @param string $group Cache group (optional).
     * @return bool True on success, false on failure.
     */
    public function delete($key, $group = self::CACHE_GROUP)
    {
        return wp_cache_delete($key, $group);
    }

    /**
     * Generate a cache key for a search query.
     *
     * @param string $term Search term.
     * @param array  $args Query arguments.
     * @return string Cache key.
     */
    public function get_search_key($term, $args = array())
    {
        $key_base = 'search_' . md5($term . serialize($args));
        // We can append a version here if we implement versioning
        $version = $this->get_cache_version();
        return $key_base . '_' . $version;
    }

    /**
     * Generate a cache key for an option or general data.
     *
     * @param string $identifier Identifier string.
     * @return string Cache key.
     */
    public function get_option_key($identifier)
    {
        $version = $this->get_cache_version();
        return 'opt_' . $identifier . '_' . $version;
    }

    /**
     * Invalidate the cache by incrementing the version.
     * Note: This effectively invalidates all keys generated with get_search_key or get_option_key.
     * since wp_cache_flush() clears EVERYTHING on the site in some setups (like Redis object cache),
     * versioning is safer for plugin-specific invalidation.
     */
    public function invalidate_cache()
    {
        // Increment the cache version
        $version = (int) get_option('trb_search_cache_version', 1);
        update_option('trb_search_cache_version', $version + 1);

        // Also try to flush the group if the object cache supports it (rare in WP but good practice)
        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group(self::CACHE_GROUP);
        }
    }

    /**
     * Get the current cache version.
     *
     * @return int Cache version.
     */
    private function get_cache_version()
    {
        // Cache the version in memory for the duration of the request
        static $version = null;
        if (null === $version) {
            $version = (int) get_option('trb_search_cache_version', 1);
        }
        return $version;
    }
}
