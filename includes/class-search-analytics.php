<?php
namespace TRB_Product_Search;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Search_Analytics
 *
 * Handles search analytics logging and reporting.
 */
class Search_Analytics
{
    /**
     * Table name for search logs.
     */
    const TABLE_NAME = 'trb_search_logs';

    /**
     * Instance of the class.
     *
     * @var Search_Analytics
     */
    private static $instance = null;

    /**
     * Get the instance of the class.
     *
     * @return Search_Analytics
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
        // Force check if table exists to prevent missing table issues
        self::create_table();

        // Schedule cleanup of old logs
        if (!wp_next_scheduled('trb_cleanup_search_logs')) {
            wp_schedule_event(time(), 'daily', 'trb_cleanup_search_logs');
        }
        add_action('trb_cleanup_search_logs', array($this, 'cleanup_old_logs'));
    }

    /**
     * Create the analytics table.
     */
    public static function create_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            search_term VARCHAR(191) NOT NULL,
            results_count INT UNSIGNED DEFAULT 0,
            has_results TINYINT(1) DEFAULT 1,
            clicked_product_id BIGINT UNSIGNED DEFAULT NULL,
            clicked_at DATETIME DEFAULT NULL,
            user_id BIGINT UNSIGNED DEFAULT NULL,
            session_id VARCHAR(191) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_search_term (search_term(100)),
            INDEX idx_created_at (created_at),
            INDEX idx_has_results (has_results)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Log a search query.
     *
     * @param string $term Search term.
     * @param int    $results_count Number of results.
     * @param bool   $has_results Whether search had results.
     */
    public function log_search($term, $results_count, $has_results)
    {
        error_log("TRB Search: FORCE logging search for '$term' (results: $results_count)");

        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $user_id = is_user_logged_in() ? get_current_user_id() : null;
        $session_id = $this->get_session_id();

        $wpdb->insert(
            $table_name,
            array(
                'search_term' => sanitize_text_field($term),
                'results_count' => absint($results_count),
                'has_results' => $has_results ? 1 : 0,
                'user_id' => $user_id,
                'session_id' => $session_id,
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%d', '%d', '%d', '%s', '%s')
        );

        if ($wpdb->last_error) {
            error_log('TRB Search Analytics DB Error: ' . $wpdb->last_error);
        } else {
            error_log("TRB Search Analytics: Logged search for '$term' successfully. ID: " . $wpdb->insert_id);
        }
    }

    /**
     * Get top searched terms.
     *
     * @param int $limit Number of results.
     * @param int $days  Days to look back.
     * @return array
     */
    public function get_top_searches($limit = 20, $days = 30)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $now = current_time('mysql');
        $date_threshold = date('Y-m-d H:i:s', strtotime("-$days days", strtotime($now)));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT search_term, COUNT(*) as search_count, 
                    AVG(results_count) as avg_results,
                    SUM(CASE WHEN has_results = 1 THEN 1 ELSE 0 END) as success_count
             FROM $table_name
             WHERE created_at >= %s
             GROUP BY search_term
             ORDER BY search_count DESC
             LIMIT %d",
            $date_threshold,
            $limit
        ));

        return $results;
    }

    /**
     * Get searches with no results.
     *
     * @param int $limit Number of results.
     * @param int $days  Days to look back.
     * @return array
     */
    public function get_no_results_searches($limit = 50, $days = 30)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $now = current_time('mysql');
        $date_threshold = date('Y-m-d H:i:s', strtotime("-$days days", strtotime($now)));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT search_term, COUNT(*) as search_count
             FROM $table_name
             WHERE created_at >= %s AND has_results = 0
             GROUP BY search_term
             ORDER BY search_count DESC
             LIMIT %d",
            $date_threshold,
            $limit
        ));

        return $results;
    }

    /**
     * Get general search statistics.
     *
     * @param int $days Days to look back.
     * @return array
     */
    public function get_search_stats($days = 30)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $now = current_time('mysql');
        $date_threshold = date('Y-m-d H:i:s', strtotime("-$days days", strtotime($now)));

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_searches,
                COUNT(DISTINCT search_term) as unique_terms,
                IFNULL(SUM(CASE WHEN has_results = 1 THEN 1 ELSE 0 END), 0) as searches_with_results,
                IFNULL(SUM(CASE WHEN has_results = 0 THEN 1 ELSE 0 END), 0) as searches_without_results,
                IFNULL(AVG(results_count), 0) as avg_results_per_search
             FROM $table_name
             WHERE created_at >= %s",
            $date_threshold
        ), ARRAY_A);

        // Ensure all values are numeric to avoid PHP 8.1+ deprecation warnings
        return array(
            'total_searches' => (int) ($stats['total_searches'] ?? 0),
            'unique_terms' => (int) ($stats['unique_terms'] ?? 0),
            'searches_with_results' => (int) ($stats['searches_with_results'] ?? 0),
            'searches_without_results' => (int) ($stats['searches_without_results'] ?? 0),
            'avg_results_per_search' => (float) ($stats['avg_results_per_search'] ?? 0),
        );
    }

    /**
     * Get daily search counts.
     *
     * @param int $days Days to look back.
     * @return array
     */
    public function get_daily_searches($days = 30)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $now = current_time('mysql');
        $date_threshold = date('Y-m-d H:i:s', strtotime("-$days days", strtotime($now)));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(created_at) as search_date,
                COUNT(*) as total_searches,
                SUM(CASE WHEN has_results = 1 THEN 1 ELSE 0 END) as with_results,
                SUM(CASE WHEN has_results = 0 THEN 1 ELSE 0 END) as without_results
             FROM $table_name
             WHERE created_at >= %s
             GROUP BY DATE(created_at)
             ORDER BY search_date ASC",
            $date_threshold
        ));

        return $results;
    }

    /**
     * Clean up old log entries.
     */
    public function cleanup_old_logs()
    {
        $retention_days = get_option('trb_analytics_retention_days', 90);

        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $date_threshold = date('Y-m-d H:i:s', strtotime("-$retention_days days"));

        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < %s",
            $date_threshold
        ));
    }

    /**
     * Get or create a session ID for anonymous users.
     *
     * @return string
     */
    public function get_session_id()
    {
        if (!isset($_COOKIE['trb_session_id'])) {
            $session_id = wp_generate_password(32, false);
            setcookie('trb_session_id', $session_id, time() + (86400 * 30), COOKIEPATH, COOKIE_DOMAIN);
            return $session_id;
        }
        return sanitize_text_field($_COOKIE['trb_session_id']);
    }

    /**
     * Export search data to CSV.
     *
     * @param int $days Days to export.
     * @return string CSV content.
     */
    public function export_to_csv($days = 30)
    {
        $top_searches = $this->get_top_searches(100, $days);

        $csv = "Search Term,Total Searches,Avg Results,Success Rate\n";

        foreach ($top_searches as $row) {
            $success_rate = $row->search_count > 0
                ? round(($row->success_count / $row->search_count) * 100, 2)
                : 0;

            $csv .= sprintf(
                '"%s",%d,%.2f,%.2f%%' . "\n",
                str_replace('"', '""', $row->search_term),
                $row->search_count,
                $row->avg_results,
                $success_rate
            );
        }

        return $csv;
    }
}
