<?php
/**
 * Seovela Meta Module
 *
 * Handles meta titles and descriptions
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Seovela Meta Class
 */
class Seovela_Meta {

    /**
     * Constructor
     */
    public function __construct() {
        // Module is loaded, functionality is handled by metabox and core class
    }

    /**
     * Get meta data for a post
     *
     * @param int $post_id Post ID
     * @return array
     */
    public function get_post_meta( $post_id ) {
        return array(
            'title'       => get_post_meta( $post_id, '_seovela_meta_title', true ),
            'description' => get_post_meta( $post_id, '_seovela_meta_description', true ),
            'noindex'     => get_post_meta( $post_id, '_seovela_noindex', true ),
            'nofollow'    => get_post_meta( $post_id, '_seovela_nofollow', true ),
        );
    }

    /**
     * Save meta data for a post
     *
     * @param int   $post_id Post ID
     * @param array $data Meta data
     */
    public function save_post_meta( $post_id, $data ) {
        if ( isset( $data['title'] ) ) {
            update_post_meta( $post_id, '_seovela_meta_title', sanitize_text_field( $data['title'] ) );
        }

        if ( isset( $data['description'] ) ) {
            update_post_meta( $post_id, '_seovela_meta_description', sanitize_textarea_field( $data['description'] ) );
        }

        if ( isset( $data['noindex'] ) ) {
            update_post_meta( $post_id, '_seovela_noindex', (bool) $data['noindex'] );
        } else {
            delete_post_meta( $post_id, '_seovela_noindex' );
        }

        if ( isset( $data['nofollow'] ) ) {
            update_post_meta( $post_id, '_seovela_nofollow', (bool) $data['nofollow'] );
        } else {
            delete_post_meta( $post_id, '_seovela_nofollow' );
        }
    }
}

