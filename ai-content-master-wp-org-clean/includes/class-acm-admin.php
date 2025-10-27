<?php
/**
 * WordPress.org Compliant Admin functionality
 *
 * @package AI_Content_Master
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle admin functionality - WordPress.org compliant
 */
class ACM_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_acm_generate_content', array( $this, 'ajax_generate_content' ) );
        add_action( 'wp_ajax_acm_generate_meta', array( $this, 'ajax_generate_meta' ) );
        add_action( 'wp_ajax_acm_generate_tags', array( $this, 'ajax_generate_tags' ) );
        add_action( 'wp_ajax_acm_generate_excerpt', array( $this, 'ajax_generate_excerpt' ) );
        add_action( 'wp_ajax_acm_generate_all', array( $this, 'ajax_generate_all' ) );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'AI Content Master', 'ai-content-master' ),
            __( 'AI Content Master', 'ai-content-master' ),
            'manage_options',
            'ai-content-master',
            array( $this, 'admin_page' )
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting( 'acm_settings', 'acm_gemini_api_key', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        
        register_setting( 'acm_settings', 'acm_language', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        
        register_setting( 'acm_settings', 'acm_post_types', array(
            'sanitize_callback' => array( $this, 'sanitize_post_types' ),
        ) );
        
        register_setting( 'acm_settings', 'acm_content_length', array(
            'sanitize_callback' => 'absint',
        ) );
        
        register_setting( 'acm_settings', 'acm_excerpt_length', array(
            'sanitize_callback' => 'absint',
        ) );
        
        register_setting( 'acm_settings', 'acm_seo_focus', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        
        register_setting( 'acm_settings', 'acm_batch_size', array(
            'sanitize_callback' => 'absint',
        ) );
        
        register_setting( 'acm_settings', 'acm_upgrade_url', array(
            'sanitize_callback' => 'esc_url_raw',
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
        $can_generate = ACM_Core::can_generate_content();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <!-- Free Generations Info -->
            <div class="acm-free-generations-banner">
                <div class="acm-free-info">
                    <div class="acm-free-item">
                        <span class="acm-free-label"><?php _e( 'Free Generations:', 'ai-content-master' ); ?></span>
                        <span class="acm-free-value"><?php echo esc_html( $can_generate['remaining'] ); ?> / <?php echo esc_html( ACM_FREE_GENERATIONS ); ?></span>
                    </div>
                    <div class="acm-free-item">
                        <span class="acm-free-label"><?php _e( 'Resets:', 'ai-content-master' ); ?></span>
                        <span class="acm-free-value"><?php _e( 'Monthly', 'ai-content-master' ); ?></span>
                    </div>
                </div>
                <?php if ( ! $can_generate['can_generate'] ) : ?>
                    <div class="acm-upgrade-actions">
                        <?php $upgrade = ACM_Core::get_upgrade_message(); ?>
                        <a href="<?php echo esc_url( $upgrade['upgrade_url'] ); ?>" class="button button-primary" target="_blank">
                            <?php echo esc_html( $upgrade['upgrade_text'] ); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields( 'acm_settings' );
                do_settings_sections( 'acm_settings' );
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="acm_gemini_api_key"><?php _e( 'Gemini API Key', 'ai-content-master' ); ?></label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="acm_gemini_api_key" 
                                   name="acm_gemini_api_key" 
                                   value="<?php echo esc_attr( ACM_Core::get_option( 'acm_gemini_api_key' ) ); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                <?php _e( 'Enter your Google Gemini API key. Get it from Google AI Studio.', 'ai-content-master' ); ?>
                                <a href="https://aistudio.google.com/app/apikey" target="_blank"><?php _e( 'Get API Key', 'ai-content-master' ); ?></a>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="acm_language"><?php _e( 'Content Language', 'ai-content-master' ); ?></label>
                        </th>
                        <td>
                            <select id="acm_language" name="acm_language">
                                <?php
                                $languages = array(
                                    'en' => __( 'English', 'ai-content-master' ),
                                    'it' => __( 'Italian', 'ai-content-master' ),
                                    'es' => __( 'Spanish', 'ai-content-master' ),
                                    'fr' => __( 'French', 'ai-content-master' ),
                                    'de' => __( 'German', 'ai-content-master' ),
                                );
                                
                                $current_language = ACM_Core::get_option( 'acm_language', 'en' );
                                
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
                            <?php _e( 'Post Types', 'ai-content-master' ); ?>
                        </th>
                        <td>
                            <?php
                            $post_types = get_post_types( array( 'public' => true ), 'objects' );
                            $selected_types = ACM_Core::get_option( 'acm_post_types', array( 'post' ) );
                            
                            foreach ( $post_types as $type ) {
                                $checked = in_array( $type->name, $selected_types ) ? 'checked' : '';
                                printf(
                                    '<label><input type="checkbox" name="acm_post_types[]" value="%s" %s> %s</label><br>',
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
                            <label for="acm_content_length"><?php _e( 'Content Length', 'ai-content-master' ); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="acm_content_length" 
                                   name="acm_content_length" 
                                   value="<?php echo esc_attr( ACM_Core::get_option( 'acm_content_length', 1000 ) ); ?>" 
                                   min="500" 
                                   max="5000" 
                                   step="100" 
                                   class="small-text" />
                            <p class="description">
                                <?php _e( 'Target word count for generated content.', 'ai-content-master' ); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="acm_excerpt_length"><?php _e( 'Excerpt Length', 'ai-content-master' ); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="acm_excerpt_length" 
                                   name="acm_excerpt_length" 
                                   value="<?php echo esc_attr( ACM_Core::get_option( 'acm_excerpt_length', 150 ) ); ?>" 
                                   min="10" 
                                   max="200" 
                                   class="small-text" />
                            <p class="description">
                                <?php _e( 'Target character count for generated excerpts.', 'ai-content-master' ); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="acm_seo_focus"><?php _e( 'SEO Focus Keywords', 'ai-content-master' ); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="acm_seo_focus" 
                                   name="acm_seo_focus" 
                                   value="<?php echo esc_attr( ACM_Core::get_option( 'acm_seo_focus' ) ); ?>" 
                                   class="regular-text" 
                                   placeholder="<?php esc_attr_e( 'e.g., digital marketing, technology', 'ai-content-master' ); ?>" />
                            <p class="description">
                                <?php _e( 'Comma-separated keywords to focus on in generated content.', 'ai-content-master' ); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="acm_batch_size"><?php _e( 'Batch Size', 'ai-content-master' ); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="acm_batch_size" 
                                   name="acm_batch_size" 
                                   value="<?php echo esc_attr( ACM_Core::get_option( 'acm_batch_size', 5 ) ); ?>" 
                                   min="1" 
                                   max="20" 
                                   class="small-text" />
                            <p class="description">
                                <?php _e( 'Number of posts to process in each batch during bulk operations.', 'ai-content-master' ); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="acm_upgrade_url"><?php _e( 'Upgrade Service URL', 'ai-content-master' ); ?></label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="acm_upgrade_url" 
                                   name="acm_upgrade_url" 
                                   value="<?php echo esc_attr( ACM_Core::get_option( 'acm_upgrade_url', 'https://your-domain.com/upgrade' ) ); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                <?php _e( 'External service URL for premium upgrades (optional).', 'ai-content-master' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <!-- Upgrade Information -->
            <div class="acm-upgrade-info">
                <h2><?php _e( 'Upgrade to Premium', 'ai-content-master' ); ?></h2>
                <p><?php _e( 'Get unlimited AI content generation with our premium service:', 'ai-content-master' ); ?></p>
                <ul>
                    <li><?php _e( 'Unlimited content generation', 'ai-content-master' ); ?></li>
                    <li><?php _e( 'Priority support', 'ai-content-master' ); ?></li>
                    <li><?php _e( 'Advanced features', 'ai-content-master' ); ?></li>
                    <li><?php _e( 'Bulk processing', 'ai-content-master' ); ?></li>
                </ul>
                <?php $upgrade = ACM_Core::get_upgrade_message(); ?>
                <p><a href="<?php echo esc_url( $upgrade['upgrade_url'] ); ?>" class="button button-primary" target="_blank">
                    <?php echo esc_html( $upgrade['upgrade_text'] ); ?>
                </a></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        $post_types = ACM_Core::get_option( 'acm_post_types', array( 'post' ) );
        
        foreach ( $post_types as $post_type ) {
            add_meta_box(
                'acm_content_generator',
                __( 'AI Content Generator', 'ai-content-master' ),
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
        $nonce = wp_create_nonce( 'acm_generate_content' );
        $can_generate = ACM_Core::can_generate_content();
        ?>
        <div id="acm-content-generator">
            <?php if ( ! $can_generate['can_generate'] ) : ?>
                <div class="acm-no-credits">
                    <p><?php _e( 'No free generations remaining this month.', 'ai-content-master' ); ?></p>
                    <?php $upgrade = ACM_Core::get_upgrade_message(); ?>
                    <a href="<?php echo esc_url( $upgrade['upgrade_url'] ); ?>" class="button button-primary" target="_blank">
                        <?php echo esc_html( $upgrade['upgrade_text'] ); ?>
                    </a>
                </div>
            <?php else : ?>
                <div class="acm-credits-info-small">
                    <p><?php printf( __( '%d free generations remaining', 'ai-content-master' ), $can_generate['remaining'] ); ?></p>
                </div>
                
                <div class="acm-buttons">
                    <button type="button" 
                            id="acm-generate-content" 
                            class="button button-secondary" 
                            data-post-id="<?php echo esc_attr( $post->ID ); ?>" 
                            data-nonce="<?php echo esc_attr( $nonce ); ?>">
                        <?php _e( 'Generate Content', 'ai-content-master' ); ?>
                    </button>
                    
                    <button type="button" 
                            id="acm-generate-meta" 
                            class="button button-secondary" 
                            data-post-id="<?php echo esc_attr( $post->ID ); ?>" 
                            data-nonce="<?php echo esc_attr( $nonce ); ?>">
                        <?php _e( 'Generate Meta Description', 'ai-content-master' ); ?>
                    </button>
                    
                    <button type="button" 
                            id="acm-generate-tags" 
                            class="button button-secondary" 
                            data-post-id="<?php echo esc_attr( $post->ID ); ?>" 
                            data-nonce="<?php echo esc_attr( $nonce ); ?>">
                        <?php _e( 'Generate Tags', 'ai-content-master' ); ?>
                    </button>
                    
                    <button type="button" 
                            id="acm-generate-excerpt" 
                            class="button button-secondary" 
                            data-post-id="<?php echo esc_attr( $post->ID ); ?>" 
                            data-nonce="<?php echo esc_attr( $nonce ); ?>">
                        <?php _e( 'Generate Excerpt', 'ai-content-master' ); ?>
                    </button>
                    
                    <button type="button" 
                            id="acm-generate-all" 
                            class="button button-primary" 
                            data-post-id="<?php echo esc_attr( $post->ID ); ?>" 
                            data-nonce="<?php echo esc_attr( $nonce ); ?>">
                        <?php _e( 'Generate All', 'ai-content-master' ); ?>
                    </button>
                </div>
            <?php endif; ?>
            
            <div id="acm-status" style="margin-top: 10px;"></div>
        </div>
        <?php
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts( $hook ) {
        if ( 'post.php' !== $hook && 'post-new.php' !== $hook && strpos( $hook, 'ai-content-master' ) === false ) {
            return;
        }
        
        wp_enqueue_script(
            'acm-admin',
            ACM_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            ACM_VERSION,
            true
        );
        
        wp_enqueue_style(
            'acm-admin',
            ACM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ACM_VERSION
        );
        
        wp_localize_script( 'acm-admin', 'acm', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'acm_ajax' ),
            'strings' => array(
                'generating_content' => __( 'Generating content...', 'ai-content-master' ),
                'generating_meta' => __( 'Generating meta description...', 'ai-content-master' ),
                'generating_tags' => __( 'Generating tags...', 'ai-content-master' ),
                'generating_excerpt' => __( 'Generating excerpt...', 'ai-content-master' ),
                'generating_all' => __( 'Generating all content...', 'ai-content-master' ),
                'no_credits' => __( 'No free generations remaining. Please upgrade to premium.', 'ai-content-master' ),
            ),
        ) );
    }
    
    /**
     * AJAX: Generate content (WordPress.org compliant)
     */
    public function ajax_generate_content() {
        $this->verify_nonce();
        
        // Check if user can generate content
        $can_generate = ACM_Core::can_generate_content();
        if ( ! $can_generate['can_generate'] ) {
            $upgrade = ACM_Core::get_upgrade_message();
            wp_send_json_error( array( 
                'message' => $upgrade['message'],
                'upgrade_url' => $upgrade['upgrade_url'],
            ) );
        }
        
        $post_id = intval( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'ai-content-master' ) ) );
        }
        
        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( array( 'message' => __( 'Post not found', 'ai-content-master' ) ) );
        }
        
        // Consume generation (free only)
        if ( ! ACM_Core::consume_generation() ) {
            wp_send_json_error( array( 'message' => __( 'Failed to consume generation', 'ai-content-master' ) ) );
        }
        
        $api = new ACM_API();
        
        // Use different prompts based on post type
        if ( $post->post_type === 'product' ) {
            $prompt = $api->build_product_prompt( $post->post_title );
        } else {
            $prompt = $api->build_content_prompt( $post->post_title );
        }
        
        $content = $api->generate_content( $prompt );
        
        if ( is_wp_error( $content ) ) {
            wp_send_json_error( array( 'message' => $content->get_error_message() ) );
        }
        
        wp_update_post( array(
            'ID' => $post_id,
            'post_content' => $content,
        ) );
        
        wp_send_json_success( array(
            'message' => __( 'Content generated successfully', 'ai-content-master' ),
            'content' => $content,
            'remaining' => ACM_Core::can_generate_content()['remaining'],
        ) );
    }
    
    /**
     * AJAX: Generate meta description (WordPress.org compliant)
     */
    public function ajax_generate_meta() {
        $this->verify_nonce();
        
        // Check if user can generate content
        $can_generate = ACM_Core::can_generate_content();
        if ( ! $can_generate['can_generate'] ) {
            $upgrade = ACM_Core::get_upgrade_message();
            wp_send_json_error( array( 
                'message' => $upgrade['message'],
                'upgrade_url' => $upgrade['upgrade_url'],
            ) );
        }
        
        $post_id = intval( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'ai-content-master' ) ) );
        }
        
        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( array( 'message' => __( 'Post not found', 'ai-content-master' ) ) );
        }
        
        // Consume generation (free only)
        if ( ! ACM_Core::consume_generation() ) {
            wp_send_json_error( array( 'message' => __( 'Failed to consume generation', 'ai-content-master' ) ) );
        }
        
        $api = new ACM_API();
        $prompt = $api->build_meta_prompt( $post->post_title, $post->post_content );
        $meta = $api->generate_content( $prompt );
        
        if ( is_wp_error( $meta ) ) {
            wp_send_json_error( array( 'message' => $meta->get_error_message() ) );
        }
        
        $sanitized_meta = sanitize_text_field( $meta );
        
        update_post_meta( $post_id, '_yoast_wpseo_metadesc', $sanitized_meta );
        
        wp_send_json_success( array(
            'message' => __( 'Meta description generated successfully', 'ai-content-master' ),
            'meta' => $sanitized_meta,
            'remaining' => ACM_Core::can_generate_content()['remaining'],
        ) );
    }
    
    /**
     * AJAX: Generate tags (WordPress.org compliant)
     */
    public function ajax_generate_tags() {
        $this->verify_nonce();
        
        // Check if user can generate content
        $can_generate = ACM_Core::can_generate_content();
        if ( ! $can_generate['can_generate'] ) {
            $upgrade = ACM_Core::get_upgrade_message();
            wp_send_json_error( array( 
                'message' => $upgrade['message'],
                'upgrade_url' => $upgrade['upgrade_url'],
            ) );
        }
        
        $post_id = intval( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'ai-content-master' ) ) );
        }
        
        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( array( 'message' => __( 'Post not found', 'ai-content-master' ) ) );
        }
        
        // Consume generation (free only)
        if ( ! ACM_Core::consume_generation() ) {
            wp_send_json_error( array( 'message' => __( 'Failed to consume generation', 'ai-content-master' ) ) );
        }
        
        $api = new ACM_API();
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
            'message' => __( 'Tags generated successfully', 'ai-content-master' ),
            'tags' => $tags,
            'remaining' => ACM_Core::can_generate_content()['remaining'],
        ) );
    }
    
    /**
     * AJAX: Generate excerpt (WordPress.org compliant)
     */
    public function ajax_generate_excerpt() {
        $this->verify_nonce();
        
        // Check if user can generate content
        $can_generate = ACM_Core::can_generate_content();
        if ( ! $can_generate['can_generate'] ) {
            $upgrade = ACM_Core::get_upgrade_message();
            wp_send_json_error( array( 
                'message' => $upgrade['message'],
                'upgrade_url' => $upgrade['upgrade_url'],
            ) );
        }
        
        $post_id = intval( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'ai-content-master' ) ) );
        }
        
        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( array( 'message' => __( 'Post not found', 'ai-content-master' ) ) );
        }
        
        // Consume generation (free only)
        if ( ! ACM_Core::consume_generation() ) {
            wp_send_json_error( array( 'message' => __( 'Failed to consume generation', 'ai-content-master' ) ) );
        }
        
        $api = new ACM_API();
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
            'message' => __( 'Excerpt generated successfully', 'ai-content-master' ),
            'excerpt' => $sanitized_excerpt,
            'remaining' => ACM_Core::can_generate_content()['remaining'],
        ) );
    }
    
    /**
     * AJAX: Generate all (WordPress.org compliant)
     */
    public function ajax_generate_all() {
        $this->verify_nonce();
        
        // Check if user can generate content
        $can_generate = ACM_Core::can_generate_content();
        if ( ! $can_generate['can_generate'] ) {
            $upgrade = ACM_Core::get_upgrade_message();
            wp_send_json_error( array( 
                'message' => $upgrade['message'],
                'upgrade_url' => $upgrade['upgrade_url'],
            ) );
        }
        
        $post_id = intval( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'ai-content-master' ) ) );
        }
        
        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( array( 'message' => __( 'Post not found', 'ai-content-master' ) ) );
        }
        
        // Consume generation (free only)
        if ( ! ACM_Core::consume_generation() ) {
            wp_send_json_error( array( 'message' => __( 'Failed to consume generation', 'ai-content-master' ) ) );
        }
        
        $api = new ACM_API();
        $results = array();
        $errors = array();
        
        // Generate content
        try {
            if ( $post->post_type === 'product' ) {
                $content_prompt = $api->build_product_prompt( $post->post_title );
            } else {
                $content_prompt = $api->build_content_prompt( $post->post_title );
            }
            $content = $api->generate_content( $content_prompt );
            
            if ( ! is_wp_error( $content ) ) {
                wp_update_post( array(
                    'ID' => $post_id,
                    'post_content' => $content,
                ) );
                $results[] = __( 'Content', 'ai-content-master' );
            } else {
                $errors[] = __( 'Content generation failed', 'ai-content-master' );
            }
        } catch ( Exception $e ) {
            $errors[] = __( 'Content generation failed', 'ai-content-master' );
        }
        
        // Generate meta description
        try {
            $meta_prompt = $api->build_meta_prompt( $post->post_title, $post->post_content );
            $meta = $api->generate_content( $meta_prompt );
            
            if ( ! is_wp_error( $meta ) ) {
                $sanitized_meta = sanitize_text_field( $meta );
                update_post_meta( $post_id, '_yoast_wpseo_metadesc', $sanitized_meta );
                $results[] = __( 'Meta Description', 'ai-content-master' );
            } else {
                $errors[] = __( 'Meta description generation failed', 'ai-content-master' );
            }
        } catch ( Exception $e ) {
            $errors[] = __( 'Meta description generation failed', 'ai-content-master' );
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
                $results[] = __( 'Tags', 'ai-content-master' );
            } else {
                $errors[] = __( 'Tags generation failed', 'ai-content-master' );
            }
        } catch ( Exception $e ) {
            $errors[] = __( 'Tags generation failed', 'ai-content-master' );
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
                $results[] = __( 'Excerpt', 'ai-content-master' );
            } else {
                $errors[] = __( 'Excerpt generation failed', 'ai-content-master' );
            }
        } catch ( Exception $e ) {
            $errors[] = __( 'Excerpt generation failed', 'ai-content-master' );
        }
        
        $message = sprintf(
            __( 'Generated: %s', 'ai-content-master' ),
            implode( ', ', $results )
        );
        
        if ( ! empty( $errors ) ) {
            $message .= ' | ' . sprintf(
                __( 'Errors: %s', 'ai-content-master' ),
                implode( ', ', $errors )
            );
        }
        
        wp_send_json_success( array(
            'message' => $message,
            'results' => $results,
            'errors' => $errors,
            'remaining' => ACM_Core::can_generate_content()['remaining'],
        ) );
    }
    
    /**
     * Verify nonce
     */
    private function verify_nonce() {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'acm_generate_content' ) ) {
            wp_die( __( 'Security check failed', 'ai-content-master' ) );
        }
        
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( __( 'Insufficient permissions', 'ai-content-master' ) );
        }
    }
}
