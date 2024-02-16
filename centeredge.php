<?php
/*
Plugin Name: Centeredge
Description: Custom Centeredge Booking Scraping
Version: 1.0
Author: Alex Nguyen
License: GPLv3
*/

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * CenterEdge Booking Plugin
 *
 * This plugin handles CenterEdge booking functionality.
 */

// Set default timezone to America/Los_Angeles
date_default_timezone_set('America/Los_Angeles');

// Add custom recurrence intervals for cron schedules
function centeredge_booking_new_recurrence_interval($schedules)
{
    $schedules['sixhours'] = [
        'interval'  => 60 * 60 * 6,
        'display'   => __('Every 6 Hours', 'textdomain'),
    ];
    $schedules['fourhours'] = [
        'interval'  => 60 * 60 * 4,
        'display'   => __('Every 4 Hours', 'textdomain'),
    ];

    return $schedules;
}
add_filter('cron_schedules', 'centeredge_booking_new_recurrence_interval');

// Activate CenterEdge Book Cron
function centeredge_booking_new_activation()
{
    if (! wp_next_scheduled('centeredge_booking_new_cron')) {
        wp_schedule_event(time(), 'twicedaily', 'centeredge_booking_new_cron');
    }
}
register_activation_hook(__FILE__, 'centeredge_booking_new_activation');

// Deactivate CenterEdge Book Cron
function centeredge_booking_new_deactivation()
{
    wp_clear_scheduled_hook('centeredge_booking_new_cron');
}
register_deactivation_hook(__FILE__, 'centeredge_booking_new_deactivation');

// Handle CRON request
add_action('centeredge_booking_new_cron', 'centeredge_booking_new');

// Execute booking function
if (! function_exists('centeredge_booking_new')) {
    function centeredge_booking_new()
    {
        // Include CenterEdge class
        require_once dirname(__FILE__) . '/inc/class-centeredge.php';
        $ce = new CenterEdge();
    }
}

/**
 * Function to create the database table on plugin activation.
 */
function centeredge_booking_new_create_db()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name      = $wpdb->prefix . 'centeredge_booking';

    // Check if the table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id INT(11) NOT NULL AUTO_INCREMENT,
            type VARCHAR(25) NOT NULL,
            name VARCHAR(100) NOT NULL,
            posted VARCHAR(25) NOT NULL,
            link VARCHAR(200) NOT NULL,
            ticket VARCHAR(50) NOT NULL,
            outstock TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            INDEX name_key (name),
            INDEX out_stock (outstock)
        ) $charset_collate;";

        // Include WordPress upgrade functions
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}

// Register the function to be executed on plugin activation
register_activation_hook(__FILE__, 'centeredge_booking_new_create_db');

/**
 * Function to remove the database table on plugin deactivation.
 */
function centeredge_booking_new_remove_database()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'centeredge_booking';

    // Drop the table if it exists
    $sql = $wpdb->prepare('DROP TABLE IF EXISTS %s', $table_name);
    $wpdb->query($sql);
}

// Register the function to be executed on plugin deactivation
register_deactivation_hook(__FILE__, 'centeredge_booking_new_remove_database');
