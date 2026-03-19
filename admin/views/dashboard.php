<?php
/**
 * Seovela Dashboard View - Premium Redesign v3
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get stats from optimizer module
$optimizer = seovela()->module_loader->get_module( 'optimizer' );
$stats = $optimizer ? $optimizer->get_stats() : array(
    'total_posts' => wp_count_posts( 'post' )->publish + wp_count_posts( 'page' )->publish,
    'posts_with_meta_title' => 0,
    'posts_with_meta_description' => 0,
    'meta_title_percentage' => 0,
    'meta_description_percentage' => 0,
);

// Get all modules status
$modules = Seovela_Module_Loader::get_available_modules();
$loaded_modules = seovela()->module_loader->get_loaded_modules();
$active_modules_count = count( $loaded_modules );
$total_modules_count = count( $modules );

// Calculate SEO health score
$seo_score = 0;

// Factor 1: Meta titles coverage (30 points max)
if ( isset( $stats['meta_title_percentage'] ) ) {
    $seo_score += min( 30, ( $stats['meta_title_percentage'] / 100 ) * 30 );
}

// Factor 2: Meta descriptions coverage (30 points max)
if ( isset( $stats['meta_description_percentage'] ) ) {
    $seo_score += min( 30, ( $stats['meta_description_percentage'] / 100 ) * 30 );
}

// Factor 3: Active modules bonus (20 points max)
$module_ratio = $active_modules_count / max( 1, $total_modules_count );
$seo_score += min( 20, $module_ratio * 20 );

// Factor 4: Base score for having plugin active (20 points)
$seo_score += 20;

// Round the score
$seo_score = round( $seo_score );

// Determine score status
if ( $seo_score >= 80 ) {
    $score_status = 'excellent';
    $score_label = __( 'Excellent', 'seovela' );
    $score_color = '#10b981';
    $score_color_end = '#059669';
} elseif ( $seo_score >= 60 ) {
    $score_status = 'good';
    $score_label = __( 'Good', 'seovela' );
    $score_color = '#3b82f6';
    $score_color_end = '#2563eb';
} elseif ( $seo_score >= 40 ) {
    $score_status = 'needs-work';
    $score_label = __( 'Needs Work', 'seovela' );
    $score_color = '#f59e0b';
    $score_color_end = '#d97706';
} else {
    $score_status = 'critical';
    $score_label = __( 'Critical', 'seovela' );
    $score_color = '#ef4444';
    $score_color_end = '#dc2626';
}

// Count total posts without meta (derived from stats — no extra DB query)
$total_without_meta = isset( $stats['total_posts'] ) && isset( $stats['posts_with_meta_title'] )
    ? max( 0, $stats['total_posts'] - $stats['posts_with_meta_title'] )
    : 0;

// Get 404 count (uses cached transient set by the admin class)
$unresolved_404_count = (int) get_transient( 'seovela_unresolved_404_count' );
if ( 0 === $unresolved_404_count && false === get_transient( 'seovela_unresolved_404_count' ) ) {
    // Only query if transient is truly missing (not just zero)
    global $wpdb;
    $table_404 = $wpdb->prefix . 'seovela_404_logs';
    $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_404 ) );
    if ( $table_exists ) {
        $unresolved_404_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_404} WHERE resolved = 0" );
        set_transient( 'seovela_unresolved_404_count', $unresolved_404_count, 5 * MINUTE_IN_SECONDS );
    }
}

// Optimized content count
$optimized_count = isset( $stats['posts_with_meta_title'] ) ? (int) $stats['posts_with_meta_title'] : 0;
$total_content = isset( $stats['total_posts'] ) ? (int) $stats['total_posts'] : 0;

// Meta percentages
$meta_title_pct = isset( $stats['meta_title_percentage'] ) ? (float) $stats['meta_title_percentage'] : 0;
$meta_desc_pct = isset( $stats['meta_description_percentage'] ) ? (float) $stats['meta_description_percentage'] : 0;

// Contextual tip based on site data
$tip_dismissed = get_user_meta( get_current_user_id(), 'seovela_tip_dismissed', true );
$tip_message = '';
$tip_action_url = '';
$tip_action_label = '';

if ( $meta_title_pct == 0 && $total_content > 0 ) {
    $tip_message = sprintf(
        __( 'Your meta titles are missing on all %s posts. Adding them is the fastest way to improve your rankings.', 'seovela' ),
        number_format_i18n( $total_content )
    );
    $tip_action_url = admin_url( 'admin.php?page=seovela-settings&tab=meta' );
    $tip_action_label = __( 'Configure Meta Tags', 'seovela' );
} elseif ( $unresolved_404_count > 100 ) {
    $tip_message = sprintf(
        __( 'You have %s unresolved 404 errors. Redirecting them prevents SEO damage and improves user experience.', 'seovela' ),
        number_format_i18n( $unresolved_404_count )
    );
    $tip_action_url = admin_url( 'admin.php?page=seovela-404-monitor' );
    $tip_action_label = __( 'View 404 Errors', 'seovela' );
} elseif ( $meta_title_pct < 50 && $total_content > 0 ) {
    $tip_message = sprintf(
        __( 'Only %s%% of your content has meta titles. Add them to %s more posts to significantly boost your score.', 'seovela' ),
        round( $meta_title_pct ),
        number_format_i18n( $total_without_meta )
    );
    $tip_action_url = admin_url( 'admin.php?page=seovela-settings&tab=meta' );
    $tip_action_label = __( 'Fix Now', 'seovela' );
} elseif ( $seo_score >= 80 ) {
    $tip_message = __( 'Great job! Your SEO health is strong. Consider adding schema markup for rich search results.', 'seovela' );
    $tip_action_url = admin_url( 'admin.php?page=seovela-settings&tab=schema' );
    $tip_action_label = __( 'Configure Schema', 'seovela' );
} else {
    $tip_message = __( 'Connect Google Search Console to get real performance data and keyword insights.', 'seovela' );
    $tip_action_url = admin_url( 'admin.php?page=seovela-gsc' );
    $tip_action_label = __( 'Connect Now', 'seovela' );
}

// Module styles — compact, brand-consistent icon colors (indigo family, NOT rainbow)
$module_styles = array(
    'meta'           => array( 'icon' => 'meta',           'color' => '#6366f1', 'desc' => __( 'Manage meta titles & descriptions', 'seovela' ) ),
    'sitemap'        => array( 'icon' => 'sitemap',        'color' => '#6366f1', 'desc' => __( 'Auto-generate XML sitemaps', 'seovela' ) ),
    'schema'         => array( 'icon' => 'schema',         'color' => '#6366f1', 'desc' => __( 'Add structured data (JSON-LD)', 'seovela' ) ),
    'optimizer'      => array( 'icon' => 'optimizer',      'color' => '#6366f1', 'desc' => __( 'Track SEO analytics & metrics', 'seovela' ) ),
    'redirects'      => array( 'icon' => 'redirects',      'color' => '#6366f1', 'desc' => __( 'Manage 301/302/307 redirects', 'seovela' ) ),
    '404-monitor'    => array( 'icon' => '404-monitor',    'color' => '#6366f1', 'desc' => __( 'Track & fix 404 errors', 'seovela' ) ),
    'internal-links' => array( 'icon' => 'internal-links', 'color' => '#6366f1', 'desc' => __( 'Smart internal linking suggestions', 'seovela' ) ),
    'image-seo'      => array( 'icon' => 'image-seo',      'color' => '#6366f1', 'desc' => __( 'Optimize images for SEO', 'seovela' ) ),
    'gsc-integration' => array( 'icon' => 'gsc-integration', 'color' => '#6366f1', 'desc' => __( 'Google Search Console insights', 'seovela' ) ),
    'llms-txt'       => array( 'icon' => 'settings',       'color' => '#6366f1', 'desc' => __( 'Guide AI models with llms.txt', 'seovela' ) ),
    'ai'             => array( 'icon' => 'ai',             'color' => '#6366f1', 'desc' => __( 'AI-powered content optimization', 'seovela' ) ),
);

// Module link mapping
$module_links = array(
    'meta'           => admin_url( 'admin.php?page=seovela-settings&tab=meta' ),
    'sitemap'        => admin_url( 'admin.php?page=seovela-settings&tab=sitemap' ),
    'schema'         => admin_url( 'admin.php?page=seovela-settings&tab=schema' ),
    'optimizer'      => admin_url( 'admin.php?page=seovela-modules' ),
    'redirects'      => admin_url( 'admin.php?page=seovela-redirects' ),
    '404-monitor'    => admin_url( 'admin.php?page=seovela-404-monitor' ),
    'internal-links' => admin_url( 'admin.php?page=seovela-internal-links' ),
    'image-seo'      => admin_url( 'admin.php?page=seovela-image-seo' ),
    'gsc-integration' => admin_url( 'admin.php?page=seovela-gsc' ),
    'llms-txt'       => admin_url( 'admin.php?page=seovela-llms-txt' ),
    'ai'             => admin_url( 'admin.php?page=seovela-ai' ),
);
?>

<div class="wrap seovela-dashboard seovela-dashboard-v2">

    <!-- Header Section -->
    <header class="seovela-dash-header">
        <div class="seovela-dash-header-content">
            <div class="seovela-dash-brand">
                <div class="seovela-dash-title-group">
                    <h1><?php esc_html_e( 'Dashboard', 'seovela' ); ?></h1>
                    <span class="seovela-dash-version">v<?php echo esc_html( SEOVELA_VERSION ); ?></span>
                </div>
            </div>
            <div class="seovela-dash-header-actions">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings' ) ); ?>" class="seovela-btn seovela-btn-secondary" title="<?php esc_attr_e( 'Settings', 'seovela' ); ?>">
                    <?php Seovela_Icons::render( 'settings', 18 ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-modules' ) ); ?>" class="seovela-btn seovela-btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    <span><?php esc_html_e( 'Manage Modules', 'seovela' ); ?></span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Dashboard Content -->
    <main class="seovela-dash-main">

        <!-- 1. SEO Health Score — Full-Width Hero -->
        <section class="seovela-dash-hero">
            <div class="seovela-hero-score">
                <div class="seovela-score-ring-container">
                    <svg class="seovela-score-ring" viewBox="0 0 200 200">
                        <defs>
                            <linearGradient id="score-gradient-<?php echo esc_attr( $score_status ); ?>" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="<?php echo esc_attr( $score_color ); ?>" />
                                <stop offset="100%" stop-color="<?php echo esc_attr( $score_color_end ); ?>" />
                            </linearGradient>
                        </defs>
                        <circle class="seovela-score-ring-bg" cx="100" cy="100" r="85" />
                        <circle
                            class="seovela-score-ring-progress"
                            cx="100"
                            cy="100"
                            r="85"
                            stroke="url(#score-gradient-<?php echo esc_attr( $score_status ); ?>)"
                            data-score="<?php echo esc_attr( $seo_score ); ?>"
                        />
                    </svg>
                    <div class="seovela-score-content">
                        <span class="seovela-score-number" data-target="<?php echo esc_attr( $seo_score ); ?>">0</span>
                        <span class="seovela-score-badge seovela-score-badge--<?php echo esc_attr( $score_status ); ?>"><?php echo esc_html( $score_label ); ?></span>
                    </div>
                </div>
            </div>
            <div class="seovela-hero-details">
                <h2 class="seovela-hero-title"><?php esc_html_e( 'SEO Health Score', 'seovela' ); ?></h2>
                <p class="seovela-hero-subtitle"><?php esc_html_e( 'Based on meta coverage, active modules, and optimization.', 'seovela' ); ?></p>

                <div class="seovela-hero-bars">
                    <div class="seovela-bar-item">
                        <div class="seovela-bar-header">
                            <span class="seovela-bar-label"><?php esc_html_e( 'Meta Titles Coverage', 'seovela' ); ?></span>
                            <span class="seovela-bar-value"><?php echo esc_html( round( $meta_title_pct ) ); ?>%</span>
                        </div>
                        <div class="seovela-bar-track">
                            <div class="seovela-bar-fill" data-width="<?php echo esc_attr( $meta_title_pct ); ?>" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="seovela-bar-item">
                        <div class="seovela-bar-header">
                            <span class="seovela-bar-label"><?php esc_html_e( 'Meta Descriptions Coverage', 'seovela' ); ?></span>
                            <span class="seovela-bar-value"><?php echo esc_html( round( $meta_desc_pct ) ); ?>%</span>
                        </div>
                        <div class="seovela-bar-track">
                            <div class="seovela-bar-fill" data-width="<?php echo esc_attr( $meta_desc_pct ); ?>" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="seovela-bar-item">
                        <div class="seovela-bar-header">
                            <span class="seovela-bar-label"><?php esc_html_e( 'Optimized Content', 'seovela' ); ?></span>
                            <span class="seovela-bar-value"><?php echo esc_html( number_format_i18n( $optimized_count ) ); ?>/<?php echo esc_html( number_format_i18n( $total_content ) ); ?></span>
                        </div>
                        <div class="seovela-bar-track">
                            <?php $opt_pct = $total_content > 0 ? ( $optimized_count / $total_content ) * 100 : 0; ?>
                            <div class="seovela-bar-fill" data-width="<?php echo esc_attr( $opt_pct ); ?>" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <?php if ( $total_without_meta > 0 ) : ?>
                <div class="seovela-hero-cta">
                    <span><?php
                        printf(
                            /* translators: %s: number of posts */
                            esc_html__( 'Add meta titles to your %s posts to improve your score.', 'seovela' ),
                            '<strong>' . esc_html( number_format_i18n( $total_without_meta ) ) . '</strong>'
                        );
                    ?></span>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=meta' ) ); ?>"><?php esc_html_e( 'Fix Now', 'seovela' ); ?> &rarr;</a>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- 2. Needs Attention Banner (moved up, compact) -->
        <?php if ( $total_without_meta > 0 || $unresolved_404_count > 0 ) : ?>
        <section class="seovela-dash-alert seovela-dash-alert--warning">
            <svg class="seovela-alert-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <span class="seovela-alert-text">
                <?php
                $alerts = array();
                if ( $total_without_meta > 0 ) {
                    $alerts[] = sprintf(
                        /* translators: %s: number of posts */
                        esc_html__( '%s posts need meta data', 'seovela' ),
                        number_format_i18n( $total_without_meta )
                    );
                }
                if ( $unresolved_404_count > 0 ) {
                    $alerts[] = sprintf(
                        /* translators: %s: number of 404 errors */
                        esc_html__( '%s unresolved 404 errors', 'seovela' ),
                        number_format_i18n( $unresolved_404_count )
                    );
                }
                echo esc_html( implode( '  &bull;  ', $alerts ) );
                ?>
            </span>
            <?php if ( $unresolved_404_count > 0 ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-404-monitor' ) ); ?>" class="seovela-alert-link"><?php esc_html_e( 'View All', 'seovela' ); ?> &rarr;</a>
            <?php else : ?>
                <a href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>" class="seovela-alert-link"><?php esc_html_e( 'View All', 'seovela' ); ?> &rarr;</a>
            <?php endif; ?>
        </section>
        <?php elseif ( $seo_score >= 60 ) : ?>
        <section class="seovela-dash-alert seovela-dash-alert--success">
            <svg class="seovela-alert-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <span class="seovela-alert-text"><?php esc_html_e( 'All content is optimized', 'seovela' ); ?></span>
        </section>
        <?php endif; ?>

        <!-- 3. Stats Row — Compact 4-column grid -->
        <section class="seovela-dash-stats">
            <div class="seovela-stat-mini">
                <div class="seovela-stat-mini-icon seovela-stat-mini-icon--indigo">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <span class="seovela-stat-mini-number" data-counter="<?php echo esc_attr( $total_content ); ?>">0</span>
                <span class="seovela-stat-mini-label"><?php esc_html_e( 'TOTAL CONTENT', 'seovela' ); ?></span>
            </div>

            <div class="seovela-stat-mini">
                <div class="seovela-stat-mini-icon seovela-stat-mini-icon--green">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <span class="seovela-stat-mini-number" data-counter="<?php echo esc_attr( $optimized_count ); ?>">0</span>
                <span class="seovela-stat-mini-label"><?php esc_html_e( 'OPTIMIZED', 'seovela' ); ?></span>
            </div>

            <div class="seovela-stat-mini">
                <div class="seovela-stat-mini-icon seovela-stat-mini-icon--purple">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </div>
                <span class="seovela-stat-mini-number" data-counter="<?php echo esc_attr( $active_modules_count ); ?>">0</span>
                <span class="seovela-stat-mini-label"><?php esc_html_e( 'ACTIVE MODULES', 'seovela' ); ?></span>
            </div>

            <div class="seovela-stat-mini seovela-stat-mini--premium">
                <div class="seovela-stat-mini-icon seovela-stat-mini-icon--star">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </div>
                <span class="seovela-stat-mini-number seovela-stat-mini-text"><?php esc_html_e( 'Free', 'seovela' ); ?></span>
                <span class="seovela-stat-mini-label"><?php esc_html_e( 'ALL FEATURES', 'seovela' ); ?></span>
            </div>
        </section>

        <!-- 4. Contextual Tip Banner -->
        <?php if ( ! $tip_dismissed && $tip_message ) : ?>
        <section class="seovela-dash-tip" id="seovela-tip-banner">
            <div class="seovela-tip-icon-wrap">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/></svg>
            </div>
            <div class="seovela-tip-body">
                <p><?php echo esc_html( $tip_message ); ?></p>
            </div>
            <?php if ( $tip_action_url ) : ?>
                <a href="<?php echo esc_url( $tip_action_url ); ?>" class="seovela-tip-action"><?php echo esc_html( $tip_action_label ); ?> &rarr;</a>
            <?php endif; ?>
            <button class="seovela-tip-dismiss" title="<?php esc_attr_e( 'Dismiss', 'seovela' ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'seovela_dismiss_tip' ) ); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </section>
        <?php endif; ?>

        <!-- 5. Main Grid: Modules + Quick Actions Sidebar -->
        <section class="seovela-dash-main-grid">

            <!-- Module Grid — 3 columns, ALL 11 modules -->
            <div class="seovela-dash-modules-section">
                <div class="seovela-section-header">
                    <h2><?php esc_html_e( 'SEO Tools', 'seovela' ); ?></h2>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-modules' ) ); ?>" class="seovela-link-btn">
                        <?php esc_html_e( 'Manage All', 'seovela' ); ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>

                <div class="seovela-modules-grid">
                    <?php foreach ( $modules as $module_key => $module_info ) :
                        $is_active = isset( $loaded_modules[ $module_key ] );
                        $style = $module_styles[ $module_key ] ?? array( 'icon' => 'settings', 'color' => '#6366f1', 'desc' => '' );
                        $module_link = $module_links[ $module_key ] ?? admin_url( 'admin.php?page=seovela-modules' );
                    ?>
                    <a href="<?php echo esc_url( $module_link ); ?>" class="seovela-module-row <?php echo $is_active ? 'seovela-module-row--active' : ''; ?>">
                        <div class="seovela-module-row-icon">
                            <?php Seovela_Icons::render( $style['icon'], 20 ); ?>
                        </div>
                        <div class="seovela-module-row-info">
                            <span class="seovela-module-row-name"><?php echo esc_html( $module_info['name'] ); ?></span>
                            <span class="seovela-module-row-desc"><?php echo esc_html( $style['desc'] ); ?></span>
                        </div>
                        <div class="seovela-module-row-status">
                            <span class="seovela-module-toggle <?php echo esc_attr( $is_active ? 'seovela-module-toggle--on' : '' ); ?>">
                                <span class="seovela-module-toggle-dot"></span>
                            </span>
                        </div>
                        <div class="seovela-module-row-arrow">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Quick Actions Sidebar -->
            <div class="seovela-dash-sidebar">
                <div class="seovela-sidebar-card">
                    <h3><?php esc_html_e( 'Quick Actions', 'seovela' ); ?></h3>
                    <div class="seovela-quick-actions">
                        <a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>" class="seovela-qa-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            <span><?php esc_html_e( 'Create New Post', 'seovela' ); ?></span>
                        </a>
                        <a href="<?php echo esc_url( home_url( '/sitemap.xml' ) ); ?>" target="_blank" class="seovela-qa-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                            <span><?php esc_html_e( 'View Sitemap', 'seovela' ); ?></span>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-redirects' ) ); ?>" class="seovela-qa-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 10 20 15 15 20"/><path d="M4 4v7a4 4 0 0 0 4 4h12"/></svg>
                            <span><?php esc_html_e( 'Add Redirect', 'seovela' ); ?></span>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=schema' ) ); ?>" class="seovela-qa-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                            <span><?php esc_html_e( 'Configure Schema', 'seovela' ); ?></span>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-modules' ) ); ?>" class="seovela-qa-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                            <span><?php esc_html_e( 'Run SEO Audit', 'seovela' ); ?></span>
                        </a>
                        <?php if ( $unresolved_404_count > 0 ) : ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-404-monitor' ) ); ?>" class="seovela-qa-item seovela-qa-item--alert">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                            <span><?php esc_html_e( 'View 404 Errors', 'seovela' ); ?></span>
                            <span class="seovela-qa-badge"><?php echo esc_html( number_format_i18n( $unresolved_404_count ) ); ?></span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Resources -->
                <div class="seovela-sidebar-card">
                    <h3><?php esc_html_e( 'Resources', 'seovela' ); ?></h3>
                    <div class="seovela-quick-actions">
                        <a href="https://seovela.com/docs" target="_blank" class="seovela-qa-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                            <span><?php esc_html_e( 'Documentation', 'seovela' ); ?></span>
                        </a>
                        <a href="https://seovela.com/support" target="_blank" class="seovela-qa-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            <span><?php esc_html_e( 'Get Support', 'seovela' ); ?></span>
                        </a>
                        <a href="https://wordpress.org/plugins/seovela/" target="_blank" class="seovela-qa-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <span><?php esc_html_e( 'Leave a Review', 'seovela' ); ?></span>
                        </a>
                    </div>
                </div>
            </div>

        </section>

    </main>

    <!-- Footer -->
    <footer class="seovela-dash-footer">
        <p>
            <?php
            printf(
                /* translators: %s: heart icon */
                esc_html__( 'Made with %s for WordPress', 'seovela' ),
                '<span class="seovela-heart">&hearts;</span>'
            );
            ?>
            &nbsp;&bull;&nbsp;
            <a href="https://seovela.com" target="_blank"><?php esc_html_e( 'Seovela.com', 'seovela' ); ?></a>
        </p>
    </footer>

</div>
