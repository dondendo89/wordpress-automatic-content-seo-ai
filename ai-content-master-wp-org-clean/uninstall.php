<?php
/**
 * Uninstall script for AI Content Master
 *
 * @package AI_Content_Master
 * @since 1.0.0
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove plugin options
$options_to_remove = array(
    'acm_gemini_api_key',
    'acm_language',
    'acm_post_types',
    'acm_content_length',
    'acm_excerpt_length',
    'acm_seo_focus',
    'acm_batch_size',
    'acm_version',
    'acm_free_generations_used',
    'acm_free_generations_reset_date',
    'acm_credits_balance',
    'acm_total_generations',
    'acm_stripe_secret_key',
    'acm_paypal_client_id'
);

foreach ( $options_to_remove as $option ) {
    delete_option( $option );
}

// Remove user meta
global $wpdb;
$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => 'acm_free_generations_used' ) );
$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => 'acm_free_generations_reset_date' ) );
$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => 'acm_credits_balance' ) );
$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => 'acm_total_generations' ) );

// Remove database tables
$table_name = $wpdb->prefix . 'acm_credits_transactions';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

$table_name = $wpdb->prefix . 'acm_bulk_jobs';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

// Remove scheduled hooks
wp_clear_scheduled_hook( 'acm_process_bulk_job' );
wp_clear_scheduled_hook( 'acm_reset_free_generations' );

// Remove post meta
$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_acm_generated' ) );
$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_yoast_wpseo_metadesc' ) );

// Log uninstall
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_log( 'AI Content Master: Plugin uninstalled and all data removed.' );
}
