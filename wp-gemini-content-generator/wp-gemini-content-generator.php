<?php
/**
 * Plugin Name:       WP Gemini Content Generator
 * Description:       Generate AI-powered SEO content including descriptions, meta descriptions, tags, and more using Google Gemini API. Professional WordPress plugin with advanced features.
 * Version:           1.0.0
 * Author:            WP Gemini Content Generator
 * Text Domain:       wp-gemini-content-generator
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Network:           false
 * Update URI:        false
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WGC_PLUGIN_FILE', __FILE__ );
define( 'WGC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WGC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WGC_VERSION', '1.0.0' );

// Options constants
define( 'WGC_OPTION_API_KEY', 'wgc_gemini_api_key' );
define( 'WGC_OPTION_LANGUAGE', 'wgc_language' );
define( 'WGC_OPTION_POST_TYPES', 'wgc_post_types' );
define( 'WGC_OPTION_APPEND_MODE', 'wgc_append_mode' );
define( 'WGC_OPTION_EMOJI_ICONS', 'wgc_emoji_icons' );
define( 'WGC_OPTION_META_DESCRIPTION', 'wgc_meta_description' );
define( 'WGC_OPTION_GENERATE_TAGS', 'wgc_generate_tags' );
define( 'WGC_OPTION_GENERATE_EXCERPT', 'wgc_generate_excerpt' );
define( 'WGC_OPTION_EXCERPT_LENGTH', 'wgc_excerpt_length' );
define( 'WGC_OPTION_BATCH_SIZE', 'wgc_batch_size' );
define( 'WGC_OPTION_CONTENT_LENGTH', 'wgc_content_length' );
define( 'WGC_OPTION_SEO_FOCUS', 'wgc_seo_focus' );

class WPGeminiContentGenerator {
	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_gutenberg_assets' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

		// AJAX actions
		add_action( 'wp_ajax_wgc_generate_for_post', [ $this, 'ajax_generate_for_post' ] );
		add_action( 'wp_ajax_wgc_bulk_generate', [ $this, 'ajax_bulk_generate' ] );
		add_action( 'wp_ajax_wgc_bulk_status', [ $this, 'ajax_bulk_status' ] );
		add_action( 'wp_ajax_wgc_generate_meta_description', [ $this, 'ajax_generate_meta_description' ] );
		add_action( 'wp_ajax_wgc_generate_tags', [ $this, 'ajax_generate_tags' ] );
		add_action( 'wp_ajax_wgc_generate_excerpt', [ $this, 'ajax_generate_excerpt' ] );
		add_action( 'wp_ajax_wgc_generate_all', [ $this, 'ajax_generate_all' ] );
		
		// Cron job for background processing
		add_action( 'wgc_bulk_process_job', [ $this, 'process_bulk_job' ] );
		add_action( 'init', [ $this, 'schedule_bulk_job' ] );

		// Add admin notices
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );
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
		// API Key setting
		register_setting( 'wgc_settings', WGC_OPTION_API_KEY, [ $this, 'sanitize_api_key' ] );
		add_settings_field(
			WGC_OPTION_API_KEY,
			__( 'Gemini API Key', 'wp-gemini-content-generator' ),
			[ $this, 'render_api_key_field' ],
			'wgc_settings',
			'wgc_main_section'
		);

		// Language setting
		register_setting( 'wgc_settings', WGC_OPTION_LANGUAGE, [ $this, 'sanitize_language' ] );
		add_settings_field(
			WGC_OPTION_LANGUAGE,
			__( 'Content Language', 'wp-gemini-content-generator' ),
			[ $this, 'render_language_field' ],
			'wgc_settings',
			'wgc_main_section'
		);

		// Post Types setting
		register_setting( 'wgc_settings', WGC_OPTION_POST_TYPES, [ $this, 'sanitize_post_types' ] );
		add_settings_field(
			WGC_OPTION_POST_TYPES,
			__( 'Active Post Types', 'wp-gemini-content-generator' ),
			[ $this, 'render_post_types_field' ],
			'wgc_settings',
			'wgc_main_section'
		);

		// Content Length setting
		register_setting( 'wgc_settings', WGC_OPTION_CONTENT_LENGTH, [ $this, 'sanitize_content_length' ] );
		add_settings_field(
			WGC_OPTION_CONTENT_LENGTH,
			__( 'Content Length', 'wp-gemini-content-generator' ),
			[ $this, 'render_content_length_field' ],
			'wgc_settings',
			'wgc_content_section'
		);

		// Append Mode setting
		register_setting( 'wgc_settings', WGC_OPTION_APPEND_MODE, [ $this, 'sanitize_append_mode' ] );
		add_settings_field(
			WGC_OPTION_APPEND_MODE,
			__( 'Content Mode', 'wp-gemini-content-generator' ),
			[ $this, 'render_append_mode_field' ],
			'wgc_settings',
			'wgc_content_section'
		);

		// Emoji/Icons setting
		register_setting( 'wgc_settings', WGC_OPTION_EMOJI_ICONS, [ $this, 'sanitize_emoji_icons' ] );
		add_settings_field(
			WGC_OPTION_EMOJI_ICONS,
			__( 'Include Emojis & Icons', 'wp-gemini-content-generator' ),
			[ $this, 'render_emoji_icons_field' ],
			'wgc_settings',
			'wgc_content_section'
		);

		// Meta Description setting
		register_setting( 'wgc_settings', WGC_OPTION_META_DESCRIPTION, [ $this, 'sanitize_meta_description' ] );
		add_settings_field(
			WGC_OPTION_META_DESCRIPTION,
			__( 'Generate Meta Descriptions', 'wp-gemini-content-generator' ),
			[ $this, 'render_meta_description_field' ],
			'wgc_settings',
			'wgc_seo_section'
		);

		// Generate Tags setting
		register_setting( 'wgc_settings', WGC_OPTION_GENERATE_TAGS, [ $this, 'sanitize_generate_tags' ] );
		add_settings_field(
			WGC_OPTION_GENERATE_TAGS,
			__( 'Generate Tags', 'wp-gemini-content-generator' ),
			[ $this, 'render_generate_tags_field' ],
			'wgc_settings',
			'wgc_seo_section'
		);

		// Generate Excerpt setting
		register_setting( 'wgc_settings', WGC_OPTION_GENERATE_EXCERPT, [ $this, 'sanitize_generate_excerpt' ] );
		add_settings_field(
			WGC_OPTION_GENERATE_EXCERPT,
			__( 'Generate Excerpt', 'wp-gemini-content-generator' ),
			[ $this, 'render_generate_excerpt_field' ],
			'wgc_settings',
			'wgc_seo_section'
		);

		// Excerpt Length setting
		register_setting( 'wgc_settings', WGC_OPTION_EXCERPT_LENGTH, [ $this, 'sanitize_excerpt_length' ] );
		add_settings_field(
			WGC_OPTION_EXCERPT_LENGTH,
			__( 'Excerpt Length (words)', 'wp-gemini-content-generator' ),
			[ $this, 'render_excerpt_length_field' ],
			'wgc_settings',
			'wgc_seo_section'
		);

		// SEO Focus setting
		register_setting( 'wgc_settings', WGC_OPTION_SEO_FOCUS, [ $this, 'sanitize_seo_focus' ] );
		add_settings_field(
			WGC_OPTION_SEO_FOCUS,
			__( 'SEO Focus Keywords', 'wp-gemini-content-generator' ),
			[ $this, 'render_seo_focus_field' ],
			'wgc_settings',
			'wgc_seo_section'
		);

		// Batch Size setting
		register_setting( 'wgc_settings', WGC_OPTION_BATCH_SIZE, [ $this, 'sanitize_batch_size' ] );
		add_settings_field(
			WGC_OPTION_BATCH_SIZE,
			__( 'Batch Size', 'wp-gemini-content-generator' ),
			[ $this, 'render_batch_size_field' ],
			'wgc_settings',
			'wgc_bulk_section'
		);

		// Add settings sections
		add_settings_section( 'wgc_main_section', __( 'Main Settings', 'wp-gemini-content-generator' ), null, 'wgc_settings' );
		add_settings_section( 'wgc_content_section', __( 'Content Settings', 'wp-gemini-content-generator' ), null, 'wgc_settings' );
		add_settings_section( 'wgc_seo_section', __( 'SEO Settings', 'wp-gemini-content-generator' ), null, 'wgc_settings' );
		add_settings_section( 'wgc_bulk_section', __( 'Bulk Processing', 'wp-gemini-content-generator' ), null, 'wgc_settings' );
	}

	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<div class="wgc-admin-header">
				<div class="wgc-header-content">
					<h2><?php _e( 'AI-Powered Content Generation for WordPress', 'wp-gemini-content-generator' ); ?></h2>
					<p><?php _e( 'Generate professional, SEO-optimized content using Google\'s Gemini AI. Create descriptions, meta descriptions, tags, and more with just one click.', 'wp-gemini-content-generator' ); ?></p>
				</div>
				<div class="wgc-header-stats">
					<div class="wgc-stat">
						<span class="wgc-stat-number">2000+</span>
						<span class="wgc-stat-label"><?php _e( 'Characters', 'wp-gemini-content-generator' ); ?></span>
					</div>
					<div class="wgc-stat">
						<span class="wgc-stat-number">12</span>
						<span class="wgc-stat-label"><?php _e( 'Languages', 'wp-gemini-content-generator' ); ?></span>
					</div>
					<div class="wgc-stat">
						<span class="wgc-stat-number">100%</span>
						<span class="wgc-stat-label"><?php _e( 'SEO Ready', 'wp-gemini-content-generator' ); ?></span>
					</div>
				</div>
			</div>

			<div class="wgc-tabs">
				<nav class="wgc-tab-nav">
					<a href="#settings" class="wgc-tab-link active"><?php _e( 'Settings', 'wp-gemini-content-generator' ); ?></a>
					<a href="#bulk" class="wgc-tab-link"><?php _e( 'Bulk Generation', 'wp-gemini-content-generator' ); ?></a>
					<a href="#documentation" class="wgc-tab-link"><?php _e( 'Documentation', 'wp-gemini-content-generator' ); ?></a>
					<a href="#support" class="wgc-tab-link"><?php _e( 'Support', 'wp-gemini-content-generator' ); ?></a>
				</nav>

				<div id="settings" class="wgc-tab-content active">
					<form method="post" action="options.php">
						<?php
						settings_fields( 'wgc_settings' );
						do_settings_sections( 'wgc_settings' );
						submit_button();
						?>
					</form>
				</div>

				<div id="bulk" class="wgc-tab-content">
					<?php $this->render_bulk_generation_section(); ?>
				</div>

				<div id="documentation" class="wgc-tab-content">
					<?php $this->render_documentation_section(); ?>
				</div>

				<div id="support" class="wgc-tab-content">
					<?php $this->render_support_section(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	public function render_bulk_generation_section() {
		$batch_size = get_option( WGC_OPTION_BATCH_SIZE, 5 );
		$post_types = get_option( WGC_OPTION_POST_TYPES, [ 'post', 'page' ] );
		$nonce = wp_create_nonce( 'wgc_bulk_generate' );
		?>
		<div class="wgc-bulk-section">
			<h2><?php _e( 'Bulk Content Generation', 'wp-gemini-content-generator' ); ?></h2>
			<p><?php _e( 'Generate content for multiple posts and pages at once. The system will process them in batches to avoid timeouts.', 'wp-gemini-content-generator' ); ?></p>
			
			<div class="wgc-bulk-controls">
				<div class="wgc-form-group">
					<label for="wgc-bulk-post-types"><?php _e( 'Select Post Types:', 'wp-gemini-content-generator' ); ?></label>
					<select id="wgc-bulk-post-types" name="wgc-bulk-post-types[]" multiple class="wgc-multi-select">
						<?php
						$available_types = get_post_types( [ 'public' => true ], 'objects' );
						foreach ( $available_types as $type ) {
							$selected = in_array( $type->name, $post_types ) ? 'selected' : '';
							echo '<option value="' . esc_attr( $type->name ) . '" ' . $selected . '>' . esc_html( $type->label ) . '</option>';
						}
						?>
					</select>
					<p class="description"><?php _e( 'Hold Ctrl/Cmd to select multiple post types.', 'wp-gemini-content-generator' ); ?></p>
				</div>

				<div class="wgc-form-group">
					<label for="wgc-batch-size"><?php _e( 'Batch Size:', 'wp-gemini-content-generator' ); ?></label>
					<input type="number" id="wgc-batch-size" name="wgc-batch-size" value="<?php echo esc_attr( $batch_size ); ?>" min="1" max="20" class="wgc-input">
					<p class="description"><?php _e( 'Number of posts to process per batch (1-20).', 'wp-gemini-content-generator' ); ?></p>
				</div>

				<div class="wgc-form-group">
					<label>
						<input type="checkbox" id="wgc-force-regenerate" name="wgc-force-regenerate">
						<?php _e( 'Force regenerate (skip already processed posts)', 'wp-gemini-content-generator' ); ?>
					</label>
				</div>

				<div class="wgc-form-group">
					<label>
						<input type="checkbox" id="wgc-include-meta" name="wgc-include-meta">
						<?php _e( 'Include meta descriptions and tags', 'wp-gemini-content-generator' ); ?>
					</label>
				</div>

				<button type="button" id="wgc-bulk-generate" class="button button-primary button-large" data-nonce="<?php echo esc_attr( $nonce ); ?>">
					<?php _e( 'Start Bulk Generation', 'wp-gemini-content-generator' ); ?>
				</button>
			</div>

			<div id="wgc-bulk-status" class="wgc-bulk-status"></div>
		</div>
		<?php
	}

	public function render_documentation_section() {
		?>
		<div class="wgc-documentation">
			<h2><?php _e( 'Documentation & Help', 'wp-gemini-content-generator' ); ?></h2>
			
			<div class="wgc-doc-grid">
				<div class="wgc-doc-card">
					<h3><?php _e( 'Quick Start', 'wp-gemini-content-generator' ); ?></h3>
					<ol>
						<li><?php _e( 'Get your free Gemini API key from Google AI Studio', 'wp-gemini-content-generator' ); ?></li>
						<li><?php _e( 'Enter the API key in the Settings tab above', 'wp-gemini-content-generator' ); ?></li>
						<li><?php _e( 'Select your preferred language and content options', 'wp-gemini-content-generator' ); ?></li>
						<li><?php _e( 'Go to any post/page and click "Generate Content"', 'wp-gemini-content-generator' ); ?></li>
					</ol>
				</div>

				<div class="wgc-doc-card">
					<h3><?php _e( 'Features', 'wp-gemini-content-generator' ); ?></h3>
					<ul>
						<li><?php _e( 'Generate 2000+ character descriptions', 'wp-gemini-content-generator' ); ?></li>
						<li><?php _e( 'Create SEO meta descriptions', 'wp-gemini-content-generator' ); ?></li>
						<li><?php _e( 'Generate relevant tags', 'wp-gemini-content-generator' ); ?></li>
						<li><?php _e( 'Support for 12 languages', 'wp-gemini-content-generator' ); ?></li>
						<li><?php _e( 'WooCommerce product descriptions', 'wp-gemini-content-generator' ); ?></li>
						<li><?php _e( 'Bulk processing capabilities', 'wp-gemini-content-generator' ); ?></li>
					</ul>
				</div>

				<div class="wgc-doc-card">
					<h3><?php _e( 'Troubleshooting', 'wp-gemini-content-generator' ); ?></h3>
					<h4><?php _e( 'Common Issues:', 'wp-gemini-content-generator' ); ?></h4>
					<ul>
						<li><strong><?php _e( 'API Error:', 'wp-gemini-content-generator' ); ?></strong> <?php _e( 'Check your API key and credits', 'wp-gemini-content-generator' ); ?></li>
						<li><strong><?php _e( 'No Content Generated:', 'wp-gemini-content-generator' ); ?></strong> <?php _e( 'Verify internet connection and API limits', 'wp-gemini-content-generator' ); ?></li>
						<li><strong><?php _e( 'Bulk Processing Fails:', 'wp-gemini-content-generator' ); ?></strong> <?php _e( 'Reduce batch size or check server timeout settings', 'wp-gemini-content-generator' ); ?></li>
					</ul>
				</div>

				<div class="wgc-doc-card">
					<h3><?php _e( 'API Key Setup', 'wp-gemini-content-generator' ); ?></h3>
					<ol>
						<li><?php _e( 'Visit Google AI Studio', 'wp-gemini-content-generator' ); ?>: <a href="https://aistudio.google.com/u/3/api-keys" target="_blank">https://aistudio.google.com/u/3/api-keys</a></li>
						<li><?php _e( 'Sign in with your Google account', 'wp-gemini-content-generator' ); ?></li>
						<li><?php _e( 'Click "Create API Key"', 'wp-gemini-content-generator' ); ?></li>
						<li><?php _e( 'Copy the generated key', 'wp-gemini-content-generator' ); ?></li>
						<li><?php _e( 'Paste it in the Settings tab above', 'wp-gemini-content-generator' ); ?></li>
					</ol>
				</div>
			</div>
		</div>
		<?php
	}

	public function render_support_section() {
		?>
		<div class="wgc-support">
			<h2><?php _e( 'Support & Contact', 'wp-gemini-content-generator' ); ?></h2>
			
			<div class="wgc-support-grid">
				<div class="wgc-support-card">
					<h3><?php _e( 'Plugin Information', 'wp-gemini-content-generator' ); ?></h3>
					<table class="wgc-info-table">
						<tr>
							<td><strong><?php _e( 'Version:', 'wp-gemini-content-generator' ); ?></strong></td>
							<td><?php echo WGC_VERSION; ?></td>
						</tr>
						<tr>
							<td><strong><?php _e( 'WordPress:', 'wp-gemini-content-generator' ); ?></strong></td>
							<td><?php echo get_bloginfo( 'version' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php _e( 'PHP:', 'wp-gemini-content-generator' ); ?></strong></td>
							<td><?php echo PHP_VERSION; ?></td>
						</tr>
						<tr>
							<td><strong><?php _e( 'API Status:', 'wp-gemini-content-generator' ); ?></strong></td>
							<td id="wgc-api-status"><?php echo $this->get_api_key() ? __( 'Configured', 'wp-gemini-content-generator' ) : __( 'Not Set', 'wp-gemini-content-generator' ); ?></td>
						</tr>
					</table>
				</div>

				<div class="wgc-support-card">
					<h3><?php _e( 'Get Help', 'wp-gemini-content-generator' ); ?></h3>
					<ul>
						<li><a href="https://help.author.envato.com/hc/en-us" target="_blank"><?php _e( 'CodeCanyon Support', 'wp-gemini-content-generator' ); ?></a></li>
						<li><a href="https://wordpress.org/support/" target="_blank"><?php _e( 'WordPress Support', 'wp-gemini-content-generator' ); ?></a></li>
						<li><a href="https://aistudio.google.com/u/3/api-keys" target="_blank"><?php _e( 'Gemini API Help', 'wp-gemini-content-generator' ); ?></a></li>
					</ul>
				</div>

				<div class="wgc-support-card">
					<h3><?php _e( 'Rate & Review', 'wp-gemini-content-generator' ); ?></h3>
					<p><?php _e( 'If you like this plugin, please consider leaving a review on CodeCanyon. Your feedback helps us improve!', 'wp-gemini-content-generator' ); ?></p>
					<a href="#" class="button button-secondary"><?php _e( 'Leave a Review', 'wp-gemini-content-generator' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}

	// Sanitization methods
	public function sanitize_api_key( $value ) {
		return sanitize_text_field( $value );
	}

	public function sanitize_language( $value ) {
		$allowed_languages = [ 'en', 'it', 'es', 'fr', 'de', 'pt', 'ru', 'ja', 'ko', 'zh', 'ar', 'hi' ];
		return in_array( $value, $allowed_languages ) ? $value : 'en';
	}

	public function sanitize_post_types( $value ) {
		if ( ! is_array( $value ) ) {
			return [ 'post', 'page' ];
		}
		$available_types = array_keys( get_post_types( [ 'public' => true ] ) );
		return array_intersect( $value, $available_types );
	}

	public function sanitize_append_mode( $value ) {
		return in_array( $value, [ 'append', 'replace' ] ) ? $value : 'append';
	}

	public function sanitize_emoji_icons( $value ) {
		return (bool) $value;
	}

	public function sanitize_meta_description( $value ) {
		return (bool) $value;
	}

	public function sanitize_generate_tags( $value ) {
		return (bool) $value;
	}

	public function sanitize_generate_excerpt( $value ) {
		return (bool) $value;
	}

	public function sanitize_excerpt_length( $value ) {
		$value = intval( $value );
		return max( 10, min( 200, $value ) );
	}

	public function sanitize_seo_focus( $value ) {
		return sanitize_text_field( $value );
	}

	public function sanitize_batch_size( $value ) {
		$value = intval( $value );
		return max( 1, min( 20, $value ) );
	}

	public function sanitize_content_length( $value ) {
		$value = intval( $value );
		return max( 500, min( 5000, $value ) );
	}

	// Render methods for settings fields
	public function render_api_key_field() {
		$value = get_option( WGC_OPTION_API_KEY, '' );
		?>
		<input type="password" name="<?php echo WGC_OPTION_API_KEY; ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<p class="description">
			<?php _e( 'Get your free API key from', 'wp-gemini-content-generator' ); ?> 
			<a href="https://aistudio.google.com/u/3/api-keys" target="_blank"><?php _e( 'Google AI Studio', 'wp-gemini-content-generator' ); ?></a>
		</p>
		<?php
	}

	public function render_language_field() {
		$value = get_option( WGC_OPTION_LANGUAGE, 'en' );
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
		?>
		<select name="<?php echo WGC_OPTION_LANGUAGE; ?>">
			<?php foreach ( $languages as $code => $name ) : ?>
				<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $value, $code ); ?>><?php echo esc_html( $name ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php _e( 'Select the language for generated content.', 'wp-gemini-content-generator' ); ?></p>
		<?php
	}

	public function render_post_types_field() {
		$value = get_option( WGC_OPTION_POST_TYPES, [ 'post', 'page' ] );
		$available_types = get_post_types( [ 'public' => true ], 'objects' );
		?>
		<div class="wgc-checkbox-group">
			<?php foreach ( $available_types as $type ) : ?>
				<label>
					<input type="checkbox" name="<?php echo WGC_OPTION_POST_TYPES; ?>[]" value="<?php echo esc_attr( $type->name ); ?>" 
						   <?php checked( in_array( $type->name, $value ) ); ?> />
					<?php echo esc_html( $type->label ); ?>
				</label><br>
			<?php endforeach; ?>
		</div>
		<p class="description"><?php _e( 'Select which post types should have the content generation feature.', 'wp-gemini-content-generator' ); ?></p>
		<?php
	}

	public function render_content_length_field() {
		$value = get_option( WGC_OPTION_CONTENT_LENGTH, 2000 );
		?>
		<input type="number" name="<?php echo WGC_OPTION_CONTENT_LENGTH; ?>" value="<?php echo esc_attr( $value ); ?>" min="500" max="5000" step="100" class="small-text" />
		<p class="description"><?php _e( 'Target length for generated content (500-5000 characters).', 'wp-gemini-content-generator' ); ?></p>
		<?php
	}

	public function render_append_mode_field() {
		$value = get_option( WGC_OPTION_APPEND_MODE, 'append' );
		?>
		<select name="<?php echo WGC_OPTION_APPEND_MODE; ?>">
			<option value="append" <?php selected( $value, 'append' ); ?>><?php _e( 'Append to existing content', 'wp-gemini-content-generator' ); ?></option>
			<option value="replace" <?php selected( $value, 'replace' ); ?>><?php _e( 'Replace existing content', 'wp-gemini-content-generator' ); ?></option>
		</select>
		<p class="description"><?php _e( 'Choose whether to add to or replace existing content.', 'wp-gemini-content-generator' ); ?></p>
		<?php
	}

	public function render_emoji_icons_field() {
		$value = get_option( WGC_OPTION_EMOJI_ICONS, true );
		?>
		<label>
			<input type="checkbox" name="<?php echo WGC_OPTION_EMOJI_ICONS; ?>" value="1" <?php checked( $value ); ?> />
			<?php _e( 'Include emojis and icons in generated content', 'wp-gemini-content-generator' ); ?>
		</label>
		<p class="description"><?php _e( 'Add visual elements to make content more engaging.', 'wp-gemini-content-generator' ); ?></p>
		<?php
	}

	public function render_meta_description_field() {
		$value = get_option( WGC_OPTION_META_DESCRIPTION, true );
		?>
		<label>
			<input type="checkbox" name="<?php echo WGC_OPTION_META_DESCRIPTION; ?>" value="1" <?php checked( $value ); ?> />
			<?php _e( 'Generate meta descriptions for posts and pages', 'wp-gemini-content-generator' ); ?>
		</label>
		<p class="description"><?php _e( 'Create SEO-optimized meta descriptions automatically.', 'wp-gemini-content-generator' ); ?></p>
		<?php
	}

	public function render_generate_tags_field() {
		$value = get_option( WGC_OPTION_GENERATE_TAGS, true );
		?>
		<label>
			<input type="checkbox" name="<?php echo WGC_OPTION_GENERATE_TAGS; ?>" value="1" <?php checked( $value ); ?> />
			<?php _e( 'Generate relevant tags for posts', 'wp-gemini-content-generator' ); ?>
		</label>
		<p class="description"><?php _e( 'Automatically create relevant tags based on content.', 'wp-gemini-content-generator' ); ?></p>
		<?php
	}

	public function render_generate_excerpt_field() {
		$value = get_option( WGC_OPTION_GENERATE_EXCERPT, true );
		?>
		<label>
			<input type="checkbox" name="<?php echo WGC_OPTION_GENERATE_EXCERPT; ?>" value="1" <?php checked( $value ); ?> />
			<?php _e( 'Generate WordPress excerpts', 'wp-gemini-content-generator' ); ?>
		</label>
		<p class="description"><?php _e( 'Automatically create excerpts for posts and pages.', 'wp-gemini-content-generator' ); ?></p>
		<?php
	}

	public function render_excerpt_length_field() {
		$value = get_option( WGC_OPTION_EXCERPT_LENGTH, 55 );
		?>
		<input type="number" name="<?php echo WGC_OPTION_EXCERPT_LENGTH; ?>" value="<?php echo esc_attr( $value ); ?>" min="10" max="200" class="small-text" />
		<p class="description"><?php _e( 'Number of words for generated excerpts (10-200 words). Default WordPress excerpt is 55 words.', 'wp-gemini-content-generator' ); ?></p>
		<?php
	}

	public function render_seo_focus_field() {
		$value = get_option( WGC_OPTION_SEO_FOCUS, '' );
		?>
		<input type="text" name="<?php echo WGC_OPTION_SEO_FOCUS; ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="<?php _e( 'e.g., digital marketing, technology', 'wp-gemini-content-generator' ); ?>" />
		<p class="description"><?php _e( 'Optional: Focus keywords to include in generated content (comma-separated).', 'wp-gemini-content-generator' ); ?></p>
		<?php
	}

	public function render_batch_size_field() {
		$value = get_option( WGC_OPTION_BATCH_SIZE, 5 );
		?>
		<input type="number" name="<?php echo WGC_OPTION_BATCH_SIZE; ?>" value="<?php echo esc_attr( $value ); ?>" min="1" max="20" class="small-text" />
		<p class="description"><?php _e( 'Number of posts to process per batch during bulk generation.', 'wp-gemini-content-generator' ); ?></p>
		<?php
	}

	// Continue with the rest of the methods...
	public function register_meta_box() {
		$post_types = get_option( WGC_OPTION_POST_TYPES, [ 'post', 'page' ] );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'wgc_content_generator',
				__( 'Gemini Content Generator', 'wp-gemini-content-generator' ),
				[ $this, 'render_meta_box' ],
				$post_type,
				'side',
				'high'
			);
		}
	}

	public function render_meta_box( $post ) {
		$is_product = ( $post->post_type === 'product' );
		$nonce = wp_create_nonce( 'wgc_generate_for_post' );
		$generated = get_post_meta( $post->ID, '_wgc_generated', true );
		
		?>
		<div class="wgc-meta-box">
			<?php if ( $is_product ) : ?>
				<p class="wgc-product-notice">
					<strong><?php _e( 'WooCommerce Product Detected', 'wp-gemini-content-generator' ); ?></strong><br>
					<?php _e( 'Generate sales-oriented product descriptions.', 'wp-gemini-content-generator' ); ?>
				</p>
			<?php endif; ?>

			<div class="wgc-generation-controls">
				<button type="button" id="wgc-generate-all" class="button button-primary button-large" 
						data-post-id="<?php echo esc_attr( $post->ID ); ?>" 
						data-nonce="<?php echo esc_attr( $nonce ); ?>">
					<?php _e( '🚀 Generate All', 'wp-gemini-content-generator' ); ?>
				</button>

				<div class="wgc-individual-controls">
					<button type="button" id="wgc-generate-content" class="button button-secondary" 
							data-post-id="<?php echo esc_attr( $post->ID ); ?>" 
							data-nonce="<?php echo esc_attr( $nonce ); ?>">
						<?php _e( 'Generate Content', 'wp-gemini-content-generator' ); ?>
					</button>

					<?php if ( get_option( WGC_OPTION_META_DESCRIPTION, true ) ) : ?>
						<button type="button" id="wgc-generate-meta" class="button button-secondary" 
								data-post-id="<?php echo esc_attr( $post->ID ); ?>" 
								data-nonce="<?php echo esc_attr( $nonce ); ?>">
							<?php _e( 'Generate Meta Description', 'wp-gemini-content-generator' ); ?>
						</button>
					<?php endif; ?>

					<button type="button" id="wgc-generate-tags" class="button button-secondary" 
							data-post-id="<?php echo esc_attr( $post->ID ); ?>" 
							data-nonce="<?php echo esc_attr( $nonce ); ?>">
						<?php _e( 'Generate Tags', 'wp-gemini-content-generator' ); ?>
					</button>

					<button type="button" id="wgc-generate-excerpt" class="button button-secondary" 
							data-post-id="<?php echo esc_attr( $post->ID ); ?>" 
							data-nonce="<?php echo esc_attr( $nonce ); ?>">
						<?php _e( 'Generate Excerpt', 'wp-gemini-content-generator' ); ?>
					</button>
				</div>
			</div>

			<?php if ( $generated ) : ?>
				<div class="wgc-generated-notice">
					<span class="dashicons dashicons-yes-alt"></span>
					<?php _e( 'Content generated on:', 'wp-gemini-content-generator' ); ?>
					<strong><?php echo esc_html( date( 'M j, Y g:i A', strtotime( $generated ) ) ); ?></strong>
				</div>
			<?php endif; ?>

			<div id="wgc-preview" class="wgc-preview" style="display: none;">
				<h4><?php _e( 'Generated Content Preview:', 'wp-gemini-content-generator' ); ?></h4>
				<div class="wgc-preview-content"></div>
				<div class="wgc-preview-stats"></div>
			</div>
		</div>
		<?php
	}

	public function enqueue_admin_assets( $hook ) {
		// Only load on our settings page and post editor pages
		if ( $hook === 'settings_page_wgc-settings' || in_array( $hook, [ 'post.php', 'post-new.php' ] ) ) {
			$post_types = get_option( WGC_OPTION_POST_TYPES, [ 'post', 'page' ] );
			$current_post_type = get_current_screen()->post_type ?? '';
			
			// Only load on selected post types
			if ( $hook === 'settings_page_wgc-settings' || in_array( $current_post_type, $post_types ) ) {
				wp_enqueue_script( 'wgc-admin', WGC_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery' ], WGC_VERSION, true );
				wp_enqueue_style( 'wgc-admin', WGC_PLUGIN_URL . 'assets/css/admin.css', [], WGC_VERSION );
				
				wp_localize_script( 'wgc-admin', 'WGC', [
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'wgc_ajax' ),
					'i18n' => [
						'generating' => __( 'Generating...', 'wp-gemini-content-generator' ),
						'success' => __( 'Content generated successfully!', 'wp-gemini-content-generator' ),
						'error' => __( 'Error generating content', 'wp-gemini-content-generator' ),
						'confirm_replace' => __( 'This will replace existing content. Continue?', 'wp-gemini-content-generator' ),
					],
				] );
			}
		}
	}

	/**
	 * Enqueue Gutenberg scripts and styles
	 */
	public function enqueue_gutenberg_assets() {
		// Check if we're in the block editor
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! $screen->is_block_editor() ) {
			return;
		}

		$post_types = get_option( WGC_OPTION_POST_TYPES, [ 'post', 'page' ] );
		$current_post_type = $screen->post_type ?? '';
		
		// Only load on selected post types
		if ( ! in_array( $current_post_type, $post_types ) ) {
			return;
		}

		wp_enqueue_script(
			'wgc-gutenberg',
			WGC_PLUGIN_URL . 'assets/js/gutenberg.js',
			[ 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n', 'wp-api-fetch' ],
			WGC_VERSION,
			true
		);

		wp_enqueue_style(
			'wgc-gutenberg',
			WGC_PLUGIN_URL . 'assets/css/gutenberg.css',
			[ 'wp-components' ],
			WGC_VERSION
		);

		// Localize script for Gutenberg
		wp_localize_script( 'wgc-gutenberg', 'WGCGutenberg', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'rest_url' => rest_url( 'wp-gemini-content-generator/v1/' ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'strings' => [
				'generating' => __( 'Generating...', 'wp-gemini-content-generator' ),
				'success' => __( 'Content generated successfully!', 'wp-gemini-content-generator' ),
				'error' => __( 'Error generating content.', 'wp-gemini-content-generator' ),
				'no_title' => __( 'Please add a title to your post first.', 'wp-gemini-content-generator' ),
				'network_error' => __( 'Network error. Please try again.', 'wp-gemini-content-generator' )
			]
		] );
	}

	/**
	 * Register REST API routes for Gutenberg integration
	 */
	public function register_rest_routes() {
		register_rest_route( 'wp-gemini-content-generator/v1', '/generate', [
			'methods' => 'POST',
			'callback' => [ $this, 'rest_generate_content' ],
			'permission_callback' => [ $this, 'rest_permission_check' ],
			'args' => [
				'post_id' => [
					'required' => true,
					'type' => 'integer',
					'sanitize_callback' => 'absint',
				],
				'type' => [
					'required' => true,
					'type' => 'string',
					'enum' => [ 'content', 'meta_description', 'tags', 'excerpt', 'all' ],
				],
				'title' => [
					'required' => false,
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'content' => [
					'required' => false,
					'type' => 'string',
					'sanitize_callback' => 'wp_kses_post',
				],
				'language' => [
					'required' => false,
					'type' => 'string',
					'default' => 'en',
				],
				'content_length' => [
					'required' => false,
					'type' => 'string',
					'default' => 'long',
				],
				'emoji_icons' => [
					'required' => false,
					'type' => 'boolean',
					'default' => true,
				],
				'seo_focus' => [
					'required' => false,
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'generate_meta' => [
					'required' => false,
					'type' => 'boolean',
					'default' => true,
				],
				'generate_tags' => [
					'required' => false,
					'type' => 'boolean',
					'default' => true,
				],
				'generate_excerpt' => [
					'required' => false,
					'type' => 'boolean',
					'default' => true,
				],
			],
		] );
	}

	/**
	 * REST API permission check
	 */
	public function rest_permission_check( $request ) {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * REST API endpoint for content generation
	 */
	public function rest_generate_content( $request ) {
		$post_id = $request->get_param( 'post_id' );
		$type = $request->get_param( 'type' );
		$title = $request->get_param( 'title' );
		$content = $request->get_param( 'content' );
		$language = $request->get_param( 'language' );
		$content_length = $request->get_param( 'content_length' );
		$emoji_icons = $request->get_param( 'emoji_icons' );
		$seo_focus = $request->get_param( 'seo_focus' );
		$generate_meta = $request->get_param( 'generate_meta' );
		$generate_tags = $request->get_param( 'generate_tags' );
		$generate_excerpt = $request->get_param( 'generate_excerpt' );

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'post_not_found', __( 'Post not found', 'wp-gemini-content-generator' ), [ 'status' => 404 ] );
		}

		$result = [];

		try {
			switch ( $type ) {
				case 'content':
					$prompt = $this->build_prompt_for_title( $title ?: $post->post_title, $post_id );
					$response = $this->call_gemini_generate( $prompt );
					if ( is_wp_error( $response ) ) {
						return $response;
					}
					$generated_content = $this->extract_text_from_gemini_response( $response );
					$result['content'] = $generated_content;
					break;

				case 'meta_description':
					$prompt = $this->build_meta_description_prompt( $title ?: $post->post_title, $content ?: $post->post_content );
					$response = $this->call_gemini_generate( $prompt );
					if ( is_wp_error( $response ) ) {
						return $response;
					}
					$meta_description = $this->extract_text_from_gemini_response( $response );
					$result['meta_description'] = $meta_description;
					break;

				case 'tags':
					$prompt = $this->build_tags_prompt( $title ?: $post->post_title, $content ?: $post->post_content );
					$response = $this->call_gemini_generate( $prompt );
					if ( is_wp_error( $response ) ) {
						return $response;
					}
					$tags = $this->extract_text_from_gemini_response( $response );
					$result['tags'] = array_map( 'trim', explode( ',', $tags ) );
					break;

				case 'excerpt':
					$prompt = $this->build_excerpt_prompt( $title ?: $post->post_title, $content ?: $post->post_content );
					$response = $this->call_gemini_generate( $prompt );
					if ( is_wp_error( $response ) ) {
						return $response;
					}
					$excerpt_text = $this->extract_text_from_gemini_response( $response );
					$excerpt_length = get_option( WGC_OPTION_EXCERPT_LENGTH, 55 );
					$result['excerpt'] = wp_trim_words( $excerpt_text, $excerpt_length, '...' );
					break;

				case 'all':
					// Generate all content types
					$content_prompt = $this->build_prompt_for_title( $title ?: $post->post_title, $post_id );
					$meta_prompt = $this->build_meta_description_prompt( $title ?: $post->post_title, $content ?: $post->post_content );
					$tags_prompt = $this->build_tags_prompt( $title ?: $post->post_title, $content ?: $post->post_content );
					$excerpt_prompt = $this->build_excerpt_prompt( $title ?: $post->post_title, $content ?: $post->post_content );

					$content_response = $this->call_gemini_generate( $content_prompt );
					$meta_response = $this->call_gemini_generate( $meta_prompt );
					$tags_response = $this->call_gemini_generate( $tags_prompt );
					$excerpt_response = $this->call_gemini_generate( $excerpt_prompt );

					if ( ! is_wp_error( $content_response ) ) {
						$result['content'] = $this->extract_text_from_gemini_response( $content_response );
					}
					if ( ! is_wp_error( $meta_response ) ) {
						$result['meta_description'] = $this->extract_text_from_gemini_response( $meta_response );
					}
					if ( ! is_wp_error( $tags_response ) ) {
						$tags_text = $this->extract_text_from_gemini_response( $tags_response );
						$result['tags'] = array_map( 'trim', explode( ',', $tags_text ) );
					}
					if ( ! is_wp_error( $excerpt_response ) ) {
						$excerpt_text = $this->extract_text_from_gemini_response( $excerpt_response );
						$excerpt_length = get_option( WGC_OPTION_EXCERPT_LENGTH, 55 );
						$result['excerpt'] = wp_trim_words( $excerpt_text, $excerpt_length, '...' );
					}
					break;
			}

			return [
				'success' => true,
				'data' => $result,
				'message' => __( 'Content generated successfully!', 'wp-gemini-content-generator' )
			];

		} catch ( Exception $e ) {
			return new WP_Error( 'generation_failed', $e->getMessage(), [ 'status' => 500 ] );
		}
	}

	// AJAX handlers
	public function ajax_generate_for_post() {
		check_ajax_referer( 'wgc_generate_for_post', 'nonce' );
		
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'Insufficient permissions', 'wp-gemini-content-generator' ) );
		}

		$post_id = intval( $_POST['post_id'] ?? 0 );
		if ( ! $post_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid post ID', 'wp-gemini-content-generator' ) ] );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_send_json_error( [ 'message' => __( 'Post not found', 'wp-gemini-content-generator' ) ] );
		}

		$prompt = $this->build_prompt_for_title( $post->post_title, $post_id );
		$result = $this->call_gemini_generate( $prompt );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		$generated_text = $this->extract_text_from_gemini_response( $result );
		if ( empty( $generated_text ) ) {
			wp_send_json_error( [ 'message' => __( 'No content generated', 'wp-gemini-content-generator' ) ] );
		}

		$character_count = strlen( $generated_text );
		$append_mode = get_option( WGC_OPTION_APPEND_MODE, 'append' );
		
		$success = $this->append_content_to_post( $post_id, $generated_text, $append_mode );
		
		if ( $success ) {
			update_post_meta( $post_id, '_wgc_generated', current_time( 'mysql' ) );
			wp_send_json_success( [
				'generated_content' => $generated_text,
				'character_count' => $character_count,
				'message' => __( 'Content generated and added successfully!', 'wp-gemini-content-generator' )
			] );
		} else {
			wp_send_json_error( [ 'message' => __( 'Failed to save content', 'wp-gemini-content-generator' ) ] );
		}
	}

	public function ajax_generate_meta_description() {
		check_ajax_referer( 'wgc_generate_for_post', 'nonce' );
		
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'Insufficient permissions', 'wp-gemini-content-generator' ) );
		}

		$post_id = intval( $_POST['post_id'] ?? 0 );
		$post = get_post( $post_id );
		
		if ( ! $post ) {
			wp_send_json_error( [ 'message' => __( 'Post not found', 'wp-gemini-content-generator' ) ] );
		}

		$prompt = $this->build_meta_description_prompt( $post->post_title, $post->post_content );
		$result = $this->call_gemini_generate( $prompt );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		$meta_description = $this->extract_text_from_gemini_response( $result );
		$meta_description = wp_trim_words( $meta_description, 25, '...' );
		
		// Update meta description (works with Yoast, RankMath, etc.)
		update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_description );
		update_post_meta( $post_id, '_rank_math_description', $meta_description );
		update_post_meta( $post_id, '_wgc_meta_description', $meta_description );

		wp_send_json_success( [
			'meta_description' => $meta_description,
			'message' => __( 'Meta description generated successfully!', 'wp-gemini-content-generator' )
		] );
	}

	public function ajax_generate_tags() {
		check_ajax_referer( 'wgc_generate_for_post', 'nonce' );
		
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'Insufficient permissions', 'wp-gemini-content-generator' ) );
		}

		$post_id = intval( $_POST['post_id'] ?? 0 );
		$post = get_post( $post_id );
		
		if ( ! $post ) {
			wp_send_json_error( [ 'message' => __( 'Post not found', 'wp-gemini-content-generator' ) ] );
		}

		$prompt = $this->build_tags_prompt( $post->post_title, $post->post_content );
		$result = $this->call_gemini_generate( $prompt );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		$tags_text = $this->extract_text_from_gemini_response( $result );
		$tags = array_map( 'trim', explode( ',', $tags_text ) );
		$tags = array_slice( $tags, 0, 10 ); // Limit to 10 tags

		// Set tags for the post/product
		$post_type = get_post_type( $post_id );
		if ( $post_type === 'product' ) {
			// For WooCommerce products, use product_tag taxonomy
			wp_set_object_terms( $post_id, $tags, 'product_tag' );
		} else {
			// For regular posts, use post_tag taxonomy
			wp_set_post_tags( $post_id, $tags );
		}

		wp_send_json_success( [
			'tags' => $tags,
			'message' => __( 'Tags generated successfully!', 'wp-gemini-content-generator' )
		] );
	}

	public function ajax_generate_excerpt() {
		check_ajax_referer( 'wgc_generate_for_post', 'nonce' );
		
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'Insufficient permissions', 'wp-gemini-content-generator' ) );
		}

		$post_id = intval( $_POST['post_id'] ?? 0 );
		$post = get_post( $post_id );
		
		if ( ! $post ) {
			wp_send_json_error( [ 'message' => __( 'Post not found', 'wp-gemini-content-generator' ) ] );
		}

		$prompt = $this->build_excerpt_prompt( $post->post_title, $post->post_content );
		$result = $this->call_gemini_generate( $prompt );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		$excerpt_text = $this->extract_text_from_gemini_response( $result );
		$excerpt_length = get_option( WGC_OPTION_EXCERPT_LENGTH, 55 );
		$excerpt = wp_trim_words( $excerpt_text, $excerpt_length, '...' );
		
		// Update post excerpt
		wp_update_post( [
			'ID' => $post_id,
			'post_excerpt' => $excerpt
		] );

		wp_send_json_success( [
			'excerpt' => $excerpt,
			'message' => __( 'Excerpt generated successfully!', 'wp-gemini-content-generator' )
		] );
	}

	public function ajax_generate_all() {
		check_ajax_referer( 'wgc_generate_for_post', 'nonce' );
		
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'Insufficient permissions', 'wp-gemini-content-generator' ) );
		}

		$post_id = intval( $_POST['post_id'] ?? 0 );
		$post = get_post( $post_id );
		
		if ( ! $post ) {
			wp_send_json_error( [ 'message' => __( 'Post not found', 'wp-gemini-content-generator' ) ] );
		}

		$results = [];
		$errors = [];

		// Generate main content
		try {
			$prompt = $this->build_prompt_for_title( $post->post_title, $post_id );
			$result = $this->call_gemini_generate( $prompt );
			
			if ( ! is_wp_error( $result ) ) {
				$generated_text = $this->extract_text_from_gemini_response( $result );
				
				if ( ! empty( $generated_text ) ) {
					$append_mode = get_option( WGC_OPTION_APPEND_MODE, 'append' );
					$success = $this->append_content_to_post( $post_id, $generated_text, $append_mode );
					
					if ( $success ) {
						update_post_meta( $post_id, '_wgc_generated', current_time( 'mysql' ) );
						$results['content'] = __( 'Content generated successfully!', 'wp-gemini-content-generator' );
					} else {
						$errors['content'] = __( 'Failed to save content to post', 'wp-gemini-content-generator' );
					}
				} else {
					$errors['content'] = __( 'No content generated from AI response', 'wp-gemini-content-generator' );
				}
			} else {
				$errors['content'] = $result->get_error_message();
			}
		} catch ( Exception $e ) {
			$errors['content'] = $e->getMessage();
		}

		// Generate meta description
		try {
			$meta_prompt = $this->build_meta_description_prompt( $post->post_title, $post->post_content );
			$meta_result = $this->call_gemini_generate( $meta_prompt );
			
			if ( ! is_wp_error( $meta_result ) ) {
				$meta_description = $this->extract_text_from_gemini_response( $meta_result );
				$meta_description = wp_trim_words( $meta_description, 25, '...' );
				
				update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_description );
				update_post_meta( $post_id, '_rank_math_description', $meta_description );
				update_post_meta( $post_id, '_wgc_meta_description', $meta_description );
				$results['meta'] = __( 'Meta description generated successfully!', 'wp-gemini-content-generator' );
			} else {
				$errors['meta'] = $meta_result->get_error_message();
			}
		} catch ( Exception $e ) {
			$errors['meta'] = $e->getMessage();
		}

		// Generate tags
		try {
			$tags_prompt = $this->build_tags_prompt( $post->post_title, $post->post_content );
			$tags_result = $this->call_gemini_generate( $tags_prompt );
			
			if ( ! is_wp_error( $tags_result ) ) {
				$tags_text = $this->extract_text_from_gemini_response( $tags_result );
				$tags = array_map( 'trim', explode( ',', $tags_text ) );
				$tags = array_slice( $tags, 0, 10 );
				
				// Set tags for the post/product
				$post_type = get_post_type( $post_id );
				if ( $post_type === 'product' ) {
					// For WooCommerce products, use product_tag taxonomy
					wp_set_object_terms( $post_id, $tags, 'product_tag' );
				} else {
					// For regular posts, use post_tag taxonomy
					wp_set_post_tags( $post_id, $tags );
				}
				$results['tags'] = __( 'Tags generated successfully!', 'wp-gemini-content-generator' );
			} else {
				$errors['tags'] = $tags_result->get_error_message();
			}
		} catch ( Exception $e ) {
			$errors['tags'] = $e->getMessage();
		}

		// Generate excerpt
		try {
			$excerpt_prompt = $this->build_excerpt_prompt( $post->post_title, $post->post_content );
			$excerpt_result = $this->call_gemini_generate( $excerpt_prompt );
			
			if ( ! is_wp_error( $excerpt_result ) ) {
				$excerpt_text = $this->extract_text_from_gemini_response( $excerpt_result );
				$excerpt_length = get_option( WGC_OPTION_EXCERPT_LENGTH, 55 );
				$excerpt = wp_trim_words( $excerpt_text, $excerpt_length, '...' );
				
				wp_update_post( [
					'ID' => $post_id,
					'post_excerpt' => $excerpt
				] );
				$results['excerpt'] = __( 'Excerpt generated successfully!', 'wp-gemini-content-generator' );
			} else {
				$errors['excerpt'] = $excerpt_result->get_error_message();
			}
		} catch ( Exception $e ) {
			$errors['excerpt'] = $e->getMessage();
		}

		// Prepare response
		$message = '';
		if ( ! empty( $results ) ) {
			$generated_items = [];
			foreach ( $results as $key => $value ) {
				switch ( $key ) {
					case 'content':
						$generated_items[] = __( 'Content', 'wp-gemini-content-generator' );
						break;
					case 'meta':
						$generated_items[] = __( 'Meta Description', 'wp-gemini-content-generator' );
						break;
					case 'tags':
						$generated_items[] = __( 'Tags', 'wp-gemini-content-generator' );
						break;
					case 'excerpt':
						$generated_items[] = __( 'Excerpt', 'wp-gemini-content-generator' );
						break;
					default:
						$generated_items[] = ucfirst( $key );
				}
			}
			$message .= __( 'Successfully generated: ', 'wp-gemini-content-generator' ) . implode( ', ', $generated_items ) . '. ';
		}
		if ( ! empty( $errors ) ) {
			$error_items = [];
			foreach ( $errors as $key => $value ) {
				switch ( $key ) {
					case 'content':
						$error_items[] = __( 'Content', 'wp-gemini-content-generator' );
						break;
					case 'meta':
						$error_items[] = __( 'Meta Description', 'wp-gemini-content-generator' );
						break;
					case 'tags':
						$error_items[] = __( 'Tags', 'wp-gemini-content-generator' );
						break;
					case 'excerpt':
						$error_items[] = __( 'Excerpt', 'wp-gemini-content-generator' );
						break;
					default:
						$error_items[] = ucfirst( $key );
				}
			}
			$message .= __( 'Errors in: ', 'wp-gemini-content-generator' ) . implode( ', ', $error_items ) . '.';
		}

		wp_send_json_success( [
			'results' => $results,
			'errors' => $errors,
			'message' => $message
		] );
	}

	// Continue with bulk generation methods...
	public function ajax_bulk_generate() {
		check_ajax_referer( 'wgc_bulk_generate', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Insufficient permissions', 'wp-gemini-content-generator' ) );
		}

		$batch_size = intval( $_POST['batchSize'] ?? 5 );
		$post_types = array_map( 'sanitize_text_field', $_POST['postTypes'] ?? [] );
		$force_regenerate = (bool) ( $_POST['forceRegenerate'] ?? false );
		$include_meta = (bool) ( $_POST['includeMeta'] ?? false );

		if ( empty( $post_types ) ) {
			wp_send_json_error( [ 'message' => __( 'No post types selected', 'wp-gemini-content-generator' ) ] );
		}

		// Get posts to process
		$meta_query = [];
		if ( ! $force_regenerate ) {
			$meta_query[] = [
				'key' => '_wgc_generated',
				'compare' => 'NOT EXISTS'
			];
		}

		$posts = get_posts( [
			'post_type' => $post_types,
			'post_status' => 'publish',
			'numberposts' => -1,
			'meta_query' => $meta_query,
		] );

		if ( empty( $posts ) ) {
			$total_in_types = 0;
			foreach ( $post_types as $type ) {
				$total_in_types += wp_count_posts( $type )->publish;
			}
			
			wp_send_json_error( [
				'message' => sprintf(
					__( 'No posts found to process. Total posts in selected types: %d. Post types: %s. %s', 'wp-gemini-content-generator' ),
					$total_in_types,
					implode( ', ', $post_types ),
					$force_regenerate ? __( 'Force regenerate is enabled.', 'wp-gemini-content-generator' ) : __( 'Try enabling "Force regenerate" to process already generated posts.', 'wp-gemini-content-generator' )
				)
			] );
		}

		// Create job
		$job_id = 'wgc_bulk_' . time();
		$job_data = [
			'job_id' => $job_id,
			'post_ids' => array_map( function( $post ) { return $post->ID; }, $posts ),
			'batch_size' => $batch_size,
			'include_meta' => $include_meta,
			'status' => 'pending',
			'processed' => 0,
			'errors' => [],
			'created' => current_time( 'mysql' ),
		];

		update_option( 'wgc_bulk_job_' . $job_id, $job_data );
		$this->schedule_bulk_job();

		wp_send_json_success( [
			'job_started' => true,
			'job_id' => $job_id,
			'total' => count( $posts ),
			'message' => sprintf( __( 'Bulk generation job started. Processing %d posts in batches of %d.', 'wp-gemini-content-generator' ), count( $posts ), $batch_size )
		] );
	}

	public function ajax_bulk_status() {
		check_ajax_referer( 'wgc_bulk_generate', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Insufficient permissions', 'wp-gemini-content-generator' ) );
		}

		$job_id = sanitize_text_field( $_POST['jobId'] ?? '' );
		if ( empty( $job_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid job ID', 'wp-gemini-content-generator' ) ] );
		}

		$job_data = get_option( 'wgc_bulk_job_' . $job_id );
		if ( ! $job_data ) {
			wp_send_json_error( [ 'message' => __( 'Job not found', 'wp-gemini-content-generator' ) ] );
		}

		wp_send_json_success( $job_data );
	}

	public function schedule_bulk_job() {
		if ( ! wp_next_scheduled( 'wgc_bulk_process_job' ) ) {
			wp_schedule_single_event( time() + 10, 'wgc_bulk_process_job' );
		}
	}

	public function process_bulk_job() {
		// Find pending jobs
		global $wpdb;
		$jobs = $wpdb->get_results( 
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'wgc_bulk_job_%' AND option_value LIKE '%\"status\":\"pending\"%'"
		);

		foreach ( $jobs as $job ) {
			$job_id = str_replace( 'wgc_bulk_job_', '', $job->option_name );
			$job_data = get_option( 'wgc_bulk_job_' . $job_id );
			
			if ( $job_data && $job_data['status'] === 'pending' ) {
				$this->process_single_bulk_job( $job_id, $job_data );
			}
		}
	}

	private function process_single_bulk_job( $job_id, $job_data ) {
		$job_data['status'] = 'running';
		update_option( 'wgc_bulk_job_' . $job_id, $job_data );

		$post_ids = array_slice( $job_data['post_ids'], $job_data['processed'], $job_data['batch_size'] );
		
		foreach ( $post_ids as $post_id ) {
			try {
				$post = get_post( $post_id );
				if ( ! $post ) continue;

				// Generate main content
				$prompt = $this->build_prompt_for_title( $post->post_title, $post_id );
				$result = $this->call_gemini_generate( $prompt );

				if ( ! is_wp_error( $result ) ) {
					$generated_text = $this->extract_text_from_gemini_response( $result );
					if ( ! empty( $generated_text ) ) {
						$append_mode = get_option( WGC_OPTION_APPEND_MODE, 'append' );
						$this->append_content_to_post( $post_id, $generated_text, $append_mode );
						update_post_meta( $post_id, '_wgc_generated', current_time( 'mysql' ) );
					}
				}

				// Generate meta description if enabled
				if ( $job_data['include_meta'] && get_option( WGC_OPTION_META_DESCRIPTION, true ) ) {
					$meta_prompt = $this->build_meta_description_prompt( $post->post_title, $post->post_content );
					$meta_result = $this->call_gemini_generate( $meta_prompt );
					
					if ( ! is_wp_error( $meta_result ) ) {
						$meta_description = $this->extract_text_from_gemini_response( $meta_result );
						$meta_description = wp_trim_words( $meta_description, 25, '...' );
						
						update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_description );
						update_post_meta( $post_id, '_rank_math_description', $meta_description );
						update_post_meta( $post_id, '_wgc_meta_description', $meta_description );
					}
				}

				// Generate tags if enabled
				if ( $job_data['include_meta'] && get_option( WGC_OPTION_GENERATE_TAGS, true ) ) {
					$tags_prompt = $this->build_tags_prompt( $post->post_title, $post->post_content );
					$tags_result = $this->call_gemini_generate( $tags_prompt );
					
					if ( ! is_wp_error( $tags_result ) ) {
						$tags_text = $this->extract_text_from_gemini_response( $tags_result );
						$tags = array_map( 'trim', explode( ',', $tags_text ) );
						$tags = array_slice( $tags, 0, 10 );
						
						// Set tags for the post/product
						$post_type = get_post_type( $post_id );
						if ( $post_type === 'product' ) {
							// For WooCommerce products, use product_tag taxonomy
							wp_set_object_terms( $post_id, $tags, 'product_tag' );
						} else {
							// For regular posts, use post_tag taxonomy
							wp_set_post_tags( $post_id, $tags );
						}
					}
				}

			} catch ( Exception $e ) {
				$job_data['errors'][] = sprintf( __( 'Post ID %d: %s', 'wp-gemini-content-generator' ), $post_id, $e->getMessage() );
			}

			$job_data['processed']++;
		}

		// Check if job is complete
		if ( $job_data['processed'] >= count( $job_data['post_ids'] ) ) {
			$job_data['status'] = 'completed';
			$job_data['completed'] = current_time( 'mysql' );
		} else {
			$job_data['status'] = 'pending';
			// Schedule next batch
			wp_schedule_single_event( time() + 30, 'wgc_bulk_process_job' );
		}

		update_option( 'wgc_bulk_job_' . $job_id, $job_data );
	}

	// Prompt building methods
	private function build_prompt_for_title( $title, $post_id ) {
		$language = get_option( WGC_OPTION_LANGUAGE, 'en' );
		$content_length = get_option( WGC_OPTION_CONTENT_LENGTH, 2000 );
		$emoji_icons = get_option( WGC_OPTION_EMOJI_ICONS, true );
		$seo_focus = get_option( WGC_OPTION_SEO_FOCUS, '' );
		
		$post = get_post( $post_id );
		$is_product = ( $post->post_type === 'product' );
		
		$language_names = [
			'en' => 'English', 'it' => 'Italian', 'es' => 'Spanish', 'fr' => 'French',
			'de' => 'German', 'pt' => 'Portuguese', 'ru' => 'Russian', 'ja' => 'Japanese',
			'ko' => 'Korean', 'zh' => 'Chinese', 'ar' => 'Arabic', 'hi' => 'Hindi'
		];
		
		$language_name = $language_names[ $language ] ?? 'English';
		
		if ( $is_product ) {
			$prompt = "Write a compelling product description in {$language_name} for: '{$title}'. ";
			$prompt .= "Create a sales-oriented description that highlights features, benefits, and encourages purchase. ";
			$prompt .= "Target length: {$content_length} characters. ";
			$prompt .= "Use persuasive language and include product specifications. ";
		} else {
			$prompt = "Write a comprehensive, engaging description in {$language_name} for: '{$title}'. ";
			$prompt .= "Create informative content that provides value to readers. ";
			$prompt .= "Target length: {$content_length} characters. ";
			$prompt .= "Make it informative and well-structured. ";
		}
		
		if ( $seo_focus ) {
			$prompt .= "Focus on these keywords: {$seo_focus}. ";
		}
		
		if ( $emoji_icons ) {
			$prompt .= "Include relevant emojis and icons to make it visually appealing. ";
		}
		
		$prompt .= "Format with proper HTML structure (h2, h3, p, ul, li tags). ";
		$prompt .= "Do not include introductory phrases like 'Here is a description' or 'This article discusses'. ";
		$prompt .= "Start directly with the content. ";
		$prompt .= "Ensure the content is unique, engaging, and SEO-friendly.";
		
		return $prompt;
	}

	private function build_meta_description_prompt( $title, $content ) {
		$language = get_option( WGC_OPTION_LANGUAGE, 'en' );
		$seo_focus = get_option( WGC_OPTION_SEO_FOCUS, '' );
		
		$language_names = [
			'en' => 'English', 'it' => 'Italian', 'es' => 'Spanish', 'fr' => 'French',
			'de' => 'German', 'pt' => 'Portuguese', 'ru' => 'Russian', 'ja' => 'Japanese',
			'ko' => 'Korean', 'zh' => 'Chinese', 'ar' => 'Arabic', 'hi' => 'Hindi'
		];
		
		$language_name = $language_names[ $language ] ?? 'English';
		
		$prompt = "Write a compelling meta description in {$language_name} for: '{$title}'. ";
		$prompt .= "Maximum 160 characters. ";
		$prompt .= "Include a call-to-action. ";
		$prompt .= "Make it SEO-optimized and click-worthy. ";
		
		if ( $seo_focus ) {
			$prompt .= "Focus on these keywords: {$seo_focus}. ";
		}
		
		$prompt .= "Do not include quotes or special characters. ";
		$prompt .= "Make it compelling and descriptive.";
		
		return $prompt;
	}

	private function build_tags_prompt( $title, $content ) {
		$language = get_option( WGC_OPTION_LANGUAGE, 'en' );
		$seo_focus = get_option( WGC_OPTION_SEO_FOCUS, '' );
		
		$language_names = [
			'en' => 'English', 'it' => 'Italian', 'es' => 'Spanish', 'fr' => 'French',
			'de' => 'German', 'pt' => 'Portuguese', 'ru' => 'Russian', 'ja' => 'Japanese',
			'ko' => 'Korean', 'zh' => 'Chinese', 'ar' => 'Arabic', 'hi' => 'Hindi'
		];
		
		$language_name = $language_names[ $language ] ?? 'English';
		
		$prompt = "Generate relevant tags in {$language_name} for: '{$title}'. ";
		$prompt .= "Provide 5-10 comma-separated tags. ";
		$prompt .= "Make them relevant to the content and SEO-friendly. ";
		
		if ( $seo_focus ) {
			$prompt .= "Include these focus keywords: {$seo_focus}. ";
		}
		
		$prompt .= "Use lowercase, no spaces in tags. ";
		$prompt .= "Make them specific and descriptive.";
		
		return $prompt;
	}

	private function build_excerpt_prompt( $title, $content ) {
		$language = get_option( WGC_OPTION_LANGUAGE, 'en' );
		$seo_focus = get_option( WGC_OPTION_SEO_FOCUS, '' );
		$excerpt_length = get_option( WGC_OPTION_EXCERPT_LENGTH, 55 );
		
		$language_names = [
			'en' => 'English', 'it' => 'Italian', 'es' => 'Spanish', 'fr' => 'French',
			'de' => 'German', 'pt' => 'Portuguese', 'ru' => 'Russian', 'ja' => 'Japanese',
			'ko' => 'Korean', 'zh' => 'Chinese', 'ar' => 'Arabic', 'hi' => 'Hindi'
		];
		
		$language_name = $language_names[ $language ] ?? 'English';
		
		$prompt = "Write a compelling WordPress excerpt in {$language_name} for: '{$title}'. ";
		$prompt .= "Target length: {$excerpt_length} words. ";
		$prompt .= "Create an engaging summary that encourages readers to click and read more. ";
		$prompt .= "Make it informative and compelling. ";
		
		if ( $seo_focus ) {
			$prompt .= "Include these focus keywords naturally: {$seo_focus}. ";
		}
		
		$prompt .= "Do not include quotes or special characters. ";
		$prompt .= "Make it concise but descriptive. ";
		$prompt .= "End with an ellipsis (...) if needed.";
		
		return $prompt;
	}

	// Gemini API methods
	private function call_gemini_generate( $prompt ) {
		$api_key = $this->get_api_key();
		if ( empty( $api_key ) ) {
			return new WP_Error( 'wgc_missing_key', __( 'Missing Gemini API key. Please set it in settings.', 'wp-gemini-content-generator' ) );
		}

		$endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent';
		$url = add_query_arg( 'key', rawurlencode( $api_key ), $endpoint );

		$body = [
			'contents' => [
				[
					'parts' => [ [ 'text' => $prompt ] ],
				],
			],
			'generationConfig' => [
				'temperature' => 0.7,
				'topK' => 40,
				'topP' => 0.95,
				'maxOutputTokens' => 2048,
			],
		];

		$args = [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'timeout' => 60,
			'body' => wp_json_encode( $body ),
		];

		$response = wp_remote_post( $url, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$json = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		
		
		if ( $code >= 200 && $code < 300 ) {
			return is_array( $json ) ? $json : [];
		}

		$message = isset( $json['error']['message'] ) ? (string) $json['error']['message'] : __( 'Gemini API error', 'wp-gemini-content-generator' );
		return new WP_Error( 'wgc_http_error', $message, [ 'status' => $code ] );
	}

	private function extract_text_from_gemini_response( $response ) {
		
		// Check if response has the expected structure
		if ( empty( $response ) || ! is_array( $response ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WGC Debug - Empty or invalid response' );
			}
			return '';
		}
		
		// Check for candidates
		if ( empty( $response['candidates'] ) || ! is_array( $response['candidates'] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WGC Debug - No candidates in response' );
			}
			return '';
		}
		
		// Check for first candidate
		if ( empty( $response['candidates'][0] ) || ! is_array( $response['candidates'][0] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WGC Debug - No first candidate' );
			}
			return '';
		}
		
		// Check for content
		if ( empty( $response['candidates'][0]['content'] ) || ! is_array( $response['candidates'][0]['content'] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WGC Debug - No content in first candidate' );
			}
			return '';
		}
		
		// Check for parts
		if ( empty( $response['candidates'][0]['content']['parts'] ) || ! is_array( $response['candidates'][0]['content']['parts'] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WGC Debug - No parts in content' );
			}
			return '';
		}
		
		// Check for first part
		if ( empty( $response['candidates'][0]['content']['parts'][0] ) || ! is_array( $response['candidates'][0]['content']['parts'][0] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WGC Debug - No first part' );
			}
			return '';
		}
		
		// Check for text
		if ( empty( $response['candidates'][0]['content']['parts'][0]['text'] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WGC Debug - No text in first part' );
			}
			return '';
		}

		$text = $response['candidates'][0]['content']['parts'][0]['text'];
		
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'WGC Debug - Extracted text: ' . substr( $text, 0, 200 ) . '...' );
		}
		
		$text = $this->clean_introductory_text( $text );
		
		// Sanitize HTML
		$allowed_html = [
			'h2' => [], 'h3' => [], 'h4' => [], 'p' => [], 'ul' => [], 'ol' => [], 'li' => [],
			'strong' => [], 'em' => [], 'br' => [], 'span' => []
		];
		
		$sanitized_text = wp_kses( $text, $allowed_html );
		
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'WGC Debug - Sanitized text length: ' . strlen( $sanitized_text ) );
		}
		
		return $sanitized_text;
	}

	private function clean_introductory_text( $text ) {
		$introductory_phrases = [
			'Here is a description',
			'Here\'s a description',
			'This is a description',
			'Below is a description',
			'Here is the content',
			'Here\'s the content',
			'This article discusses',
			'This post covers',
			'In this article',
			'This content describes',
		];

		foreach ( $introductory_phrases as $phrase ) {
			if ( stripos( $text, $phrase ) === 0 ) {
				$text = substr( $text, strlen( $phrase ) );
				$text = ltrim( $text, ': .' );
				break;
			}
		}

		// Remove markdown code blocks
		$text = preg_replace( '/```[\s\S]*?```/', '', $text );
		$text = preg_replace( '/`[^`]*`/', '', $text );

		return trim( $text );
	}

	private function append_content_to_post( $post_id, $content, $mode ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		if ( $mode === 'replace' ) {
			$new_content = $content;
		} else {
			$separator = "\n\n<!-- Gemini Generated Description -->\n\n";
			$new_content = $post->post_content . $separator . $content;
		}

		$result = wp_update_post( [
			'ID' => $post_id,
			'post_content' => $new_content,
		] );

		return ! is_wp_error( $result );
	}

	private function get_api_key() {
		return get_option( WGC_OPTION_API_KEY, '' );
	}

	public function admin_notices() {
		$api_key = $this->get_api_key();
		if ( empty( $api_key ) ) {
			$screen = get_current_screen();
			if ( $screen && $screen->id === 'settings_page_wgc-settings' ) {
				echo '<div class="notice notice-warning"><p>';
				printf(
					__( 'Please configure your Gemini API key in %s to start generating content.', 'wp-gemini-content-generator' ),
					'<a href="' . admin_url( 'options-general.php?page=wgc-settings' ) . '">' . __( 'Settings', 'wp-gemini-content-generator' ) . '</a>'
				);
				echo '</p></div>';
			}
		}
	}
}

// Initialize the plugin
new WPGeminiContentGenerator();

// Activation hook
register_activation_hook( __FILE__, function() {
	// Set default options
	add_option( WGC_OPTION_LANGUAGE, 'en' );
	add_option( WGC_OPTION_POST_TYPES, [ 'post', 'page' ] );
	add_option( WGC_OPTION_APPEND_MODE, 'append' );
	add_option( WGC_OPTION_EMOJI_ICONS, true );
	add_option( WGC_OPTION_META_DESCRIPTION, true );
	add_option( WGC_OPTION_GENERATE_TAGS, true );
	add_option( WGC_OPTION_GENERATE_EXCERPT, true );
	add_option( WGC_OPTION_EXCERPT_LENGTH, 55 );
	add_option( WGC_OPTION_BATCH_SIZE, 5 );
	add_option( WGC_OPTION_CONTENT_LENGTH, 2000 );
	add_option( WGC_OPTION_SEO_FOCUS, '' );
} );