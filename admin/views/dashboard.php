<?php
/**
 * Seovela Dashboard View - Premium Redesign
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
$score_factors = 0;

// Factor 1: Meta titles coverage (30 points max)
if ( isset( $stats['meta_title_percentage'] ) ) {
    $seo_score += min( 30, ( $stats['meta_title_percentage'] / 100 ) * 30 );
    $score_factors++;
}

// Factor 2: Meta descriptions coverage (30 points max)
if ( isset( $stats['meta_description_percentage'] ) ) {
    $seo_score += min( 30, ( $stats['meta_description_percentage'] / 100 ) * 30 );
    $score_factors++;
}

// Factor 3: Active modules bonus (20 points max)
$module_ratio = $active_modules_count / max( 1, $total_modules_count );
$seo_score += min( 20, $module_ratio * 20 );
$score_factors++;

// Factor 4: Base score for having plugin active (20 points)
$seo_score += 20;

// Round the score
$seo_score = round( $seo_score );

// Determine score status
if ( $seo_score >= 80 ) {
    $score_status = 'excellent';
    $score_label = __( 'Excellent', 'seovela' );
    $score_color = '#10b981';
} elseif ( $seo_score >= 60 ) {
    $score_status = 'good';
    $score_label = __( 'Good', 'seovela' );
    $score_color = '#3b82f6';
} elseif ( $seo_score >= 40 ) {
    $score_status = 'needs-work';
    $score_label = __( 'Needs Work', 'seovela' );
    $score_color = '#f59e0b';
} else {
    $score_status = 'critical';
    $score_label = __( 'Critical', 'seovela' );
    $score_color = '#ef4444';
}

// Get recent posts without SEO
$posts_without_seo = get_posts( array(
    'post_type' => array( 'post', 'page' ),
    'posts_per_page' => 5,
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => '_seovela_meta_title',
            'compare' => 'NOT EXISTS',
        ),
        array(
            'key' => '_seovela_meta_title',
            'value' => '',
        ),
    ),
) );

// Module styles for cards
$module_styles = array(
    'meta' => array( 'icon' => '📝', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' ),
    'sitemap' => array( 'icon' => '🗺️', 'gradient' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' ),
    'schema' => array( 'icon' => '⚡', 'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' ),
    'optimizer' => array( 'icon' => '📊', 'gradient' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' ),
    'redirects' => array( 'icon' => '🔄', 'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)' ),
    '404-monitor' => array( 'icon' => '🔍', 'gradient' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)' ),
    'internal-links' => array( 'icon' => '🔗', 'gradient' => 'linear-gradient(135deg, #5ee7df 0%, #b490ca 100%)' ),
    'image-seo' => array( 'icon' => '🖼️', 'gradient' => 'linear-gradient(135deg, #d299c2 0%, #fef9d7 100%)' ),
    'gsc-integration' => array( 'icon' => '📈', 'gradient' => 'linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%)' ),
    'ai' => array( 'icon' => '✨', 'gradient' => 'linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%)' ),
);

// Quick tips
$tips = array(
    __( 'Add focus keywords to your meta titles for better rankings', 'seovela' ),
    __( 'Keep meta descriptions between 150-160 characters', 'seovela' ),
    __( 'Use internal links to boost page authority', 'seovela' ),
    __( 'Optimize images with descriptive alt text', 'seovela' ),
    __( 'Connect Google Search Console for real performance data', 'seovela' ),
);
$random_tip = $tips[ array_rand( $tips ) ];
?>

<div class="wrap seovela-dashboard seovela-dashboard-v2">
    
    <!-- Animated Background -->
    <div class="seovela-dash-bg">
        <div class="seovela-dash-bg-gradient"></div>
        <div class="seovela-dash-bg-pattern"></div>
    </div>

    <!-- Header Section -->
    <header class="seovela-dash-header">
        <div class="seovela-dash-header-content">
            <div class="seovela-dash-brand">
                <div class="seovela-dash-logo">
                    <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="48" height="48" rx="12" fill="url(#logo-gradient)"/>
                        <path d="M14 28C14 28 18 20 24 20C30 20 34 28 34 28" stroke="white" stroke-width="3" stroke-linecap="round"/>
                        <circle cx="24" cy="18" r="4" fill="white"/>
                        <path d="M16 34H32" stroke="white" stroke-width="3" stroke-linecap="round"/>
                        <defs>
                            <linearGradient id="logo-gradient" x1="0" y1="0" x2="48" y2="48">
                                <stop stop-color="#667eea"/>
                                <stop offset="1" stop-color="#764ba2"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <div class="seovela-dash-title-group">
                    <h1><?php esc_html_e( 'Seovela Dashboard', 'seovela' ); ?></h1>
                    <span class="seovela-dash-version">v<?php echo esc_html( SEOVELA_VERSION ); ?></span>
                </div>
            </div>
            <div class="seovela-dash-header-actions">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings' ) ); ?>" class="seovela-btn seovela-btn-secondary">
                    <span class="dashicons dashicons-admin-generic"></span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Dashboard Content -->
    <main class="seovela-dash-main">
        
        <!-- Top Row: Score + Quick Stats -->
        <section class="seovela-dash-top-row">
            
            <!-- SEO Health Score Card -->
            <div class="seovela-dash-score-card">
                <div class="seovela-score-ring-container">
                    <svg class="seovela-score-ring" viewBox="0 0 200 200">
                        <defs>
                            <linearGradient id="score-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color: <?php echo esc_attr( $score_color ); ?>; stop-opacity: 1" />
                                <stop offset="100%" style="stop-color: <?php echo esc_attr( $score_color ); ?>; stop-opacity: 0.6" />
                            </linearGradient>
                        </defs>
                        <circle class="seovela-score-ring-bg" cx="100" cy="100" r="85" />
                        <circle 
                            class="seovela-score-ring-progress" 
                            cx="100" 
                            cy="100" 
                            r="85" 
                            stroke="url(#score-gradient)"
                            data-score="<?php echo esc_attr( $seo_score ); ?>"
                        />
                    </svg>
                    <div class="seovela-score-content">
                        <span class="seovela-score-number" data-target="<?php echo esc_attr( $seo_score ); ?>">0</span>
                        <span class="seovela-score-label"><?php echo esc_html( $score_label ); ?></span>
                    </div>
                </div>
                <div class="seovela-score-details">
                    <h3><?php esc_html_e( 'SEO Health Score', 'seovela' ); ?></h3>
                    <p><?php esc_html_e( 'Based on meta coverage, active modules, and optimization settings.', 'seovela' ); ?></p>
                    <div class="seovela-score-factors">
                        <div class="seovela-score-factor">
                            <span class="seovela-factor-icon">📝</span>
                            <span class="seovela-factor-label"><?php esc_html_e( 'Meta Titles', 'seovela' ); ?></span>
                            <span class="seovela-factor-value"><?php echo isset( $stats['meta_title_percentage'] ) ? esc_html( $stats['meta_title_percentage'] ) : '0'; ?>%</span>
                        </div>
                        <div class="seovela-score-factor">
                            <span class="seovela-factor-icon">📄</span>
                            <span class="seovela-factor-label"><?php esc_html_e( 'Meta Descriptions', 'seovela' ); ?></span>
                            <span class="seovela-factor-value"><?php echo isset( $stats['meta_description_percentage'] ) ? esc_html( $stats['meta_description_percentage'] ) : '0'; ?>%</span>
                        </div>
                        <div class="seovela-score-factor">
                            <span class="seovela-factor-icon">⚡</span>
                            <span class="seovela-factor-label"><?php esc_html_e( 'Active Modules', 'seovela' ); ?></span>
                            <span class="seovela-factor-value"><?php echo esc_html( $active_modules_count ); ?>/<?php echo esc_html( $total_modules_count ); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats Grid -->
            <div class="seovela-dash-stats-grid">
                <div class="seovela-stat-card seovela-stat-posts">
                    <div class="seovela-stat-card-bg"></div>
                    <div class="seovela-stat-icon">📚</div>
                    <div class="seovela-stat-content">
                        <span class="seovela-stat-number"><?php echo esc_html( $stats['total_posts'] ?? 0 ); ?></span>
                        <span class="seovela-stat-label"><?php esc_html_e( 'Total Content', 'seovela' ); ?></span>
                    </div>
                </div>
                
                <div class="seovela-stat-card seovela-stat-optimized">
                    <div class="seovela-stat-card-bg"></div>
                    <div class="seovela-stat-icon">✅</div>
                    <div class="seovela-stat-content">
                        <span class="seovela-stat-number"><?php echo esc_html( $stats['posts_with_meta_title'] ?? 0 ); ?></span>
                        <span class="seovela-stat-label"><?php esc_html_e( 'Optimized', 'seovela' ); ?></span>
                    </div>
                </div>
                
                <div class="seovela-stat-card seovela-stat-modules">
                    <div class="seovela-stat-card-bg"></div>
                    <div class="seovela-stat-icon">🧩</div>
                    <div class="seovela-stat-content">
                        <span class="seovela-stat-number"><?php echo esc_html( $active_modules_count ); ?></span>
                        <span class="seovela-stat-label"><?php esc_html_e( 'Active Modules', 'seovela' ); ?></span>
                    </div>
                </div>
                
                <div class="seovela-stat-card seovela-stat-version">
                    <div class="seovela-stat-card-bg"></div>
                    <div class="seovela-stat-icon">🆓</div>
                    <div class="seovela-stat-content">
                        <span class="seovela-stat-number seovela-stat-text"><?php esc_html_e( 'Free', 'seovela' ); ?></span>
                        <span class="seovela-stat-label"><?php esc_html_e( 'All Features', 'seovela' ); ?></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tip Banner -->
        <section class="seovela-dash-tip-banner">
            <div class="seovela-tip-icon">💡</div>
            <div class="seovela-tip-content">
                <span class="seovela-tip-label"><?php esc_html_e( 'SEO Tip', 'seovela' ); ?></span>
                <p><?php echo esc_html( $random_tip ); ?></p>
            </div>
            <button class="seovela-tip-dismiss" title="<?php esc_attr_e( 'Dismiss', 'seovela' ); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </section>

        <!-- Main Grid: Modules + Activity -->
        <section class="seovela-dash-main-grid">
            
            <!-- Modules Quick Access -->
            <div class="seovela-dash-section seovela-dash-modules">
                <div class="seovela-section-header">
                    <h2>
                        <span class="seovela-section-icon">🚀</span>
                        <?php esc_html_e( 'SEO Tools', 'seovela' ); ?>
                    </h2>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-modules' ) ); ?>" class="seovela-link-btn">
                        <?php esc_html_e( 'Manage All', 'seovela' ); ?>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                </div>
                
                <div class="seovela-modules-quick-grid">
                    <?php 
                    $priority_modules = array( 'meta', 'sitemap', 'schema', 'gsc-integration', 'internal-links', 'image-seo' );
                    foreach ( $priority_modules as $module_key ) : 
                        if ( ! isset( $modules[ $module_key ] ) ) continue;
                        $module_info = $modules[ $module_key ];
                        $is_active = isset( $loaded_modules[ $module_key ] );
                        $style = $module_styles[ $module_key ] ?? array( 'icon' => '⚙️', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' );
                        
                        // Determine module link
                        $module_link = admin_url( 'admin.php?page=seovela-modules' );
                        if ( $module_key === 'meta' || $module_key === 'sitemap' || $module_key === 'schema' ) {
                            $module_link = admin_url( 'admin.php?page=seovela-settings&tab=' . $module_key );
                        } elseif ( $module_key === 'gsc-integration' ) {
                            $module_link = admin_url( 'admin.php?page=seovela-gsc' );
                        }
                    ?>
                        <a href="<?php echo esc_url( $module_link ); ?>" class="seovela-module-quick-card <?php echo $is_active ? 'seovela-module-active' : 'seovela-module-inactive'; ?>">
                            <div class="seovela-module-quick-icon" style="background: <?php echo esc_attr( $style['gradient'] ); ?>">
                                <span><?php echo $style['icon']; ?></span>
                            </div>
                            <div class="seovela-module-quick-info">
                                <h4><?php echo esc_html( $module_info['name'] ); ?></h4>
                                <span class="seovela-module-status <?php echo $is_active ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $is_active ? esc_html__( 'Active', 'seovela' ) : esc_html__( 'Inactive', 'seovela' ); ?>
                                </span>
                            </div>
                            <span class="seovela-module-arrow">
                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Quick Actions + Needs Attention -->
            <div class="seovela-dash-side-column">
                
                <!-- Quick Actions -->
                <div class="seovela-dash-section seovela-dash-actions">
                    <div class="seovela-section-header">
                        <h2>
                            <span class="seovela-section-icon">⚡</span>
                            <?php esc_html_e( 'Quick Actions', 'seovela' ); ?>
                        </h2>
                    </div>
                    <div class="seovela-actions-list">
                        <a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>" class="seovela-action-item">
                            <span class="seovela-action-icon">✍️</span>
                            <span><?php esc_html_e( 'Create New Post', 'seovela' ); ?></span>
                        </a>
                        <a href="<?php echo esc_url( home_url( '/sitemap.xml' ) ); ?>" target="_blank" class="seovela-action-item">
                            <span class="seovela-action-icon">🗺️</span>
                            <span><?php esc_html_e( 'View Sitemap', 'seovela' ); ?></span>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-redirects' ) ); ?>" class="seovela-action-item">
                            <span class="seovela-action-icon">🔄</span>
                            <span><?php esc_html_e( 'Add Redirect', 'seovela' ); ?></span>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=schema' ) ); ?>" class="seovela-action-item">
                            <span class="seovela-action-icon">⚙️</span>
                            <span><?php esc_html_e( 'Configure Schema', 'seovela' ); ?></span>
                        </a>
                    </div>
                </div>

                <!-- Content Needing SEO -->
                <?php if ( ! empty( $posts_without_seo ) ) : ?>
                <div class="seovela-dash-section seovela-dash-attention">
                    <div class="seovela-section-header">
                        <h2>
                            <span class="seovela-section-icon">⚠️</span>
                            <?php esc_html_e( 'Needs Attention', 'seovela' ); ?>
                        </h2>
                    </div>
                    <div class="seovela-attention-list">
                        <?php foreach ( $posts_without_seo as $post ) : ?>
                            <a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>" class="seovela-attention-item">
                                <span class="seovela-attention-type <?php echo esc_attr( $post->post_type ); ?>">
                                    <?php echo $post->post_type === 'page' ? '📄' : '📝'; ?>
                                </span>
                                <span class="seovela-attention-title"><?php echo esc_html( wp_trim_words( $post->post_title, 5 ) ); ?></span>
                                <span class="seovela-attention-badge"><?php esc_html_e( 'No Meta', 'seovela' ); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </section>

        <!-- Bottom Section: Resources -->
        <section class="seovela-dash-resources">
            <div class="seovela-resource-card seovela-resource-docs">
                <div class="seovela-resource-icon">📖</div>
                <h3><?php esc_html_e( 'Documentation', 'seovela' ); ?></h3>
                <p><?php esc_html_e( 'Learn how to get the most out of Seovela with our guides.', 'seovela' ); ?></p>
                <a href="https://seovela.com/docs" target="_blank" class="seovela-resource-link">
                    <?php esc_html_e( 'Read Docs', 'seovela' ); ?>
                    <span class="dashicons dashicons-external"></span>
                </a>
            </div>
            
            <div class="seovela-resource-card seovela-resource-support">
                <div class="seovela-resource-icon">💬</div>
                <h3><?php esc_html_e( 'Get Support', 'seovela' ); ?></h3>
                <p><?php esc_html_e( 'Need help? Our support team is here to assist you.', 'seovela' ); ?></p>
                <a href="https://seovela.com/support" target="_blank" class="seovela-resource-link">
                    <?php esc_html_e( 'Contact Us', 'seovela' ); ?>
                    <span class="dashicons dashicons-external"></span>
                </a>
            </div>
            
            <div class="seovela-resource-card seovela-resource-community">
                <div class="seovela-resource-icon">🌟</div>
                <h3><?php esc_html_e( 'Leave a Review', 'seovela' ); ?></h3>
                <p><?php esc_html_e( 'Enjoying Seovela? Help others discover us with a review.', 'seovela' ); ?></p>
                <a href="https://wordpress.org/plugins/seovela/" target="_blank" class="seovela-resource-link">
                    <?php esc_html_e( 'Rate Plugin', 'seovela' ); ?>
                    <span class="dashicons dashicons-external"></span>
                </a>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="seovela-dash-footer">
        <p>
            <?php 
            printf(
                /* translators: %s: heart emoji */
                esc_html__( 'Made with %s for WordPress', 'seovela' ),
                '❤️'
            ); 
            ?>
            &nbsp;•&nbsp;
            <a href="https://seovela.com" target="_blank"><?php esc_html_e( 'Seovela.com', 'seovela' ); ?></a>
        </p>
    </footer>

</div>
