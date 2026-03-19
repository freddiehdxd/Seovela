<?php
/**
 * Seovela Schema Settings View - Premium Design
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
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=schema' ) ); ?>" class="seovela-header-tab active">
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

        <!-- Available Schema Types -->
        <div class="seovela-premium-card">
            <div class="seovela-premium-card-header">
                <div class="card-icon" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
                    <span class="dashicons dashicons-code-standards" style="color: #fff;"></span>
                </div>
                <div>
                    <h2><?php esc_html_e( 'Schema Markup', 'seovela' ); ?></h2>
                    <p><?php esc_html_e( 'Structured data helps search engines display rich snippets in search results.', 'seovela' ); ?></p>
                </div>
            </div>
            <div class="seovela-premium-card-body">
                <div class="seovela-schema-grid">
                    <div class="seovela-schema-item">
                        <div class="schema-icon"><span class="dashicons dashicons-media-text"></span></div>
                        <div class="schema-info">
                            <h4><?php esc_html_e( 'Article', 'seovela' ); ?></h4>
                            <p><?php esc_html_e( 'Blog posts, news articles, and editorial content', 'seovela' ); ?></p>
                        </div>
                    </div>
                    <div class="seovela-schema-item">
                        <div class="schema-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);"><span class="dashicons dashicons-editor-help"></span></div>
                        <div class="schema-info">
                            <h4><?php esc_html_e( 'FAQ', 'seovela' ); ?></h4>
                            <p><?php esc_html_e( 'Q&A displayed directly in search results', 'seovela' ); ?></p>
                        </div>
                    </div>
                    <div class="seovela-schema-item">
                        <div class="schema-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);"><span class="dashicons dashicons-editor-ol"></span></div>
                        <div class="schema-info">
                            <h4><?php esc_html_e( 'HowTo', 'seovela' ); ?></h4>
                            <p><?php esc_html_e( 'Step-by-step instructions and tutorials', 'seovela' ); ?></p>
                        </div>
                    </div>
                    <div class="seovela-schema-item">
                        <div class="schema-icon" style="background: linear-gradient(135deg, #ec4899, #db2777);"><span class="dashicons dashicons-location"></span></div>
                        <div class="schema-info">
                            <h4><?php esc_html_e( 'Local Business', 'seovela' ); ?></h4>
                            <p><?php esc_html_e( 'Business hours, location, and contact info', 'seovela' ); ?></p>
                        </div>
                    </div>
                    <div class="seovela-schema-item">
                        <div class="schema-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);"><span class="dashicons dashicons-admin-users"></span></div>
                        <div class="schema-info">
                            <h4><?php esc_html_e( 'Person', 'seovela' ); ?></h4>
                            <p><?php esc_html_e( 'Author profiles with social links', 'seovela' ); ?></p>
                        </div>
                    </div>
                    <div class="seovela-schema-item">
                        <div class="schema-icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);"><span class="dashicons dashicons-cart"></span></div>
                        <div class="schema-info">
                            <h4><?php esc_html_e( 'Product', 'seovela' ); ?></h4>
                            <p><?php esc_html_e( 'Price, availability, and ratings', 'seovela' ); ?></p>
                        </div>
                    </div>
                    <div class="seovela-schema-item">
                        <div class="schema-icon" style="background: linear-gradient(135deg, #14b8a6, #0d9488);"><span class="dashicons dashicons-building"></span></div>
                        <div class="schema-info">
                            <h4><?php esc_html_e( 'Organization', 'seovela' ); ?></h4>
                            <p><?php esc_html_e( 'Automatically added to homepage', 'seovela' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- How to Use -->
        <div class="seovela-premium-info info-blue">
            <div class="info-icon">&#127919;</div>
            <div class="info-content">
                <h3><?php esc_html_e( 'How to Use Schema Markup', 'seovela' ); ?></h3>
                <ol>
                    <li><?php esc_html_e( 'Edit any post or page', 'seovela' ); ?></li>
                    <li><?php esc_html_e( 'Scroll to the "Seovela SEO" metabox', 'seovela' ); ?></li>
                    <li><?php esc_html_e( 'Select your schema type (or use auto-detect)', 'seovela' ); ?></li>
                    <li><?php esc_html_e( 'Fill in schema-specific fields and save', 'seovela' ); ?></li>
                </ol>
            </div>
        </div>

        <!-- Compatibility -->
        <div class="seovela-premium-info info-amber">
            <div class="info-icon">&#128161;</div>
            <div class="info-content">
                <h3><?php esc_html_e( 'Schema Compatibility', 'seovela' ); ?></h3>
                <p><?php esc_html_e( 'Some schema types can be combined:', 'seovela' ); ?></p>
                <ul>
                    <li><?php esc_html_e( 'Article + FAQ', 'seovela' ); ?></li>
                    <li><?php esc_html_e( 'Article + HowTo', 'seovela' ); ?></li>
                    <li><?php esc_html_e( 'Product + FAQ', 'seovela' ); ?></li>
                    <li><?php esc_html_e( 'LocalBusiness + FAQ', 'seovela' ); ?></li>
                </ul>
            </div>
        </div>

        <!-- Features -->
        <div class="seovela-premium-info info-green">
            <div class="info-icon">&#10024;</div>
            <div class="info-content">
                <h3><?php esc_html_e( 'Features', 'seovela' ); ?></h3>
                <ul>
                    <li><strong><?php esc_html_e( 'Auto-Detection:', 'seovela' ); ?></strong> <?php esc_html_e( 'Automatically selects the best schema type', 'seovela' ); ?></li>
                    <li><strong><?php esc_html_e( 'FAQ Auto-Extract:', 'seovela' ); ?></strong> <?php esc_html_e( 'Detect Q&A pairs from H2/H3 headings', 'seovela' ); ?></li>
                    <li><strong><?php esc_html_e( 'Live Preview:', 'seovela' ); ?></strong> <?php esc_html_e( 'Preview generated schema JSON before publishing', 'seovela' ); ?></li>
                    <li><strong><?php esc_html_e( 'Google Testing:', 'seovela' ); ?></strong> <?php esc_html_e( 'One-click test with Google Rich Results Test', 'seovela' ); ?></li>
                </ul>
            </div>
        </div>

        <!-- Learn More Card -->
        <div class="seovela-premium-card">
            <div class="seovela-premium-card-header">
                <div class="card-icon" style="background: linear-gradient(135deg, #64748b 0%, #475569 100%);">
                    <span class="dashicons dashicons-book" style="color: #fff;"></span>
                </div>
                <div>
                    <h2><?php esc_html_e( 'Learn More', 'seovela' ); ?></h2>
                    <p><?php esc_html_e( 'Schema markup can lead to rich snippets like star ratings, prices, and expandable Q&A.', 'seovela' ); ?></p>
                </div>
            </div>
            <div class="seovela-premium-card-body" style="display: flex; gap: 16px; flex-wrap: wrap;">
                <a href="https://schema.org/" target="_blank" rel="noopener noreferrer" class="button" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; border-radius: 8px;">
                    <span class="dashicons dashicons-external"></span>
                    <?php esc_html_e( 'Schema.org', 'seovela' ); ?>
                </a>
                <a href="https://search.google.com/test/rich-results" target="_blank" rel="noopener noreferrer" class="button" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; border-radius: 8px;">
                    <span class="dashicons dashicons-external"></span>
                    <?php esc_html_e( 'Google Rich Results Test', 'seovela' ); ?>
                </a>
            </div>
        </div>

    </div><!-- .seovela-page-body -->
</div><!-- .seovela-premium-page -->
