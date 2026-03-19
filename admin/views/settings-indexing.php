<?php
/**
 * Seovela Indexing Settings View - Premium Design
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="seovela-premium-page">

    <!-- Premium Header -->
    <div class="seovela-page-header">
        <div class="seovela-page-header-bg"></div>
        <div class="seovela-page-header-content">
            <div class="seovela-page-header-top">
                <div class="seovela-page-header-text">
                    <div class="seovela-page-breadcrumb">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela' ) ); ?>">Seovela</a>
                        <span class="sep">/</span>
                        <span class="current"><?php esc_html_e( 'Settings', 'seovela' ); ?></span>
                    </div>
                    <h1><?php esc_html_e( 'SEO Settings', 'seovela' ); ?></h1>
                    <p><?php esc_html_e( 'Configure titles, meta tags, sitemaps, schema, and more to optimize your site for search engines.', 'seovela' ); ?></p>
                </div>
            </div>
            <div class="seovela-page-header-tabs">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=meta' ) ); ?>" class="seovela-header-tab">
                    <?php Seovela_Icons::render( 'meta', 16 ); ?>
                    <?php esc_html_e( 'Titles & Meta', 'seovela' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=sitemap' ) ); ?>" class="seovela-header-tab">
                    <?php Seovela_Icons::render( 'sitemap', 16 ); ?>
                    <?php esc_html_e( 'Sitemap', 'seovela' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=schema' ) ); ?>" class="seovela-header-tab">
                    <?php Seovela_Icons::render( 'schema', 16 ); ?>
                    <?php esc_html_e( 'Schema', 'seovela' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=indexing' ) ); ?>" class="seovela-header-tab active">
                    <?php Seovela_Icons::render( 'indexing', 16 ); ?>
                    <?php esc_html_e( 'Indexing', 'seovela' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=ai' ) ); ?>" class="seovela-header-tab">
                    <?php Seovela_Icons::render( 'ai', 16 ); ?>
                    <?php esc_html_e( 'AI Optimization', 'seovela' ); ?>
                </a>
            </div>
        </div>
    </div>

    <?php settings_errors(); ?>

    <div class="seovela-page-body">

        <!-- Global Indexing Settings -->
        <form method="post" action="options.php">
            <?php settings_fields( 'seovela_indexing_settings' ); ?>

            <div class="seovela-premium-card">
                <div class="seovela-premium-card-header">
                    <div class="card-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                        <span class="dashicons dashicons-search" style="color: #fff;"></span>
                    </div>
                    <div>
                        <h2><?php esc_html_e( 'Global Indexing Settings', 'seovela' ); ?></h2>
                        <p><?php esc_html_e( 'Control how search engines index your entire website.', 'seovela' ); ?></p>
                    </div>
                </div>
                <div class="seovela-premium-card-body">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Global Noindex', 'seovela' ); ?></th>
                            <td>
                                <div class="seovela-toggle-item">
                                    <label class="seovela-toggle-modern">
                                        <input type="checkbox" name="seovela_global_noindex" value="1" <?php checked( get_option( 'seovela_global_noindex', false ) ); ?> />
                                        <span class="seovela-toggle-slider-modern"></span>
                                    </label>
                                    <span class="seovela-toggle-label"><?php esc_html_e( 'Discourage search engines from indexing this site', 'seovela' ); ?></span>
                                    <p class="description"><?php esc_html_e( 'Warning: This will prevent search engines from indexing your entire site. Use with caution!', 'seovela' ); ?></p>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e( 'Global Nofollow', 'seovela' ); ?></th>
                            <td>
                                <div class="seovela-toggle-item">
                                    <label class="seovela-toggle-modern">
                                        <input type="checkbox" name="seovela_global_nofollow" value="1" <?php checked( get_option( 'seovela_global_nofollow', false ) ); ?> />
                                        <span class="seovela-toggle-slider-modern"></span>
                                    </label>
                                    <span class="seovela-toggle-label"><?php esc_html_e( 'Add nofollow to all links sitewide', 'seovela' ); ?></span>
                                    <p class="description"><?php esc_html_e( 'This tells search engines not to follow any links on your site.', 'seovela' ); ?></p>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="submit">
                    <?php submit_button( __( 'Save Indexing Settings', 'seovela' ), 'primary', 'submit', false ); ?>
                </div>
            </div>
        </form>

        <!-- Per-Post Settings Info -->
        <div class="seovela-premium-info info-blue">
            <div class="info-icon">&#128221;</div>
            <div class="info-content">
                <h3><?php esc_html_e( 'Per-Post Settings', 'seovela' ); ?></h3>
                <p><?php esc_html_e( 'You can control indexing for individual posts and pages in the post editor. Look for the "Seovela SEO" meta box and check the appropriate options.', 'seovela' ); ?></p>
            </div>
        </div>

    </div><!-- .seovela-page-body -->
</div><!-- .seovela-premium-page -->
