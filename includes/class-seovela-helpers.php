<?php
/**
 * Seovela Helper Functions
 *
 * Utility functions used throughout the plugin
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Seovela Helpers Class
 */
class Seovela_Helpers {

    /**
     * Check if all features are available
     *
     * Since Seovela is now fully free and open-source, this always returns true.
     *
     * @return bool
     */
    public static function is_pro_active() {
        return true;
    }

    /**
     * Truncate text to specific length
     *
     * @param string $text Text to truncate
     * @param int    $length Maximum length
     * @return string
     */
    public static function truncate( $text, $length = 160 ) {
        if ( strlen( $text ) <= $length ) {
            return $text;
        }
        return substr( $text, 0, $length ) . '...';
    }

    /**
     * Get character count with color coding
     *
     * @param int $count Character count
     * @param int $ideal Ideal count
     * @param int $max Maximum count
     * @return string CSS class
     */
    public static function get_count_class( $count, $ideal, $max ) {
        if ( $count < $ideal ) {
            return 'seovela-count-short';
        } elseif ( $count > $max ) {
            return 'seovela-count-long';
        } else {
            return 'seovela-count-good';
        }
    }

    /**
     * Sanitize array of post types
     *
     * @param array $post_types Array of post type slugs
     * @return array
     */
    public static function sanitize_post_types( $post_types ) {
        if ( ! is_array( $post_types ) ) {
            return array();
        }
        return array_map( 'sanitize_key', $post_types );
    }

    /**
     * Get all public post types
     *
     * @return array
     */
    public static function get_public_post_types() {
        $post_types = get_post_types( array( 'public' => true ), 'objects' );
        $output = array();

        foreach ( $post_types as $post_type ) {
            if ( $post_type->name !== 'attachment' ) {
                $output[ $post_type->name ] = $post_type->label;
            }
        }

        return $output;
    }

    /**
     * Get all public taxonomies
     *
     * @return array
     */
    public static function get_public_taxonomies() {
        $taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
        $output = array();

        foreach ( $taxonomies as $taxonomy ) {
            $output[ $taxonomy->name ] = $taxonomy->label;
        }

        return $output;
    }

    /**
     * Format date for display
     *
     * @param string $date Date string
     * @return string
     */
    public static function format_date( $date ) {
        if ( empty( $date ) ) {
            return __( 'N/A', 'seovela' );
        }
        return date_i18n( get_option( 'date_format' ), strtotime( $date ) );
    }

    /**
     * Get plugin settings URL
     *
     * @return string
     */
    public static function get_settings_url() {
        return admin_url( 'admin.php?page=seovela-settings' );
    }
}
