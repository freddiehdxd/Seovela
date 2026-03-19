<?php
/**
 * Seovela Settings Class
 *
 * Handles plugin settings registration and saving
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Seovela Settings Class
 */
class Seovela_Settings {

    /**
     * Hooks added flag
     *
     * @var bool
     */
    private static $hooks_added = false;

    /**
     * Constructor
     */
    public function __construct() {
        // Prevent duplicate hooks
        if ( self::$hooks_added ) {
            return;
        }
        self::$hooks_added = true;

        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        // Sitemap settings
        register_setting( 'seovela_sitemap_settings', 'seovela_sitemap_post_types', array(
            'type'              => 'array',
            'sanitize_callback' => array( 'Seovela_Helpers', 'sanitize_post_types' ),
            'default'           => array( 'post', 'page' ),
        ) );

        register_setting( 'seovela_sitemap_settings', 'seovela_sitemap_taxonomies', array(
            'type'              => 'array',
            'sanitize_callback' => array( 'Seovela_Helpers', 'sanitize_post_types' ),
            'default'           => array( 'category' ),
        ) );

        // Indexing settings
        register_setting( 'seovela_indexing_settings', 'seovela_global_noindex', array(
            'type'              => 'boolean',
            'default'           => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ) );

        register_setting( 'seovela_indexing_settings', 'seovela_global_nofollow', array(
            'type'              => 'boolean',
            'default'           => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ) );

        // Module toggles
        register_setting( 'seovela_modules', 'seovela_meta_enabled', array(
            'type'              => 'boolean',
            'default'           => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ) );

        register_setting( 'seovela_modules', 'seovela_sitemap_enabled', array(
            'type'              => 'boolean',
            'default'           => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ) );

        register_setting( 'seovela_modules', 'seovela_schema_enabled', array(
            'type'              => 'boolean',
            'default'           => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ) );

        register_setting( 'seovela_modules', 'seovela_optimizer_enabled', array(
            'type'              => 'boolean',
            'default'           => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ) );

        register_setting( 'seovela_modules', 'seovela_redirects_enabled', array(
            'type'              => 'boolean',
            'default'           => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ) );

        register_setting( 'seovela_modules', 'seovela_404_monitor_enabled', array(
            'type'              => 'boolean',
            'default'           => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ) );

        register_setting( 'seovela_modules', 'seovela_ai_enabled', array(
            'type'              => 'boolean',
            'default'           => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ) );

        register_setting( 'seovela_modules', 'seovela_internal_links_enabled', array(
            'type'              => 'boolean',
            'default'           => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ) );

        register_setting( 'seovela_modules', 'seovela_image_seo_enabled', array(
            'type'              => 'boolean',
            'default'           => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ) );

        register_setting( 'seovela_modules', 'seovela_gsc_integration_enabled', array(
            'type'              => 'boolean',
            'default'           => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ) );

        register_setting( 'seovela_modules', 'seovela_llms_txt_enabled', array(
            'type'              => 'boolean',
            'default'           => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ) );

        // LLMS Txt settings
        register_setting( 'seovela_llms_txt_settings', 'seovela_llms_txt_post_types', array(
            'type'              => 'array',
            'sanitize_callback' => array( 'Seovela_Helpers', 'sanitize_post_types' ),
            'default'           => array( 'post', 'page' ),
        ) );

        register_setting( 'seovela_llms_txt_settings', 'seovela_llms_txt_taxonomies', array(
            'type'              => 'array',
            'sanitize_callback' => array( 'Seovela_Helpers', 'sanitize_post_types' ),
            'default'           => array(),
        ) );

        register_setting( 'seovela_llms_txt_settings', 'seovela_llms_txt_limit', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 50,
        ) );

        register_setting( 'seovela_llms_txt_settings', 'seovela_llms_txt_additional_content', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default'           => '',
        ) );

        // AI settings
        register_setting( 'seovela_ai_settings', 'seovela_ai_endpoint', array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => '',
        ) );

        register_setting( 'seovela_ai_settings', 'seovela_ai_token', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
    }
}

