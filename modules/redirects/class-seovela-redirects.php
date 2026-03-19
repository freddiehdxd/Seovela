<?php
/**
 * Seovela Redirects Manager
 *
 * Handles 301/302/307 redirects with logging
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seovela Redirects Class
 */
class Seovela_Redirects {

	/**
	 * Single instance
	 *
	 * @var Seovela_Redirects
	 */
	private static $instance = null;

	/**
	 * Database table name
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Get singleton instance
	 *
	 * @return Seovela_Redirects
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
		$this->table_name = $wpdb->prefix . 'seovela_redirects';

		// Hook into template_redirect to handle redirects
		add_action( 'template_redirect', array( $this, 'handle_redirect' ), 1 );
	}

	/**
	 * Create database table
	 */
	public static function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'seovela_redirects';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			source_url varchar(500) NOT NULL,
			target_url varchar(500) NOT NULL,
			redirect_type varchar(10) NOT NULL DEFAULT '301',
			regex tinyint(1) NOT NULL DEFAULT 0,
			enabled tinyint(1) NOT NULL DEFAULT 1,
			hits bigint(20) NOT NULL DEFAULT 0,
			last_hit datetime DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY source_url (source_url(191)),
			KEY enabled (enabled),
			KEY redirect_type (redirect_type)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Add a redirect
	 *
	 * @param array $data Redirect data
	 * @return int|WP_Error Redirect ID or error
	 */
	public function add_redirect( $data ) {
		global $wpdb;

		// Validate data
		$validated = $this->validate_redirect( $data );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		// Check for duplicates
		$existing = $this->get_redirect_by_source( $validated['source_url'] );
		if ( $existing ) {
			return new WP_Error(
				'duplicate_redirect',
				__( 'A redirect with this source URL already exists.', 'seovela' )
			);
		}

		// Insert redirect
		$result = $wpdb->insert(
			$this->table_name,
			array(
				'source_url'    => $validated['source_url'],
				'target_url'    => $validated['target_url'],
				'redirect_type' => $validated['redirect_type'],
				'regex'         => $validated['regex'],
				'enabled'       => $validated['enabled'],
				'created_at'    => current_time( 'mysql' ),
				'updated_at'    => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
		);

		if ( $result === false ) {
			return new WP_Error( 'db_error', __( 'Failed to add redirect.', 'seovela' ) );
		}

		do_action( 'seovela_redirect_added', $wpdb->insert_id, $validated );

		return $wpdb->insert_id;
	}

	/**
	 * Update a redirect
	 *
	 * @param int   $redirect_id Redirect ID
	 * @param array $data        Redirect data
	 * @return bool|WP_Error Success or error
	 */
	public function update_redirect( $redirect_id, $data ) {
		global $wpdb;

		// Validate data
		$validated = $this->validate_redirect( $data );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		// Check if redirect exists
		$existing = $this->get_redirect( $redirect_id );
		if ( ! $existing ) {
			return new WP_Error( 'not_found', __( 'Redirect not found.', 'seovela' ) );
		}

		// Check for duplicate source (excluding current redirect)
		$duplicate = $this->get_redirect_by_source( $validated['source_url'], $redirect_id );
		if ( $duplicate ) {
			return new WP_Error(
				'duplicate_redirect',
				__( 'Another redirect with this source URL already exists.', 'seovela' )
			);
		}

		// Update redirect
		$result = $wpdb->update(
			$this->table_name,
			array(
				'source_url'    => $validated['source_url'],
				'target_url'    => $validated['target_url'],
				'redirect_type' => $validated['redirect_type'],
				'regex'         => $validated['regex'],
				'enabled'       => $validated['enabled'],
				'updated_at'    => current_time( 'mysql' ),
			),
			array( 'id' => $redirect_id ),
			array( '%s', '%s', '%s', '%d', '%d', '%s' ),
			array( '%d' )
		);

		if ( $result === false ) {
			return new WP_Error( 'db_error', __( 'Failed to update redirect.', 'seovela' ) );
		}

		do_action( 'seovela_redirect_updated', $redirect_id, $validated );

		return true;
	}

	/**
	 * Delete a redirect
	 *
	 * @param int $redirect_id Redirect ID
	 * @return bool Success
	 */
	public function delete_redirect( $redirect_id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $redirect_id ),
			array( '%d' )
		);

		if ( $result ) {
			do_action( 'seovela_redirect_deleted', $redirect_id );
		}

		return (bool) $result;
	}

	/**
	 * Get a redirect by ID
	 *
	 * @param int $redirect_id Redirect ID
	 * @return object|null Redirect object or null
	 */
	public function get_redirect( $redirect_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d",
				$redirect_id
			)
		);
	}

	/**
	 * Get a redirect by source URL
	 *
	 * @param string $source_url Source URL
	 * @param int    $exclude_id Exclude this ID
	 * @return object|null Redirect object or null
	 */
	public function get_redirect_by_source( $source_url, $exclude_id = null ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE source_url = %s",
			$source_url
		);

		if ( $exclude_id ) {
			$sql .= $wpdb->prepare( ' AND id != %d', $exclude_id );
		}

		$sql .= ' LIMIT 1';

		return $wpdb->get_row( $sql );
	}

	/**
	 * Get all redirects
	 *
	 * @param array $args Query arguments
	 * @return array Redirects
	 */
	public function get_redirects( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'orderby' => 'created_at',
			'order'   => 'DESC',
			'limit'   => 100,
			'offset'  => 0,
			'enabled' => null,
			'search'  => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );

		if ( $args['enabled'] !== null ) {
			$where[] = $wpdb->prepare( 'enabled = %d', $args['enabled'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[] = $wpdb->prepare( '(source_url LIKE %s OR target_url LIKE %s)', $search, $search );
		}

		$where_sql = implode( ' AND ', $where );

		// Allowlist orderby and order to prevent SQL injection.
		$allowed_orderby = array( 'id', 'source_url', 'target_url', 'redirect_type', 'hits', 'enabled', 'is_regex', 'created_at', 'updated_at' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $args['order'] ) : 'DESC';

		$order_sql = sprintf( 'ORDER BY %s %s', $orderby, $order );
		$limit_sql = sprintf( 'LIMIT %d OFFSET %d', absint( $args['limit'] ), absint( $args['offset'] ) );

		$sql = "SELECT * FROM {$this->table_name} WHERE {$where_sql} {$order_sql} {$limit_sql}"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is plugin-prefixed, orderby/order are allowlisted.

		return $wpdb->get_results( $sql );
	}

	/**
	 * Get total redirect count
	 *
	 * @param array $args Query arguments
	 * @return int Total count
	 */
	public function get_total_count( $args = array() ) {
		global $wpdb;

		$where = array( '1=1' );

		if ( isset( $args['enabled'] ) && $args['enabled'] !== null ) {
			$where[] = $wpdb->prepare( 'enabled = %d', $args['enabled'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[] = $wpdb->prepare( '(source_url LIKE %s OR target_url LIKE %s)', $search, $search );
		}

		$where_sql = implode( ' AND ', $where );

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_sql}" );
	}

	/**
	 * Handle redirect on template_redirect
	 */
	public function handle_redirect() {
		// Don't redirect in admin area
		if ( is_admin() ) {
			return;
		}

		$current_url = $this->get_current_url();
		$redirect = $this->find_matching_redirect( $current_url );

		if ( ! $redirect || ! $redirect->enabled ) {
			return;
		}

		// Update hit counter
		$this->increment_hits( $redirect->id );

		// Perform redirect
		$redirect_type_code = $this->get_redirect_code( $redirect->redirect_type );
		
		do_action( 'seovela_before_redirect', $redirect, $current_url );

		wp_redirect( esc_url_raw( $redirect->target_url ), $redirect_type_code );
		exit;
	}

	/**
	 * Find matching redirect for URL
	 *
	 * @param string $url URL to match
	 * @return object|null Redirect object or null
	 */
	private function find_matching_redirect( $url ) {
		// Try to get from cache first
		$cache_key = 'redirect_' . md5( $url );
		$cached_redirect = Seovela_Cache::get( $cache_key, 'transient' );
		
		if ( false !== $cached_redirect ) {
			// Return null if explicitly cached as no redirect, otherwise return the redirect
			return ( $cached_redirect === 'none' ) ? null : $cached_redirect;
		}

		global $wpdb;

		// Parse URL to get path and query string
		$parsed_url = wp_parse_url( $url );
		$request_uri = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '/';
		if ( ! empty( $parsed_url['query'] ) ) {
			$request_uri .= '?' . $parsed_url['query'];
		}

		// First try exact match with full URL
		$redirect = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE source_url = %s AND enabled = 1 AND regex = 0 LIMIT 1",
				$url
			)
		);

		if ( $redirect ) {
			// Cache for 1 hour
			Seovela_Cache::set( $cache_key, $redirect, HOUR_IN_SECONDS, 'transient' );
			return $redirect;
		}

		// Try exact match with relative URL (path + query)
		$redirect = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE source_url = %s AND enabled = 1 AND regex = 0 LIMIT 1",
				$request_uri
			)
		);

		if ( $redirect ) {
			// Cache for 1 hour
			Seovela_Cache::set( $cache_key, $redirect, HOUR_IN_SECONDS, 'transient' );
			return $redirect;
		}

		// Try regex matches (cache these separately as they're more expensive)
		$regex_cache_key = 'redirect_regex_list';
		$regex_redirects = Seovela_Cache::get( $regex_cache_key, 'transient' );
		
		if ( false === $regex_redirects ) {
			$regex_redirects = $wpdb->get_results(
				"SELECT * FROM {$this->table_name} WHERE enabled = 1 AND regex = 1 ORDER BY created_at DESC"
			);
			// Cache regex list for 1 hour
			Seovela_Cache::set( $regex_cache_key, $regex_redirects, HOUR_IN_SECONDS, 'transient' );
		}

		foreach ( $regex_redirects as $redirect ) {
			// Try matching against both full URL and relative URL
			if ( @preg_match( $redirect->source_url, $url ) || @preg_match( $redirect->source_url, $request_uri ) ) {
				// Cache this match for 1 hour
				Seovela_Cache::set( $cache_key, $redirect, HOUR_IN_SECONDS, 'transient' );
				return $redirect;
			}
		}

		// Cache "no redirect found" for 1 hour to avoid repeated lookups
		Seovela_Cache::set( $cache_key, 'none', HOUR_IN_SECONDS, 'transient' );
		
		return null;
	}

	/**
	 * Get current URL
	 *
	 * @return string Current URL
	 */
	private function get_current_url() {
		$protocol = is_ssl() ? 'https://' : 'http://';
		$host     = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri      = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		return $protocol . $host . $uri;
	}

	/**
	 * Increment hit counter
	 *
	 * Defers the DB write to a shutdown function so the redirect response
	 * is sent immediately without waiting for the database UPDATE to finish.
	 *
	 * @param int $redirect_id Redirect ID
	 */
	private function increment_hits( $redirect_id ) {
		$table = $this->table_name;
		$time  = current_time( 'mysql' );

		register_shutdown_function( static function () use ( $redirect_id, $table, $time ) {
			global $wpdb;
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET hits = hits + 1, last_hit = %s WHERE id = %d",
					$time,
					$redirect_id
				)
			);
		} );
	}

	/**
	 * Get redirect HTTP code
	 *
	 * @param string $type Redirect type (301, 302, 307)
	 * @return int HTTP code
	 */
	private function get_redirect_code( $type ) {
		$codes = array(
			'301' => 301,
			'302' => 302,
			'307' => 307,
		);

		return isset( $codes[ $type ] ) ? $codes[ $type ] : 301;
	}

	/**
	 * Validate redirect data
	 *
	 * @param array $data Redirect data
	 * @return array|WP_Error Validated data or error
	 */
	private function validate_redirect( $data ) {
		// Source URL required
		if ( empty( $data['source_url'] ) ) {
			return new WP_Error( 'missing_source', __( 'Source URL is required.', 'seovela' ) );
		}

		// Target URL required
		if ( empty( $data['target_url'] ) ) {
			return new WP_Error( 'missing_target', __( 'Target URL is required.', 'seovela' ) );
		}

		// Sanitize URLs
		$source_url = trim( $data['source_url'] );
		$target_url = esc_url_raw( trim( $data['target_url'] ) );

		// Validate redirect type
		$redirect_type = isset( $data['redirect_type'] ) ? $data['redirect_type'] : '301';
		if ( ! in_array( $redirect_type, array( '301', '302', '307' ), true ) ) {
			$redirect_type = '301';
		}

		// Regex flag
		$regex = ! empty( $data['regex'] ) ? 1 : 0;

		// Validate regex if enabled
		if ( $regex && @preg_match( $source_url, '' ) === false ) {
			return new WP_Error( 'invalid_regex', __( 'Invalid regular expression.', 'seovela' ) );
		}

		// Enabled flag
		$enabled = ! isset( $data['enabled'] ) || $data['enabled'] ? 1 : 0;

		return array(
			'source_url'    => $source_url,
			'target_url'    => $target_url,
			'redirect_type' => $redirect_type,
			'regex'         => $regex,
			'enabled'       => $enabled,
		);
	}

	/**
	 * Export redirects to CSV
	 *
	 * @return string CSV content
	 */
	public function export_csv() {
		$redirects = $this->get_redirects( array( 'limit' => 99999 ) );

		$csv = "Source URL,Target URL,Type,Regex,Enabled,Hits,Last Hit,Created\n";

		foreach ( $redirects as $redirect ) {
			$csv .= sprintf(
				'"%s","%s","%s","%s","%s","%d","%s","%s"' . "\n",
				$this->escape_csv_value( $redirect->source_url ),
				$this->escape_csv_value( $redirect->target_url ),
				$this->escape_csv_value( $redirect->redirect_type ),
				$redirect->regex ? 'Yes' : 'No',
				$redirect->enabled ? 'Yes' : 'No',
				$redirect->hits,
				$redirect->last_hit ? $redirect->last_hit : '',
				$redirect->created_at
			);
		}

		return $csv;
	}

	/**
	 * Escape a CSV cell value to prevent formula injection.
	 *
	 * Prefixes values starting with =, +, -, @, \t, or \r with a single quote
	 * so spreadsheet applications do not interpret them as formulas.
	 *
	 * @param string $value Cell value.
	 * @return string Escaped value.
	 */
	private function escape_csv_value( $value ) {
		$value = str_replace( '"', '""', $value );

		// Prevent formula injection in spreadsheet applications.
		$first_char = isset( $value[0] ) ? $value[0] : '';
		if ( in_array( $first_char, array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			$value = "'" . $value;
		}

		return $value;
	}

	/**
	 * Import redirects from CSV
	 *
	 * @param string $csv_content CSV content
	 * @return array Result with success count and errors
	 */
	public function import_csv( $csv_content ) {
		$lines = explode( "\n", $csv_content );
		$header = array_shift( $lines ); // Remove header

		$success_count = 0;
		$errors = array();

		foreach ( $lines as $line_num => $line ) {
			$line = trim( $line );
			if ( empty( $line ) ) {
				continue;
			}

			$data = str_getcsv( $line );
			
			if ( count( $data ) < 2 ) {
				$errors[] = sprintf( __( 'Line %d: Invalid format', 'seovela' ), $line_num + 2 );
				continue;
			}

			$redirect_data = array(
				'source_url'    => $data[0],
				'target_url'    => $data[1],
				'redirect_type' => isset( $data[2] ) ? $data[2] : '301',
				'regex'         => isset( $data[3] ) && strtolower( $data[3] ) === 'yes' ? 1 : 0,
				'enabled'       => isset( $data[4] ) && strtolower( $data[4] ) === 'no' ? 0 : 1,
			);

			$result = $this->add_redirect( $redirect_data );

			if ( is_wp_error( $result ) ) {
				$errors[] = sprintf( __( 'Line %d: %s', 'seovela' ), $line_num + 2, $result->get_error_message() );
			} else {
				$success_count++;
			}
		}

		return array(
			'success' => $success_count,
			'errors'  => $errors,
		);
	}
}

