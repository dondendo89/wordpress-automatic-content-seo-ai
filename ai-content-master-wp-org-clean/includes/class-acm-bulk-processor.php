<?php
/**
 * Bulk processor with freemium functionality
 *
 * @package AI_Content_Master
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle bulk content generation with freemium features
 */
class ACM_Bulk_Processor {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_bulk_menu' ) );
        add_action( 'wp_ajax_acm_bulk_generate', array( $this, 'ajax_bulk_generate' ) );
        add_action( 'wp_ajax_acm_bulk_status', array( $this, 'ajax_bulk_status' ) );
        add_action( 'wgc_process_bulk_job', array( $this, 'process_bulk_job' ) );
    }
    
    /**
     * Add bulk menu
     */
    public function add_bulk_menu() {
        add_submenu_page(
            'ai-content-master',
            __( 'Bulk Generation', 'ai-content-master' ),
            __( 'Bulk Generation', 'ai-content-master' ),
            'manage_options',
            'acm-bulk-generation',
            array( $this, 'bulk_page' )
        );
    }
    
    /**
     * Bulk generation page
     */
    public function bulk_page() {
        $credits_info = ( new ACM_Credits() )->get_credits_info();
        ?>
        <div class="wrap">
            <h1><?php _e( 'Bulk Content Generation', 'ai-content-master' ); ?></h1>
            
            <!-- Credits Info -->
            <div class="acm-bulk-credits-info">
                <div class="acm-credits-summary">
                    <div class="acm-credit-item">
                        <span class="acm-credit-label"><?php _e( 'Free Generations:', 'ai-content-master' ); ?></span>
                        <span class="acm-credit-value"><?php echo esc_html( $credits_info['free_generations_remaining'] ); ?> / <?php echo esc_html( ACM_FREE_GENERATIONS ); ?></span>
                    </div>
                    <div class="acm-credit-item">
                        <span class="acm-credit-label"><?php _e( 'Credits Balance:', 'ai-content-master' ); ?></span>
                        <span class="acm-credit-value"><?php echo esc_html( $credits_info['credits_remaining'] ); ?> <?php _e( 'generations', 'ai-content-master' ); ?></span>
                    </div>
                </div>
                <div class="acm-credits-actions">
                    <a href="<?php echo admin_url( 'admin.php?page=acm-credits' ); ?>" class="button button-primary">
                        <?php _e( 'Buy More Credits', 'ai-content-master' ); ?>
                    </a>
                </div>
            </div>
            
            <div class="acm-bulk-generator">
                <form id="acm-bulk-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="acm-post-types"><?php _e( 'Post Types', 'ai-content-master' ); ?></label>
                            </th>
                            <td>
                                <?php
                                $post_types = get_post_types( array( 'public' => true ), 'objects' );
                                $selected_types = ACM_Core::get_option( 'acm_post_types', array( 'post' ) );
                                
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
                                <?php _e( 'Generation Options', 'ai-content-master' ); ?>
                            </th>
                            <td>
                                <label><input type="checkbox" name="generateContent" value="1" checked> <?php _e( 'Generate Content', 'ai-content-master' ); ?></label><br>
                                <label><input type="checkbox" name="generateMeta" value="1" checked> <?php _e( 'Generate Meta Descriptions', 'ai-content-master' ); ?></label><br>
                                <label><input type="checkbox" name="generateTags" value="1" checked> <?php _e( 'Generate Tags', 'ai-content-master' ); ?></label><br>
                                <label><input type="checkbox" name="generateExcerpt" value="1" checked> <?php _e( 'Generate Excerpts', 'ai-content-master' ); ?></label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="acm-batch-size"><?php _e( 'Batch Size', 'ai-content-master' ); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="acm-batch-size" 
                                       name="batchSize" 
                                       value="<?php echo esc_attr( ACM_Core::get_option( 'acm_batch_size', 5 ) ); ?>" 
                                       min="1" 
                                       max="20" 
                                       class="acm-input">
                                <p class="description">
                                    <?php _e( 'Number of posts to process in each batch.', 'ai-content-master' ); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="acm-force-regenerate"><?php _e( 'Force Regenerate', 'ai-content-master' ); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="acm-force-regenerate" 
                                           name="forceRegenerate" 
                                           value="1">
                                    <?php _e( 'Regenerate content even if it already exists', 'ai-content-master' ); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" 
                                id="acm-bulk-generate" 
                                class="button button-primary button-large" 
                                data-nonce="<?php echo esc_attr( wp_create_nonce( 'acm_bulk_generate' ) ); ?>">
                            <?php _e( 'Start Bulk Generation', 'ai-content-master' ); ?>
                        </button>
                    </p>
                </form>
                
                <div id="acm-bulk-status" style="margin-top: 20px;"></div>
                <div id="acm-bulk-progress" style="margin-top: 20px;"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Start bulk generation
     */
    public function ajax_bulk_generate() {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'acm_bulk_generate' ) ) {
            wp_die( __( 'Security check failed', 'ai-content-master' ) );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Insufficient permissions', 'ai-content-master' ) );
        }
        
        $post_types = array_map( 'sanitize_text_field', $_POST['postTypes'] ?? [] );
        $generate_content = ! empty( $_POST['generateContent'] );
        $generate_meta = ! empty( $_POST['generateMeta'] );
        $generate_tags = ! empty( $_POST['generateTags'] );
        $generate_excerpt = ! empty( $_POST['generateExcerpt'] );
        $batch_size = intval( $_POST['batchSize'] ?? 5 );
        $force_regenerate = ! empty( $_POST['forceRegenerate'] );
        
        if ( empty( $post_types ) ) {
            wp_send_json_error( array( 'message' => __( 'Please select at least one post type', 'ai-content-master' ) ) );
        }
        
        // Check if user has enough credits for bulk operation
        $total_posts = $this->count_posts_to_process( $post_types, $force_regenerate );
        $total_generations_needed = $total_posts * $this->count_generation_types( $generate_content, $generate_meta, $generate_tags, $generate_excerpt );
        
        $can_generate = ACM_Core::can_generate_content();
        $available_generations = $can_generate['remaining'];
        
        if ( $total_generations_needed > $available_generations ) {
            wp_send_json_error( array( 
                'message' => sprintf( 
                    __( 'Not enough credits. You need %d generations but only have %d available.', 'ai-content-master' ), 
                    $total_generations_needed, 
                    $available_generations 
                ),
                'redirect' => admin_url( 'admin.php?page=acm-credits' ),
            ) );
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
                    'key' => '_acm_generated',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => '_acm_generated',
                    'value' => '',
                    'compare' => '=',
                ),
            );
        }
        
        $posts = get_posts( $args );
        
        if ( empty( $posts ) ) {
            wp_send_json_error( array( 'message' => __( 'No posts found to process', 'ai-content-master' ) ) );
        }
        
        // Create job
        $job_id = 'acm_bulk_' . time();
        $job_data = array(
            'job_id' => $job_id,
            'user_id' => get_current_user_id(),
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
            'message' => __( 'Bulk generation started', 'ai-content-master' ),
            'job_id' => $job_id,
            'total_posts' => count( $posts ),
            'total_generations' => $total_generations_needed,
        ) );
    }
    
    /**
     * Count posts to process
     */
    private function count_posts_to_process( $post_types, $force_regenerate ) {
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
                    'key' => '_acm_generated',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => '_acm_generated',
                    'value' => '',
                    'compare' => '=',
                ),
            );
        }
        
        $posts = get_posts( $args );
        return count( $posts );
    }
    
    /**
     * Count generation types selected
     */
    private function count_generation_types( $generate_content, $generate_meta, $generate_tags, $generate_excerpt ) {
        $count = 0;
        if ( $generate_content ) $count++;
        if ( $generate_meta ) $count++;
        if ( $generate_tags ) $count++;
        if ( $generate_excerpt ) $count++;
        return $count;
    }
    
    /**
     * AJAX: Get bulk status
     */
    public function ajax_bulk_status() {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'acm_bulk_generate' ) ) {
            wp_die( __( 'Security check failed', 'ai-content-master' ) );
        }
        
        $job_id = sanitize_text_field( $_POST['jobId'] ?? '' );
        if ( empty( $job_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Job ID required', 'ai-content-master' ) ) );
        }
        
        $job_data = $this->get_job( $job_id );
        if ( ! $job_data ) {
            wp_send_json_error( array( 'message' => __( 'Job not found', 'ai-content-master' ) ) );
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
        
        $api = new ACM_API();
        
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
                
                // Consume generation
                ACM_Core::consume_generation();
            }
            
            // Generate meta description
            if ( $options['generate_meta'] ) {
                $meta_prompt = $api->build_meta_prompt( $post->post_title, $post->post_content );
                $meta = $api->generate_content( $meta_prompt );
                
                if ( ! is_wp_error( $meta ) ) {
                    $sanitized_meta = sanitize_text_field( $meta );
                    update_post_meta( $post_id, '_yoast_wpseo_metadesc', $sanitized_meta );
                }
                
                // Consume generation
                ACM_Core::consume_generation();
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
                
                // Consume generation
                ACM_Core::consume_generation();
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
                
                // Consume generation
                ACM_Core::consume_generation();
            }
            
            // Mark as generated
            update_post_meta( $post_id, '_acm_generated', current_time( 'mysql' ) );
            
        } catch ( Exception $e ) {
            ACM_Core::log_error( 'Bulk processing error for post ' . $post_id . ': ' . $e->getMessage() );
        }
    }
    
    /**
     * Save job data
     */
    private function save_job( $job_data ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'acm_bulk_jobs';
        
        $wpdb->replace(
            $table_name,
            array(
                'job_id' => $job_data['job_id'],
                'user_id' => $job_data['user_id'],
                'post_ids' => wp_json_encode( $job_data['post_ids'] ),
                'status' => $job_data['status'],
                'processed' => $job_data['processed'],
                'total' => $job_data['total'],
                'errors' => wp_json_encode( $job_data['errors'] ),
                'options' => wp_json_encode( $job_data['options'] ),
            ),
            array( '%s', '%d', '%s', '%s', '%d', '%d', '%s', '%s' )
        );
    }
    
    /**
     * Get job data
     */
    private function get_job( $job_id ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'acm_bulk_jobs';
        
        $result = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table_name WHERE job_id = %s", $job_id ),
            ARRAY_A
        );
        
        if ( ! $result ) {
            return false;
        }
        
        return array(
            'job_id' => $result['job_id'],
            'user_id' => intval( $result['user_id'] ),
            'post_ids' => json_decode( $result['post_ids'], true ),
            'status' => $result['status'],
            'processed' => intval( $result['processed'] ),
            'total' => intval( $result['total'] ),
            'errors' => json_decode( $result['errors'], true ),
            'options' => json_decode( $result['options'], true ),
        );
    }
}
