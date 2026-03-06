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
        
        // API keys are stored encrypted — decrypt before checking
        switch ( $provider ) {
            case 'openai':
                $key = get_option( 'seovela_openai_api_key', '' );
                return ! empty( Seovela_Helpers::decrypt( $key ) );
            case 'gemini':
                $key = get_option( 'seovela_gemini_api_key', '' );
                return ! empty( Seovela_Helpers::decrypt( $key ) );
            case 'claude':
                $key = get_option( 'seovela_claude_api_key', '' );
                return ! empty( Seovela_Helpers::decrypt( $key ) );
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

        // Schema meta fields
        register_post_meta( '', '_seovela_schema_type', array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'string',
            'default'       => 'auto',
            'auth_callback' => function () {
                return current_user_can( 'edit_posts' );
            },
        ) );

        register_post_meta( '', '_seovela_disable_schema', array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'string',
            'default'       => '',
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

        // Collapse empty meta-boxes area that WordPress renders even when no metaboxes exist.
        wp_add_inline_script( 'seovela-gutenberg-sidebar', '
            (function() {
                if ( typeof wp !== "undefined" && wp.domReady ) {
                    wp.domReady( function() {
                        setTimeout( function() {
                            var area = document.querySelector( ".edit-post-meta-boxes-main" );
                            if ( ! area ) return;
                            var boxes = area.querySelectorAll( ".meta-box-sortables > .postbox" );
                            if ( boxes.length === 0 ) {
                                area.style.display = "none";
                            }
                        }, 300 );
                    } );
                }
            })();
        ' );

        // Check if AI is configured
        $ai_configured = $this->is_ai_configured();

        // Build schema types list for the sidebar.
        $schema_types = array();
        if ( class_exists( 'Seovela_Schema_Builder' ) || file_exists( SEOVELA_PLUGIN_DIR . 'modules/schema/class-schema-builder.php' ) ) {
            require_once SEOVELA_PLUGIN_DIR . 'modules/schema/class-schema-builder.php';
            Seovela_Schema_Builder::init();
            $raw_types = Seovela_Schema_Builder::get_available_types();
            foreach ( $raw_types as $key => $data ) {
                $schema_types[] = array(
                    'value' => $key,
                    'label' => $data['name'],
                );
            }
        }

        $localize_data = array(
            'aiEnabled'     => $ai_configured,
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'siteUrl'       => home_url( '/' ),
            'analysisNonce' => wp_create_nonce( 'seovela_metabox_nonce' ),
            'schemaTypes'   => $schema_types,
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

        // Detect block editor — skip the Classic metabox when Gutenberg is active
        // because the Gutenberg sidebar (gutenberg-sidebar.js) provides SEO controls.
        $is_block_editor = false;

        $post = get_post();
        if ( $post && function_exists( 'use_block_editor_for_post' ) ) {
            $is_block_editor = use_block_editor_for_post( $post );
        }

        if ( $is_block_editor ) {
            return;
        }

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

        // Skip metabox assets in the block editor — the Gutenberg sidebar handles SEO there.
        $post = get_post();
        if ( $post && function_exists( 'use_block_editor_for_post' ) && use_block_editor_for_post( $post ) ) {
            return;
        }

        // Required for the OG Image media uploader in the Social tab.
        wp_enqueue_media();

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
        $meta_title       = get_post_meta( $post->ID, '_seovela_meta_title', true );
        $meta_description = get_post_meta( $post->ID, '_seovela_meta_description', true );
        $focus_keyword    = get_post_meta( $post->ID, '_seovela_focus_keyword', true );
        $noindex          = get_post_meta( $post->ID, '_seovela_noindex', true );
        $nofollow         = get_post_meta( $post->ID, '_seovela_nofollow', true );

        // Social meta
        $og_title            = get_post_meta( $post->ID, '_seovela_og_title', true );
        $og_description      = get_post_meta( $post->ID, '_seovela_og_description', true );
        $og_image            = get_post_meta( $post->ID, '_seovela_og_image', true );
        $twitter_card        = get_post_meta( $post->ID, '_seovela_twitter_card', true ) ?: 'summary_large_image';
        $twitter_title       = get_post_meta( $post->ID, '_seovela_twitter_title', true );
        $twitter_description = get_post_meta( $post->ID, '_seovela_twitter_description', true );

        // Advanced meta
        $canonical_url = get_post_meta( $post->ID, '_seovela_canonical_url', true );
        $noarchive     = get_post_meta( $post->ID, '_seovela_noarchive', true );
        $nosnippet     = get_post_meta( $post->ID, '_seovela_nosnippet', true );
        $noimageindex   = get_post_meta( $post->ID, '_seovela_noimageindex', true );

        // Default values
        if ( empty( $meta_title ) ) {
            $meta_title = get_the_title( $post->ID );
        }
        if ( empty( $meta_description ) ) {
            $meta_description = wp_trim_words( $post->post_content, 20 );
        }

        // Load cached SEO score from post meta
        $cached_score = get_post_meta( $post->ID, '_seovela_seo_score', true );

        if ( ! empty( $cached_score ) && is_array( $cached_score ) ) {
            $analysis = $cached_score;
        } else {
            $analysis = array(
                'score'    => 0,
                'status'   => 'unknown',
                'errors'   => array(),
                'warnings' => array(),
                'good'     => array(),
            );
        }

        $score = $analysis['score'];

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
        $offset = $circumference - ( $score / 100 ) * $circumference;

        // Load status label helper if available
        $status_label = '';
        if ( $analysis['status'] !== 'unknown' ) {
            require_once SEOVELA_PLUGIN_DIR . 'modules/content-analysis/class-seo-scorer.php';
            $status_label = Seovela_SEO_Scorer::get_status_label( $analysis['status'] );
        } else {
            $status_label = __( 'Not analyzed', 'seovela' );
        }

        // Score color for tab badge
        $score_color = '#94a3b8';
        if ( $score >= 80 ) {
            $score_color = '#10b981';
        } elseif ( $score >= 50 ) {
            $score_color = '#f59e0b';
        } elseif ( $score > 0 ) {
            $score_color = '#ef4444';
        }

        $ai_configured = $this->is_ai_configured();
        ?>
        <div class="seovela-metabox">
            <style>
                /* Critical inline styles — full styles in metabox.css */
                .seovela-tabs-nav{display:flex;align-items:center;gap:0;background:#f8f9fa;border-bottom:2px solid #e5e7eb;padding:0 4px;overflow-x:auto}
                .seovela-tab-btn{display:inline-flex;align-items:center;gap:6px;padding:12px 16px;background:none;border:none;border-bottom:3px solid transparent;margin-bottom:-2px;cursor:pointer;font-size:13px;font-weight:500;color:#64748b;white-space:nowrap;transition:all .2s}
                .seovela-tab-btn:hover{color:#334155}
                .seovela-tab-btn.active{color:#6366f1;border-bottom-color:#6366f1;font-weight:600}
                .seovela-tab-btn svg{flex-shrink:0}
                .seovela-tab-score{margin-left:auto;display:flex;align-items:center;gap:4px;padding:4px 12px;font-size:13px;font-weight:600;color:#64748b;white-space:nowrap}
                .seovela-tab-score-number{font-size:16px;font-weight:700}
                .seovela-tab-panel{display:none;padding:24px;background:#fff}
                .seovela-tab-panel.active{display:block}
                .seovela-field-group{margin-bottom:24px}
                .seovela-field-group h4{margin:0 0 12px;font-size:14px;font-weight:600;color:#1e293b}
                .seovela-score-bg,.seovela-score-progress{fill:none;stroke-width:10}
                .seovela-score-text{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center}
                .seovela-score-circle{position:relative;width:100px;height:100px}
                .seovela-score-compact{display:flex;align-items:center;gap:20px;padding:16px;background:#f8f9fa;border-radius:10px;margin-bottom:20px}
                .seovela-score-compact .seovela-score-actions{display:flex;gap:8px;margin-top:8px}
                .seovela-robots-chips{display:flex;flex-wrap:wrap;gap:8px}
                .seovela-robot-chip{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:20px;cursor:pointer;font-size:13px;color:#475569;transition:all .2s;user-select:none}
                .seovela-robot-chip:hover{background:#e8ecf1}
                .seovela-robot-chip input[type="checkbox"]{display:none}
                .seovela-robot-chip.checked,.seovela-robot-chip:has(input:checked){background:#fef2f2;border-color:#fca5a5;color:#dc2626}
                .seovela-social-subtabs{display:flex;gap:0;margin-bottom:20px;background:#f1f5f9;border-radius:8px;padding:3px;overflow:hidden}
                .seovela-social-tab{flex:1;padding:10px 16px;border:none;background:none;cursor:pointer;font-size:13px;font-weight:500;color:#64748b;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;gap:6px;transition:all .2s}
                .seovela-social-tab.active[data-social="facebook"]{background:#1877f2;color:#fff}
                .seovela-social-tab.active[data-social="twitter"]{background:#14171a;color:#fff}
                .seovela-social-panel{display:none}
                .seovela-social-panel.active{display:block}
                .seovela-og-preview{background:#f0f2f5;border:1px solid #dddfe2;border-radius:8px;overflow:hidden;margin-bottom:20px}
                .seovela-og-preview-image{width:100%;height:160px;background:#e4e6ea;display:flex;align-items:center;justify-content:center;color:#8a8d91;font-size:13px}
                .seovela-og-preview-image img{width:100%;height:100%;object-fit:cover}
                .seovela-og-preview-body{padding:12px}
                .seovela-og-preview-domain{font-size:12px;color:#65676b;text-transform:uppercase;margin-bottom:4px}
                .seovela-og-preview-title{font-size:16px;font-weight:600;color:#1c1e21;line-height:1.3;margin-bottom:4px}
                .seovela-og-preview-desc{font-size:14px;color:#65676b;line-height:1.4}
                @media(max-width:782px){.seovela-tab-btn span{display:none}.seovela-tab-btn.active span{display:inline}}
            </style>

            <!-- Tab Navigation -->
            <div class="seovela-tabs-nav">
                <button type="button" class="seovela-tab-btn active" data-tab="general">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="10" cy="10" r="8"/>
                        <path d="M10 6v4l2.5 2.5"/>
                    </svg>
                    <span><?php esc_html_e( 'General', 'seovela' ); ?></span>
                </button>
                <button type="button" class="seovela-tab-btn" data-tab="advanced">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="10" cy="10" r="3"/>
                        <path d="M10 1.5v2M10 16.5v2M3.4 3.4l1.4 1.4M15.2 15.2l1.4 1.4M1.5 10h2M16.5 10h2M3.4 16.6l1.4-1.4M15.2 4.8l1.4-1.4"/>
                    </svg>
                    <span><?php esc_html_e( 'Advanced', 'seovela' ); ?></span>
                </button>
                <button type="button" class="seovela-tab-btn" data-tab="schema">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 2L2 6l8 4 8-4-8-4z"/>
                        <path d="M2 14l8 4 8-4"/>
                        <path d="M2 10l8 4 8-4"/>
                    </svg>
                    <span><?php esc_html_e( 'Schema', 'seovela' ); ?></span>
                </button>
                <button type="button" class="seovela-tab-btn" data-tab="social">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="15" cy="5" r="3"/>
                        <circle cx="5" cy="10" r="3"/>
                        <circle cx="15" cy="15" r="3"/>
                        <line x1="7.7" y1="8.8" x2="12.3" y2="6.2"/>
                        <line x1="7.7" y1="11.2" x2="12.3" y2="13.8"/>
                    </svg>
                    <span><?php esc_html_e( 'Social', 'seovela' ); ?></span>
                </button>
                <!-- SEO Score badge -->
                <div class="seovela-tab-score" data-score="<?php echo esc_attr( $score ); ?>" style="color: <?php echo esc_attr( $score_color ); ?>">
                    <span class="seovela-tab-score-number"><?php echo esc_html( $score ); ?></span>/100
                </div>
            </div>

            <!-- ==================== GENERAL TAB ==================== -->
            <div class="seovela-tab-panel active" data-panel="general">

                <!-- SEO Score Widget (compact) -->
                <div class="seovela-score-compact">
                    <div class="seovela-score-circle" data-score="<?php echo esc_attr( $score ); ?>" data-status="<?php echo esc_attr( $analysis['status'] ); ?>">
                        <svg class="seovela-score-svg" width="100" height="100" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                            <circle class="seovela-score-bg" cx="60" cy="60" r="54" fill="none" stroke="#e5e7eb" stroke-width="10"></circle>
                            <circle class="seovela-score-progress" cx="60" cy="60" r="54" fill="none" stroke="<?php echo esc_attr( $progress_color ); ?>" stroke-width="10" style="stroke-dasharray:339.29;stroke-dashoffset:<?php echo esc_attr( $offset ); ?>"></circle>
                        </svg>
                        <div class="seovela-score-text">
                            <span class="seovela-score-number"><?php echo esc_html( $score ); ?></span>
                            <span class="seovela-score-label"><?php echo esc_html( $status_label ); ?></span>
                        </div>
                    </div>
                    <div class="seovela-score-info">
                        <h3 style="margin:0 0 4px;font-size:15px;"><?php esc_html_e( 'SEO Score', 'seovela' ); ?></h3>
                        <p style="margin:0 0 8px;font-size:13px;color:#64748b;"><?php esc_html_e( 'Optimize your content for better search engine rankings', 'seovela' ); ?></p>
                        <div class="seovela-score-actions">
                            <button type="button" class="button seovela-refresh-analysis"><?php esc_html_e( 'Refresh Analysis', 'seovela' ); ?></button>
                            <button type="button" class="button seovela-toggle-analysis"><?php esc_html_e( 'View Analysis', 'seovela' ); ?></button>
                        </div>
                    </div>
                </div>

                <!-- Analysis Results (collapsible) -->
                <div class="seovela-analysis-results" style="display: none;">
                    <?php if ( $analysis['status'] === 'unknown' ) : ?>
                        <p class="seovela-no-analysis"><?php esc_html_e( 'No analysis available yet. Click "Refresh Analysis" to run the SEO check.', 'seovela' ); ?></p>
                    <?php else : ?>
                        <?php if ( ! empty( $analysis['errors'] ) ) : ?>
                            <div class="seovela-analysis-section seovela-errors">
                                <h4><?php esc_html_e( 'Errors', 'seovela' ); ?> (<?php echo esc_html( count( $analysis['errors'] ) ); ?>)</h4>
                                <ul>
                                    <?php foreach ( $analysis['errors'] as $error ) : ?>
                                        <li><?php echo esc_html( $error ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $analysis['warnings'] ) ) : ?>
                            <div class="seovela-analysis-section seovela-warnings">
                                <h4><?php esc_html_e( 'Warnings', 'seovela' ); ?> (<?php echo esc_html( count( $analysis['warnings'] ) ); ?>)</h4>
                                <ul>
                                    <?php foreach ( $analysis['warnings'] as $warning ) : ?>
                                        <li><?php echo esc_html( $warning ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $analysis['good'] ) ) : ?>
                            <div class="seovela-analysis-section seovela-good">
                                <h4><?php esc_html_e( 'Good', 'seovela' ); ?> (<?php echo esc_html( count( $analysis['good'] ) ); ?>)</h4>
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
                        <?php if ( $ai_configured ) : ?>
                            <button type="button" class="button seovela-suggest-keywords">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2v3M10 15v3M4.2 4.2l2.1 2.1M13.7 13.7l2.1 2.1M2 10h3M15 10h3M4.2 15.8l2.1-2.1M13.7 6.3l2.1-2.1"/><circle cx="10" cy="10" r="3"/></svg>
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
                    <div style="display:flex;gap:8px;align-items:flex-start;">
                        <div style="flex:1;">
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
                        </div>
                        <?php if ( $ai_configured ) : ?>
                            <button type="button" class="button seovela-ai-optimize" data-field="title" title="<?php esc_attr_e( 'Generate with AI', 'seovela' ); ?>">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2l1.5 4.5L16 8l-4.5 1.5L10 14l-1.5-4.5L4 8l4.5-1.5L10 2z"/></svg>
                                <?php esc_html_e( 'AI', 'seovela' ); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Meta Description -->
                <div class="seovela-field">
                    <label for="seovela_meta_description">
                        <strong><?php esc_html_e( 'Meta Description', 'seovela' ); ?></strong>
                    </label>
                    <div style="display:flex;gap:8px;align-items:flex-start;">
                        <div style="flex:1;">
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
                        </div>
                        <?php if ( $ai_configured ) : ?>
                            <button type="button" class="button seovela-ai-optimize" data-field="description" title="<?php esc_attr_e( 'Generate with AI', 'seovela' ); ?>">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2l1.5 4.5L16 8l-4.5 1.5L10 14l-1.5-4.5L4 8l4.5-1.5L10 2z"/></svg>
                                <?php esc_html_e( 'AI', 'seovela' ); ?>
                            </button>
                        <?php endif; ?>
                    </div>
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
            </div>

            <!-- ==================== ADVANCED TAB ==================== -->
            <div class="seovela-tab-panel" data-panel="advanced">

                <!-- Robots Meta -->
                <div class="seovela-field-group">
                    <h4><?php esc_html_e( 'Robots Meta', 'seovela' ); ?></h4>
                    <p class="description" style="margin-bottom:12px;"><?php esc_html_e( 'Control how search engines index and follow this page.', 'seovela' ); ?></p>
                    <div class="seovela-robots-chips">
                        <label class="seovela-robot-chip <?php echo $noindex ? 'checked' : ''; ?>">
                            <input type="checkbox" name="seovela_noindex" value="1" <?php checked( $noindex, true ); ?> />
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="8"/><line x1="6" y1="6" x2="14" y2="14"/></svg>
                            <?php esc_html_e( 'Noindex', 'seovela' ); ?>
                        </label>
                        <label class="seovela-robot-chip <?php echo $nofollow ? 'checked' : ''; ?>">
                            <input type="checkbox" name="seovela_nofollow" value="1" <?php checked( $nofollow, true ); ?> />
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 6l-8 8"/><path d="M4 10V4h6"/></svg>
                            <?php esc_html_e( 'Nofollow', 'seovela' ); ?>
                        </label>
                        <label class="seovela-robot-chip <?php echo $noarchive ? 'checked' : ''; ?>">
                            <input type="checkbox" name="seovela_noarchive" value="1" <?php checked( $noarchive, true ); ?> />
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="14" height="14" rx="2"/><path d="M3 8h14"/><path d="M8 8v9"/></svg>
                            <?php esc_html_e( 'Noarchive', 'seovela' ); ?>
                        </label>
                        <label class="seovela-robot-chip <?php echo $nosnippet ? 'checked' : ''; ?>">
                            <input type="checkbox" name="seovela_nosnippet" value="1" <?php checked( $nosnippet, true ); ?> />
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h12"/><path d="M4 10h8"/><path d="M4 15h5"/></svg>
                            <?php esc_html_e( 'Nosnippet', 'seovela' ); ?>
                        </label>
                        <label class="seovela-robot-chip <?php echo $noimageindex ? 'checked' : ''; ?>">
                            <input type="checkbox" name="seovela_noimageindex" value="1" <?php checked( $noimageindex, true ); ?> />
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="14" height="14" rx="2"/><circle cx="8" cy="8" r="2"/><path d="M17 13l-4-4-10 10"/></svg>
                            <?php esc_html_e( 'Noimageindex', 'seovela' ); ?>
                        </label>
                    </div>
                </div>

                <!-- Canonical URL -->
                <div class="seovela-field-group">
                    <h4><?php esc_html_e( 'Canonical URL', 'seovela' ); ?></h4>
                    <input
                        type="url"
                        id="seovela_canonical_url"
                        name="seovela_canonical_url"
                        value="<?php echo esc_attr( $canonical_url ); ?>"
                        class="widefat"
                        placeholder="<?php echo esc_attr( get_permalink( $post->ID ) ); ?>"
                    />
                    <p class="description"><?php esc_html_e( 'Override the default canonical URL for this page. Leave empty to use the current permalink.', 'seovela' ); ?></p>
                </div>
            </div>

            <!-- ==================== SCHEMA TAB ==================== -->
            <div class="seovela-tab-panel" data-panel="schema">
                <?php $this->render_schema_selector( $post ); ?>
            </div>

            <!-- ==================== SOCIAL TAB ==================== -->
            <div class="seovela-tab-panel" data-panel="social">

                <!-- Social sub-tabs -->
                <div class="seovela-social-subtabs">
                    <button type="button" class="seovela-social-tab active" data-social="facebook">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path d="M18 10a8 8 0 10-9.25 7.9v-5.59H6.74V10h2.01V8.12c0-1.99 1.19-3.09 3-3.09.87 0 1.78.16 1.78.16v1.96h-1a1.15 1.15 0 00-1.3 1.24V10h2.2l-.35 2.31h-1.85v5.59A8 8 0 0018 10z"/></svg>
                        <?php esc_html_e( 'Facebook', 'seovela' ); ?>
                    </button>
                    <button type="button" class="seovela-social-tab" data-social="twitter">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path d="M11.2 8.87L16.77 2.5h-1.32l-4.83 5.53L6.68 2.5H2.5l5.84 8.38L2.5 17.5h1.32l5.11-5.85 4.08 5.85H17.5l-6.06-8.7-.24.02zm-1.81 2.07l-.59-.83L4.4 3.5h2.03l3.8 5.36.59.83 4.94 6.96h-2.03l-4.03-5.71h-.01z"/></svg>
                        <?php esc_html_e( 'Twitter / X', 'seovela' ); ?>
                    </button>
                </div>

                <!-- Facebook Panel -->
                <div class="seovela-social-panel active" data-social-panel="facebook">

                    <!-- OG Live Preview -->
                    <div class="seovela-og-preview">
                        <div class="seovela-og-preview-image">
                            <?php if ( ! empty( $og_image ) ) : ?>
                                <img src="<?php echo esc_url( $og_image ); ?>" alt="" />
                            <?php else : ?>
                                <?php esc_html_e( 'No image set', 'seovela' ); ?>
                            <?php endif; ?>
                        </div>
                        <div class="seovela-og-preview-body">
                            <div class="seovela-og-preview-domain"><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></div>
                            <div class="seovela-og-preview-title" id="seovela-og-preview-title"><?php echo esc_html( ! empty( $og_title ) ? $og_title : $meta_title ); ?></div>
                            <div class="seovela-og-preview-desc" id="seovela-og-preview-desc"><?php echo esc_html( ! empty( $og_description ) ? $og_description : $meta_description ); ?></div>
                        </div>
                    </div>

                    <div class="seovela-field">
                        <label for="seovela_og_title"><strong><?php esc_html_e( 'OG Title', 'seovela' ); ?></strong></label>
                        <input
                            type="text"
                            id="seovela_og_title"
                            name="seovela_og_title"
                            value="<?php echo esc_attr( $og_title ); ?>"
                            class="widefat"
                            placeholder="<?php echo esc_attr( $meta_title ); ?>"
                        />
                        <p class="description"><?php esc_html_e( 'Leave empty to use the meta title.', 'seovela' ); ?></p>
                    </div>

                    <div class="seovela-field">
                        <label for="seovela_og_description"><strong><?php esc_html_e( 'OG Description', 'seovela' ); ?></strong></label>
                        <textarea
                            id="seovela_og_description"
                            name="seovela_og_description"
                            rows="3"
                            class="widefat"
                            placeholder="<?php echo esc_attr( $meta_description ); ?>"
                        ><?php echo esc_textarea( $og_description ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Leave empty to use the meta description.', 'seovela' ); ?></p>
                    </div>

                    <div class="seovela-field">
                        <label for="seovela_og_image"><strong><?php esc_html_e( 'OG Image URL', 'seovela' ); ?></strong></label>
                        <div style="display:flex;gap:8px;">
                            <input
                                type="url"
                                id="seovela_og_image"
                                name="seovela_og_image"
                                value="<?php echo esc_attr( $og_image ); ?>"
                                class="widefat"
                                placeholder="<?php esc_attr_e( 'https://example.com/image.jpg', 'seovela' ); ?>"
                            />
                            <button type="button" class="button seovela-upload-og-image">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 15l4-4 3 3 4-5 3 3"/><rect x="2" y="2" width="16" height="16" rx="2"/></svg>
                                <?php esc_html_e( 'Upload', 'seovela' ); ?>
                            </button>
                        </div>
                        <p class="description"><?php esc_html_e( 'Recommended: 1200x630 pixels.', 'seovela' ); ?></p>
                    </div>
                </div>

                <!-- Twitter Panel -->
                <div class="seovela-social-panel" data-social-panel="twitter">

                    <div class="seovela-field">
                        <label for="seovela_twitter_card"><strong><?php esc_html_e( 'Card Type', 'seovela' ); ?></strong></label>
                        <select id="seovela_twitter_card" name="seovela_twitter_card" class="widefat">
                            <option value="summary_large_image" <?php selected( $twitter_card, 'summary_large_image' ); ?>><?php esc_html_e( 'Summary with Large Image', 'seovela' ); ?></option>
                            <option value="summary" <?php selected( $twitter_card, 'summary' ); ?>><?php esc_html_e( 'Summary', 'seovela' ); ?></option>
                        </select>
                    </div>

                    <div class="seovela-field">
                        <label for="seovela_twitter_title"><strong><?php esc_html_e( 'Twitter Title', 'seovela' ); ?></strong></label>
                        <input
                            type="text"
                            id="seovela_twitter_title"
                            name="seovela_twitter_title"
                            value="<?php echo esc_attr( $twitter_title ); ?>"
                            class="widefat"
                            placeholder="<?php echo esc_attr( $meta_title ); ?>"
                        />
                        <p class="description"><?php esc_html_e( 'Leave empty to use the OG title or meta title.', 'seovela' ); ?></p>
                    </div>

                    <div class="seovela-field">
                        <label for="seovela_twitter_description"><strong><?php esc_html_e( 'Twitter Description', 'seovela' ); ?></strong></label>
                        <textarea
                            id="seovela_twitter_description"
                            name="seovela_twitter_description"
                            rows="3"
                            class="widefat"
                            placeholder="<?php echo esc_attr( $meta_description ); ?>"
                        ><?php echo esc_textarea( $twitter_description ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Leave empty to use the OG description or meta description.', 'seovela' ); ?></p>
                    </div>
                </div>
            </div>

        </div>

        <script>
        (function(){
            /* Tab switching */
            document.querySelectorAll('.seovela-tab-btn').forEach(function(btn){
                btn.addEventListener('click', function(){
                    var tab = this.getAttribute('data-tab');
                    this.closest('.seovela-metabox').querySelectorAll('.seovela-tab-btn').forEach(function(b){b.classList.remove('active');});
                    this.classList.add('active');
                    this.closest('.seovela-metabox').querySelectorAll('.seovela-tab-panel').forEach(function(p){
                        p.classList.toggle('active', p.getAttribute('data-panel') === tab);
                    });
                });
            });

            /* Social sub-tab switching */
            document.querySelectorAll('.seovela-social-tab').forEach(function(btn){
                btn.addEventListener('click', function(){
                    var social = this.getAttribute('data-social');
                    this.closest('.seovela-social-subtabs').querySelectorAll('.seovela-social-tab').forEach(function(b){b.classList.remove('active');});
                    this.classList.add('active');
                    this.closest('.seovela-tab-panel').querySelectorAll('.seovela-social-panel').forEach(function(p){
                        p.classList.toggle('active', p.getAttribute('data-social-panel') === social);
                    });
                });
            });

            /* Robot chip toggle visual */
            document.querySelectorAll('.seovela-robot-chip input[type="checkbox"]').forEach(function(cb){
                cb.addEventListener('change', function(){
                    this.closest('.seovela-robot-chip').classList.toggle('checked', this.checked);
                });
            });

        })();
        </script>
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

        // Save advanced robots meta
        $bool_fields = array( 'seovela_noarchive', 'seovela_nosnippet', 'seovela_noimageindex' );
        foreach ( $bool_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, true );
            } else {
                delete_post_meta( $post_id, '_' . $field );
            }
        }

        // Save canonical URL
        if ( isset( $_POST['seovela_canonical_url'] ) ) {
            $canonical = esc_url_raw( wp_unslash( $_POST['seovela_canonical_url'] ) );
            if ( ! empty( $canonical ) ) {
                update_post_meta( $post_id, '_seovela_canonical_url', $canonical );
            } else {
                delete_post_meta( $post_id, '_seovela_canonical_url' );
            }
        }

        // Save Social / OpenGraph fields
        $text_fields = array(
            'seovela_og_title',
            'seovela_og_description',
            'seovela_og_image',
            'seovela_twitter_title',
            'seovela_twitter_description',
        );
        foreach ( $text_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                $val = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
                if ( ! empty( $val ) ) {
                    update_post_meta( $post_id, '_' . $field, $val );
                } else {
                    delete_post_meta( $post_id, '_' . $field );
                }
            }
        }

        // Save Twitter card type
        if ( isset( $_POST['seovela_twitter_card'] ) ) {
            update_post_meta( $post_id, '_seovela_twitter_card', sanitize_text_field( wp_unslash( $_POST['seovela_twitter_card'] ) ) );
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

