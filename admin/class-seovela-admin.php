<?php
/**
 * Seovela Pro Admin Class
 *
 * Handles admin area functionality
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Seovela Admin Class
 */
class Seovela_Admin {

    /**
     * Single instance
     *
     * @var Seovela_Admin
     */
    private static $instance = null;

    /**
     * Hooks added flag
     *
     * @var bool
     */
    private static $hooks_added = false;

    /**
     * Custom SVG icon for the admin menu (base64 encoded)
     *
     * @var string
     */
    private $menu_icon = '';

    /**
     * Get singleton instance
     *
     * @return Seovela_Admin
     */
    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        // Prevent duplicate hooks
        if ( self::$hooks_added ) {
            return;
        }
        self::$hooks_added = true;

        $this->menu_icon = $this->get_menu_icon();

        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // Initialize settings and metabox
        new Seovela_Settings();
        new Seovela_Metabox();
    }

    /**
     * Get custom SVG icon for the top-level menu
     *
     * Clean, modern analytics/chart icon encoded as a data URI.
     *
     * @return string Base64-encoded SVG data URI
     */
    private function get_menu_icon() {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none">'
            . '<path d="M3 15V13L5 10L8 12L12 6L17 9V15H3Z" fill="currentColor" opacity="0.2"/>'
            . '<path d="M3 15L5 10L8 12L12 6L17 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
            . '<circle cx="5" cy="10" r="1.5" fill="currentColor"/>'
            . '<circle cx="8" cy="12" r="1.5" fill="currentColor"/>'
            . '<circle cx="12" cy="6" r="1.5" fill="currentColor"/>'
            . '<circle cx="17" cy="9" r="1.5" fill="currentColor"/>'
            . '<path d="M2 17H18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>'
            . '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode( $svg );
    }

    /**
     * Get unresolved 404 count for the menu badge
     *
     * Uses a short transient to avoid running a COUNT query on every admin page load.
     *
     * @return int
     */
    private function get_unresolved_404_count() {
        $count = get_transient( 'seovela_unresolved_404_count' );

        if ( false === $count ) {
            global $wpdb;
            $table = $wpdb->prefix . 'seovela_404_logs';

            // Only query if the table exists
            $table_exists = $wpdb->get_var(
                $wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
            );

            if ( $table_exists ) {
                $count = (int) $wpdb->get_var(
                    "SELECT COUNT(*) FROM {$table} WHERE resolved = 0"
                );
            } else {
                $count = 0;
            }

            // Cache for 5 minutes to keep the badge fresh without hammering the DB
            set_transient( 'seovela_unresolved_404_count', $count, 5 * MINUTE_IN_SECONDS );
        }

        return (int) $count;
    }

    /**
     * Add admin menu pages
     *
     * Menu structure follows a Configure -> Monitor -> Optimize workflow:
     *   1. Dashboard    — overview
     *   2. Settings     — tabbed SEO settings (meta, sitemap, schema, indexing, AI)
     *   3. Modules      — enable/disable modules
     *   4. Redirects    — manage URL redirects
     *   5. 404 Monitor  — unresolved 404 errors (with count badge)
     *   6. Tools        — Internal Links, Image SEO, Import/Export, LLMS Txt
     *   7. Search Console
     *   8. AI Optimization (only when AI module is enabled)
     */
    public function add_admin_menu() {
        // ── Top-level menu ─────────────────────────────────────────────
        add_menu_page(
            __( 'Seovela', 'seovela' ),
            __( 'Seovela', 'seovela' ),
            'manage_options',
            'seovela',
            array( $this, 'render_dashboard' ),
            $this->menu_icon,
            80
        );

        // ── 1. Dashboard (default submenu that replaces the auto-generated one) ──
        add_submenu_page(
            'seovela',
            __( 'Dashboard', 'seovela' ),
            __( 'Dashboard', 'seovela' ),
            'manage_options',
            'seovela',
            array( $this, 'render_dashboard' )
        );

        // ── 2. Settings (tabbed: meta, sitemap, schema, indexing, AI) ──
        add_submenu_page(
            'seovela',
            __( 'SEO Settings', 'seovela' ),
            __( 'Settings', 'seovela' ),
            'manage_options',
            'seovela-settings',
            array( $this, 'render_settings' )
        );

        // ── 3. Modules ──
        add_submenu_page(
            'seovela',
            __( 'Modules', 'seovela' ),
            __( 'Modules', 'seovela' ),
            'manage_options',
            'seovela-modules',
            array( $this, 'render_modules' )
        );

        // ── 4. Redirects (only if module enabled) ──
        if ( get_option( 'seovela_redirects_enabled', true ) ) {
            add_submenu_page(
                'seovela',
                __( 'Redirects', 'seovela' ),
                __( 'Redirects', 'seovela' ),
                'manage_options',
                'seovela-redirects',
                array( $this, 'render_redirects' )
            );
        }

        // ── 5. 404 Monitor (with unresolved count badge, only if module enabled) ──
        if ( get_option( 'seovela_404_monitor_enabled', true ) ) {
            $unresolved = $this->get_unresolved_404_count();
            $badge      = '';

            if ( $unresolved > 0 ) {
                $badge = sprintf(
                    ' <span class="awaiting-mod">%s</span>',
                    number_format_i18n( $unresolved )
                );
            }

            add_submenu_page(
                'seovela',
                __( '404 Monitor', 'seovela' ),
                /* translators: %s: unresolved 404 count badge HTML */
                sprintf( __( '404 Monitor%s', 'seovela' ), $badge ),
                'manage_options',
                'seovela-404-monitor',
                array( $this, 'render_404_monitor' )
            );
        }

        // ── 6. Tools (combines Import/Export, links to Internal Links & Image SEO) ──
        add_submenu_page(
            'seovela',
            __( 'Tools', 'seovela' ),
            __( 'Tools', 'seovela' ),
            'manage_options',
            'seovela-tools',
            array( $this, 'render_tools' )
        );

        // ── 7. Search Console (registered by GSC module at priority 20; placeholder
        //       only added here if the module didn't register it) ──
        // The GSC module's own add_menu_page() at priority 20 handles this.

        // ── 8. AI Optimization (only if AI module is enabled) ──
        if ( get_option( 'seovela_ai_enabled', true ) ) {
            add_submenu_page(
                'seovela',
                __( 'AI Optimization', 'seovela' ),
                __( 'AI Optimization', 'seovela' ),
                'manage_options',
                'seovela-ai',
                array( $this, 'render_ai' )
            );
        }

        // ── Hidden pages (accessible by URL but not shown in menu) ──
        // LLMS Txt — accessible from the Tools page
        if ( get_option( 'seovela_llms_txt_enabled', true ) ) {
            add_submenu_page(
                null, // Hidden from menu
                __( 'LLMS Txt', 'seovela' ),
                __( 'LLMS Txt', 'seovela' ),
                'manage_options',
                'seovela-llms-txt',
                array( $this, 'render_llms_txt' )
            );
        }

        // Import/Export — accessible from the Tools page
        add_submenu_page(
            null, // Hidden from menu
            __( 'Import/Export', 'seovela' ),
            __( 'Import/Export', 'seovela' ),
            'manage_options',
            'seovela-import-export',
            array( $this, 'render_import_export' )
        );

        // Internal Links — accessible from the Tools page
        add_submenu_page(
            null, // Hidden from menu
            __( 'Internal Links', 'seovela' ),
            __( 'Internal Links', 'seovela' ),
            'manage_options',
            'seovela-internal-links',
            array( $this, 'render_internal_links' )
        );

        // Image SEO — accessible from the Tools page
        add_submenu_page(
            null, // Hidden from menu
            __( 'Image SEO', 'seovela' ),
            __( 'Image SEO', 'seovela' ),
            'manage_options',
            'seovela-image-seo',
            array( $this, 'render_image_seo' )
        );
    }



    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_assets( $hook ) {
        // Check if we're on a Seovela page by checking the page parameter
        $current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

        // List of all Seovela page slugs
        $seovela_pages = array(
            'seovela',
            'seovela-settings',
            'seovela-modules',
            'seovela-redirects',
            'seovela-404-monitor',
            'seovela-tools',
            'seovela-llms-txt',
            'seovela-import-export',
            'seovela-image-seo',
            'seovela-internal-links',
            'seovela-gsc',
            'seovela-ai',
            'seovela-schema',
            'seovela-sitemap',
            'seovela-breadcrumbs',
        );

        // Check if we're on a Seovela page by hook or page parameter
        $is_seovela_page = in_array( $current_page, $seovela_pages, true )
            || strpos( $hook, 'seovela' ) !== false;

        if ( ! $is_seovela_page ) {
            return;
        }

        // Enqueue dashicons for icons
        wp_enqueue_style( 'dashicons' );

        // LLMS Txt page specific styles
        if ( 'seovela-llms-txt' === $current_page ) {
            wp_enqueue_style(
                'seovela-llms-txt',
                SEOVELA_PLUGIN_URL . 'modules/llms-txt/assets/css/llms-txt.css',
                array( 'dashicons' ),
                SEOVELA_VERSION
            );
        }

        // Enqueue admin styles
        wp_enqueue_style(
            'seovela-admin',
            SEOVELA_PLUGIN_URL . 'assets/css/admin.css',
            array( 'dashicons' ),
            SEOVELA_VERSION
        );

        // Enqueue dashboard-specific styles (on main dashboard page)
        if ( 'seovela' === $current_page ) {
            wp_enqueue_style(
                'seovela-dashboard',
                SEOVELA_PLUGIN_URL . 'assets/css/dashboard.css',
                array( 'seovela-admin' ),
                SEOVELA_VERSION
            );

            wp_enqueue_script(
                'seovela-dashboard',
                SEOVELA_PLUGIN_URL . 'assets/js/dashboard.js',
                array( 'jquery' ),
                SEOVELA_VERSION,
                true
            );
        }

        // Enqueue admin scripts
        wp_enqueue_script(
            'seovela-admin',
            SEOVELA_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            SEOVELA_VERSION,
            true
        );

        wp_localize_script(
            'seovela-admin',
            'seovelaAdmin',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'seovela_admin_nonce' ),
            )
        );

        // Enqueue Technical SEO assets (Redirects & 404 Monitor)
        if ( in_array( $current_page, array( 'seovela-redirects', 'seovela-404-monitor' ), true ) ) {
            wp_enqueue_style(
                'seovela-technical-seo',
                SEOVELA_PLUGIN_URL . 'assets/css/admin-technical-seo.css',
                array(),
                SEOVELA_VERSION
            );

            wp_enqueue_script(
                'seovela-technical-seo',
                SEOVELA_PLUGIN_URL . 'assets/js/admin-technical-seo.js',
                array( 'jquery' ),
                SEOVELA_VERSION,
                true
            );

            // Redirects Data
            if ( 'seovela-redirects' === $current_page ) {
                wp_localize_script(
                    'seovela-technical-seo',
                    'seovelaRedirects',
                    array(
                        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                        'nonce'   => wp_create_nonce( 'seovela_redirects' ),
                    )
                );
            }

            // 404 Monitor Data
            if ( 'seovela-404-monitor' === $current_page ) {
                wp_localize_script(
                    'seovela-technical-seo',
                    'seovela404Monitor',
                    array(
                        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                        'nonce'   => wp_create_nonce( 'seovela_404_monitor' ),
                    )
                );
            }
        }
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard() {
        include SEOVELA_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Render settings page
     */
    public function render_settings() {
        $allowed_tabs = array( 'meta', 'sitemap', 'schema', 'indexing', 'ai' );
        $active_tab   = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'meta'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab navigation.

        if ( ! in_array( $active_tab, $allowed_tabs, true ) ) {
            $active_tab = 'meta';
        }

        include SEOVELA_PLUGIN_DIR . 'admin/views/settings-' . $active_tab . '.php';
    }

    /**
     * Render modules page
     */
    public function render_modules() {
        include SEOVELA_PLUGIN_DIR . 'admin/views/modules.php';
    }

    /**
     * Render redirects page
     *
     * Delegates to the Technical SEO admin class which owns the redirect logic.
     */
    public function render_redirects() {
        $technical_seo = Seovela_Technical_SEO_Admin::get_instance();
        $technical_seo->render_redirects_page();
    }

    /**
     * Render 404 monitor page
     *
     * Delegates to the Technical SEO admin class which owns the 404 monitor logic.
     */
    public function render_404_monitor() {
        // Invalidate the cached count so the badge updates after viewing the page
        delete_transient( 'seovela_unresolved_404_count' );

        $technical_seo = Seovela_Technical_SEO_Admin::get_instance();
        $technical_seo->render_404_monitor_page();
    }

    /**
     * Render tools page
     *
     * Hub page providing access to Internal Links, Image SEO, Import/Export, and LLMS Txt.
     */
    public function render_tools() {
        $active_tool = isset( $_GET['tool'] ) ? sanitize_key( wp_unslash( $_GET['tool'] ) ) : 'overview';

        switch ( $active_tool ) {
            case 'import-export':
                include SEOVELA_PLUGIN_DIR . 'admin/views/import-export.php';
                break;

            default:
                $this->render_tools_overview();
                break;
        }
    }

    /**
     * Render tools overview page
     */
    private function render_tools_overview() {
        $tools = array();

        $tool_gradients = array(
            'dashicons-admin-links'    => 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)',
            'dashicons-format-image'   => 'linear-gradient(135deg, #f97316 0%, #ea580c 100%)',
            'dashicons-database-export' => 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)',
            'dashicons-media-text'     => 'linear-gradient(135deg, #14b8a6 0%, #0d9488 100%)',
        );

        if ( get_option( 'seovela_internal_links_enabled', true ) ) {
            $tools[] = array(
                'title'       => __( 'Internal Links', 'seovela' ),
                'description' => __( 'Analyze and optimize your internal linking structure for better SEO and user navigation.', 'seovela' ),
                'icon'        => 'dashicons-admin-links',
                'url'         => admin_url( 'admin.php?page=seovela-internal-links' ),
            );
        }

        if ( get_option( 'seovela_image_seo_enabled', true ) ) {
            $tools[] = array(
                'title'       => __( 'Image SEO', 'seovela' ),
                'description' => __( 'Optimize image alt text, titles, and filenames for better search engine visibility.', 'seovela' ),
                'icon'        => 'dashicons-format-image',
                'url'         => admin_url( 'admin.php?page=seovela-image-seo' ),
            );
        }

        $tools[] = array(
            'title'       => __( 'Import / Export', 'seovela' ),
            'description' => __( 'Export your SEO settings or import them from another site or SEO plugin.', 'seovela' ),
            'icon'        => 'dashicons-database-export',
            'url'         => admin_url( 'admin.php?page=seovela-import-export' ),
        );

        if ( get_option( 'seovela_llms_txt_enabled', true ) ) {
            $tools[] = array(
                'title'       => __( 'LLMS Txt', 'seovela' ),
                'description' => __( 'Configure your llms.txt file to control how large language models interact with your content.', 'seovela' ),
                'icon'        => 'dashicons-media-text',
                'url'         => admin_url( 'admin.php?page=seovela-llms-txt' ),
            );
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
                                <span class="current"><?php esc_html_e( 'Tools', 'seovela' ); ?></span>
                            </div>
                            <h1><?php esc_html_e( 'SEO Tools', 'seovela' ); ?></h1>
                            <p><?php esc_html_e( 'Powerful tools to optimize your internal links, images, and manage your SEO data.', 'seovela' ); ?></p>
                        </div>
                        <div class="seovela-page-header-stats">
                            <div class="seovela-header-stat">
                                <div class="seovela-header-stat-number"><?php echo esc_html( count( $tools ) ); ?></div>
                                <div class="seovela-header-stat-label"><?php esc_html_e( 'Available', 'seovela' ); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="seovela-page-body">
                <div class="seovela-tools-cards">
                    <?php foreach ( $tools as $tool ) :
                        $gradient = isset( $tool_gradients[ $tool['icon'] ] ) ? $tool_gradients[ $tool['icon'] ] : 'linear-gradient(135deg, #64748b 0%, #475569 100%)';
                    ?>
                        <div class="seovela-tool-card">
                            <div class="seovela-tool-card-body">
                                <div class="seovela-tool-card-icon" style="background: <?php echo esc_attr( $gradient ); ?>;">
                                    <span class="dashicons <?php echo esc_attr( $tool['icon'] ); ?>"></span>
                                </div>
                                <h3><?php echo esc_html( $tool['title'] ); ?></h3>
                                <p><?php echo esc_html( $tool['description'] ); ?></p>
                            </div>
                            <div class="seovela-tool-card-footer">
                                <a href="<?php echo esc_url( $tool['url'] ); ?>" class="button">
                                    <?php
                                    /* translators: %s: tool name */
                                    printf( esc_html__( 'Open %s', 'seovela' ), esc_html( $tool['title'] ) );
                                    ?>
                                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div><!-- .seovela-page-body -->
        </div><!-- .seovela-premium-page -->
        <?php
    }

    /**
     * Render AI Optimization page
     *
     * Renders the AI settings tab. Only the 'ai' tab is valid here.
     */
    public function render_ai() {
        $active_tab = 'ai';
        include SEOVELA_PLUGIN_DIR . 'admin/views/settings-' . $active_tab . '.php';
    }

    /**
     * Render LLMS Txt settings page
     */
    public function render_llms_txt() {
        include SEOVELA_PLUGIN_DIR . 'modules/llms-txt/views/settings-llms-txt.php';
    }

    /**
     * Render import/export page
     */
    public function render_import_export() {
        include SEOVELA_PLUGIN_DIR . 'admin/views/import-export.php';
    }

    /**
     * Render Internal Links page
     *
     * Loads the module on demand and delegates to its render method.
     */
    public function render_internal_links() {
        // Ensure the module class is loaded
        $file_path = SEOVELA_PLUGIN_DIR . 'modules/internal-links/class-seovela-internal-links.php';
        if ( ! class_exists( 'Seovela_Internal_Links' ) && file_exists( $file_path ) ) {
            require_once $file_path;
        }

        if ( class_exists( 'Seovela_Internal_Links' ) ) {
            $instance = Seovela_Internal_Links::get_instance();
            $instance->render_page();
        }
    }

    /**
     * Render Image SEO page
     *
     * Loads the module on demand and delegates to its render method.
     */
    public function render_image_seo() {
        // Ensure the module class is loaded
        $file_path = SEOVELA_PLUGIN_DIR . 'modules/image-seo/class-seovela-image-seo.php';
        if ( ! class_exists( 'Seovela_Image_Seo' ) && file_exists( $file_path ) ) {
            require_once $file_path;
        }

        if ( class_exists( 'Seovela_Image_Seo' ) ) {
            $instance = Seovela_Image_Seo::get_instance();
            $instance->render_page();
        }
    }
}
