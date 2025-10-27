<?php
/**
 * Plugin Name:       AI Content Master
 * Plugin URI:        https://wordpress.org/plugins/ai-content-master/
 * Description:       Professional AI-powered content generation plugin. Generate SEO-optimized content, meta descriptions, tags, and excerpts. Free tier includes 10 generations per month.
 * Version:           1.0.0
 * Author:            AI Content Master
 * Author URI:        https://wordpress.org/plugins/ai-content-master/
 * Text Domain:       ai-content-master
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Network:           false
 * Update URI:        https://wordpress.org/plugins/ai-content-master/
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'ACM_PLUGIN_FILE', __FILE__ );
define( 'ACM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ACM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ACM_VERSION', '1.0.0' );

// WordPress.org compliant constants
define( 'ACM_FREE_GENERATIONS', 10 );
define( 'ACM_UPGRADE_URL', 'https://your-domain.com/upgrade' ); // External upgrade service

// Include required files
require_once ACM_PLUGIN_DIR . 'includes/class-acm-core.php';
require_once ACM_PLUGIN_DIR . 'includes/class-acm-admin.php';
require_once ACM_PLUGIN_DIR . 'includes/class-acm-api.php';
require_once ACM_PLUGIN_DIR . 'includes/class-acm-bulk-processor.php';
require_once ACM_PLUGIN_DIR . 'includes/class-acm-gutenberg.php';

/**
 * Initialize the plugin
 */
function acm_init() {
    $core = new ACM_Core();
    $core->init();
}
add_action( 'plugins_loaded', 'acm_init' );

/**
 * Plugin activation hook
 */
function acm_activate() {
    ACM_Core::activate();
}
register_activation_hook( __FILE__, 'acm_activate' );

/**
 * Plugin deactivation hook
 */
function acm_deactivate() {
    ACM_Core::deactivate();
}
register_deactivation_hook( __FILE__, 'acm_deactivate' );
