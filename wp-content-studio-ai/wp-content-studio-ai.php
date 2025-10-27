<?php
/**
 * Plugin Name:       WP Content Studio AI
 * Plugin URI:        https://example.com/wp-content-studio-ai
 * Description:       AI-powered long descriptions for WordPress and WooCommerce using AI content APIs.
 * Version:           1.0.0
 * Author:            WP Content Studio AI
 * Author URI:        https://example.com
 * Text Domain:       wp-content-studio-ai
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Tested up to:      6.8.3
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WGC_PLUGIN_FILE', __FILE__ );
define( 'WGC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WGC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WGC_OPTION_API_KEY', 'wgc_gemini_api_key' );
define( 'WGC_OPTION_LANGUAGE', 'wgc_language' );
define( 'WGC_OPTION_POST_TYPES', 'wgc_post_types' );
define( 'WGC_OPTION_APPEND_MODE', 'wgc_append_mode' );
define( 'WGC_OPTION_EMOJI_ICONS', 'wgc_emoji_icons' );
define( 'WGC_OPTION_ENABLE_META', 'wgc_enable_meta' );
define( 'WGC_OPTION_ENABLE_TAGS', 'wgc_enable_tags' );

class WPGeminiContentGenerator {
	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		// AJAX actions for single generate and bulk generate
		add_action( 'wp_ajax_wgc_generate_for_post', [ $this, 'ajax_generate_for_post' ] );
		add_action( 'wp_ajax_wgc_generate_meta', [ $this, 'ajax_generate_meta' ] );
		add_action( 'wp_ajax_wgc_generate_tags', [ $this, 'ajax_generate_tags' ] );
		add_action( 'wp_ajax_wgc_bulk_generate', [ $this, 'ajax_bulk_generate' ] );
		add_action( 'wp_ajax_wgc_bulk_status', [ $this, 'ajax_bulk_status' ] );
		
		// Cron job for background processing
		add_action( 'wgc_bulk_process_job', [ $this, 'process_bulk_job' ] );
		add_action( 'init', [ $this, 'schedule_bulk_job' ] );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'wp-content-studio-ai', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public function register_settings_page() {
		add_options_page(
			__( 'Content Studio AI', 'wp-content-studio-ai' ),
			__( 'Content Studio AI', 'wp-content-studio-ai' ),
			'manage_options',
			'wgc-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	public function register_settings() {
		// Ensure sensitive option does not autoload
		$this->ensure_api_key_autoload_no();
		register_setting( 'wgc_settings_group', WGC_OPTION_API_KEY, [
			'type'              => 'string',
			'sanitize_callback' => [ $this, 'sanitize_api_key' ],
			'default'           => '',
			'capability'        => 'manage_options',
		] );
		// Meta description toggle
		register_setting( 'wgc_settings_group', WGC_OPTION_ENABLE_META, [
			'type'              => 'boolean',
			'sanitize_callback' => [ $this, 'sanitize_emoji_icons' ],
			'default'           => false,
			'capability'        => 'manage_options',
		] );
		// Tags generation toggle
		register_setting( 'wgc_settings_group', WGC_OPTION_ENABLE_TAGS, [
			'type'              => 'boolean',
			'sanitize_callback' => [ $this, 'sanitize_emoji_icons' ],
			'default'           => false,
			'capability'        => 'manage_options',
		] );

		register_setting( 'wgc_settings_group', WGC_OPTION_LANGUAGE, [
			'type'              => 'string',
			'sanitize_callback' => [ $this, 'sanitize_language' ],
			'default'           => 'en',
			'capability'        => 'manage_options',
		] );

		register_setting( 'wgc_settings_group', WGC_OPTION_POST_TYPES, [
			'type'              => 'array',
			'sanitize_callback' => [ $this, 'sanitize_post_types' ],
			'default'           => [ 'post', 'page' ],
			'capability'        => 'manage_options',
		] );

		register_setting( 'wgc_settings_group', WGC_OPTION_APPEND_MODE, [
			'type'              => 'string',
			'sanitize_callback' => [ $this, 'sanitize_append_mode' ],
			'default'           => 'replace',
			'capability'        => 'manage_options',
		] );

		register_setting( 'wgc_settings_group', WGC_OPTION_EMOJI_ICONS, [
			'type'              => 'boolean',
			'sanitize_callback' => [ $this, 'sanitize_emoji_icons' ],
			'default'           => false,
			'capability'        => 'manage_options',
		] );

		add_settings_section(
			'wgc_api_section',
			__( 'API Settings', 'wp-content-studio-ai' ),
			function () {
				echo '<p>' . esc_html__( 'Configure your API key to enable content generation.', 'wp-content-studio-ai' ) . '</p>';
			},
			'wgc-settings'
		);

		add_settings_field(
			'wgc_api_key',
			__( 'Gemini API Key', 'wp-content-studio-ai' ),
			[ $this, 'render_api_key_field' ],
			'wgc-settings',
			'wgc_api_section'
		);

		add_settings_field(
			'wgc_language',
			__( 'Content Language', 'wp-content-studio-ai' ),
			[ $this, 'render_language_field' ],
			'wgc-settings',
			'wgc_api_section'
		);

		add_settings_field(
			'wgc_post_types',
			__( 'Enable on Post Types', 'wp-content-studio-ai' ),
			[ $this, 'render_post_types_field' ],
			'wgc-settings',
			'wgc_api_section'
		);

		add_settings_field(
			'wgc_append_mode',
			__( 'Content Mode', 'wp-content-studio-ai' ),
			[ $this, 'render_append_mode_field' ],
			'wgc-settings',
			'wgc_api_section'
		);

		add_settings_field(
			'wgc_emoji_icons',
			__( 'Emoji & Icons', 'wp-content-studio-ai' ),
			[ $this, 'render_emoji_icons_field' ],
			'wgc-settings',
			'wgc_api_section'
		);

		add_settings_field(
			'wgc_enable_meta',
			__( 'Meta Description', 'wp-content-studio-ai' ),
			[ $this, 'render_enable_meta_field' ],
			'wgc-settings',
			'wgc_api_section'
		);

		add_settings_field(
			'wgc_enable_tags',
			__( 'Tags Generation', 'wp-content-studio-ai' ),
			[ $this, 'render_enable_tags_field' ],
			'wgc-settings',
			'wgc_api_section'
		);
	}

	public function sanitize_api_key( $value ) {
		$value = trim( (string) $value );
		return $value;
	}

	public function sanitize_language( $value ) {
		$allowed_languages = [ 'en', 'it', 'es', 'fr', 'de', 'pt', 'ru', 'ja', 'ko', 'zh', 'ar', 'hi' ];
		$value = trim( (string) $value );
		return in_array( $value, $allowed_languages, true ) ? $value : 'en';
	}

	public function sanitize_post_types( $value ) {
		if ( ! is_array( $value ) ) {
			return [ 'post', 'page' ];
		}
		
		$allowed_post_types = get_post_types( [ 'public' => true ], 'names' );
		$sanitized = [];
		
		foreach ( $value as $post_type ) {
			if ( in_array( $post_type, $allowed_post_types, true ) ) {
				$sanitized[] = sanitize_text_field( $post_type );
			}
		}
		
		return empty( $sanitized ) ? [ 'post', 'page' ] : $sanitized;
	}

	public function sanitize_append_mode( $value ) {
		$allowed_modes = [ 'replace', 'append' ];
		$value = trim( (string) $value );
		return in_array( $value, $allowed_modes, true ) ? $value : 'replace';
	}

	public function sanitize_emoji_icons( $value ) {
		return (bool) $value;
	}

	public function render_api_key_field() {
		$api_key = (string) get_option( 'wgc_ai_studio_api_key', get_option( WGC_OPTION_API_KEY, '' ) );
		echo '<input type="password" style="width: 420px;" name="' . esc_attr( WGC_OPTION_API_KEY ) . '" value="' . esc_attr( $api_key ) . '" placeholder="AIza..." />';
		echo '<p class="description">' . esc_html__( 'Your Gemini API key. Stored in WordPress options.', 'wp-content-studio-ai' ) . '</p>';
		echo '<p class="description"><a href="https://aistudio.google.com/u/7/api-keys" target="_blank" rel="nofollow noopener">Google AI Studio — Get Gemini API key</a></p>';
	}

	public function render_language_field() {
		$language = get_option( WGC_OPTION_LANGUAGE, 'en' );
		$languages = [
			'en' => __( 'English', 'wp-content-studio-ai' ),
			'it' => __( 'Italian', 'wp-content-studio-ai' ),
			'es' => __( 'Spanish', 'wp-content-studio-ai' ),
			'fr' => __( 'French', 'wp-content-studio-ai' ),
			'de' => __( 'German', 'wp-content-studio-ai' ),
			'pt' => __( 'Portuguese', 'wp-content-studio-ai' ),
			'ru' => __( 'Russian', 'wp-content-studio-ai' ),
			'ja' => __( 'Japanese', 'wp-content-studio-ai' ),
			'ko' => __( 'Korean', 'wp-content-studio-ai' ),
			'zh' => __( 'Chinese', 'wp-content-studio-ai' ),
			'ar' => __( 'Arabic', 'wp-content-studio-ai' ),
			'hi' => __( 'Hindi', 'wp-content-studio-ai' ),
		];
		
		echo '<select name="' . esc_attr( WGC_OPTION_LANGUAGE ) . '" style="width: 200px;">';
		foreach ( $languages as $code => $name ) {
			$selected = selected( $language, $code, false );
			echo '<option value="' . esc_attr( $code ) . '"' . $selected . '>' . esc_html( $name ) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Select the language for generated content.', 'wp-content-studio-ai' ) . '</p>';
	}

	public function render_post_types_field() {
		$selected_post_types = get_option( WGC_OPTION_POST_TYPES, [ 'post', 'page' ] );
		$available_post_types = get_post_types( [ 'public' => true ], 'objects' );
		
		echo '<div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">';
		foreach ( $available_post_types as $post_type ) {
			$checked = in_array( $post_type->name, $selected_post_types, true ) ? 'checked="checked"' : '';
			echo '<label style="display: block; margin: 5px 0;">';
			echo '<input type="checkbox" name="' . esc_attr( WGC_OPTION_POST_TYPES ) . '[]" value="' . esc_attr( $post_type->name ) . '" ' . $checked . ' /> ';
			echo esc_html( $post_type->label ) . ' (' . esc_html( $post_type->name ) . ')';
			echo '</label>';
		}
		echo '</div>';
		echo '<p class="description">' . esc_html__( 'Select which post types should have the Content Studio AI meta box.', 'wp-content-studio-ai' ) . '</p>';
	}

	public function render_append_mode_field() {
		$append_mode = get_option( WGC_OPTION_APPEND_MODE, 'replace' );
		
		echo '<label style="display: block; margin: 5px 0;">';
		echo '<input type="radio" name="' . esc_attr( WGC_OPTION_APPEND_MODE ) . '" value="replace" ' . checked( $append_mode, 'replace', false ) . ' /> ';
		echo esc_html__( 'Replace existing content (recommended)', 'wp-content-studio-ai' );
		echo '</label>';
		
		echo '<label style="display: block; margin: 5px 0;">';
		echo '<input type="radio" name="' . esc_attr( WGC_OPTION_APPEND_MODE ) . '" value="append" ' . checked( $append_mode, 'append', false ) . ' /> ';
		echo esc_html__( 'Append to existing content', 'wp-content-studio-ai' );
		echo '</label>';
		
		echo '<p class="description">' . esc_html__( 'Choose how to handle existing content when generating new descriptions.', 'wp-content-studio-ai' ) . '</p>';
	}

	public function render_emoji_icons_field() {
		$emoji_icons = get_option( WGC_OPTION_EMOJI_ICONS, false );
		
		echo '<label style="display: block; margin: 5px 0;">';
		echo '<input type="checkbox" name="' . esc_attr( WGC_OPTION_EMOJI_ICONS ) . '" value="1" ' . checked( $emoji_icons, true, false ) . ' /> ';
		echo esc_html__( 'Enable emoji and icons in generated content', 'wp-content-studio-ai' );
		echo '</label>';
		
		echo '<p class="description">' . esc_html__( 'Add emoji and icons to make the content more engaging and visually appealing.', 'wp-content-studio-ai' ) . '</p>';
	}

	public function render_enable_meta_field() {
		$enabled = (bool) get_option( WGC_OPTION_ENABLE_META, false );
		echo '<label style="display:block;margin:5px 0;">';
		echo '<input type="checkbox" name="' . esc_attr( WGC_OPTION_ENABLE_META ) . '" value="1" ' . checked( $enabled, true, false ) . ' /> ';
		echo esc_html__( 'Enable meta description generation', 'wp-content-studio-ai' );
		echo '</label>';
		echo '<p class="description">' . esc_html__( 'Adds a button in the post editor to generate a 155–160 characters meta description.', 'wp-content-studio-ai' ) . '</p>';
	}

	public function render_enable_tags_field() {
		$enabled = (bool) get_option( WGC_OPTION_ENABLE_TAGS, false );
		echo '<label style="display:block;margin:5px 0;">';
		echo '<input type="checkbox" name="' . esc_attr( WGC_OPTION_ENABLE_TAGS ) . '" value="1" ' . checked( $enabled, true, false ) . ' /> ';
		echo esc_html__( 'Enable tags generation', 'wp-content-studio-ai' );
		echo '</label>';
		echo '<p class="description">' . esc_html__( 'Adds a button in the post editor to generate and assign suggested tags.', 'wp-content-studio-ai' ) . '</p>';
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$nonce = wp_create_nonce( 'wgc_bulk_nonce' );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Content Studio AI', 'wp-content-studio-ai' ) . '</h1>';

		echo '<form method="post" action="options.php">';
		settings_fields( 'wgc_settings_group' );
		do_settings_sections( 'wgc-settings' );
		submit_button( __( 'Save Settings', 'wp-content-studio-ai' ) );
		echo '</form>';

		// Security & Privacy section
		echo '<hr />';
		echo '<h2>' . esc_html__( 'Security & Privacy', 'wp-content-studio-ai' ) . '</h2>';
		echo '<ul style="list-style: disc; margin-left: 20px;">';
		echo '<li>' . esc_html__( 'API keys are stored only in WordPress options.', 'wp-content-studio-ai' ) . '</li>';
		echo '<li>' . esc_html__( 'Requests use HTTPS and are sent only when you trigger generation.', 'wp-content-studio-ai' ) . '</li>';
		echo '<li>' . esc_html__( 'Admin-only capabilities and nonces protect all AJAX operations.', 'wp-content-studio-ai' ) . '</li>';
		echo '</ul>';

		echo '<hr />';
		echo '<h2>' . esc_html__( 'Bulk Update Posts/Pages', 'wp-content-studio-ai' ) . '</h2>';
		echo '<p>' . esc_html__( 'Generate content, meta descriptions, and tags for multiple posts and pages. The tool will skip items already processed unless you force regenerate.', 'wp-content-studio-ai' ) . '</p>';
		echo '<p><label>' . esc_html__( 'Batch size (per request):', 'wp-content-studio-ai' ) . ' <input id="wgc-batch-size" type="number" min="1" max="20" value="5" /></label></p>';

		// Bulk post types selector (multi-select)
		$available_post_types = get_post_types( [ 'public' => true ], 'objects' );
		echo '<p><label for="wgc-bulk-post-types"><strong>' . esc_html__( 'Select post types to process:', 'wp-content-studio-ai' ) . '</strong></label></p>';
		echo '<select id="wgc-bulk-post-types" multiple size="6" style="min-width: 280px; max-width: 100%;">';
		foreach ( $available_post_types as $post_type ) {
			echo '<option value="' . esc_attr( $post_type->name ) . '">' . esc_html( $post_type->label ) . ' (' . esc_html( $post_type->name ) . ')</option>';
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Hold Cmd/Ctrl to select multiple types. If none selected, the saved setting under "Enable on Post Types" will be used.', 'wp-content-studio-ai' ) . '</p>';

		// What to generate selector
		echo '<p><label for="wgc-bulk-mode"><strong>' . esc_html__( 'What to generate:', 'wp-content-studio-ai' ) . '</strong></label><br />';
		echo '<select id="wgc-bulk-mode" style="min-width: 280px;">';
		echo '<option value="all">' . esc_html__( 'Generate All (Content + Meta + Tags)', 'wp-content-studio-ai' ) . '</option>';
		echo '<option value="content">' . esc_html__( 'Content Only', 'wp-content-studio-ai' ) . '</option>';
		echo '<option value="meta">' . esc_html__( 'Meta Descriptions Only', 'wp-content-studio-ai' ) . '</option>';
		echo '<option value="tags">' . esc_html__( 'Tags Only', 'wp-content-studio-ai' ) . '</option>';
		echo '</select></p>';
		
		echo '<p><label><input type="checkbox" id="wgc-force-regenerate" /> ' . esc_html__( 'Force regenerate (include already processed posts)', 'wp-content-studio-ai' ) . '</label></p>';
		echo '<p><button class="button button-primary" id="wgc-bulk-generate" data-nonce="' . esc_attr( $nonce ) . '">' . esc_html__( 'Run Bulk Generation', 'wp-content-studio-ai' ) . '</button> <span id="wgc-bulk-status"></span></p>';
		echo '</div>';
	}

	public function register_meta_box() {
		$post_types = get_option( WGC_OPTION_POST_TYPES, [ 'post', 'page' ] );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'wgc_meta_box',
				__( 'Content Studio AI', 'wp-content-studio-ai' ),
				[ $this, 'render_meta_box' ],
				$post_type,
				'side',
				'high'
			);
		}
	}

	public function render_meta_box( $post ) {
		$nonce = wp_create_nonce( 'wgc_single_nonce' );
		$generated_time = get_post_meta( $post->ID, '_wgc_generated', true );
		
		// Different message for products vs other post types
		if ( $post->post_type === 'product' ) {
			echo '<p>' . esc_html__( 'Generate a 2000+ character product description based on the title and insert it into the content.', 'wp-content-studio-ai' ) . '</p>';
		} else {
			echo '<p>' . esc_html__( 'Generate a 2000+ character description based on the title and insert it into the content.', 'wp-content-studio-ai' ) . '</p>';
		}
		// Different button text for products vs other post types
		if ( $post->post_type === 'product' ) {
			$button_text = __( 'Generate Product Description', 'wp-content-studio-ai' );
		} else {
			$button_text = __( 'Generate Long Description', 'wp-content-studio-ai' );
		}
		echo '<p><button type="button" class="button button-primary wgc-generate" data-post-id="' . esc_attr( (string) $post->ID ) . '" data-nonce="' . esc_attr( $nonce ) . '">' . esc_html( $button_text ) . '</button></p>';
		// Feature buttons: Meta & Tags
		if ( get_option( WGC_OPTION_ENABLE_META, false ) ) {
			echo '<p><button type="button" class="button wgc-generate-meta" data-post-id="' . esc_attr( (string) $post->ID ) . '" data-nonce="' . esc_attr( $nonce ) . '">' . esc_html__( 'Generate Meta Description', 'wp-content-studio-ai' ) . '</button></p>';
		}
		if ( get_option( WGC_OPTION_ENABLE_TAGS, false ) ) {
			echo '<p><button type="button" class="button wgc-generate-tags" data-post-id="' . esc_attr( (string) $post->ID ) . '" data-nonce="' . esc_attr( $nonce ) . '">' . esc_html__( 'Generate Tags', 'wp-content-studio-ai' ) . '</button></p>';
		}
		
		if ( $generated_time ) {
			echo '<div class="wgc-generated-info" style="background: #f0f8ff; border: 1px solid #0073aa; padding: 10px; margin: 10px 0; border-radius: 3px;">';
			echo '<p><strong>' . esc_html__( 'Last generated:', 'wp-content-studio-ai' ) . '</strong> ' . esc_html( $generated_time ) . '</p>';
			echo '</div>';
		}
		
		echo '<div id="wgc-preview" style="display: none; background: #f9f9f9; border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 3px; max-height: 300px; overflow-y: auto;">';
		echo '<h4>' . esc_html__( 'Generated Description Preview:', 'wp-content-studio-ai' ) . '</h4>';
		echo '<div id="wgc-preview-content" style="font-size: 13px; line-height: 1.4;"></div>';
		echo '</div>';
		
		echo '<div id="wgc-meta-preview" style="display:none; background:#f9f9f9; border:1px solid #ddd; padding:10px; margin:10px 0; border-radius:3px;">';
		echo '<h4>' . esc_html__( 'Generated Meta Description:', 'wp-content-studio-ai' ) . '</h4>';
		echo '<div id="wgc-meta-preview-content" style="font-size:13px; line-height:1.4;"></div>';
		echo '</div>';
		echo '<div id="wgc-tags-preview" style="display:none; background:#f9f9f9; border:1px solid #ddd; padding:10px; margin:10px 0; border-radius:3px;">';
		echo '<h4>' . esc_html__( 'Generated Tags:', 'wp-content-studio-ai' ) . '</h4>';
		echo '<div id="wgc-tags-preview-content" style="font-size:13px; line-height:1.4;"></div>';
		echo '</div>';
		
		$append_mode = get_option( WGC_OPTION_APPEND_MODE, 'replace' );
		if ( $append_mode === 'replace' ) {
			echo '<p class="description">' . esc_html__( 'Content will replace the existing content.', 'wp-content-studio-ai' ) . '</p>';
		} else {
			echo '<p class="description">' . esc_html__( 'Content will be appended to the end of the editor content.', 'wp-content-studio-ai' ) . '</p>';
		}
	}

	public function enqueue_admin_assets( $hook ) {
		// Load on post editor screens and our settings page only
		$screen = get_current_screen();
		$screen_id = is_object( $screen ) ? $screen->id : '';
		$post_types = get_option( WGC_OPTION_POST_TYPES, [ 'post', 'page' ] );
		
		// Build array of allowed screen IDs
		$allowed_screens = [ 'settings_page_wgc-settings' ];
		foreach ( $post_types as $post_type ) {
			$allowed_screens[] = $post_type;
		}
		
		if ( in_array( $screen_id, $allowed_screens, true ) ) {
			wp_enqueue_script( 'wgc-admin', WGC_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery' ], '1.0.0', true );
			wp_localize_script( 'wgc-admin', 'WGC', [
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'i18n'    => [
					'generating' => __( 'Generating...', 'wp-content-studio-ai' ),
					'done'       => __( 'Done', 'wp-content-studio-ai' ),
					'error'      => __( 'Error', 'wp-content-studio-ai' ),
				],
			] );
		}
	}

	private function get_api_key() {
		$new = (string) get_option( 'wgc_ai_studio_api_key', '' );
		if ( ! empty( $new ) ) {
			return $new;
		}
		return (string) get_option( WGC_OPTION_API_KEY, '' );
	}

	private function build_prompt_for_title( string $title, int $post_id = 0 ): string {
		$language = get_option( WGC_OPTION_LANGUAGE, 'en' );
		$language_names = [
			'en' => 'English',
			'it' => 'Italian', 
			'es' => 'Spanish',
			'fr' => 'French',
			'de' => 'German',
			'pt' => 'Portuguese',
			'ru' => 'Russian',
			'ja' => 'Japanese',
			'ko' => 'Korean',
			'zh' => 'Chinese',
			'ar' => 'Arabic',
			'hi' => 'Hindi',
		];
		$language_name = $language_names[ $language ] ?? 'English';
		
		// Check if this is a WooCommerce product
		$is_product = false;
		if ( $post_id > 0 ) {
			$post = get_post( $post_id );
			$is_product = $post && $post->post_type === 'product';
		}
		
		// Check if emoji and icons are enabled
		$emoji_icons_enabled = get_option( WGC_OPTION_EMOJI_ICONS, false );
		
		if ( $is_product ) {
			// Product-specific prompt
			$emoji_instruction = $emoji_icons_enabled ? '- Use relevant emoji and icons to make the content more engaging and visually appealing' : '- Do not use emoji or icons';
			
			$instructions = 'You are an expert e-commerce SEO writer. Write a comprehensive, SEO-friendly product description of at least 2,100 characters (not words) for the following product in ' . $language_name . '. 

Requirements:
- Write in ' . $language_name . ' language
- Focus on product features, benefits, and specifications
- Use HTML formatting with proper SEO structure
- Include H2 and H3 headings for product sections
- Use <strong> tags for important product keywords
- Use <em> tags for emphasis on key features
- Include <ul> and <li> for product features and benefits
- Use <p> tags for paragraphs
- Include relevant product keywords naturally
- Write in a persuasive, sales-oriented tone
- Do not include a title (H1)
- Do not mention that you are an AI
- Do not include any introductory text
- Do not include code blocks or markdown formatting
- Start directly with the product content
- Make it compelling for potential buyers
- Include product benefits, features, and use cases
' . $emoji_instruction . '

Product: ';
		} else {
			// General content prompt
			$emoji_instruction = $emoji_icons_enabled ? '- Use relevant emoji and icons to make the content more engaging and visually appealing' : '- Do not use emoji or icons';
			
			$instructions = 'You are an expert SEO content writer. Write a comprehensive, SEO-friendly long description of at least 2,100 characters (not words) about the following topic in ' . $language_name . '. 

Requirements:
- Write in ' . $language_name . ' language
- Use HTML formatting with proper SEO structure
- Include H2 and H3 headings for better SEO
- Use <strong> tags for important keywords
- Use <em> tags for emphasis
- Include <ul> and <li> for lists when appropriate
- Use <p> tags for paragraphs
- Include relevant keywords naturally
- Write in a professional, informative tone
- Do not include a title (H1)
- Do not mention that you are an AI
- Do not include any introductory text like "Here is a description" or "Ecco una descrizione"
- Do not include code blocks or markdown formatting
- Start directly with the content
- Make it engaging and valuable for readers
' . $emoji_instruction . '

Topic: ';
		}
		
		return $instructions . '"' . $title . '"';
	}

	private function call_gemini_generate( string $prompt ) {
		$api_key = $this->get_api_key();
		if ( empty( $api_key ) ) {
			return new WP_Error( 'wgc_missing_key', __( 'Missing API key. Please set it in settings.', 'wp-content-studio-ai' ) );
		}

		// Use the basic stable model
		$endpoints = [
			'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent'
		];
		$response = null;
		
		foreach ( $endpoints as $endpoint ) {
			$url = add_query_arg( 'key', rawurlencode( $api_key ), $endpoint );

			$body = [
				'contents' => [
					[
						'parts' => [ [ 'text' => $prompt ] ],
					],
				],
				'generationConfig' => [
					'temperature' => 0.7,
					'topK'       => 40,
					'topP'       => 0.95,
					'maxOutputTokens' => 2048, // generous output length
				],
			];

			$args = [
				'headers' => [ 'Content-Type' => 'application/json' ],
				'timeout' => 60,
				'body'    => wp_json_encode( $body ),
			];

			$response = wp_remote_post( $url, $args );
			if ( is_wp_error( $response ) ) {
				continue; // Try next model
			}

			$code = wp_remote_retrieve_response_code( $response );
			$json = json_decode( (string) wp_remote_retrieve_body( $response ), true );
			if ( $code >= 200 && $code < 300 ) {
				return is_array( $json ) ? $json : [];
			}
			
			// If model not found, try next model
			if ( $code === 404 ) {
				continue;
			}
		}

		// If all models failed, return the last error
		$message = isset( $json['error']['message'] ) ? (string) $json['error']['message'] : __( 'No available AI models found.', 'wp-content-studio-ai' );
		return new WP_Error( 'wgc_http_error', $message, [ 'status' => $code ] );
	}

	private function extract_text_from_gemini_response( array $json ): string {
		// Expected structure: candidates[0].content.parts[].text
		if ( empty( $json['candidates'][0]['content']['parts'] ) || ! is_array( $json['candidates'][0]['content']['parts'] ) ) {
			return '';
		}
		$parts = $json['candidates'][0]['content']['parts'];
		$texts = [];
		foreach ( $parts as $part ) {
			if ( isset( $part['text'] ) ) {
				$texts[] = (string) $part['text'];
			}
		}
		$content = trim( implode( "\n\n", $texts ) );
		
		// Remove introductory text patterns
		$content = $this->clean_introductory_text( $content );
		
		// Clean and validate HTML
		$content = wp_kses( $content, [
			'h2' => [],
			'h3' => [],
			'h4' => [],
			'p' => [],
			'strong' => [],
			'em' => [],
			'ul' => [],
			'ol' => [],
			'li' => [],
			'br' => [],
			'a' => [ 'href' => [], 'title' => [], 'target' => [] ],
			'span' => [ 'class' => [] ],
			'div' => [ 'class' => [] ]
		]);
		
		return $content;
	}

	private function clean_introductory_text( string $content ): string {
		// Remove common introductory patterns
		$patterns = [
			'/^.*?Ecco una descrizione lunga e SEO-friendly.*?formattata in HTML:\s*/is',
			'/^.*?Here is a long and SEO-friendly description.*?formatted in HTML:\s*/is',
			'/^.*?```html\s*/is',
			'/^.*?```\s*$/is',
			'/^.*?Here is a description.*?:\s*/is',
			'/^.*?Ecco una descrizione.*?:\s*/is',
			'/^.*?Here is.*?:\s*/is',
			'/^.*?Ecco.*?:\s*/is',
		];
		
		foreach ( $patterns as $pattern ) {
			$content = preg_replace( $pattern, '', $content );
		}
		
		// Remove any remaining markdown code blocks
		$content = preg_replace( '/```[a-z]*\s*/', '', $content );
		$content = preg_replace( '/```\s*$/', '', $content );
		
		// Trim and return
		return trim( $content );
	}

	private function append_content_to_post( int $post_id, string $new_content ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'wgc_missing_post', __( 'Post not found.', 'wp-content-studio-ai' ) );
		}

		$append_mode = get_option( WGC_OPTION_APPEND_MODE, 'replace' );
		
		if ( $append_mode === 'replace' ) {
			// Replace existing content
			$updated_content = $new_content;
		} else {
			// Append to existing content
			$separator = apply_filters( 'wgc_content_separator', "\n\n<!-- AI Generated Description -->\n\n" );
			$updated_content = (string) $post->post_content . $separator . $new_content;
		}

		$update_result = wp_update_post( [
			'ID'           => $post_id,
			'post_content' => $updated_content,
		], true );

		if ( is_wp_error( $update_result ) ) {
			return $update_result;
		}

		update_post_meta( $post_id, '_wgc_generated', current_time( 'mysql' ) );
		return (int) $update_result;
	}

	public function ajax_generate_for_post() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'wp-content-studio-ai' ) ], 403 );
		}
		check_ajax_referer( 'wgc_single_nonce', 'nonce' );

		$post_id = isset( $_POST['postId'] ) ? (int) $_POST['postId'] : 0;
		$title   = get_the_title( $post_id );
		if ( empty( $post_id ) || empty( $title ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid post.', 'wp-content-studio-ai' ) ], 400 );
		}

		$prompt = $this->build_prompt_for_title( $title, $post_id );
		$json   = $this->call_gemini_generate( $prompt );
		if ( is_wp_error( $json ) ) {
			wp_send_json_error( [ 'message' => $json->get_error_message() ], 500 );
		}

		$text = $this->extract_text_from_gemini_response( $json );
		if ( strlen( $text ) < 2000 ) {
			wp_send_json_error( [ 'message' => __( 'Generated text is shorter than 2000 characters. Try again.', 'wp-content-studio-ai' ) ], 422 );
		}

		$update = $this->append_content_to_post( $post_id, $text );
		if ( is_wp_error( $update ) ) {
			wp_send_json_error( [ 'message' => $update->get_error_message() ], 500 );
		}

		wp_send_json_success( [ 
			'message' => __( 'Content generated and inserted.', 'wp-content-studio-ai' ),
			'generated_content' => $text,
			'character_count' => strlen( $text )
		] );
	}

	public function ajax_generate_meta() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'wp-content-studio-ai' ) ], 403 );
		}
		check_ajax_referer( 'wgc_single_nonce', 'nonce' );

		$post_id = isset( $_POST['postId'] ) ? (int) $_POST['postId'] : 0;
		$title   = get_the_title( $post_id );
		if ( empty( $post_id ) || empty( $title ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid post.', 'wp-content-studio-ai' ) ], 400 );
		}
		$language = get_option( WGC_OPTION_LANGUAGE, 'en' );
		$language_names = [ 'en'=>'English','it'=>'Italian','es'=>'Spanish','fr'=>'French','de'=>'German','pt'=>'Portuguese','ru'=>'Russian','ja'=>'Japanese','ko'=>'Korean','zh'=>'Chinese','ar'=>'Arabic','hi'=>'Hindi' ];
		$language_name = $language_names[ $language ] ?? 'English';
		$prompt = 'You are an expert SEO copywriter. Write a concise meta description (max 160 characters) in ' . $language_name . ' for the following page title. It must be a single sentence, no quotes, no markdown, compelling and keyword-rich. Title: "' . $title . '"';
		$json   = $this->call_gemini_generate( $prompt );
		if ( is_wp_error( $json ) ) {
			wp_send_json_error( [ 'message' => $json->get_error_message() ], 500 );
		}
		$text = $this->extract_text_from_gemini_response( $json );
		$text = wp_strip_all_tags( $text );
		$text = trim( preg_replace( '/\s+/', ' ', $text ) );
		if ( strlen( $text ) > 160 ) { $text = mb_substr( $text, 0, 160 ); }
		update_post_meta( $post_id, '_wgc_meta_description', $text );
		if ( defined( 'WPSEO_VERSION' ) ) { update_post_meta( $post_id, '_yoast_wpseo_metadesc', $text ); }
		wp_send_json_success( [ 'message' => __( 'Meta description generated.', 'wp-content-studio-ai' ), 'meta_description' => $text ] );
	}

	public function ajax_generate_tags() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'wp-content-studio-ai' ) ], 403 );
		}
		check_ajax_referer( 'wgc_single_nonce', 'nonce' );

		$post_id = isset( $_POST['postId'] ) ? (int) $_POST['postId'] : 0;
		$title   = get_the_title( $post_id );
		if ( empty( $post_id ) || empty( $title ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid post.', 'wp-content-studio-ai' ) ], 400 );
		}
		$language = get_option( WGC_OPTION_LANGUAGE, 'en' );
		$language_names = [ 'en'=>'English','it'=>'Italian','es'=>'Spanish','fr'=>'French','de'=>'German','pt'=>'Portuguese','ru'=>'Russian','ja'=>'Japanese','ko'=>'Korean','zh'=>'Chinese','ar'=>'Arabic','hi'=>'Hindi' ];
		$language_name = $language_names[ $language ] ?? 'English';
		$prompt = 'Suggest 5 SEO-friendly tags in ' . $language_name . ' for this title. Return only comma-separated keywords, no extra text. Title: "' . $title . '"';
		$json   = $this->call_gemini_generate( $prompt );
		if ( is_wp_error( $json ) ) {
			wp_send_json_error( [ 'message' => $json->get_error_message() ], 500 );
		}
		$text = $this->extract_text_from_gemini_response( $json );
		$raw = strtolower( wp_strip_all_tags( $text ) );
		$parts = array_filter( array_map( 'trim', preg_split( '/[,\n]+/', $raw ) ) );
		$tags = [];
		foreach ( $parts as $p ) { $tags[] = sanitize_text_field( $p ); }
		if ( empty( $tags ) ) { wp_send_json_error( [ 'message' => __( 'No tags generated.', 'wp-content-studio-ai' ) ], 422 ); }
		wp_set_post_tags( $post_id, $tags, true );
		wp_send_json_success( [ 'message' => __( 'Tags generated and assigned.', 'wp-content-studio-ai' ), 'tags' => $tags ] );
	}

	public function ajax_bulk_generate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'wp-content-studio-ai' ) ], 403 );
		}
		check_ajax_referer( 'wgc_bulk_nonce', 'nonce' );

		$batch_size = isset( $_POST['batchSize'] ) ? max( 1, min( 20, (int) $_POST['batchSize'] ) ) : 5;
		$force_regenerate = isset( $_POST['forceRegenerate'] ) && ( $_POST['forceRegenerate'] === 'true' || $_POST['forceRegenerate'] === true );
		$mode = isset( $_POST['mode'] ) ? sanitize_text_field( $_POST['mode'] ) : 'all';
		if ( ! in_array( $mode, [ 'all', 'content', 'meta', 'tags' ], true ) ) {
			$mode = 'all';
		}

		// Use selected post types from request, falling back to plugin option
		$requested_types = isset( $_POST['postTypes'] ) ? (array) $_POST['postTypes'] : [];
		$requested_types = array_map( 'sanitize_text_field', $requested_types );
		$public_types = get_post_types( [ 'public' => true ], 'names' );
		$post_types = array_values( array_intersect( $requested_types, $public_types ) );
		if ( empty( $post_types ) ) {
			$post_types = get_option( WGC_OPTION_POST_TYPES, [ 'post', 'page' ] );
			$post_types = array_values( array_intersect( (array) $post_types, $public_types ) );
		}

		// Count all selected posts
		$args_all = [
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		];
		$all_query = new WP_Query( $args_all );
		$all_posts = $all_query->found_posts;

		// Derive total by mode (when possible)
		$total_posts = $all_posts;
		if ( ! $force_regenerate ) {
			if ( $mode === 'content' ) {
				$args = [
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'meta_query'     => [
						'relation' => 'OR',
						[ 'key' => '_wgc_generated', 'compare' => 'NOT EXISTS' ],
						[ 'key' => '_wgc_generated', 'value' => '', 'compare' => '=' ],
					],
				];
				$q = new WP_Query( $args );
				$total_posts = $q->found_posts;
			} elseif ( $mode === 'meta' ) {
				$args = [
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'meta_query'     => [
						'relation' => 'OR',
						[ 'key' => '_wgc_meta_description', 'compare' => 'NOT EXISTS' ],
						[ 'key' => '_wgc_meta_description', 'value' => '', 'compare' => '=' ],
					],
				];
				$q = new WP_Query( $args );
				$total_posts = $q->found_posts;
			} else {
				// tags or all: keep total as all posts
				$total_posts = $all_posts;
			}
		}

		if ( $total_posts === 0 ) {
			$debug_message = sprintf( 
				__( 'No posts found to process. Debug: Total posts in selected types: %d, Post types: %s', 'wp-content-studio-ai' ),
				$all_posts,
				implode( ', ', $post_types )
			);
			
			wp_send_json_success( [
				'job_started' => false,
				'message' => $debug_message,
				'total' => 0,
				'processed' => 0,
				'debug' => [
					'all_posts' => $all_posts,
					'post_types' => $post_types,
					'selected_types' => $requested_types,
					'mode' => $mode,
				],
			] );
		}

		// Store job parameters
		$job_id = 'wgc_bulk_' . time();
		update_option( 'wgc_bulk_job_' . $job_id, [
			'post_types' => $post_types,
			'batch_size' => $batch_size,
			'total_posts' => $total_posts,
			'processed' => 0,
			'errors' => [],
			'status' => 'running',
			'started_at' => current_time( 'mysql' ),
			'force_regenerate' => $force_regenerate,
			'mode' => $mode,
		] );

		// Schedule the job and process first batch immediately (cron fallback)
		wp_schedule_single_event( time(), 'wgc_bulk_process_job', [ $job_id ] );
		$this->process_bulk_job( $job_id );

		$job_data_after = get_option( 'wgc_bulk_job_' . $job_id );

		wp_send_json_success( [
			'job_started' => true,
			'job_id' => $job_id,
			'message' => __( 'Bulk generation job started. Processing in background...', 'wp-content-studio-ai' ),
			'total' => $total_posts,
			'processed' => isset( $job_data_after['processed'] ) ? (int) $job_data_after['processed'] : 0,
		] );
	}

	public function ajax_bulk_status() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'wp-content-studio-ai' ) ], 403 );
		}
		check_ajax_referer( 'wgc_bulk_nonce', 'nonce' );

		$job_id = isset( $_POST['jobId'] ) ? sanitize_text_field( $_POST['jobId'] ) : '';
		if ( empty( $job_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Job ID required.', 'wp-content-studio-ai' ) ], 400 );
		}

		$job_data = get_option( 'wgc_bulk_job_' . $job_id, null );
		if ( ! $job_data ) {
			wp_send_json_error( [ 'message' => __( 'Job not found.', 'wp-content-studio-ai' ) ], 404 );
		}

		wp_send_json_success( $job_data );
	}

	public function schedule_bulk_job() {
		// Check if there are any pending jobs and schedule them
		global $wpdb;
		$jobs = $wpdb->get_results( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'wgc_bulk_job_%' AND option_value LIKE '%\"status\":\"running\"%'" );
		
		foreach ( $jobs as $job ) {
			$job_id = str_replace( 'wgc_bulk_job_', '', $job->option_name );
			if ( ! wp_next_scheduled( 'wgc_bulk_process_job', [ $job_id ] ) ) {
				wp_schedule_single_event( time(), 'wgc_bulk_process_job', [ $job_id ] );
			}
		}
	}

	private function ensure_api_key_autoload_no() {
		global $wpdb;
		$value = get_option( WGC_OPTION_API_KEY, null );
		if ( $value === null ) {
			return;
		}
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", WGC_OPTION_API_KEY ) );
		if ( $row && isset( $row->autoload ) && $row->autoload !== 'no' ) {
			delete_option( WGC_OPTION_API_KEY );
			add_option( WGC_OPTION_API_KEY, $value, '', 'no' );
		}
	}

	public function process_bulk_job( $job_id ) {
		$job_data = get_option( 'wgc_bulk_job_' . $job_id, null );
		if ( ! $job_data || $job_data['status'] !== 'running' ) {
			return;
		}

		$post_types = $job_data['post_types'];
		$batch_size = $job_data['batch_size'];
		$processed = isset( $job_data['processed'] ) ? (int) $job_data['processed'] : 0;
		$errors = $job_data['errors'];
		$force = ! empty( $job_data['force_regenerate'] );
		$mode  = isset( $job_data['mode'] ) ? $job_data['mode'] : 'all';

		// Get next batch of posts
		$args = [
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => $batch_size,
			'offset'         => $processed,
			'orderby'        => 'date',
			'order'          => 'DESC',
		];
		
		// Add meta filtering per mode when not forcing regeneration
		if ( ! $force ) {
			if ( $mode === 'content' ) {
				$args['meta_query'] = [
					'relation' => 'OR',
					[ 'key' => '_wgc_generated', 'compare' => 'NOT EXISTS' ],
					[ 'key' => '_wgc_generated', 'value' => '', 'compare' => '=' ],
				];
			} elseif ( $mode === 'meta' ) {
				$args['meta_query'] = [
					'relation' => 'OR',
					[ 'key' => '_wgc_meta_description', 'compare' => 'NOT EXISTS' ],
					[ 'key' => '_wgc_meta_description', 'value' => '', 'compare' => '=' ],
				];
			}
		}

		$q = new WP_Query( $args );
		$batch_scanned = 0;

		if ( $q->have_posts() ) {
			foreach ( $q->posts as $post ) {
				$batch_scanned++;
				$ran_any = false;

				$do_content = ( $mode === 'all' || $mode === 'content' );
				$do_meta    = ( $mode === 'all' || $mode === 'meta' );
				$do_tags    = ( $mode === 'all' || $mode === 'tags' );

				if ( $do_content ) {
					$already = get_post_meta( (int) $post->ID, '_wgc_generated', true );
					if ( $force || empty( $already ) ) {
						$title = get_the_title( $post );
						if ( ! empty( $title ) ) {
							$prompt = $this->build_prompt_for_title( $title, (int) $post->ID );
							$json   = $this->call_gemini_generate( $prompt );
							if ( is_wp_error( $json ) ) {
								$errors[] = $json->get_error_message();
							} else {
								$text = $this->extract_text_from_gemini_response( $json );
								if ( strlen( $text ) < 2000 ) {
									$errors[] = sprintf( __( 'Post %d generated text too short.', 'wp-content-studio-ai' ), (int) $post->ID );
								} else {
									$update = $this->append_content_to_post( (int) $post->ID, $text );
									if ( is_wp_error( $update ) ) {
										$errors[] = $update->get_error_message();
									} else {
										$ran_any = true;
									}
								}
							}
						}
					}
				}

				if ( $do_meta ) {
					$existing_meta = get_post_meta( (int) $post->ID, '_wgc_meta_description', true );
					if ( $force || empty( $existing_meta ) ) {
						$language = get_option( WGC_OPTION_LANGUAGE, 'en' );
						$language_names = [ 'en'=>'English','it'=>'Italian','es'=>'Spanish','fr'=>'French','de'=>'German','pt'=>'Portuguese','ru'=>'Russian','ja'=>'Japanese','ko'=>'Korean','zh'=>'Chinese','ar'=>'Arabic','hi'=>'Hindi' ];
						$language_name = $language_names[ $language ] ?? 'English';
						$meta_prompt = 'You are an expert SEO copywriter. Write a concise meta description (max 160 characters) in ' . $language_name . ' for the following page title. It must be a single sentence, no quotes, no markdown, compelling and keyword-rich. Title: "' . get_the_title( $post ) . '"';
						$json   = $this->call_gemini_generate( $meta_prompt );
						if ( is_wp_error( $json ) ) {
							$errors[] = $json->get_error_message();
						} else {
							$text = $this->extract_text_from_gemini_response( $json );
							$text = wp_strip_all_tags( $text );
							$text = trim( preg_replace( '/\s+/', ' ', $text ) );
							if ( strlen( $text ) > 160 ) { $text = mb_substr( $text, 0, 160 ); }
							update_post_meta( (int) $post->ID, '_wgc_meta_description', $text );
							if ( defined( 'WPSEO_VERSION' ) ) { update_post_meta( (int) $post->ID, '_yoast_wpseo_metadesc', $text ); }
							$ran_any = true;
						}
					}
				}

				if ( $do_tags ) {
					$has_existing_tags = false;
					if ( ! $force ) {
						$existing_terms = wp_get_post_terms( (int) $post->ID, 'post_tag', [ 'fields' => 'ids' ] );
						$has_existing_tags = ! empty( $existing_terms );
					}
					if ( $force || ! $has_existing_tags ) {
						$language = get_option( WGC_OPTION_LANGUAGE, 'en' );
						$language_names = [ 'en'=>'English','it'=>'Italian','es'=>'Spanish','fr'=>'French','de'=>'German','pt'=>'Portuguese','ru'=>'Russian','ja'=>'Japanese','ko'=>'Korean','zh'=>'Chinese','ar'=>'Arabic','hi'=>'Hindi' ];
						$language_name = $language_names[ $language ] ?? 'English';
						$tags_prompt = 'Suggest 5 SEO-friendly tags in ' . $language_name . ' for this title. Return only comma-separated keywords, no extra text. Title: "' . get_the_title( $post ) . '"';
						$json   = $this->call_gemini_generate( $tags_prompt );
						if ( is_wp_error( $json ) ) {
							$errors[] = $json->get_error_message();
						} else {
							$text = $this->extract_text_from_gemini_response( $json );
							$raw = strtolower( wp_strip_all_tags( $text ) );
							$parts = array_filter( array_map( 'trim', preg_split( '/[,\n]+/', $raw ) ) );
							$tags = [];
							foreach ( $parts as $p ) { $tags[] = sanitize_text_field( $p ); }
							if ( ! empty( $tags ) ) { wp_set_post_tags( (int) $post->ID, $tags, true ); $ran_any = true; } else { $errors[] = sprintf( __( 'No tags generated for post %d.', 'wp-content-studio-ai' ), (int) $post->ID ); }
						}
					}
				}

				// If we wanted to track updated count separately, we could, but for progress we use scanned count
			}
		}

		// Update job data
		$new_processed = $processed + $batch_scanned;
		$is_complete = $new_processed >= (int) $job_data['total_posts'] || $batch_scanned === 0;

		$job_data['processed'] = $new_processed;
		$job_data['errors'] = $errors;
		$job_data['status'] = $is_complete ? 'completed' : 'running';
		$job_data['completed_at'] = $is_complete ? current_time( 'mysql' ) : null;

		update_option( 'wgc_bulk_job_' . $job_id, $job_data );

		// Schedule next batch if not complete
		if ( ! $is_complete ) {
			wp_schedule_single_event( time() + 30, 'wgc_bulk_process_job', [ $job_id ] );
		}
	}
}

if ( ! class_exists( 'WPCSAI_ContentGenerator' ) && class_exists( 'WPGeminiContentGenerator' ) ) { class_alias( 'WPGeminiContentGenerator', 'WPCSAI_ContentGenerator' ); }

new WPGeminiContentGenerator();




register_deactivation_hook( __FILE__, 'wgc_on_deactivate' );
function wgc_on_deactivate() {
	// Clear potential scheduled hooks if used
	if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
		wp_clear_scheduled_hook( 'wgc_bulk_process_job' );
	}
	// Cleanup any stored bulk job markers
	global $wpdb;
	if ( isset( $wpdb ) && property_exists( $wpdb, 'options' ) ) {
		$table = $wpdb->options;
		$wpdb->query( "DELETE FROM {$table} WHERE option_name LIKE 'wgc_bulk_job_%'" );
	}
}




register_activation_hook( __FILE__, 'wgc_on_activate' );
function wgc_on_activate() {
	// Ensure API key option exists and is not autoloaded
	add_option( WGC_OPTION_API_KEY, '', '', 'no' );
}



