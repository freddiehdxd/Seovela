<?php
/**
 * FAQ Schema Type
 *
 * FAQ schema with Q&A pairs
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * FAQ Schema Class
 */
class Seovela_FAQ_Schema {

    /**
     * Generate FAQ schema
     *
     * @param int $post_id Post ID
     * @return array Schema array
     */
    public static function generate( $post_id ) {
        // Check if auto-detect is enabled
        $auto_detect = get_post_meta( $post_id, '_seovela_faq_auto_detect', true );
        
        if ( $auto_detect === 'yes' ) {
            $faq_items = self::auto_detect_faqs( $post_id );
        } else {
            $faq_items = self::get_manual_faqs( $post_id );
        }

        // If no FAQ items, return empty
        if ( empty( $faq_items ) ) {
            return array();
        }

        $schema = array(
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => array(),
        );

        foreach ( $faq_items as $item ) {
            if ( empty( $item['question'] ) || empty( $item['answer'] ) ) {
                continue;
            }

            $schema['mainEntity'][] = array(
                '@type'          => 'Question',
                'name'           => sanitize_text_field( $item['question'] ),
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text'  => wp_kses_post( $item['answer'] ),
                ),
            );
        }

        // If no valid items, return empty
        if ( empty( $schema['mainEntity'] ) ) {
            return array();
        }

        return apply_filters( 'seovela_faq_schema', $schema, $post_id );
    }

    /**
     * Get manually entered FAQ items
     *
     * @param int $post_id Post ID
     * @return array FAQ items
     */
    private static function get_manual_faqs( $post_id ) {
        $faqs = get_post_meta( $post_id, '_seovela_faq_items', true );
        
        if ( empty( $faqs ) || ! is_array( $faqs ) ) {
            return array();
        }

        return $faqs;
    }

    /**
     * Auto-detect FAQs from H2/H3 headings
     *
     * @param int $post_id Post ID
     * @return array FAQ items
     */
    private static function auto_detect_faqs( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return array();
        }

        $content = $post->post_content;
        $faqs = array();

        // Parse content for H2/H3 question patterns
        // Look for headings that contain question marks or start with question words
        preg_match_all( '/<h[23][^>]*>(.*?)<\/h[23]>/is', $content, $headings );
        
        if ( empty( $headings[1] ) ) {
            return array();
        }

        $question_patterns = array( '?', 'how', 'what', 'why', 'when', 'where', 'who', 'which', 'can', 'is', 'are', 'do', 'does' );
        
        foreach ( $headings[1] as $index => $heading ) {
            $heading_text = wp_strip_all_tags( $heading );
            
            // Check if this looks like a question
            $is_question = false;
            $heading_lower = strtolower( $heading_text );
            
            foreach ( $question_patterns as $pattern ) {
                if ( strpos( $heading_lower, $pattern ) === 0 || strpos( $heading_text, '?' ) !== false ) {
                    $is_question = true;
                    break;
                }
            }

            if ( ! $is_question ) {
                continue;
            }

            // Extract the answer (content until next heading or end)
            $answer = self::extract_answer_after_heading( $content, $headings[0][ $index ], isset( $headings[0][ $index + 1 ] ) ? $headings[0][ $index + 1 ] : null );

            if ( ! empty( $answer ) ) {
                $faqs[] = array(
                    'question' => $heading_text,
                    'answer'   => $answer,
                );
            }
        }

        return $faqs;
    }

    /**
     * Extract answer content after a heading
     *
     * @param string $content Full content
     * @param string $current_heading Current heading HTML
     * @param string $next_heading Next heading HTML (or null)
     * @return string Answer text
     */
    private static function extract_answer_after_heading( $content, $current_heading, $next_heading ) {
        $start = strpos( $content, $current_heading );
        if ( $start === false ) {
            return '';
        }

        $start += strlen( $current_heading );

        if ( $next_heading ) {
            $end = strpos( $content, $next_heading, $start );
            if ( $end !== false ) {
                $answer = substr( $content, $start, $end - $start );
            } else {
                $answer = substr( $content, $start );
            }
        } else {
            $answer = substr( $content, $start );
        }

        // Clean up the answer
        $answer = trim( wp_strip_all_tags( $answer ) );
        $answer = wp_trim_words( $answer, 100, '...' );

        return $answer;
    }

    /**
     * Check if this schema type is compatible with another
     *
     * @param string $other_type Other schema type
     * @return bool
     */
    public static function is_compatible_with( $other_type ) {
        $compatible = array( 'Article', 'LocalBusiness', 'Product' );
        return in_array( $other_type, $compatible, true );
    }

    /**
     * Get schema type display name
     *
     * @return string
     */
    public static function get_display_name() {
        return __( 'FAQ', 'seovela' );
    }

    /**
     * Get schema type description
     *
     * @return string
     */
    public static function get_description() {
        return __( 'Frequently Asked Questions. Displays Q&A directly in search results with expandable answers.', 'seovela' );
    }
}

