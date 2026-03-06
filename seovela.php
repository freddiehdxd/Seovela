<?php
/**
 * Plugin Name: Seovela
 * Plugin URI: https://seovela.com
 * Description: Lightweight, fully free SEO plugin with AI-powered optimization (BYOK). All features included — no premium tier.
 * Version: 2.1.0
 * Author: Freddie
 * Author URI: https://seovela.com
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: seovela
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'SEOVELA_VERSION', '2.1.0' );
define( 'SEOVELA_PLUGIN_FILE', __FILE__ );
define( 'SEOVELA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SEOVELA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SEOVELA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Include the main Seovela class
require_once SEOVELA_PLUGIN_DIR . 'includes/class-seovela-core.php';

/**
 * Main function to get Seovela instance
 *
 * @return Seovela_Core
 */
function seovela() {
    return Seovela_Core::instance();
}

// Initialize the plugin
seovela();

/**
 * Run migrations for existing installations
 */
function seovela_run_migrations() {
    $current_version = get_option( 'seovela_db_version', '0' );
    
    // Migration 1.2.3: Set recommended robots defaults
    if ( version_compare( $current_version, '1.2.3', '<' ) ) {
        // Update robots meta defaults for existing installations
        update_option( 'seovela_robots_index', 'index' );
        update_option( 'seovela_robots_max_snippet', '-1' );
        update_option( 'seovela_robots_max_video_preview', '-1' );
        update_option( 'seovela_robots_max_image_preview', 'standard' );
        update_option( 'seovela_noindex_empty_archives', '1' );
        
        update_option( 'seovela_db_version', '1.2.3' );
    }

    // Migration 2.0.0: Seovela Pro consolidation
    if ( version_compare( $current_version, '2.0.0', '<' ) ) {
        update_option( 'seovela_db_version', '2.0.0' );
    }

    // Migration 2.1.0: Remove Pro license artifacts from existing installations
    if ( version_compare( $current_version, '2.1.0', '<' ) ) {
        // Clean up license-related options
        delete_option( 'seovela_license_key' );
        delete_option( 'seovela_license_status' );
        delete_option( 'seovela_license_data' );
        delete_option( 'seovela_license_last_check' );
        delete_option( 'seovela_pro_license_key' );
        delete_option( 'seovela_pro_license_status' );
        delete_option( 'seovela_pro_license_data' );

        // Clean up transients
        delete_transient( 'seovela_license_check' );
        delete_transient( 'seovela_pro_license_check' );
        delete_transient( 'seovela_pro_update_check' );
        delete_transient( 'seovela_pro_rollback_error' );

        // Remove license cron
        $timestamp = wp_next_scheduled( 'seovela_daily_license_check' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'seovela_daily_license_check' );
        }

        // Enable AI module by default for existing users (was Pro-only)
        if ( get_option( 'seovela_ai_enabled' ) === false ) {
            update_option( 'seovela_ai_enabled', true );
        }

        update_option( 'seovela_db_version', '2.1.0' );
    }
}
add_action( 'admin_init', 'seovela_run_migrations' );

/**
 * Activation hook
 */
function seovela_activate() {
    // Set default options
    $defaults = array(
        'seovela_meta_enabled'      => true,
        'seovela_sitemap_enabled'   => true,
        'seovela_schema_enabled'    => true,
        'seovela_optimizer_enabled' => true,
        'seovela_ai_enabled'        => true,
        'seovela_redirects_enabled' => true,
        'seovela_404_monitor_enabled' => true,
        'seovela_internal_links_enabled' => true,
        'seovela_image_seo_enabled' => true,
        'seovela_gsc_integration_enabled' => false, // Requires OAuth setup
        'seovela_llms_txt_enabled'  => true,
        'seovela_sitemap_post_types' => array( 'post', 'page' ),
        'seovela_sitemap_taxonomies' => array( 'category' ),
        // Meta/Robots defaults
        'seovela_robots_index'             => 'index',
        'seovela_robots_max_snippet'       => '-1',
        'seovela_robots_max_video_preview' => '-1',
        'seovela_robots_max_image_preview' => 'standard',
        'seovela_noindex_empty_archives'   => '1',
    );

    // Options that should NOT autoload (not needed on every frontend page load)
    $no_autoload = array(
        'seovela_optimizer_enabled',
        'seovela_ai_enabled',
        'seovela_internal_links_enabled',
        'seovela_image_seo_enabled',
        'seovela_gsc_integration_enabled',
        'seovela_sitemap_post_types',
        'seovela_sitemap_taxonomies',
    );

    foreach ( $defaults as $key => $value ) {
        if ( get_option( $key ) === false ) {
            if ( in_array( $key, $no_autoload, true ) ) {
                add_option( $key, $value, '', 'no' );
            } else {
                add_option( $key, $value );
            }
        }
    }

    // Create database tables for Technical SEO features
    require_once SEOVELA_PLUGIN_DIR . 'modules/redirects/class-seovela-redirects.php';
    require_once SEOVELA_PLUGIN_DIR . 'modules/404-monitor/class-seovela-404-monitor.php';
    require_once SEOVELA_PLUGIN_DIR . 'modules/internal-links/class-seovela-internal-links.php';
    require_once SEOVELA_PLUGIN_DIR . 'modules/image-seo/class-seovela-image-seo.php';
    require_once SEOVELA_PLUGIN_DIR . 'modules/gsc-integration/class-seovela-gsc-integration.php';
    
    Seovela_Redirects::create_table();
    Seovela_404_Monitor::create_table();
    Seovela_Internal_Links::create_table();
    Seovela_Image_Seo::create_table();
    Seovela_Gsc_Integration::create_table();

    // Flush rewrite rules for sitemap
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'seovela_activate' );

/**
 * Deactivation hook
 */
function seovela_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'seovela_deactivate' );
