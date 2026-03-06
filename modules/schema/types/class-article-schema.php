<?php
/**
 * Article Schema Type
 *
 * Enhanced article schema for pages and posts
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Article Schema Class
 */
class Seovela_Article_Schema {

    /**
     * Generate Article schema
     *
     * @param int $post_id Post ID
     * @return array Schema array
     */
    public static function generate( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return array();
        }

        $schema = array(
            '@context'      => 'https://schema.org',
            '@type'         => 'Article',
            'headline'      => get_the_title( $post_id ),
            'datePublished' => get_the_date( 'c', $post_id ),
            'dateModified'  => get_the_modified_date( 'c', $post_id ),
        );

        // Author
        $author_id = $post->post_author;
        $schema['author'] = array(
            '@type' => 'Person',
            'name'  => get_the_author_meta( 'display_name', $author_id ),
        );

        // Author URL if available
        $author_url = get_the_author_meta( 'user_url', $author_id );
        if ( ! empty( $author_url ) ) {
            $schema['author']['url'] = esc_url( $author_url );
        }

        // Description
        $description = get_post_meta( $post_id, '_seovela_meta_description', true );
        if ( empty( $description ) ) {
            $description = wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '...' );
        }
        if ( ! empty( $description ) ) {
            $schema['description'] = sanitize_text_field( $description );
        }

        // Featured image
        if ( has_post_thumbnail( $post_id ) ) {
            $image_url = get_the_post_thumbnail_url( $post_id, 'full' );
            if ( $image_url ) {
                $schema['image'] = array(
                    '@type' => 'ImageObject',
                    'url'   => esc_url( $image_url ),
                );

                // Get image dimensions
                $image_id = get_post_thumbnail_id( $post_id );
                $image_meta = wp_get_attachment_metadata( $image_id );
                if ( isset( $image_meta['width'] ) && isset( $image_meta['height'] ) ) {
                    $schema['image']['width']  = $image_meta['width'];
                    $schema['image']['height'] = $image_meta['height'];
                }
            }
        }

        // Publisher (organization)
        $schema['publisher'] = array(
            '@type' => 'Organization',
            'name'  => get_bloginfo( 'name' ),
            'url'   => home_url(),
        );

        // Publisher logo
        $custom_logo_id = get_theme_mod( 'custom_logo' );
        if ( $custom_logo_id ) {
            $logo_url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
            if ( $logo_url ) {
                $schema['publisher']['logo'] = array(
                    '@type' => 'ImageObject',
                    'url'   => esc_url( $logo_url ),
                );
            }
        }

        // Article body (optional, for better understanding)
        $content = wp_strip_all_tags( $post->post_content );
        if ( ! empty( $content ) ) {
            $schema['articleBody'] = wp_trim_words( $content, 100, '...' );
        }

        // Word count
        $word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );
        if ( $word_count > 0 ) {
            $schema['wordCount'] = $word_count;
        }

        // Main entity of page
        $schema['mainEntityOfPage'] = array(
            '@type' => 'WebPage',
            '@id'   => get_permalink( $post_id ),
        );

        return apply_filters( 'seovela_article_schema', $schema, $post_id );
    }

    /**
     * Check if this schema type is compatible with another
     *
     * @param string $other_type Other schema type
     * @return bool
     */
    public static function is_compatible_with( $other_type ) {
        $compatible = array( 'FAQ', 'HowTo' );
        return in_array( $other_type, $compatible, true );
    }

    /**
     * Get schema type display name
     *
     * @return string
     */
    public static function get_display_name() {
        return __( 'Article', 'seovela' );
    }

    /**
     * Get schema type description
     *
     * @return string
     */
    public static function get_description() {
        return __( 'Suitable for news articles, blog posts, and editorial content. Provides rich search results.', 'seovela' );
    }
}

