<?php
/**
 * Seovela Sitemap Settings View - Premium Design
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Handle regenerate sitemap
if ( isset( $_POST['seovela_regenerate_sitemap'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seovela_sitemap_nonce'] ) ), 'seovela_regenerate_sitemap' ) ) {
    flush_rewrite_rules();
    add_settings_error( 'seovela_sitemap', 'sitemap_regenerated', __( 'Sitemap regenerated successfully!', 'seovela' ), 'success' );
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
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=sitemap' ) ); ?>" class="seovela-header-tab active">
                    <?php Seovela_Icons::render( 'sitemap', 16 ); ?>
                    <?php esc_html_e( 'Sitemap', 'seovela' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=schema' ) ); ?>" class="seovela-header-tab">
                    <?php Seovela_Icons::render( 'schema', 16 ); ?>
                    <?php esc_html_e( 'Schema', 'seovela' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=indexing' ) ); ?>" class="seovela-header-tab">
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

        <!-- Sitemap URL Info -->
        <div class="seovela-premium-info info-blue">
            <div class="info-icon">&#127758;</div>
            <div class="info-content">
                <h3><?php esc_html_e( 'Your Sitemap', 'seovela' ); ?></h3>
                <p>
                    <?php esc_html_e( 'Your XML sitemap is available at:', 'seovela' ); ?>
                    <a href="<?php echo esc_url( home_url( '/sitemap.xml' ) ); ?>" target="_blank" rel="noopener">
                        <?php echo esc_url( home_url( '/sitemap.xml' ) ); ?>
                    </a>
                </p>
            </div>
        </div>

        <!-- Sitemap Settings Card -->
        <form method="post" action="options.php">
            <?php settings_fields( 'seovela_sitemap_settings' ); ?>

            <div class="seovela-premium-card">
                <div class="seovela-premium-card-header">
                    <div class="card-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <span class="dashicons dashicons-networking" style="color: #fff;"></span>
                    </div>
                    <div>
                        <h2><?php esc_html_e( 'XML Sitemap Settings', 'seovela' ); ?></h2>
                        <p><?php esc_html_e( 'Choose which content types appear in your sitemap.', 'seovela' ); ?></p>
                    </div>
                </div>
                <div class="seovela-premium-card-body">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Include Post Types', 'seovela' ); ?></th>
                            <td>
                                <div class="seovela-checkbox-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px;">
                                    <?php
                                    $post_types = Seovela_Helpers::get_public_post_types();
                                    $selected = get_option( 'seovela_sitemap_post_types', array( 'post', 'page' ) );
                                    foreach ( $post_types as $slug => $label ) :
                                    ?>
                                        <label class="seovela-checkbox-item" style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s;">
                                            <input 
                                                type="checkbox" 
                                                name="seovela_sitemap_post_types[]" 
                                                value="<?php echo esc_attr( $slug ); ?>"
                                                <?php checked( in_array( $slug, $selected ) ); ?>
                                            />
                                            <span><?php echo esc_html( $label ); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e( 'Include Taxonomies', 'seovela' ); ?></th>
                            <td>
                                <div class="seovela-checkbox-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px;">
                                    <?php
                                    $taxonomies = Seovela_Helpers::get_public_taxonomies();
                                    $selected = get_option( 'seovela_sitemap_taxonomies', array( 'category' ) );
                                    foreach ( $taxonomies as $slug => $label ) :
                                    ?>
                                        <label class="seovela-checkbox-item" style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s;">
                                            <input 
                                                type="checkbox" 
                                                name="seovela_sitemap_taxonomies[]" 
                                                value="<?php echo esc_attr( $slug ); ?>"
                                                <?php checked( in_array( $slug, $selected ) ); ?>
                                            />
                                            <span><?php echo esc_html( $label ); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="submit">
                    <?php submit_button( __( 'Save Sitemap Settings', 'seovela' ), 'primary', 'submit', false ); ?>
                </div>
            </div>
        </form>

        <!-- Regenerate Sitemap Card -->
        <div class="seovela-premium-card">
            <div class="seovela-premium-card-header">
                <div class="card-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <span class="dashicons dashicons-update" style="color: #fff;"></span>
                </div>
                <div>
                    <h2><?php esc_html_e( 'Regenerate Sitemap', 'seovela' ); ?></h2>
                    <p><?php esc_html_e( 'Force regeneration of your XML sitemap if it appears outdated.', 'seovela' ); ?></p>
                </div>
            </div>
            <div class="seovela-premium-card-body">
                <form method="post">
                    <?php wp_nonce_field( 'seovela_regenerate_sitemap', 'seovela_sitemap_nonce' ); ?>
                    <button type="submit" name="seovela_regenerate_sitemap" class="button button-secondary" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border-radius: 8px;">
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e( 'Regenerate Sitemap', 'seovela' ); ?>
                    </button>
                </form>
            </div>
        </div>

    </div><!-- .seovela-page-body -->
</div><!-- .seovela-premium-page -->
