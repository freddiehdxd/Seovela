<?php
/**
 * Seovela Metabox Class
 *
 * Adds SEO meta box to posts and pages
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Seovela Metabox Class
 */
class Seovela_Metabox {

    /**
     * Hooks added flag
     *
     * @var bool
     */
    private static $hooks_added = false;

    /**
     * Constructor
     */
    public function __construct() {
        // Prevent duplicate hooks
        if ( self::$hooks_added ) {
            return;
        }
        self::$hooks_added = true;

        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_meta_box' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_metabox_assets' ) );
        add_action( 'init', array( $this, 'register_post_meta_for_rest' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_gutenberg_sidebar' ) );
    }

    /**
     * Check if AI is configured
     *
     * @return bool
     */
    private function is_ai_configured() {
        $provider = get_option( 'seovela_ai_provider', 'openai' );
        $ai_post_types = get_option( 'seovela_ai_post_types', array( 'post', 'page' ) );
        
        // Check if current post type is enabled for AI
        $current_post_type = get_post_type();
        if ( ! in_array( $current_post_type, $ai_post_types, true ) ) {
            return false;
        }
        
        switch ( $provider ) {
            case 'openai':
                return ! empty( get_option( 'seovela_openai_api_key', '' ) );
            case 'gemini':
                return ! empty( get_option( 'seovela_gemini_api_key', '' ) );
            case 'claude':
                return ! empty( get_option( 'seovela_claude_api_key', '' ) );
            default:
                return false;
        }
    }

    /**
     * Register post meta fields for the REST API / Block Editor.
     *
     * This allows the Gutenberg sidebar to read and write
     * SEO meta through the standard post-save mechanism.
     */
    public function register_post_meta_for_rest() {
        $string_meta_keys = array(
            '_seovela_meta_title',
            '_seovela_meta_description',
            '_seovela_focus_keyword',
        );

        foreach ( $string_meta_keys as $meta_key ) {
            register_post_meta( '', $meta_key, array(
                'show_in_rest'  => true,
                'single'        => true,
                'type'          => 'string',
                'default'       => '',
                'auth_callback' => function () {
                    return current_user_can( 'edit_posts' );
                },
            ) );
        }

        // Boolean meta fields
        $boolean_meta_keys = array(
            '_seovela_noindex',
            '_seovela_nofollow',
        );

        foreach ( $boolean_meta_keys as $meta_key ) {
            register_post_meta( '', $meta_key, array(
                'show_in_rest'  => true,
                'single'        => true,
                'type'          => 'boolean',
                'default'       => false,
                'auth_callback' => function () {
                    return current_user_can( 'edit_posts' );
                },
            ) );
        }

        // Integer meta field for SEO score
        register_post_meta( '', '_seovela_seo_score', array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'integer',
            'default'       => 0,
            'auth_callback' => function () {
                return current_user_can( 'edit_posts' );
            },
        ) );
    }

    /**
     * Enqueue Gutenberg sidebar script when the block editor is active.
     */
    public function enqueue_gutenberg_sidebar() {
        wp_enqueue_script(
            'seovela-gutenberg-sidebar',
            SEOVELA_PLUGIN_URL . 'assets/js/gutenberg-sidebar.js',
            array(
                'wp-plugins',
                'wp-edit-post',
                'wp-components',
                'wp-data',
                'wp-element',
                'wp-i18n',
                'wp-editor',
            ),
            SEOVELA_VERSION,
            true
        );

        // Check if AI is configured
        $ai_configured = $this->is_ai_configured();

        $localize_data = array(
            'aiEnabled' => $ai_configured,
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'siteUrl'   => home_url( '/' ),
        );

        // Add AI nonce only when AI is available
        if ( $ai_configured ) {
            $localize_data['aiNonce'] = wp_create_nonce( 'seovela_ai_nonce' );
        }

        wp_localize_script( 'seovela-gutenberg-sidebar', 'seovelaEditor', $localize_data );
    }

    /**
     * Add meta box
     */
    public function add_meta_box() {
        $post_types = get_post_types( array( 'public' => true ), 'names' );
        
        foreach ( $post_types as $post_type ) {
            if ( $post_type !== 'attachment' ) {
                add_meta_box(
                    'seovela_meta_box',
                    __( 'Seovela SEO', 'seovela' ),
                    array( $this, 'render_meta_box' ),
                    $post_type,
                    'normal',
                    'high'
                );
            }
        }
    }

    /**
     * Enqueue metabox assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_metabox_assets( $hook ) {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
            return;
        }

        wp_enqueue_style(
            'seovela-metabox',
            SEOVELA_PLUGIN_URL . 'assets/css/metabox.css',
            array(),
            SEOVELA_VERSION
        );

        wp_enqueue_script(
            'seovela-metabox',
            SEOVELA_PLUGIN_URL . 'assets/js/metabox.js',
            array( 'jquery' ),
            SEOVELA_VERSION,
            true
        );

        // Check if AI is configured
        $ai_configured = $this->is_ai_configured();

        // Localize script data
        $localize_data = array(
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'analysisNonce' => wp_create_nonce( 'seovela_metabox_nonce' ),
            'aiEnabled'     => $ai_configured,
            'aiProvider'    => get_option( 'seovela_ai_provider', 'openai' ),
        );

        // Add AI nonce if AI is configured
        if ( $ai_configured ) {
            $localize_data['aiNonce'] = wp_create_nonce( 'seovela_ai_nonce' );
        }

            wp_localize_script(
                'seovela-metabox',
                'seovelaMetabox',
            $localize_data
            );
    }

    /**
     * Render meta box content
     *
     * @param WP_Post $post Post object
     */
    public function render_meta_box( $post ) {
        // Load schema builder
        require_once SEOVELA_PLUGIN_DIR . 'modules/schema/class-schema-builder.php';

        // Nonce field
        wp_nonce_field( 'seovela_save_meta_box', 'seovela_meta_box_nonce' );

        // Get current values
        $meta_title = get_post_meta( $post->ID, '_seovela_meta_title', true );
        $meta_description = get_post_meta( $post->ID, '_seovela_meta_description', true );
        $focus_keyword = get_post_meta( $post->ID, '_seovela_focus_keyword', true );
        $noindex = get_post_meta( $post->ID, '_seovela_noindex', true );
        $nofollow = get_post_meta( $post->ID, '_seovela_nofollow', true );

        // Default values
        if ( empty( $meta_title ) ) {
            $meta_title = get_the_title( $post->ID );
        }
        if ( empty( $meta_description ) ) {
            $meta_description = wp_trim_words( $post->post_content, 20 );
        }

        // Load cached SEO score from post meta instead of running analysis synchronously
        $cached_score = get_post_meta( $post->ID, '_seovela_seo_score', true );

        if ( ! empty( $cached_score ) && is_array( $cached_score ) ) {
            $analysis = $cached_score;
        } else {
            // No cached score available — show a neutral placeholder
            $analysis = array(
                'score'    => 0,
                'status'   => 'unknown',
                'errors'   => array(),
                'warnings' => array(),
                'good'     => array(),
            );
        }

        // Determine progress color based on status
        $progress_color = '#94a3b8';
        if ( $analysis['status'] === 'good' ) {
            $progress_color = '#10b981';
        } elseif ( $analysis['status'] === 'warning' ) {
            $progress_color = '#f59e0b';
        } elseif ( $analysis['status'] === 'error' ) {
            $progress_color = '#ef4444';
        }

        // Calculate stroke offset for server-side rendering
        $circumference = 339.29;
        $offset = $circumference - ( $analysis['score'] / 100 ) * $circumference;

        // Load status label helper if available
        $status_label = '';
        if ( $analysis['status'] !== 'unknown' ) {
            require_once SEOVELA_PLUGIN_DIR . 'modules/content-analysis/class-seo-scorer.php';
            $status_label = Seovela_SEO_Scorer::get_status_label( $analysis['status'] );
        } else {
            $status_label = __( 'Not analyzed', 'seovela' );
        }
        ?>
        <div class="seovela-metabox">
            <style>
                /* Critical styles fallback */
                .seovela-score-bg, .seovela-score-progress { fill: none; stroke-width: 10; }
                .seovela-score-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; }
                .seovela-score-circle { position: relative; width: 120px; height: 120px; }
            </style>
            <!-- SEO Score Widget -->
            <div class="seovela-score-widget">
                <div class="seovela-score-circle" data-score="<?php echo esc_attr( $analysis['score'] ); ?>" data-status="<?php echo esc_attr( $analysis['status'] ); ?>">
                    <svg class="seovela-score-svg" width="120" height="120" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                        <circle class="seovela-score-bg" cx="60" cy="60" r="54" fill="none" stroke="#e5e7eb" stroke-width="10"></circle>
                        <circle class="seovela-score-progress" cx="60" cy="60" r="54" fill="none" stroke="<?php echo esc_attr( $progress_color ); ?>" stroke-width="10" style="stroke-dasharray: 339.29; stroke-dashoffset: <?php echo esc_attr( $offset ); ?>"></circle>
                    </svg>
                    <div class="seovela-score-text">
                        <span class="seovela-score-number"><?php echo esc_html( $analysis['score'] ); ?></span>
                        <span class="seovela-score-label"><?php echo esc_html( $status_label ); ?></span>
                    </div>
                </div>
                <div class="seovela-score-info">
                    <h3><?php esc_html_e( 'SEO Score', 'seovela' ); ?></h3>
                    <p><?php esc_html_e( 'Optimize your content for better search engine rankings', 'seovela' ); ?></p>
                    <button type="button" class="button seovela-refresh-analysis"><?php esc_html_e( 'Refresh Analysis', 'seovela' ); ?></button>
                    <button type="button" class="button seovela-toggle-analysis"><?php esc_html_e( 'View Analysis', 'seovela' ); ?></button>
                </div>
            </div>

            <!-- Analysis Results (collapsible) -->
            <div class="seovela-analysis-results" style="display: none;">
                <?php if ( $analysis['status'] === 'unknown' ) : ?>
                    <p class="seovela-no-analysis"><?php esc_html_e( 'No analysis available yet. Click "Refresh Analysis" to run the SEO check.', 'seovela' ); ?></p>
                <?php else : ?>
                    <?php if ( ! empty( $analysis['errors'] ) ) : ?>
                        <div class="seovela-analysis-section seovela-errors">
                            <h4>❌ <?php esc_html_e( 'Errors', 'seovela' ); ?> (<?php echo esc_html( count( $analysis['errors'] ) ); ?>)</h4>
                            <ul>
                                <?php foreach ( $analysis['errors'] as $error ) : ?>
                                    <li><?php echo esc_html( $error ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $analysis['warnings'] ) ) : ?>
                        <div class="seovela-analysis-section seovela-warnings">
                            <h4>⚠️ <?php esc_html_e( 'Warnings', 'seovela' ); ?> (<?php echo esc_html( count( $analysis['warnings'] ) ); ?>)</h4>
                            <ul>
                                <?php foreach ( $analysis['warnings'] as $warning ) : ?>
                                    <li><?php echo esc_html( $warning ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $analysis['good'] ) ) : ?>
                        <div class="seovela-analysis-section seovela-good">
                            <h4>✓ <?php esc_html_e( 'Good', 'seovela' ); ?> (<?php echo esc_html( count( $analysis['good'] ) ); ?>)</h4>
                            <ul>
                                <?php foreach ( $analysis['good'] as $good ) : ?>
                                    <li><?php echo esc_html( $good ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Focus Keyword -->
            <div class="seovela-field seovela-keyword-field">
                <label for="seovela_focus_keyword">
                    <strong><?php esc_html_e( 'Focus Keyword', 'seovela' ); ?></strong>
                    <span class="seovela-help-tip" title="<?php esc_attr_e( 'Enter the main keyword you want to optimize this content for', 'seovela' ); ?>">?</span>
                </label>
                <div class="seovela-keyword-wrapper">
                <input 
                    type="text" 
                    id="seovela_focus_keyword" 
                    name="seovela_focus_keyword" 
                    value="<?php echo esc_attr( $focus_keyword ); ?>" 
                    class="widefat seovela-keyword-input"
                    placeholder="<?php esc_attr_e( 'e.g., WordPress SEO plugin', 'seovela' ); ?>"
                />
                    <?php if ( $this->is_ai_configured() ) : ?>
                        <button type="button" class="button seovela-suggest-keywords">
                            <span class="dashicons dashicons-lightbulb"></span>
                            <?php esc_html_e( 'Suggest', 'seovela' ); ?>
                        </button>
                    <?php endif; ?>
                </div>
                <div class="seovela-keyword-suggestions" style="display: none;">
                    <p class="description"><?php esc_html_e( 'Suggested keywords (click to use):', 'seovela' ); ?></p>
                    <div class="seovela-suggestions-list"></div>
                </div>
                <p class="description"><?php esc_html_e( 'Enter a keyword or phrase to optimize this content for search engines', 'seovela' ); ?></p>
            </div>

            <!-- Meta Title -->
            <div class="seovela-field">
                <label for="seovela_meta_title">
                    <strong><?php esc_html_e( 'Meta Title', 'seovela' ); ?></strong>
                </label>
                <input 
                    type="text" 
                    id="seovela_meta_title" 
                    name="seovela_meta_title" 
                    value="<?php echo esc_attr( $meta_title ); ?>" 
                    class="widefat seovela-title-input"
                    maxlength="70"
                />
                <div class="seovela-counter">
                    <span class="seovela-count" data-field="title">0</span> / 60 <?php esc_html_e( 'characters', 'seovela' ); ?>
                    <span class="seovela-status"></span>
                </div>
                <?php if ( $this->is_ai_configured() ) : ?>
                    <button type="button" class="button seovela-ai-optimize" data-field="title">
                        <span class="dashicons dashicons-superhero-alt"></span>
                        <?php esc_html_e( 'Generate with AI', 'seovela' ); ?>
                    </button>
                <?php else : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=ai' ) ); ?>" class="button seovela-ai-setup-link">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php esc_html_e( 'Setup AI', 'seovela' ); ?>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Meta Description -->
            <div class="seovela-field">
                <label for="seovela_meta_description">
                    <strong><?php esc_html_e( 'Meta Description', 'seovela' ); ?></strong>
                </label>
                <textarea 
                    id="seovela_meta_description" 
                    name="seovela_meta_description" 
                    rows="3" 
                    class="widefat seovela-description-input"
                    maxlength="170"
                ><?php echo esc_textarea( $meta_description ); ?></textarea>
                <div class="seovela-counter">
                    <span class="seovela-count" data-field="description">0</span> / 160 <?php esc_html_e( 'characters', 'seovela' ); ?>
                    <span class="seovela-status"></span>
                </div>
                <?php if ( $this->is_ai_configured() ) : ?>
                    <button type="button" class="button seovela-ai-optimize" data-field="description">
                        <span class="dashicons dashicons-superhero-alt"></span>
                        <?php esc_html_e( 'Generate with AI', 'seovela' ); ?>
                    </button>
                <?php else : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=ai' ) ); ?>" class="button seovela-ai-setup-link">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php esc_html_e( 'Setup AI', 'seovela' ); ?>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Google Preview -->
            <div class="seovela-field">
                <label><strong><?php esc_html_e( 'Google Preview', 'seovela' ); ?></strong></label>
                <div class="seovela-google-preview">
                    <div class="seovela-preview-url"><?php echo esc_url( get_permalink( $post->ID ) ); ?></div>
                    <div class="seovela-preview-title"><?php echo esc_html( $meta_title ); ?></div>
                    <div class="seovela-preview-description"><?php echo esc_html( $meta_description ); ?></div>
                </div>
            </div>


            <!-- Indexing Settings -->
            <div class="seovela-field">
                <label><strong><?php esc_html_e( 'Indexing Settings', 'seovela' ); ?></strong></label>
                <div class="seovela-checkbox-group">
                    <label>
                        <input 
                            type="checkbox" 
                            name="seovela_noindex" 
                            value="1" 
                            <?php checked( $noindex, true ); ?>
                        />
                        <?php esc_html_e( 'Noindex (hide from search engines)', 'seovela' ); ?>
                    </label>
                    <br>
                    <label>
                        <input 
                            type="checkbox" 
                            name="seovela_nofollow" 
                            value="1" 
                            <?php checked( $nofollow, true ); ?>
                        />
                        <?php esc_html_e( 'Nofollow (don\'t follow links)', 'seovela' ); ?>
                    </label>
                </div>
            </div>

            <?php
            // Render schema selector
            $this->render_schema_selector( $post );
            ?>
        </div>
        <?php
    }

    /**
     * Render schema selector section
     *
     * @param WP_Post $post Post object
     */
    private function render_schema_selector( $post ) {
        // Get current schema settings
        $schema_type = get_post_meta( $post->ID, '_seovela_schema_type', true );
        $disable_schema = get_post_meta( $post->ID, '_seovela_disable_schema', true );
        $additional_types = get_post_meta( $post->ID, '_seovela_schema_additional_types', true );
        
        if ( empty( $schema_type ) ) {
            $schema_type = 'auto';
        }
        
        if ( ! is_array( $additional_types ) ) {
            $additional_types = array();
        }

        // Get available schema types
        $available_types = Seovela_Schema_Builder::get_available_types();
        $default_type = Seovela_Schema_Builder::get_default_schema_type( $post->ID );
        ?>
        
        <!-- Schema Settings - Modern Accordion Design -->
        <div class="seovela-field seovela-schema-section">
            <!-- Schema Header with Toggle -->
            <div class="seovela-schema-header" id="seovela-schema-header">
                <div class="seovela-schema-header-content">
                    <div class="seovela-schema-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                            <path d="M2 17l10 5 10-5"/>
                            <path d="M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <div class="seovela-schema-header-text">
                        <strong><?php esc_html_e( 'Schema Markup', 'seovela' ); ?></strong>
                        <span class="seovela-schema-subtitle"><?php esc_html_e( 'Structured data for rich search results', 'seovela' ); ?></span>
                    </div>
                </div>
                <div class="seovela-schema-header-toggle">
                    <span class="seovela-schema-status <?php echo $disable_schema === 'yes' ? 'disabled' : 'active'; ?>">
                        <?php echo $disable_schema === 'yes' ? esc_html__( 'Disabled', 'seovela' ) : esc_html__( 'Active', 'seovela' ); ?>
                    </span>
                    <span class="seovela-schema-chevron">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </span>
                </div>
            </div>
            
            <!-- Schema Content (Collapsible) -->
            <div class="seovela-schema-content" id="seovela-schema-content" style="display: none;">
                <!-- Disable Schema Toggle -->
                <div class="seovela-schema-toggle-row">
                    <label class="seovela-toggle-switch">
                        <input 
                            type="checkbox" 
                            name="seovela_disable_schema" 
                            value="yes" 
                            <?php checked( $disable_schema, 'yes' ); ?>
                            id="seovela_disable_schema"
                        />
                        <span class="seovela-toggle-slider"></span>
                    </label>
                    <span class="seovela-toggle-label"><?php esc_html_e( 'Disable schema for this page', 'seovela' ); ?></span>
                </div>

                <div class="seovela-schema-selector" id="seovela-schema-selector" <?php echo $disable_schema === 'yes' ? 'style="display:none;"' : ''; ?>>
                    <!-- Primary Schema Type Card -->
                    <div class="seovela-schema-card">
                        <div class="seovela-schema-card-header">
                            <span class="seovela-schema-card-icon">🎯</span>
                            <label for="seovela_schema_type"><?php esc_html_e( 'Primary Schema Type', 'seovela' ); ?></label>
                        </div>
                        <select name="seovela_schema_type" id="seovela_schema_type" class="seovela-select-modern">
                            <option value="auto" <?php selected( $schema_type, 'auto' ); ?>>
                                <?php
                                /* translators: %s: Default schema type name */
                                printf( esc_html__( 'Auto-detect (Default: %s)', 'seovela' ), esc_html( $default_type ? $default_type : 'None' ) );
                                ?>
                            </option>
                            <?php foreach ( $available_types as $type_key => $type_data ) : ?>
                                <option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $schema_type, $type_key ); ?>>
                                    <?php echo esc_html( $type_data['name'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="seovela-schema-type-description" id="seovela-schema-description"></p>
                    </div>

                    <!-- Additional Schema Types Accordion -->
                    <div class="seovela-accordion" id="seovela-additional-accordion">
                        <div class="seovela-accordion-header" data-target="additional-types">
                            <span class="seovela-accordion-icon">➕</span>
                            <span class="seovela-accordion-title"><?php esc_html_e( 'Additional Schema Types', 'seovela' ); ?></span>
                            <span class="seovela-accordion-badge"><?php echo esc_html( count( $additional_types ) ); ?></span>
                            <span class="seovela-accordion-chevron">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </span>
                        </div>
                        <div class="seovela-accordion-content" id="additional-types">
                            <p class="seovela-accordion-desc"><?php esc_html_e( 'Select compatible schema types to combine with your primary type.', 'seovela' ); ?></p>
                            <div class="seovela-schema-types-grid">
                                <?php foreach ( $available_types as $type_key => $type_data ) : ?>
                                    <label class="seovela-schema-type-chip <?php echo in_array( $type_key, $additional_types, true ) ? 'selected' : ''; ?>">
                                        <input 
                                            type="checkbox" 
                                            name="seovela_schema_additional_types[]" 
                                            value="<?php echo esc_attr( $type_key ); ?>"
                                            <?php checked( in_array( $type_key, $additional_types, true ) ); ?>
                                            class="seovela-additional-schema"
                                            data-schema-type="<?php echo esc_attr( $type_key ); ?>"
                                        />
                                        <span class="seovela-type-name"><?php echo esc_html( $type_data['name'] ); ?></span>
                                        <span class="seovela-type-check">✓</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="seovela-schema-warnings" id="seovela-schema-warnings"></div>

                    <!-- Schema Actions -->
                    <div class="seovela-schema-actions-row">
                        <button type="button" class="seovela-btn seovela-btn-secondary" id="seovela-preview-schema">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <?php esc_html_e( 'Preview Schema', 'seovela' ); ?>
                        </button>
                        <a href="<?php echo esc_url( Seovela_Schema_Builder::get_rich_results_test_url( $post->ID ) ); ?>" 
                           class="seovela-btn seovela-btn-outline" 
                           target="_blank"
                           rel="noopener noreferrer">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                            <?php esc_html_e( 'Test with Google', 'seovela' ); ?>
                        </a>
                    </div>

                    <div class="seovela-schema-preview-container" id="seovela-schema-preview" style="display: none;">
                        <div class="seovela-schema-preview-header">
                            <span><?php esc_html_e( 'JSON-LD Preview', 'seovela' ); ?></span>
                            <button type="button" class="seovela-preview-close" id="seovela-preview-close">×</button>
                        </div>
                        <pre class="seovela-schema-json"></pre>
                    </div>
                </div>

                <?php
                // Render schema-specific fields (accordion style)
                $this->render_schema_fields( $post, $schema_type );
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render schema-specific fields
     *
     * @param WP_Post $post Post object
     * @param string  $schema_type Current schema type
     */
    private function render_schema_fields( $post, $schema_type ) {
        $disable_schema = get_post_meta( $post->ID, '_seovela_disable_schema', true );
        
        if ( $disable_schema === 'yes' ) {
            return;
        }

        // Schema fields container
        ?>
        <div class="seovela-schema-fields-wrapper" id="seovela-schema-fields-wrapper">
            <?php
            // Render FAQ fields
            $this->render_faq_fields( $post );

            // Render HowTo fields
            $this->render_howto_fields( $post );

            // Render Product fields
            $this->render_product_fields( $post );

            // Render Person fields
            $this->render_person_fields( $post );
            ?>
        </div>
        <?php
    }

    /**
     * Render FAQ schema fields
     *
     * @param WP_Post $post Post object
     */
    private function render_faq_fields( $post ) {
        $faq_items = get_post_meta( $post->ID, '_seovela_faq_items', true );
        $auto_detect = get_post_meta( $post->ID, '_seovela_faq_auto_detect', true );
        
        if ( ! is_array( $faq_items ) ) {
            $faq_items = array();
        }
        
        $faq_count = count( array_filter( $faq_items, function( $item ) {
            return ! empty( $item['question'] );
        } ) );
        ?>
        <div class="seovela-schema-accordion seovela-faq-fields" data-schema="FAQ" style="display: none;">
            <div class="seovela-schema-accordion-header" data-target="faq-content">
                <div class="seovela-schema-accordion-info">
                    <span class="seovela-schema-type-icon">❓</span>
                    <div class="seovela-schema-type-text">
                        <span class="seovela-schema-type-title"><?php esc_html_e( 'FAQ Schema', 'seovela' ); ?></span>
                        <span class="seovela-schema-type-desc"><?php esc_html_e( 'Questions & Answers for rich results', 'seovela' ); ?></span>
                    </div>
                </div>
                <div class="seovela-schema-accordion-meta">
                    <?php if ( $faq_count > 0 ) : ?>
                        <span class="seovela-schema-count"><?php printf( esc_html__( '%d items', 'seovela' ), $faq_count ); ?></span>
                    <?php endif; ?>
                    <span class="seovela-schema-accordion-chevron">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </span>
                </div>
            </div>
            <div class="seovela-schema-accordion-content" id="faq-content">
                <!-- Auto-detect Option -->
                <div class="seovela-schema-option-card">
                    <label class="seovela-toggle-switch">
                        <input 
                            type="checkbox" 
                            name="seovela_faq_auto_detect" 
                            value="yes" 
                            <?php checked( $auto_detect, 'yes' ); ?>
                        />
                        <span class="seovela-toggle-slider"></span>
                    </label>
                    <div class="seovela-toggle-text">
                        <span class="seovela-toggle-title"><?php esc_html_e( 'Auto-detect FAQs', 'seovela' ); ?></span>
                        <span class="seovela-toggle-desc"><?php esc_html_e( 'Extract Q&A pairs from H2/H3 headings that look like questions.', 'seovela' ); ?></span>
                    </div>
                </div>

                <!-- Manual FAQ Items -->
                <div class="seovela-faq-manual">
                    <div class="seovela-manual-header">
                        <span class="seovela-manual-title"><?php esc_html_e( 'Manual FAQ Items', 'seovela' ); ?></span>
                    </div>
                    <div class="seovela-faq-repeater" id="seovela-faq-repeater">
                        <?php
                        if ( ! empty( $faq_items ) ) {
                            foreach ( $faq_items as $index => $item ) {
                                $this->render_faq_item( $index, $item );
                            }
                        } else {
                            $this->render_faq_item( 0, array( 'question' => '', 'answer' => '' ) );
                        }
                        ?>
                    </div>
                    <button type="button" class="seovela-btn seovela-btn-add seovela-add-faq">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        <?php esc_html_e( 'Add FAQ Item', 'seovela' ); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render single FAQ item
     *
     * @param int   $index Item index
     * @param array $item FAQ item data
     */
    private function render_faq_item( $index, $item ) {
        ?>
        <div class="seovela-faq-item" data-index="<?php echo esc_attr( $index ); ?>">
            <div class="seovela-faq-item-header">
                <span class="seovela-faq-item-number"><?php echo esc_html( $index + 1 ); ?></span>
                <button type="button" class="seovela-remove-faq" title="<?php esc_attr_e( 'Remove', 'seovela' ); ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="seovela-faq-fields-group">
                <div class="seovela-input-wrapper">
                    <label class="seovela-input-label"><?php esc_html_e( 'Question', 'seovela' ); ?></label>
                    <input 
                        type="text" 
                        name="seovela_faq_items[<?php echo esc_attr( $index ); ?>][question]" 
                        value="<?php echo esc_attr( $item['question'] ); ?>" 
                        placeholder="<?php esc_attr_e( 'e.g., What is your return policy?', 'seovela' ); ?>"
                        class="seovela-input seovela-faq-question"
                    />
                </div>
                <div class="seovela-input-wrapper">
                    <label class="seovela-input-label"><?php esc_html_e( 'Answer', 'seovela' ); ?></label>
                    <textarea 
                        name="seovela_faq_items[<?php echo esc_attr( $index ); ?>][answer]" 
                        rows="3" 
                        placeholder="<?php esc_attr_e( 'Provide a clear and helpful answer...', 'seovela' ); ?>"
                        class="seovela-textarea seovela-faq-answer"
                    ><?php echo esc_textarea( $item['answer'] ); ?></textarea>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render HowTo schema fields
     *
     * @param WP_Post $post Post object
     */
    private function render_howto_fields( $post ) {
        $howto_steps = get_post_meta( $post->ID, '_seovela_howto_steps', true );
        $total_time = get_post_meta( $post->ID, '_seovela_howto_total_time', true );
        
        if ( ! is_array( $howto_steps ) ) {
            $howto_steps = array();
        }
        
        $step_count = count( array_filter( $howto_steps, function( $step ) {
            return ! empty( $step['name'] );
        } ) );
        ?>
        <div class="seovela-schema-accordion seovela-howto-fields" data-schema="HowTo" style="display: none;">
            <div class="seovela-schema-accordion-header" data-target="howto-content">
                <div class="seovela-schema-accordion-info">
                    <span class="seovela-schema-type-icon">📝</span>
                    <div class="seovela-schema-type-text">
                        <span class="seovela-schema-type-title"><?php esc_html_e( 'HowTo Schema', 'seovela' ); ?></span>
                        <span class="seovela-schema-type-desc"><?php esc_html_e( 'Step-by-step instructions for tutorials', 'seovela' ); ?></span>
                    </div>
                </div>
                <div class="seovela-schema-accordion-meta">
                    <?php if ( $step_count > 0 ) : ?>
                        <span class="seovela-schema-count"><?php printf( esc_html__( '%d steps', 'seovela' ), $step_count ); ?></span>
                    <?php endif; ?>
                    <span class="seovela-schema-accordion-chevron">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </span>
                </div>
            </div>
            <div class="seovela-schema-accordion-content" id="howto-content">
                <!-- Total Time -->
                <div class="seovela-schema-option-card seovela-time-card">
                    <div class="seovela-time-icon">⏱️</div>
                    <div class="seovela-time-content">
                        <label for="seovela_howto_total_time" class="seovela-input-label"><?php esc_html_e( 'Total Time (optional)', 'seovela' ); ?></label>
                        <input 
                            type="text" 
                            id="seovela_howto_total_time" 
                            name="seovela_howto_total_time" 
                            value="<?php echo esc_attr( $total_time ); ?>" 
                            placeholder="PT30M"
                            class="seovela-input seovela-input-sm"
                        />
                        <span class="seovela-input-hint"><?php esc_html_e( 'Format: PT30M (30 min), PT2H (2 hours)', 'seovela' ); ?></span>
                    </div>
                </div>

                <!-- Steps -->
                <div class="seovela-howto-steps-container">
                    <div class="seovela-manual-header">
                        <span class="seovela-manual-title"><?php esc_html_e( 'Instructions', 'seovela' ); ?></span>
                    </div>
                    <div class="seovela-howto-repeater" id="seovela-howto-repeater">
                        <?php
                        if ( ! empty( $howto_steps ) ) {
                            foreach ( $howto_steps as $index => $step ) {
                                $this->render_howto_step( $index, $step );
                            }
                        } else {
                            $this->render_howto_step( 0, array( 'name' => '', 'text' => '' ) );
                        }
                        ?>
                    </div>
                    <button type="button" class="seovela-btn seovela-btn-add seovela-add-howto-step">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        <?php esc_html_e( 'Add Step', 'seovela' ); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render single HowTo step
     *
     * @param int   $index Step index
     * @param array $step Step data
     */
    private function render_howto_step( $index, $step ) {
        ?>
        <div class="seovela-howto-step" data-index="<?php echo esc_attr( $index ); ?>">
            <div class="seovela-howto-step-header">
                <span class="seovela-howto-step-number"><?php echo esc_html( $index + 1 ); ?></span>
                <button type="button" class="seovela-remove-howto-step" title="<?php esc_attr_e( 'Remove', 'seovela' ); ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="seovela-howto-fields-group">
                <div class="seovela-input-wrapper">
                    <label class="seovela-input-label"><?php esc_html_e( 'Step Title', 'seovela' ); ?></label>
                    <input 
                        type="text" 
                        name="seovela_howto_steps[<?php echo esc_attr( $index ); ?>][name]" 
                        value="<?php echo esc_attr( isset( $step['name'] ) ? $step['name'] : '' ); ?>" 
                        placeholder="<?php esc_attr_e( 'e.g., Mix the ingredients', 'seovela' ); ?>"
                        class="seovela-input seovela-howto-step-name"
                    />
                </div>
                <div class="seovela-input-wrapper">
                    <label class="seovela-input-label"><?php esc_html_e( 'Instructions', 'seovela' ); ?></label>
                    <textarea 
                        name="seovela_howto_steps[<?php echo esc_attr( $index ); ?>][text]" 
                        rows="2" 
                        placeholder="<?php esc_attr_e( 'Describe what to do in this step...', 'seovela' ); ?>"
                        class="seovela-textarea seovela-howto-step-text"
                    ><?php echo esc_textarea( isset( $step['text'] ) ? $step['text'] : '' ); ?></textarea>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Product schema fields
     *
     * @param WP_Post $post Post object
     */
    private function render_product_fields( $post ) {
        $product_price = get_post_meta( $post->ID, '_seovela_product_price', true );
        $product_currency = get_post_meta( $post->ID, '_seovela_product_currency', true );
        $product_availability = get_post_meta( $post->ID, '_seovela_product_availability', true );
        $product_sku = get_post_meta( $post->ID, '_seovela_product_sku', true );
        $product_brand = get_post_meta( $post->ID, '_seovela_product_brand', true );
        $product_rating = get_post_meta( $post->ID, '_seovela_product_rating', true );
        $product_review_count = get_post_meta( $post->ID, '_seovela_product_review_count', true );
        
        if ( empty( $product_currency ) ) {
            $product_currency = 'USD';
        }
        if ( empty( $product_availability ) ) {
            $product_availability = 'in_stock';
        }
        ?>
        <div class="seovela-schema-accordion seovela-product-fields" data-schema="Product" style="display: none;">
            <div class="seovela-schema-accordion-header" data-target="product-content">
                <div class="seovela-schema-accordion-info">
                    <span class="seovela-schema-type-icon">🛍️</span>
                    <div class="seovela-schema-type-text">
                        <span class="seovela-schema-type-title"><?php esc_html_e( 'Product Schema', 'seovela' ); ?></span>
                        <span class="seovela-schema-type-desc"><?php esc_html_e( 'Price, availability & ratings in search', 'seovela' ); ?></span>
                    </div>
                </div>
                <div class="seovela-schema-accordion-meta">
                    <?php if ( ! empty( $product_price ) ) : ?>
                        <span class="seovela-schema-count"><?php echo esc_html( $product_currency . ' ' . $product_price ); ?></span>
                    <?php endif; ?>
                    <span class="seovela-schema-accordion-chevron">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </span>
                </div>
            </div>
            <div class="seovela-schema-accordion-content" id="product-content">
                <!-- Price & Currency -->
                <div class="seovela-product-grid">
                    <div class="seovela-input-wrapper">
                        <label for="seovela_product_price" class="seovela-input-label">
                            💰 <?php esc_html_e( 'Price', 'seovela' ); ?>
                        </label>
                        <input 
                            type="number" 
                            id="seovela_product_price" 
                            name="seovela_product_price" 
                            value="<?php echo esc_attr( $product_price ); ?>" 
                            step="0.01"
                            placeholder="29.99"
                            class="seovela-input"
                        />
                    </div>
                    <div class="seovela-input-wrapper">
                        <label for="seovela_product_currency" class="seovela-input-label">
                            🌐 <?php esc_html_e( 'Currency', 'seovela' ); ?>
                        </label>
                        <select id="seovela_product_currency" name="seovela_product_currency" class="seovela-select">
                            <option value="USD" <?php selected( $product_currency, 'USD' ); ?>>USD ($)</option>
                            <option value="EUR" <?php selected( $product_currency, 'EUR' ); ?>>EUR (€)</option>
                            <option value="GBP" <?php selected( $product_currency, 'GBP' ); ?>>GBP (£)</option>
                            <option value="CAD" <?php selected( $product_currency, 'CAD' ); ?>>CAD ($)</option>
                            <option value="AUD" <?php selected( $product_currency, 'AUD' ); ?>>AUD ($)</option>
                        </select>
                    </div>
                </div>

                <!-- Availability -->
                <div class="seovela-input-wrapper">
                    <label for="seovela_product_availability" class="seovela-input-label">
                        📦 <?php esc_html_e( 'Availability', 'seovela' ); ?>
                    </label>
                    <select id="seovela_product_availability" name="seovela_product_availability" class="seovela-select">
                        <option value="in_stock" <?php selected( $product_availability, 'in_stock' ); ?>>✅ <?php esc_html_e( 'In Stock', 'seovela' ); ?></option>
                        <option value="out_of_stock" <?php selected( $product_availability, 'out_of_stock' ); ?>>❌ <?php esc_html_e( 'Out of Stock', 'seovela' ); ?></option>
                        <option value="preorder" <?php selected( $product_availability, 'preorder' ); ?>>⏳ <?php esc_html_e( 'Pre-order', 'seovela' ); ?></option>
                        <option value="discontinued" <?php selected( $product_availability, 'discontinued' ); ?>>🚫 <?php esc_html_e( 'Discontinued', 'seovela' ); ?></option>
                    </select>
                </div>

                <!-- SKU & Brand -->
                <div class="seovela-product-grid">
                    <div class="seovela-input-wrapper">
                        <label for="seovela_product_sku" class="seovela-input-label">
                            🏷️ <?php esc_html_e( 'SKU', 'seovela' ); ?>
                            <span class="seovela-optional"><?php esc_html_e( '(optional)', 'seovela' ); ?></span>
                        </label>
                        <input 
                            type="text" 
                            id="seovela_product_sku" 
                            name="seovela_product_sku" 
                            value="<?php echo esc_attr( $product_sku ); ?>" 
                            placeholder="PROD-123"
                            class="seovela-input"
                        />
                    </div>
                    <div class="seovela-input-wrapper">
                        <label for="seovela_product_brand" class="seovela-input-label">
                            🏢 <?php esc_html_e( 'Brand', 'seovela' ); ?>
                            <span class="seovela-optional"><?php esc_html_e( '(optional)', 'seovela' ); ?></span>
                        </label>
                        <input 
                            type="text" 
                            id="seovela_product_brand" 
                            name="seovela_product_brand" 
                            value="<?php echo esc_attr( $product_brand ); ?>" 
                            placeholder="Brand Name"
                            class="seovela-input"
                        />
                    </div>
                </div>

                <!-- Rating & Reviews -->
                <div class="seovela-product-grid">
                    <div class="seovela-input-wrapper">
                        <label for="seovela_product_rating" class="seovela-input-label">
                            ⭐ <?php esc_html_e( 'Rating', 'seovela' ); ?>
                            <span class="seovela-optional"><?php esc_html_e( '(1-5)', 'seovela' ); ?></span>
                        </label>
                        <input 
                            type="number" 
                            id="seovela_product_rating" 
                            name="seovela_product_rating" 
                            value="<?php echo esc_attr( $product_rating ); ?>" 
                            step="0.1"
                            min="1"
                            max="5"
                            placeholder="4.5"
                            class="seovela-input"
                        />
                    </div>
                    <div class="seovela-input-wrapper">
                        <label for="seovela_product_review_count" class="seovela-input-label">
                            💬 <?php esc_html_e( 'Reviews', 'seovela' ); ?>
                            <span class="seovela-optional"><?php esc_html_e( '(count)', 'seovela' ); ?></span>
                        </label>
                        <input 
                            type="number" 
                            id="seovela_product_review_count" 
                            name="seovela_product_review_count" 
                            value="<?php echo esc_attr( $product_review_count ); ?>" 
                            placeholder="42"
                            class="seovela-input"
                        />
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Person schema fields
     *
     * @param WP_Post $post Post object
     */
    private function render_person_fields( $post ) {
        $person_type = get_post_meta( $post->ID, '_seovela_person_type', true );
        $person_job_title = get_post_meta( $post->ID, '_seovela_person_job_title', true );
        
        if ( empty( $person_type ) ) {
            $person_type = 'author';
        }
        ?>
        <div class="seovela-schema-accordion seovela-person-fields" data-schema="Person" style="display: none;">
            <div class="seovela-schema-accordion-header" data-target="person-content">
                <div class="seovela-schema-accordion-info">
                    <span class="seovela-schema-type-icon">👤</span>
                    <div class="seovela-schema-type-text">
                        <span class="seovela-schema-type-title"><?php esc_html_e( 'Person Schema', 'seovela' ); ?></span>
                        <span class="seovela-schema-type-desc"><?php esc_html_e( 'Author profiles for knowledge panels', 'seovela' ); ?></span>
                    </div>
                </div>
                <div class="seovela-schema-accordion-meta">
                    <span class="seovela-schema-count"><?php echo $person_type === 'author' ? esc_html__( 'Auto', 'seovela' ) : esc_html__( 'Custom', 'seovela' ); ?></span>
                    <span class="seovela-schema-accordion-chevron">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </span>
                </div>
            </div>
            <div class="seovela-schema-accordion-content" id="person-content">
                <!-- Person Type -->
                <div class="seovela-input-wrapper">
                    <label for="seovela_person_type" class="seovela-input-label">
                        🎭 <?php esc_html_e( 'Person Type', 'seovela' ); ?>
                    </label>
                    <select id="seovela_person_type" name="seovela_person_type" class="seovela-select">
                        <option value="author" <?php selected( $person_type, 'author' ); ?>>📝 <?php esc_html_e( 'Post Author (automatic)', 'seovela' ); ?></option>
                        <option value="custom" <?php selected( $person_type, 'custom' ); ?>>✏️ <?php esc_html_e( 'Custom Person', 'seovela' ); ?></option>
                    </select>
                    <span class="seovela-input-hint"><?php esc_html_e( 'Author type pulls data from the post author\'s profile automatically.', 'seovela' ); ?></span>
                </div>

                <!-- Job Title -->
                <div class="seovela-input-wrapper">
                    <label for="seovela_person_job_title" class="seovela-input-label">
                        💼 <?php esc_html_e( 'Job Title', 'seovela' ); ?>
                        <span class="seovela-optional"><?php esc_html_e( '(optional)', 'seovela' ); ?></span>
                    </label>
                    <input 
                        type="text" 
                        id="seovela_person_job_title" 
                        name="seovela_person_job_title" 
                        value="<?php echo esc_attr( $person_job_title ); ?>" 
                        placeholder="<?php esc_attr_e( 'e.g., CEO, Software Engineer, Content Writer', 'seovela' ); ?>"
                        class="seovela-input"
                    />
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Save meta box data
     *
     * @param int $post_id Post ID
     */
    public function save_meta_box( $post_id ) {
        // Check nonce
        if ( ! isset( $_POST['seovela_meta_box_nonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seovela_meta_box_nonce'] ) ), 'seovela_save_meta_box' ) ) {
            return;
        }

        // Check autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save focus keyword
        if ( isset( $_POST['seovela_focus_keyword'] ) ) {
            update_post_meta( $post_id, '_seovela_focus_keyword', sanitize_text_field( wp_unslash( $_POST['seovela_focus_keyword'] ) ) );
        }

        // Save meta title
        if ( isset( $_POST['seovela_meta_title'] ) ) {
            update_post_meta( $post_id, '_seovela_meta_title', sanitize_text_field( wp_unslash( $_POST['seovela_meta_title'] ) ) );
        }

        // Save meta description
        if ( isset( $_POST['seovela_meta_description'] ) ) {
            update_post_meta( $post_id, '_seovela_meta_description', sanitize_textarea_field( wp_unslash( $_POST['seovela_meta_description'] ) ) );
        }

        // Save noindex
        if ( isset( $_POST['seovela_noindex'] ) ) {
            update_post_meta( $post_id, '_seovela_noindex', true );
        } else {
            delete_post_meta( $post_id, '_seovela_noindex' );
        }

        // Save nofollow
        if ( isset( $_POST['seovela_nofollow'] ) ) {
            update_post_meta( $post_id, '_seovela_nofollow', true );
        } else {
            delete_post_meta( $post_id, '_seovela_nofollow' );
        }

        // Save schema settings
        $this->save_schema_data( $post_id );
    }

    /**
     * Save schema data
     *
     * @param int $post_id Post ID
     */
    private function save_schema_data( $post_id ) {
        // Disable schema
        if ( isset( $_POST['seovela_disable_schema'] ) ) {
            update_post_meta( $post_id, '_seovela_disable_schema', 'yes' );
        } else {
            delete_post_meta( $post_id, '_seovela_disable_schema' );
        }

        // Schema type
        if ( isset( $_POST['seovela_schema_type'] ) ) {
            update_post_meta( $post_id, '_seovela_schema_type', sanitize_text_field( wp_unslash( $_POST['seovela_schema_type'] ) ) );
        }

        // Additional schema types
        if ( isset( $_POST['seovela_schema_additional_types'] ) && is_array( $_POST['seovela_schema_additional_types'] ) ) {
            $additional_types = array_map( 'sanitize_text_field', wp_unslash( $_POST['seovela_schema_additional_types'] ) );
            update_post_meta( $post_id, '_seovela_schema_additional_types', $additional_types );
        } else {
            delete_post_meta( $post_id, '_seovela_schema_additional_types' );
        }

        // Save FAQ data
        if ( isset( $_POST['seovela_faq_auto_detect'] ) ) {
            update_post_meta( $post_id, '_seovela_faq_auto_detect', 'yes' );
        } else {
            delete_post_meta( $post_id, '_seovela_faq_auto_detect' );
        }

        if ( isset( $_POST['seovela_faq_items'] ) && is_array( $_POST['seovela_faq_items'] ) ) {
            $faq_items = array();
            $raw_faq_items = wp_unslash( $_POST['seovela_faq_items'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per-field below.
            foreach ( $raw_faq_items as $item ) {
                if ( ! empty( $item['question'] ) && ! empty( $item['answer'] ) ) {
                    $faq_items[] = array(
                        'question' => sanitize_text_field( $item['question'] ),
                        'answer'   => wp_kses_post( $item['answer'] ),
                    );
                }
            }
            update_post_meta( $post_id, '_seovela_faq_items', $faq_items );
        } else {
            delete_post_meta( $post_id, '_seovela_faq_items' );
        }

        // Save HowTo data
        if ( isset( $_POST['seovela_howto_total_time'] ) ) {
            update_post_meta( $post_id, '_seovela_howto_total_time', sanitize_text_field( wp_unslash( $_POST['seovela_howto_total_time'] ) ) );
        }

        if ( isset( $_POST['seovela_howto_steps'] ) && is_array( $_POST['seovela_howto_steps'] ) ) {
            $howto_steps = array();
            $raw_howto_steps = wp_unslash( $_POST['seovela_howto_steps'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per-field below.
            foreach ( $raw_howto_steps as $step ) {
                if ( ! empty( $step['text'] ) ) {
                    $howto_steps[] = array(
                        'name' => ! empty( $step['name'] ) ? sanitize_text_field( $step['name'] ) : '',
                        'text' => wp_kses_post( $step['text'] ),
                    );
                }
            }
            update_post_meta( $post_id, '_seovela_howto_steps', $howto_steps );
        } else {
            delete_post_meta( $post_id, '_seovela_howto_steps' );
        }

        // Save Product data
        if ( isset( $_POST['seovela_product_price'] ) ) {
            update_post_meta( $post_id, '_seovela_product_price', sanitize_text_field( wp_unslash( $_POST['seovela_product_price'] ) ) );
        }
        if ( isset( $_POST['seovela_product_currency'] ) ) {
            update_post_meta( $post_id, '_seovela_product_currency', sanitize_text_field( wp_unslash( $_POST['seovela_product_currency'] ) ) );
        }
        if ( isset( $_POST['seovela_product_availability'] ) ) {
            update_post_meta( $post_id, '_seovela_product_availability', sanitize_text_field( wp_unslash( $_POST['seovela_product_availability'] ) ) );
        }
        if ( isset( $_POST['seovela_product_sku'] ) ) {
            update_post_meta( $post_id, '_seovela_product_sku', sanitize_text_field( wp_unslash( $_POST['seovela_product_sku'] ) ) );
        }
        if ( isset( $_POST['seovela_product_brand'] ) ) {
            update_post_meta( $post_id, '_seovela_product_brand', sanitize_text_field( wp_unslash( $_POST['seovela_product_brand'] ) ) );
        }
        if ( isset( $_POST['seovela_product_rating'] ) ) {
            update_post_meta( $post_id, '_seovela_product_rating', sanitize_text_field( wp_unslash( $_POST['seovela_product_rating'] ) ) );
        }
        if ( isset( $_POST['seovela_product_review_count'] ) ) {
            update_post_meta( $post_id, '_seovela_product_review_count', sanitize_text_field( wp_unslash( $_POST['seovela_product_review_count'] ) ) );
        }

        // Save Person data
        if ( isset( $_POST['seovela_person_type'] ) ) {
            update_post_meta( $post_id, '_seovela_person_type', sanitize_text_field( wp_unslash( $_POST['seovela_person_type'] ) ) );
        }
        if ( isset( $_POST['seovela_person_job_title'] ) ) {
            update_post_meta( $post_id, '_seovela_person_job_title', sanitize_text_field( wp_unslash( $_POST['seovela_person_job_title'] ) ) );
        }
    }
}

