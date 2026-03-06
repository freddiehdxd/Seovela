<?php
/**
 * Seovela Core Class
 *
 * Main plugin class that initializes all components
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Seovela Core Class
 */
class Seovela_Core {

    /**
     * Single instance of the class
     *
     * @var Seovela_Core
     */
    protected static $instance = null;

    /**
     * Module loader instance
     *
     * @var Seovela_Module_Loader
     */
    public $module_loader;

    /**
     * Admin instance
     *
     * @var Seovela_Admin
     */
    public $admin;

    /**
     * Main Seovela Instance
     *
     * Ensures only one instance of Seovela is loaded or can be loaded
     *
     * @return Seovela_Core
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required core files
     */
    private function includes() {
        // Core classes
        require_once SEOVELA_PLUGIN_DIR . 'includes/class-seovela-cache.php';
        require_once SEOVELA_PLUGIN_DIR . 'includes/class-seovela-helpers.php';
        require_once SEOVELA_PLUGIN_DIR . 'includes/class-seovela-module-loader.php';
        require_once SEOVELA_PLUGIN_DIR . 'includes/class-seovela-conflict-detector.php';

        // Frontend classes
        if ( ! is_admin() ) {
            require_once SEOVELA_PLUGIN_DIR . 'includes/class-seovela-frontend.php';
        }

        // AI module - load early for both admin and REST API requests
        // This is lightweight (only registers hooks) and needed for REST streaming endpoint
        require_once SEOVELA_PLUGIN_DIR . 'modules/ai/class-seovela-ai.php';

        // Admin classes
        if ( is_admin() ) {
            require_once SEOVELA_PLUGIN_DIR . 'admin/class-seovela-admin.php';
            require_once SEOVELA_PLUGIN_DIR . 'admin/class-seovela-settings.php';
            require_once SEOVELA_PLUGIN_DIR . 'admin/class-seovela-metabox.php';
            require_once SEOVELA_PLUGIN_DIR . 'includes/class-seovela-ajax.php';
            require_once SEOVELA_PLUGIN_DIR . 'admin/class-seovela-ai-editor.php';
            require_once SEOVELA_PLUGIN_DIR . 'modules/import-export/class-seovela-import-export.php';
        }

        // Load Technical SEO features (both frontend and admin)
        require_once SEOVELA_PLUGIN_DIR . 'modules/redirects/class-seovela-redirects.php';
        require_once SEOVELA_PLUGIN_DIR . 'modules/404-monitor/class-seovela-404-monitor.php';
        
        // Load Technical SEO admin (admin only)
        if ( is_admin() ) {
            require_once SEOVELA_PLUGIN_DIR . 'admin/class-seovela-technical-seo.php';
        }

        // Load breadcrumbs (both frontend and admin)
        if ( file_exists( SEOVELA_PLUGIN_DIR . 'modules/breadcrumbs/breadcrumbs-init.php' ) ) {
            require_once SEOVELA_PLUGIN_DIR . 'modules/breadcrumbs/breadcrumbs-init.php';
        }
    }

    /**
     * Hook into actions and filters
     */
    private function init_hooks() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
        add_action( 'init', array( $this, 'load_textdomain' ) );
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'seovela', false, dirname( SEOVELA_PLUGIN_BASENAME ) . '/languages' );
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Initialize cache system
        Seovela_Cache::init();

        // Initialize module loader
        $this->module_loader = new Seovela_Module_Loader();

        // Initialize Technical SEO features (redirects & 404 monitor)
        // These need to run on both frontend and admin
        Seovela_Redirects::get_instance();
        Seovela_404_Monitor::get_instance();

        // Initialize admin
        if ( is_admin() ) {
            $this->admin = Seovela_Admin::get_instance();
            new Seovela_Ajax();
            Seovela_Technical_SEO_Admin::get_instance();
            new Seovela_Import_Export();
        }

        // Check for DB updates (ensure tables exist) - only in admin context
        if ( is_admin() ) {
            $this->check_db_updates();
        }

        // Initialize frontend
        if ( ! is_admin() ) {
            new Seovela_Frontend();
        }

        // Check for conflicts with other SEO plugins
        Seovela_Conflict_Detector::check_conflicts();
    }

    /**
     * Check if all features are available
     *
     * Since Seovela is now fully free and open-source, this always returns true.
     *
     * @return bool
     */
    public function is_pro_active() {
        return true;
    }

    /**
     * Check for DB updates and ensure tables exist
     */
    private function check_db_updates() {
        if ( ! get_option( 'seovela_tables_created_v120' ) ) {
             if ( ! class_exists( 'Seovela_Redirects' ) ) {
                 require_once SEOVELA_PLUGIN_DIR . 'modules/redirects/class-seovela-redirects.php';
             }
             if ( ! class_exists( 'Seovela_404_Monitor' ) ) {
                 require_once SEOVELA_PLUGIN_DIR . 'modules/404-monitor/class-seovela-404-monitor.php';
             }
             
             Seovela_Redirects::create_table();
             Seovela_404_Monitor::create_table();
             update_option( 'seovela_tables_created_v120', true );
        }
    }
}
