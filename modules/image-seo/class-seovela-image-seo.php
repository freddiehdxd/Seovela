<?php
/**
 * Seovela Image SEO - Advanced Image Optimization
 *
 * Comprehensive image SEO management with WebP conversion,
 * smart variables, attribute management, and bulk operations.
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seovela Image SEO Class
 */
class Seovela_Image_Seo {

	/**
	 * Single instance
	 *
	 * @var Seovela_Image_Seo
	 */
	private static $instance = null;

	/**
	 * Database table name
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Available variables for dynamic content
	 *
	 * @var array
	 */
	private $variables = array();

	/**
	 * Casing options
	 *
	 * @var array
	 */
	private $casing_options = array(
		'none'      => 'No Change',
		'lower'     => 'lowercase',
		'upper'     => 'UPPERCASE',
		'title'     => 'Title Case',
		'sentence'  => 'Sentence case',
	);

	/**
	 * Get singleton instance
	 *
	 * @return Seovela_Image_Seo
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
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'seovela_image_seo_cache';

		// Ensure table exists (admin only — avoids DB query on every frontend load)
		if ( is_admin() ) {
			$this->ensure_table_exists();
		}

		$this->init_variables();

		// Admin hooks
		add_action( 'admin_menu', array( $this, 'add_menu_page' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// AJAX hooks
		add_action( 'wp_ajax_seovela_image_scan', array( $this, 'ajax_scan_images' ) );
		add_action( 'wp_ajax_seovela_image_update', array( $this, 'ajax_update_image' ) );
		add_action( 'wp_ajax_seovela_image_bulk_update', array( $this, 'ajax_bulk_update' ) );
		add_action( 'wp_ajax_seovela_image_convert_webp', array( $this, 'ajax_convert_webp' ) );
		add_action( 'wp_ajax_seovela_image_get_stats', array( $this, 'ajax_get_stats' ) );
		add_action( 'wp_ajax_seovela_image_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_seovela_image_apply_template', array( $this, 'ajax_apply_template' ) );
		add_action( 'wp_ajax_seovela_image_get_list', array( $this, 'ajax_get_image_list' ) );
		
		// Auto-process on upload
		add_action( 'add_attachment', array( $this, 'process_new_upload' ), 10, 1 );
		
		// Dashboard widget
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );

		// WebP serving hooks (frontend)
		$this->init_webp_serving();

		// Media Library integration
		add_filter( 'manage_media_columns', array( $this, 'add_media_columns' ) );
		add_action( 'manage_media_custom_column', array( $this, 'render_media_column' ), 10, 2 );
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_attachment_webp_field' ), 10, 2 );
		add_action( 'admin_footer-upload.php', array( $this, 'media_library_webp_script' ) );
		add_action( 'wp_ajax_seovela_convert_single_webp', array( $this, 'ajax_convert_single_from_media' ) );
	}

	/**
	 * Initialize WebP serving hooks
	 */
	private function init_webp_serving() {
		$settings = $this->get_settings();
		
		if ( empty( $settings['serve_webp'] ) ) {
			return;
		}

		// Only serve WebP if browser supports it
		if ( ! $this->browser_supports_webp() ) {
			return;
		}

		// PHP method - filter image URLs
		if ( $settings['webp_serving_method'] === 'php' ) {
			// Filter attachment image src
			add_filter( 'wp_get_attachment_image_src', array( $this, 'filter_attachment_image_src' ), 10, 4 );
			
			// Filter image srcset
			add_filter( 'wp_calculate_image_srcset', array( $this, 'filter_image_srcset' ), 10, 5 );
			
			// Filter the_content for inline images
			add_filter( 'the_content', array( $this, 'filter_content_images' ), 999 );
			
			// Filter get_attachment_url
			add_filter( 'wp_get_attachment_url', array( $this, 'filter_attachment_url' ), 10, 2 );
		}
	}

	/**
	 * Check if browser supports WebP
	 */
	private function browser_supports_webp() {
		// Check Accept header
		if ( isset( $_SERVER['HTTP_ACCEPT'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ), 'image/webp' ) !== false ) {
			return true;
		}
		
		// Check User-Agent for known WebP-supporting browsers
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$ua = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
			// Chrome, Firefox, Edge, Opera, Safari 14+
			if ( preg_match( '/Chrome|Firefox|Edge|OPR|Safari/i', $ua ) ) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Filter attachment image src to serve WebP
	 */
	public function filter_attachment_image_src( $image, $attachment_id, $size, $icon ) {
		if ( ! $image || empty( $image[0] ) ) {
			return $image;
		}

		$webp_url = $this->get_webp_url( $image[0] );
		if ( $webp_url ) {
			$image[0] = $webp_url;
		}

		return $image;
	}

	/**
	 * Filter image srcset to serve WebP
	 */
	public function filter_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		if ( empty( $sources ) ) {
			return $sources;
		}

		foreach ( $sources as $width => &$source ) {
			$webp_url = $this->get_webp_url( $source['url'] );
			if ( $webp_url ) {
				$source['url'] = $webp_url;
			}
		}

		return $sources;
	}

	/**
	 * Filter content images to serve WebP
	 */
	public function filter_content_images( $content ) {
		if ( empty( $content ) ) {
			return $content;
		}

		// Match img tags with src attributes
		$content = preg_replace_callback(
			'/<img[^>]+src=["\']([^"\']+\.(jpe?g|png|gif))["\'][^>]*>/i',
			array( $this, 'replace_image_src' ),
			$content
		);

		return $content;
	}

	/**
	 * Replace image src with WebP version
	 */
	private function replace_image_src( $matches ) {
		$img_tag = $matches[0];
		$original_url = $matches[1];
		
		$webp_url = $this->get_webp_url( $original_url );
		if ( $webp_url ) {
			$img_tag = str_replace( $original_url, $webp_url, $img_tag );
			
			// Also replace srcset URLs if present
			if ( preg_match( '/srcset=["\']([^"\']+)["\']/', $img_tag, $srcset_match ) ) {
				$srcset = $srcset_match[1];
				$new_srcset = preg_replace_callback(
					'/([^\s,]+\.(jpe?g|png|gif))(\s+\d+[wx])?/i',
					function( $m ) {
						$webp = $this->get_webp_url( $m[1] );
						return $webp ? $webp . ( $m[3] ?? '' ) : $m[0];
					},
					$srcset
				);
				$img_tag = str_replace( $srcset, $new_srcset, $img_tag );
			}
		}

		return $img_tag;
	}

	/**
	 * Filter attachment URL to serve WebP
	 */
	public function filter_attachment_url( $url, $attachment_id ) {
		// Only filter image URLs
		$mime_type = get_post_mime_type( $attachment_id );
		if ( ! in_array( $mime_type, array( 'image/jpeg', 'image/png', 'image/gif' ) ) ) {
			return $url;
		}

		$webp_url = $this->get_webp_url( $url );
		return $webp_url ? $webp_url : $url;
	}

	/**
	 * Get WebP URL for an image if it exists
	 */
	private function get_webp_url( $url ) {
		// Convert URL to file path
		$upload_dir = wp_upload_dir();
		$base_url = $upload_dir['baseurl'];
		$base_dir = $upload_dir['basedir'];

		// Check if URL is from uploads directory
		if ( strpos( $url, $base_url ) !== 0 ) {
			return false;
		}

		// Get relative path
		$relative_path = substr( $url, strlen( $base_url ) );
		$file_path = $base_dir . $relative_path;

		// Get WebP path
		$webp_path = preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $file_path );
		
		// Check if WebP file exists
		if ( file_exists( $webp_path ) ) {
			$webp_url = preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $url );
			return $webp_url;
		}

		return false;
	}

	/**
	 * Initialize available variables
	 */
	private function init_variables() {
		$this->variables = array(
			'file_name' => array(
				'label'       => __( 'File Name', 'seovela' ),
				'description' => __( 'File name of the attachment (without extension)', 'seovela' ),
				'category'    => 'image',
			),
			'file_name_raw' => array(
				'label'       => __( 'File Name (Raw)', 'seovela' ),
				'description' => __( 'Raw file name with dashes/underscores replaced by spaces', 'seovela' ),
				'category'    => 'image',
			),
			'image_alt' => array(
				'label'       => __( 'Image Alt', 'seovela' ),
				'description' => __( 'Current alt text of the image', 'seovela' ),
				'category'    => 'image',
			),
			'image_title' => array(
				'label'       => __( 'Image Title', 'seovela' ),
				'description' => __( 'Current title of the image', 'seovela' ),
				'category'    => 'image',
			),
			'image_caption' => array(
				'label'       => __( 'Image Caption', 'seovela' ),
				'description' => __( 'Current caption of the image', 'seovela' ),
				'category'    => 'image',
			),
			'post_title' => array(
				'label'       => __( 'Post Title', 'seovela' ),
				'description' => __( 'Title of the parent post/page', 'seovela' ),
				'category'    => 'post',
			),
			'post_excerpt' => array(
				'label'       => __( 'Post Excerpt', 'seovela' ),
				'description' => __( 'Excerpt of the parent post', 'seovela' ),
				'category'    => 'post',
			),
			'post_category' => array(
				'label'       => __( 'Post Category', 'seovela' ),
				'description' => __( 'First category of the parent post', 'seovela' ),
				'category'    => 'post',
			),
			'post_tags' => array(
				'label'       => __( 'Post Tags', 'seovela' ),
				'description' => __( 'Comma-separated tags of the parent post', 'seovela' ),
				'category'    => 'post',
			),
			'author_name' => array(
				'label'       => __( 'Author Name', 'seovela' ),
				'description' => __( 'Display name of the image author', 'seovela' ),
				'category'    => 'post',
			),
			'site_title' => array(
				'label'       => __( 'Site Title', 'seovela' ),
				'description' => __( 'Title of the website', 'seovela' ),
				'category'    => 'site',
			),
			'site_description' => array(
				'label'       => __( 'Site Description', 'seovela' ),
				'description' => __( 'Tagline of the website', 'seovela' ),
				'category'    => 'site',
			),
			'current_date' => array(
				'label'       => __( 'Current Date', 'seovela' ),
				'description' => __( 'Current date in site format', 'seovela' ),
				'category'    => 'date',
			),
			'current_year' => array(
				'label'       => __( 'Current Year', 'seovela' ),
				'description' => __( 'Current 4-digit year', 'seovela' ),
				'category'    => 'date',
			),
			'current_month' => array(
				'label'       => __( 'Current Month', 'seovela' ),
				'description' => __( 'Current month name', 'seovela' ),
				'category'    => 'date',
			),
			'upload_date' => array(
				'label'       => __( 'Upload Date', 'seovela' ),
				'description' => __( 'Date when image was uploaded', 'seovela' ),
				'category'    => 'date',
			),
			'counter' => array(
				'label'       => __( 'Counter', 'seovela' ),
				'description' => __( 'Auto-incrementing number starting at 1', 'seovela' ),
				'category'    => 'utility',
			),
			'separator' => array(
				'label'       => __( 'Separator', 'seovela' ),
				'description' => __( 'Separator character (configured in settings)', 'seovela' ),
				'category'    => 'utility',
			),
		);
	}

	/**
	 * Ensure database table exists and has correct schema
	 */
	private function ensure_table_exists() {
		global $wpdb;
		
		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$this->table_name
		) );
		
		if ( ! $table_exists ) {
			self::create_table();
		} else {
			// Check if schema needs updating (migration)
			$this->maybe_update_schema();
		}
	}

	/**
	 * Check and update database schema if needed
	 */
	private function maybe_update_schema() {
		global $wpdb;
		
		$schema_version = get_option( 'seovela_image_seo_schema_version', 0 );
		$current_version = 2; // Increment this when making schema changes
		
		if ( $schema_version >= $current_version ) {
			return;
		}
		
		// Get current columns
		$columns = $wpdb->get_col( "DESCRIBE {$this->table_name}", 0 ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is hardcoded plugin prefix.
		
		// Required columns with their definitions
		$required_columns = array(
			'file_path'       => "ADD COLUMN file_path varchar(500) NOT NULL DEFAULT '' AFTER file_name",
			'file_type'       => "ADD COLUMN file_type varchar(50) NOT NULL DEFAULT '' AFTER file_size",
			'width'           => "ADD COLUMN width int(11) DEFAULT 0 AFTER file_type",
			'height'          => "ADD COLUMN height int(11) DEFAULT 0 AFTER width",
			'has_description' => "ADD COLUMN has_description tinyint(1) NOT NULL DEFAULT 0 AFTER has_caption",
			'has_webp'        => "ADD COLUMN has_webp tinyint(1) NOT NULL DEFAULT 0 AFTER is_oversized",
			'webp_path'       => "ADD COLUMN webp_path varchar(500) DEFAULT NULL AFTER has_webp",
			'webp_size'       => "ADD COLUMN webp_size bigint(20) DEFAULT 0 AFTER webp_path",
			'suggested_title' => "ADD COLUMN suggested_title varchar(500) DEFAULT NULL AFTER suggested_alt",
			'parent_post_id'  => "ADD COLUMN parent_post_id bigint(20) DEFAULT NULL AFTER suggested_title",
		);
		
		// Add missing columns
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name and column definitions are hardcoded plugin values.
		foreach ( $required_columns as $column => $definition ) {
			if ( ! in_array( $column, $columns ) ) {
				$wpdb->query( "ALTER TABLE {$this->table_name} {$definition}" );
			}
		}
		
		// Add indexes if missing
		$indexes = $wpdb->get_results( "SHOW INDEX FROM {$this->table_name}", ARRAY_A );
		$index_columns = array_column( $indexes, 'Column_name' );
		
		if ( ! in_array( 'has_webp', $index_columns ) ) {
			$wpdb->query( "ALTER TABLE {$this->table_name} ADD INDEX has_webp (has_webp)" );
		}
		if ( ! in_array( 'file_type', $index_columns ) ) {
			$wpdb->query( "ALTER TABLE {$this->table_name} ADD INDEX file_type (file_type)" );
		}
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		
		update_option( 'seovela_image_seo_schema_version', $current_version );
	}

	/**
	 * Create database table
	 */
	public static function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'seovela_image_seo_cache';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			attachment_id bigint(20) NOT NULL,
			file_name varchar(255) NOT NULL,
			file_path varchar(500) NOT NULL,
			file_size bigint(20) NOT NULL,
			file_type varchar(50) NOT NULL,
			width int(11) DEFAULT 0,
			height int(11) DEFAULT 0,
			has_alt tinyint(1) NOT NULL DEFAULT 0,
			has_title tinyint(1) NOT NULL DEFAULT 0,
			has_caption tinyint(1) NOT NULL DEFAULT 0,
			has_description tinyint(1) NOT NULL DEFAULT 0,
			is_descriptive tinyint(1) NOT NULL DEFAULT 0,
			is_oversized tinyint(1) NOT NULL DEFAULT 0,
			has_webp tinyint(1) NOT NULL DEFAULT 0,
			webp_path varchar(500) DEFAULT NULL,
			webp_size bigint(20) DEFAULT 0,
			issues_count tinyint(2) NOT NULL DEFAULT 0,
			suggested_alt varchar(500) DEFAULT NULL,
			suggested_title varchar(500) DEFAULT NULL,
			parent_post_id bigint(20) DEFAULT NULL,
			last_scanned datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY attachment_id (attachment_id),
			KEY issues_count (issues_count),
			KEY is_oversized (is_oversized),
			KEY has_alt (has_alt),
			KEY has_webp (has_webp),
			KEY file_type (file_type)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Add admin menu page
	 *
	 * Note: The page is now registered centrally by Seovela_Admin with null parent
	 * (hidden from sidebar, accessible via Tools page). This method is kept as a
	 * no-op for backward compatibility with the admin_menu hook.
	 */
	public function add_menu_page() {
		// Page registration is handled by Seovela_Admin::add_admin_menu().
	}

	/**
	 * Enqueue admin scripts
	 */
	public function enqueue_scripts( $hook ) {
		// Check for Image SEO page or dashboard
		$is_image_seo_page = strpos( $hook, 'seovela-image-seo' ) !== false || strpos( $hook, 'seovela_page_seovela-image-seo' ) !== false;
		if ( ! $is_image_seo_page && 'index.php' !== $hook ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_script(
			'seovela-image-seo',
			plugin_dir_url( __FILE__ ) . 'assets/js/image-seo.js',
			array( 'jquery' ),
			filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/image-seo.js' ),
			true
		);

		wp_localize_script(
			'seovela-image-seo',
			'seovelaImageSeo',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'seovela_image_seo' ),
				'variables'    => $this->variables,
				'casingOptions' => $this->casing_options,
				'strings'      => array(
					'scanning'     => __( 'Scanning...', 'seovela' ),
					'converting'   => __( 'Converting...', 'seovela' ),
					'saving'       => __( 'Saving...', 'seovela' ),
					'success'      => __( 'Success!', 'seovela' ),
					'error'        => __( 'Error occurred', 'seovela' ),
					'confirm_bulk' => __( 'Apply changes to selected images?', 'seovela' ),
				),
			)
		);

		wp_enqueue_style(
			'seovela-image-seo',
			plugin_dir_url( __FILE__ ) . 'assets/css/image-seo.css',
			array(),
			filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/image-seo.css' )
		);
	}

	/**
	 * Get settings
	 */
	public function get_settings() {
		$defaults = array(
			'enabled'              => true,
			'auto_process_upload'  => true,
			'size_threshold'       => 200, // KB
			'separator'            => ' - ',
			// Alt text settings
			'alt_template'         => '%file_name_raw%',
			'alt_casing'           => 'title',
			'add_missing_alt'      => true,
			'overwrite_alt'        => false,
			// Title settings
			'title_template'       => '%file_name_raw%',
			'title_casing'         => 'title',
			'add_missing_title'    => true,
			'overwrite_title'      => false,
			// Caption settings
			'caption_template'     => '',
			'caption_casing'       => 'sentence',
			'add_missing_caption'  => false,
			'overwrite_caption'    => false,
			// Description settings
			'description_template' => '',
			'description_casing'   => 'sentence',
			'add_missing_description' => false,
			'overwrite_description'   => false,
			// WebP conversion
			'enable_webp_conversion' => true,
			'webp_quality'           => 85,
			'webp_library'           => 'auto', // auto, imagick, gd
			'convert_on_upload'      => false,
			'replace_original'       => false, // Replace original with WebP (delete original)
			// WebP serving
			'serve_webp'             => false,
			'webp_serving_method'    => 'php', // php, htaccess
		);

		$saved = get_option( 'seovela_image_seo_settings', array() );
		return wp_parse_args( $saved, $defaults );
	}

	/**
	 * Get image statistics
	 */
	public function get_image_stats() {
		global $wpdb;
		
		// Get total images
		$total_images = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} 
			WHERE post_type = 'attachment' 
			AND post_mime_type LIKE 'image/%'"
		);

		// Get scanned count
		$scanned = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->table_name}"
		);

		// Get issues from cache
		$missing_alt = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->table_name} WHERE has_alt = 0"
		);

		$missing_title = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->table_name} WHERE has_title = 0"
		);

		$missing_caption = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->table_name} WHERE has_caption = 0"
		);

		$missing_description = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->table_name} WHERE has_description = 0"
		);

		$oversized = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->table_name} WHERE is_oversized = 1"
		);

		$poor_filename = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->table_name} WHERE is_descriptive = 0"
		);

		$has_webp = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->table_name} WHERE has_webp = 1"
		);

		$convertible = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->table_name} 
			WHERE has_webp = 0 AND file_type IN ('image/jpeg', 'image/png', 'image/gif')"
		);

		// Calculate total size and potential savings
		$total_size = $wpdb->get_var(
			"SELECT SUM(file_size) FROM {$this->table_name}"
		);

		$webp_savings = $wpdb->get_var(
			"SELECT SUM(file_size - webp_size) FROM {$this->table_name} WHERE has_webp = 1 AND webp_size > 0"
		);

		return array(
			'total_images'        => (int) $total_images,
			'scanned'             => (int) $scanned,
			'missing_alt'         => (int) $missing_alt,
			'missing_title'       => (int) $missing_title,
			'missing_caption'     => (int) $missing_caption,
			'missing_description' => (int) $missing_description,
			'oversized'           => (int) $oversized,
			'poor_filename'       => (int) $poor_filename,
			'has_webp'            => (int) $has_webp,
			'convertible'         => (int) $convertible,
			'total_size'          => (int) $total_size,
			'webp_savings'        => (int) $webp_savings,
			'total_issues'        => (int) ( $missing_alt + $missing_title + $oversized + $poor_filename ),
		);
	}

	/**
	 * Check available image libraries
	 */
	public function get_available_libraries() {
		$libraries = array();

		// Check Imagick
		if ( extension_loaded( 'imagick' ) && class_exists( 'Imagick' ) ) {
			$imagick = new Imagick();
			$formats = $imagick->queryFormats();
			$libraries['imagick'] = array(
				'available'   => true,
				'webp_support' => in_array( 'WEBP', $formats ),
				'version'     => Imagick::getVersion()['versionString'] ?? 'Unknown',
			);
		} else {
			$libraries['imagick'] = array(
				'available'   => false,
				'webp_support' => false,
				'version'     => null,
			);
		}

		// Check GD
		if ( extension_loaded( 'gd' ) && function_exists( 'gd_info' ) ) {
			$gd_info = gd_info();
			$libraries['gd'] = array(
				'available'   => true,
				'webp_support' => ! empty( $gd_info['WebP Support'] ),
				'version'     => $gd_info['GD Version'] ?? 'Unknown',
			);
		} else {
			$libraries['gd'] = array(
				'available'   => false,
				'webp_support' => false,
				'version'     => null,
			);
		}

		return $libraries;
	}

	/**
	 * Parse template with variables
	 */
	public function parse_template( $template, $attachment_id, $counter = 1 ) {
		if ( empty( $template ) ) {
			return '';
		}

		$attachment = get_post( $attachment_id );
		if ( ! $attachment ) {
			return $template;
		}

		$file_path = get_attached_file( $attachment_id );
		$file_name = pathinfo( $file_path, PATHINFO_FILENAME );
		
		// Get parent post
		$parent_id = wp_get_post_parent_id( $attachment_id );
		$parent = $parent_id ? get_post( $parent_id ) : null;

		// Get author
		$author = get_userdata( $attachment->post_author );

		// Build replacements
		$replacements = array(
			'%file_name%'         => $file_name,
			'%file_name_raw%'     => ucwords( str_replace( array( '-', '_' ), ' ', $file_name ) ),
			'%image_alt%'         => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			'%image_title%'       => $attachment->post_title,
			'%image_caption%'     => $attachment->post_excerpt,
			'%post_title%'        => $parent ? $parent->post_title : '',
			'%post_excerpt%'      => $parent ? $parent->post_excerpt : '',
			'%post_category%'     => '',
			'%post_tags%'         => '',
			'%author_name%'       => $author ? $author->display_name : '',
			'%site_title%'        => get_bloginfo( 'name' ),
			'%site_description%'  => get_bloginfo( 'description' ),
			'%current_date%'      => date_i18n( get_option( 'date_format' ) ),
			'%current_year%'      => gmdate( 'Y' ),
			'%current_month%'     => date_i18n( 'F' ),
			'%upload_date%'       => get_the_date( '', $attachment ),
			'%counter%'           => $counter,
			'%separator%'         => $this->get_settings()['separator'],
		);

		// Get category if parent post exists
		if ( $parent_id ) {
			$categories = get_the_category( $parent_id );
			if ( ! empty( $categories ) ) {
				$replacements['%post_category%'] = $categories[0]->name;
			}

			$tags = get_the_tags( $parent_id );
			if ( ! empty( $tags ) ) {
				$replacements['%post_tags%'] = implode( ', ', wp_list_pluck( $tags, 'name' ) );
			}
		}

		// Apply replacements
		$result = str_replace(
			array_keys( $replacements ),
			array_values( $replacements ),
			$template
		);

		// Clean up multiple spaces and trim
		$result = preg_replace( '/\s+/', ' ', $result );
		$result = trim( $result );

		return $result;
	}

	/**
	 * Apply casing to text
	 */
	public function apply_casing( $text, $casing ) {
		if ( empty( $text ) ) {
			return $text;
		}

		switch ( $casing ) {
			case 'lower':
				return strtolower( $text );
			case 'upper':
				return strtoupper( $text );
			case 'title':
				return ucwords( strtolower( $text ) );
			case 'sentence':
				return ucfirst( strtolower( $text ) );
			default:
				return $text;
		}
	}

	/**
	 * Scan single image
	 */
	public function scan_image( $attachment_id ) {
		global $wpdb;
		
		$file_path = get_attached_file( $attachment_id );
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return false;
		}

		$attachment = get_post( $attachment_id );
		if ( ! $attachment ) {
			return false;
		}

		$settings = $this->get_settings();
		$file_size = filesize( $file_path );
		$file_name = basename( $file_path );
		$mime_type = get_post_mime_type( $attachment_id );

		// Get image dimensions
		$image_meta = wp_get_attachment_metadata( $attachment_id );
		$width = $image_meta['width'] ?? 0;
		$height = $image_meta['height'] ?? 0;

		// Get attributes
		$alt_text = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		$has_alt = ! empty( $alt_text );
		$has_title = ! empty( $attachment->post_title ) && $attachment->post_title !== $file_name;
		$has_caption = ! empty( $attachment->post_excerpt );
		$has_description = ! empty( $attachment->post_content );

		// Check filename
		$is_descriptive = $this->is_filename_descriptive( $file_name );

		// Check size
		$is_oversized = $file_size > ( $settings['size_threshold'] * 1024 );

		// Check for WebP version
		$webp_path = $this->get_webp_path( $file_path );
		$has_webp = file_exists( $webp_path );
		$webp_size = $has_webp ? filesize( $webp_path ) : 0;

		// Count issues
		$issues_count = 0;
		if ( ! $has_alt ) $issues_count++;
		if ( ! $has_title ) $issues_count++;
		if ( ! $is_descriptive ) $issues_count++;
		if ( $is_oversized ) $issues_count++;

		// Generate suggestions
		$suggested_alt = $this->parse_template( $settings['alt_template'], $attachment_id );
		$suggested_alt = $this->apply_casing( $suggested_alt, $settings['alt_casing'] );
		
		$suggested_title = $this->parse_template( $settings['title_template'], $attachment_id );
		$suggested_title = $this->apply_casing( $suggested_title, $settings['title_casing'] );

		// Prepare data
		$data = array(
			'attachment_id'     => $attachment_id,
			'file_name'         => $file_name,
			'file_path'         => $file_path,
			'file_size'         => $file_size,
			'file_type'         => $mime_type,
			'width'             => $width,
			'height'            => $height,
			'has_alt'           => $has_alt ? 1 : 0,
			'has_title'         => $has_title ? 1 : 0,
			'has_caption'       => $has_caption ? 1 : 0,
			'has_description'   => $has_description ? 1 : 0,
			'is_descriptive'    => $is_descriptive ? 1 : 0,
			'is_oversized'      => $is_oversized ? 1 : 0,
			'has_webp'          => $has_webp ? 1 : 0,
			'webp_path'         => $has_webp ? $webp_path : null,
			'webp_size'         => $webp_size,
			'issues_count'      => $issues_count,
			'suggested_alt'     => $suggested_alt,
			'suggested_title'   => $suggested_title,
			'parent_post_id'    => wp_get_post_parent_id( $attachment_id ),
			'last_scanned'      => current_time( 'mysql', true ),
		);

		// Insert or update
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$this->table_name} WHERE attachment_id = %d",
			$attachment_id
		) );

		if ( $existing ) {
			$wpdb->update(
				$this->table_name,
				$data,
				array( 'attachment_id' => $attachment_id )
			);
		} else {
			$wpdb->insert( $this->table_name, $data );
		}

		return $data;
	}

	/**
	 * Check if filename is descriptive
	 */
	private function is_filename_descriptive( $filename ) {
		$name = pathinfo( $filename, PATHINFO_FILENAME );
		
		$patterns = array(
			'/^IMG[_-]?\d+$/i',
			'/^DSC[_-]?\d+$/i',
			'/^DCIM[_-]?\d+$/i',
			'/^photo[_-]?\d+$/i',
			'/^image[_-]?\d+$/i',
			'/^screenshot[_-]?\d*$/i',
			'/^screen[_-]?shot[_-]?\d*$/i',
			'/^\d{8,}$/',
			'/^[a-f0-9]{32}$/i',
			'/^untitled/i',
			'/^whatsapp/i',
			'/^signal-\d+/i',
			'/^telegram/i',
		);
		
		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $name ) ) {
				return false;
			}
		}
		
		return strlen( $name ) >= 3 && ! ctype_digit( str_replace( array( '-', '_' ), '', $name ) );
	}

	/**
	 * Get WebP path for an image
	 */
	private function get_webp_path( $file_path ) {
		$info = pathinfo( $file_path );
		return $info['dirname'] . '/' . $info['filename'] . '.webp';
	}

	/**
	 * Convert image to WebP
	 */
	public function convert_to_webp( $attachment_id ) {
		$file_path = get_attached_file( $attachment_id );
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return array( 'success' => false, 'message' => __( 'File not found', 'seovela' ) );
		}

		// Verify the file is within the uploads directory to prevent path traversal.
		$upload_dir = wp_upload_dir();
		$real_file  = realpath( $file_path );
		$real_base  = realpath( $upload_dir['basedir'] );

		if ( false === $real_file || false === $real_base || strpos( $real_file, $real_base ) !== 0 ) {
			return array( 'success' => false, 'message' => __( 'File is outside the uploads directory', 'seovela' ) );
		}

		$mime_type = get_post_mime_type( $attachment_id );
		$allowed_types = array( 'image/jpeg', 'image/png', 'image/gif' );
		
		if ( ! in_array( $mime_type, $allowed_types, true ) ) {
			return array( 'success' => false, 'message' => __( 'Unsupported image type', 'seovela' ) );
		}

		$settings = $this->get_settings();
		$webp_path = $this->get_webp_path( $file_path );
		$quality = $settings['webp_quality'];
		$library = $settings['webp_library'];

		$result = false;

		// Try Imagick first if preferred or auto
		if ( $library === 'imagick' || $library === 'auto' ) {
			$result = $this->convert_with_imagick( $file_path, $webp_path, $quality );
		}

		// Fall back to GD
		if ( ! $result && ( $library === 'gd' || $library === 'auto' ) ) {
			$result = $this->convert_with_gd( $file_path, $webp_path, $quality, $mime_type );
		}

		if ( $result && file_exists( $webp_path ) ) {
			$original_size = filesize( $file_path );
			$webp_size = filesize( $webp_path );
			$savings = $original_size - $webp_size;
			$savings_percent = round( ( $savings / $original_size ) * 100 );

			global $wpdb;
			$replaced_original = false;

			// Check if we should replace the original
			if ( ! empty( $settings['replace_original'] ) ) {
				$replaced_original = $this->replace_original_with_webp( $attachment_id, $file_path, $webp_path, $mime_type );
			}

			// Update cache database
			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$this->table_name} WHERE attachment_id = %d",
				$attachment_id
			) );

			$cache_data = array(
				'has_webp'     => $replaced_original ? 0 : 1, // No separate WebP if replaced
				'webp_path'    => $replaced_original ? null : $webp_path,
				'webp_size'    => $replaced_original ? 0 : $webp_size,
				'file_size'    => $replaced_original ? $webp_size : $original_size,
				'file_type'    => $replaced_original ? 'image/webp' : $mime_type,
				'last_scanned' => current_time( 'mysql', true ),
			);

			if ( $existing ) {
				$wpdb->update(
					$this->table_name,
					$cache_data,
					array( 'attachment_id' => $attachment_id ),
					array( '%d', '%s', '%d', '%d', '%s', '%s' ),
					array( '%d' )
				);
			} else {
				$file_name = $replaced_original ? basename( $webp_path ) : basename( $file_path );
				$cache_data['attachment_id'] = $attachment_id;
				$cache_data['file_name'] = $file_name;
				$cache_data['file_path'] = $replaced_original ? $webp_path : $file_path;
				$wpdb->insert( $this->table_name, $cache_data );
			}

			$message = $replaced_original 
				? sprintf( __( 'Converted and replaced original! Saved %s (%d%%)', 'seovela' ), size_format( $savings ), $savings_percent )
				: sprintf( __( 'Converted successfully! Saved %s (%d%%)', 'seovela' ), size_format( $savings ), $savings_percent );

			return array(
				'success'           => true,
				'webp_path'         => $webp_path,
				'original_size'     => $original_size,
				'webp_size'         => $webp_size,
				'savings'           => $savings,
				'savings_percent'   => $savings_percent,
				'replaced_original' => $replaced_original,
				'message'           => $message,
			);
		}

		return array( 'success' => false, 'message' => __( 'Conversion failed', 'seovela' ) );
	}

	/**
	 * Replace original image with WebP version
	 */
	private function replace_original_with_webp( $attachment_id, $original_path, $webp_path, $original_mime ) {
		// Get attachment metadata
		$metadata = wp_get_attachment_metadata( $attachment_id );
		if ( ! $metadata ) {
			return false;
		}

		$upload_dir = wp_upload_dir();
		$base_dir = trailingslashit( $upload_dir['basedir'] );
		
		// Convert thumbnail sizes to WebP and delete originals
		if ( ! empty( $metadata['sizes'] ) ) {
			$original_dir = dirname( $original_path );
			
			foreach ( $metadata['sizes'] as $size => $size_data ) {
				$size_path = $original_dir . '/' . $size_data['file'];
				$size_webp_path = $this->get_webp_path( $size_path );
				
				// Convert this size to WebP
				if ( file_exists( $size_path ) ) {
					$settings = $this->get_settings();
					$quality = $settings['webp_quality'];
					
					// Try to convert
					$converted = $this->convert_with_imagick( $size_path, $size_webp_path, $quality );
					if ( ! $converted ) {
						$converted = $this->convert_with_gd( $size_path, $size_webp_path, $quality, $original_mime );
					}
					
					if ( $converted && file_exists( $size_webp_path ) ) {
						// Delete original size file
						@unlink( $size_path );
						
						// Update metadata for this size
						$new_filename = pathinfo( $size_data['file'], PATHINFO_FILENAME ) . '.webp';
						$metadata['sizes'][ $size ]['file'] = $new_filename;
						$metadata['sizes'][ $size ]['mime-type'] = 'image/webp';
					}
				}
			}
		}

		// Delete the original main file
		if ( file_exists( $original_path ) && $original_path !== $webp_path ) {
			@unlink( $original_path );
		}

		// Update main file reference
		$new_filename = pathinfo( $metadata['file'], PATHINFO_FILENAME ) . '.webp';
		$new_file_path = dirname( $metadata['file'] ) . '/' . $new_filename;
		$metadata['file'] = $new_file_path;

		// Update attachment metadata
		wp_update_attachment_metadata( $attachment_id, $metadata );

		// Update the attachment post
		wp_update_post( array(
			'ID'             => $attachment_id,
			'post_mime_type' => 'image/webp',
		) );

		// Update the _wp_attached_file meta
		update_post_meta( $attachment_id, '_wp_attached_file', $new_file_path );

		return true;
	}

	/**
	 * Convert using Imagick
	 */
	private function convert_with_imagick( $source, $destination, $quality ) {
		if ( ! extension_loaded( 'imagick' ) || ! class_exists( 'Imagick' ) ) {
			return false;
		}

		try {
			$imagick = new Imagick( $source );
			$imagick->setImageFormat( 'webp' );
			$imagick->setImageCompressionQuality( $quality );
			
			// Strip metadata for smaller file
			$imagick->stripImage();
			
			$result = $imagick->writeImage( $destination );
			$imagick->destroy();
			
			return $result;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Convert using GD
	 */
	private function convert_with_gd( $source, $destination, $quality, $mime_type ) {
		if ( ! extension_loaded( 'gd' ) || ! function_exists( 'imagewebp' ) ) {
			return false;
		}

		try {
			switch ( $mime_type ) {
				case 'image/jpeg':
					$image = imagecreatefromjpeg( $source );
					break;
				case 'image/png':
					$image = imagecreatefrompng( $source );
					// Preserve transparency
					imagepalettetotruecolor( $image );
					imagealphablending( $image, true );
					imagesavealpha( $image, true );
					break;
				case 'image/gif':
					$image = imagecreatefromgif( $source );
					break;
				default:
					return false;
			}

			if ( ! $image ) {
				return false;
			}

			$result = imagewebp( $image, $destination, $quality );
			imagedestroy( $image );
			
			return $result;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Update image attributes
	 */
	public function update_image_attributes( $attachment_id, $attributes ) {
		$attachment = get_post( $attachment_id );
		if ( ! $attachment ) {
			return false;
		}

		// Update alt text
		if ( isset( $attributes['alt'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $attributes['alt'] ) );
		}

		// Update post data (title, caption, description)
		$post_data = array( 'ID' => $attachment_id );
		$update_post = false;

		if ( isset( $attributes['title'] ) ) {
			$post_data['post_title'] = sanitize_text_field( $attributes['title'] );
			$update_post = true;
		}

		if ( isset( $attributes['caption'] ) ) {
			$post_data['post_excerpt'] = sanitize_textarea_field( $attributes['caption'] );
			$update_post = true;
		}

		if ( isset( $attributes['description'] ) ) {
			$post_data['post_content'] = sanitize_textarea_field( $attributes['description'] );
			$update_post = true;
		}

		if ( $update_post ) {
			wp_update_post( $post_data );
		}

		// Re-scan to update cache
		$this->scan_image( $attachment_id );

		return true;
	}

	/**
	 * Process new upload
	 */
	public function process_new_upload( $attachment_id ) {
		$mime_type = get_post_mime_type( $attachment_id );
		if ( strpos( $mime_type, 'image/' ) !== 0 ) {
			return;
		}

		$settings = $this->get_settings();
		
		if ( ! $settings['auto_process_upload'] ) {
			return;
		}

		// Scan the image
		$this->scan_image( $attachment_id );

		// Auto-fill missing attributes
		$updates = array();

		if ( $settings['add_missing_alt'] ) {
			$current_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			if ( empty( $current_alt ) || $settings['overwrite_alt'] ) {
				$new_alt = $this->parse_template( $settings['alt_template'], $attachment_id );
				$new_alt = $this->apply_casing( $new_alt, $settings['alt_casing'] );
				if ( ! empty( $new_alt ) ) {
					$updates['alt'] = $new_alt;
				}
			}
		}

		if ( $settings['add_missing_title'] ) {
			$attachment = get_post( $attachment_id );
			$file_name = basename( get_attached_file( $attachment_id ) );
			if ( empty( $attachment->post_title ) || $attachment->post_title === $file_name || $settings['overwrite_title'] ) {
				$new_title = $this->parse_template( $settings['title_template'], $attachment_id );
				$new_title = $this->apply_casing( $new_title, $settings['title_casing'] );
				if ( ! empty( $new_title ) ) {
					$updates['title'] = $new_title;
				}
			}
		}

		if ( $settings['add_missing_caption'] ) {
			$attachment = get_post( $attachment_id );
			if ( empty( $attachment->post_excerpt ) || $settings['overwrite_caption'] ) {
				$new_caption = $this->parse_template( $settings['caption_template'], $attachment_id );
				$new_caption = $this->apply_casing( $new_caption, $settings['caption_casing'] );
				if ( ! empty( $new_caption ) ) {
					$updates['caption'] = $new_caption;
				}
			}
		}

		if ( $settings['add_missing_description'] ) {
			$attachment = get_post( $attachment_id );
			if ( empty( $attachment->post_content ) || $settings['overwrite_description'] ) {
				$new_description = $this->parse_template( $settings['description_template'], $attachment_id );
				$new_description = $this->apply_casing( $new_description, $settings['description_casing'] );
				if ( ! empty( $new_description ) ) {
					$updates['description'] = $new_description;
				}
			}
		}

		if ( ! empty( $updates ) ) {
			$this->update_image_attributes( $attachment_id, $updates );
		}

		// Auto-convert to WebP
		if ( $settings['convert_on_upload'] && $settings['enable_webp_conversion'] ) {
			$this->convert_to_webp( $attachment_id );
		}
	}

	/**
	 * Get images list - queries from WordPress posts table with cache data
	 */
	public function get_images( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'filter'  => 'all',
			'search'  => '',
			'limit'   => 50,
			'offset'  => 0,
			'orderby' => 'p.ID',
			'order'   => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		// Base where clause - all image attachments
		$where = array( 
			"p.post_type = 'attachment'",
			"p.post_mime_type LIKE 'image/%'"
		);

		$join = "LEFT JOIN {$this->table_name} c ON p.ID = c.attachment_id";

		// Apply filter
		switch ( $args['filter'] ) {
			case 'missing_alt':
				$where[] = "(c.has_alt = 0 OR c.has_alt IS NULL)";
				break;
			case 'missing_title':
				$where[] = "(c.has_title = 0 OR c.has_title IS NULL)";
				break;
			case 'missing_caption':
				$where[] = "(c.has_caption = 0 OR c.has_caption IS NULL)";
				break;
			case 'missing_description':
				$where[] = "(c.has_description = 0 OR c.has_description IS NULL)";
				break;
			case 'oversized':
				$where[] = "c.is_oversized = 1";
				break;
			case 'poor_filename':
				$where[] = "(c.is_descriptive = 0 OR c.is_descriptive IS NULL)";
				break;
			case 'no_webp':
				$where[] = "(c.has_webp = 0 OR c.has_webp IS NULL) AND p.post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')";
				break;
			case 'has_webp':
				$where[] = "c.has_webp = 1";
				break;
			case 'issues':
				$where[] = "(c.issues_count > 0 OR c.issues_count IS NULL)";
				break;
			case 'scanned':
				$where[] = "c.attachment_id IS NOT NULL";
				break;
			case 'not_scanned':
				$where[] = "c.attachment_id IS NULL";
				break;
		}

		// Search
		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[] = $wpdb->prepare( "(p.post_title LIKE %s OR c.file_name LIKE %s)", $search, $search );
		}

		$where_clause = implode( ' AND ', $where );
		
		// Handle orderby
		$orderby_map = array(
			'last_scanned' => 'c.last_scanned',
			'file_size'    => 'c.file_size',
			'issues_count' => 'c.issues_count',
			'ID'           => 'p.ID',
			'date'         => 'p.post_date',
		);
		
		$order_column = isset( $orderby_map[ $args['orderby'] ] ) ? $orderby_map[ $args['orderby'] ] : 'p.ID';
		$order_dir = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		// Sanitize limit and offset as integers
		$limit = absint( $args['limit'] );
		$offset = absint( $args['offset'] );
		
		// Build WHERE clause for filters that need cache data
		$cache_filter = '';
		$needs_cache_filter = in_array( $args['filter'], array( 'oversized', 'poor_filename', 'no_webp', 'has_webp', 'issues', 'scanned' ) );
		
		if ( $needs_cache_filter ) {
			switch ( $args['filter'] ) {
				case 'oversized':
					$cache_filter = "AND c.is_oversized = 1";
					break;
				case 'poor_filename':
					$cache_filter = "AND c.is_descriptive = 0";
					break;
				case 'no_webp':
					$cache_filter = "AND (c.has_webp = 0 OR c.has_webp IS NULL)";
					break;
				case 'has_webp':
					$cache_filter = "AND c.has_webp = 1";
					break;
				case 'issues':
					$cache_filter = "AND c.issues_count > 0";
					break;
				case 'scanned':
					$cache_filter = "AND c.attachment_id IS NOT NULL";
					break;
			}
		}
		
		// Build base query
		if ( $needs_cache_filter ) {
			// Use INNER JOIN for filters that require cache data
			$sql = "SELECT 
					p.ID as attachment_id,
					p.post_title,
					p.post_excerpt,
					p.post_content,
					p.post_mime_type as file_type,
					p.post_date,
					c.file_name,
					c.file_size,
					c.width,
					c.height,
					c.has_alt,
					c.has_title,
					c.has_caption,
					c.has_description,
					c.is_descriptive,
					c.is_oversized,
					c.has_webp,
					c.webp_size,
					c.issues_count,
					c.last_scanned
				FROM {$wpdb->posts} p
				INNER JOIN {$this->table_name} c ON p.ID = c.attachment_id
				WHERE p.post_type = 'attachment' 
				AND p.post_mime_type LIKE 'image/%'
				{$cache_filter}
				ORDER BY p.ID DESC
				LIMIT {$limit} OFFSET {$offset}";
			
			$count_sql = "SELECT COUNT(*) FROM {$wpdb->posts} p 
				INNER JOIN {$this->table_name} c ON p.ID = c.attachment_id
				WHERE p.post_type = 'attachment' 
				AND p.post_mime_type LIKE 'image/%'
				{$cache_filter}";
		} elseif ( $args['filter'] === 'not_scanned' ) {
			// Get images NOT in cache
			$sql = "SELECT 
					p.ID as attachment_id,
					p.post_title,
					p.post_excerpt,
					p.post_content,
					p.post_mime_type as file_type,
					p.post_date
				FROM {$wpdb->posts} p
				LEFT JOIN {$this->table_name} c ON p.ID = c.attachment_id
				WHERE p.post_type = 'attachment' 
				AND p.post_mime_type LIKE 'image/%'
				AND c.attachment_id IS NULL
				ORDER BY p.ID DESC
				LIMIT {$limit} OFFSET {$offset}";
			
			$count_sql = "SELECT COUNT(*) FROM {$wpdb->posts} p 
				LEFT JOIN {$this->table_name} c ON p.ID = c.attachment_id
				WHERE p.post_type = 'attachment' 
				AND p.post_mime_type LIKE 'image/%'
				AND c.attachment_id IS NULL";
		} else {
			// Simple query for 'all' filter - cache data will be fetched separately
			$sql = "SELECT 
					p.ID as attachment_id,
					p.post_title,
					p.post_excerpt,
					p.post_content,
					p.post_mime_type as file_type,
					p.post_date
				FROM {$wpdb->posts} p
				WHERE p.post_type = 'attachment' 
				AND p.post_mime_type LIKE 'image/%'
				ORDER BY p.ID DESC
				LIMIT {$limit} OFFSET {$offset}";
			
			$count_sql = "SELECT COUNT(*) FROM {$wpdb->posts} p 
				WHERE p.post_type = 'attachment' 
				AND p.post_mime_type LIKE 'image/%'";
		}
		
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table names are plugin-prefixed constants, cache_filter is hardcoded, limit/offset are absint().
		$images = $wpdb->get_results( $sql );

		// Get total count
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Same safe construction as above.
		$total = $wpdb->get_var( $count_sql );

		return array(
			'images' => $images,
			'total'  => (int) $total,
		);
	}

	/**
	 * Add dashboard widget
	 */
	public function add_dashboard_widget() {
		if ( ! get_option( 'seovela_image_seo_enabled', true ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'seovela_image_seo_widget',
			__( 'Image SEO Status', 'seovela' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render dashboard widget
	 */
	public function render_dashboard_widget() {
		$stats = $this->get_image_stats();
		$compliance = $stats['total_images'] > 0 
			? round( ( ( $stats['scanned'] - $stats['missing_alt'] ) / max( $stats['scanned'], 1 ) ) * 100 )
			: 0;
		?>
		<div class="seovela-image-seo-widget">
			<div class="widget-stats">
				<div class="stat-item">
					<span class="stat-value"><?php echo esc_html( number_format( $stats['total_images'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Total', 'seovela' ); ?></span>
				</div>
				<div class="stat-item warning">
					<span class="stat-value"><?php echo esc_html( number_format( $stats['missing_alt'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'No Alt', 'seovela' ); ?></span>
				</div>
				<div class="stat-item success">
					<span class="stat-value"><?php echo esc_html( number_format( $stats['has_webp'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'WebP', 'seovela' ); ?></span>
				</div>
			</div>
			<div class="compliance-bar">
				<div class="compliance-fill" style="width: <?php echo esc_attr( $compliance ); ?>%"></div>
			</div>
			<p class="compliance-text"><?php printf( esc_html__( '%d%% Alt Text Compliance', 'seovela' ), $compliance ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-image-seo' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Manage Images', 'seovela' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Render admin page
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = $this->get_settings();
		$stats = $this->get_image_stats();
		$libraries = $this->get_available_libraries();
		
		require_once plugin_dir_path( __FILE__ ) . 'views/image-seo.php';
	}

	/**
	 * AJAX: Scan images
	 */
	public function ajax_scan_images() {
		check_ajax_referer( 'seovela_image_seo', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$offset = isset( $_POST['offset'] ) ? absint( wp_unslash( $_POST['offset'] ) ) : 0;
		$limit = isset( $_POST['limit'] ) ? absint( wp_unslash( $_POST['limit'] ) ) : 50;

		global $wpdb;

		// Get total images
		$total = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} 
			WHERE post_type = 'attachment' 
			AND post_mime_type LIKE 'image/%'"
		);

		// Get batch
		$images = $wpdb->get_results( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} 
			WHERE post_type = 'attachment' 
			AND post_mime_type LIKE 'image/%%'
			ORDER BY ID DESC
			LIMIT %d OFFSET %d",
			$limit,
			$offset
		) );

		$scanned = 0;
		foreach ( $images as $image ) {
			$this->scan_image( $image->ID );
			$scanned++;
		}

		$new_offset = $offset + $scanned;
		$progress = min( 100, round( ( $new_offset / max( $total, 1 ) ) * 100 ) );

		wp_send_json_success( array(
			'scanned'   => $scanned,
			'offset'    => $new_offset,
			'total'     => $total,
			'progress'  => $progress,
			'completed' => $scanned < $limit,
		) );
	}

	/**
	 * AJAX: Update image
	 */
	public function ajax_update_image() {
		check_ajax_referer( 'seovela_image_seo', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$attachment_id = isset( $_POST['attachment_id'] ) ? intval( wp_unslash( $_POST['attachment_id'] ) ) : 0;
		$attributes = array();

		if ( isset( $_POST['alt'] ) ) {
			$attributes['alt'] = sanitize_text_field( wp_unslash( $_POST['alt'] ) );
		}
		if ( isset( $_POST['title'] ) ) {
			$attributes['title'] = sanitize_text_field( wp_unslash( $_POST['title'] ) );
		}
		if ( isset( $_POST['caption'] ) ) {
			$attributes['caption'] = sanitize_textarea_field( wp_unslash( $_POST['caption'] ) );
		}
		if ( isset( $_POST['description'] ) ) {
			$attributes['description'] = sanitize_textarea_field( wp_unslash( $_POST['description'] ) );
		}

		$result = $this->update_image_attributes( $attachment_id, $attributes );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Image updated successfully', 'seovela' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to update image', 'seovela' ) ) );
		}
	}

	/**
	 * AJAX: Bulk update
	 */
	public function ajax_bulk_update() {
		check_ajax_referer( 'seovela_image_seo', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$action_type = isset( $_POST['action_type'] ) ? sanitize_text_field( wp_unslash( $_POST['action_type'] ) ) : '';
		$attachment_ids = isset( $_POST['attachment_ids'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['attachment_ids'] ) ) : array();
		$filter = isset( $_POST['filter'] ) ? sanitize_text_field( wp_unslash( $_POST['filter'] ) ) : 'all';

		// If no specific IDs, get from filter
		if ( empty( $attachment_ids ) && ! empty( $filter ) ) {
			$result = $this->get_images( array( 'filter' => $filter, 'limit' => 1000 ) );
			$attachment_ids = wp_list_pluck( $result['images'], 'attachment_id' );
		}

		if ( empty( $attachment_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No images selected', 'seovela' ) ) );
		}

		$settings = $this->get_settings();
		$updated = 0;
		$counter = 1;

		foreach ( $attachment_ids as $attachment_id ) {
			$updates = array();

			switch ( $action_type ) {
				case 'apply_alt':
					$alt = $this->parse_template( $settings['alt_template'], $attachment_id, $counter );
					$alt = $this->apply_casing( $alt, $settings['alt_casing'] );
					if ( ! empty( $alt ) ) {
						$updates['alt'] = $alt;
					}
					break;

				case 'apply_title':
					$title = $this->parse_template( $settings['title_template'], $attachment_id, $counter );
					$title = $this->apply_casing( $title, $settings['title_casing'] );
					if ( ! empty( $title ) ) {
						$updates['title'] = $title;
					}
					break;

				case 'apply_caption':
					$caption = $this->parse_template( $settings['caption_template'], $attachment_id, $counter );
					$caption = $this->apply_casing( $caption, $settings['caption_casing'] );
					if ( ! empty( $caption ) ) {
						$updates['caption'] = $caption;
					}
					break;

				case 'apply_description':
					$description = $this->parse_template( $settings['description_template'], $attachment_id, $counter );
					$description = $this->apply_casing( $description, $settings['description_casing'] );
					if ( ! empty( $description ) ) {
						$updates['description'] = $description;
					}
					break;

				case 'apply_all':
					if ( ! empty( $settings['alt_template'] ) ) {
						$alt = $this->parse_template( $settings['alt_template'], $attachment_id, $counter );
						$updates['alt'] = $this->apply_casing( $alt, $settings['alt_casing'] );
					}
					if ( ! empty( $settings['title_template'] ) ) {
						$title = $this->parse_template( $settings['title_template'], $attachment_id, $counter );
						$updates['title'] = $this->apply_casing( $title, $settings['title_casing'] );
					}
					if ( ! empty( $settings['caption_template'] ) ) {
						$caption = $this->parse_template( $settings['caption_template'], $attachment_id, $counter );
						$updates['caption'] = $this->apply_casing( $caption, $settings['caption_casing'] );
					}
					if ( ! empty( $settings['description_template'] ) ) {
						$description = $this->parse_template( $settings['description_template'], $attachment_id, $counter );
						$updates['description'] = $this->apply_casing( $description, $settings['description_casing'] );
					}
					break;
			}

			if ( ! empty( $updates ) ) {
				$this->update_image_attributes( $attachment_id, $updates );
				$updated++;
			}

			$counter++;
		}

		wp_send_json_success( array(
			'message' => sprintf( __( 'Updated %d images', 'seovela' ), $updated ),
			'updated' => $updated,
		) );
	}

	/**
	 * AJAX: Convert to WebP
	 */
	public function ajax_convert_webp() {
		check_ajax_referer( 'seovela_image_seo', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$attachment_ids = isset( $_POST['attachment_ids'] ) ? array_map( 'intval', wp_unslash( $_POST['attachment_ids'] ) ) : array();

		if ( empty( $attachment_ids ) ) {
			// Get all convertible images
			$result = $this->get_images( array( 'filter' => 'no_webp', 'limit' => 1000 ) );
			$attachment_ids = wp_list_pluck( $result['images'], 'attachment_id' );
		}

		if ( empty( $attachment_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No images to convert', 'seovela' ) ) );
		}

		$converted = 0;
		$total_savings = 0;
		$errors = array();

		foreach ( $attachment_ids as $attachment_id ) {
			$result = $this->convert_to_webp( $attachment_id );
			
			if ( $result['success'] ) {
				$converted++;
				$total_savings += $result['savings'];
			} else {
				$errors[] = sprintf( __( 'Image %d: %s', 'seovela' ), $attachment_id, $result['message'] );
			}
		}

		wp_send_json_success( array(
			'message'       => sprintf( __( 'Converted %d images. Total savings: %s', 'seovela' ), $converted, size_format( $total_savings ) ),
			'converted'     => $converted,
			'total_savings' => $total_savings,
			'errors'        => $errors,
		) );
	}

	/**
	 * AJAX: Get stats
	 */
	public function ajax_get_stats() {
		check_ajax_referer( 'seovela_image_seo', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$stats = $this->get_image_stats();
		wp_send_json_success( array( 'stats' => $stats ) );
	}

	/**
	 * AJAX: Save settings
	 */
	public function ajax_save_settings() {
		check_ajax_referer( 'seovela_image_seo', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$settings = array(
			'enabled'              => ! empty( $_POST['enabled'] ),
			'auto_process_upload'  => ! empty( $_POST['auto_process_upload'] ),
			'size_threshold'       => isset( $_POST['size_threshold'] ) ? absint( wp_unslash( $_POST['size_threshold'] ) ) : 200,
			'separator'            => isset( $_POST['separator'] ) ? sanitize_text_field( wp_unslash( $_POST['separator'] ) ) : ' - ',
			// Alt text
			'alt_template'         => isset( $_POST['alt_template'] ) ? sanitize_text_field( wp_unslash( $_POST['alt_template'] ) ) : '',
			'alt_casing'           => isset( $_POST['alt_casing'] ) ? sanitize_text_field( wp_unslash( $_POST['alt_casing'] ) ) : 'none',
			'add_missing_alt'      => ! empty( $_POST['add_missing_alt'] ),
			'overwrite_alt'        => ! empty( $_POST['overwrite_alt'] ),
			// Title
			'title_template'       => isset( $_POST['title_template'] ) ? sanitize_text_field( wp_unslash( $_POST['title_template'] ) ) : '',
			'title_casing'         => isset( $_POST['title_casing'] ) ? sanitize_text_field( wp_unslash( $_POST['title_casing'] ) ) : 'none',
			'add_missing_title'    => ! empty( $_POST['add_missing_title'] ),
			'overwrite_title'      => ! empty( $_POST['overwrite_title'] ),
			// Caption
			'caption_template'     => isset( $_POST['caption_template'] ) ? sanitize_text_field( wp_unslash( $_POST['caption_template'] ) ) : '',
			'caption_casing'       => isset( $_POST['caption_casing'] ) ? sanitize_text_field( wp_unslash( $_POST['caption_casing'] ) ) : 'none',
			'add_missing_caption'  => ! empty( $_POST['add_missing_caption'] ),
			'overwrite_caption'    => ! empty( $_POST['overwrite_caption'] ),
			// Description
			'description_template' => isset( $_POST['description_template'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description_template'] ) ) : '',
			'description_casing'   => isset( $_POST['description_casing'] ) ? sanitize_text_field( wp_unslash( $_POST['description_casing'] ) ) : 'none',
			'add_missing_description' => ! empty( $_POST['add_missing_description'] ),
			'overwrite_description'   => ! empty( $_POST['overwrite_description'] ),
			// WebP
			'enable_webp_conversion' => ! empty( $_POST['enable_webp_conversion'] ),
			'webp_quality'           => isset( $_POST['webp_quality'] ) ? absint( wp_unslash( $_POST['webp_quality'] ) ) : 85,
			'webp_library'           => isset( $_POST['webp_library'] ) ? sanitize_text_field( wp_unslash( $_POST['webp_library'] ) ) : 'auto',
			'convert_on_upload'      => ! empty( $_POST['convert_on_upload'] ),
			'replace_original'       => ! empty( $_POST['replace_original'] ),
			// WebP serving
			'serve_webp'             => ! empty( $_POST['serve_webp'] ),
			'webp_serving_method'    => isset( $_POST['webp_serving_method'] ) ? sanitize_text_field( wp_unslash( $_POST['webp_serving_method'] ) ) : 'php',
		);

		// Handle .htaccess rules if using htaccess method
		$old_settings = $this->get_settings();
		$htaccess_changed = ( $settings['serve_webp'] !== $old_settings['serve_webp'] ) ||
		                    ( $settings['webp_serving_method'] !== $old_settings['webp_serving_method'] );

		update_option( 'seovela_image_seo_settings', $settings );
		update_option( 'seovela_image_seo_enabled', $settings['enabled'] );

		// Update .htaccess if needed
		if ( $htaccess_changed ) {
			if ( $settings['serve_webp'] && $settings['webp_serving_method'] === 'htaccess' ) {
				$htaccess_result = $this->add_webp_htaccess_rules();
			} else {
				$htaccess_result = $this->remove_webp_htaccess_rules();
			}
		}

		wp_send_json_success( array( 'message' => __( 'Settings saved successfully', 'seovela' ) ) );
	}

	/**
	 * AJAX: Apply template to single image
	 */
	public function ajax_apply_template() {
		check_ajax_referer( 'seovela_image_seo', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$attachment_id = isset( $_POST['attachment_id'] ) ? intval( wp_unslash( $_POST['attachment_id'] ) ) : 0;
		$attribute = isset( $_POST['attribute'] ) ? sanitize_text_field( wp_unslash( $_POST['attribute'] ) ) : 'alt';

		$settings = $this->get_settings();
		$template_key = $attribute . '_template';
		$casing_key = $attribute . '_casing';

		if ( ! isset( $settings[ $template_key ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid attribute', 'seovela' ) ) );
		}

		$value = $this->parse_template( $settings[ $template_key ], $attachment_id );
		$value = $this->apply_casing( $value, $settings[ $casing_key ] );

		wp_send_json_success( array( 'value' => $value ) );
	}

	/**
	 * AJAX: Get image list
	 */
	public function ajax_get_image_list() {
		check_ajax_referer( 'seovela_image_seo', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$args = array(
			'filter'  => isset( $_POST['filter'] ) ? sanitize_text_field( wp_unslash( $_POST['filter'] ) ) : 'all',
			'search'  => isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '',
			'limit'   => isset( $_POST['limit'] ) ? absint( wp_unslash( $_POST['limit'] ) ) : 50,
			'offset'  => isset( $_POST['offset'] ) ? absint( wp_unslash( $_POST['offset'] ) ) : 0,
			'orderby' => isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : 'ID',
			'order'   => isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'DESC',
		);

		$result = $this->get_images( $args );
		$settings = $this->get_settings();

		global $wpdb;
		
		// Add extra data for each image
		foreach ( $result['images'] as &$image ) {
			$attachment_id = $image->attachment_id;
			
			// Get thumbnail
			$image->thumbnail_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
			$image->edit_url = get_edit_post_link( $attachment_id, 'raw' );
			
			// Get current values from WordPress (not cache)
			$image->current_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			$image->current_title = $image->post_title;
			$image->current_caption = $image->post_excerpt;
			$image->current_description = $image->post_content;
			
			// Try to get cache data if not already present
			if ( ! isset( $image->last_scanned ) || empty( $image->last_scanned ) ) {
				$cache_data = $wpdb->get_row( $wpdb->prepare(
					"SELECT * FROM {$this->table_name} WHERE attachment_id = %d",
					$attachment_id
				) );
				
				if ( $cache_data ) {
					$image->file_name = $cache_data->file_name;
					$image->file_size = $cache_data->file_size;
					$image->width = $cache_data->width;
					$image->height = $cache_data->height;
					$image->has_alt = $cache_data->has_alt;
					$image->has_title = $cache_data->has_title;
					$image->has_caption = $cache_data->has_caption;
					$image->has_description = $cache_data->has_description;
					$image->is_descriptive = $cache_data->is_descriptive;
					$image->is_oversized = $cache_data->is_oversized;
					$image->has_webp = $cache_data->has_webp;
					$image->webp_size = $cache_data->webp_size;
					$image->issues_count = $cache_data->issues_count;
					$image->last_scanned = $cache_data->last_scanned;
					$image->is_scanned = true;
				}
			} else {
				$image->is_scanned = true;
			}
			
			// If still no cache data, get basic file info
			if ( empty( $image->file_name ) ) {
				$file_path = get_attached_file( $attachment_id );
				if ( $file_path && file_exists( $file_path ) ) {
					$image->file_name = basename( $file_path );
					$image->file_size = filesize( $file_path );
					$image->is_oversized = $image->file_size > ( $settings['size_threshold'] * 1024 ) ? 1 : 0;
				} else {
					$image->file_name = __( 'File not found', 'seovela' );
					$image->file_size = 0;
				}
				
				// Get dimensions from metadata
				$metadata = wp_get_attachment_metadata( $attachment_id );
				$image->width = $metadata['width'] ?? 0;
				$image->height = $metadata['height'] ?? 0;
				
				// Set defaults for unscanned
				$image->has_alt = ! empty( $image->current_alt ) ? 1 : 0;
				$image->has_title = ( ! empty( $image->post_title ) && $image->post_title !== $image->file_name ) ? 1 : 0;
				$image->has_caption = ! empty( $image->post_excerpt ) ? 1 : 0;
				$image->has_description = ! empty( $image->post_content ) ? 1 : 0;
				$image->has_webp = 0;
				$image->webp_size = 0;
				$image->is_descriptive = 1; // Assume descriptive until scanned
				$image->last_scanned = null;
				$image->is_scanned = false;
			}
		}

		wp_send_json_success( $result );
	}

	/**
	 * Add WebP rewrite rules to .htaccess
	 */
	public function add_webp_htaccess_rules() {
		$htaccess_file = ABSPATH . '.htaccess';
		
		if ( ! is_writable( $htaccess_file ) ) {
			return false;
		}

		$rules = $this->get_webp_htaccess_rules();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local .htaccess file.
		$contents = file_get_contents( $htaccess_file );

		// Check if rules already exist
		if ( strpos( $contents, '# BEGIN SEOVela WebP' ) !== false ) {
			return true; // Already exists
		}

		// Add rules before WordPress rules
		$marker = '# BEGIN WordPress';
		if ( strpos( $contents, $marker ) !== false ) {
			$contents = str_replace( $marker, $rules . "\n\n" . $marker, $contents );
		} else {
			$contents = $rules . "\n\n" . $contents;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Writing local .htaccess file.
		return file_put_contents( $htaccess_file, $contents ) !== false;
	}

	/**
	 * Remove WebP rewrite rules from .htaccess
	 */
	public function remove_webp_htaccess_rules() {
		$htaccess_file = ABSPATH . '.htaccess';
		
		if ( ! is_writable( $htaccess_file ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local .htaccess file.
		$contents = file_get_contents( $htaccess_file );

		// Remove rules
		$pattern = '/# BEGIN SEOVela WebP.*?# END SEOVela WebP\s*/s';
		$contents = preg_replace( $pattern, '', $contents );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Writing local .htaccess file.
		return file_put_contents( $htaccess_file, $contents ) !== false;
	}

	/**
	 * Get WebP .htaccess rules
	 */
	private function get_webp_htaccess_rules() {
		$upload_dir = wp_upload_dir();
		$upload_path = str_replace( ABSPATH, '', $upload_dir['basedir'] );

		return '# BEGIN SEOVela WebP
<IfModule mod_rewrite.c>
RewriteEngine On

# Check if browser accepts WebP
RewriteCond %{HTTP_ACCEPT} image/webp

# Check if WebP file exists
RewriteCond %{DOCUMENT_ROOT}/' . $upload_path . '/$1.webp -f

# Serve WebP instead of jpg/png/gif
RewriteRule ^(' . $upload_path . '/.+)\.(jpe?g|png|gif)$ $1.webp [T=image/webp,L]
</IfModule>

<IfModule mod_headers.c>
# Add Vary header for caching
<FilesMatch "\.(jpe?g|png|gif)$">
Header append Vary Accept
</FilesMatch>
</IfModule>

# Set correct mime type for WebP
<IfModule mod_mime.c>
AddType image/webp .webp
</IfModule>
# END SEOVela WebP';
	}

	/**
	 * Get current .htaccess WebP status
	 */
	public function get_htaccess_status() {
		$htaccess_file = ABSPATH . '.htaccess';
		
		$status = array(
			'exists'    => file_exists( $htaccess_file ),
			'writable'  => is_writable( $htaccess_file ),
			'has_rules' => false,
		);

		if ( $status['exists'] ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local .htaccess file.
			$contents = file_get_contents( $htaccess_file );
			$status['has_rules'] = strpos( $contents, '# BEGIN SEOVela WebP' ) !== false;
		}

		return $status;
	}

	/**
	 * Add WebP column to Media Library
	 */
	public function add_media_columns( $columns ) {
		$columns['seovela_webp'] = __( 'WebP', 'seovela' );
		return $columns;
	}

	/**
	 * Render WebP column in Media Library
	 */
	public function render_media_column( $column_name, $attachment_id ) {
		if ( $column_name !== 'seovela_webp' ) {
			return;
		}

		$mime_type = get_post_mime_type( $attachment_id );
		$convertible_types = array( 'image/jpeg', 'image/png', 'image/gif' );

		if ( ! in_array( $mime_type, $convertible_types ) ) {
			echo '<span style="color:#94a3b8;">—</span>';
			return;
		}

		$file_path = get_attached_file( $attachment_id );
		$webp_path = $this->get_webp_path( $file_path );
		$has_webp = file_exists( $webp_path );

		if ( $has_webp ) {
			$original_size = filesize( $file_path );
			$webp_size = filesize( $webp_path );
			$savings = round( ( ( $original_size - $webp_size ) / $original_size ) * 100 );
			
			echo '<span style="display:inline-flex;align-items:center;gap:4px;padding:4px 8px;background:#dcfce7;color:#166534;border-radius:4px;font-size:12px;font-weight:500;">';
			echo '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
			echo esc_html( size_format( $webp_size ) ) . ' <span style="opacity:0.7;">(-' . esc_html( $savings ) . '%)</span>';
			echo '</span>';
		} else {
			echo '<button type="button" class="button button-small seovela-convert-webp-btn" data-id="' . esc_attr( $attachment_id ) . '" style="font-size:11px;">';
			echo esc_html__( 'Convert', 'seovela' );
			echo '</button>';
		}
	}

	/**
	 * Add WebP field to attachment edit screen
	 */
	public function add_attachment_webp_field( $form_fields, $post ) {
		$mime_type = get_post_mime_type( $post->ID );
		$convertible_types = array( 'image/jpeg', 'image/png', 'image/gif' );

		if ( ! in_array( $mime_type, $convertible_types ) ) {
			return $form_fields;
		}

		$file_path = get_attached_file( $post->ID );
		$webp_path = $this->get_webp_path( $file_path );
		$has_webp = file_exists( $webp_path );

		if ( $has_webp ) {
			$original_size = filesize( $file_path );
			$webp_size = filesize( $webp_path );
			$savings = round( ( ( $original_size - $webp_size ) / $original_size ) * 100 );
			
			// Get WebP URL
			$upload_dir = wp_upload_dir();
			$webp_url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $webp_path );

			$html = '<div style="display:flex;align-items:center;gap:12px;padding:10px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;">';
			$html .= '<div style="flex-shrink:0;width:60px;height:60px;border-radius:4px;overflow:hidden;background:#fff;border:1px solid #e2e8f0;">';
			$html .= '<img src="' . esc_url( $webp_url ) . '" style="width:100%;height:100%;object-fit:cover;">';
			$html .= '</div>';
			$html .= '<div style="flex:1;">';
			$html .= '<div style="font-weight:600;color:#166534;margin-bottom:4px;">' . esc_html__( 'WebP Version Available', 'seovela' ) . '</div>';
			$html .= '<div style="font-size:12px;color:#15803d;">';
			$html .= esc_html( sprintf( __( 'Size: %s (-%d%% from original)', 'seovela' ), size_format( $webp_size ), $savings ) );
			$html .= '</div>';
			$html .= '<div style="font-size:11px;color:#64748b;margin-top:4px;">';
			$html .= '<a href="' . esc_url( $webp_url ) . '" target="_blank" style="color:#6366f1;">' . esc_html__( 'View WebP', 'seovela' ) . '</a>';
			$html .= '</div>';
			$html .= '</div>';
			$html .= '</div>';
		} else {
			$html = '<div style="display:flex;align-items:center;gap:12px;padding:10px;background:#fef3c7;border:1px solid #fcd34d;border-radius:6px;">';
			$html .= '<div style="flex:1;">';
			$html .= '<div style="font-weight:500;color:#92400e;margin-bottom:8px;">' . esc_html__( 'No WebP version yet', 'seovela' ) . '</div>';
			$html .= '<button type="button" class="button seovela-convert-webp-attachment" data-id="' . esc_attr( $post->ID ) . '">';
			$html .= esc_html__( 'Convert to WebP', 'seovela' );
			$html .= '</button>';
			$html .= '</div>';
			$html .= '</div>';
			
			// Register inline script for the convert button via wp_add_inline_script.
			wp_add_inline_script( 'seovela-admin', '
				jQuery(document).ready(function($) {
					$(".seovela-convert-webp-attachment").on("click", function(e) {
						e.preventDefault();
						var $btn = $(this);
						var id = $btn.data("id");
						$btn.prop("disabled", true).text("' . esc_js( __( 'Converting...', 'seovela' ) ) . '");

						$.post(ajaxurl, {
							action: "seovela_convert_single_webp",
							attachment_id: id,
							nonce: "' . wp_create_nonce( 'seovela_convert_webp' ) . '"
						}, function(response) {
							if (response.success) {
								location.reload();
							} else {
								alert(response.data.message || "Conversion failed");
								$btn.prop("disabled", false).text("' . esc_js( __( 'Convert to WebP', 'seovela' ) ) . '");
							}
						});
					});
				});
			' );
		}

		$form_fields['seovela_webp'] = array(
			'label' => __( 'WebP Status', 'seovela' ),
			'input' => 'html',
			'html'  => $html,
		);

		return $form_fields;
	}

	/**
	 * Add script to Media Library for WebP conversion buttons
	 */
	public function media_library_webp_script() {
		wp_add_inline_style( 'seovela-admin', '
			.seovela-convert-webp-btn.loading {
				opacity: 0.7;
				pointer-events: none;
			}
			.column-seovela_webp {
				width: 100px;
			}
		' );

		wp_add_inline_script( 'seovela-admin', '
		jQuery(document).ready(function($) {
			$(document).on("click", ".seovela-convert-webp-btn", function(e) {
				e.preventDefault();
				var $btn = $(this);
				var id = $btn.data("id");

				$btn.addClass("loading").text("' . esc_js( __( 'Converting...', 'seovela' ) ) . '");

				$.post(ajaxurl, {
					action: "seovela_convert_single_webp",
					attachment_id: id,
					nonce: "' . wp_create_nonce( 'seovela_convert_webp' ) . '"
				}, function(response) {
					if (response.success) {
						// Replace button with success badge
						$btn.replaceWith(
							\'<span style="display:inline-flex;align-items:center;gap:4px;padding:4px 8px;background:#dcfce7;color:#166534;border-radius:4px;font-size:12px;font-weight:500;">\' +
							\'<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>\' +
							response.data.webp_size + \' <span style="opacity:0.7;">(-\' + response.data.savings_percent + \'%)</span>\' +
							\'</span>\'
						);
					} else {
						alert(response.data.message || "Conversion failed");
						$btn.removeClass("loading").text("' . esc_js( __( 'Convert', 'seovela' ) ) . '");
					}
				}).fail(function() {
					alert("Error during conversion");
					$btn.removeClass("loading").text("' . esc_js( __( 'Convert', 'seovela' ) ) . '");
				});
			});
		});
		' );
	}

	/**
	 * AJAX handler for single image WebP conversion from Media Library
	 */
	public function ajax_convert_single_from_media() {
		check_ajax_referer( 'seovela_convert_webp', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$attachment_id = isset( $_POST['attachment_id'] ) ? intval( wp_unslash( $_POST['attachment_id'] ) ) : 0;
		if ( ! $attachment_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid attachment ID', 'seovela' ) ) );
		}

		$result = $this->convert_to_webp( $attachment_id );

		if ( $result['success'] ) {
			wp_send_json_success( array(
				'message'         => $result['message'],
				'webp_size'       => size_format( $result['webp_size'] ),
				'savings_percent' => $result['savings_percent'],
			) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}
}
