<?php

/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete log table
global $wpdb;
$wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i", $wpdb->prefix . 'toret_manager_log'));

// Unschedule licence cron
$timestamp = wp_next_scheduled('wp_trman_initial_sync_cron');
$original_args = array();
wp_unschedule_event($timestamp, 'wp_trman_initial_sync_cron', $original_args);