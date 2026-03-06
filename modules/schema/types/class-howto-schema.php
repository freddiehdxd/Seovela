<?php
/**
 * HowTo Schema Type
 *
 * Step-by-step instructional content schema
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * HowTo Schema Class
 */
class Seovela_HowTo_Schema {

    /**
     * Generate HowTo schema
     *
     * @param int $post_id Post ID
     * @return array Schema array
     */
    public static function generate( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return array();
        }

        // Get HowTo data
        $steps = get_post_meta( $post_id, '_seovela_howto_steps', true );
        
        if ( empty( $steps ) || ! is_array( $steps ) ) {
            return array();
        }

        $schema = array(
            '@context'    => 'https://schema.org',
            '@type'       => 'HowTo',
            'name'        => get_the_title( $post_id ),
            'description' => '',
            'step'        => array(),
        );

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
                $schema['image'] = esc_url( $image_url );
            }
        }

        // Total time (optional)
        $total_time = get_post_meta( $post_id, '_seovela_howto_total_time', true );
        if ( ! empty( $total_time ) ) {
            $schema['totalTime'] = sanitize_text_field( $total_time ); // Format: PT30M (30 minutes)
        }

        // Tools needed (optional)
        $tools = get_post_meta( $post_id, '_seovela_howto_tools', true );
        if ( ! empty( $tools ) && is_array( $tools ) ) {
            $schema['tool'] = array();
            foreach ( $tools as $tool ) {
                if ( ! empty( $tool ) ) {
                    $schema['tool'][] = array(
                        '@type' => 'HowToTool',
                        'name'  => sanitize_text_field( $tool ),
                    );
                }
            }
        }

        // Materials needed (optional)
        $materials = get_post_meta( $post_id, '_seovela_howto_materials', true );
        if ( ! empty( $materials ) && is_array( $materials ) ) {
            $schema['supply'] = array();
            foreach ( $materials as $material ) {
                if ( ! empty( $material ) ) {
                    $schema['supply'][] = array(
                        '@type' => 'HowToSupply',
                        'name'  => sanitize_text_field( $material ),
                    );
                }
            }
        }

        // Steps
        $step_number = 1;
        foreach ( $steps as $step ) {
            if ( empty( $step['text'] ) ) {
                continue;
            }

            $step_schema = array(
                '@type' => 'HowToStep',
                'name'  => ! empty( $step['name'] ) ? sanitize_text_field( $step['name'] ) : 'Step ' . $step_number,
                'text'  => wp_kses_post( $step['text'] ),
            );

            // Step image (optional)
            if ( ! empty( $step['image'] ) ) {
                $step_schema['image'] = esc_url( $step['image'] );
            }

            // Step URL (optional)
            if ( ! empty( $step['url'] ) ) {
                $step_schema['url'] = esc_url( $step['url'] );
            }

            $schema['step'][] = $step_schema;
            $step_number++;
        }

        // If no valid steps, return empty
        if ( empty( $schema['step'] ) ) {
            return array();
        }

        return apply_filters( 'seovela_howto_schema', $schema, $post_id );
    }

    /**
     * Check if this schema type is compatible with another
     *
     * @param string $other_type Other schema type
     * @return bool
     */
    public static function is_compatible_with( $other_type ) {
        // HowTo generally doesn't combine well with Article
        $compatible = array( 'Product' );
        return in_array( $other_type, $compatible, true );
    }

    /**
     * Get schema type display name
     *
     * @return string
     */
    public static function get_display_name() {
        return __( 'HowTo', 'seovela' );
    }

    /**
     * Get schema type description
     *
     * @return string
     */
    public static function get_description() {
        return __( 'Step-by-step instructions. Perfect for tutorials, recipes, and guides. Shows steps in search results.', 'seovela' );
    }
}

