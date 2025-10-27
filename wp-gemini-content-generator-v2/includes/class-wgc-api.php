<?php
/**
 * Gemini API handler
 *
 * @package WP_Gemini_Content_Generator
 * @since 2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle Gemini API interactions
 */
class WGC_API {
    
    /**
     * API endpoint
     */
    private $api_endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize API
    }
    
    /**
     * Generate content using Gemini API
     */
    public function generate_content( $prompt, $options = array() ) {
        $api_key = WGC_Core::get_option( 'wgc_gemini_api_key' );
        
        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'API key not configured', 'wp-gemini-content-generator' ) );
        }
        
        $request_data = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $prompt
                        )
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 2048,
            )
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode( $request_data ),
            'timeout' => 30,
        );
        
        $url = $this->api_endpoint . '?key=' . $api_key;
        
        $response = wp_remote_post( $url, $args );
        
        if ( is_wp_error( $response ) ) {
            WGC_Core::log_error( 'API request failed: ' . $response->get_error_message() );
            return $response;
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( ! $data || ! isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
            WGC_Core::log_error( 'Invalid API response: ' . $body );
            return new WP_Error( 'invalid_response', __( 'Invalid API response', 'wp-gemini-content-generator' ) );
        }
        
        return $data['candidates'][0]['content']['parts'][0]['text'];
    }
    
    /**
     * Build content generation prompt
     */
    public function build_content_prompt( $title, $options = array() ) {
        $language = WGC_Core::get_option( 'wgc_language', 'en' );
        $content_length = WGC_Core::get_option( 'wgc_content_length', 1000 );
        $seo_focus = WGC_Core::get_option( 'wgc_seo_focus', '' );
        
        $prompt = sprintf(
            __( 'Write a comprehensive, SEO-optimized article about "%s" in %s language. The article should be approximately %d words long.', 'wp-gemini-content-generator' ),
            $title,
            $language,
            $content_length
        );
        
        if ( ! empty( $seo_focus ) ) {
            $prompt .= sprintf(
                __( ' Focus on the following topics: %s.', 'wp-gemini-content-generator' ),
                $seo_focus
            );
        }
        
        $prompt .= __( ' Make sure the content is original, engaging, and provides value to readers. Use proper headings, subheadings, and structure.', 'wp-gemini-content-generator' );
        
        return $prompt;
    }
    
    /**
     * Build meta description prompt
     */
    public function build_meta_prompt( $title, $content = '' ) {
        $language = WGC_Core::get_option( 'wgc_language', 'en' );
        
        $prompt = sprintf(
            __( 'Generate a compelling meta description for "%s" in %s language. The description should be 150-160 characters long, include relevant keywords, and encourage clicks.', 'wp-gemini-content-generator' ),
            $title,
            $language
        );
        
        if ( ! empty( $content ) ) {
            $prompt .= sprintf(
                __( ' Based on this content: %s', 'wp-gemini-content-generator' ),
                wp_trim_words( $content, 50 )
            );
        }
        
        return $prompt;
    }
    
    /**
     * Build tags prompt
     */
    public function build_tags_prompt( $title, $content = '' ) {
        $language = WGC_Core::get_option( 'wgc_language', 'en' );
        
        $prompt = sprintf(
            __( 'Generate 5-8 relevant, SEO-friendly tags for "%s" in %s language. Return only the tags, separated by commas.', 'wp-gemini-content-generator' ),
            $title,
            $language
        );
        
        if ( ! empty( $content ) ) {
            $prompt .= sprintf(
                __( ' Based on this content: %s', 'wp-gemini-content-generator' ),
                wp_trim_words( $content, 30 )
            );
        }
        
        return $prompt;
    }
    
    /**
     * Build excerpt prompt
     */
    public function build_excerpt_prompt( $title, $content = '' ) {
        $language = WGC_Core::get_option( 'wgc_language', 'en' );
        $excerpt_length = WGC_Core::get_option( 'wgc_excerpt_length', 150 );
        
        $prompt = sprintf(
            __( 'Generate a compelling excerpt for "%s" in %s language. The excerpt should be approximately %d characters long and summarize the main points.', 'wp-gemini-content-generator' ),
            $title,
            $excerpt_length
        );
        
        if ( ! empty( $content ) ) {
            $prompt .= sprintf(
                __( ' Based on this content: %s', 'wp-gemini-content-generator' ),
                wp_trim_words( $content, 100 )
            );
        }
        
        return $prompt;
    }
    
    /**
     * Sanitize API response
     */
    public function sanitize_response( $text ) {
        if ( empty( $text ) ) {
            return '';
        }
        
        // Remove any potential HTML tags that shouldn't be there
        $allowed_html = array(
            'p' => array(),
            'br' => array(),
            'strong' => array(),
            'em' => array(),
            'ul' => array(),
            'ol' => array(),
            'li' => array(),
            'h1' => array(),
            'h2' => array(),
            'h3' => array(),
            'h4' => array(),
            'h5' => array(),
            'h6' => array(),
        );
        
        $sanitized = wp_kses( $text, $allowed_html );
        
        // Clean up extra whitespace
        $sanitized = preg_replace( '/\s+/', ' ', $sanitized );
        $sanitized = trim( $sanitized );
        
        return $sanitized;
    }
}
