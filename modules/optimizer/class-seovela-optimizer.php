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
     * Uses direct SQL COUNT queries instead of loading every post object,
     * and caches the result for 5 minutes to keep the dashboard fast.
     *
     * @return array
     */
    public function get_stats() {
        $cached = get_transient( 'seovela_dashboard_stats' );
        if ( false !== $cached ) {
            return $cached;
        }

        global $wpdb;

        // Total published posts + pages in a single query
        $total = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type IN ('post','page') AND post_status = 'publish'"
        );

        // Posts with non-empty meta title
        $with_title = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type IN ('post','page')
               AND p.post_status = 'publish'
               AND pm.meta_key = %s
               AND pm.meta_value != ''",
            '_seovela_meta_title'
        ) );

        // Posts with non-empty meta description
        $with_desc = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type IN ('post','page')
               AND p.post_status = 'publish'
               AND pm.meta_key = %s
               AND pm.meta_value != ''",
            '_seovela_meta_description'
        ) );

        $stats = array(
            'total_posts'                    => $total,
            'posts_with_meta_title'          => $with_title,
            'posts_without_meta_title'       => max( 0, $total - $with_title ),
            'posts_with_meta_description'    => $with_desc,
            'posts_without_meta_description' => max( 0, $total - $with_desc ),
            'meta_title_percentage'          => $total > 0 ? round( ( $with_title / $total ) * 100, 1 ) : 0,
            'meta_description_percentage'    => $total > 0 ? round( ( $with_desc / $total ) * 100, 1 ) : 0,
        );

        set_transient( 'seovela_dashboard_stats', $stats, 5 * MINUTE_IN_SECONDS );

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

