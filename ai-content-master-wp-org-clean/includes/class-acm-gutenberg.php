<?php
/**
 * Gutenberg integration with freemium features
 *
 * @package AI_Content_Master
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle Gutenberg editor integration with freemium features
 */
class ACM_Gutenberg {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_assets' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }
    
    /**
     * Enqueue Gutenberg assets
     */
    public function enqueue_assets() {
        wp_enqueue_script(
            'acm-gutenberg',
            ACM_PLUGIN_URL . 'assets/js/gutenberg.js',
            array( 'wp-element', 'wp-components', 'wp-editor', 'wp-data', 'wp-api-fetch' ),
            ACM_VERSION,
            true
        );
        
        wp_enqueue_style(
            'acm-gutenberg',
            ACM_PLUGIN_URL . 'assets/css/gutenberg.css',
            array(),
            ACM_VERSION
        );
        
        wp_localize_script( 'acm-gutenberg', 'acmGutenberg', array(
            'restUrl' => rest_url( 'ai-content-master/v1/' ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
            'creditsInfo' => ( new ACM_Credits() )->get_credits_info(),
            'strings' => array(
                'no_credits' => __( 'No credits remaining. Please purchase more credits.', 'ai-content-master' ),
                'generating' => __( 'Generating...', 'ai-content-master' ),
                'success' => __( 'Generated successfully!', 'ai-content-master' ),
                'error' => __( 'Generation failed', 'ai-content-master' ),
            ),
        ) );
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route( 'ai-content-master/v1', '/generate', array(
            'methods' => 'POST',
            'callback' => array( $this, 'rest_generate_content' ),
            'permission_callback' => array( $this, 'rest_permission_check' ),
        ) );
        
        register_rest_route( 'ai-content-master/v1', '/credits', array(
            'methods' => 'GET',
            'callback' => array( $this, 'rest_get_credits' ),
            'permission_callback' => array( $this, 'rest_permission_check' ),
        ) );
    }
    
    /**
     * REST API permission check
     */
    public function rest_permission_check( $request ) {
        return current_user_can( 'edit_posts' );
    }
    
    /**
     * REST API: Generate content
     */
    public function rest_generate_content( $request ) {
        $params = $request->get_json_params();
        $post_id = intval( $params['postId'] ?? 0 );
        $type = sanitize_text_field( $params['type'] ?? '' );
        
        if ( ! $post_id ) {
            return new WP_Error( 'invalid_post_id', __( 'Invalid post ID', 'ai-content-master' ), array( 'status' => 400 ) );
        }
        
        $post = get_post( $post_id );
        if ( ! $post ) {
            return new WP_Error( 'post_not_found', __( 'Post not found', 'ai-content-master' ), array( 'status' => 404 ) );
        }
        
        // Check if user can generate content
        $can_generate = ACM_Core::can_generate_content();
        if ( ! $can_generate['can_generate'] ) {
            return new WP_Error( 'no_credits', __( 'No credits remaining. Please purchase more credits.', 'ai-content-master' ), array( 'status' => 402 ) );
        }
        
        // Consume generation
        if ( ! ACM_Core::consume_generation() ) {
            return new WP_Error( 'consume_failed', __( 'Failed to consume generation', 'ai-content-master' ), array( 'status' => 500 ) );
        }
        
        $api = new ACM_API();
        
        switch ( $type ) {
            case 'content':
                $prompt = $api->build_content_prompt( $post->post_title );
                $content = $api->generate_content( $prompt );
                
                if ( is_wp_error( $content ) ) {
                    return $content;
                }
                
                $sanitized_content = $api->sanitize_response( $content );
                
                wp_update_post( array(
                    'ID' => $post_id,
                    'post_content' => $sanitized_content,
                ) );
                
                return array(
                    'success' => true,
                    'content' => $sanitized_content,
                    'credits_info' => ( new ACM_Credits() )->get_credits_info(),
                );
                
            case 'meta':
                $prompt = $api->build_meta_prompt( $post->post_title, $post->post_content );
                $meta = $api->generate_content( $prompt );
                
                if ( is_wp_error( $meta ) ) {
                    return $meta;
                }
                
                $sanitized_meta = sanitize_text_field( $meta );
                update_post_meta( $post_id, '_yoast_wpseo_metadesc', $sanitized_meta );
                
                return array(
                    'success' => true,
                    'meta' => $sanitized_meta,
                    'credits_info' => ( new ACM_Credits() )->get_credits_info(),
                );
                
            case 'tags':
                $prompt = $api->build_tags_prompt( $post->post_title, $post->post_content );
                $tags_response = $api->generate_content( $prompt );
                
                if ( is_wp_error( $tags_response ) ) {
                    return $tags_response;
                }
                
                $tags = array_map( 'trim', explode( ',', $tags_response ) );
                $tags = array_filter( $tags );
                
                $taxonomy = ( $post->post_type === 'product' ) ? 'product_tag' : 'post_tag';
                wp_set_object_terms( $post_id, $tags, $taxonomy );
                
                return array(
                    'success' => true,
                    'tags' => $tags,
                    'credits_info' => ( new ACM_Credits() )->get_credits_info(),
                );
                
            case 'excerpt':
                $prompt = $api->build_excerpt_prompt( $post->post_title, $post->post_content );
                $excerpt = $api->generate_content( $prompt );
                
                if ( is_wp_error( $excerpt ) ) {
                    return $excerpt;
                }
                
                $sanitized_excerpt = sanitize_text_field( $excerpt );
                
                wp_update_post( array(
                    'ID' => $post_id,
                    'post_excerpt' => $sanitized_excerpt,
                ) );
                
                return array(
                    'success' => true,
                    'excerpt' => $sanitized_excerpt,
                    'credits_info' => ( new ACM_Credits() )->get_credits_info(),
                );
                
            default:
                return new WP_Error( 'invalid_type', __( 'Invalid generation type', 'ai-content-master' ), array( 'status' => 400 ) );
        }
    }
    
    /**
     * REST API: Get credits info
     */
    public function rest_get_credits( $request ) {
        $credits_info = ( new ACM_Credits() )->get_credits_info();
        
        return array(
            'success' => true,
            'credits_info' => $credits_info,
        );
    }
}
