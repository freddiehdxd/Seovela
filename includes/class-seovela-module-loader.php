<?php
/**
 * Seovela Module Loader
 *
 * Manages loading and initialization of plugin modules
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Seovela Module Loader Class
 */
class Seovela_Module_Loader {

    /**
     * Loaded modules
     *
     * @var array
     */
    private $modules = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->load_modules();
    }

    /**
     * Load all enabled modules
     *
     * Modules that have no frontend output are only loaded in admin context
     * to reduce memory usage and file includes on visitor page loads.
     */
    private function load_modules() {
        // Modules with frontend hooks (always load when enabled)
        $this->load_module( 'meta', 'seovela_meta_enabled' );
        $this->load_module( 'sitemap', 'seovela_sitemap_enabled' );
        $this->load_module( 'schema', 'seovela_schema_enabled' );
        $this->load_module( 'llms-txt', 'seovela_llms_txt_enabled' );

        // Redirects + 404 are loaded separately in class-seovela-core.php
        // (they're registered here for status tracking only in admin)
        if ( is_admin() ) {
            $this->load_module( 'redirects', 'seovela_redirects_enabled' );
            $this->load_module( '404-monitor', 'seovela_404_monitor_enabled' );
        }

        // Admin-only modules (no frontend hooks, only dashboards/settings)
        if ( is_admin() ) {
            $this->load_module( 'optimizer', 'seovela_optimizer_enabled' );
            $this->load_module( 'internal-links', 'seovela_internal_links_enabled' );
            $this->load_module( 'image-seo', 'seovela_image_seo_enabled' );
            $this->load_module( 'gsc-integration', 'seovela_gsc_integration_enabled' );
            $this->load_module( 'ai', 'seovela_ai_enabled' );
        }
    }

    /**
     * Load individual module
     *
     * @param string $module_name Module name
     * @param string $option_key Option key to check if enabled
     */
    private function load_module( $module_name, $option_key ) {
        // Check if module is enabled (use cached options)
        if ( ! Seovela_Cache::get_option( $option_key, true ) ) {
            return;
        }

        // Build file path
        $file_path = SEOVELA_PLUGIN_DIR . 'modules/' . $module_name . '/class-seovela-' . $module_name . '.php';

        // Load module file
        if ( file_exists( $file_path ) ) {
            require_once $file_path;

            // Initialize module class
            // Convert module name to class name (e.g., '404-monitor' => 'Seovela_404_Monitor')
            $class_parts = explode( '-', $module_name );
            $class_parts = array_map( 'ucfirst', $class_parts );
            $class_name = 'Seovela_' . implode( '_', $class_parts );
            
            if ( class_exists( $class_name ) ) {
                // Use get_instance() if available (singleton pattern), otherwise instantiate directly
                if ( method_exists( $class_name, 'get_instance' ) ) {
                    $this->modules[ $module_name ] = call_user_func( array( $class_name, 'get_instance' ) );
                } else {
                    $this->modules[ $module_name ] = new $class_name();
                }
            }
        }
    }

    /**
     * Get loaded module
     *
     * @param string $module_name Module name
     * @return mixed|null
     */
    public function get_module( $module_name ) {
        return isset( $this->modules[ $module_name ] ) ? $this->modules[ $module_name ] : null;
    }

    /**
     * Check if module is loaded
     *
     * @param string $module_name Module name
     * @return bool
     */
    public function is_module_loaded( $module_name ) {
        return isset( $this->modules[ $module_name ] );
    }

    /**
     * Get all loaded modules
     *
     * @return array
     */
    public function get_loaded_modules() {
        return $this->modules;
    }

    /**
     * Get available modules with their info
     *
     * @return array
     */
    public static function get_available_modules() {
        return array(
            'meta' => array(
                'name'        => __( 'Meta Tags', 'seovela' ),
                'description' => __( 'Add custom meta titles and descriptions to posts and pages.', 'seovela' ),
                'is_pro'      => false,
                'option_key'  => 'seovela_meta_enabled',
            ),
            'sitemap' => array(
                'name'        => __( 'XML Sitemap', 'seovela' ),
                'description' => __( 'Auto-generate XML sitemap for search engines.', 'seovela' ),
                'is_pro'      => false,
                'option_key'  => 'seovela_sitemap_enabled',
            ),
            'schema' => array(
                'name'        => __( 'Schema Markup', 'seovela' ),
                'description' => __( 'Add structured data (JSON-LD) to your content.', 'seovela' ),
                'is_pro'      => false,
                'option_key'  => 'seovela_schema_enabled',
            ),
            'optimizer' => array(
                'name'        => __( 'SEO Optimizer', 'seovela' ),
                'description' => __( 'Basic SEO analytics and optimization tracking.', 'seovela' ),
                'is_pro'      => false,
                'option_key'  => 'seovela_optimizer_enabled',
            ),
            'redirects' => array(
                'name'        => __( 'Redirects Manager', 'seovela' ),
                'description' => __( 'Manage 301, 302, and 307 redirects with hit tracking.', 'seovela' ),
                'is_pro'      => false,
                'option_key'  => 'seovela_redirects_enabled',
            ),
            '404-monitor' => array(
                'name'        => __( '404 Monitor', 'seovela' ),
                'description' => __( 'Track and fix 404 errors with automatic logging.', 'seovela' ),
                'is_pro'      => false,
                'option_key'  => 'seovela_404_monitor_enabled',
            ),
            'internal-links' => array(
                'name'        => __( 'Internal Links', 'seovela' ),
                'description' => __( 'Smart internal linking suggestions based on content relevance.', 'seovela' ),
                'is_pro'      => false,
                'option_key'  => 'seovela_internal_links_enabled',
            ),
            'image-seo' => array(
                'name'        => __( 'Image SEO', 'seovela' ),
                'description' => __( 'Scan and optimize images for SEO (alt text, file size, filenames).', 'seovela' ),
                'is_pro'      => false,
                'option_key'  => 'seovela_image_seo_enabled',
            ),
            'gsc-integration' => array(
                'name'        => __( 'Search Console', 'seovela' ),
                'description' => __( 'Connect with Google Search Console for performance insights.', 'seovela' ),
                'is_pro'      => false,
                'option_key'  => 'seovela_gsc_integration_enabled',
            ),
            'llms-txt' => array(
                'name'        => __( 'LLMS Txt', 'seovela' ),
                'description' => __( 'Serve a custom llms.txt file to guide AI models with your content.', 'seovela' ),
                'is_pro'      => false,
                'option_key'  => 'seovela_llms_txt_enabled',
            ),
            'ai' => array(
                'name'        => __( 'AI Optimization', 'seovela' ),
                'description' => __( 'AI-powered content optimization and suggestions.', 'seovela' ),
                'is_pro'      => false,
                'option_key'  => 'seovela_ai_enabled',
            ),
        );
    }
}

