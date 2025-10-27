<?php
/**
 * Admin functionality
 *
 * @package WP_Gemini_Content_Generator
 * @since 2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle admin functionality
 */
class WGC_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_wgc_generate_content', array( $this, 'ajax_generate_content' ) );
        add_action( 'wp_ajax_wgc_generate_meta', array( $this, 'ajax_generate_meta' ) );
        add_action( 'wp_ajax_wgc_generate_tags', array( $this, 'ajax_generate_tags' ) );
        add_action( 'wp_ajax_wgc_generate_excerpt', array( $this, 'ajax_generate_excerpt' ) );
        add_action( 'wp_ajax_wgc_generate_all', array( $this, 'ajax_generate_all' ) );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'WP Gemini Content Generator', 'wp-gemini-content-generator' ),
            __( 'Gemini Content', 'wp-gemini-content-generator' ),
            'manage_options',
            'wp-gemini-content-generator',
            array( $this, 'admin_page' )
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting( 'wgc_settings', 'wgc_gemini_api_key', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        
        register_setting( 'wgc_settings', 'wgc_language', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        
        register_setting( 'wgc_settings', 'wgc_post_types', array(
            'sanitize_callback' => array( $this, 'sanitize_post_types' ),
        ) );
        
        register_setting( 'wgc_settings', 'wgc_content_length', array(
            'sanitize_callback' => 'absint',
        ) );
        
        register_setting( 'wgc_settings', 'wgc_excerpt_length', array(
            'sanitize_callback' => 'absint',
        ) );
        
        register_setting( 'wgc_settings', 'wgc_seo_focus', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        
        register_setting( 'wgc_settings', 'wgc_batch_size', array(
            'sanitize_callback' => 'absint',
        ) );
    }
    
    /**
     * Sanitize post types
     */
    public function sanitize_post_types( $value ) {
        if ( ! is_array( $value ) ) {
            return array( 'post' );
        }
        
        return array_map( 'sanitize_text_field', $value );
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields( 'wgc_settings' );
                do_settings_sections( 'wgc_settings' );
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wgc_gemini_api_key"><?php _e( 'Gemini API Key', 'wp-gemini-content-generator' ); ?></label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="wgc_gemini_api_key" 
                                   name="wgc_gemini_api_key" 
                                   value="<?php echo esc_attr( WGC_Core::get_option( 'wgc_gemini_api_key' ) ); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                <?php _e( 'Enter your Google Gemini API key. Get it from Google AI Studio.', 'wp-gemini-content-generator' ); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="wgc_language"><?php _e( 'Content Language', 'wp-gemini-content-generator' ); ?></label>
                        </th>
                        <td>
                            <select id="wgc_language" name="wgc_language">
                                <?php
                                $languages = array(
                                    'en' => __( 'English', 'wp-gemini-content-generator' ),
                                    'it' => __( 'Italian', 'wp-gemini-content-generator' ),
                                    'es' => __( 'Spanish', 'wp-gemini-content-generator' ),
                                    'fr' => __( 'French', 'wp-gemini-content-generator' ),
                                    'de' => __( 'German', 'wp-gemini-content-generator' ),
                                );
                                
                                $current_language = WGC_Core::get_option( 'wgc_language', 'en' );
                                
                                foreach ( $languages as $code => $name ) {
                                    printf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr( $code ),
                                        selected( $current_language, $code, false ),
                                        esc_html( $name )
                                    );
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <?php _e( 'Post Types', 'wp-gemini-content-generator' ); ?>
                        </th>
                        <td>
                            <?php
                            $post_types = get_post_types( array( 'public' => true ), 'objects' );
                            $selected_types = WGC_Core::get_option( 'wgc_post_types', array( 'post' ) );
                            
                            foreach ( $post_types as $type ) {
                                $checked = in_array( $type->name, $selected_types ) ? 'checked' : '';
                                printf(
                                    '<label><input type="checkbox" name="wgc_post_types[]" value="%s" %s> %s</label><br>',
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
                            <label for="wgc_content_length"><?php _e( 'Content Length', 'wp-gemini-content-generator' ); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="wgc_content_length" 
                                   name="wgc_content_length" 
                                   value="<?php echo esc_attr( WGC_Core::get_option( 'wgc_content_length', 1000 ) ); ?>" 
                                   min="500" 
                                   max="5000" 
                                   step="100" 
                                   class="small-text" />
                            <p class="description">
                                <?php _e( 'Target word count for generated content.', 'wp-gemini-content-generator' ); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="wgc_excerpt_length"><?php _e( 'Excerpt Length', 'wp-gemini-content-generator' ); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="wgc_excerpt_length" 
                                   name="wgc_excerpt_length" 
                                   value="<?php echo esc_attr( WGC_Core::get_option( 'wgc_excerpt_length', 150 ) ); ?>" 
                                   min="10" 
                                   max="200" 
                                   class="small-text" />
                            <p class="description">
                                <?php _e( 'Target character count for generated excerpts.', 'wp-gemini-content-generator' ); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="wgc_seo_focus"><?php _e( 'SEO Focus Keywords', 'wp-gemini-content-generator' ); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="wgc_seo_focus" 
                                   name="wgc_seo_focus" 
                                   value="<?php echo esc_attr( WGC_Core::get_option( 'wgc_seo_focus' ) ); ?>" 
                                   class="regular-text" 
                                   placeholder="<?php esc_attr_e( 'e.g., digital marketing, technology', 'wp-gemini-content-generator' ); ?>" />
                            <p class="description">
                                <?php _e( 'Comma-separated keywords to focus on in generated content.', 'wp-gemini-content-generator' ); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="wgc_batch_size"><?php _e( 'Batch Size', 'wp-gemini-content-generator' ); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="wgc_batch_size" 
                                   name="wgc_batch_size" 
                                   value="<?php echo esc_attr( WGC_Core::get_option( 'wgc_batch_size', 5 ) ); ?>" 
                                   min="1" 
                                   max="20" 
                                   class="small-text" />
                            <p class="description">
                                <?php _e( 'Number of posts to process in each batch during bulk operations.', 'wp-gemini-content-generator' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        $post_types = WGC_Core::get_option( 'wgc_post_types', array( 'post' ) );
        
        foreach ( $post_types as $post_type ) {
            add_meta_box(
                'wgc_content_generator',
                __( 'AI Content Generator', 'wp-gemini-content-generator' ),
                array( $this, 'meta_box_callback' ),
                $post_type,
                'side',
                'high'
            );
        }
    }
    
    /**
     * Meta box callback
     */
    public function meta_box_callback( $post ) {
        $nonce = wp_create_nonce( 'wgc_generate_content' );
        ?>
        <div id="wgc-content-generator">
            <p><?php _e( 'Generate AI-powered content for this post:', 'wp-gemini-content-generator' ); ?></p>
            
            <div class="wgc-buttons">
                <button type="button" 
                        id="wgc-generate-content" 
                        class="button button-secondary" 
                        data-post-id="<?php echo esc_attr( $post->ID ); ?>" 
                        data-nonce="<?php echo esc_attr( $nonce ); ?>">
                    <?php _e( 'Generate Content', 'wp-gemini-content-generator' ); ?>
                </button>
                
                <button type="button" 
                        id="wgc-generate-meta" 
                        class="button button-secondary" 
                        data-post-id="<?php echo esc_attr( $post->ID ); ?>" 
                        data-nonce="<?php echo esc_attr( $nonce ); ?>">
                    <?php _e( 'Generate Meta Description', 'wp-gemini-content-generator' ); ?>
                </button>
                
                <button type="button" 
                        id="wgc-generate-tags" 
                        class="button button-secondary" 
                        data-post-id="<?php echo esc_attr( $post->ID ); ?>" 
                        data-nonce="<?php echo esc_attr( $nonce ); ?>">
                    <?php _e( 'Generate Tags', 'wp-gemini-content-generator' ); ?>
                </button>
                
                <button type="button" 
                        id="wgc-generate-excerpt" 
                        class="button button-secondary" 
                        data-post-id="<?php echo esc_attr( $post->ID ); ?>" 
                        data-nonce="<?php echo esc_attr( $nonce ); ?>">
                    <?php _e( 'Generate Excerpt', 'wp-gemini-content-generator' ); ?>
                </button>
                
                <button type="button" 
                        id="wgc-generate-all" 
                        class="button button-primary" 
                        data-post-id="<?php echo esc_attr( $post->ID ); ?>" 
                        data-nonce="<?php echo esc_attr( $nonce ); ?>">
                    <?php _e( 'Generate All', 'wp-gemini-content-generator' ); ?>
                </button>
            </div>
            
            <div id="wgc-status" style="margin-top: 10px;"></div>
        </div>
        <?php
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts( $hook ) {
        if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
            return;
        }
        
        wp_enqueue_script(
            'wgc-admin',
            WGC_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            WGC_VERSION,
            true
        );
        
        wp_enqueue_style(
            'wgc-admin',
            WGC_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WGC_VERSION
        );
        
        wp_localize_script( 'wgc-admin', 'wgc', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wgc_ajax' ),
        ) );
    }
    
    /**
     * AJAX: Generate content
     */
    public function ajax_generate_content() {
        $this->verify_nonce();
        
        $post_id = intval( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'wp-gemini-content-generator' ) ) );
        }
        
        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( array( 'message' => __( 'Post not found', 'wp-gemini-content-generator' ) ) );
        }
        
        $api = new WGC_API();
        $prompt = $api->build_content_prompt( $post->post_title );
        $content = $api->generate_content( $prompt );
        
        if ( is_wp_error( $content ) ) {
            wp_send_json_error( array( 'message' => $content->get_error_message() ) );
        }
        
        $sanitized_content = $api->sanitize_response( $content );
        
        wp_update_post( array(
            'ID' => $post_id,
            'post_content' => $sanitized_content,
        ) );
        
        wp_send_json_success( array(
            'message' => __( 'Content generated successfully', 'wp-gemini-content-generator' ),
            'content' => $sanitized_content,
        ) );
    }
    
    /**
     * AJAX: Generate meta description
     */
    public function ajax_generate_meta() {
        $this->verify_nonce();
        
        $post_id = intval( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'wp-gemini-content-generator' ) ) );
        }
        
        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( array( 'message' => __( 'Post not found', 'wp-gemini-content-generator' ) ) );
        }
        
        $api = new WGC_API();
        $prompt = $api->build_meta_prompt( $post->post_title, $post->post_content );
        $meta = $api->generate_content( $prompt );
        
        if ( is_wp_error( $meta ) ) {
            wp_send_json_error( array( 'message' => $meta->get_error_message() ) );
        }
        
        $sanitized_meta = sanitize_text_field( $meta );
        
        update_post_meta( $post_id, '_yoast_wpseo_metadesc', $sanitized_meta );
        
        wp_send_json_success( array(
            'message' => __( 'Meta description generated successfully', 'wp-gemini-content-generator' ),
            'meta' => $sanitized_meta,
        ) );
    }
    
    /**
     * AJAX: Generate tags
     */
    public function ajax_generate_tags() {
        $this->verify_nonce();
        
        $post_id = intval( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'wp-gemini-content-generator' ) ) );
        }
        
        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( array( 'message' => __( 'Post not found', 'wp-gemini-content-generator' ) ) );
        }
        
        $api = new WGC_API();
        $prompt = $api->build_tags_prompt( $post->post_title, $post->post_content );
        $tags_response = $api->generate_content( $prompt );
        
        if ( is_wp_error( $tags_response ) ) {
            wp_send_json_error( array( 'message' => $tags_response->get_error_message() ) );
        }
        
        $tags = array_map( 'trim', explode( ',', $tags_response ) );
        $tags = array_filter( $tags );
        
        // Determine taxonomy based on post type
        $taxonomy = ( $post->post_type === 'product' ) ? 'product_tag' : 'post_tag';
        
        wp_set_object_terms( $post_id, $tags, $taxonomy );
        
        wp_send_json_success( array(
            'message' => __( 'Tags generated successfully', 'wp-gemini-content-generator' ),
            'tags' => $tags,
        ) );
    }
    
    /**
     * AJAX: Generate excerpt
     */
    public function ajax_generate_excerpt() {
        $this->verify_nonce();
        
        $post_id = intval( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'wp-gemini-content-generator' ) ) );
        }
        
        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( array( 'message' => __( 'Post not found', 'wp-gemini-content-generator' ) ) );
        }
        
        $api = new WGC_API();
        $prompt = $api->build_excerpt_prompt( $post->post_title, $post->post_content );
        $excerpt = $api->generate_content( $prompt );
        
        if ( is_wp_error( $excerpt ) ) {
            wp_send_json_error( array( 'message' => $excerpt->get_error_message() ) );
        }
        
        $sanitized_excerpt = sanitize_text_field( $excerpt );
        
        wp_update_post( array(
            'ID' => $post_id,
            'post_excerpt' => $sanitized_excerpt,
        ) );
        
        wp_send_json_success( array(
            'message' => __( 'Excerpt generated successfully', 'wp-gemini-content-generator' ),
            'excerpt' => $sanitized_excerpt,
        ) );
    }
    
    /**
     * AJAX: Generate all
     */
    public function ajax_generate_all() {
        $this->verify_nonce();
        
        $post_id = intval( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'wp-gemini-content-generator' ) ) );
        }
        
        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( array( 'message' => __( 'Post not found', 'wp-gemini-content-generator' ) ) );
        }
        
        $api = new WGC_API();
        $results = array();
        $errors = array();
        
        // Generate content
        try {
            $content_prompt = $api->build_content_prompt( $post->post_title );
            $content = $api->generate_content( $content_prompt );
            
            if ( ! is_wp_error( $content ) ) {
                $sanitized_content = $api->sanitize_response( $content );
                wp_update_post( array(
                    'ID' => $post_id,
                    'post_content' => $sanitized_content,
                ) );
                $results[] = __( 'Content', 'wp-gemini-content-generator' );
            } else {
                $errors[] = __( 'Content generation failed', 'wp-gemini-content-generator' );
            }
        } catch ( Exception $e ) {
            $errors[] = __( 'Content generation failed', 'wp-gemini-content-generator' );
        }
        
        // Generate meta description
        try {
            $meta_prompt = $api->build_meta_prompt( $post->post_title, $post->post_content );
            $meta = $api->generate_content( $meta_prompt );
            
            if ( ! is_wp_error( $meta ) ) {
                $sanitized_meta = sanitize_text_field( $meta );
                update_post_meta( $post_id, '_yoast_wpseo_metadesc', $sanitized_meta );
                $results[] = __( 'Meta Description', 'wp-gemini-content-generator' );
            } else {
                $errors[] = __( 'Meta description generation failed', 'wp-gemini-content-generator' );
            }
        } catch ( Exception $e ) {
            $errors[] = __( 'Meta description generation failed', 'wp-gemini-content-generator' );
        }
        
        // Generate tags
        try {
            $tags_prompt = $api->build_tags_prompt( $post->post_title, $post->post_content );
            $tags_response = $api->generate_content( $tags_prompt );
            
            if ( ! is_wp_error( $tags_response ) ) {
                $tags = array_map( 'trim', explode( ',', $tags_response ) );
                $tags = array_filter( $tags );
                
                $taxonomy = ( $post->post_type === 'product' ) ? 'product_tag' : 'post_tag';
                wp_set_object_terms( $post_id, $tags, $taxonomy );
                $results[] = __( 'Tags', 'wp-gemini-content-generator' );
            } else {
                $errors[] = __( 'Tags generation failed', 'wp-gemini-content-generator' );
            }
        } catch ( Exception $e ) {
            $errors[] = __( 'Tags generation failed', 'wp-gemini-content-generator' );
        }
        
        // Generate excerpt
        try {
            $excerpt_prompt = $api->build_excerpt_prompt( $post->post_title, $post->post_content );
            $excerpt = $api->generate_content( $excerpt_prompt );
            
            if ( ! is_wp_error( $excerpt ) ) {
                $sanitized_excerpt = sanitize_text_field( $excerpt );
                wp_update_post( array(
                    'ID' => $post_id,
                    'post_excerpt' => $sanitized_excerpt,
                ) );
                $results[] = __( 'Excerpt', 'wp-gemini-content-generator' );
            } else {
                $errors[] = __( 'Excerpt generation failed', 'wp-gemini-content-generator' );
            }
        } catch ( Exception $e ) {
            $errors[] = __( 'Excerpt generation failed', 'wp-gemini-content-generator' );
        }
        
        $message = sprintf(
            __( 'Generated: %s', 'wp-gemini-content-generator' ),
            implode( ', ', $results )
        );
        
        if ( ! empty( $errors ) ) {
            $message .= ' | ' . sprintf(
                __( 'Errors: %s', 'wp-gemini-content-generator' ),
                implode( ', ', $errors )
            );
        }
        
        wp_send_json_success( array(
            'message' => $message,
            'results' => $results,
            'errors' => $errors,
        ) );
    }
    
    /**
     * Verify nonce
     */
    private function verify_nonce() {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'wgc_generate_content' ) ) {
            wp_die( __( 'Security check failed', 'wp-gemini-content-generator' ) );
        }
        
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( __( 'Insufficient permissions', 'wp-gemini-content-generator' ) );
        }
    }
}
