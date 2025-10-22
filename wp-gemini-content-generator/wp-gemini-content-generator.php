<?php
/**
 * Plugin Name:       WP Gemini Content Generator
 * Description:       Generate 2000+ character descriptions for posts/pages using the Gemini API. Includes settings to store API key and bulk update.
 * Version:           1.0.0
 * Author:            WP Gemini Content Generator
 * Text Domain:       wp-gemini-content-generator
 * Domain Path:       /languages
 * Requires at least: 6.0
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

class WPGeminiContentGenerator {
	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		// AJAX actions for single generate and bulk generate
		add_action( 'wp_ajax_wgc_generate_for_post', [ $this, 'ajax_generate_for_post' ] );
		add_action( 'wp_ajax_wgc_bulk_generate', [ $this, 'ajax_bulk_generate' ] );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'wp-gemini-content-generator', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public function register_settings_page() {
		add_options_page(
			__( 'Gemini Content Generator', 'wp-gemini-content-generator' ),
			__( 'Gemini Generator', 'wp-gemini-content-generator' ),
			'manage_options',
			'wgc-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	public function register_settings() {
		register_setting( 'wgc_settings_group', WGC_OPTION_API_KEY, [
			'type'              => 'string',
			'sanitize_callback' => [ $this, 'sanitize_api_key' ],
			'default'           => '',
		] );

		register_setting( 'wgc_settings_group', WGC_OPTION_LANGUAGE, [
			'type'              => 'string',
			'sanitize_callback' => [ $this, 'sanitize_language' ],
			'default'           => 'en',
		] );

		register_setting( 'wgc_settings_group', WGC_OPTION_POST_TYPES, [
			'type'              => 'array',
			'sanitize_callback' => [ $this, 'sanitize_post_types' ],
			'default'           => [ 'post', 'page' ],
		] );

		register_setting( 'wgc_settings_group', WGC_OPTION_APPEND_MODE, [
			'type'              => 'string',
			'sanitize_callback' => [ $this, 'sanitize_append_mode' ],
			'default'           => 'replace',
		] );

		register_setting( 'wgc_settings_group', WGC_OPTION_EMOJI_ICONS, [
			'type'              => 'boolean',
			'sanitize_callback' => [ $this, 'sanitize_emoji_icons' ],
			'default'           => false,
		] );

		add_settings_section(
			'wgc_api_section',
			__( 'API Settings', 'wp-gemini-content-generator' ),
			function () {
				echo '<p>' . esc_html__( 'Configure your Gemini API key to enable content generation.', 'wp-gemini-content-generator' ) . '</p>';
			},
			'wgc-settings'
		);

		add_settings_field(
			'wgc_api_key',
			__( 'Gemini API Key', 'wp-gemini-content-generator' ),
			[ $this, 'render_api_key_field' ],
			'wgc-settings',
			'wgc_api_section'
		);

		add_settings_field(
			'wgc_language',
			__( 'Content Language', 'wp-gemini-content-generator' ),
			[ $this, 'render_language_field' ],
			'wgc-settings',
			'wgc_api_section'
		);

		add_settings_field(
			'wgc_post_types',
			__( 'Enable on Post Types', 'wp-gemini-content-generator' ),
			[ $this, 'render_post_types_field' ],
			'wgc-settings',
			'wgc_api_section'
		);

		add_settings_field(
			'wgc_append_mode',
			__( 'Content Mode', 'wp-gemini-content-generator' ),
			[ $this, 'render_append_mode_field' ],
			'wgc-settings',
			'wgc_api_section'
		);

		add_settings_field(
			'wgc_emoji_icons',
			__( 'Emoji & Icons', 'wp-gemini-content-generator' ),
			[ $this, 'render_emoji_icons_field' ],
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
		$api_key = get_option( WGC_OPTION_API_KEY, '' );
		echo '<input type="password" style="width: 420px;" name="' . esc_attr( WGC_OPTION_API_KEY ) . '" value="' . esc_attr( $api_key ) . '" placeholder="AIza..." />';
		echo '<p class="description">' . esc_html__( 'Your Google Gemini API key. Stored in WordPress options.', 'wp-gemini-content-generator' ) . '</p>';
		echo '<p class="description"><a href="https://aistudio.google.com/u/3/api-keys" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Create your Gemini API Key here', 'wp-gemini-content-generator' ) . '</a> 🔗</p>';
	}

	public function render_language_field() {
		$language = get_option( WGC_OPTION_LANGUAGE, 'en' );
		$languages = [
			'en' => __( 'English', 'wp-gemini-content-generator' ),
			'it' => __( 'Italian', 'wp-gemini-content-generator' ),
			'es' => __( 'Spanish', 'wp-gemini-content-generator' ),
			'fr' => __( 'French', 'wp-gemini-content-generator' ),
			'de' => __( 'German', 'wp-gemini-content-generator' ),
			'pt' => __( 'Portuguese', 'wp-gemini-content-generator' ),
			'ru' => __( 'Russian', 'wp-gemini-content-generator' ),
			'ja' => __( 'Japanese', 'wp-gemini-content-generator' ),
			'ko' => __( 'Korean', 'wp-gemini-content-generator' ),
			'zh' => __( 'Chinese', 'wp-gemini-content-generator' ),
			'ar' => __( 'Arabic', 'wp-gemini-content-generator' ),
			'hi' => __( 'Hindi', 'wp-gemini-content-generator' ),
		];
		
		echo '<select name="' . esc_attr( WGC_OPTION_LANGUAGE ) . '" style="width: 200px;">';
		foreach ( $languages as $code => $name ) {
			$selected = selected( $language, $code, false );
			echo '<option value="' . esc_attr( $code ) . '"' . $selected . '>' . esc_html( $name ) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Select the language for generated content.', 'wp-gemini-content-generator' ) . '</p>';
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
		echo '<p class="description">' . esc_html__( 'Select which post types should have the Gemini Content Generator meta box.', 'wp-gemini-content-generator' ) . '</p>';
	}

	public function render_append_mode_field() {
		$append_mode = get_option( WGC_OPTION_APPEND_MODE, 'replace' );
		
		echo '<label style="display: block; margin: 5px 0;">';
		echo '<input type="radio" name="' . esc_attr( WGC_OPTION_APPEND_MODE ) . '" value="replace" ' . checked( $append_mode, 'replace', false ) . ' /> ';
		echo esc_html__( 'Replace existing content (recommended)', 'wp-gemini-content-generator' );
		echo '</label>';
		
		echo '<label style="display: block; margin: 5px 0;">';
		echo '<input type="radio" name="' . esc_attr( WGC_OPTION_APPEND_MODE ) . '" value="append" ' . checked( $append_mode, 'append', false ) . ' /> ';
		echo esc_html__( 'Append to existing content', 'wp-gemini-content-generator' );
		echo '</label>';
		
		echo '<p class="description">' . esc_html__( 'Choose how to handle existing content when generating new descriptions.', 'wp-gemini-content-generator' ) . '</p>';
	}

	public function render_emoji_icons_field() {
		$emoji_icons = get_option( WGC_OPTION_EMOJI_ICONS, false );
		
		echo '<label style="display: block; margin: 5px 0;">';
		echo '<input type="checkbox" name="' . esc_attr( WGC_OPTION_EMOJI_ICONS ) . '" value="1" ' . checked( $emoji_icons, true, false ) . ' /> ';
		echo esc_html__( 'Enable emoji and icons in generated content', 'wp-gemini-content-generator' );
		echo '</label>';
		
		echo '<p class="description">' . esc_html__( 'Add emoji and icons to make the content more engaging and visually appealing.', 'wp-gemini-content-generator' ) . '</p>';
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$nonce = wp_create_nonce( 'wgc_bulk_nonce' );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Gemini Content Generator', 'wp-gemini-content-generator' ) . '</h1>';

		echo '<form method="post" action="options.php">';
		settings_fields( 'wgc_settings_group' );
		do_settings_sections( 'wgc-settings' );
		submit_button( __( 'Save Settings', 'wp-gemini-content-generator' ) );
		echo '</form>';

		echo '<hr />';
		echo '<h2>' . esc_html__( 'Bulk Update Posts/Pages', 'wp-gemini-content-generator' ) . '</h2>';
		echo '<p>' . esc_html__( 'Generate and insert long descriptions for multiple posts and pages. The tool will skip items already processed.', 'wp-gemini-content-generator' ) . '</p>';
		echo '<p><label>' . esc_html__( 'Batch size (per request):', 'wp-gemini-content-generator' ) . ' <input id="wgc-batch-size" type="number" min="1" max="20" value="5" /></label></p>';
		echo '<p><button class="button button-primary" id="wgc-bulk-generate" data-nonce="' . esc_attr( $nonce ) . '">' . esc_html__( 'Run Bulk Generation', 'wp-gemini-content-generator' ) . '</button> <span id="wgc-bulk-status"></span></p>';
		echo '</div>';
	}

	public function register_meta_box() {
		$post_types = get_option( WGC_OPTION_POST_TYPES, [ 'post', 'page' ] );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'wgc_meta_box',
				__( 'Gemini Content Generator', 'wp-gemini-content-generator' ),
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
			echo '<p>' . esc_html__( 'Generate a 2000+ character product description based on the title and insert it into the content.', 'wp-gemini-content-generator' ) . '</p>';
		} else {
			echo '<p>' . esc_html__( 'Generate a 2000+ character description based on the title and insert it into the content.', 'wp-gemini-content-generator' ) . '</p>';
		}
		// Different button text for products vs other post types
		if ( $post->post_type === 'product' ) {
			$button_text = __( 'Generate Product Description', 'wp-gemini-content-generator' );
		} else {
			$button_text = __( 'Generate Long Description', 'wp-gemini-content-generator' );
		}
		echo '<p><button type="button" class="button button-primary wgc-generate" data-post-id="' . esc_attr( (string) $post->ID ) . '" data-nonce="' . esc_attr( $nonce ) . '">' . esc_html( $button_text ) . '</button></p>';
		
		if ( $generated_time ) {
			echo '<div class="wgc-generated-info" style="background: #f0f8ff; border: 1px solid #0073aa; padding: 10px; margin: 10px 0; border-radius: 3px;">';
			echo '<p><strong>' . esc_html__( 'Last generated:', 'wp-gemini-content-generator' ) . '</strong> ' . esc_html( $generated_time ) . '</p>';
			echo '</div>';
		}
		
		echo '<div id="wgc-preview" style="display: none; background: #f9f9f9; border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 3px; max-height: 300px; overflow-y: auto;">';
		echo '<h4>' . esc_html__( 'Generated Description Preview:', 'wp-gemini-content-generator' ) . '</h4>';
		echo '<div id="wgc-preview-content" style="font-size: 13px; line-height: 1.4;"></div>';
		echo '</div>';
		
		$append_mode = get_option( WGC_OPTION_APPEND_MODE, 'replace' );
		if ( $append_mode === 'replace' ) {
			echo '<p class="description">' . esc_html__( 'Content will replace the existing content.', 'wp-gemini-content-generator' ) . '</p>';
		} else {
			echo '<p class="description">' . esc_html__( 'Content will be appended to the end of the editor content.', 'wp-gemini-content-generator' ) . '</p>';
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
					'generating' => __( 'Generating...', 'wp-gemini-content-generator' ),
					'done'       => __( 'Done', 'wp-gemini-content-generator' ),
					'error'      => __( 'Error', 'wp-gemini-content-generator' ),
				],
			] );
		}
	}

	private function get_api_key() {
		$api_key = (string) get_option( WGC_OPTION_API_KEY, '' );
		return $api_key;
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
			return new WP_Error( 'wgc_missing_key', __( 'Missing Gemini API key. Please set it in settings.', 'wp-gemini-content-generator' ) );
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
		$message = isset( $json['error']['message'] ) ? (string) $json['error']['message'] : __( 'No available Gemini models found.', 'wp-gemini-content-generator' );
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
			return new WP_Error( 'wgc_missing_post', __( 'Post not found.', 'wp-gemini-content-generator' ) );
		}

		$append_mode = get_option( WGC_OPTION_APPEND_MODE, 'replace' );
		
		if ( $append_mode === 'replace' ) {
			// Replace existing content
			$updated_content = $new_content;
		} else {
			// Append to existing content
			$separator = apply_filters( 'wgc_content_separator', "\n\n<!-- Gemini Generated Description -->\n\n" );
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
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'wp-gemini-content-generator' ) ], 403 );
		}
		check_ajax_referer( 'wgc_single_nonce', 'nonce' );

		$post_id = isset( $_POST['postId'] ) ? (int) $_POST['postId'] : 0;
		$title   = get_the_title( $post_id );
		if ( empty( $post_id ) || empty( $title ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid post.', 'wp-gemini-content-generator' ) ], 400 );
		}

		$prompt = $this->build_prompt_for_title( $title, $post_id );
		$json   = $this->call_gemini_generate( $prompt );
		if ( is_wp_error( $json ) ) {
			wp_send_json_error( [ 'message' => $json->get_error_message() ], 500 );
		}

		$text = $this->extract_text_from_gemini_response( $json );
		if ( strlen( $text ) < 2000 ) {
			wp_send_json_error( [ 'message' => __( 'Generated text is shorter than 2000 characters. Try again.', 'wp-gemini-content-generator' ) ], 422 );
		}

		$update = $this->append_content_to_post( $post_id, $text );
		if ( is_wp_error( $update ) ) {
			wp_send_json_error( [ 'message' => $update->get_error_message() ], 500 );
		}

		wp_send_json_success( [ 
			'message' => __( 'Content generated and inserted.', 'wp-gemini-content-generator' ),
			'generated_content' => $text,
			'character_count' => strlen( $text )
		] );
	}

	public function ajax_bulk_generate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'wp-gemini-content-generator' ) ], 403 );
		}
		check_ajax_referer( 'wgc_bulk_nonce', 'nonce' );

		$batch_size = isset( $_POST['batchSize'] ) ? max( 1, min( 20, (int) $_POST['batchSize'] ) ) : 5;

		$post_types = get_option( WGC_OPTION_POST_TYPES, [ 'post', 'page' ] );
		$args = [
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => $batch_size,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => [
				'relation' => 'OR',
				[ 'key' => '_wgc_generated', 'compare' => 'NOT EXISTS' ],
				[ 'key' => '_wgc_generated', 'value' => '', 'compare' => '=' ],
			],
		];

		$q = new WP_Query( $args );
		$count_processed = 0;
		$errors = [];
		if ( $q->have_posts() ) {
			foreach ( $q->posts as $post ) {
				$title = get_the_title( $post );
				if ( empty( $title ) ) {
					continue;
				}
				$prompt = $this->build_prompt_for_title( $title, (int) $post->ID );
				$json   = $this->call_gemini_generate( $prompt );
				if ( is_wp_error( $json ) ) {
					$errors[] = $json->get_error_message();
					continue;
				}
				$text = $this->extract_text_from_gemini_response( $json );
				if ( strlen( $text ) < 2000 ) {
					$errors[] = sprintf( /* translators: %d: Post ID */ __( 'Post %d generated text too short.', 'wp-gemini-content-generator' ), (int) $post->ID );
					continue;
				}
				$update = $this->append_content_to_post( (int) $post->ID, $text );
				if ( is_wp_error( $update ) ) {
					$errors[] = $update->get_error_message();
					continue;
				}
				$count_processed++;
			}
		}
		wp_send_json_success( [
			'processed' => $count_processed,
			'errors'    => $errors,
		] );
	}
}

new WPGeminiContentGenerator();



