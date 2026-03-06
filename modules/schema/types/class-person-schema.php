<?php
/**
 * Person Schema Type
 *
 * Author/person schema for author pages and profiles
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Person Schema Class
 */
class Seovela_Person_Schema {

    /**
     * Generate Person schema
     *
     * @param int $post_id Post ID
     * @return array Schema array
     */
    public static function generate( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return array();
        }

        // Get person data (could be author or custom person)
        $person_type = get_post_meta( $post_id, '_seovela_person_type', true );
        
        if ( $person_type === 'author' ) {
            return self::generate_author_schema( $post->post_author );
        } else {
            return self::generate_custom_person_schema( $post_id );
        }
    }

    /**
     * Generate schema for post author
     *
     * @param int $author_id Author ID
     * @return array Schema array
     */
    private static function generate_author_schema( $author_id ) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type'    => 'Person',
            'name'     => get_the_author_meta( 'display_name', $author_id ),
        );

        // Description/Bio
        $bio = get_the_author_meta( 'description', $author_id );
        if ( ! empty( $bio ) ) {
            $schema['description'] = sanitize_text_field( $bio );
        }

        // URL
        $url = get_the_author_meta( 'user_url', $author_id );
        if ( ! empty( $url ) ) {
            $schema['url'] = esc_url( $url );
        }

        // Email (optional, usually not recommended for privacy)
        // Not including by default

        // Social profiles
        $social_links = array();
        
        $twitter = get_the_author_meta( 'twitter', $author_id );
        if ( ! empty( $twitter ) ) {
            $social_links[] = esc_url( $twitter );
        }

        $facebook = get_the_author_meta( 'facebook', $author_id );
        if ( ! empty( $facebook ) ) {
            $social_links[] = esc_url( $facebook );
        }

        $linkedin = get_the_author_meta( 'linkedin', $author_id );
        if ( ! empty( $linkedin ) ) {
            $social_links[] = esc_url( $linkedin );
        }

        if ( ! empty( $social_links ) ) {
            $schema['sameAs'] = $social_links;
        }

        // Avatar/Image
        $avatar_url = get_avatar_url( $author_id, array( 'size' => 512 ) );
        if ( $avatar_url ) {
            $schema['image'] = esc_url( $avatar_url );
        }

        return $schema;
    }

    /**
     * Generate schema for custom person
     *
     * @param int $post_id Post ID
     * @return array Schema array
     */
    private static function generate_custom_person_schema( $post_id ) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type'    => 'Person',
        );

        // Name (required)
        $name = get_post_meta( $post_id, '_seovela_person_name', true );
        if ( empty( $name ) ) {
            $name = get_the_title( $post_id );
        }
        $schema['name'] = sanitize_text_field( $name );

        // Job title
        $job_title = get_post_meta( $post_id, '_seovela_person_job_title', true );
        if ( ! empty( $job_title ) ) {
            $schema['jobTitle'] = sanitize_text_field( $job_title );
        }

        // Description
        $description = get_post_meta( $post_id, '_seovela_person_description', true );
        if ( ! empty( $description ) ) {
            $schema['description'] = sanitize_text_field( $description );
        }

        // URL/Website
        $url = get_post_meta( $post_id, '_seovela_person_url', true );
        if ( ! empty( $url ) ) {
            $schema['url'] = esc_url( $url );
        }

        // Email
        $email = get_post_meta( $post_id, '_seovela_person_email', true );
        if ( ! empty( $email ) ) {
            $schema['email'] = sanitize_email( $email );
        }

        // Telephone
        $telephone = get_post_meta( $post_id, '_seovela_person_telephone', true );
        if ( ! empty( $telephone ) ) {
            $schema['telephone'] = sanitize_text_field( $telephone );
        }

        // Image
        if ( has_post_thumbnail( $post_id ) ) {
            $image_url = get_the_post_thumbnail_url( $post_id, 'full' );
            if ( $image_url ) {
                $schema['image'] = esc_url( $image_url );
            }
        }

        // Social profiles
        $social = get_post_meta( $post_id, '_seovela_person_social', true );
        if ( ! empty( $social ) && is_array( $social ) ) {
            $schema['sameAs'] = array_map( 'esc_url', $social );
        }

        // Organization affiliation
        $organization = get_post_meta( $post_id, '_seovela_person_organization', true );
        if ( ! empty( $organization ) ) {
            $schema['worksFor'] = array(
                '@type' => 'Organization',
                'name'  => sanitize_text_field( $organization ),
            );
        }

        return apply_filters( 'seovela_person_schema', $schema, $post_id );
    }

    /**
     * Check if this schema type is compatible with another
     *
     * @param string $other_type Other schema type
     * @return bool
     */
    public static function is_compatible_with( $other_type ) {
        // Person schema typically stands alone
        return false;
    }

    /**
     * Get schema type display name
     *
     * @return string
     */
    public static function get_display_name() {
        return __( 'Person', 'seovela' );
    }

    /**
     * Get schema type description
     *
     * @return string
     */
    public static function get_description() {
        return __( 'For author profiles and people pages. Shows person info, job title, and social profiles in knowledge panels.', 'seovela' );
    }
}

