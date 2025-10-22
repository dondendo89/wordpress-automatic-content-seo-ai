<?php
/**
 * Cleanup on uninstall
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
\texit;
}

delete_option( 'wgc_gemini_api_key' );
delete_option( 'wgc_language' );
delete_option( 'wgc_post_types' );
delete_option( 'wgc_append_mode' );
delete_option( 'wgc_emoji_icons' );



