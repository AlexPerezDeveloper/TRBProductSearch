<?php
/**
 * Manual script to create analytics table
 * Run this once if you don't want to deactivate/reactivate the plugin
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');

if (!defined('ABSPATH')) {
    die('WordPress not loaded');
}

// Create the table
\TRB_Product_Search\Search_Analytics::create_table();

// Set default options if they don't exist
add_option('trb_analytics_enabled', true);
add_option('trb_analytics_retention_days', 90);
add_option('trb_analytics_track_guests', true);

echo "✅ Analytics table created successfully!\n";
echo "✅ Default options set.\n";
echo "\nYou can now use the Analytics dashboard.\n";
