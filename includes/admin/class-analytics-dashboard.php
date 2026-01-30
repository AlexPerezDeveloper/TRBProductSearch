<?php
namespace TRB_Product_Search\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Analytics_Dashboard
 *
 * Handles the analytics dashboard in WordPress admin.
 */
class Analytics_Dashboard
{
    /**
     * Instance of the class.
     *
     * @var Analytics_Dashboard
     */
    private static $instance = null;

    /**
     * Get the instance of the class.
     *
     * @return Analytics_Dashboard
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
        // No need for separate menu, we'll use tabs in settings
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_post_trb_export_analytics', array($this, 'handle_export'));
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets($hook)
    {
        // Only load on TRB Search settings page
        if ('settings_page_trb-product-search' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'trb-analytics-dashboard',
            TRB_PRODUCT_SEARCH_URL . 'assets/css/admin-analytics.css',
            array(),
            TRB_PRODUCT_SEARCH_VERSION
        );

        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            array(),
            '4.4.0',
            true
        );

        wp_enqueue_script(
            'trb-analytics-dashboard',
            TRB_PRODUCT_SEARCH_URL . 'assets/js/admin-analytics.js',
            array('jquery', 'chart-js'),
            TRB_PRODUCT_SEARCH_VERSION,
            true
        );
    }

    /**
     * Render the analytics dashboard.
     */
    public function render_dashboard()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $analytics = \TRB_Product_Search\Search_Analytics::get_instance();

        // Get selected time period
        $days = isset($_GET['days']) ? absint($_GET['days']) : 30;
        $days = in_array($days, array(7, 30, 90)) ? $days : 30;

        // Get statistics
        $stats = $analytics->get_search_stats($days);
        $top_searches = $analytics->get_top_searches(20, $days);
        $no_results = $analytics->get_no_results_searches(20, $days);
        $daily_data = $analytics->get_daily_searches($days);

        ?>
        <!-- Time Period Selector -->
        <div class="trb-period-selector">
            <a href="?page=trb-product-search&tab=analytics&days=7" class="<?php echo $days === 7 ? 'active' : ''; ?>">
                <?php esc_html_e('Last 7 Days', 'trb-product-search'); ?>
            </a>
            <a href="?page=trb-product-search&tab=analytics&days=30" class="<?php echo $days === 30 ? 'active' : ''; ?>">
                <?php esc_html_e('Last 30 Days', 'trb-product-search'); ?>
            </a>
            <a href="?page=trb-product-search&tab=analytics&days=90" class="<?php echo $days === 90 ? 'active' : ''; ?>">
                <?php esc_html_e('Last 90 Days', 'trb-product-search'); ?>
            </a>
        </div>

        <!-- Key Metrics -->
        <div class="trb-metrics-grid">
            <div class="trb-metric-card">
                <h3>
                    <?php esc_html_e('Total Searches', 'trb-product-search'); ?>
                </h3>
                <p class="trb-metric-value">
                    <?php echo number_format_i18n($stats['total_searches']); ?>
                </p>
            </div>
            <div class="trb-metric-card">
                <h3>
                    <?php esc_html_e('Unique Terms', 'trb-product-search'); ?>
                </h3>
                <p class="trb-metric-value">
                    <?php echo number_format_i18n($stats['unique_terms']); ?>
                </p>
            </div>
            <div class="trb-metric-card">
                <h3>
                    <?php esc_html_e('Success Rate', 'trb-product-search'); ?>
                </h3>
                <p class="trb-metric-value">
                    <?php
                    $success_rate = $stats['total_searches'] > 0
                        ? ($stats['searches_with_results'] / $stats['total_searches']) * 100
                        : 0;
                    echo number_format_i18n($success_rate, 1) . '%';
                    ?>
                </p>
            </div>
            <div class="trb-metric-card">
                <h3>
                    <?php esc_html_e('Avg Results', 'trb-product-search'); ?>
                </h3>
                <p class="trb-metric-value">
                    <?php echo number_format_i18n($stats['avg_results_per_search'], 1); ?>
                </p>
            </div>
        </div>

        <!-- Charts -->
        <div class="trb-charts-grid">
            <div class="trb-chart-container">
                <h2>
                    <?php esc_html_e('Daily Searches', 'trb-product-search'); ?>
                </h2>
                <canvas id="trb-daily-chart"></canvas>
            </div>
            <div class="trb-chart-container">
                <h2>
                    <?php esc_html_e('Results Distribution', 'trb-product-search'); ?>
                </h2>
                <canvas id="trb-results-chart"></canvas>
            </div>
        </div>

        <!-- Top Searches Table -->
        <div class="trb-table-container">
            <h2>
                <?php esc_html_e('Top Searched Terms', 'trb-product-search'); ?>
            </h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>
                            <?php esc_html_e('Search Term', 'trb-product-search'); ?>
                        </th>
                        <th>
                            <?php esc_html_e('Searches', 'trb-product-search'); ?>
                        </th>
                        <th>
                            <?php esc_html_e('Avg Results', 'trb-product-search'); ?>
                        </th>
                        <th>
                            <?php esc_html_e('Success Rate', 'trb-product-search'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_searches)): ?>
                        <?php foreach ($top_searches as $search): ?>
                            <tr>
                                <td><strong>
                                        <?php echo esc_html($search->search_term); ?>
                                    </strong></td>
                                <td>
                                    <?php echo number_format_i18n($search->search_count); ?>
                                </td>
                                <td>
                                    <?php echo number_format_i18n($search->avg_results, 1); ?>
                                </td>
                                <td>
                                    <?php
                                    $rate = ($search->success_count / $search->search_count) * 100;
                                    echo number_format_i18n($rate, 1) . '%';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">
                                <?php esc_html_e('No data available', 'trb-product-search'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- No Results Table -->
        <div class="trb-table-container trb-no-results-table">
            <h2>
                <?php esc_html_e('Searches Without Results (Opportunities)', 'trb-product-search'); ?>
            </h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>
                            <?php esc_html_e('Search Term', 'trb-product-search'); ?>
                        </th>
                        <th>
                            <?php esc_html_e('Frequency', 'trb-product-search'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($no_results)): ?>
                        <?php foreach ($no_results as $search): ?>
                            <tr>
                                <td><strong>
                                        <?php echo esc_html($search->search_term); ?>
                                    </strong></td>
                                <td>
                                    <?php echo number_format_i18n($search->search_count); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2">
                                <?php esc_html_e('No searches without results', 'trb-product-search'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Export Button -->
        <div class="trb-export-section">
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="trb_export_analytics">
                <input type="hidden" name="days" value="<?php echo esc_attr($days); ?>">
                <?php wp_nonce_field('trb_export_analytics', 'trb_export_nonce'); ?>
                <button type="submit" class="button button-primary">
                    <?php esc_html_e('Export to CSV', 'trb-product-search'); ?>
                </button>
            </form>
        </div>

        <!-- Chart Data (for JS) -->
        <script type="text/javascript">
            var trbDailyData = <?php echo json_encode($daily_data); ?>;
            var trbStatsData = {
                with_results: <?php echo intval($stats['searches_with_results']); ?>,
                without_results: <?php echo intval($stats['searches_without_results']); ?>
            };
        </script>
        <?php
    }

    /**
     * Handle CSV export.
     */
    public function handle_export()
    {
        check_admin_referer('trb_export_analytics', 'trb_export_nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions.'));
        }

        $days = isset($_POST['days']) ? absint($_POST['days']) : 30;
        $analytics = \TRB_Product_Search\Search_Analytics::get_instance();
        $csv = $analytics->export_to_csv($days);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=trb-search-analytics-' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $csv;
        exit;
    }
}
