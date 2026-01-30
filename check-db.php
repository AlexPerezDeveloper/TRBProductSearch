<?php
// Load WordPress
$wp_load = __DIR__ . '/../../../wp-load.php';
if (!file_exists($wp_load)) {
    $wp_load = __DIR__ . '/../../../../wp-load.php'; // try one more level
}

if (!file_exists($wp_load)) {
    die("Could not find wp-load.php");
}

require_once($wp_load);

global $wpdb;
$table_name = $wpdb->prefix . 'trb_search_logs';

echo "Database Check:\n";
echo "Table: $table_name\n";

$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
if (!$table_exists) {
    echo "ERROR: Table does not exist!\n";
    exit;
}

echo "Table exists.\n";

$count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
echo "Total rows: $count\n\n";

echo "Last 5 entries:\n";
$rows = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT 5");
foreach ($rows as $row) {
    echo "ID: {$row->id} | Term: {$row->search_term} | Count: {$row->results_count} | Has Results: {$row->has_results} | Created: {$row->created_at}\n";
}

echo "\nOptions:\n";
echo "trb_analytics_enabled: " . get_option('trb_analytics_enabled', 'MISSING') . "\n";
echo "trb_analytics_track_guests: " . get_option('trb_analytics_track_guests', 'MISSING') . "\n";

echo "\nWP Time:\n";
echo "current_time('mysql'): " . current_time('mysql') . "\n";
echo "PHP date('Y-m-d H:i:s'): " . date('Y-m-d H:i:s') . "\n";
