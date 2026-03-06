<?php
/**
 * Seovela Uninstall
 *
 * Runs when the plugin is deleted (NOT on deactivation).
 * Removes all plugin data including options, post meta, database tables, and transients.
 *
 * @package Seovela
 */

// Exit if not called by WordPress
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Clean up all Seovela Pro data
 */
function seovela_uninstall_cleanup() {
    // phpcs:disable WordPress.DB.DirectDatabaseQuery
    global $wpdb;

    // ===========================================
    // 1. Delete all plugin options
    // ===========================================
    $options_to_delete = array(
        // Module toggles
        'seovela_meta_enabled',
        'seovela_sitemap_enabled',
        'seovela_schema_enabled',
        'seovela_optimizer_enabled',
        'seovela_ai_enabled',
        'seovela_redirects_enabled',
        'seovela_404_monitor_enabled',
        'seovela_internal_links_enabled',
        'seovela_image_seo_enabled',
        'seovela_gsc_integration_enabled',
        'seovela_llms_txt_enabled',
        'seovela_breadcrumbs_enabled',
        
        // Sitemap settings
        'seovela_sitemap_post_types',
        'seovela_sitemap_taxonomies',
        
        // Schema settings
        'seovela_schema_organization_name',
        'seovela_schema_organization_logo',
        'seovela_schema_social_profiles',
        'seovela_schema_local_business',
        
        // AI settings
        'seovela_ai_provider',
        'seovela_ai_api_key',
        'seovela_ai_endpoint',
        'seovela_ai_model',
        'seovela_ai_temperature',
        'seovela_ai_post_types',
        'seovela_openai_api_key',
        'seovela_openai_model',
        'seovela_gemini_api_key',
        'seovela_gemini_model',
        'seovela_claude_api_key',
        'seovela_claude_model',
        
        // GSC settings
        'seovela_gsc_access_token',
        'seovela_gsc_refresh_token',
        'seovela_gsc_site_url',
        'seovela_gsc_client_id',
        'seovela_gsc_client_secret',
        
        // LLMS.txt settings
        'seovela_llms_txt_content',
        'seovela_llms_txt_full_content',
        
        // General settings
        'seovela_title_separator',
        'seovela_homepage_title',
        'seovela_homepage_description',
        'seovela_default_robots',
        
        // Internal tracking
        'seovela_tables_created_v120',
        'seovela_version',
        'seovela_db_version',
        
        // Cache keys
        'seovela_sitemap_cache',
        

    );

    foreach ( $options_to_delete as $option ) {
        delete_option( $option );
    }

    // Delete any options that start with seovela_ (catch-all)
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 'seovela\_%' ) );
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 'seovela\_pro\_%' ) );

    // ===========================================
    // 2. Delete all post meta
    // ===========================================
    $meta_keys_to_delete = array(
        '_seovela_meta_title',
        '_seovela_meta_description',
        '_seovela_focus_keyword',
        '_seovela_focus_keywords',
        '_seovela_robots_noindex',
        '_seovela_robots_nofollow',
        '_seovela_canonical_url',
        '_seovela_schema_type',
        '_seovela_schema_data',
        '_seovela_og_title',
        '_seovela_og_description',
        '_seovela_og_image',
        '_seovela_twitter_title',
        '_seovela_twitter_description',
        '_seovela_twitter_image',
        '_seovela_seo_score',
        '_seovela_readability_score',
        '_seovela_primary_category',
    );

    foreach ( $meta_keys_to_delete as $meta_key ) {
        delete_post_meta_by_key( $meta_key );
    }

    // Delete any post meta that starts with _seovela_ (catch-all)
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s", '\_seovela\_%' ) );

    // ===========================================
    // 3. Delete term meta
    // ===========================================
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->termmeta} WHERE meta_key LIKE %s", '\_seovela\_%' ) );
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->termmeta} WHERE meta_key LIKE %s", 'seovela\_%' ) );

    // ===========================================
    // 4. Delete user meta
    // ===========================================
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", 'seovela\_%' ) );

    // ===========================================
    // 5. Drop custom database tables
    // ===========================================
    $tables_to_drop = array(
        $wpdb->prefix . 'seovela_redirects',
        $wpdb->prefix . 'seovela_404_log',
        $wpdb->prefix . 'seovela_internal_links',
        $wpdb->prefix . 'seovela_image_seo',
        $wpdb->prefix . 'seovela_gsc_data',
    );

    foreach ( $tables_to_drop as $table ) {
        $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are hardcoded plugin prefixed values.
    }

    // ===========================================
    // 6. Delete transients
    // ===========================================
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '\_transient\_seovela\_%' ) );
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '\_transient\_timeout\_seovela\_%' ) );
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '\_site\_transient\_seovela\_%' ) );
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '\_site\_transient\_timeout\_seovela\_%' ) );

    // ===========================================
    // 7. Clear any scheduled cron events
    // ===========================================
    wp_clear_scheduled_hook( 'seovela_daily_cleanup' );
    wp_clear_scheduled_hook( 'seovela_sitemap_ping' );
    wp_clear_scheduled_hook( 'seovela_gsc_sync' );

    // ===========================================
    // 8. Clear rewrite rules
    // ===========================================
    flush_rewrite_rules();
}

// Run the cleanup
seovela_uninstall_cleanup();
