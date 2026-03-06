<?php
/**
 * LocalBusiness Schema Type
 *
 * Uses existing Local SEO settings
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LocalBusiness Schema Class
 */
class Seovela_LocalBusiness_Schema {

    /**
     * Generate LocalBusiness schema
     *
     * @param int $post_id Post ID (not used, but kept for consistency)
     * @return array Schema array
     */
    public static function generate( $post_id = 0 ) {
        // Get Local SEO settings
        $local_settings = get_option( 'seovela_local_seo', array() );

        // Check if we have minimum required data
        if ( empty( $local_settings['business_name'] ) ) {
            return array();
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type'    => 'LocalBusiness',
            'name'     => sanitize_text_field( $local_settings['business_name'] ),
        );

        // Business type (can be more specific: Restaurant, Store, etc.)
        if ( ! empty( $local_settings['business_type'] ) ) {
            $schema['@type'] = sanitize_text_field( $local_settings['business_type'] );
        }

        // Description
        if ( ! empty( $local_settings['business_description'] ) ) {
            $schema['description'] = sanitize_text_field( $local_settings['business_description'] );
        }

        // URL
        $schema['url'] = home_url();

        // Telephone
        if ( ! empty( $local_settings['phone'] ) ) {
            $schema['telephone'] = sanitize_text_field( $local_settings['phone'] );
        }

        // Email
        if ( ! empty( $local_settings['email'] ) ) {
            $schema['email'] = sanitize_email( $local_settings['email'] );
        }

        // Address
        if ( ! empty( $local_settings['street_address'] ) || ! empty( $local_settings['city'] ) ) {
            $schema['address'] = array(
                '@type' => 'PostalAddress',
            );

            if ( ! empty( $local_settings['street_address'] ) ) {
                $schema['address']['streetAddress'] = sanitize_text_field( $local_settings['street_address'] );
            }

            if ( ! empty( $local_settings['city'] ) ) {
                $schema['address']['addressLocality'] = sanitize_text_field( $local_settings['city'] );
            }

            if ( ! empty( $local_settings['state'] ) ) {
                $schema['address']['addressRegion'] = sanitize_text_field( $local_settings['state'] );
            }

            if ( ! empty( $local_settings['postal_code'] ) ) {
                $schema['address']['postalCode'] = sanitize_text_field( $local_settings['postal_code'] );
            }

            if ( ! empty( $local_settings['country'] ) ) {
                $schema['address']['addressCountry'] = sanitize_text_field( $local_settings['country'] );
            }
        }

        // Geo coordinates
        if ( ! empty( $local_settings['latitude'] ) && ! empty( $local_settings['longitude'] ) ) {
            $schema['geo'] = array(
                '@type'     => 'GeoCoordinates',
                'latitude'  => floatval( $local_settings['latitude'] ),
                'longitude' => floatval( $local_settings['longitude'] ),
            );
        }

        // Logo/Image
        $logo_id = get_theme_mod( 'custom_logo' );
        if ( $logo_id ) {
            $logo_url = wp_get_attachment_image_url( $logo_id, 'full' );
            if ( $logo_url ) {
                $schema['image'] = esc_url( $logo_url );
                $schema['logo'] = esc_url( $logo_url );
            }
        } elseif ( ! empty( $local_settings['logo'] ) ) {
            $schema['image'] = esc_url( $local_settings['logo'] );
            $schema['logo'] = esc_url( $local_settings['logo'] );
        }

        // Opening hours
        if ( ! empty( $local_settings['opening_hours'] ) && is_array( $local_settings['opening_hours'] ) ) {
            $schema['openingHoursSpecification'] = array();
            
            foreach ( $local_settings['opening_hours'] as $day => $hours ) {
                if ( empty( $hours['open'] ) || empty( $hours['close'] ) ) {
                    continue;
                }

                $schema['openingHoursSpecification'][] = array(
                    '@type'     => 'OpeningHoursSpecification',
                    'dayOfWeek' => ucfirst( $day ),
                    'opens'     => sanitize_text_field( $hours['open'] ),
                    'closes'    => sanitize_text_field( $hours['close'] ),
                );
            }
        }

        // Price range
        if ( ! empty( $local_settings['price_range'] ) ) {
            $schema['priceRange'] = sanitize_text_field( $local_settings['price_range'] );
        }

        return apply_filters( 'seovela_localbusiness_schema', $schema, $post_id );
    }

    /**
     * Check if this schema type is compatible with another
     *
     * @param string $other_type Other schema type
     * @return bool
     */
    public static function is_compatible_with( $other_type ) {
        // LocalBusiness can work with FAQ
        $compatible = array( 'FAQ' );
        return in_array( $other_type, $compatible, true );
    }

    /**
     * Get schema type display name
     *
     * @return string
     */
    public static function get_display_name() {
        return __( 'Local Business', 'seovela' );
    }

    /**
     * Get schema type description
     *
     * @return string
     */
    public static function get_description() {
        return __( 'For local businesses. Shows business hours, location, contact info in search and maps. Uses Local SEO settings.', 'seovela' );
    }

    /**
     * Check if Local Business schema is configured
     *
     * @return bool
     */
    public static function is_configured() {
        $local_settings = get_option( 'seovela_local_seo', array() );
        return ! empty( $local_settings['business_name'] );
    }
}

