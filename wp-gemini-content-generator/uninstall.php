<?php
/**
 * Uninstall script for WP Gemini Content Generator
 * 
 * This file is executed when the plugin is deleted through the WordPress admin.
 * It removes all plugin data including options, meta data, and scheduled events.
 * 
 * @package WP_Gemini_Content_Generator
 * @version 1.0.0
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Define plugin constants if not already defined
if ( ! defined( 'WGC_OPTION_API_KEY' ) ) {
    define( 'WGC_OPTION_API_KEY', 'wgc_gemini_api_key' );
    define( 'WGC_OPTION_LANGUAGE', 'wgc_language' );
    define( 'WGC_OPTION_POST_TYPES', 'wgc_post_types' );
    define( 'WGC_OPTION_APPEND_MODE', 'wgc_append_mode' );
    define( 'WGC_OPTION_EMOJI_ICONS', 'wgc_emoji_icons' );
    define( 'WGC_OPTION_META_DESCRIPTION', 'wgc_meta_description' );
    define( 'WGC_OPTION_GENERATE_TAGS', 'wgc_generate_tags' );
    define( 'WGC_OPTION_GENERATE_EXCERPT', 'wgc_generate_excerpt' );
    define( 'WGC_OPTION_EXCERPT_LENGTH', 'wgc_excerpt_length' );
    define( 'WGC_OPTION_BATCH_SIZE', 'wgc_batch_size' );
    define( 'WGC_OPTION_CONTENT_LENGTH', 'wgc_content_length' );
    define( 'WGC_OPTION_SEO_FOCUS', 'wgc_seo_focus' );
}

/**
 * Remove all plugin options
 */
function wgc_remove_plugin_options() {
    // Main plugin options
    delete_option( WGC_OPTION_API_KEY );
    delete_option( WGC_OPTION_LANGUAGE );
    delete_option( WGC_OPTION_POST_TYPES );
    delete_option( WGC_OPTION_APPEND_MODE );
    delete_option( WGC_OPTION_EMOJI_ICONS );
    delete_option( WGC_OPTION_META_DESCRIPTION );
    delete_option( WGC_OPTION_GENERATE_TAGS );
    delete_option( WGC_OPTION_GENERATE_EXCERPT );
    delete_option( WGC_OPTION_EXCERPT_LENGTH );
    delete_option( WGC_OPTION_BATCH_SIZE );
    delete_option( WGC_OPTION_CONTENT_LENGTH );
    delete_option( WGC_OPTION_SEO_FOCUS );
    
    // Settings group
    delete_option( 'wgc_settings' );
    
    // Version tracking
    delete_option( 'wgc_version' );
    delete_option( 'wgc_db_version' );
}

/**
 * Remove all post meta data created by the plugin
 */
function wgc_remove_post_meta() {
    global $wpdb;
    
    // Remove all meta keys created by the plugin
    $meta_keys = [
        '_wgc_generated',
        '_wgc_meta_description',
        '_wgc_content_length',
        '_wgc_generation_date',
        '_wgc_api_response',
        '_wgc_error_log'
    ];
    
    foreach ( $meta_keys as $meta_key ) {
        $wpdb->delete(
            $wpdb->postmeta,
            [ 'meta_key' => $meta_key ],
            [ '%s' ]
        );
    }
}

/**
 * Remove all bulk job data
 */
function wgc_remove_bulk_jobs() {
    global $wpdb;
    
    // Remove all bulk job options
    $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wgc_bulk_job_%'"
    );
    
    // Remove bulk job settings
    delete_option( 'wgc_bulk_job_settings' );
    delete_option( 'wgc_bulk_job_queue' );
    delete_option( 'wgc_bulk_job_stats' );
}

/**
 * Clear all scheduled events
 */
function wgc_clear_scheduled_events() {
    // Clear any scheduled cron events
    wp_clear_scheduled_hook( 'wgc_bulk_process_job' );
    wp_clear_scheduled_hook( 'wgc_cleanup_jobs' );
    wp_clear_scheduled_hook( 'wgc_daily_maintenance' );
    
    // Clear any custom scheduled events
    $timestamp = wp_next_scheduled( 'wgc_bulk_process_job' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'wgc_bulk_process_job' );
    }
}

/**
 * Remove plugin-specific user meta
 */
function wgc_remove_user_meta() {
    global $wpdb;
    
    // Remove any user preferences or settings
    $wpdb->delete(
        $wpdb->usermeta,
        [ 'meta_key' => 'wgc_user_preferences' ],
        [ '%s' ]
    );
    
    $wpdb->delete(
        $wpdb->usermeta,
        [ 'meta_key' => 'wgc_api_usage_stats' ],
        [ '%s' ]
    );
}

/**
 * Remove plugin-specific transients
 */
function wgc_remove_transients() {
    global $wpdb;
    
    // Remove all transients created by the plugin
    $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wgc_%' OR option_name LIKE '_transient_timeout_wgc_%'"
    );
}

/**
 * Remove plugin-specific database tables (if any were created)
 */
function wgc_remove_database_tables() {
    global $wpdb;
    
    // Remove any custom tables created by the plugin
    $tables = [
        $wpdb->prefix . 'wgc_generation_log',
        $wpdb->prefix . 'wgc_api_usage',
        $wpdb->prefix . 'wgc_bulk_jobs'
    ];
    
    foreach ( $tables as $table ) {
        $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
    }
}

/**
 * Remove plugin-specific files (if any were created)
 */
function wgc_remove_plugin_files() {
    $upload_dir = wp_upload_dir();
    $plugin_dir = $upload_dir['basedir'] . '/wp-gemini-content-generator/';
    
    if ( is_dir( $plugin_dir ) ) {
        // Remove the entire plugin directory from uploads
        wgc_recursive_rmdir( $plugin_dir );
    }
}

/**
 * Recursively remove directory and all its contents
 */
function wgc_recursive_rmdir( $dir ) {
    if ( ! is_dir( $dir ) ) {
        return false;
    }
    
    $files = array_diff( scandir( $dir ), [ '.', '..' ] );
    
    foreach ( $files as $file ) {
        $path = $dir . '/' . $file;
        is_dir( $path ) ? wgc_recursive_rmdir( $path ) : unlink( $path );
    }
    
    return rmdir( $dir );
}

/**
 * Log uninstall action for debugging purposes
 */
function wgc_log_uninstall() {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( 'WP Gemini Content Generator: Plugin uninstalled and all data removed.' );
    }
}

/**
 * Main uninstall process
 */
function wgc_uninstall_plugin() {
    // Remove all plugin data
    wgc_remove_plugin_options();
    wgc_remove_post_meta();
    wgc_remove_bulk_jobs();
    wgc_clear_scheduled_events();
    wgc_remove_user_meta();
    wgc_remove_transients();
    wgc_remove_database_tables();
    wgc_remove_plugin_files();
    
    // Log the uninstall action
    wgc_log_uninstall();
    
    // Clear any object cache
    if ( function_exists( 'wp_cache_flush' ) ) {
        wp_cache_flush();
    }
}

// Execute the uninstall process
wgc_uninstall_plugin();

// Final cleanup message
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_log( 'WP Gemini Content Generator: Uninstall completed successfully.' );
}