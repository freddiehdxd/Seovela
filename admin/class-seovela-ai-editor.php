<?php
/**
 * Seovela AI Editor Integration
 *
 * Adds AI content assistant to Classic Editor (TinyMCE) and Block Editor (Gutenberg)
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Seovela AI Editor Class
 */
class Seovela_AI_Editor {

    /**
     * Instance
     *
     * @var Seovela_AI_Editor
     */
    private static $instance = null;



    /**
     * Get instance
     *
     * @return Seovela_AI_Editor
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Classic Editor (TinyMCE)
        add_action( 'admin_init', array( $this, 'setup_tinymce' ) );
        
        // Block Editor (Gutenberg)
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
        
        // Both editors - floating panel
        add_action( 'admin_footer', array( $this, 'render_ai_panel' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_editor_assets' ) );
    }

    /**
     * Check if AI is configured (has API key for selected provider)
     *
     * @return bool
     */
    private function is_ai_configured() {
        $provider = get_option( 'seovela_ai_provider', 'openai' );
        
        if ( $provider === 'openai' ) {
            return ! empty( Seovela_Helpers::decrypt( get_option( 'seovela_openai_api_key', '' ) ) );
        } elseif ( $provider === 'claude' ) {
            return ! empty( Seovela_Helpers::decrypt( get_option( 'seovela_claude_api_key', '' ) ) );
        } else {
            return ! empty( Seovela_Helpers::decrypt( get_option( 'seovela_gemini_api_key', '' ) ) );
        }
    }

    /**
     * Setup TinyMCE integration for Classic Editor
     */
    public function setup_tinymce() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        add_filter( 'mce_buttons', array( $this, 'add_tinymce_button' ) );
        add_filter( 'mce_external_plugins', array( $this, 'add_tinymce_plugin' ) );
    }

    /**
     * Add TinyMCE button
     *
     * @param array $buttons Existing buttons
     * @return array
     */
    public function add_tinymce_button( $buttons ) {
        $buttons[] = 'seovela_ai';
        return $buttons;
    }

    /**
     * Add TinyMCE plugin
     *
     * @param array $plugins Existing plugins
     * @return array
     */
    public function add_tinymce_plugin( $plugins ) {
        $plugins['seovela_ai'] = SEOVELA_PLUGIN_URL . 'assets/js/tinymce-ai-plugin.js';
        return $plugins;
    }

    /**
     * Enqueue Block Editor assets
     */
    public function enqueue_block_editor_assets() {
        wp_enqueue_script(
            'seovela-ai-block-editor',
            SEOVELA_PLUGIN_URL . 'assets/js/block-editor-ai.js',
            array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-compose', 'wp-i18n' ),
            SEOVELA_VERSION,
            true
        );

        wp_localize_script( 'seovela-ai-block-editor', 'seovelaAI', array(
            'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'seovela_ai_nonce' ),
            'restUrl'      => esc_url_raw( rest_url() ),
            'restNonce'    => wp_create_nonce( 'wp_rest' ),
            'isConfigured' => $this->is_ai_configured(),
            'isPro'        => true,
            'upgradeUrl'   => admin_url( 'admin.php?page=seovela-settings&tab=ai' ),
            'settingsUrl'  => admin_url( 'admin.php?page=seovela-settings&tab=ai' ),
            'i18n'         => array(
                'title'           => __( 'AI Content Assistant', 'seovela' ),
                'improve'         => __( 'Improve', 'seovela' ),
                'write'           => __( 'Write', 'seovela' ),
                'improveReadability' => __( 'Improve Readability', 'seovela' ),
                'expandContent'   => __( 'Expand Content', 'seovela' ),
                'seoOptimize'     => __( 'SEO Optimize', 'seovela' ),
                'simplify'        => __( 'Simplify', 'seovela' ),
                'shorten'         => __( 'Shorten', 'seovela' ),
                'topic'           => __( 'Topic', 'seovela' ),
                'topicPlaceholder' => __( 'e.g., How to improve website SEO', 'seovela' ),
                'contentType'     => __( 'Content Type', 'seovela' ),
                'article'         => __( 'Article', 'seovela' ),
                'listicle'        => __( 'Listicle', 'seovela' ),
                'howTo'           => __( 'How-To Guide', 'seovela' ),
                'comparison'      => __( 'Comparison', 'seovela' ),
                'review'          => __( 'Review', 'seovela' ),
                'tone'            => __( 'Tone', 'seovela' ),
                'professional'    => __( 'Professional', 'seovela' ),
                'casual'          => __( 'Casual', 'seovela' ),
                'friendly'        => __( 'Friendly', 'seovela' ),
                'formal'          => __( 'Formal', 'seovela' ),
                'generate'        => __( 'Generate Content', 'seovela' ),
                'generating'      => __( 'Generating...', 'seovela' ),
                'processing'      => __( 'Processing...', 'seovela' ),
                'insert'          => __( 'Insert', 'seovela' ),
                'replace'         => __( 'Replace All', 'seovela' ),
                'discard'         => __( 'Discard', 'seovela' ),
                'noContent'       => __( 'Please add some content first.', 'seovela' ),
                'enterTopic'      => __( 'Please enter a topic to write about.', 'seovela' ),
                'error'           => __( 'Error connecting to AI service.', 'seovela' ),
                'configureAI'      => __( 'Configure AI', 'seovela' ),
                'aiNotConfigured'  => __( 'AI Not Configured', 'seovela' ),
                'aiNotConfiguredDesc' => __( 'Please configure your AI API key in settings to use this feature.', 'seovela' ),
            ),
        ) );

        wp_enqueue_style(
            'seovela-ai-block-editor',
            SEOVELA_PLUGIN_URL . 'assets/css/block-editor-ai.css',
            array(),
            SEOVELA_VERSION
        );
    }

    /**
     * Enqueue editor assets for both editors
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_editor_assets( $hook ) {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
            return;
        }

        // Check if using Classic Editor
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Detecting editor type, no state change.
        $is_classic = ! function_exists( 'use_block_editor_for_post' ) || 
                      ( isset( $_GET['classic-editor'] ) ) ||
                      ( function_exists( 'use_block_editor_for_post' ) && ! use_block_editor_for_post( get_post() ) );

        if ( $is_classic ) {
            wp_enqueue_style(
                'seovela-ai-editor',
                SEOVELA_PLUGIN_URL . 'assets/css/ai-editor.css',
                array(),
                SEOVELA_VERSION
            );

            wp_enqueue_script(
                'seovela-ai-editor',
                SEOVELA_PLUGIN_URL . 'assets/js/ai-editor.js',
                array( 'jquery' ),
                SEOVELA_VERSION,
                true
            );

            wp_localize_script( 'seovela-ai-editor', 'seovelaAI', array(
                'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
                'nonce'        => wp_create_nonce( 'seovela_ai_nonce' ),
                'restUrl'      => esc_url_raw( rest_url() ),
                'restNonce'    => wp_create_nonce( 'wp_rest' ),
                'isConfigured' => $this->is_ai_configured(),
                'isPro'        => true,
                'upgradeUrl'   => admin_url( 'admin.php?page=seovela-settings&tab=ai' ),
                'settingsUrl'  => admin_url( 'admin.php?page=seovela-settings&tab=ai' ),
            ) );
        }
    }

    /**
     * Render AI panel HTML for Classic Editor
     */
    public function render_ai_panel() {
        $screen = get_current_screen();
        
        if ( ! $screen || $screen->base !== 'post' ) {
            return;
        }

        // Only render for Classic Editor
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Detecting editor type, no state change.
        $is_classic = ! function_exists( 'use_block_editor_for_post' ) || 
                      ( isset( $_GET['classic-editor'] ) ) ||
                      ( function_exists( 'use_block_editor_for_post' ) && ! use_block_editor_for_post( get_post() ) );

        if ( ! $is_classic ) {
            return;
        }

        // Determine which panel to render
        if ( ! $this->is_ai_configured() ) {
            $this->render_configure_ai_panel();
        } else {
            $this->render_full_ai_panel();
        }
    }

    /**
     * Render configure AI panel for Pro users without API key
     */
    private function render_configure_ai_panel() {
        ?>
        <!-- Seovela AI Floating Button -->
        <div id="seovela-ai-fab" class="seovela-ai-fab" title="<?php esc_attr_e( 'AI Content Assistant', 'seovela' ); ?>">
            <span class="dashicons dashicons-superhero-alt"></span>
        </div>

        <!-- Seovela AI Configure Panel -->
        <div id="seovela-ai-panel" class="seovela-ai-panel seovela-ai-panel-configure" style="display: none;">
            <div class="seovela-ai-panel-header">
                <h3>
                    <span class="dashicons dashicons-superhero-alt"></span>
                    <?php esc_html_e( 'AI Content Assistant', 'seovela' ); ?>
                </h3>
                <button type="button" class="seovela-ai-panel-close">&times;</button>
            </div>

            <div class="seovela-ai-panel-body seovela-ai-configure-body">
                <div class="seovela-ai-configure-icon">
                    <span class="dashicons dashicons-admin-settings"></span>
                </div>
                <h4><?php esc_html_e( 'AI Not Configured', 'seovela' ); ?></h4>
                <p><?php esc_html_e( 'Please configure your AI API key in settings to use this feature.', 'seovela' ); ?></p>
                
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=ai' ) ); ?>" class="button button-primary">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php esc_html_e( 'Configure AI', 'seovela' ); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Render full AI panel for Pro users with configured API key
     */
    private function render_full_ai_panel() {
        ?>
        <!-- Seovela AI Floating Button -->
        <div id="seovela-ai-fab" class="seovela-ai-fab" title="<?php esc_attr_e( 'AI Content Assistant', 'seovela' ); ?>">
            <span class="dashicons dashicons-superhero-alt"></span>
        </div>

        <!-- Seovela AI Panel -->
        <div id="seovela-ai-panel" class="seovela-ai-panel" style="display: none;">
            <div class="seovela-ai-panel-header">
                <h3>
                    <span class="dashicons dashicons-superhero-alt"></span>
                    <?php esc_html_e( 'AI Content Assistant', 'seovela' ); ?>
                </h3>
                <button type="button" class="seovela-ai-panel-close">&times;</button>
            </div>

            <div class="seovela-ai-panel-body">
                <!-- Tabs -->
                <div class="seovela-ai-panel-tabs">
                    <button type="button" class="seovela-ai-panel-tab active" data-tab="improve">
                        <span class="dashicons dashicons-edit"></span>
                        <?php esc_html_e( 'Improve', 'seovela' ); ?>
                    </button>
                    <button type="button" class="seovela-ai-panel-tab" data-tab="write">
                        <span class="dashicons dashicons-welcome-write-blog"></span>
                        <?php esc_html_e( 'Write', 'seovela' ); ?>
                    </button>
                </div>

                <!-- Improve Tab -->
                <div class="seovela-ai-panel-content active" data-tab="improve">
                    <p class="description"><?php esc_html_e( 'Select text in the editor or improve all content:', 'seovela' ); ?></p>
                    <div class="seovela-ai-panel-actions">
                        <button type="button" class="button seovela-ai-btn" data-action="improve">
                            <span class="dashicons dashicons-edit-large"></span>
                            <?php esc_html_e( 'Improve Readability', 'seovela' ); ?>
                        </button>
                        <button type="button" class="button seovela-ai-btn" data-action="expand">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php esc_html_e( 'Expand Content', 'seovela' ); ?>
                        </button>
                        <button type="button" class="button seovela-ai-btn" data-action="seo_optimize">
                            <span class="dashicons dashicons-chart-line"></span>
                            <?php esc_html_e( 'SEO Optimize', 'seovela' ); ?>
                        </button>
                        <button type="button" class="button seovela-ai-btn" data-action="simplify">
                            <span class="dashicons dashicons-editor-textcolor"></span>
                            <?php esc_html_e( 'Simplify', 'seovela' ); ?>
                        </button>
                        <button type="button" class="button seovela-ai-btn" data-action="shorten">
                            <span class="dashicons dashicons-editor-contract"></span>
                            <?php esc_html_e( 'Shorten', 'seovela' ); ?>
                        </button>
                    </div>
                </div>

                <!-- Write Tab -->
                <div class="seovela-ai-panel-content" data-tab="write">
                    <div class="seovela-ai-panel-field">
                        <label for="seovela-ai-topic"><?php esc_html_e( 'Topic', 'seovela' ); ?></label>
                        <input type="text" id="seovela-ai-topic" placeholder="<?php esc_attr_e( 'e.g., How to improve website SEO', 'seovela' ); ?>" />
                    </div>
                    <div class="seovela-ai-panel-row">
                        <div class="seovela-ai-panel-field">
                            <label for="seovela-ai-type"><?php esc_html_e( 'Type', 'seovela' ); ?></label>
                            <select id="seovela-ai-type">
                                <option value="article"><?php esc_html_e( 'Article', 'seovela' ); ?></option>
                                <option value="listicle"><?php esc_html_e( 'Listicle', 'seovela' ); ?></option>
                                <option value="how-to"><?php esc_html_e( 'How-To Guide', 'seovela' ); ?></option>
                                <option value="comparison"><?php esc_html_e( 'Comparison', 'seovela' ); ?></option>
                                <option value="review"><?php esc_html_e( 'Review', 'seovela' ); ?></option>
                            </select>
                        </div>
                        <div class="seovela-ai-panel-field">
                            <label for="seovela-ai-tone"><?php esc_html_e( 'Tone', 'seovela' ); ?></label>
                            <select id="seovela-ai-tone">
                                <option value="professional"><?php esc_html_e( 'Professional', 'seovela' ); ?></option>
                                <option value="casual"><?php esc_html_e( 'Casual', 'seovela' ); ?></option>
                                <option value="friendly"><?php esc_html_e( 'Friendly', 'seovela' ); ?></option>
                                <option value="formal"><?php esc_html_e( 'Formal', 'seovela' ); ?></option>
                            </select>
                        </div>
                    </div>
                    <button type="button" class="button button-primary seovela-ai-generate">
                        <span class="dashicons dashicons-superhero-alt"></span>
                        <?php esc_html_e( 'Generate Content', 'seovela' ); ?>
                    </button>
                </div>

                <!-- Output Preview -->
                <div class="seovela-ai-panel-output" style="display: none;">
                    <div class="seovela-ai-panel-output-header">
                        <strong><?php esc_html_e( 'Generated Content', 'seovela' ); ?></strong>
                    </div>
                    <div class="seovela-ai-panel-output-content"></div>
                    <div class="seovela-ai-panel-output-actions">
                        <button type="button" class="button button-primary seovela-ai-insert-content">
                            <span class="dashicons dashicons-yes"></span>
                            <?php esc_html_e( 'Insert', 'seovela' ); ?>
                        </button>
                        <button type="button" class="button seovela-ai-replace-content">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e( 'Replace All', 'seovela' ); ?>
                        </button>
                        <button type="button" class="button seovela-ai-discard-content">
                            <span class="dashicons dashicons-no"></span>
                            <?php esc_html_e( 'Discard', 'seovela' ); ?>
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <div class="seovela-ai-panel-loading" style="display: none;">
                    <div class="seovela-ai-spinner"></div>
                    <p><?php esc_html_e( 'AI is working...', 'seovela' ); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize
Seovela_AI_Editor::get_instance();

