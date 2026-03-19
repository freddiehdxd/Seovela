<?php
/**
 * Seovela Import/Export Class
 *
 * Handles settings import/export and batched migration from Yoast SEO and Rank Math
 * with progress tracking, preview, backup, redirect migration, and logging.
 *
 * @package Seovela
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seovela Import/Export Class
 */
class Seovela_Import_Export {

	/**
	 * Hooks added flag.
	 *
	 * @var bool
	 */
	private static $hooks_added = false;

	/**
	 * Batch size for migration processing.
	 *
	 * @var int
	 */
	const BATCH_SIZE = 100;

	/**
	 * Yoast meta key mapping.
	 *
	 * Source key => Seovela key.
	 *
	 * @var array
	 */
	private $yoast_meta_map = array(
		'_yoast_wpseo_title'                 => '_seovela_meta_title',
		'_yoast_wpseo_metadesc'              => '_seovela_meta_description',
		'_yoast_wpseo_focuskw'               => '_seovela_focus_keyword',
		'_yoast_wpseo_canonical'             => '_seovela_canonical_url',
		'_yoast_wpseo_opengraph-title'       => '_seovela_og_title',
		'_yoast_wpseo_opengraph-description' => '_seovela_og_description',
		'_yoast_wpseo_opengraph-image'       => '_seovela_og_image',
		'_yoast_wpseo_schema_page_type'      => '_seovela_schema_type',
	);

	/**
	 * Yoast boolean meta key mapping.
	 *
	 * Source key => array( Seovela key, source value that means true ).
	 *
	 * @var array
	 */
	private $yoast_bool_map = array(
		'_yoast_wpseo_meta-robots-noindex'  => array( '_seovela_noindex', '1' ),
		'_yoast_wpseo_meta-robots-nofollow' => array( '_seovela_nofollow', '1' ),
	);

	/**
	 * Rank Math meta key mapping.
	 *
	 * Source key => Seovela key.
	 *
	 * @var array
	 */
	private $rankmath_meta_map = array(
		'rank_math_title'                => '_seovela_meta_title',
		'rank_math_description'          => '_seovela_meta_description',
		'rank_math_focus_keyword'        => '_seovela_focus_keyword',
		'rank_math_canonical_url'        => '_seovela_canonical_url',
		'rank_math_facebook_title'       => '_seovela_og_title',
		'rank_math_facebook_description' => '_seovela_og_description',
		'rank_math_facebook_image'       => '_seovela_og_image',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Prevent duplicate hooks.
		if ( self::$hooks_added ) {
			return;
		}
		self::$hooks_added = true;

		// Settings import/export (synchronous, admin_init).
		add_action( 'admin_init', array( $this, 'handle_export' ) );
		add_action( 'admin_init', array( $this, 'handle_import' ) );

		// AJAX migration endpoints.
		add_action( 'wp_ajax_seovela_migration_detect', array( $this, 'ajax_migration_detect' ) );
		add_action( 'wp_ajax_seovela_migration_preview', array( $this, 'ajax_migration_preview' ) );
		add_action( 'wp_ajax_seovela_migration_backup', array( $this, 'ajax_migration_backup' ) );
		add_action( 'wp_ajax_seovela_migration_run', array( $this, 'ajax_migration_run' ) );
	}

	// =========================================================================
	// Settings Export / Import (unchanged from original, kept for completeness)
	// =========================================================================

	/**
	 * Get all Seovela settings.
	 *
	 * @return array
	 */
	public function get_all_settings() {
		global $wpdb;

		$settings = array();
		$options  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
				'seovela_%'
			)
		);

		foreach ( $options as $option ) {
			$settings[ $option->option_name ] = maybe_unserialize( $option->option_value );
		}

		return $settings;
	}

	/**
	 * Export settings to JSON.
	 */
	public function handle_export() {
		if ( ! isset( $_GET['seovela_export_settings'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'seovela_export_settings' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export settings.', 'seovela' ) );
		}

		$settings    = $this->get_all_settings();
		$export_data = array(
			'version'   => SEOVELA_VERSION,
			'timestamp' => current_time( 'mysql' ),
			'settings'  => $settings,
		);

		$filename = 'seovela-settings-' . gmdate( 'Y-m-d' ) . '.json';

		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		echo wp_json_encode( $export_data );
		exit;
	}

	/**
	 * Import settings from JSON.
	 */
	public function handle_import() {
		if ( ! isset( $_POST['seovela_import_settings'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'seovela_import_settings' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to import settings.', 'seovela' ) );
		}

		if ( ! isset( $_FILES['seovela_import_file'] ) || $_FILES['seovela_import_file']['error'] !== UPLOAD_ERR_OK ) {
			add_settings_error( 'seovela_import', 'import_error', __( 'Error uploading file. Please try again.', 'seovela' ), 'error' );
			return;
		}

		$file = $_FILES['seovela_import_file'];

		// Validate file size (max 1 MB).
		if ( $file['size'] > MB_IN_BYTES ) {
			add_settings_error( 'seovela_import', 'import_error', __( 'File too large. Maximum size is 1 MB.', 'seovela' ), 'error' );
			return;
		}

		// Validate file extension.
		$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( 'json' !== $ext ) {
			add_settings_error( 'seovela_import', 'import_error', __( 'Invalid file type. Please upload a JSON file.', 'seovela' ), 'error' );
			return;
		}

		$file_content = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$import_data  = json_decode( $file_content, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			add_settings_error( 'seovela_import', 'import_error', __( 'Invalid JSON file. Please check the file format.', 'seovela' ), 'error' );
			return;
		}

		if ( ! isset( $import_data['settings'] ) || ! is_array( $import_data['settings'] ) ) {
			add_settings_error( 'seovela_import', 'import_error', __( 'Invalid settings file format.', 'seovela' ), 'error' );
			return;
		}

		// Build an allowlist from existing seovela_* options in the database.
		// This ensures we only overwrite options that already exist, preventing
		// creation of arbitrary new options via a crafted import file.
		$existing_keys = $this->get_existing_option_keys();

		// Sensitive keys that must never be overwritten via import.
		$blocked_keys = array(
			'seovela_openai_api_key',
			'seovela_gemini_api_key',
			'seovela_claude_api_key',
			'seovela_gsc_access_token',
			'seovela_gsc_refresh_token',
			'seovela_gsc_token',
		);

		$imported = 0;
		$skipped  = 0;

		foreach ( $import_data['settings'] as $key => $value ) {
			// Must start with seovela_ prefix.
			if ( strpos( $key, 'seovela_' ) !== 0 ) {
				continue;
			}

			// Must not be a sensitive key.
			if ( in_array( $key, $blocked_keys, true ) ) {
				$skipped++;
				continue;
			}

			// Must be an existing option (or a known module-enabled flag).
			if ( ! in_array( $key, $existing_keys, true ) ) {
				$skipped++;
				continue;
			}

			// Sanitize the value recursively.
			$value = $this->sanitize_import_value( $value );

			update_option( $key, $value );
			$imported++;
		}

		$message = sprintf(
			/* translators: 1: number imported, 2: number skipped */
			__( 'Successfully imported %1$d settings. %2$d skipped (sensitive or unrecognized keys).', 'seovela' ),
			$imported,
			$skipped
		);

		add_settings_error( 'seovela_import', 'import_success', $message, 'success' );
	}

	/**
	 * Get all existing seovela_* option keys from the database.
	 *
	 * @return array
	 */
	private function get_existing_option_keys() {
		global $wpdb;

		$keys = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				'seovela_%'
			)
		);

		return is_array( $keys ) ? $keys : array();
	}

	/**
	 * Recursively sanitize an import value.
	 *
	 * Strings are sanitized, booleans/integers/floats are type-cast,
	 * arrays are recursively processed, and objects are rejected.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return mixed
	 */
	private function sanitize_import_value( $value ) {
		if ( is_string( $value ) ) {
			// Allow URLs to keep their slashes.
			if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
				return esc_url_raw( $value );
			}
			return sanitize_text_field( $value );
		}

		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_int( $value ) ) {
			return intval( $value );
		}

		if ( is_float( $value ) ) {
			return floatval( $value );
		}

		if ( is_array( $value ) ) {
			$sanitized = array();
			foreach ( $value as $k => $v ) {
				$sanitized_key = is_string( $k ) ? sanitize_key( $k ) : intval( $k );
				$sanitized[ $sanitized_key ] = $this->sanitize_import_value( $v );
			}
			return $sanitized;
		}

		// Reject objects and other types.
		return '';
	}

	// =========================================================================
	// AJAX: Migration Detect
	// =========================================================================

	/**
	 * AJAX handler: detect available SEO plugins and data.
	 *
	 * Returns which plugins have data in the database (even if not currently active)
	 * by checking for known meta keys and tables.
	 */
	public function ajax_migration_detect() {
		check_ajax_referer( 'seovela_migration', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'seovela' ) ), 403 );
		}

		global $wpdb;

		// Detect Yoast data.
		$yoast_active = defined( 'WPSEO_VERSION' );
		$yoast_posts  = (int) $wpdb->get_var(
			"SELECT COUNT( DISTINCT post_id ) FROM {$wpdb->postmeta}
			 WHERE meta_key IN ( '_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw',
			                     '_yoast_wpseo_canonical', '_yoast_wpseo_opengraph-title',
			                     '_yoast_wpseo_opengraph-description', '_yoast_wpseo_opengraph-image',
			                     '_yoast_wpseo_schema_page_type',
			                     '_yoast_wpseo_meta-robots-noindex', '_yoast_wpseo_meta-robots-nofollow' )
			 AND meta_value != ''"
		);

		// Detect Rank Math data.
		$rankmath_active = defined( 'RANK_MATH_VERSION' );
		$rankmath_posts  = (int) $wpdb->get_var(
			"SELECT COUNT( DISTINCT post_id ) FROM {$wpdb->postmeta}
			 WHERE meta_key IN ( 'rank_math_title', 'rank_math_description', 'rank_math_focus_keyword',
			                     'rank_math_canonical_url', 'rank_math_facebook_title',
			                     'rank_math_facebook_description', 'rank_math_facebook_image',
			                     'rank_math_robots' )
			 AND meta_value != ''"
		);

		// Detect Rank Math redirections table.
		$rm_redirections_table = $wpdb->prefix . 'rank_math_redirections';
		$rm_redirects_count    = 0;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $rm_redirections_table ) );
		if ( $table_exists ) {
			$rm_redirects_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$rm_redirections_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// Detect Rank Math 404 logs table.
		$rm_404_table = $wpdb->prefix . 'rank_math_404_log';
		$rm_404_count = 0;
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $rm_404_table ) );
		if ( $table_exists ) {
			$rm_404_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$rm_404_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		wp_send_json_success( array(
			'yoast'    => array(
				'active'     => $yoast_active,
				'has_data'   => $yoast_posts > 0,
				'post_count' => $yoast_posts,
			),
			'rankmath' => array(
				'active'          => $rankmath_active,
				'has_data'        => $rankmath_posts > 0 || $rm_redirects_count > 0 || $rm_404_count > 0,
				'post_count'      => $rankmath_posts,
				'redirects_count' => $rm_redirects_count,
				'404_count'       => $rm_404_count,
			),
		) );
	}

	// =========================================================================
	// AJAX: Migration Preview
	// =========================================================================

	/**
	 * AJAX handler: preview what will be migrated.
	 *
	 * Accepts POST param `source` (yoast|rankmath).
	 * Returns per-field counts so the user knows exactly what is about to happen.
	 */
	public function ajax_migration_preview() {
		check_ajax_referer( 'seovela_migration', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'seovela' ) ), 403 );
		}

		$source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';

		if ( ! in_array( $source, array( 'yoast', 'rankmath' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid migration source.', 'seovela' ) ) );
		}

		global $wpdb;

		$preview = array(
			'source'     => $source,
			'fields'     => array(),
			'total_posts' => 0,
			'redirects'  => 0,
			'404_logs'   => 0,
		);

		if ( 'yoast' === $source ) {
			$preview = $this->preview_yoast( $preview );
		} else {
			$preview = $this->preview_rankmath( $preview );
		}

		wp_send_json_success( $preview );
	}

	/**
	 * Build preview data for Yoast migration.
	 *
	 * @param array $preview Preview structure.
	 * @return array
	 */
	private function preview_yoast( $preview ) {
		global $wpdb;

		// Count each string-mapped meta key.
		foreach ( $this->yoast_meta_map as $yoast_key => $seovela_key ) {
			$count = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != ''",
				$yoast_key
			) );

			$preview['fields'][ $yoast_key ] = array(
				'target' => $seovela_key,
				'count'  => $count,
			);
		}

		// Count boolean meta keys.
		foreach ( $this->yoast_bool_map as $yoast_key => $config ) {
			$count = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s",
				$yoast_key,
				$config[1]
			) );

			$preview['fields'][ $yoast_key ] = array(
				'target' => $config[0],
				'count'  => $count,
			);
		}

		// Total distinct posts with any Yoast data.
		$all_yoast_keys = array_merge(
			array_keys( $this->yoast_meta_map ),
			array_keys( $this->yoast_bool_map )
		);
		$placeholders = implode( ', ', array_fill( 0, count( $all_yoast_keys ), '%s' ) );

		$preview['total_posts'] = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT( DISTINCT post_id ) FROM {$wpdb->postmeta}
			 WHERE meta_key IN ( {$placeholders} ) AND meta_value != ''", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			...$all_yoast_keys
		) );

		return $preview;
	}

	/**
	 * Build preview data for Rank Math migration.
	 *
	 * @param array $preview Preview structure.
	 * @return array
	 */
	private function preview_rankmath( $preview ) {
		global $wpdb;

		// Count each string-mapped meta key.
		foreach ( $this->rankmath_meta_map as $rm_key => $seovela_key ) {
			$count = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != ''",
				$rm_key
			) );

			$preview['fields'][ $rm_key ] = array(
				'target' => $seovela_key,
				'count'  => $count,
			);
		}

		// Robots (noindex / nofollow derived from rank_math_robots array).
		$robots_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'rank_math_robots' AND meta_value != ''"
		);
		$preview['fields']['rank_math_robots'] = array(
			'target' => '_seovela_noindex / _seovela_nofollow',
			'count'  => $robots_count,
		);

		// Total distinct posts.
		$all_rm_keys    = array_merge( array_keys( $this->rankmath_meta_map ), array( 'rank_math_robots' ) );
		$placeholders   = implode( ', ', array_fill( 0, count( $all_rm_keys ), '%s' ) );
		$preview['total_posts'] = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT( DISTINCT post_id ) FROM {$wpdb->postmeta}
			 WHERE meta_key IN ( {$placeholders} ) AND meta_value != ''", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			...$all_rm_keys
		) );

		// Rank Math redirects.
		$rm_redirections_table = $wpdb->prefix . 'rank_math_redirections';
		$table_exists          = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $rm_redirections_table ) );
		if ( $table_exists ) {
			$preview['redirects'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$rm_redirections_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// Rank Math 404 logs.
		$rm_404_table = $wpdb->prefix . 'rank_math_404_log';
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $rm_404_table ) );
		if ( $table_exists ) {
			$preview['404_logs'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$rm_404_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		return $preview;
	}

	// =========================================================================
	// AJAX: Migration Backup
	// =========================================================================

	/**
	 * AJAX handler: create backup of current Seovela meta before migration.
	 *
	 * Stores a snapshot of all _seovela_* post meta as a wp_option
	 * keyed `seovela_migration_backup_{timestamp}`.
	 */
	public function ajax_migration_backup() {
		check_ajax_referer( 'seovela_migration', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'seovela' ) ), 403 );
		}

		global $wpdb;

		// Gather current Seovela post meta.
		$rows = $wpdb->get_results(
			"SELECT post_id, meta_key, meta_value
			 FROM {$wpdb->postmeta}
			 WHERE meta_key LIKE '\_seovela\_%'
			 ORDER BY post_id ASC"
		);

		$backup = array();
		foreach ( $rows as $row ) {
			$backup[] = array(
				'post_id'    => (int) $row->post_id,
				'meta_key'   => $row->meta_key,
				'meta_value' => $row->meta_value,
			);
		}

		// Also backup Seovela options.
		$options = $this->get_all_settings();

		$timestamp  = time();
		$option_key = 'seovela_migration_backup_' . $timestamp;

		$payload = array(
			'timestamp'  => $timestamp,
			'date'       => current_time( 'mysql' ),
			'version'    => defined( 'SEOVELA_VERSION' ) ? SEOVELA_VERSION : 'unknown',
			'post_meta'  => $backup,
			'options'    => $options,
			'meta_count' => count( $backup ),
		);

		// Store as autoloaded=no to keep options table lean.
		$saved = update_option( $option_key, $payload, false );

		if ( ! $saved ) {
			// update_option returns false if value unchanged OR on failure.
			// Since this is a new key, false means failure.
			$existing = get_option( $option_key );
			if ( false === $existing ) {
				wp_send_json_error( array( 'message' => __( 'Failed to save backup.', 'seovela' ) ) );
			}
		}

		wp_send_json_success( array(
			'backup_key'  => $option_key,
			'meta_count'  => count( $backup ),
			'option_count' => count( $options ),
			'timestamp'   => $timestamp,
		) );
	}

	// =========================================================================
	// AJAX: Migration Run (Batched)
	// =========================================================================

	/**
	 * AJAX handler: run a single migration batch.
	 *
	 * Accepts POST params:
	 *   - source (yoast|rankmath)
	 *   - offset (int, default 0)
	 *   - step   (posts|redirects|404s|settings) - which phase to run
	 *
	 * Returns:
	 *   - processed (int)
	 *   - skipped (array of log messages)
	 *   - has_more (bool)
	 *   - next_offset (int)
	 */
	public function ajax_migration_run() {
		check_ajax_referer( 'seovela_migration', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'seovela' ) ), 403 );
		}

		$source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
		$offset = isset( $_POST['offset'] ) ? absint( wp_unslash( $_POST['offset'] ) ) : 0;
		$step   = isset( $_POST['step'] ) ? sanitize_text_field( wp_unslash( $_POST['step'] ) ) : 'posts';

		if ( ! in_array( $source, array( 'yoast', 'rankmath' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid migration source.', 'seovela' ) ) );
		}

		$result = array(
			'processed'   => 0,
			'skipped'     => array(),
			'has_more'    => false,
			'next_offset' => 0,
			'step'        => $step,
		);

		switch ( $step ) {
			case 'posts':
				$result = $this->run_post_batch( $source, $offset, $result );
				break;

			case 'redirects':
				$result = $this->run_redirect_migration( $source, $result );
				break;

			case '404s':
				$result = $this->run_404_migration( $source, $result );
				break;

			case 'settings':
				$result = $this->run_settings_migration( $source, $result );
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Invalid migration step.', 'seovela' ) ) );
		}

		wp_send_json_success( $result );
	}

	// =========================================================================
	// Batch Processors
	// =========================================================================

	/**
	 * Process a batch of posts for migration.
	 *
	 * Uses a direct DB query to get post IDs that have source meta, paginated
	 * with LIMIT/OFFSET so we never load the full set.
	 *
	 * @param string $source yoast|rankmath.
	 * @param int    $offset Current offset.
	 * @param array  $result Result accumulator.
	 * @return array
	 */
	private function run_post_batch( $source, $offset, $result ) {
		global $wpdb;

		// Build list of source meta keys.
		if ( 'yoast' === $source ) {
			$source_keys = array_merge(
				array_keys( $this->yoast_meta_map ),
				array_keys( $this->yoast_bool_map )
			);
		} else {
			$source_keys = array_merge(
				array_keys( $this->rankmath_meta_map ),
				array( 'rank_math_robots' )
			);
		}

		$placeholders = implode( ', ', array_fill( 0, count( $source_keys ), '%s' ) );

		// Get distinct post IDs for this batch.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$post_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT post_id FROM {$wpdb->postmeta}
			 WHERE meta_key IN ( {$placeholders} ) AND meta_value != ''
			 ORDER BY post_id ASC
			 LIMIT %d OFFSET %d",
			...array_merge( $source_keys, array( self::BATCH_SIZE, $offset ) )
		) );

		if ( empty( $post_ids ) ) {
			$result['has_more']    = false;
			$result['next_offset'] = $offset;
			return $result;
		}

		foreach ( $post_ids as $post_id ) {
			$post_id = (int) $post_id;

			if ( 'yoast' === $source ) {
				$this->migrate_yoast_post( $post_id, $result );
			} else {
				$this->migrate_rankmath_post( $post_id, $result );
			}

			$result['processed']++;
		}

		// Determine if there are more posts.
		$next_offset = $offset + self::BATCH_SIZE;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$remaining = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT( DISTINCT post_id ) FROM {$wpdb->postmeta}
			 WHERE meta_key IN ( {$placeholders} ) AND meta_value != ''
			 ORDER BY post_id ASC
			 LIMIT 1 OFFSET %d",
			...array_merge( $source_keys, array( $next_offset ) )
		) );

		$result['has_more']    = $remaining > 0;
		$result['next_offset'] = $next_offset;

		return $result;
	}

	/**
	 * Migrate a single post from Yoast.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $result  Result accumulator (passed by reference).
	 */
	private function migrate_yoast_post( $post_id, &$result ) {
		// String fields.
		foreach ( $this->yoast_meta_map as $yoast_key => $seovela_key ) {
			$value = get_post_meta( $post_id, $yoast_key, true );

			if ( '' === $value || false === $value ) {
				continue;
			}

			// Sanitize based on field type.
			if ( '_seovela_meta_description' === $seovela_key || '_seovela_og_description' === $seovela_key ) {
				$value = sanitize_textarea_field( $value );
			} elseif ( '_seovela_og_image' === $seovela_key || '_seovela_canonical_url' === $seovela_key ) {
				$value = esc_url_raw( $value );
			} else {
				$value = sanitize_text_field( $value );
			}

			if ( ! empty( $value ) ) {
				update_post_meta( $post_id, $seovela_key, $value );
			}
		}

		// Boolean fields.
		foreach ( $this->yoast_bool_map as $yoast_key => $config ) {
			$seovela_key  = $config[0];
			$true_value   = $config[1];
			$source_value = get_post_meta( $post_id, $yoast_key, true );

			if ( (string) $source_value === (string) $true_value ) {
				update_post_meta( $post_id, $seovela_key, true );
			}
		}
	}

	/**
	 * Migrate a single post from Rank Math.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $result  Result accumulator (passed by reference).
	 */
	private function migrate_rankmath_post( $post_id, &$result ) {
		// String fields.
		foreach ( $this->rankmath_meta_map as $rm_key => $seovela_key ) {
			$value = get_post_meta( $post_id, $rm_key, true );

			if ( '' === $value || false === $value ) {
				continue;
			}

			// Sanitize based on field type.
			if ( '_seovela_meta_description' === $seovela_key || '_seovela_og_description' === $seovela_key ) {
				$value = sanitize_textarea_field( $value );
			} elseif ( '_seovela_og_image' === $seovela_key || '_seovela_canonical_url' === $seovela_key ) {
				$value = esc_url_raw( $value );
			} else {
				$value = sanitize_text_field( $value );
			}

			if ( ! empty( $value ) ) {
				update_post_meta( $post_id, $seovela_key, $value );
			}
		}

		// Robots meta (stored as serialized array in Rank Math).
		$rm_robots = get_post_meta( $post_id, 'rank_math_robots', true );

		if ( is_array( $rm_robots ) ) {
			if ( in_array( 'noindex', $rm_robots, true ) ) {
				update_post_meta( $post_id, '_seovela_noindex', true );
			}
			if ( in_array( 'nofollow', $rm_robots, true ) ) {
				update_post_meta( $post_id, '_seovela_nofollow', true );
			}
		} elseif ( is_string( $rm_robots ) && ! empty( $rm_robots ) ) {
			// Sometimes stored as serialized string; try to unserialize.
			$unserialized = maybe_unserialize( $rm_robots );
			if ( is_array( $unserialized ) ) {
				if ( in_array( 'noindex', $unserialized, true ) ) {
					update_post_meta( $post_id, '_seovela_noindex', true );
				}
				if ( in_array( 'nofollow', $unserialized, true ) ) {
					update_post_meta( $post_id, '_seovela_nofollow', true );
				}
			} else {
				$result['skipped'][] = sprintf(
					/* translators: 1: post ID, 2: raw meta value */
					__( 'Post %1$d: Could not parse rank_math_robots value "%2$s"', 'seovela' ),
					$post_id,
					substr( $rm_robots, 0, 100 )
				);
			}
		}
	}

	// =========================================================================
	// Redirect Migration (Rank Math only)
	// =========================================================================

	/**
	 * Migrate Rank Math redirections to seovela_redirects table.
	 *
	 * @param string $source Source plugin.
	 * @param array  $result Result accumulator.
	 * @return array
	 */
	private function run_redirect_migration( $source, $result ) {
		if ( 'rankmath' !== $source ) {
			$result['skipped'][] = __( 'Redirect migration is only available for Rank Math.', 'seovela' );
			return $result;
		}

		global $wpdb;

		$rm_table = $wpdb->prefix . 'rank_math_redirections';
		$sv_table = $wpdb->prefix . 'seovela_redirects';

		// Verify source table exists.
		$source_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $rm_table ) );
		if ( ! $source_exists ) {
			$result['skipped'][] = __( 'Rank Math redirections table not found.', 'seovela' );
			return $result;
		}

		// Verify destination table exists.
		$dest_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $sv_table ) );
		if ( ! $dest_exists ) {
			$result['skipped'][] = __( 'Seovela redirects table not found. Please ensure the Redirects module is enabled.', 'seovela' );
			return $result;
		}

		// Rank Math stores sources in a separate table or serialized in the url_to column.
		// The rank_math_redirections table has: id, url_to, header_code, hits, status, created, updated, sources (serialized).
		// We also check for rank_math_redirections_cache which some versions use.
		$redirections = $wpdb->get_results( "SELECT * FROM {$rm_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( empty( $redirections ) ) {
			$result['skipped'][] = __( 'No Rank Math redirections found to migrate.', 'seovela' );
			return $result;
		}

		$now = current_time( 'mysql' );

		foreach ( $redirections as $redirect ) {
			$target_url    = isset( $redirect->url_to ) ? $redirect->url_to : '';
			$redirect_type = isset( $redirect->header_code ) ? (string) $redirect->header_code : '301';
			$enabled       = isset( $redirect->status ) ? ( 'active' === $redirect->status ? 1 : 0 ) : 1;
			$hits          = isset( $redirect->hits ) ? (int) $redirect->hits : 0;
			$created       = ! empty( $redirect->created ) ? $redirect->created : $now;
			$updated       = ! empty( $redirect->updated ) ? $redirect->updated : $now;

			// Rank Math stores sources as serialized array of objects with 'pattern', 'comparison' keys.
			$sources = array();
			if ( isset( $redirect->sources ) ) {
				$decoded = maybe_unserialize( $redirect->sources );
				if ( is_array( $decoded ) ) {
					$sources = $decoded;
				}
			}

			if ( empty( $sources ) && empty( $target_url ) ) {
				$result['skipped'][] = sprintf(
					/* translators: %d: redirect ID */
					__( 'Redirect ID %d: No source URLs or target URL found.', 'seovela' ),
					$redirect->id
				);
				continue;
			}

			// Each Rank Math redirect can have multiple source patterns.
			foreach ( $sources as $source_entry ) {
				$source_url = '';
				$is_regex   = 0;

				if ( is_array( $source_entry ) ) {
					$source_url = isset( $source_entry['pattern'] ) ? $source_entry['pattern'] : '';
					$comparison = isset( $source_entry['comparison'] ) ? $source_entry['comparison'] : 'exact';
					$is_regex   = ( 'regex' === $comparison ) ? 1 : 0;
				} elseif ( is_string( $source_entry ) ) {
					$source_url = $source_entry;
				}

				if ( empty( $source_url ) ) {
					$result['skipped'][] = sprintf(
						/* translators: %d: redirect ID */
						__( 'Redirect ID %d: Empty source URL in source entry.', 'seovela' ),
						$redirect->id
					);
					continue;
				}

				// Check for duplicate.
				$existing = $wpdb->get_var( $wpdb->prepare(
					"SELECT id FROM {$sv_table} WHERE source_url = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$source_url
				) );

				if ( $existing ) {
					$result['skipped'][] = sprintf(
						/* translators: %s: source URL */
						__( 'Redirect "%s": Already exists in Seovela, skipped.', 'seovela' ),
						$source_url
					);
					continue;
				}

				// Normalize redirect type.
				if ( ! in_array( $redirect_type, array( '301', '302', '307' ), true ) ) {
					$redirect_type = '301';
				}

				$inserted = $wpdb->insert(
					$sv_table,
					array(
						'source_url'    => $source_url,
						'target_url'    => esc_url_raw( $target_url ),
						'redirect_type' => $redirect_type,
						'regex'         => $is_regex,
						'enabled'       => $enabled,
						'hits'          => $hits,
						'last_hit'      => null,
						'created_at'    => $created,
						'updated_at'    => $updated,
					),
					array( '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s' )
				);

				if ( false === $inserted ) {
					$result['skipped'][] = sprintf(
						/* translators: %s: source URL */
						__( 'Redirect "%s": Database insert failed.', 'seovela' ),
						$source_url
					);
				} else {
					$result['processed']++;
				}
			}
		}

		return $result;
	}

	// =========================================================================
	// 404 Log Migration (Rank Math only)
	// =========================================================================

	/**
	 * Migrate Rank Math 404 logs to seovela_404_logs table.
	 *
	 * @param string $source Source plugin.
	 * @param array  $result Result accumulator.
	 * @return array
	 */
	private function run_404_migration( $source, $result ) {
		if ( 'rankmath' !== $source ) {
			$result['skipped'][] = __( '404 log migration is only available for Rank Math.', 'seovela' );
			return $result;
		}

		global $wpdb;

		$rm_table = $wpdb->prefix . 'rank_math_404_log';
		$sv_table = $wpdb->prefix . 'seovela_404_logs';

		// Verify source table exists.
		$source_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $rm_table ) );
		if ( ! $source_exists ) {
			$result['skipped'][] = __( 'Rank Math 404 log table not found.', 'seovela' );
			return $result;
		}

		// Verify destination table exists.
		$dest_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $sv_table ) );
		if ( ! $dest_exists ) {
			$result['skipped'][] = __( 'Seovela 404 logs table not found. Please ensure the 404 Monitor module is enabled.', 'seovela' );
			return $result;
		}

		// Rank Math 404_log table: id, uri, accessed, times_accessed, user_agent, referer.
		$logs = $wpdb->get_results( "SELECT * FROM {$rm_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( empty( $logs ) ) {
			$result['skipped'][] = __( 'No Rank Math 404 logs found to migrate.', 'seovela' );
			return $result;
		}

		foreach ( $logs as $log ) {
			$url        = isset( $log->uri ) ? $log->uri : '';
			$accessed   = isset( $log->accessed ) ? $log->accessed : current_time( 'mysql' );
			$count      = isset( $log->times_accessed ) ? (int) $log->times_accessed : 1;
			$user_agent = isset( $log->user_agent ) ? sanitize_text_field( $log->user_agent ) : null;
			$referer    = isset( $log->referer ) ? esc_url_raw( $log->referer ) : null;

			if ( empty( $url ) ) {
				$result['skipped'][] = sprintf(
					/* translators: %d: log ID */
					__( '404 log ID %d: Empty URL, skipped.', 'seovela' ),
					isset( $log->id ) ? $log->id : 0
				);
				continue;
			}

			// Ensure URL is absolute.
			if ( strpos( $url, 'http' ) !== 0 ) {
				$url = home_url( '/' . ltrim( $url, '/' ) );
			}

			// Check for duplicate.
			$existing = $wpdb->get_row( $wpdb->prepare(
				"SELECT id, count FROM {$sv_table} WHERE url = %s AND resolved = 0 LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$url
			) );

			if ( $existing ) {
				// Merge hit counts.
				$wpdb->update(
					$sv_table,
					array(
						'count'    => (int) $existing->count + $count,
						'last_hit' => $accessed,
					),
					array( 'id' => $existing->id ),
					array( '%d', '%s' ),
					array( '%d' )
				);
				$result['processed']++;
				continue;
			}

			$inserted = $wpdb->insert(
				$sv_table,
				array(
					'url'        => $url,
					'referer'    => $referer,
					'user_agent' => $user_agent,
					'ip_address' => null,
					'count'      => $count,
					'first_hit'  => $accessed,
					'last_hit'   => $accessed,
					'resolved'   => 0,
				),
				array( '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d' )
			);

			if ( false === $inserted ) {
				$result['skipped'][] = sprintf(
					/* translators: %s: 404 URL */
					__( '404 "%s": Database insert failed.', 'seovela' ),
					substr( $url, 0, 100 )
				);
			} else {
				$result['processed']++;
			}
		}

		return $result;
	}

	// =========================================================================
	// Global Settings Migration
	// =========================================================================

	/**
	 * Migrate global settings (title separator, etc.).
	 *
	 * @param string $source Source plugin.
	 * @param array  $result Result accumulator.
	 * @return array
	 */
	private function run_settings_migration( $source, $result ) {
		if ( 'yoast' === $source ) {
			$yoast_titles = get_option( 'wpseo_titles', array() );
			if ( isset( $yoast_titles['separator'] ) && ! empty( $yoast_titles['separator'] ) ) {
				// Yoast stores separators as entity names like 'sc-dash'. Map common ones.
				$separator = $this->map_yoast_separator( $yoast_titles['separator'] );
				update_option( 'seovela_separator_character', $separator );
				$result['processed']++;
			} else {
				$result['skipped'][] = __( 'No Yoast title separator setting found.', 'seovela' );
			}
		} elseif ( 'rankmath' === $source ) {
			// Rank Math stores options in rank-math-options-titles or similar.
			$rm_titles = get_option( 'rank-math-options-titles', array() );
			if ( empty( $rm_titles ) ) {
				$rm_titles = get_option( 'rank_math_options', array() );
			}

			if ( isset( $rm_titles['title_separator'] ) && ! empty( $rm_titles['title_separator'] ) ) {
				update_option( 'seovela_separator_character', sanitize_text_field( $rm_titles['title_separator'] ) );
				$result['processed']++;
			} else {
				$result['skipped'][] = __( 'No Rank Math title separator setting found.', 'seovela' );
			}
		}

		return $result;
	}

	/**
	 * Map Yoast separator entity name to actual character.
	 *
	 * @param string $separator Yoast separator identifier.
	 * @return string
	 */
	private function map_yoast_separator( $separator ) {
		$map = array(
			'sc-dash'   => '-',
			'sc-ndash'  => "\xE2\x80\x93", // –
			'sc-mdash'  => "\xE2\x80\x94", // —
			'sc-colon'  => ':',
			'sc-middot' => "\xC2\xB7",      // ·
			'sc-bull'   => "\xE2\x80\xA2",  // •
			'sc-star'   => '*',
			'sc-smstar' => "\xE2\x80\xA2",  // •
			'sc-pipe'   => '|',
			'sc-tilde'  => '~',
			'sc-laquo'  => "\xC2\xAB",      // «
			'sc-raquo'  => "\xC2\xBB",      // »
			'sc-lt'     => '<',
			'sc-gt'     => '>',
		);

		return isset( $map[ $separator ] ) ? $map[ $separator ] : sanitize_text_field( $separator );
	}

	// =========================================================================
	// Legacy Compatibility
	// =========================================================================

	/**
	 * Get migration status.
	 *
	 * @return array
	 */
	public function get_migration_status() {
		return array(
			'yoast'    => defined( 'WPSEO_VERSION' ),
			'rankmath' => defined( 'RANK_MATH_VERSION' ),
		);
	}
}
