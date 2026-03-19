<?php
/**
 * LLMS Txt Settings View - Modern Light Theme Design
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$llms_txt_url = home_url( '/llms.txt' );
$public_post_types = get_post_types( array( 'public' => true ), 'objects' );
$public_taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );

// Get current settings
$selected_post_types = get_option( 'seovela_llms_txt_post_types', array( 'post', 'page' ) );
$selected_taxonomies = get_option( 'seovela_llms_txt_taxonomies', array() );
$posts_limit = get_option( 'seovela_llms_txt_limit', 260 );
$additional_content = get_option( 'seovela_llms_txt_additional_content', '' );

// Exclude attachment from post types
unset( $public_post_types['attachment'] );

// Get stats
$total_posts = 0;
if ( ! empty( $selected_post_types ) ) {
    foreach ( $selected_post_types as $pt ) {
        $count = wp_count_posts( $pt );
        if ( isset( $count->publish ) ) {
            $total_posts += $count->publish;
        }
    }
}

// Calculate estimated links
$estimated_links = min( $total_posts, $posts_limit * count( $selected_post_types ) );
?>

<div class="wrap seovela-llms-txt-page">
    <!-- Header -->
    <header class="seovela-llms-header">
        <div class="seovela-llms-header-left">
            <div class="seovela-llms-header-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div class="seovela-llms-header-text">
                <h1>
                    <?php esc_html_e( 'LLMS.txt Generator', 'seovela' ); ?>
                    <span class="seovela-llms-badge"><?php esc_html_e( 'NEW', 'seovela' ); ?></span>
                </h1>
                <p><?php esc_html_e( 'Configure AI crawler access', 'seovela' ); ?></p>
            </div>
        </div>
        <button type="submit" form="seovela-llms-txt-form" class="seovela-llms-btn seovela-llms-btn-primary">
            <?php esc_html_e( 'Save Changes', 'seovela' ); ?>
        </button>
    </header>

    <?php settings_errors( 'seovela_llms_txt' ); ?>

    <div class="seovela-llms-container">
        <div class="seovela-llms-grid">
            <!-- Sidebar -->
            <aside class="seovela-llms-sidebar">
                <!-- Live Preview Card -->
                <div class="seovela-llms-card">
                    <div class="seovela-llms-sidebar-header">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                        </svg>
                        <h3><?php esc_html_e( 'Live Preview', 'seovela' ); ?></h3>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="seovela-llms-stats">
                    <!-- Post Types -->
                    <div class="seovela-llms-stat-card">
                        <div class="seovela-llms-stat-icon purple">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="seovela-llms-stat-content">
                            <span class="seovela-llms-stat-number" id="seovela-selected-posts"><?php echo count( $selected_post_types ); ?></span>
                            <span class="seovela-llms-stat-label"><?php esc_html_e( 'Post Types', 'seovela' ); ?></span>
                        </div>
                    </div>

                    <!-- Taxonomies -->
                    <div class="seovela-llms-stat-card">
                        <div class="seovela-llms-stat-icon green">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="seovela-llms-stat-content">
                            <span class="seovela-llms-stat-number" id="seovela-selected-taxonomies"><?php echo count( $selected_taxonomies ); ?></span>
                            <span class="seovela-llms-stat-label"><?php esc_html_e( 'Taxonomies', 'seovela' ); ?></span>
                        </div>
                    </div>

                    <!-- Est. Links -->
                    <div class="seovela-llms-stat-card">
                        <div class="seovela-llms-stat-icon amber">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="seovela-llms-stat-content">
                            <span class="seovela-llms-stat-number" id="seovela-total-links">~<?php echo esc_html( $estimated_links ); ?></span>
                            <span class="seovela-llms-stat-label"><?php esc_html_e( 'Est. Links', 'seovela' ); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Public URL -->
                <div class="seovela-llms-card">
                    <div class="seovela-llms-url-section">
                        <span class="seovela-llms-url-label"><?php esc_html_e( 'Public URL', 'seovela' ); ?></span>
                        <div class="seovela-llms-url-box">
                            <input 
                                type="text" 
                                value="<?php echo esc_attr( str_replace( array( 'http://', 'https://' ), '', $llms_txt_url ) ); ?>" 
                                readonly 
                            />
                            <button type="button" class="seovela-copy-url-btn" data-url="<?php echo esc_attr( $llms_txt_url ); ?>" title="<?php esc_attr_e( 'Copy URL', 'seovela' ); ?>">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="seovela-llms-info-box">
                    <div class="seovela-llms-info-content">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <h4><?php esc_html_e( 'What is llms.txt?', 'seovela' ); ?></h4>
                            <p><?php esc_html_e( 'The llms.txt file helps AI models understand your site structure and prioritize content. It\'s similar to robots.txt but designed for AI crawlers.', 'seovela' ); ?></p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="seovela-llms-main">
                <form method="post" action="options.php" id="seovela-llms-txt-form">
                    <?php settings_fields( 'seovela_llms_txt_settings' ); ?>
                    
                    <!-- Content Types Section -->
                    <section class="seovela-llms-section">
                        <div class="seovela-llms-section-header">
                            <div class="seovela-llms-section-title">
                                <svg fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                                </svg>
                                <h2><?php esc_html_e( 'Content Types', 'seovela' ); ?></h2>
                            </div>
                            <button type="button" class="seovela-llms-toggle-btn" id="seovela-toggle-post-types">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                                <?php esc_html_e( 'Toggle All', 'seovela' ); ?>
                            </button>
                        </div>
                        <p class="seovela-llms-section-description">
                            <?php esc_html_e( 'Select which post types to include in your llms.txt file. Checked types will be visible to AI crawlers.', 'seovela' ); ?>
                        </p>
                        
                        <div class="seovela-llms-checkbox-grid">
                            <?php foreach ( $public_post_types as $post_type ) : 
                                $is_checked = in_array( $post_type->name, $selected_post_types, true );
                                $post_count = wp_count_posts( $post_type->name );
                                $published_count = isset( $post_count->publish ) ? $post_count->publish : 0;
                            ?>
                                <label class="seovela-llms-checkbox-card <?php echo $is_checked ? 'checked' : ''; ?>">
                                    <input 
                                        type="checkbox" 
                                        name="seovela_llms_txt_post_types[]" 
                                        value="<?php echo esc_attr( $post_type->name ); ?>"
                                        <?php checked( $is_checked ); ?>
                                        class="seovela-post-type-checkbox"
                                    />
                                    <div class="seovela-llms-checkbox-content">
                                        <span class="seovela-llms-checkbox-label"><?php echo esc_html( $post_type->labels->name ); ?></span>
                                        <span class="seovela-llms-checkbox-meta"><?php echo esc_html( $published_count ); ?> <?php esc_html_e( 'published', 'seovela' ); ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- Taxonomies Section -->
                    <section class="seovela-llms-section">
                        <div class="seovela-llms-section-header">
                            <div class="seovela-llms-section-title">
                                <svg fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                </svg>
                                <h2><?php esc_html_e( 'Taxonomies', 'seovela' ); ?></h2>
                            </div>
                            <button type="button" class="seovela-llms-toggle-btn" id="seovela-toggle-taxonomies">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                                <?php esc_html_e( 'Toggle All', 'seovela' ); ?>
                            </button>
                        </div>
                        <p class="seovela-llms-section-description">
                            <?php esc_html_e( 'Include taxonomy archive pages to help AI understand your content organization and topics.', 'seovela' ); ?>
                        </p>
                        
                        <div class="seovela-llms-checkbox-grid three-col">
                            <?php foreach ( $public_taxonomies as $taxonomy ) : 
                                $is_checked = in_array( $taxonomy->name, $selected_taxonomies, true );
                                $term_count = wp_count_terms( array( 'taxonomy' => $taxonomy->name, 'hide_empty' => true ) );
                                if ( is_wp_error( $term_count ) ) {
                                    $term_count = 0;
                                }
                            ?>
                                <label class="seovela-llms-checkbox-card <?php echo $is_checked ? 'checked' : ''; ?>">
                                    <input 
                                        type="checkbox" 
                                        name="seovela_llms_txt_taxonomies[]" 
                                        value="<?php echo esc_attr( $taxonomy->name ); ?>"
                                        <?php checked( $is_checked ); ?>
                                        class="seovela-taxonomy-checkbox"
                                    />
                                    <div class="seovela-llms-checkbox-content">
                                        <span class="seovela-llms-checkbox-label"><?php echo esc_html( $taxonomy->labels->name ); ?></span>
                                        <span class="seovela-llms-checkbox-meta"><?php echo esc_html( $term_count ); ?> <?php esc_html_e( 'terms', 'seovela' ); ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- Two Column Grid: Content Limit & Custom Content -->
                    <div class="seovela-llms-two-col">
                        <!-- Content Limit Section -->
                        <section class="seovela-llms-section">
                            <div class="seovela-llms-section-header">
                                <div class="seovela-llms-section-title">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <h2><?php esc_html_e( 'Content Limit', 'seovela' ); ?></h2>
                                </div>
                            </div>
                            <p class="seovela-llms-section-description">
                                <?php esc_html_e( 'Maximum number of items per post type/taxonomy.', 'seovela' ); ?>
                            </p>
                            
                            <div class="seovela-llms-slider-display">
                                <span class="seovela-llms-slider-value" id="seovela-limit-display"><?php echo esc_html( $posts_limit ); ?></span>
                                <span class="seovela-llms-slider-unit"><?php esc_html_e( 'links', 'seovela' ); ?></span>
                            </div>
                            
                            <input 
                                type="range" 
                                id="seovela-limit-slider"
                                name="seovela_llms_txt_limit"
                                min="0" 
                                max="500" 
                                value="<?php echo esc_attr( $posts_limit ); ?>"
                                class="seovela-llms-slider"
                            />
                            
                            <div class="seovela-llms-recommendations">
                                <div class="seovela-llms-rec-item small">
                                    <span class="seovela-llms-rec-label"><?php esc_html_e( 'SMALL SITE', 'seovela' ); ?></span>
                                    <span class="seovela-llms-rec-range">10-50</span>
                                </div>
                                <div class="seovela-llms-rec-item medium">
                                    <span class="seovela-llms-rec-label"><?php esc_html_e( 'MEDIUM', 'seovela' ); ?></span>
                                    <span class="seovela-llms-rec-range">50-200</span>
                                </div>
                                <div class="seovela-llms-rec-item large">
                                    <span class="seovela-llms-rec-label"><?php esc_html_e( 'LARGE SITE', 'seovela' ); ?></span>
                                    <span class="seovela-llms-rec-range">200-500</span>
                                </div>
                            </div>
                        </section>

                        <!-- Custom Content Section -->
                        <section class="seovela-llms-section">
                            <div class="seovela-llms-section-header">
                                <div class="seovela-llms-section-title">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                    <h2><?php esc_html_e( 'Custom Content', 'seovela' ); ?></h2>
                                </div>
                            </div>
                            <p class="seovela-llms-section-description">
                                <?php esc_html_e( 'Add custom lines to the top of your llms.txt file.', 'seovela' ); ?>
                            </p>
                            
                            <textarea 
                                name="seovela_llms_txt_additional_content" 
                                class="seovela-llms-textarea"
                                placeholder="# Important pages&#10;important: https://example.com/about&#10;important: https://example.com/services&#10;&#10;# Instructions for AI&#10;focus: technical documentation"
                            ><?php echo esc_textarea( $additional_content ); ?></textarea>
                            
                            <div class="seovela-llms-textarea-hint">
                                <svg fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                <span><?php esc_html_e( 'Use # for comments, add custom URLs or AI instructions.', 'seovela' ); ?></span>
                            </div>
                        </section>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <footer class="seovela-llms-footer">
        <div class="seovela-llms-footer-inner">
            <button type="button" class="seovela-llms-btn seovela-llms-btn-secondary" id="seovela-reset-llms-options">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <?php esc_html_e( 'Reset to Defaults', 'seovela' ); ?>
            </button>
            <button type="submit" form="seovela-llms-txt-form" class="seovela-llms-btn seovela-llms-btn-primary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                </svg>
                <?php esc_html_e( 'Save Changes', 'seovela' ); ?>
            </button>
        </div>
    </footer>
</div>

<?php
wp_add_inline_script( 'seovela-admin', '
jQuery(document).ready(function($) {
    // Update stats in real-time
    function updateStats() {
        var postTypesCount = $(".seovela-post-type-checkbox:checked").length;
        var taxonomiesCount = $(".seovela-taxonomy-checkbox:checked").length;

        $("#seovela-selected-posts").text(postTypesCount);
        $("#seovela-selected-taxonomies").text(taxonomiesCount);
    }

    // Toggle post types
    $("#seovela-toggle-post-types").on("click", function() {
        var checkboxes = $(".seovela-post-type-checkbox");
        var allChecked = checkboxes.length === checkboxes.filter(":checked").length;
        checkboxes.prop("checked", !allChecked).trigger("change");
    });

    // Toggle taxonomies
    $("#seovela-toggle-taxonomies").on("click", function() {
        var checkboxes = $(".seovela-taxonomy-checkbox");
        var allChecked = checkboxes.length === checkboxes.filter(":checked").length;
        checkboxes.prop("checked", !allChecked).trigger("change");
    });

    // Checkbox card styling
    $(".seovela-llms-checkbox-card input[type=\'checkbox\']").on("change", function() {
        $(this).closest(".seovela-llms-checkbox-card").toggleClass("checked", this.checked);
        updateStats();
    });

    // Slider sync with display
    $("#seovela-limit-slider").on("input", function() {
        $("#seovela-limit-display").text(this.value);
    });

    // Copy URL
    $(".seovela-copy-url-btn").on("click", function() {
        var url = $(this).data("url");
        var $btn = $(this);

        navigator.clipboard.writeText(url).then(function() {
            $btn.addClass("copied");
            $btn.find("svg").html(\'<polyline points="20 6 9 17 4 12" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>\');

            setTimeout(function() {
                $btn.removeClass("copied");
                $btn.find("svg").html(\'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>\');
            }, 2000);
        });
    });

    // Reset options
    $("#seovela-reset-llms-options").on("click", function() {
        if (confirm("' . esc_js( __( 'Reset all LLMS Txt settings to defaults?', 'seovela' ) ) . '")) {
            $(".seovela-post-type-checkbox").prop("checked", false);
            $("input[value=\'post\'], input[value=\'page\']").filter(".seovela-post-type-checkbox").prop("checked", true).trigger("change");
            $(".seovela-taxonomy-checkbox").prop("checked", false).trigger("change");
            $("#seovela-limit-slider").val("260");
            $("#seovela-limit-display").text("260");
            $("textarea[name=\'seovela_llms_txt_additional_content\']").val("");
        }
    });

    // Form submit animation
    $("#seovela-llms-txt-form").on("submit", function() {
        $(this).find(".seovela-llms-btn-primary").addClass("seovela-llms-btn-loading");
        $(".seovela-llms-footer .seovela-llms-btn-primary").addClass("seovela-llms-btn-loading");
    });
});
' );
?>

<?php
