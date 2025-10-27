<?php
/**
 * Plugin Name:       WP Gemini Content Generator
 * Plugin URI:        https://codecanyon.net/item/wp-gemini-content-generator/
 * Description:       Professional AI-powered content generation plugin using Google Gemini API. Generate SEO-optimized content, meta descriptions, tags, and excerpts with advanced bulk processing capabilities.
 * Version:           2.0.0
 * Author:            WP Gemini Content Generator
 * Author URI:        https://codecanyon.net/user/wp-gemini-content-generator
 * Text Domain:       wp-gemini-content-generator
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Network:           false
 * Update URI:        false
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'WGC_PLUGIN_FILE', __FILE__ );
define( 'WGC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WGC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WGC_VERSION', '2.0.0' );

// Include required files
require_once WGC_PLUGIN_DIR . 'includes/class-wgc-core.php';
require_once WGC_PLUGIN_DIR . 'includes/class-wgc-admin.php';
require_once WGC_PLUGIN_DIR . 'includes/class-wgc-api.php';
require_once WGC_PLUGIN_DIR . 'includes/class-wgc-bulk-processor.php';
require_once WGC_PLUGIN_DIR . 'includes/class-wgc-gutenberg.php';

/**
 * Initialize the plugin
 */
function wgc_init() {
    $core = new WGC_Core();
    $core->init();
}
add_action( 'plugins_loaded', 'wgc_init' );

/**
 * Plugin activation hook
 */
function wgc_activate() {
    WGC_Core::activate();
}
register_activation_hook( __FILE__, 'wgc_activate' );

/**
 * Plugin deactivation hook
 */
function wgc_deactivate() {
    WGC_Core::deactivate();
}
register_deactivation_hook( __FILE__, 'wgc_deactivate' );
