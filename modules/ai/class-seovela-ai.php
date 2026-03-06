<?php
/**
 * Seovela AI Module
 *
 * AI-powered SEO optimization using OpenAI, Google Gemini, and Claude
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Seovela AI Class
 */
class Seovela_AI {

    /**
     * Instance
     *
     * @var Seovela_AI
     */
    private static $instance = null;

    /**
     * OpenAI API endpoint
     *
     * @var string
     */
    private $openai_endpoint = 'https://api.openai.com/v1/chat/completions';

    /**
     * Gemini API endpoint base
     *
     * @var string
     */
    private $gemini_endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/';

    /**
     * Claude API endpoint
     *
     * @var string
     */
    private $claude_endpoint = 'https://api.anthropic.com/v1/messages';

    /**
     * Get instance
     *
     * @return Seovela_AI
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'wp_ajax_seovela_generate_ai_content', array( $this, 'ajax_generate_content' ) );
        add_action( 'wp_ajax_seovela_test_ai_connection', array( $this, 'ajax_test_connection' ) );
        add_action( 'wp_ajax_seovela_suggest_keywords', array( $this, 'ajax_suggest_keywords' ) );
        add_action( 'wp_ajax_seovela_improve_content', array( $this, 'ajax_improve_content' ) );
        add_action( 'wp_ajax_seovela_write_content', array( $this, 'ajax_write_content' ) );
        
        // Legacy AJAX handlers for backward compatibility
        add_action( 'wp_ajax_seovela_ai_optimize_title', array( $this, 'ajax_optimize_title_legacy' ) );
        add_action( 'wp_ajax_seovela_ai_optimize_description', array( $this, 'ajax_optimize_description_legacy' ) );
        
        // Streaming REST API endpoint
        add_action( 'rest_api_init', array( $this, 'register_streaming_endpoints' ) );
    }

    /**
     * Register streaming REST API endpoints
     */
    public function register_streaming_endpoints() {
        register_rest_route( 'seovela/v1', '/ai-stream', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'stream_ai_content' ),
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
        ) );
    }

    /**
     * Stream AI content via SSE
     *
     * @param WP_REST_Request $request Request object.
     */
    public function stream_ai_content( $request ) {
        // Kill all output buffering
        while ( ob_get_level() > 0 ) {
            ob_end_clean();
        }

        // Disable compression that would buffer output
        if ( function_exists( 'apache_setenv' ) ) {
            @apache_setenv( 'no-gzip', '1' );
        }
        @ini_set( 'zlib.output_compression', '0' );
        @ini_set( 'output_buffering', 'off' );
        @ini_set( 'implicit_flush', '1' );

        // SSE headers
        header( 'Content-Type: text/event-stream; charset=utf-8' );
        header( 'Cache-Control: no-cache, no-transform' );
        header( 'X-Accel-Buffering: no' ); // For Nginx
        header( 'Connection: keep-alive' );

        // Force PHP to flush directly
        ob_implicit_flush( true );

        // Get request parameters
        $action_type   = $request->get_param( 'action_type' ) ?: 'write';
        $content       = $request->get_param( 'content' ) ?: '';
        $topic         = $request->get_param( 'topic' ) ?: '';
        $content_type  = $request->get_param( 'content_type' ) ?: 'article';
        $tone          = $request->get_param( 'tone' ) ?: 'professional';
        $focus_keyword = $request->get_param( 'focus_keyword' ) ?: '';

        // Build prompt based on action type
        $prompt = $this->build_stream_prompt( $action_type, $content, $topic, $content_type, $tone, $focus_keyword );

        if ( ! $prompt ) {
            echo "data: " . wp_json_encode( array( 'error' => 'Invalid request parameters' ) ) . "\n\n";
            @ob_flush();
            @flush();
            exit;
        }

        $provider = get_option( 'seovela_ai_provider', 'openai' );

        if ( $provider === 'openai' ) {
            $this->stream_openai( $prompt );
        } elseif ( $provider === 'claude' ) {
            $this->stream_claude( $prompt );
        } else {
            $this->stream_gemini( $prompt );
        }

        // Signal completion
        echo "data: [DONE]\n\n";
        @ob_flush();
        @flush();
        exit;
    }

    /**
     * Build prompt for streaming based on action type
     *
     * @param string $action_type   Action type (write, improve, expand, etc.).
     * @param string $content       Existing content.
     * @param string $topic         Topic for writing.
     * @param string $content_type  Content type.
     * @param string $tone          Tone.
     * @param string $focus_keyword Focus keyword.
     * @return string|false
     */
    private function build_stream_prompt( $action_type, $content, $topic, $content_type, $tone, $focus_keyword ) {
        $keyword_instruction = $focus_keyword ? " Optimize for the keyword: {$focus_keyword}." : '';

        switch ( $action_type ) {
            case 'write':
                if ( empty( $topic ) ) {
                    return false;
                }
                return "Write a comprehensive, SEO-optimized {$content_type} about: {$topic}

Requirements:
- Tone: {$tone}
- Include relevant headings (H2, H3)
- Write engaging, informative content
- Format with proper HTML tags (h2, h3, p, ul, li)
- Aim for 500-800 words
{$keyword_instruction}

Output clean HTML content only, no markdown.";

            case 'improve':
                if ( empty( $content ) ) {
                    return false;
                }
                return "Improve the readability and flow of this content while maintaining the same meaning and key points:

{$content}

Requirements:
- Improve sentence structure and clarity
- Fix any grammar or spelling issues
- Maintain the original meaning
- Output clean HTML
{$keyword_instruction}";

            case 'expand':
                if ( empty( $content ) ) {
                    return false;
                }
                return "Expand this content with more details, examples, and explanations:

{$content}

Requirements:
- Add relevant details and examples
- Expand on key points
- Maintain the original tone and style
- Double the content length approximately
- Output clean HTML
{$keyword_instruction}";

            case 'seo_optimize':
                if ( empty( $content ) ) {
                    return false;
                }
                return "Optimize this content for SEO while maintaining readability:

{$content}

Requirements:
- Improve keyword placement naturally
- Add relevant semantic keywords
- Improve heading structure
- Ensure content is comprehensive
- Output clean HTML
{$keyword_instruction}";

            case 'simplify':
                if ( empty( $content ) ) {
                    return false;
                }
                return "Simplify this content to make it easier to read and understand:

{$content}

Requirements:
- Use simpler words and shorter sentences
- Break down complex concepts
- Maintain the key information
- Target a general audience
- Output clean HTML";

            case 'shorten':
                if ( empty( $content ) ) {
                    return false;
                }
                return "Shorten this content while keeping the most important information:

{$content}

Requirements:
- Reduce length by approximately 50%
- Keep the key points and main message
- Remove redundant information
- Maintain clarity
- Output clean HTML";

            default:
                return false;
        }
    }

    /**
     * Stream response from OpenAI
     *
     * @param string $prompt The prompt to send.
     */
    private function stream_openai( $prompt ) {
        $api_key = Seovela_Helpers::decrypt( get_option( 'seovela_openai_api_key', '' ) );
        $model   = get_option( 'seovela_openai_model', 'gpt-5-mini' );

        if ( empty( $api_key ) ) {
            echo "data: " . wp_json_encode( array( 'error' => 'OpenAI API key not configured' ) ) . "\n\n";
            @ob_flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            @flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            return;
        }

        $payload = wp_json_encode( array(
            'model'    => $model,
            'stream'   => true,
            'messages' => array(
                array(
                    'role'    => 'system',
                    'content' => 'You are an expert SEO content writer. Output clean HTML content without markdown formatting. Do not wrap content in code blocks.',
                ),
                array(
                    'role'    => 'user',
                    'content' => $prompt,
                ),
            ),
            'temperature' => floatval( get_option( 'seovela_ai_temperature', 0.7 ) ),
        ) );

        $ch = curl_init( $this->openai_endpoint ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init -- Required for SSE streaming.

        curl_setopt_array( $ch, array( // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt_array
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $api_key,
            ),
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CAINFO         => $this->get_ca_bundle_path(),
            CURLOPT_WRITEFUNCTION  => function( $curl, $chunk ) {
                // Parse SSE data from OpenAI
                $lines = explode( "\n", $chunk );
                foreach ( $lines as $line ) {
                    $line = trim( $line );
                    if ( strpos( $line, 'data: ' ) === 0 ) {
                        $data = substr( $line, 6 );
                        if ( $data === '[DONE]' ) {
                            continue;
                        }
                        $json = json_decode( $data, true );
                        if ( $json && isset( $json['choices'][0]['delta']['content'] ) ) {
                            $content = $json['choices'][0]['delta']['content'];
                            echo "data: " . wp_json_encode( array( 'content' => $content ) ) . "\n\n";
                            @ob_flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                            @flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                        }
                    }
                }
                return strlen( $chunk );
            },
        ) );

        curl_exec( $ch ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec

        if ( curl_errno( $ch ) ) {
            echo "data: " . wp_json_encode( array( 'error' => 'Connection error: ' . curl_error( $ch ) ) ) . "\n\n";
            @ob_flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            @flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        }

        curl_close( $ch ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close
    }

    /**
     * Stream response from Gemini
     *
     * @param string $prompt The prompt to send.
     */
    private function stream_gemini( $prompt ) {
        $api_key = Seovela_Helpers::decrypt( get_option( 'seovela_gemini_api_key', '' ) );
        $model   = get_option( 'seovela_gemini_model', 'gemini-3-flash-preview' );

        if ( empty( $api_key ) ) {
            echo "data: " . wp_json_encode( array( 'error' => 'Gemini API key not configured' ) ) . "\n\n";
            @ob_flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            @flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            return;
        }

        // Gemini streaming endpoint
        $url = $this->gemini_endpoint . $model . ':streamGenerateContent?alt=sse&key=' . $api_key;

        $payload = wp_json_encode( array(
            'contents' => array(
                array(
                    'parts' => array(
                        array( 'text' => $prompt ),
                    ),
                ),
            ),
            'generationConfig' => array(
                'temperature' => floatval( get_option( 'seovela_ai_temperature', 0.7 ) ),
            ),
        ) );

        $ch = curl_init( $url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init -- Required for SSE streaming.

        curl_setopt_array( $ch, array( // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt_array
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/json',
            ),
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CAINFO         => $this->get_ca_bundle_path(),
            CURLOPT_WRITEFUNCTION  => function( $curl, $chunk ) {
                // Parse SSE data from Gemini
                $lines = explode( "\n", $chunk );
                foreach ( $lines as $line ) {
                    $line = trim( $line );
                    if ( strpos( $line, 'data: ' ) === 0 ) {
                        $data = substr( $line, 6 );
                        $json = json_decode( $data, true );
                        if ( $json && isset( $json['candidates'][0]['content']['parts'][0]['text'] ) ) {
                            $content = $json['candidates'][0]['content']['parts'][0]['text'];
                            echo "data: " . wp_json_encode( array( 'content' => $content ) ) . "\n\n";
                            @ob_flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                            @flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                        }
                    }
                }
                return strlen( $chunk );
            },
        ) );

        curl_exec( $ch ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec

        if ( curl_errno( $ch ) ) {
            echo "data: " . wp_json_encode( array( 'error' => 'Connection error: ' . curl_error( $ch ) ) ) . "\n\n";
            @ob_flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            @flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        }

        curl_close( $ch ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close
    }

    /**
     * Check if AI is configured
     *
     * @return bool
     */
    public function is_configured() {
        $provider = get_option( 'seovela_ai_provider', 'openai' );
        
        if ( $provider === 'openai' ) {
            return ! empty( Seovela_Helpers::decrypt( get_option( 'seovela_openai_api_key', '' ) ) );
        } elseif ( $provider === 'claude' ) {
            return ! empty( Seovela_Helpers::decrypt( get_option( 'seovela_claude_api_key', '' ) ) );
        } else {
            return ! empty( Seovela_Helpers::decrypt( get_option( 'seovela_gemini_api_key', '' ) ) );
        }
    }

    /**
     * Get current AI provider
     *
     * @return string
     */
    public function get_provider() {
        return get_option( 'seovela_ai_provider', 'openai' );
    }

    /**
     * Get the CA bundle path for cURL SSL verification.
     *
     * Uses the WordPress-bundled certificate if available, falls back
     * to the system default.
     *
     * @return string Path to CA bundle file.
     */
    private function get_ca_bundle_path() {
        $wp_ca_bundle = ABSPATH . WPINC . '/certificates/ca-bundle.crt';

        if ( file_exists( $wp_ca_bundle ) ) {
            return $wp_ca_bundle;
        }

        // Fall back to cURL's default — return empty to let cURL use system CA.
        return '';
    }

    /**
     * AJAX handler for generating AI content
     */
    public function ajax_generate_content() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'seovela_ai_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'seovela' ) ) );
        }

        // Check permissions
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'seovela' ) ) );
        }

        // Get parameters
        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'title';
        $content = isset( $_POST['content'] ) ? sanitize_textarea_field( $_POST['content'] ) : '';
        $focus_keyword = isset( $_POST['focus_keyword'] ) ? sanitize_text_field( $_POST['focus_keyword'] ) : '';

        if ( empty( $content ) && $post_id > 0 ) {
            $post = get_post( $post_id );
            if ( $post ) {
                $content = wp_strip_all_tags( $post->post_content );
                $content = substr( $content, 0, 3000 ); // Limit content length
            }
        }

        if ( empty( $content ) ) {
            wp_send_json_error( array( 'message' => __( 'No content available to analyze.', 'seovela' ) ) );
        }

        // Generate content
        $result = $this->generate_seo_content( $content, $type, $focus_keyword );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array( 
            'content' => $result,
            'type' => $type
        ) );
    }

    /**
     * AJAX handler for testing AI connection
     */
    public function ajax_test_connection() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'seovela_test_ai' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'seovela' ) ) );
        }

        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'seovela' ) ) );
        }

        $provider = isset( $_POST['provider'] ) ? sanitize_text_field( $_POST['provider'] ) : 'openai';
        $api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( $_POST['api_key'] ) : '';

        // If no key submitted, try to use the saved (encrypted) key
        if ( empty( $api_key ) ) {
            $option_key = 'seovela_' . $provider . '_api_key';
            $api_key = Seovela_Helpers::decrypt( get_option( $option_key, '' ) );
        }

        if ( empty( $api_key ) ) {
            wp_send_json_error( array( 'message' => __( 'API key is required. Please enter an API key or save one first.', 'seovela' ) ) );
        }

        // Test connection
        $result = $this->test_api_connection( $provider, $api_key );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array( 'message' => __( 'Connection successful!', 'seovela' ) ) );
    }

    /**
     * AJAX handler for suggesting focus keywords
     */
    public function ajax_suggest_keywords() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'seovela_ai_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'seovela' ) ) );
        }

        // Check permissions
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'seovela' ) ) );
        }

        $content = isset( $_POST['content'] ) ? sanitize_textarea_field( $_POST['content'] ) : '';
        $title = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';

        if ( empty( $content ) && empty( $title ) ) {
            wp_send_json_error( array( 'message' => __( 'No content available to analyze.', 'seovela' ) ) );
        }

        $result = $this->suggest_keywords( $content, $title );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array( 'keywords' => $result ) );
    }

    /**
     * AJAX handler for improving content
     */
    public function ajax_improve_content() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'seovela_ai_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'seovela' ) ) );
        }

        // Check permissions
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'seovela' ) ) );
        }

        $content = isset( $_POST['content'] ) ? wp_kses_post( $_POST['content'] ) : '';
        $action_type = isset( $_POST['action_type'] ) ? sanitize_text_field( $_POST['action_type'] ) : 'improve';
        $focus_keyword = isset( $_POST['focus_keyword'] ) ? sanitize_text_field( $_POST['focus_keyword'] ) : '';

        if ( empty( $content ) ) {
            wp_send_json_error( array( 'message' => __( 'No content to improve.', 'seovela' ) ) );
        }

        $result = $this->improve_content( $content, $action_type, $focus_keyword );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array( 'content' => $result ) );
    }

    /**
     * AJAX handler for writing new content
     */
    public function ajax_write_content() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'seovela_ai_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'seovela' ) ) );
        }

        // Check permissions
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'seovela' ) ) );
        }

        $topic = isset( $_POST['topic'] ) ? sanitize_text_field( $_POST['topic'] ) : '';
        $content_type = isset( $_POST['content_type'] ) ? sanitize_text_field( $_POST['content_type'] ) : 'article';
        $focus_keyword = isset( $_POST['focus_keyword'] ) ? sanitize_text_field( $_POST['focus_keyword'] ) : '';
        $tone = isset( $_POST['tone'] ) ? sanitize_text_field( $_POST['tone'] ) : 'professional';

        if ( empty( $topic ) ) {
            wp_send_json_error( array( 'message' => __( 'Please provide a topic to write about.', 'seovela' ) ) );
        }

        $result = $this->write_content( $topic, $content_type, $focus_keyword, $tone );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array( 'content' => $result ) );
    }

    /**
     * Legacy AJAX handler for title optimization (backward compatibility)
     */
    public function ajax_optimize_title_legacy() {
        check_ajax_referer( 'seovela_ai_nonce', 'nonce' );

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
        }

        $post = get_post( $post_id );
        $content = wp_strip_all_tags( $post->post_content );
        $focus_keyword = get_post_meta( $post_id, '_seovela_focus_keyword', true );
        
        $optimized = $this->generate_seo_content( $content, 'title', $focus_keyword );

        if ( is_wp_error( $optimized ) ) {
            wp_send_json_error( array( 'message' => $optimized->get_error_message() ) );
        }

        wp_send_json_success( array( 'title' => $optimized ) );
    }

    /**
     * Legacy AJAX handler for description optimization (backward compatibility)
     */
    public function ajax_optimize_description_legacy() {
        check_ajax_referer( 'seovela_ai_nonce', 'nonce' );

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
        }

        $post = get_post( $post_id );
        $content = wp_strip_all_tags( $post->post_content );
        $focus_keyword = get_post_meta( $post_id, '_seovela_focus_keyword', true );
        
        $optimized = $this->generate_seo_content( $content, 'description', $focus_keyword );

        if ( is_wp_error( $optimized ) ) {
            wp_send_json_error( array( 'message' => $optimized->get_error_message() ) );
        }

        wp_send_json_success( array( 'description' => $optimized ) );
    }

    /**
     * Test API connection
     *
     * @param string $provider Provider name (openai or gemini)
     * @param string $api_key API key
     * @return bool|WP_Error
     */
    private function test_api_connection( $provider, $api_key ) {
        if ( $provider === 'openai' ) {
            return $this->test_openai_connection( $api_key );
        } elseif ( $provider === 'claude' ) {
            return $this->test_claude_connection( $api_key );
        } else {
            return $this->test_gemini_connection( $api_key );
        }
    }

    /**
     * Test OpenAI connection
     *
     * @param string $api_key API key
     * @return bool|WP_Error
     */
    private function test_openai_connection( $api_key ) {
        $response = wp_remote_post( $this->openai_endpoint, array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'model' => 'gpt-5-nano',
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => 'Hi',
                    ),
                ),
                'max_completion_tokens' => 5,
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            $error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Unknown error occurred.', 'seovela' );
            return new WP_Error( 'api_error', $error_message );
        }

        return true;
    }

    /**
     * Test Gemini connection
     *
     * @param string $api_key API key
     * @return bool|WP_Error
     */
    private function test_gemini_connection( $api_key ) {
        $model = 'gemini-3.1-flash-lite-preview';
        $url = $this->gemini_endpoint . $model . ':generateContent?key=' . $api_key;

        $response = wp_remote_post( $url, array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'contents' => array(
                    array(
                        'parts' => array(
                            array( 'text' => 'Hi' ),
                        ),
                    ),
                ),
                'generationConfig' => array(
                    'maxOutputTokens' => 5,
                ),
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            $error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Unknown error occurred.', 'seovela' );
            return new WP_Error( 'api_error', $error_message );
        }

        return true;
    }

    /**
     * Test Claude connection
     *
     * @param string $api_key API key
     * @return bool|WP_Error
     */
    private function test_claude_connection( $api_key ) {
        $response = wp_remote_post( $this->claude_endpoint, array(
            'timeout' => 30,
            'headers' => array(
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'model'      => 'claude-sonnet-4-6',
                'max_tokens' => 10,
                'messages'   => array(
                    array(
                        'role'    => 'user',
                        'content' => 'Hi',
                    ),
                ),
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            $error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Unknown error occurred.', 'seovela' );
            return new WP_Error( 'api_error', $error_message );
        }

        return true;
    }

    /**
     * Stream response from Claude
     *
     * @param string $prompt The prompt to send.
     */
    private function stream_claude( $prompt ) {
        $api_key = Seovela_Helpers::decrypt( get_option( 'seovela_claude_api_key', '' ) );
        $model   = get_option( 'seovela_claude_model', 'claude-sonnet-4-6' );

        if ( empty( $api_key ) ) {
            echo "data: " . wp_json_encode( array( 'error' => 'Claude API key not configured' ) ) . "\n\n";
            @ob_flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            @flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            return;
        }

        $payload = wp_json_encode( array(
            'model'      => $model,
            'max_tokens' => 4096,
            'stream'     => true,
            'messages'   => array(
                array(
                    'role'    => 'user',
                    'content' => $prompt,
                ),
            ),
            'system'      => 'You are an expert SEO content writer. Output clean HTML content without markdown formatting. Do not wrap content in code blocks.',
            'temperature' => floatval( get_option( 'seovela_ai_temperature', 0.7 ) ),
        ) );

        $ch = curl_init( $this->claude_endpoint ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init -- Required for SSE streaming.

        curl_setopt_array( $ch, array( // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt_array
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/json',
                'x-api-key: ' . $api_key,
                'anthropic-version: 2023-06-01',
            ),
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CAINFO         => $this->get_ca_bundle_path(),
            CURLOPT_WRITEFUNCTION  => function( $curl, $chunk ) {
                $lines = explode( "\n", $chunk );
                foreach ( $lines as $line ) {
                    $line = trim( $line );
                    if ( strpos( $line, 'data: ' ) === 0 ) {
                        $data = substr( $line, 6 );
                        if ( $data === '[DONE]' ) {
                            continue;
                        }
                        $json = json_decode( $data, true );
                        if ( $json && isset( $json['type'] ) && $json['type'] === 'content_block_delta' && isset( $json['delta']['text'] ) ) {
                            $content = $json['delta']['text'];
                            echo "data: " . wp_json_encode( array( 'content' => $content ) ) . "\n\n";
                            @ob_flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                            @flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                        }
                    }
                }
                return strlen( $chunk );
            },
        ) );

        curl_exec( $ch ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec

        if ( curl_errno( $ch ) ) {
            echo "data: " . wp_json_encode( array( 'error' => 'Connection error: ' . curl_error( $ch ) ) ) . "\n\n";
            @ob_flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            @flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        }

        curl_close( $ch ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close
    }

    /**
     * Generate content with Claude
     *
     * @param string $content Post content
     * @param string $type Content type
     * @param string $focus_keyword Focus keyword
     * @return string|WP_Error
     */
    private function generate_with_claude( $content, $type, $focus_keyword ) {
        $api_key = Seovela_Helpers::decrypt( get_option( 'seovela_claude_api_key', '' ) );
        $model = get_option( 'seovela_claude_model', 'claude-sonnet-4-6' );
        $temperature = floatval( get_option( 'seovela_ai_temperature', 0.7 ) );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'Claude API key is not configured. Please configure it in Seovela Settings > AI Optimization.', 'seovela' ) );
        }

        $prompt = $this->get_seo_prompt( $type, $focus_keyword );

        $response = wp_remote_post( $this->claude_endpoint, array(
            'timeout' => 60,
            'headers' => array(
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'model'      => $model,
                'max_tokens' => $type === 'title' ? 100 : 200,
                'system'     => $prompt,
                'messages'   => array(
                    array(
                        'role'    => 'user',
                        'content' => 'Content to optimize: ' . substr( $content, 0, 3000 ),
                    ),
                ),
                'temperature' => $temperature,
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            $error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Failed to generate content.', 'seovela' );
            return new WP_Error( 'api_error', $error_message );
        }

        if ( isset( $body['content'][0]['text'] ) ) {
            return trim( $body['content'][0]['text'] );
        }

        return new WP_Error( 'invalid_response', __( 'Invalid response from Claude.', 'seovela' ) );
    }

    /**
     * Generate SEO content using AI
     *
     * @param string $content Post content
     * @param string $type Content type (title or description)
     * @param string $focus_keyword Focus keyword
     * @return string|WP_Error
     */
    public function generate_seo_content( $content, $type = 'title', $focus_keyword = '' ) {
        $provider = $this->get_provider();

        if ( $provider === 'openai' ) {
            return $this->generate_with_openai( $content, $type, $focus_keyword );
        } elseif ( $provider === 'claude' ) {
            return $this->generate_with_claude( $content, $type, $focus_keyword );
        } else {
            return $this->generate_with_gemini( $content, $type, $focus_keyword );
        }
    }

    /**
     * Get SEO prompt
     *
     * @param string $type Content type (title or description)
     * @param string $focus_keyword Focus keyword
     * @return string
     */
    private function get_seo_prompt( $type, $focus_keyword = '' ) {
        $keyword_instruction = '';
        if ( ! empty( $focus_keyword ) ) {
            $keyword_instruction = sprintf(
                __( 'The focus keyword is "%s". ', 'seovela' ),
                $focus_keyword
            );
        }

        if ( $type === 'title' ) {
            return sprintf(
                __( 'You are an SEO expert. %sGenerate 5 SEO-optimized title variations. Each must be under 60 characters. Place the focus keyword near the beginning. Include power words. Format: return only the titles, one per line, numbered 1-5.', 'seovela' ),
                $keyword_instruction
            );
        } else {
            return sprintf(
                __( 'You are an SEO expert. %sGenerate 3 meta description variations. Each must be 150-160 characters. Include a compelling call-to-action. Include the focus keyword naturally. Format: return only the descriptions, one per line, numbered 1-3.', 'seovela' ),
                $keyword_instruction
            );
        }
    }

    /**
     * Generate content with OpenAI
     *
     * @param string $content Post content
     * @param string $type Content type
     * @param string $focus_keyword Focus keyword
     * @return string|WP_Error
     */
    private function generate_with_openai( $content, $type, $focus_keyword ) {
        $api_key = Seovela_Helpers::decrypt( get_option( 'seovela_openai_api_key', '' ) );
        $model = get_option( 'seovela_openai_model', 'gpt-5-mini' );
        $temperature = floatval( get_option( 'seovela_ai_temperature', 0.7 ) );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'OpenAI API key is not configured. Please configure it in Seovela Settings > AI Optimization.', 'seovela' ) );
        }

        $prompt = $this->get_seo_prompt( $type, $focus_keyword );

        $response = wp_remote_post( $this->openai_endpoint, array(
            'timeout' => 60,
                'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
            'body' => wp_json_encode( array(
                'model' => $model,
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => $prompt,
                    ),
                    array(
                        'role' => 'user',
                        'content' => 'Content to optimize: ' . substr( $content, 0, 3000 ),
                    ),
                ),
                'max_completion_tokens' => $type === 'title' ? 100 : 200,
                'temperature' => $temperature,
                ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            $error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Failed to generate content.', 'seovela' );
            return new WP_Error( 'api_error', $error_message );
        }

        if ( isset( $body['choices'][0]['message']['content'] ) ) {
            return trim( $body['choices'][0]['message']['content'] );
        }

        return new WP_Error( 'invalid_response', __( 'Invalid response from OpenAI.', 'seovela' ) );
        }

    /**
     * Generate content with Gemini
     *
     * @param string $content Post content
     * @param string $type Content type
     * @param string $focus_keyword Focus keyword
     * @return string|WP_Error
     */
    private function generate_with_gemini( $content, $type, $focus_keyword ) {
        $api_key = Seovela_Helpers::decrypt( get_option( 'seovela_gemini_api_key', '' ) );
        $model = get_option( 'seovela_gemini_model', 'gemini-3-flash-preview' );
        $temperature = floatval( get_option( 'seovela_ai_temperature', 0.7 ) );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'Gemini API key is not configured. Please configure it in Seovela Settings > AI Optimization.', 'seovela' ) );
        }

        $prompt = $this->get_seo_prompt( $type, $focus_keyword );
        $full_prompt = $prompt . "\n\nContent to optimize:\n" . substr( $content, 0, 3000 );

        $url = $this->gemini_endpoint . $model . ':generateContent?key=' . $api_key;

        $response = wp_remote_post( $url, array(
            'timeout' => 60,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'contents' => array(
            array(
                        'parts' => array(
                            array( 'text' => $full_prompt ),
                        ),
                    ),
                ),
                'generationConfig' => array(
                    'maxOutputTokens' => $type === 'title' ? 100 : 200,
                    'temperature' => $temperature,
                ),
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            $error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Failed to generate content.', 'seovela' );
            return new WP_Error( 'api_error', $error_message );
        }

        if ( isset( $body['candidates'][0]['content']['parts'][0]['text'] ) ) {
            return trim( $body['candidates'][0]['content']['parts'][0]['text'] );
        }

        return new WP_Error( 'invalid_response', __( 'Invalid response from Gemini.', 'seovela' ) );
    }

    /**
     * Suggest focus keywords based on content
     *
     * @param string $content Post content
     * @param string $title Post title
     * @return array|WP_Error
     */
    public function suggest_keywords( $content, $title = '' ) {
        $prompt = __( 'You are an SEO expert. Analyze the following content and suggest 5 focus keywords/phrases that would be ideal for SEO optimization. Consider search volume potential, relevance, and competition. Return ONLY a JSON array of strings with the 5 keywords, nothing else. Example: ["keyword 1", "keyword 2", "keyword 3", "keyword 4", "keyword 5"]', 'seovela' );
        
        $full_content = '';
        if ( ! empty( $title ) ) {
            $full_content .= "Title: " . $title . "\n\n";
        }
        $full_content .= "Content: " . substr( $content, 0, 2500 );

        $result = $this->make_ai_request( $prompt, $full_content, 200 );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Parse JSON response
        $result = trim( $result );
        // Remove markdown code blocks if present
        $result = preg_replace( '/^```json\s*/i', '', $result );
        $result = preg_replace( '/\s*```$/i', '', $result );
        
        $keywords = json_decode( $result, true );
        
        if ( ! is_array( $keywords ) ) {
            // Try to extract keywords from text response
            preg_match_all( '/"([^"]+)"/', $result, $matches );
            if ( ! empty( $matches[1] ) ) {
                $keywords = array_slice( $matches[1], 0, 5 );
            } else {
                return new WP_Error( 'parse_error', __( 'Failed to parse keyword suggestions.', 'seovela' ) );
            }
        }

        return array_slice( $keywords, 0, 5 );
    }

    /**
     * Improve existing content
     *
     * @param string $content Content to improve
     * @param string $action_type Type of improvement (improve, expand, simplify, seo_optimize)
     * @param string $focus_keyword Focus keyword for SEO optimization
     * @return string|WP_Error
     */
    public function improve_content( $content, $action_type = 'improve', $focus_keyword = '' ) {
        $prompts = array(
            'improve' => __( 'You are a professional content editor. Improve the following content for better readability, clarity, and engagement. Fix any grammar issues, improve sentence structure, and enhance the overall flow. Maintain the original meaning and tone. Return ONLY the improved content in HTML format, nothing else.', 'seovela' ),
            'expand' => __( 'You are a professional content writer. Expand the following content by adding more details, examples, and explanations. Make it more comprehensive and valuable for readers. Keep the same structure and tone. Return ONLY the expanded content in HTML format, nothing else.', 'seovela' ),
            'simplify' => __( 'You are a professional content editor. Simplify the following content to make it easier to read and understand. Use shorter sentences, simpler words, and clearer explanations. Remove jargon and complex terminology. Return ONLY the simplified content in HTML format, nothing else.', 'seovela' ),
            'seo_optimize' => sprintf(
                __( 'You are an SEO expert. Optimize the following content for search engines while maintaining readability. %sAdd relevant headings (H2, H3), improve keyword density naturally, add transition words, and ensure proper structure. Return ONLY the optimized content in HTML format, nothing else.', 'seovela' ),
                ! empty( $focus_keyword ) ? sprintf( __( 'Focus on the keyword "%s". ', 'seovela' ), $focus_keyword ) : ''
            ),
            'shorten' => __( 'You are a professional content editor. Shorten the following content while keeping the key points and main message. Remove redundancy and unnecessary details. Make it concise and impactful. Return ONLY the shortened content in HTML format, nothing else.', 'seovela' ),
        );

        $prompt = isset( $prompts[ $action_type ] ) ? $prompts[ $action_type ] : $prompts['improve'];
        
        // Determine max tokens based on action
        $max_tokens = 2000;
        if ( $action_type === 'expand' ) {
            $max_tokens = 3000;
        } elseif ( $action_type === 'shorten' ) {
            $max_tokens = 1000;
        }

        return $this->make_ai_request( $prompt, $content, $max_tokens );
    }

    /**
     * Write new content based on topic
     *
     * @param string $topic Topic to write about
     * @param string $content_type Type of content (article, listicle, how-to, comparison)
     * @param string $focus_keyword Focus keyword
     * @param string $tone Writing tone (professional, casual, friendly, formal)
     * @return string|WP_Error
     */
    public function write_content( $topic, $content_type = 'article', $focus_keyword = '', $tone = 'professional' ) {
        $content_templates = array(
            'article' => __( 'Write a comprehensive blog article', 'seovela' ),
            'listicle' => __( 'Write a listicle-style article with numbered points', 'seovela' ),
            'how-to' => __( 'Write a detailed how-to guide with step-by-step instructions', 'seovela' ),
            'comparison' => __( 'Write a comparison article analyzing different options', 'seovela' ),
            'review' => __( 'Write a detailed review article', 'seovela' ),
        );

        $tone_descriptions = array(
            'professional' => __( 'professional and authoritative', 'seovela' ),
            'casual' => __( 'casual and conversational', 'seovela' ),
            'friendly' => __( 'friendly and approachable', 'seovela' ),
            'formal' => __( 'formal and academic', 'seovela' ),
        );

        $content_instruction = isset( $content_templates[ $content_type ] ) ? $content_templates[ $content_type ] : $content_templates['article'];
        $tone_instruction = isset( $tone_descriptions[ $tone ] ) ? $tone_descriptions[ $tone ] : $tone_descriptions['professional'];

        $keyword_instruction = '';
        if ( ! empty( $focus_keyword ) ) {
            $keyword_instruction = sprintf( __( 'Optimize the content for the focus keyword "%s" by including it naturally in the title, headings, and throughout the content. ', 'seovela' ), $focus_keyword );
        }

        $prompt = sprintf(
            __( 'You are an expert content writer and SEO specialist. %s about the following topic. Use a %s tone. %sStructure the content with proper HTML headings (H2, H3), paragraphs, and lists where appropriate. Make it engaging, informative, and optimized for search engines. The content should be around 800-1200 words. Return ONLY the content in HTML format, nothing else.', 'seovela' ),
            $content_instruction,
            $tone_instruction,
            $keyword_instruction
        );

        return $this->make_ai_request( $prompt, "Topic: " . $topic, 3000 );
    }

    /**
     * Make AI request (unified method for all providers)
     *
     * Includes retry logic for rate limiting (429) and usage tracking.
     *
     * @param string $system_prompt System prompt
     * @param string $user_content User content
     * @param int    $max_tokens Maximum tokens
     * @return string|WP_Error
     */
    private function make_ai_request( $system_prompt, $user_content, $max_tokens = 1000 ) {
        $provider = $this->get_provider();
        $temperature = floatval( get_option( 'seovela_ai_temperature', 0.7 ) );

        // Retry delays for rate limiting (429).
        $retry_delays = array( 2, 5 );
        $attempt = 0;
        $max_attempts = 3; // initial + 2 retries

        while ( $attempt < $max_attempts ) {
            $response = $this->dispatch_ai_request( $provider, $system_prompt, $user_content, $max_tokens, $temperature );

            // If wp_remote_post itself failed, return immediately.
            if ( is_wp_error( $response ) ) {
                return $response;
            }

            $status_code = wp_remote_retrieve_response_code( $response );

            // Handle rate limiting with retries.
            if ( 429 === $status_code ) {
                $attempt++;
                if ( $attempt < $max_attempts ) {
                    sleep( $retry_delays[ $attempt - 1 ] );
                    continue;
                }
                return new WP_Error(
                    'rate_limited',
                    __( 'AI API rate limit exceeded. Please wait a moment and try again. If this persists, check your API plan usage limits.', 'seovela' )
                );
            }

            // Not a 429, break out of retry loop.
            break;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $status_code = wp_remote_retrieve_response_code( $response );

        if ( $status_code !== 200 ) {
            $error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'API request failed.', 'seovela' );
            return new WP_Error( 'api_error', $error_message );
        }

        // Extract content and track usage based on provider.
        $content = null;
        $tokens_in = 0;
        $tokens_out = 0;

        if ( $provider === 'openai' ) {
            if ( isset( $body['choices'][0]['message']['content'] ) ) {
                $content = trim( $body['choices'][0]['message']['content'] );
            }
            if ( isset( $body['usage']['prompt_tokens'] ) ) {
                $tokens_in = intval( $body['usage']['prompt_tokens'] );
            }
            if ( isset( $body['usage']['completion_tokens'] ) ) {
                $tokens_out = intval( $body['usage']['completion_tokens'] );
            }
        } elseif ( $provider === 'claude' ) {
            if ( isset( $body['content'][0]['text'] ) ) {
                $content = trim( $body['content'][0]['text'] );
            }
            if ( isset( $body['usage']['input_tokens'] ) ) {
                $tokens_in = intval( $body['usage']['input_tokens'] );
            }
            if ( isset( $body['usage']['output_tokens'] ) ) {
                $tokens_out = intval( $body['usage']['output_tokens'] );
            }
        } else {
            // Gemini
            if ( isset( $body['candidates'][0]['content']['parts'][0]['text'] ) ) {
                $content = trim( $body['candidates'][0]['content']['parts'][0]['text'] );
            }
            if ( isset( $body['usageMetadata']['promptTokenCount'] ) ) {
                $tokens_in = intval( $body['usageMetadata']['promptTokenCount'] );
            }
            if ( isset( $body['usageMetadata']['candidatesTokenCount'] ) ) {
                $tokens_out = intval( $body['usageMetadata']['candidatesTokenCount'] );
            }
        }

        // Track usage.
        $this->track_usage( $provider, $tokens_in, $tokens_out );

        if ( null !== $content ) {
            return $content;
        }

        return new WP_Error( 'invalid_response', __( 'Invalid API response.', 'seovela' ) );
    }

    /**
     * Dispatch AI request to the appropriate provider
     *
     * @param string $provider     AI provider (openai, claude, gemini).
     * @param string $system_prompt System prompt.
     * @param string $user_content  User content.
     * @param int    $max_tokens    Maximum tokens.
     * @param float  $temperature   Temperature setting.
     * @return array|WP_Error Raw wp_remote_post response or WP_Error.
     */
    private function dispatch_ai_request( $provider, $system_prompt, $user_content, $max_tokens, $temperature ) {
        if ( $provider === 'openai' ) {
            $api_key = Seovela_Helpers::decrypt( get_option( 'seovela_openai_api_key', '' ) );
            $model   = get_option( 'seovela_openai_model', 'gpt-5-mini' );

            if ( empty( $api_key ) ) {
                return new WP_Error( 'no_api_key', __( 'OpenAI API key is not configured.', 'seovela' ) );
            }

            return wp_remote_post( $this->openai_endpoint, array(
                'timeout' => 120,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body' => wp_json_encode( array(
                    'model'                 => $model,
                    'messages'              => array(
                        array( 'role' => 'system', 'content' => $system_prompt ),
                        array( 'role' => 'user', 'content' => $user_content ),
                    ),
                    'max_completion_tokens' => $max_tokens,
                    'temperature'           => $temperature,
                ) ),
            ) );
        } elseif ( $provider === 'claude' ) {
            $api_key = Seovela_Helpers::decrypt( get_option( 'seovela_claude_api_key', '' ) );
            $model   = get_option( 'seovela_claude_model', 'claude-sonnet-4-6' );

            if ( empty( $api_key ) ) {
                return new WP_Error( 'no_api_key', __( 'Claude API key is not configured.', 'seovela' ) );
            }

            return wp_remote_post( $this->claude_endpoint, array(
                'timeout' => 120,
                'headers' => array(
                    'x-api-key'         => $api_key,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type'      => 'application/json',
                ),
                'body' => wp_json_encode( array(
                    'model'       => $model,
                    'max_tokens'  => $max_tokens,
                    'system'      => $system_prompt,
                    'messages'    => array(
                        array( 'role' => 'user', 'content' => $user_content ),
                    ),
                    'temperature' => $temperature,
                ) ),
            ) );
        } else {
            // Gemini
            $api_key = Seovela_Helpers::decrypt( get_option( 'seovela_gemini_api_key', '' ) );
            $model   = get_option( 'seovela_gemini_model', 'gemini-3-flash-preview' );

            if ( empty( $api_key ) ) {
                return new WP_Error( 'no_api_key', __( 'Gemini API key is not configured.', 'seovela' ) );
            }

            $url         = $this->gemini_endpoint . $model . ':generateContent?key=' . $api_key;
            $full_prompt = $system_prompt . "\n\n" . $user_content;

            return wp_remote_post( $url, array(
                'timeout' => 120,
                'headers' => array( 'Content-Type' => 'application/json' ),
                'body'    => wp_json_encode( array(
                    'contents' => array(
                        array( 'parts' => array( array( 'text' => $full_prompt ) ) ),
                    ),
                    'generationConfig' => array(
                        'maxOutputTokens' => $max_tokens,
                        'temperature'     => $temperature,
                    ),
                ) ),
            ) );
        }
    }

    /**
     * Track AI usage
     *
     * Stores cumulative usage data per provider in wp_options as seovela_ai_usage.
     *
     * @param string $provider   AI provider name (openai, claude, gemini).
     * @param int    $tokens_in  Number of input tokens used.
     * @param int    $tokens_out Number of output tokens used.
     */
    public function track_usage( $provider, $tokens_in, $tokens_out ) {
        $usage = get_option( 'seovela_ai_usage', array() );

        if ( ! isset( $usage[ $provider ] ) ) {
            $usage[ $provider ] = array(
                'provider'         => $provider,
                'total_calls'      => 0,
                'total_tokens_in'  => 0,
                'total_tokens_out' => 0,
                'last_used'        => '',
            );
        }

        $usage[ $provider ]['total_calls']      += 1;
        $usage[ $provider ]['total_tokens_in']   += intval( $tokens_in );
        $usage[ $provider ]['total_tokens_out']  += intval( $tokens_out );
        $usage[ $provider ]['last_used']          = current_time( 'mysql' );

        update_option( 'seovela_ai_usage', $usage );
    }

    /**
     * Get AI usage statistics
     *
     * @return array Array of usage data per provider with keys:
     *               provider, total_calls, total_tokens_in, total_tokens_out, last_used.
     */
    public function get_usage_stats() {
        $usage = get_option( 'seovela_ai_usage', array() );

        // Return as indexed array for consistent consumption.
        return array_values( $usage );
    }

    /**
     * Reset AI usage statistics
     *
     * Clears all tracked usage data.
     */
    public function reset_usage_stats() {
        delete_option( 'seovela_ai_usage' );
    }

    /**
     * Analyze content for SEO improvements
     *
     * Uses AI to analyze content and provide structured SEO suggestions
     * covering keyword placement, heading structure, content gaps, and readability.
     *
     * @param string $content       The content to analyze.
     * @param string $focus_keyword The focus keyword to optimize for.
     * @return array|WP_Error Array of structured suggestions or WP_Error on failure.
     */
    public function analyze_content_for_seo( $content, $focus_keyword ) {
        if ( empty( $content ) ) {
            return new WP_Error( 'no_content', __( 'No content provided for analysis.', 'seovela' ) );
        }

        if ( empty( $focus_keyword ) ) {
            return new WP_Error( 'no_keyword', __( 'A focus keyword is required for SEO analysis.', 'seovela' ) );
        }

        $system_prompt = sprintf(
            __( 'You are an expert SEO analyst. Analyze the following content for the focus keyword "%s" and provide specific, actionable suggestions. Return your analysis as a JSON object with exactly these keys:
- "keyword_placement": array of suggestions for improving keyword usage and placement
- "heading_structure": array of suggestions for improving heading hierarchy and keyword usage in headings
- "content_gaps": array of missing topics or subtopics that should be covered to improve topical authority
- "readability": array of suggestions for improving readability, sentence structure, and engagement

Each array should contain 2-5 specific, actionable string suggestions. Return ONLY valid JSON, nothing else.', 'seovela' ),
            $focus_keyword
        );

        $result = $this->make_ai_request( $system_prompt, substr( $content, 0, 4000 ), 1500 );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Clean and parse JSON response.
        $result = trim( $result );
        $result = preg_replace( '/^```json\s*/i', '', $result );
        $result = preg_replace( '/\s*```$/i', '', $result );

        $suggestions = json_decode( $result, true );

        if ( ! is_array( $suggestions ) ) {
            return new WP_Error( 'parse_error', __( 'Failed to parse SEO analysis response.', 'seovela' ) );
        }

        // Ensure all expected keys exist.
        $defaults = array(
            'keyword_placement' => array(),
            'heading_structure' => array(),
            'content_gaps'      => array(),
            'readability'       => array(),
        );

        return wp_parse_args( $suggestions, $defaults );
    }

    /**
     * Estimate API cost for a given text and operation type
     *
     * Provides a rough cost estimate based on approximate token counts and
     * published API pricing for each provider/model combination.
     *
     * @param string $text The text to estimate cost for.
     * @param string $type The operation type (title, description, content, analysis).
     * @return string Formatted cost estimate string.
     */
    public function estimate_cost( $text, $type = 'title' ) {
        // Rough token estimation: ~1.3 tokens per word.
        $word_count      = str_word_count( $text );
        $estimated_tokens = intval( $word_count * 1.3 );

        // Estimate output tokens based on type.
        $output_multipliers = array(
            'title'       => 0.1,  // Short output
            'description' => 0.15, // Short output
            'content'     => 2.0,  // Long output (writing new content)
            'analysis'    => 0.5,  // Medium output
        );

        $multiplier       = isset( $output_multipliers[ $type ] ) ? $output_multipliers[ $type ] : 0.5;
        $estimated_output  = max( intval( $estimated_tokens * $multiplier ), 50 );

        // Pricing per 1M tokens: array( input_cost, output_cost ).
        $pricing = array(
            'openai' => array(
                'gpt-5.2'       => array( 1.75, 14.00 ),
                'gpt-5-mini'    => array( 0.25, 2.00 ),
                'gpt-5-nano'    => array( 0.05, 0.40 ),
                'gpt-4.1'       => array( 2.00, 8.00 ),
                'gpt-4.1-mini'  => array( 0.40, 1.60 ),
                'gpt-4.1-nano'  => array( 0.10, 0.40 ),
                'gpt-4o'        => array( 2.50, 10.00 ),
                'gpt-4o-mini'   => array( 0.15, 0.60 ),
            ),
            'claude' => array(
                'claude-opus-4-6'           => array( 5.00, 25.00 ),
                'claude-sonnet-4-6'         => array( 3.00, 15.00 ),
                'claude-haiku-4-5-20251001' => array( 1.00, 5.00 ),
            ),
            'gemini' => array(
                'gemini-3.1-pro-preview'        => array( 2.00, 12.00 ),
                'gemini-3-flash-preview'        => array( 0.50, 3.00 ),
                'gemini-3.1-flash-lite-preview' => array( 0.25, 1.50 ),
                'text-embedding-004'            => array( 0.00, 0.00 ),
            ),
        );

        $provider = $this->get_provider();

        // Determine the active model.
        if ( $provider === 'openai' ) {
            $model = get_option( 'seovela_openai_model', 'gpt-5-mini' );
        } elseif ( $provider === 'claude' ) {
            $model = get_option( 'seovela_claude_model', 'claude-sonnet-4-6' );
        } else {
            $model = get_option( 'seovela_gemini_model', 'gemini-3-flash-preview' );
        }

        // Look up pricing for the provider/model.
        if ( isset( $pricing[ $provider ][ $model ] ) ) {
            $input_rate  = $pricing[ $provider ][ $model ][0];
            $output_rate = $pricing[ $provider ][ $model ][1];
        } else {
            // Fallback to default rates per provider.
            $defaults = array(
                'openai' => array( 0.25, 2.00 ),
                'claude' => array( 3.00, 15.00 ),
                'gemini' => array( 0.50, 3.00 ),
            );
            $input_rate  = isset( $defaults[ $provider ] ) ? $defaults[ $provider ][0] : 0.15;
            $output_rate = isset( $defaults[ $provider ] ) ? $defaults[ $provider ][1] : 0.60;
        }

        $input_cost  = ( $estimated_tokens / 1000000 ) * $input_rate;
        $output_cost = ( $estimated_output / 1000000 ) * $output_rate;
        $total_cost  = $input_cost + $output_cost;

        return sprintf(
            /* translators: 1: estimated cost, 2: input tokens, 3: output tokens, 4: provider, 5: model */
            __( 'Estimated cost: $%1$s (~%2$d input tokens, ~%3$d output tokens using %4$s/%5$s)', 'seovela' ),
            number_format( $total_cost, 6 ),
            $estimated_tokens,
            $estimated_output,
            $provider,
            $model
        );
    }

    /**
     * Get available AI models
     *
     * @param string $provider Provider name
     * @return array
     */
    public function get_available_models( $provider = 'openai' ) {
        if ( $provider === 'claude' ) {
            return array(
                'claude-opus-4-6' => array(
                    'name' => 'Claude Opus 4.6',
                    'description' => __( 'Most intelligent - complex reasoning', 'seovela' ),
                ),
                'claude-sonnet-4-6' => array(
                    'name' => 'Claude Sonnet 4.6',
                    'description' => __( 'Balanced intelligence and speed', 'seovela' ),
                ),
                'claude-haiku-4-5-20251001' => array(
                    'name' => 'Claude Haiku 4.5',
                    'description' => __( 'Fastest and cheapest', 'seovela' ),
                ),
            );
        } elseif ( $provider === 'openai' ) {
            return array(
                'gpt-5.2' => array(
                    'name' => 'GPT-5.2',
                    'description' => __( 'Flagship - most capable', 'seovela' ),
                ),
                'gpt-5-mini' => array(
                    'name' => 'GPT-5 Mini',
                    'description' => __( 'Fast and smart', 'seovela' ),
                ),
                'gpt-5-nano' => array(
                    'name' => 'GPT-5 Nano',
                    'description' => __( 'Ultra fast and cheap', 'seovela' ),
                ),
                'gpt-4.1' => array(
                    'name' => 'GPT-4.1',
                    'description' => __( 'Previous flagship', 'seovela' ),
                ),
                'gpt-4.1-mini' => array(
                    'name' => 'GPT-4.1 Mini',
                    'description' => __( 'Balanced', 'seovela' ),
                ),
                'gpt-4.1-nano' => array(
                    'name' => 'GPT-4.1 Nano',
                    'description' => __( 'Budget', 'seovela' ),
                ),
                'gpt-4o' => array(
                    'name' => 'GPT-4o',
                    'description' => __( 'Legacy multimodal', 'seovela' ),
                ),
                'gpt-4o-mini' => array(
                    'name' => 'GPT-4o Mini',
                    'description' => __( 'Legacy fast', 'seovela' ),
                ),
            );
        } else {
            return array(
                'gemini-3.1-pro-preview' => array(
                    'name' => 'Gemini 3.1 Pro',
                    'description' => __( 'Flagship - deep reasoning', 'seovela' ),
                ),
                'gemini-3-flash-preview' => array(
                    'name' => 'Gemini 3 Flash',
                    'description' => __( 'Balanced - fast and smart', 'seovela' ),
                ),
                'gemini-3.1-flash-lite-preview' => array(
                    'name' => 'Gemini 3.1 Flash Lite',
                    'description' => __( 'Ultra fast and cheap', 'seovela' ),
                ),
                'text-embedding-004' => array(
                    'name' => 'Text Embedding 004',
                    'description' => __( 'Semantic search - free', 'seovela' ),
                ),
            );
    }
}
}

// Initialize AI class
Seovela_AI::get_instance();
