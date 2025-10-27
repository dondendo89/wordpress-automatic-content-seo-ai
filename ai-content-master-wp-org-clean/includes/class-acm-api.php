<?php
/**
 * API handler for AI content generation
 *
 * @package AI_Content_Master
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle AI API interactions
 */
class ACM_API {
    
    /**
     * API endpoint
     */
    private $api_endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent';
    
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
        $api_key = ACM_Core::get_option( 'acm_gemini_api_key' );
        
        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'API key not configured', 'ai-content-master' ) );
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
            'timeout' => 60,
            'sslverify' => true,
        );
        
        $url = $this->api_endpoint . '?key=' . $api_key;
        
        $response = wp_remote_post( $url, $args );
        
        if ( is_wp_error( $response ) ) {
            ACM_Core::log_error( 'API request failed: ' . $response->get_error_message() );
            return $response;
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        // Check for API errors first
        if ( isset( $data['error'] ) ) {
            ACM_Core::log_error( 'API Error: ' . $data['error']['message'] );
            return new WP_Error( 'api_error', $data['error']['message'] );
        }
        
        if ( ! $data || ! isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
            ACM_Core::log_error( 'Invalid API response structure: ' . $body );
            return new WP_Error( 'invalid_response', __( 'Invalid API response structure', 'ai-content-master' ) );
        }
        
        $content = $data['candidates'][0]['content']['parts'][0]['text'];
        
        // Clean unwanted content
        $content = $this->clean_generated_content( $content );
        
        // Sanitize HTML content
        $allowed_html = array(
            'h2' => array(),
            'h3' => array(),
            'h4' => array(),
            'p' => array(),
            'ul' => array(),
            'ol' => array(),
            'li' => array(),
            'strong' => array(),
            'em' => array(),
            'br' => array(),
            'span' => array(),
            'div' => array(),
        );
        
        return wp_kses( $content, $allowed_html );
    }
    
    /**
     * Build content generation prompt
     */
    public function build_content_prompt( $title, $options = array() ) {
        $language = ACM_Core::get_option( 'acm_language', 'en' );
        $content_length = ACM_Core::get_option( 'acm_content_length', 1000 );
        $seo_focus = ACM_Core::get_option( 'acm_seo_focus', '' );
        
        $prompt = sprintf(
            __( 'Write a comprehensive, SEO-optimized article about "%s" in %s language. The article should be approximately %d words long.', 'ai-content-master' ),
            $title,
            $language,
            $content_length
        );
        
        if ( ! empty( $seo_focus ) ) {
            $prompt .= sprintf(
                __( ' Focus on the following topics: %s.', 'ai-content-master' ),
                $seo_focus
            );
        }
        
        $prompt .= __( ' Format the content using proper HTML structure with h2, h3, p, ul, li, strong, and em tags. Make sure the content is original, engaging, and provides value to readers. Use proper headings, subheadings, and structure. Do not include CSS styles, introductory phrases like "Here is" or "This article", or any styling code. Start directly with the content using only HTML tags.', 'ai-content-master' );
        
        return $prompt;
    }
    
    /**
     * Build product description prompt (for WooCommerce)
     */
    public function build_product_prompt( $title, $options = array() ) {
        $language = ACM_Core::get_option( 'acm_language', 'en' );
        $content_length = ACM_Core::get_option( 'acm_content_length', 1000 );
        $seo_focus = ACM_Core::get_option( 'acm_seo_focus', '' );
        
        $prompt = sprintf(
            __( 'Write a compelling product description for "%s" in %s language. The description should be approximately %d words long.', 'ai-content-master' ),
            $title,
            $language,
            $content_length
        );
        
        if ( ! empty( $seo_focus ) ) {
            $prompt .= sprintf(
                __( ' Focus on the following topics: %s.', 'ai-content-master' ),
                $seo_focus
            );
        }
        
        $prompt .= __( ' Format the content using proper HTML structure with h2, h3, p, ul, li, strong, and em tags. Create a sales-oriented description that highlights features, benefits, and encourages purchase. Include product specifications and persuasive language. Do not include CSS styles, introductory phrases like "Here is" or "This product", or any styling code. Start directly with the content using only HTML tags.', 'ai-content-master' );
        
        return $prompt;
    }
    
    /**
     * Clean generated content from unwanted elements
     */
    private function clean_generated_content( $content ) {
        // Remove CSS styles
        $content = preg_replace( '/<style[^>]*>.*?<\/style>/is', '', $content );
        $content = preg_replace( '/body\s*\{[^}]*\}/i', '', $content );
        $content = preg_replace( '/h[1-6]\s*\{[^}]*\}/i', '', $content );
        $content = preg_replace( '/strong\s*\{[^}]*\}/i', '', $content );
        $content = preg_replace( '/em\s*\{[^}]*\}/i', '', $content );
        $content = preg_replace( '/ul\s*\{[^}]*\}/i', '', $content );
        $content = preg_replace( '/li\s*\{[^}]*\}/i', '', $content );
        
        // Remove introductory phrases
        $introductory_phrases = array(
            'Here is a',
            'Here\'s a',
            'This is a',
            'Below is a',
            'Here is the',
            'Here\'s the',
            'This article discusses',
            'This post covers',
            'In this article',
            'This content describes',
            'RC Auto Genertel:',
        );
        
        foreach ( $introductory_phrases as $phrase ) {
            if ( stripos( $content, $phrase ) === 0 ) {
                $content = substr( $content, strlen( $phrase ) );
                $content = ltrim( $content, ': .' );
                break;
            }
        }
        
        // Remove markdown code blocks
        $content = preg_replace( '/```[\s\S]*?```/', '', $content );
        $content = preg_replace( '/`[^`]*`/', '', $content );
        
        // Clean up extra whitespace
        $content = preg_replace( '/\n\s*/', "\n", $content );
        $content = preg_replace( '/>\s+</', '><', $content );
        
        return trim( $content );
    }
    
    /**
     * Build meta description prompt
     */
    public function build_meta_prompt( $title, $content = '' ) {
        $language = ACM_Core::get_option( 'acm_language', 'en' );
        
        $prompt = sprintf(
            __( 'Generate a compelling meta description for "%s" in %s language. The description should be 150-160 characters long, include relevant keywords, and encourage clicks.', 'ai-content-master' ),
            $title,
            $language
        );
        
        if ( ! empty( $content ) ) {
            $prompt .= sprintf(
                __( ' Based on this content: %s', 'ai-content-master' ),
                wp_trim_words( $content, 50 )
            );
        }
        
        return $prompt;
    }
    
    /**
     * Build tags prompt
     */
    public function build_tags_prompt( $title, $content = '' ) {
        $language = ACM_Core::get_option( 'acm_language', 'en' );
        
        $prompt = sprintf(
            __( 'Generate 5-8 relevant, SEO-friendly tags for "%s" in %s language. Return only the tags, separated by commas.', 'ai-content-master' ),
            $title,
            $language
        );
        
        if ( ! empty( $content ) ) {
            $prompt .= sprintf(
                __( ' Based on this content: %s', 'ai-content-master' ),
                wp_trim_words( $content, 30 )
            );
        }
        
        return $prompt;
    }
    
    /**
     * Build excerpt prompt
     */
    public function build_excerpt_prompt( $title, $content = '' ) {
        $language = ACM_Core::get_option( 'acm_language', 'en' );
        $excerpt_length = ACM_Core::get_option( 'acm_excerpt_length', 150 );
        
        $prompt = sprintf(
            __( 'Generate a compelling excerpt for "%s" in %s language. The excerpt should be approximately %d characters long and summarize the main points.', 'ai-content-master' ),
            $title,
            $excerpt_length
        );
        
        if ( ! empty( $content ) ) {
            $prompt .= sprintf(
                __( ' Based on this content: %s', 'ai-content-master' ),
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
