<?php
/**
 * Uninstall script for WP Gemini Content Generator
 *
 * @package WP_Gemini_Content_Generator
 * @since 2.0.0
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove plugin options
$options_to_remove = array(
    'wgc_gemini_api_key',
    'wgc_language',
    'wgc_post_types',
    'wgc_append_mode',
    'wgc_emoji_icons',
    'wgc_meta_description',
    'wgc_generate_tags',
    'wgc_generate_excerpt',
    'wgc_excerpt_length',
    'wgc_batch_size',
    'wgc_content_length',
    'wgc_seo_focus',
    'wgc_version'
);

foreach ( $options_to_remove as $option ) {
    delete_option( $option );
}

// Remove database table
global $wpdb;
$table_name = $wpdb->prefix . 'wgc_bulk_jobs';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

// Remove scheduled hooks
wp_clear_scheduled_hook( 'wgc_process_bulk_job' );

// Remove post meta
$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_wgc_generated' ) );
$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_yoast_wpseo_metadesc' ) );

// Log uninstall
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_log( 'WP Gemini Content Generator: Plugin uninstalled and all data removed.' );
}
