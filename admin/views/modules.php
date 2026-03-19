<?php
/**
 * Seovela Modules View - Premium Design
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Handle module toggle
if ( isset( $_POST['seovela_save_modules'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seovela_modules_nonce'] ) ), 'seovela_save_modules' ) ) {
    $modules = Seovela_Module_Loader::get_available_modules();
    
    foreach ( $modules as $module_key => $module_info ) {
        $enabled = isset( $_POST['seovela_module_' . $module_key] ) ? true : false;
        update_option( $module_info['option_key'], $enabled );
    }
    
    add_settings_error( 'seovela_modules', 'modules_saved', __( 'Modules updated successfully!', 'seovela' ), 'success' );
}

$modules = Seovela_Module_Loader::get_available_modules();

// Define module categories and metadata
$module_categories = array(
    'core' => array(
        'title' => __( 'Core SEO', 'seovela' ),
        'description' => __( 'Essential tools for search engine optimization', 'seovela' ),
        'modules' => array( 'meta', 'sitemap', 'schema' ),
    ),
    'content' => array(
        'title' => __( 'Content & Links', 'seovela' ),
        'description' => __( 'Optimize your content and link structure', 'seovela' ),
        'modules' => array( 'optimizer', 'internal-links', 'image-seo' ),
    ),
    'technical' => array(
        'title' => __( 'Technical SEO', 'seovela' ),
        'description' => __( 'Advanced technical optimization tools', 'seovela' ),
        'modules' => array( 'redirects', '404-monitor' ),
    ),
    'ai' => array(
        'title' => __( 'AI & Analytics', 'seovela' ),
        'description' => __( 'AI-powered features and analytics', 'seovela' ),
        'modules' => array( 'ai', 'gsc-integration', 'llms-txt' ),
    ),
);

// Define module icons using Dashicons
$module_meta = array(
    'meta' => array(
        'icon' => 'dashicons-edit-page',
        'gradient' => 'linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%)',
        'color' => '#0ea5e9',
        'bg' => 'rgba(14, 165, 233, 0.1)',
    ),
    'sitemap' => array(
        'icon' => 'dashicons-networking',
        'gradient' => 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
        'color' => '#f59e0b',
        'bg' => 'rgba(245, 158, 11, 0.1)',
    ),
    'schema' => array(
        'icon' => 'dashicons-code-standards',
        'gradient' => 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)',
        'color' => '#6366f1',
        'bg' => 'rgba(99, 102, 241, 0.1)',
    ),
    'optimizer' => array(
        'icon' => 'dashicons-performance',
        'gradient' => 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)',
        'color' => '#8b5cf6',
        'bg' => 'rgba(139, 92, 246, 0.1)',
    ),
    'redirects' => array(
        'icon' => 'dashicons-randomize',
        'gradient' => 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
        'color' => '#10b981',
        'bg' => 'rgba(16, 185, 129, 0.1)',
    ),
    '404-monitor' => array(
        'icon' => 'dashicons-warning',
        'gradient' => 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
        'color' => '#ef4444',
        'bg' => 'rgba(239, 68, 68, 0.1)',
    ),
    'ai' => array(
        'icon' => 'dashicons-superhero-alt',
        'gradient' => 'linear-gradient(135deg, #ec4899 0%, #db2777 100%)',
        'color' => '#ec4899',
        'bg' => 'rgba(236, 72, 153, 0.1)',
    ),
    'internal-links' => array(
        'icon' => 'dashicons-admin-links',
        'gradient' => 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)',
        'color' => '#06b6d4',
        'bg' => 'rgba(6, 182, 212, 0.1)',
    ),
    'image-seo' => array(
        'icon' => 'dashicons-format-image',
        'gradient' => 'linear-gradient(135deg, #f97316 0%, #ea580c 100%)',
        'color' => '#f97316',
        'bg' => 'rgba(249, 115, 22, 0.1)',
    ),
    'gsc-integration' => array(
        'icon' => 'dashicons-chart-bar',
        'gradient' => 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)',
        'color' => '#22c55e',
        'bg' => 'rgba(34, 197, 94, 0.1)',
    ),
    'llms-txt' => array(
        'icon' => 'dashicons-media-code',
        'gradient' => 'linear-gradient(135deg, #14b8a6 0%, #0d9488 100%)',
        'color' => '#14b8a6',
        'bg' => 'rgba(20, 184, 166, 0.1)',
    ),
);

// Count active modules
$active_count = 0;
$total_count = count( $modules );
foreach ( $modules as $module_key => $module_info ) {
    if ( get_option( $module_info['option_key'], true ) ) {
        $active_count++;
    }
}
?>

<div class="seovela-modules-premium">
    
    <!-- Premium Header -->
    <div class="seovela-modules-header">
        <div class="seovela-modules-header-bg"></div>
        <div class="seovela-modules-header-content">
            <div class="seovela-modules-header-text">
                <h1><?php esc_html_e( 'Module Center', 'seovela' ); ?></h1>
                <p><?php esc_html_e( 'Enable or disable modules to customize your SEO toolkit. Only use what you need for optimal performance.', 'seovela' ); ?></p>
            </div>
            <div class="seovela-modules-stats">
                <div class="seovela-modules-stat-card">
                    <div class="seovela-modules-stat-number"><?php echo esc_html( $active_count ); ?></div>
                    <div class="seovela-modules-stat-label"><?php esc_html_e( 'Active', 'seovela' ); ?></div>
                </div>
                <div class="seovela-modules-stat-divider"></div>
                <div class="seovela-modules-stat-card">
                    <div class="seovela-modules-stat-number"><?php echo esc_html( $total_count ); ?></div>
                    <div class="seovela-modules-stat-label"><?php esc_html_e( 'Total', 'seovela' ); ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php settings_errors( 'seovela_modules' ); ?>

    <form method="post" id="seovela-modules-form">
        <?php wp_nonce_field( 'seovela_save_modules', 'seovela_modules_nonce' ); ?>
        
        <!-- Module Categories -->
        <?php foreach ( $module_categories as $cat_key => $category ) : ?>
            <div class="seovela-module-category">
                <div class="seovela-category-header">
                    <div class="seovela-category-info">
                        <h2><?php echo esc_html( $category['title'] ); ?></h2>
                        <span class="seovela-category-desc"><?php echo esc_html( $category['description'] ); ?></span>
                    </div>
                    <div class="seovela-category-count">
                        <?php 
                        $cat_active = 0;
                        foreach ( $category['modules'] as $mod_key ) {
                            if ( isset( $modules[ $mod_key ] ) ) {
                                $mod = $modules[ $mod_key ];
                                if ( get_option( $mod['option_key'], true ) ) {
                                    $cat_active++;
                                }
                            }
                        }
                        echo esc_html( $cat_active . '/' . count( $category['modules'] ) );
                        ?>
                    </div>
                </div>
                
                <div class="seovela-modules-cards">
                    <?php foreach ( $category['modules'] as $module_key ) : ?>
                        <?php 
                        if ( ! isset( $modules[ $module_key ] ) ) continue;
                        $module_info = $modules[ $module_key ];
                        $enabled = get_option( $module_info['option_key'], true );
                        $is_locked = false;
                        $meta = isset( $module_meta[ $module_key ] ) ? $module_meta[ $module_key ] : array(
                            'icon' => 'dashicons-admin-generic',
                            'gradient' => 'linear-gradient(135deg, #64748b 0%, #475569 100%)',
                            'color' => '#64748b',
                            'bg' => 'rgba(100, 116, 139, 0.1)',
                        );
                        ?>
                        
                        <div class="seovela-module-card-premium <?php echo $is_locked ? 'is-locked' : ''; ?> <?php echo $enabled && ! $is_locked ? 'is-active' : ''; ?>" data-module="<?php echo esc_attr( $module_key ); ?>">
                            
                            <?php if ( $is_locked ) : ?>
                                <div class="seovela-module-pro-ribbon">
                                    <span><?php esc_html_e( 'PRO', 'seovela' ); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="seovela-module-card-inner">
                                <!-- Icon -->
                                <div class="seovela-module-icon-premium" style="background: <?php echo esc_attr( $meta['bg'] ); ?>;">
                                    <span class="dashicons <?php echo esc_attr( $meta['icon'] ); ?>" style="background: <?php echo esc_attr( $meta['gradient'] ); ?>; -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></span>
                                </div>
                                
                                <!-- Content -->
                                <div class="seovela-module-info-premium">
                                    <h3><?php echo esc_html( $module_info['name'] ); ?></h3>
                                    <p><?php echo esc_html( $module_info['description'] ); ?></p>
                                </div>
                                
                                <!-- Toggle -->
                                <div class="seovela-module-toggle-area">
                                        <label class="seovela-toggle-premium">
                                            <input 
                                                type="checkbox" 
                                                name="seovela_module_<?php echo esc_attr( $module_key ); ?>"
                                                <?php checked( $enabled ); ?>
                                                class="seovela-module-toggle-input"
                                                data-module="<?php echo esc_attr( $module_key ); ?>"
                                            />
                                            <span class="seovela-toggle-track">
                                                <span class="seovela-toggle-thumb"></span>
                                            </span>
                                            <span class="seovela-toggle-status"><?php echo $enabled ? esc_html__( 'On', 'seovela' ) : esc_html__( 'Off', 'seovela' ); ?></span>
                                        </label>
                                </div>
                            </div>
                            
                            <!-- Active indicator bar -->
                            <?php if ( $enabled && ! $is_locked ) : ?>
                                <div class="seovela-module-active-bar" style="background: <?php echo esc_attr( $meta['gradient'] ); ?>;"></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Save Actions -->
        <div class="seovela-modules-save-bar">
            <div class="seovela-save-bar-inner">
                <div class="seovela-save-bar-info">
                    <span class="dashicons dashicons-info-outline"></span>
                    <span><?php esc_html_e( 'Disabling unused modules helps improve site performance.', 'seovela' ); ?></span>
                </div>
                <div class="seovela-save-bar-actions">
                    <button type="submit" name="seovela_save_modules" class="seovela-btn-save">
                        <span class="dashicons dashicons-saved"></span>
                        <?php esc_html_e( 'Save Changes', 'seovela' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
/* Premium Modules Page Styles */
.seovela-modules-premium {
    margin: -20px -20px 0 -2px;
    background: #f8fafc;
    min-height: calc(100vh - 32px);
    padding-bottom: 100px;
    font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* Header */
.seovela-modules-header {
    position: relative;
    padding: 48px 40px;
    overflow: hidden;
}

.seovela-modules-header-bg {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    z-index: 0;
}

.seovela-modules-header-bg::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: 
        radial-gradient(circle at 20% 80%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(16, 185, 129, 0.1) 0%, transparent 40%);
    z-index: 1;
}

.seovela-modules-header-bg::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.5;
    z-index: 2;
}

.seovela-modules-header-content {
    position: relative;
    z-index: 10;
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1400px;
    margin: 0 auto;
    gap: 40px;
}

.seovela-modules-header-text h1 {
    font-size: 36px;
    font-weight: 800;
    color: #ffffff;
    margin: 0 0 12px 0;
    letter-spacing: -0.02em;
}

.seovela-modules-header-text p {
    font-size: 16px;
    color: rgba(255, 255, 255, 0.7);
    margin: 0;
    max-width: 500px;
    line-height: 1.6;
}

/* Stats Cards */
.seovela-modules-stats {
    display: flex;
    align-items: center;
    gap: 24px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    padding: 20px 32px;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.seovela-modules-stat-card {
    text-align: center;
}

.seovela-modules-stat-number {
    font-size: 42px;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
    margin-bottom: 6px;
}

.seovela-modules-stat-label {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.6);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-weight: 600;
}

.seovela-modules-stat-divider {
    width: 1px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
}

/* Categories */
.seovela-module-category {
    padding: 0 40px;
    margin-bottom: 40px;
    max-width: 1480px;
    margin-left: auto;
    margin-right: auto;
}

.seovela-module-category:first-of-type {
    padding-top: 40px;
}

.seovela-category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.seovela-category-info h2 {
    font-size: 22px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 4px 0;
}

.seovela-category-desc {
    font-size: 14px;
    color: #64748b;
}

.seovela-category-count {
    background: #e2e8f0;
    color: #475569;
    font-size: 13px;
    font-weight: 700;
    padding: 6px 14px;
    border-radius: 20px;
}

/* Module Cards Grid */
.seovela-modules-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 20px;
}

/* Premium Card */
.seovela-module-card-premium {
    position: relative;
    background: #ffffff;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid #e2e8f0;
}

.seovela-module-card-premium:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.15);
    border-color: #cbd5e1;
}

.seovela-module-card-premium.is-active {
    border-color: #10b981;
    background: linear-gradient(to bottom, #ffffff 0%, #f0fdf4 100%);
}

.seovela-module-card-premium.is-active:hover {
    border-color: #059669;
}

.seovela-module-card-premium.is-locked {
    opacity: 0.85;
    background: linear-gradient(to bottom, #fafafa 0%, #f5f5f5 100%);
}

.seovela-module-card-premium.is-locked:hover {
    transform: none;
    box-shadow: none;
}

/* PRO Ribbon */
.seovela-module-pro-ribbon {
    position: absolute;
    top: 14px;
    right: -30px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #ffffff;
    font-size: 10px;
    font-weight: 800;
    padding: 4px 36px;
    transform: rotate(45deg);
    z-index: 10;
    letter-spacing: 0.1em;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
}

/* Card Inner */
.seovela-module-card-inner {
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
}

/* Module Icon */
.seovela-module-icon-premium {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: transform 0.3s ease;
}

.seovela-module-card-premium:hover .seovela-module-icon-premium {
    transform: scale(1.1);
}

.seovela-module-icon-premium .dashicons {
    font-size: 26px;
    width: 26px;
    height: 26px;
}

/* Module Info */
.seovela-module-info-premium {
    flex: 1;
    min-width: 0;
}

.seovela-module-info-premium h3 {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 6px 0;
    line-height: 1.3;
}

.seovela-module-info-premium p {
    font-size: 13px;
    color: #64748b;
    margin: 0;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Toggle Area */
.seovela-module-toggle-area {
    flex-shrink: 0;
}

/* Premium Toggle Switch */
.seovela-toggle-premium {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.seovela-toggle-premium input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.seovela-toggle-track {
    position: relative;
    width: 48px;
    height: 26px;
    background: #cbd5e1;
    border-radius: 26px;
    transition: all 0.3s ease;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

.seovela-toggle-thumb {
    position: absolute;
    top: 3px;
    left: 3px;
    width: 20px;
    height: 20px;
    background: #ffffff;
    border-radius: 50%;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.seovela-toggle-premium input:checked + .seovela-toggle-track {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
}

.seovela-toggle-premium input:checked + .seovela-toggle-track .seovela-toggle-thumb {
    transform: translateX(22px);
}

.seovela-toggle-premium input:focus + .seovela-toggle-track {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
}

.seovela-toggle-status {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #94a3b8;
    min-width: 24px;
}

.seovela-toggle-premium input:checked ~ .seovela-toggle-status {
    color: #10b981;
}

/* Unlock Button */
.seovela-unlock-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #ffffff;
    font-size: 12px;
    font-weight: 700;
    padding: 10px 16px;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.seovela-unlock-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
    color: #ffffff;
}

.seovela-unlock-btn .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Active Bar */
.seovela-module-active-bar {
    height: 4px;
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
}

/* Save Bar */
.seovela-modules-save-bar {
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
    padding: 20px 40px;
    margin-top: 40px;
    position: relative;
    z-index: 100;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
    border-radius: 16px 16px 0 0;
    margin-left: 40px;
    margin-right: 40px;
}

.seovela-save-bar-inner {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
}

.seovela-save-bar-info {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #64748b;
    font-size: 14px;
}

.seovela-save-bar-info .dashicons {
    color: #94a3b8;
}

.seovela-save-bar-actions {
    display: flex;
    gap: 12px;
}

.seovela-btn-save {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: #ffffff;
    font-size: 15px;
    font-weight: 700;
    padding: 14px 28px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35);
}

.seovela-btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.45);
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
}

.seovela-btn-save .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.seovela-btn-upgrade {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #ffffff;
    font-size: 15px;
    font-weight: 700;
    padding: 14px 28px;
    border: none;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
}

.seovela-btn-upgrade:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(245, 158, 11, 0.45);
    color: #ffffff;
}

.seovela-btn-upgrade .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

/* Responsive */
@media (max-width: 1200px) {
    .seovela-modules-cards {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }
}

@media (max-width: 960px) {
    .seovela-modules-header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .seovela-modules-header-text p {
        max-width: 100%;
    }
    
    .seovela-modules-stats {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 782px) {
    .seovela-modules-header {
        padding: 32px 20px;
    }
    
    .seovela-modules-header-text h1 {
        font-size: 28px;
    }
    
    .seovela-module-category {
        padding: 0 20px;
    }
    
    .seovela-module-category:first-of-type {
        padding-top: 24px;
    }
    
    .seovela-modules-cards {
        grid-template-columns: 1fr;
    }
    
    .seovela-module-card-inner {
        flex-wrap: wrap;
    }
    
    .seovela-module-info-premium {
        flex: 1 1 calc(100% - 72px);
    }
    
    .seovela-module-toggle-area {
        width: 100%;
        padding-top: 16px;
        border-top: 1px solid #f1f5f9;
        margin-top: 8px;
        display: flex;
        justify-content: flex-end;
    }
    
    .seovela-save-bar-inner {
        flex-direction: column;
    }
    
    .seovela-save-bar-info {
        text-align: center;
    }
    
    .seovela-save-bar-actions {
        width: 100%;
        flex-direction: column;
    }
    
    .seovela-btn-save,
    .seovela-btn-upgrade {
        width: 100%;
        justify-content: center;
    }
    
    .seovela-modules-save-bar {
        padding: 20px;
    }

    .seovela-category-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}

/* Success/Error Messages */
.seovela-modules-premium .notice {
    margin: 20px 40px;
    border-radius: 10px;
    border-left-width: 4px;
}

.seovela-modules-premium .notice-success {
    background: #f0fdf4;
    border-color: #10b981;
}

/* Animation for toggle change */
@keyframes pulse-success {
    0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    50% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0.2); }
}

.seovela-module-card-premium.is-active {
    animation: pulse-success 0.5s ease;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Toggle status text update
    $(".seovela-module-toggle-input").on("change", function() {
        var $card = $(this).closest(".seovela-module-card-premium");
        var $status = $(this).siblings(".seovela-toggle-status");

        if ($(this).is(":checked")) {
            $card.addClass("is-active");
            $status.text("<?php echo esc_js( __( 'On', 'seovela' ) ); ?>");
        } else {
            $card.removeClass("is-active");
            $status.text("<?php echo esc_js( __( 'Off', 'seovela' ) ); ?>");
        }

        // Update stats counter
        updateStats();
    });

    function updateStats() {
        var activeCount = $(".seovela-module-toggle-input:checked").length;
        $(".seovela-modules-stat-card:first .seovela-modules-stat-number").text(activeCount);

        // Update category counts
        $(".seovela-module-category").each(function() {
            var $category = $(this);
            var total = $category.find(".seovela-module-card-premium:not(.is-locked)").length;
            var active = $category.find(".seovela-module-card-premium.is-active").length;
            $category.find(".seovela-category-count").text(active + "/" + total);
        });
    }
});
</script>
