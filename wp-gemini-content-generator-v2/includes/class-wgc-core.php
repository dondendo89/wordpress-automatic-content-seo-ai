<?php
/**
 * Core plugin class
 *
 * @package WP_Gemini_Content_Generator
 * @since 2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Core plugin functionality
 */
class WGC_Core {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain
        add_action( 'init', array( $this, 'load_textdomain' ) );
        
        // Initialize admin
        if ( is_admin() ) {
            new WGC_Admin();
        }
        
        // Initialize Gutenberg
        if ( function_exists( 'register_block_type' ) ) {
            new WGC_Gutenberg();
        }
        
        // Initialize API
        new WGC_API();
        
        // Initialize bulk processor
        new WGC_Bulk_Processor();
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wp-gemini-content-generator',
            false,
            dirname( plugin_basename( WGC_PLUGIN_FILE ) ) . '/languages'
        );
    }
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Set default options
        $default_options = array(
            'wgc_gemini_api_key' => '',
            'wgc_language' => 'en',
            'wgc_post_types' => array( 'post' ),
            'wgc_append_mode' => false,
            'wgc_emoji_icons' => true,
            'wgc_meta_description' => true,
            'wgc_generate_tags' => true,
            'wgc_generate_excerpt' => true,
            'wgc_excerpt_length' => 150,
            'wgc_batch_size' => 5,
            'wgc_content_length' => 1000,
            'wgc_seo_focus' => '',
            'wgc_version' => WGC_VERSION
        );
        
        foreach ( $default_options as $option => $value ) {
            if ( ! get_option( $option ) ) {
                add_option( $option, $value );
            }
        }
        
        // Create database tables if needed
        self::create_tables();
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Clear scheduled hooks
        wp_clear_scheduled_hook( 'wgc_process_bulk_job' );
    }
    
    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wgc_bulk_jobs';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            job_id varchar(255) NOT NULL,
            post_ids longtext NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            processed int(11) NOT NULL DEFAULT 0,
            total int(11) NOT NULL DEFAULT 0,
            errors longtext,
            options longtext,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY job_id (job_id)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    
    /**
     * Get plugin option
     */
    public static function get_option( $option, $default = false ) {
        return get_option( $option, $default );
    }
    
    /**
     * Update plugin option
     */
    public static function update_option( $option, $value ) {
        return update_option( $option, $value );
    }
    
    /**
     * Log error message
     */
    public static function log_error( $message ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'WGC Error: ' . $message );
        }
    }
    
    /**
     * Log debug message
     */
    public static function log_debug( $message ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            error_log( 'WGC Debug: ' . $message );
        }
    }
}
