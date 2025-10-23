<?php
/**
 * Cleanup on uninstall
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove plugin options
delete_option( 'wgc_gemini_api_key' );
delete_option( 'wgc_ai_studio_api_key' );
delete_option( 'wgc_language' );
delete_option( 'wgc_post_types' );
delete_option( 'wgc_append_mode' );
delete_option( 'wgc_emoji_icons' );

// Remove any stored bulk job state
global $wpdb;
$job_options = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'wgc_bulk_job_%'" );
if ( is_array( $job_options ) ) {
	foreach ( $job_options as $opt ) {
		delete_option( $opt );
	}
}

// Clear scheduled events for bulk processing
if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
	wp_clear_scheduled_hook( 'wgc_bulk_process_job' );
}



