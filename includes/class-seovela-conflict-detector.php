<?php
/**
 * Seovela Conflict Detector
 *
 * Detects conflicts with other SEO plugins
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Seovela Conflict Detector Class
 */
class Seovela_Conflict_Detector {

    /**
     * Known SEO plugins to check for
     *
     * @var array
     */
    private static $seo_plugins = array(
        'wordpress-seo/wp-seo.php'                    => 'Yoast SEO',
        'wordpress-seo-premium/wp-seo-premium.php'    => 'Yoast SEO Premium',
        'seo-by-rank-math/rank-math.php'              => 'Rank Math',
        'seo-by-rank-math-pro/rank-math-pro.php'      => 'Rank Math Pro',
        'wp-seopress/seopress.php'                    => 'SEOPress',
        'wp-seopress-pro/seopress-pro.php'            => 'SEOPress Pro',
        'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All in One SEO Pack',
        'all-in-one-seo-pack-pro/all_in_one_seo_pack.php' => 'All in One SEO Pro',
        'smartcrawl-seo/wpmu-dev-seo.php'             => 'SmartCrawl',
    );

    /**
     * Check for conflicts with other SEO plugins
     */
    public static function check_conflicts() {
        if ( ! is_admin() ) {
            return;
        }

        $active_plugins = get_option( 'active_plugins', array() );
        $conflicts = array();

        foreach ( self::$seo_plugins as $plugin_path => $plugin_name ) {
            if ( in_array( $plugin_path, $active_plugins ) || self::is_plugin_active_for_network( $plugin_path ) ) {
                $conflicts[] = $plugin_name;
            }
        }

        if ( ! empty( $conflicts ) ) {
            add_action( 'admin_notices', function() use ( $conflicts ) {
                self::display_conflict_notice( $conflicts );
            } );
        }
    }

    /**
     * Check if plugin is active for network
     *
     * @param string $plugin_path Plugin path
     * @return bool
     */
    private static function is_plugin_active_for_network( $plugin_path ) {
        if ( ! is_multisite() ) {
            return false;
        }

        $plugins = get_site_option( 'active_sitewide_plugins', array() );
        return isset( $plugins[ $plugin_path ] );
    }

    /**
     * Display conflict notice
     *
     * @param array $conflicts Array of conflicting plugin names
     */
    private static function display_conflict_notice( $conflicts ) {
        $plugin_list = implode( ', ', array_map( 'esc_html', $conflicts ) );
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php esc_html_e( 'Seovela: SEO Plugin Conflict Detected', 'seovela' ); ?></strong>
            </p>
            <p>
                <?php
                printf(
                    /* translators: %s: List of conflicting plugin names */
                    esc_html__( 'You have other SEO plugins active: %s. Having multiple SEO plugins active may cause conflicts and duplicate meta tags. We recommend keeping only one main SEO plugin active.', 'seovela' ),
                    $plugin_list
                );
                ?>
            </p>
        </div>
        <?php
    }
}

