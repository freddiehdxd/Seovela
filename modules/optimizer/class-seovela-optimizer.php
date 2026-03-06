<?php
/**
 * Seovela Optimizer Module
 *
 * Basic SEO analytics and optimization tracking
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Seovela Optimizer Class
 */
class Seovela_Optimizer {

    /**
     * Constructor
     */
    public function __construct() {
        // Module loaded
    }

    /**
     * Get SEO stats for dashboard
     *
     * @return array
     */
    public function get_stats() {
        $stats = array(
            'posts_with_meta_title'       => 0,
            'posts_without_meta_title'    => 0,
            'posts_with_meta_description' => 0,
            'posts_without_meta_description' => 0,
            'total_posts'                 => 0,
        );

        // Get all published posts
        $posts = get_posts( array(
            'post_type'      => array( 'post', 'page' ),
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ) );

        $stats['total_posts'] = count( $posts );

        foreach ( $posts as $post ) {
            $meta_title = get_post_meta( $post->ID, '_seovela_meta_title', true );
            $meta_description = get_post_meta( $post->ID, '_seovela_meta_description', true );

            if ( ! empty( $meta_title ) ) {
                $stats['posts_with_meta_title']++;
            } else {
                $stats['posts_without_meta_title']++;
            }

            if ( ! empty( $meta_description ) ) {
                $stats['posts_with_meta_description']++;
            } else {
                $stats['posts_without_meta_description']++;
            }
        }

        // Calculate percentages
        if ( $stats['total_posts'] > 0 ) {
            $stats['meta_title_percentage'] = round( ( $stats['posts_with_meta_title'] / $stats['total_posts'] ) * 100, 1 );
            $stats['meta_description_percentage'] = round( ( $stats['posts_with_meta_description'] / $stats['total_posts'] ) * 100, 1 );
        } else {
            $stats['meta_title_percentage'] = 0;
            $stats['meta_description_percentage'] = 0;
        }

        return $stats;
    }

    /**
     * Analyze single post SEO
     *
     * @param int $post_id Post ID
     * @return array
     */
    public function analyze_post( $post_id ) {
        $post = get_post( $post_id );
        $analysis = array(
            'score'   => 0,
            'issues'  => array(),
            'success' => array(),
        );

        // Check meta title
        $meta_title = get_post_meta( $post_id, '_seovela_meta_title', true );
        if ( empty( $meta_title ) ) {
            $analysis['issues'][] = __( 'Missing meta title', 'seovela' );
        } else {
            $title_length = strlen( $meta_title );
            if ( $title_length < 30 ) {
                $analysis['issues'][] = __( 'Meta title is too short', 'seovela' );
            } elseif ( $title_length > 60 ) {
                $analysis['issues'][] = __( 'Meta title is too long', 'seovela' );
            } else {
                $analysis['success'][] = __( 'Meta title looks good', 'seovela' );
                $analysis['score'] += 30;
            }
        }

        // Check meta description
        $meta_description = get_post_meta( $post_id, '_seovela_meta_description', true );
        if ( empty( $meta_description ) ) {
            $analysis['issues'][] = __( 'Missing meta description', 'seovela' );
        } else {
            $desc_length = strlen( $meta_description );
            if ( $desc_length < 120 ) {
                $analysis['issues'][] = __( 'Meta description is too short', 'seovela' );
            } elseif ( $desc_length > 160 ) {
                $analysis['issues'][] = __( 'Meta description is too long', 'seovela' );
            } else {
                $analysis['success'][] = __( 'Meta description looks good', 'seovela' );
                $analysis['score'] += 30;
            }
        }

        // Check content length
        $content_length = strlen( strip_tags( $post->post_content ) );
        if ( $content_length < 300 ) {
            $analysis['issues'][] = __( 'Content is too short (less than 300 characters)', 'seovela' );
        } else {
            $analysis['success'][] = __( 'Content length is good', 'seovela' );
            $analysis['score'] += 20;
        }

        // Check featured image
        if ( ! has_post_thumbnail( $post_id ) ) {
            $analysis['issues'][] = __( 'Missing featured image', 'seovela' );
        } else {
            $analysis['success'][] = __( 'Featured image is set', 'seovela' );
            $analysis['score'] += 20;
        }

        return $analysis;
    }
}

