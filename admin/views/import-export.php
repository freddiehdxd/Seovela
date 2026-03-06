<?php
/**
 * Seovela Import/Export View - Premium Design
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get migration status (class already initialized in core)
$import_export = new Seovela_Import_Export();
$migration_status = $import_export->get_migration_status();

// Display settings errors
settings_errors( 'seovela_import' );
settings_errors( 'seovela_migration' );

// Active tab
$active_ie_tab = isset( $_GET['ie_tab'] ) ? sanitize_key( $_GET['ie_tab'] ) : 'export';
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
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-tools' ) ); ?>"><?php esc_html_e( 'Tools', 'seovela' ); ?></a>
                        <span class="sep">/</span>
                        <span class="current"><?php esc_html_e( 'Import / Export', 'seovela' ); ?></span>
                    </div>
                    <h1><?php esc_html_e( 'Import / Export', 'seovela' ); ?></h1>
                    <p><?php esc_html_e( 'Export your Seovela settings, import from a backup, or migrate from other SEO plugins.', 'seovela' ); ?></p>
                </div>
            </div>
            <div class="seovela-page-header-tabs">
                <a href="#seovela-ie-export" class="seovela-header-tab seovela-ie-tab <?php echo $active_ie_tab === 'export' ? 'active' : ''; ?>" data-tab="export">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e( 'Export', 'seovela' ); ?>
                </a>
                <a href="#seovela-ie-import" class="seovela-header-tab seovela-ie-tab <?php echo $active_ie_tab === 'import' ? 'active' : ''; ?>" data-tab="import">
                    <span class="dashicons dashicons-upload"></span>
                    <?php esc_html_e( 'Import', 'seovela' ); ?>
                </a>
                <a href="#seovela-ie-migration" class="seovela-header-tab seovela-ie-tab <?php echo $active_ie_tab === 'migration' ? 'active' : ''; ?>" data-tab="migration">
                    <span class="dashicons dashicons-migrate"></span>
                    <?php esc_html_e( 'Migration', 'seovela' ); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="seovela-page-body">

        <!-- Export Tab -->
        <div id="seovela-ie-export" class="seovela-ie-content <?php echo $active_ie_tab === 'export' ? 'active' : ''; ?>">
            <div class="seovela-premium-card">
                <div class="seovela-premium-card-header">
                    <div class="card-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <span class="dashicons dashicons-download" style="color: #fff;"></span>
                    </div>
                    <div>
                        <h2><?php esc_html_e( 'Export Settings', 'seovela' ); ?></h2>
                        <p><?php esc_html_e( 'Download all your Seovela settings as a JSON file for backup or migration.', 'seovela' ); ?></p>
                    </div>
                </div>
                <div class="seovela-premium-card-body">
                    <form method="get" action="">
                        <input type="hidden" name="page" value="seovela-import-export">
                        <input type="hidden" name="seovela_export_settings" value="1">
                        <?php wp_nonce_field( 'seovela_export_settings' ); ?>
                        <button type="submit" class="button button-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 8px; font-size: 15px; height: auto;">
                            <span class="dashicons dashicons-download"></span>
                            <?php esc_html_e( 'Export Settings', 'seovela' ); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Import Tab -->
        <div id="seovela-ie-import" class="seovela-ie-content <?php echo $active_ie_tab === 'import' ? 'active' : ''; ?>">
            <div class="seovela-premium-info info-amber">
                <div class="info-icon">&#9888;&#65039;</div>
                <div class="info-content">
                    <h3><?php esc_html_e( 'Warning', 'seovela' ); ?></h3>
                    <p><?php esc_html_e( 'Importing settings will overwrite your current configuration. Make sure to export your current settings first as a backup.', 'seovela' ); ?></p>
                </div>
            </div>

            <div class="seovela-premium-card">
                <div class="seovela-premium-card-header">
                    <div class="card-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                        <span class="dashicons dashicons-upload" style="color: #fff;"></span>
                    </div>
                    <div>
                        <h2><?php esc_html_e( 'Import Settings', 'seovela' ); ?></h2>
                        <p><?php esc_html_e( 'Upload a previously exported JSON file to restore your configuration.', 'seovela' ); ?></p>
                    </div>
                </div>
                <div class="seovela-premium-card-body">
                    <form method="post" action="" enctype="multipart/form-data">
                        <?php wp_nonce_field( 'seovela_import_settings' ); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="seovela_import_file"><?php esc_html_e( 'Settings File', 'seovela' ); ?></label>
                                </th>
                                <td>
                                    <input type="file" name="seovela_import_file" id="seovela_import_file" accept=".json" required style="padding: 10px; border: 2px dashed #cbd5e1; border-radius: 8px; width: 100%; max-width: 400px;">
                                    <p class="description"><?php esc_html_e( 'Select a JSON file exported from Seovela.', 'seovela' ); ?></p>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                <div class="submit">
                    <button type="submit" name="seovela_import_settings" class="button button-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-upload"></span>
                        <?php esc_html_e( 'Import Settings', 'seovela' ); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Migration Tab -->
        <div id="seovela-ie-migration" class="seovela-ie-content <?php echo $active_ie_tab === 'migration' ? 'active' : ''; ?>">

            <div class="seovela-premium-info info-blue">
                <div class="info-icon">&#128712;</div>
                <div class="info-content">
                    <h3><?php esc_html_e( 'Safe Migration', 'seovela' ); ?></h3>
                    <p><?php esc_html_e( 'Migration does not delete data from the original plugin. You can safely test Seovela alongside your current SEO plugin before deactivating it.', 'seovela' ); ?></p>
                </div>
            </div>

            <!-- Yoast SEO Migration -->
            <div class="seovela-migration-card">
                <div class="seovela-migration-card-header">
                    <div class="migration-brand">
                        <div class="migration-logo" style="background: linear-gradient(135deg, #a4286a 0%, #7b1fa2 100%); color: #fff;">Y</div>
                        <h3><?php esc_html_e( 'Yoast SEO', 'seovela' ); ?></h3>
                    </div>
                    <?php if ( $migration_status['yoast'] ) : ?>
                        <span class="seovela-migration-status status-ready">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e( 'Ready', 'seovela' ); ?>
                        </span>
                    <?php else : ?>
                        <span class="seovela-migration-status status-not-found">
                            <span class="dashicons dashicons-warning"></span>
                            <?php esc_html_e( 'Not Detected', 'seovela' ); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="seovela-migration-card-body">
                    <div class="seovela-migration-features">
                        <span class="seovela-migration-feature"><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Meta titles', 'seovela' ); ?></span>
                        <span class="seovela-migration-feature"><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Meta descriptions', 'seovela' ); ?></span>
                        <span class="seovela-migration-feature"><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Focus keywords', 'seovela' ); ?></span>
                        <span class="seovela-migration-feature"><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Noindex/Nofollow', 'seovela' ); ?></span>
                        <span class="seovela-migration-feature"><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Title separator', 'seovela' ); ?></span>
                    </div>
                    <?php if ( $migration_status['yoast'] ) : ?>
                        <form method="post" action="" style="margin-top: 16px;">
                            <?php wp_nonce_field( 'seovela_migrate_yoast' ); ?>
                            <button type="submit" name="seovela_migrate_yoast" class="button button-primary" onclick="return confirm('<?php esc_attr_e( 'This will migrate all posts from Yoast SEO. Continue?', 'seovela' ); ?>');" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border-radius: 8px;">
                                <span class="dashicons dashicons-migrate"></span>
                                <?php esc_html_e( 'Migrate from Yoast SEO', 'seovela' ); ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Rank Math Migration -->
            <div class="seovela-migration-card">
                <div class="seovela-migration-card-header">
                    <div class="migration-brand">
                        <div class="migration-logo" style="background: linear-gradient(135deg, #4e8cff 0%, #2563eb 100%); color: #fff;">R</div>
                        <h3><?php esc_html_e( 'Rank Math', 'seovela' ); ?></h3>
                    </div>
                    <?php if ( $migration_status['rankmath'] ) : ?>
                        <span class="seovela-migration-status status-ready">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e( 'Ready', 'seovela' ); ?>
                        </span>
                    <?php else : ?>
                        <span class="seovela-migration-status status-not-found">
                            <span class="dashicons dashicons-warning"></span>
                            <?php esc_html_e( 'Not Detected', 'seovela' ); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="seovela-migration-card-body">
                    <div class="seovela-migration-features">
                        <span class="seovela-migration-feature"><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Meta titles', 'seovela' ); ?></span>
                        <span class="seovela-migration-feature"><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Meta descriptions', 'seovela' ); ?></span>
                        <span class="seovela-migration-feature"><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Focus keywords', 'seovela' ); ?></span>
                        <span class="seovela-migration-feature"><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Noindex/Nofollow', 'seovela' ); ?></span>
                        <span class="seovela-migration-feature"><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Title separator', 'seovela' ); ?></span>
                    </div>
                    <?php if ( $migration_status['rankmath'] ) : ?>
                        <form method="post" action="" style="margin-top: 16px;">
                            <?php wp_nonce_field( 'seovela_migrate_rankmath' ); ?>
                            <button type="submit" name="seovela_migrate_rankmath" class="button button-primary" onclick="return confirm('<?php esc_attr_e( 'This will migrate all posts from Rank Math. Continue?', 'seovela' ); ?>');" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border-radius: 8px;">
                                <span class="dashicons dashicons-migrate"></span>
                                <?php esc_html_e( 'Migrate from Rank Math', 'seovela' ); ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div><!-- .seovela-page-body -->
</div><!-- .seovela-premium-page -->

<style>
.seovela-ie-content {
    display: none;
}
.seovela-ie-content.active {
    display: block;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.seovela-ie-tab').on('click', function(e) {
        e.preventDefault();
        var tab = $(this).data('tab');

        // Update tabs
        $('.seovela-ie-tab').removeClass('active');
        $(this).addClass('active');

        // Update content
        $('.seovela-ie-content').removeClass('active');
        $('#seovela-ie-' + tab).addClass('active');
    });
});
</script>
