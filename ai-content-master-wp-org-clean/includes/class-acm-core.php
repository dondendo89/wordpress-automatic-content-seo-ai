<?php
/**
 * WordPress.org Compliant Core Plugin Class
 *
 * @package AI_Content_Master
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Core plugin functionality - WordPress.org compliant
 */
class ACM_Core {
    
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
            new ACM_Admin();
        }
        
        // Initialize Gutenberg
        if ( function_exists( 'register_block_type' ) ) {
            new ACM_Gutenberg();
        }
        
        // Initialize API
        new ACM_API();
        
        // Initialize bulk processor
        new ACM_Bulk_Processor();
        
        // Add admin notices
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'ai-content-master',
            false,
            dirname( plugin_basename( ACM_PLUGIN_FILE ) ) . '/languages'
        );
    }
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Set default options
        $default_options = array(
            'acm_gemini_api_key' => '',
            'acm_language' => 'en',
            'acm_post_types' => array( 'post' ),
            'acm_content_length' => 1000,
            'acm_excerpt_length' => 150,
            'acm_seo_focus' => '',
            'acm_batch_size' => 5,
            'acm_version' => ACM_VERSION,
            'acm_upgrade_url' => 'https://your-domain.com/upgrade', // External upgrade service
        );
        
        foreach ( $default_options as $option => $value ) {
            if ( ! get_option( $option ) ) {
                add_option( $option, $value );
            }
        }
        
        // Schedule monthly reset for free generations
        if ( ! wp_next_scheduled( 'acm_reset_free_generations' ) ) {
            wp_schedule_event( time(), 'monthly', 'acm_reset_free_generations' );
        }
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Clear scheduled hooks
        wp_clear_scheduled_hook( 'acm_reset_free_generations' );
    }
    
    /**
     * Check if user can generate content (WordPress.org compliant)
     */
    public static function can_generate_content( $user_id = null ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
        
        // Check free generations
        $free_used = get_user_meta( $user_id, 'acm_free_generations_used', true );
        $free_reset_date = get_user_meta( $user_id, 'acm_free_generations_reset_date', true );
        
        // Reset free generations monthly
        if ( ! $free_reset_date || $free_reset_date < date( 'Y-m-01' ) ) {
            update_user_meta( $user_id, 'acm_free_generations_used', 0 );
            update_user_meta( $user_id, 'acm_free_generations_reset_date', date( 'Y-m-01' ) );
            $free_used = 0;
        }
        
        // Check if user has free generations left
        if ( $free_used < ACM_FREE_GENERATIONS ) {
            return array(
                'can_generate' => true,
                'type' => 'free',
                'remaining' => ACM_FREE_GENERATIONS - $free_used,
            );
        }
        
        return array(
            'can_generate' => false,
            'type' => 'none',
            'remaining' => 0,
        );
    }
    
    /**
     * Consume generation (free only - WordPress.org compliant)
     */
    public static function consume_generation( $user_id = null ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
        
        $can_generate = self::can_generate_content( $user_id );
        
        if ( ! $can_generate['can_generate'] ) {
            return false;
        }
        
        // Consume free generation
        $free_used = get_user_meta( $user_id, 'acm_free_generations_used', true );
        update_user_meta( $user_id, 'acm_free_generations_used', $free_used + 1 );
        
        return true;
    }
    
    /**
     * Get upgrade message for external service
     */
    public static function get_upgrade_message() {
        $upgrade_url = self::get_option( 'acm_upgrade_url', 'https://your-domain.com/upgrade' );
        
        return array(
            'message' => __( 'You have used all 10 free generations this month. Upgrade to our premium service for unlimited generations.', 'ai-content-master' ),
            'upgrade_url' => $upgrade_url,
            'upgrade_text' => __( 'Upgrade Now', 'ai-content-master' ),
        );
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
     * Admin notices
     */
    public function admin_notices() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        $api_key = self::get_option( 'acm_gemini_api_key' );
        if ( empty( $api_key ) ) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>AI Content Master:</strong> ' . __( 'Please configure your Gemini API key in the plugin settings.', 'ai-content-master' ) . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Log error message
     */
    public static function log_error( $message ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'ACM Error: ' . $message );
        }
    }
    
    /**
     * Log debug message
     */
    public static function log_debug( $message ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            error_log( 'ACM Debug: ' . $message );
        }
    }
}

/**
 * Reset free generations monthly
 */
function acm_reset_free_generations() {
    global $wpdb;
    
    $wpdb->update(
        $wpdb->usermeta,
        array( 'meta_value' => 0 ),
        array( 'meta_key' => 'acm_free_generations_used' )
    );
    
    $wpdb->update(
        $wpdb->usermeta,
        array( 'meta_value' => date( 'Y-m-01' ) ),
        array( 'meta_key' => 'acm_free_generations_reset_date' )
    );
}
add_action( 'acm_reset_free_generations', 'acm_reset_free_generations' );
