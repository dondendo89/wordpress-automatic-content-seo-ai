<?php
/**
 * Bulk processor functionality
 *
 * @package WP_Gemini_Content_Generator
 * @since 2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle bulk content generation
 */
class WGC_Bulk_Processor {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_bulk_menu' ) );
        add_action( 'wp_ajax_wgc_bulk_generate', array( $this, 'ajax_bulk_generate' ) );
        add_action( 'wp_ajax_wgc_bulk_status', array( $this, 'ajax_bulk_status' ) );
        add_action( 'wgc_process_bulk_job', array( $this, 'process_bulk_job' ) );
    }
    
    /**
     * Add bulk menu
     */
    public function add_bulk_menu() {
        add_submenu_page(
            'wp-gemini-content-generator',
            __( 'Bulk Generation', 'wp-gemini-content-generator' ),
            __( 'Bulk Generation', 'wp-gemini-content-generator' ),
            'manage_options',
            'wgc-bulk-generation',
            array( $this, 'bulk_page' )
        );
    }
    
    /**
     * Bulk generation page
     */
    public function bulk_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Bulk Content Generation', 'wp-gemini-content-generator' ); ?></h1>
            
            <div class="wgc-bulk-generator">
                <form id="wgc-bulk-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="wgc-post-types"><?php _e( 'Post Types', 'wp-gemini-content-generator' ); ?></label>
                            </th>
                            <td>
                                <?php
                                $post_types = get_post_types( array( 'public' => true ), 'objects' );
                                $selected_types = WGC_Core::get_option( 'wgc_post_types', array( 'post' ) );
                                
                                foreach ( $post_types as $type ) {
                                    $checked = in_array( $type->name, $selected_types ) ? 'checked' : '';
                                    printf(
                                        '<label><input type="checkbox" name="postTypes[]" value="%s" %s> %s</label><br>',
                                        esc_attr( $type->name ),
                                        $checked,
                                        esc_html( $type->label )
                                    );
                                }
                                ?>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <?php _e( 'Generation Options', 'wp-gemini-content-generator' ); ?>
                            </th>
                            <td>
                                <label><input type="checkbox" name="generateContent" value="1" checked> <?php _e( 'Generate Content', 'wp-gemini-content-generator' ); ?></label><br>
                                <label><input type="checkbox" name="generateMeta" value="1" checked> <?php _e( 'Generate Meta Descriptions', 'wp-gemini-content-generator' ); ?></label><br>
                                <label><input type="checkbox" name="generateTags" value="1" checked> <?php _e( 'Generate Tags', 'wp-gemini-content-generator' ); ?></label><br>
                                <label><input type="checkbox" name="generateExcerpt" value="1" checked> <?php _e( 'Generate Excerpts', 'wp-gemini-content-generator' ); ?></label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="wgc-batch-size"><?php _e( 'Batch Size', 'wp-gemini-content-generator' ); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="wgc-batch-size" 
                                       name="batchSize" 
                                       value="<?php echo esc_attr( WGC_Core::get_option( 'wgc_batch_size', 5 ) ); ?>" 
                                       min="1" 
                                       max="20" 
                                       class="wgc-input">
                                <p class="description">
                                    <?php _e( 'Number of posts to process in each batch.', 'wp-gemini-content-generator' ); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="wgc-force-regenerate"><?php _e( 'Force Regenerate', 'wp-gemini-content-generator' ); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="wgc-force-regenerate" 
                                           name="forceRegenerate" 
                                           value="1">
                                    <?php _e( 'Regenerate content even if it already exists', 'wp-gemini-content-generator' ); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" 
                                id="wgc-bulk-generate" 
                                class="button button-primary button-large" 
                                data-nonce="<?php echo esc_attr( wp_create_nonce( 'wgc_bulk_generate' ) ); ?>">
                            <?php _e( 'Start Bulk Generation', 'wp-gemini-content-generator' ); ?>
                        </button>
                    </p>
                </form>
                
                <div id="wgc-bulk-status" style="margin-top: 20px;"></div>
                <div id="wgc-bulk-progress" style="margin-top: 20px;"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Start bulk generation
     */
    public function ajax_bulk_generate() {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'wgc_bulk_generate' ) ) {
            wp_die( __( 'Security check failed', 'wp-gemini-content-generator' ) );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Insufficient permissions', 'wp-gemini-content-generator' ) );
        }
        
        $post_types = array_map( 'sanitize_text_field', $_POST['postTypes'] ?? [] );
        $generate_content = ! empty( $_POST['generateContent'] );
        $generate_meta = ! empty( $_POST['generateMeta'] );
        $generate_tags = ! empty( $_POST['generateTags'] );
        $generate_excerpt = ! empty( $_POST['generateExcerpt'] );
        $batch_size = intval( $_POST['batchSize'] ?? 5 );
        $force_regenerate = ! empty( $_POST['forceRegenerate'] );
        
        if ( empty( $post_types ) ) {
            wp_send_json_error( array( 'message' => __( 'Please select at least one post type', 'wp-gemini-content-generator' ) ) );
        }
        
        // Get posts to process
        $args = array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        );
        
        if ( ! $force_regenerate ) {
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => '_wgc_generated',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => '_wgc_generated',
                    'value' => '',
                    'compare' => '=',
                ),
            );
        }
        
        $posts = get_posts( $args );
        
        if ( empty( $posts ) ) {
            wp_send_json_error( array( 'message' => __( 'No posts found to process', 'wp-gemini-content-generator' ) ) );
        }
        
        // Create job
        $job_id = 'wgc_bulk_' . time();
        $job_data = array(
            'job_id' => $job_id,
            'post_ids' => $posts,
            'status' => 'pending',
            'processed' => 0,
            'total' => count( $posts ),
            'errors' => array(),
            'options' => array(
                'generate_content' => $generate_content,
                'generate_meta' => $generate_meta,
                'generate_tags' => $generate_tags,
                'generate_excerpt' => $generate_excerpt,
                'batch_size' => $batch_size,
                'force_regenerate' => $force_regenerate,
            ),
        );
        
        $this->save_job( $job_data );
        
        // Schedule job processing
        wp_schedule_single_event( time(), 'wgc_process_bulk_job', array( $job_id ) );
        
        // Start processing immediately
        $this->process_bulk_job( $job_id );
        
        wp_send_json_success( array(
            'message' => __( 'Bulk generation started', 'wp-gemini-content-generator' ),
            'job_id' => $job_id,
            'total_posts' => count( $posts ),
        ) );
    }
    
    /**
     * AJAX: Get bulk status
     */
    public function ajax_bulk_status() {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'wgc_bulk_generate' ) ) {
            wp_die( __( 'Security check failed', 'wp-gemini-content-generator' ) );
        }
        
        $job_id = sanitize_text_field( $_POST['jobId'] ?? '' );
        if ( empty( $job_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Job ID required', 'wp-gemini-content-generator' ) ) );
        }
        
        $job_data = $this->get_job( $job_id );
        if ( ! $job_data ) {
            wp_send_json_error( array( 'message' => __( 'Job not found', 'wp-gemini-content-generator' ) ) );
        }
        
        wp_send_json_success( $job_data );
    }
    
    /**
     * Process bulk job
     */
    public function process_bulk_job( $job_id ) {
        $job_data = $this->get_job( $job_id );
        if ( ! $job_data ) {
            return;
        }
        
        if ( $job_data['status'] === 'completed' ) {
            return;
        }
        
        $options = $job_data['options'];
        $batch_size = $options['batch_size'];
        $processed = $job_data['processed'];
        $remaining_posts = array_slice( $job_data['post_ids'], $processed, $batch_size );
        
        if ( empty( $remaining_posts ) ) {
            $job_data['status'] = 'completed';
            $this->save_job( $job_data );
            return;
        }
        
        $job_data['status'] = 'processing';
        $this->save_job( $job_data );
        
        foreach ( $remaining_posts as $post_id ) {
            $this->process_single_post( $post_id, $options );
            $job_data['processed']++;
        }
        
        if ( $job_data['processed'] >= $job_data['total'] ) {
            $job_data['status'] = 'completed';
        } else {
            $job_data['status'] = 'pending';
            // Schedule next batch
            wp_schedule_single_event( time() + 30, 'wgc_process_bulk_job', array( $job_id ) );
        }
        
        $this->save_job( $job_data );
    }
    
    /**
     * Process single post
     */
    private function process_single_post( $post_id, $options ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return;
        }
        
        $api = new WGC_API();
        
        try {
            // Generate content
            if ( $options['generate_content'] ) {
                $content_prompt = $api->build_content_prompt( $post->post_title );
                $content = $api->generate_content( $content_prompt );
                
                if ( ! is_wp_error( $content ) ) {
                    $sanitized_content = $api->sanitize_response( $content );
                    wp_update_post( array(
                        'ID' => $post_id,
                        'post_content' => $sanitized_content,
                    ) );
                }
            }
            
            // Generate meta description
            if ( $options['generate_meta'] ) {
                $meta_prompt = $api->build_meta_prompt( $post->post_title, $post->post_content );
                $meta = $api->generate_content( $meta_prompt );
                
                if ( ! is_wp_error( $meta ) ) {
                    $sanitized_meta = sanitize_text_field( $meta );
                    update_post_meta( $post_id, '_yoast_wpseo_metadesc', $sanitized_meta );
                }
            }
            
            // Generate tags
            if ( $options['generate_tags'] ) {
                $tags_prompt = $api->build_tags_prompt( $post->post_title, $post->post_content );
                $tags_response = $api->generate_content( $tags_prompt );
                
                if ( ! is_wp_error( $tags_response ) ) {
                    $tags = array_map( 'trim', explode( ',', $tags_response ) );
                    $tags = array_filter( $tags );
                    
                    $taxonomy = ( $post->post_type === 'product' ) ? 'product_tag' : 'post_tag';
                    wp_set_object_terms( $post_id, $tags, $taxonomy );
                }
            }
            
            // Generate excerpt
            if ( $options['generate_excerpt'] ) {
                $excerpt_prompt = $api->build_excerpt_prompt( $post->post_title, $post->post_content );
                $excerpt = $api->generate_content( $excerpt_prompt );
                
                if ( ! is_wp_error( $excerpt ) ) {
                    $sanitized_excerpt = sanitize_text_field( $excerpt );
                    wp_update_post( array(
                        'ID' => $post_id,
                        'post_excerpt' => $sanitized_excerpt,
                    ) );
                }
            }
            
            // Mark as generated
            update_post_meta( $post_id, '_wgc_generated', current_time( 'mysql' ) );
            
        } catch ( Exception $e ) {
            WGC_Core::log_error( 'Bulk processing error for post ' . $post_id . ': ' . $e->getMessage() );
        }
    }
    
    /**
     * Save job data
     */
    private function save_job( $job_data ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wgc_bulk_jobs';
        
        $wpdb->replace(
            $table_name,
            array(
                'job_id' => $job_data['job_id'],
                'post_ids' => wp_json_encode( $job_data['post_ids'] ),
                'status' => $job_data['status'],
                'processed' => $job_data['processed'],
                'total' => $job_data['total'],
                'errors' => wp_json_encode( $job_data['errors'] ),
                'options' => wp_json_encode( $job_data['options'] ),
            ),
            array( '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
        );
    }
    
    /**
     * Get job data
     */
    private function get_job( $job_id ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wgc_bulk_jobs';
        
        $result = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table_name WHERE job_id = %s", $job_id ),
            ARRAY_A
        );
        
        if ( ! $result ) {
            return false;
        }
        
        return array(
            'job_id' => $result['job_id'],
            'post_ids' => json_decode( $result['post_ids'], true ),
            'status' => $result['status'],
            'processed' => intval( $result['processed'] ),
            'total' => intval( $result['total'] ),
            'errors' => json_decode( $result['errors'], true ),
            'options' => json_decode( $result['options'], true ),
        );
    }
}
