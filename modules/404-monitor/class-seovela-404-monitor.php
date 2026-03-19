<?php
/**
 * Seovela 404 Monitor
 *
 * Logs 404 errors and suggests redirects
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seovela 404 Monitor Class
 */
class Seovela_404_Monitor {

	/**
	 * Single instance
	 *
	 * @var Seovela_404_Monitor
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
	 * @return Seovela_404_Monitor
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
		$this->table_name = $wpdb->prefix . 'seovela_404_logs';

		// Hook into template_redirect to log 404s and handle global redirect
		add_action( 'template_redirect', array( $this, 'handle_404' ), 1 );
		add_action( 'template_redirect', array( $this, 'log_404' ), 999 );

		// Schedule cleanup
		add_action( 'seovela_404_cleanup', array( $this, 'cleanup_old_logs' ) );
		
		if ( ! wp_next_scheduled( 'seovela_404_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'seovela_404_cleanup' );
		}
	}

	/**
	 * Handle 404 global redirect
	 */
	public function handle_404() {
		if ( ! is_404() ) {
			return;
		}

		// Don't redirect in admin
		if ( is_admin() ) {
			return;
		}

		// Get settings
		$settings = get_option( 'seovela_404_settings', array() );
		
		// Check if global redirect is enabled
		if ( empty( $settings['redirect_enabled'] ) || empty( $settings['redirect_url'] ) ) {
			return;
		}

		// Prevent redirect loops
		$current_url = $this->get_current_url();
		$redirect_url = esc_url_raw( $settings['redirect_url'] );
		
		if ( trailingslashit( $current_url ) === trailingslashit( $redirect_url ) ) {
			return;
		}

		// Perform redirect — use wp_safe_redirect to restrict to allowed hosts.
		wp_safe_redirect( $redirect_url, 301 );
		exit;
	}

	/**
	 * Create database table
	 */
	public static function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'seovela_404_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			url varchar(500) NOT NULL,
			referer varchar(500) DEFAULT NULL,
			user_agent varchar(255) DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			count int(11) NOT NULL DEFAULT 1,
			first_hit datetime NOT NULL,
			last_hit datetime NOT NULL,
			resolved tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY url (url(191)),
			KEY resolved (resolved),
			KEY last_hit (last_hit)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Log 404 error
	 */
	public function log_404() {
		// Only log on 404 pages
		if ( ! is_404() ) {
            // Force check if status header is 404, as some themes/plugins don't set is_404() correctly
            if ( function_exists('http_response_code') && http_response_code() == 404 ) {
                // Continue
            } else {
			    return;
            }
		}

		// Get cached settings to reduce DB queries
		$settings = Seovela_Cache::get_option( '404_settings', array() );
		
		// Don't log for admins (optional)
		if ( ! empty( $settings['ignore_admins'] ) && current_user_can( 'manage_options' ) ) {
			return;
		}

		// Don't log if disabled
		if ( isset( $settings['enabled'] ) && ! $settings['enabled'] ) {
			return;
		}

		// Get request data
		$url = $this->get_current_url();
		$referer = ! empty( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : null;
		$user_agent = ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : null;
		$ip_address = $this->get_client_ip();

		// Ignore certain URLs (like assets, admin-ajax, etc.)
		if ( $this->should_ignore_url( $url ) ) {
			return;
		}

		// Rate limit: max 10 entries per minute per IP to prevent database bloat
		if ( $this->is_rate_limited( $ip_address ) ) {
			return;
		}

		// Check for bot scan patterns and auto-dismiss
		if ( $this->is_bot_scan_pattern( $url ) ) {
			$this->log_entry( $url, $referer, $user_agent, $ip_address, 2 );
			return;
		}

		// Log the 404
		$this->log_entry( $url, $referer, $user_agent, $ip_address );
	}

	/**
	 * Log 404 entry (update if exists, insert if not)
	 *
	 * @param string $url        URL that returned 404
	 * @param string $referer    Referer URL
	 * @param string $user_agent User agent
	 * @param string $ip_address IP address
	 * @param int    $resolved   Resolved status (0=unresolved, 1=resolved, 2=bot scan)
	 */
	private function log_entry( $url, $referer, $user_agent, $ip_address, $resolved = 0 ) {
		global $wpdb;

		// Check if URL already logged (match on same resolved status for bot scans)
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, count FROM {$this->table_name} WHERE url = %s AND resolved = %d",
				$url,
				$resolved
			)
		);

		$current_time = current_time( 'mysql' );

		if ( $existing ) {
			// Update existing entry
			$wpdb->update(
				$this->table_name,
				array(
					'count'      => $existing->count + 1,
					'last_hit'   => $current_time,
					'referer'    => $referer,
					'user_agent' => $user_agent,
					'ip_address' => $ip_address,
				),
				array( 'id' => $existing->id ),
				array( '%d', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			// Insert new entry
			$wpdb->insert(
				$this->table_name,
				array(
					'url'        => $url,
					'referer'    => $referer,
					'user_agent' => $user_agent,
					'ip_address' => $ip_address,
					'count'      => 1,
					'first_hit'  => $current_time,
					'last_hit'   => $current_time,
					'resolved'   => $resolved,
				),
				array( '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d' )
			);
		}

		do_action( 'seovela_404_logged', $url, $referer );
	}

	/**
	 * Get all 404 logs
	 *
	 * @param array $args Query arguments
	 * @return array 404 logs
	 */
	public function get_logs( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'orderby'  => 'last_hit',
			'order'    => 'DESC',
			'limit'    => 50,
			'offset'   => 0,
			'resolved' => null,
			'search'   => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );

		if ( $args['resolved'] !== null ) {
			$where[] = $wpdb->prepare( 'resolved = %d', $args['resolved'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[] = $wpdb->prepare( '(url LIKE %s OR referer LIKE %s)', $search, $search );
		}

		$where_sql = implode( ' AND ', $where );

		// Allowlist orderby and order to prevent SQL injection.
		$allowed_orderby = array( 'id', 'url', 'count', 'first_hit', 'last_hit', 'resolved', 'user_agent', 'referer', 'ip_address' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'last_hit';
		$order           = in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $args['order'] ) : 'DESC';

		$order_sql = sprintf( 'ORDER BY %s %s', $orderby, $order );
		$limit_sql = sprintf( 'LIMIT %d OFFSET %d', absint( $args['limit'] ), absint( $args['offset'] ) );

		$sql = "SELECT * FROM {$this->table_name} WHERE {$where_sql} {$order_sql} {$limit_sql}"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is plugin-prefixed, orderby/order are allowlisted.

		return $wpdb->get_results( $sql );
	}

	/**
	 * Get total log count
	 *
	 * @param array $args Query arguments
	 * @return int Total count
	 */
	public function get_total_count( $args = array() ) {
		global $wpdb;

		$where = array( '1=1' );

		if ( isset( $args['resolved'] ) && $args['resolved'] !== null ) {
			$where[] = $wpdb->prepare( 'resolved = %d', $args['resolved'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[] = $wpdb->prepare( '(url LIKE %s OR referer LIKE %s)', $search, $search );
		}

		$where_sql = implode( ' AND ', $where );

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_sql}" );
	}

	/**
	 * Get 404 log by ID
	 *
	 * @param int $log_id Log ID
	 * @return object|null Log object or null
	 */
	public function get_log( $log_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d",
				$log_id
			)
		);
	}

	/**
	 * Mark log as resolved
	 *
	 * @param int $log_id Log ID
	 * @return bool Success
	 */
	public function mark_resolved( $log_id ) {
		global $wpdb;

		$result = $wpdb->update(
			$this->table_name,
			array( 'resolved' => 1 ),
			array( 'id' => $log_id ),
			array( '%d' ),
			array( '%d' )
		);

		if ( $result ) {
			do_action( 'seovela_404_resolved', $log_id );
		}

		return (bool) $result;
	}

	/**
	 * Delete a log
	 *
	 * @param int $log_id Log ID
	 * @return bool Success
	 */
	public function delete_log( $log_id ) {
		global $wpdb;

		return (bool) $wpdb->delete(
			$this->table_name,
			array( 'id' => $log_id ),
			array( '%d' )
		);
	}

	/**
	 * Delete all logs
	 *
	 * @param bool $resolved_only Delete only resolved logs
	 * @return int Number of deleted rows
	 */
	public function delete_all_logs( $resolved_only = false ) {
		global $wpdb;

		if ( $resolved_only ) {
			return $wpdb->delete(
				$this->table_name,
				array( 'resolved' => 1 ),
				array( '%d' )
			);
		} else {
			return $wpdb->query( "TRUNCATE TABLE {$this->table_name}" );
		}
	}

	/**
	 * Cleanup old logs
	 *
	 * - Deletes ALL entries (resolved or not) older than configured days
	 * - Deletes bot scan entries (resolved=2) older than 7 days regardless of settings
	 * - Enforces max entries limit (default 5000) by deleting oldest entries
	 */
	public function cleanup_old_logs() {
		global $wpdb;

		$settings = Seovela_Cache::get_option( '404_settings', array() );
		$days = ! empty( $settings['cleanup_days'] ) ? intval( $settings['cleanup_days'] ) : 30;
		$max_entries = ! empty( $settings['max_entries'] ) ? intval( $settings['max_entries'] ) : 5000;

		$deleted = 0;

		// Delete ALL entries older than configured days (regardless of resolved status)
		$date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		$deleted += (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table_name} WHERE last_hit < %s",
				$date
			)
		);

		// Delete bot scan entries (resolved=2) older than 7 days regardless of settings
		$bot_date = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );
		$deleted += (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table_name} WHERE resolved = 2 AND last_hit < %s",
				$bot_date
			)
		);

		// Enforce max entries limit - delete oldest entries if table exceeds limit
		$total_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
		if ( $total_count > $max_entries ) {
			$excess = $total_count - $max_entries;
			$deleted += (int) $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$this->table_name} ORDER BY last_hit ASC LIMIT %d",
					$excess
				)
			);
		}

		do_action( 'seovela_404_cleanup_completed', $deleted, $days );

		return $deleted;
	}

	/**
	 * Get redirect suggestions for a 404 URL
	 *
	 * @param string $url 404 URL
	 * @return array Suggested URLs
	 */
	public function get_redirect_suggestions( $url ) {
		$suggestions = array();

		// Parse URL
		$parsed = parse_url( $url );
		$path = isset( $parsed['path'] ) ? trim( $parsed['path'], '/' ) : '';

		if ( empty( $path ) ) {
			return $suggestions;
		}

		// Search for similar posts
		$posts = get_posts(
			array(
				'posts_per_page' => 5,
				's'              => $path,
				'post_type'      => 'any',
				'post_status'    => 'publish',
			)
		);

		foreach ( $posts as $post ) {
			$suggestions[] = array(
				'title' => $post->post_title,
				'url'   => get_permalink( $post ),
				'type'  => 'post',
			);
		}

		// Search for similar pages by slug
		$slug_parts = explode( '/', $path );
		$last_slug = end( $slug_parts );

		if ( ! empty( $last_slug ) ) {
			$page = get_page_by_path( $last_slug );
			if ( $page && ! in_array( get_permalink( $page ), array_column( $suggestions, 'url' ), true ) ) {
				$suggestions[] = array(
					'title' => $page->post_title,
					'url'   => get_permalink( $page ),
					'type'  => 'page',
				);
			}
		}

		// Limit to 10 suggestions
		$suggestions = array_slice( $suggestions, 0, 10 );

		return apply_filters( 'seovela_404_redirect_suggestions', $suggestions, $url );
	}

	/**
	 * Check if the current IP is rate limited for 404 logging
	 *
	 * Limits to max 10 entries per minute per IP to prevent database bloat from bot attacks.
	 *
	 * @param string $ip_address Client IP address
	 * @return bool True if rate limited (should skip logging)
	 */
	private function is_rate_limited( $ip_address ) {
		if ( empty( $ip_address ) ) {
			return false;
		}

		$transient_key = 'seovela_404_rate_' . md5( $ip_address );
		$current_count = get_transient( $transient_key );

		if ( false === $current_count ) {
			// First request in this window - set count to 1 with 60-second expiry
			set_transient( $transient_key, 1, 60 );
			return false;
		}

		$current_count = (int) $current_count;

		if ( $current_count >= 10 ) {
			return true;
		}

		// Increment count (preserve existing TTL by using the same expiry)
		set_transient( $transient_key, $current_count + 1, 60 );
		return false;
	}

	/**
	 * Check if URL matches known bot scan patterns
	 *
	 * Bot scans probe for common vulnerability paths. These are logged with
	 * resolved=2 (bot scan) status for tracking but auto-dismissed from active monitoring.
	 *
	 * @param string $url URL to check
	 * @return bool True if URL matches a bot scan pattern
	 */
	public function is_bot_scan_pattern( $url ) {
		$parsed = wp_parse_url( $url );
		$path = isset( $parsed['path'] ) ? strtolower( $parsed['path'] ) : '';

		if ( empty( $path ) ) {
			return false;
		}

		$bot_patterns = array(
			'/cgi-bin/',
			'/.env',
			'/config.php',
			'/phpinfo',
			'/admin/config',
			'/wp-admin/setup-config.php',
			'/.git/',
			'/vendor/',
			'/node_modules/',
		);

		/**
		 * Filter the list of bot scan URL patterns.
		 *
		 * @param array $bot_patterns Array of URL path substrings to match.
		 */
		$bot_patterns = apply_filters( 'seovela_404_bot_scan_patterns', $bot_patterns );

		foreach ( $bot_patterns as $pattern ) {
			if ( strpos( $path, $pattern ) !== false ) {
				return true;
			}
		}

		// Special case: /wp-login.php is a bot scan if the actual wp-login.php doesn't exist at that path
		// (i.e., the request 404'd, which means it's a probe on a non-standard login URL)
		if ( strpos( $path, '/wp-login.php' ) !== false ) {
			return true;
		}

		// Special case: /xmlrpc.php - if it 404'd, it's been disabled or doesn't exist, likely a bot probe
		if ( strpos( $path, '/xmlrpc.php' ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Get 404s grouped by URL pattern prefix
	 *
	 * Groups 404 entries by their URL path prefix (first two path segments),
	 * allowing bulk operations on patterns like /cgi-bin/*, /old-blog/*, etc.
	 *
	 * @param array $args Optional arguments: 'resolved' (int|null), 'min_count' (int)
	 * @return array Array of grouped patterns with pattern, total_count, entry_count, and sample URLs
	 */
	public function get_grouped_patterns( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'resolved'  => null,
			'min_count' => 1,
		);
		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		if ( $args['resolved'] !== null ) {
			$where[] = $wpdb->prepare( 'resolved = %d', $args['resolved'] );
		}
		$where_sql = implode( ' AND ', $where );

		// Get all entries matching criteria
		$entries = $wpdb->get_results(
			"SELECT id, url, count, resolved FROM {$this->table_name} WHERE {$where_sql} ORDER BY url ASC"
		);

		if ( empty( $entries ) ) {
			return array();
		}

		$groups = array();

		foreach ( $entries as $entry ) {
			$parsed = wp_parse_url( $entry->url );
			$path = isset( $parsed['path'] ) ? $parsed['path'] : '/';

			// Extract pattern prefix: use first two path segments (e.g., /cgi-bin/*, /old-blog/posts/*)
			$segments = explode( '/', trim( $path, '/' ) );
			if ( count( $segments ) > 1 ) {
				$pattern = '/' . $segments[0] . '/' . $segments[1];
			} elseif ( ! empty( $segments[0] ) ) {
				$pattern = '/' . $segments[0];
			} else {
				$pattern = '/';
			}

			if ( ! isset( $groups[ $pattern ] ) ) {
				$groups[ $pattern ] = array(
					'pattern'     => $pattern,
					'total_hits'  => 0,
					'entry_count' => 0,
					'entry_ids'   => array(),
					'sample_urls' => array(),
				);
			}

			$groups[ $pattern ]['total_hits'] += (int) $entry->count;
			$groups[ $pattern ]['entry_count']++;
			$groups[ $pattern ]['entry_ids'][] = (int) $entry->id;

			// Keep up to 5 sample URLs per group
			if ( count( $groups[ $pattern ]['sample_urls'] ) < 5 ) {
				$groups[ $pattern ]['sample_urls'][] = $entry->url;
			}
		}

		// Filter by minimum count
		if ( $args['min_count'] > 1 ) {
			$groups = array_filter( $groups, function( $group ) use ( $args ) {
				return $group['entry_count'] >= $args['min_count'];
			} );
		}

		// Sort by entry count descending
		usort( $groups, function( $a, $b ) {
			return $b['entry_count'] - $a['entry_count'];
		} );

		return array_values( $groups );
	}

	/**
	 * Bulk resolve multiple 404 entries
	 *
	 * @param array $ids Array of log entry IDs to mark as resolved
	 * @return int Number of entries resolved
	 */
	public function bulk_resolve( $ids ) {
		global $wpdb;

		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return 0;
		}

		$ids = array_map( 'absint', $ids );
		$ids = array_filter( $ids );

		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table_name} SET resolved = 1 WHERE id IN ({$placeholders})",
				...$ids
			)
		);

		if ( $result ) {
			do_action( 'seovela_404_bulk_resolved', $ids, $result );
		}

		return (int) $result;
	}

	/**
	 * Bulk delete multiple 404 entries
	 *
	 * @param array $ids Array of log entry IDs to delete
	 * @return int Number of entries deleted
	 */
	public function bulk_delete( $ids ) {
		global $wpdb;

		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return 0;
		}

		$ids = array_map( 'absint', $ids );
		$ids = array_filter( $ids );

		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table_name} WHERE id IN ({$placeholders})",
				...$ids
			)
		);

		if ( $result ) {
			do_action( 'seovela_404_bulk_deleted', $ids, $result );
		}

		return (int) $result;
	}

	/**
	 * Bulk resolve all 404 entries matching a URL pattern
	 *
	 * @param string $pattern URL pattern prefix to match (e.g., '/cgi-bin/' or '/old-blog/')
	 * @return int Number of entries resolved
	 */
	public function bulk_resolve_by_pattern( $pattern ) {
		global $wpdb;

		if ( empty( $pattern ) ) {
			return 0;
		}

		$like_pattern = '%' . $wpdb->esc_like( $pattern ) . '%';

		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table_name} SET resolved = 1 WHERE url LIKE %s AND resolved = 0",
				$like_pattern
			)
		);

		if ( $result ) {
			do_action( 'seovela_404_bulk_resolved_by_pattern', $pattern, $result );
		}

		return (int) $result;
	}

	/**
	 * Create a redirect from a 404 log entry
	 *
	 * Gets the 404 entry, creates a redirect in the seovela_redirects table,
	 * marks the 404 as resolved, and returns the result.
	 *
	 * @param int    $log_id     The 404 log entry ID
	 * @param string $target_url The URL to redirect to
	 * @param string $type       Redirect type: '301', '302', or '307' (default '301')
	 * @return array|WP_Error Array with 'redirect_id' and 'log_id' on success, WP_Error on failure
	 */
	public function create_redirect_from_404( $log_id, $target_url, $type = '301' ) {
		// Get the 404 entry
		$log_entry = $this->get_log( $log_id );

		if ( ! $log_entry ) {
			return new WP_Error(
				'not_found',
				__( '404 log entry not found.', 'seovela' )
			);
		}

		if ( empty( $target_url ) ) {
			return new WP_Error(
				'missing_target',
				__( 'Target URL is required.', 'seovela' )
			);
		}

		// Extract the path from the 404 URL to use as redirect source
		$parsed = wp_parse_url( $log_entry->url );
		$source_url = isset( $parsed['path'] ) ? $parsed['path'] : $log_entry->url;
		if ( ! empty( $parsed['query'] ) ) {
			$source_url .= '?' . $parsed['query'];
		}

		// Create the redirect via the Redirects module if available
		$redirects_table = $GLOBALS['wpdb']->prefix . 'seovela_redirects';

		// Check if the Redirects class is available and use it
		if ( class_exists( 'Seovela_Redirects' ) ) {
			$redirects = Seovela_Redirects::get_instance();
			$redirect_id = $redirects->add_redirect( array(
				'source_url'    => $source_url,
				'target_url'    => esc_url_raw( $target_url ),
				'redirect_type' => $type,
				'regex'         => 0,
				'enabled'       => 1,
			) );

			if ( is_wp_error( $redirect_id ) ) {
				return $redirect_id;
			}
		} else {
			// Fallback: insert directly into the redirects table
			global $wpdb;

			$current_time = current_time( 'mysql' );

			// Check for duplicate
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$redirects_table} WHERE source_url = %s LIMIT 1",
					$source_url
				)
			);

			if ( $existing ) {
				return new WP_Error(
					'duplicate_redirect',
					__( 'A redirect for this URL already exists.', 'seovela' )
				);
			}

			$result = $wpdb->insert(
				$redirects_table,
				array(
					'source_url'    => $source_url,
					'target_url'    => esc_url_raw( $target_url ),
					'redirect_type' => in_array( $type, array( '301', '302', '307' ), true ) ? $type : '301',
					'regex'         => 0,
					'enabled'       => 1,
					'hits'          => 0,
					'created_at'    => $current_time,
					'updated_at'    => $current_time,
				),
				array( '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' )
			);

			if ( false === $result ) {
				return new WP_Error(
					'db_error',
					__( 'Failed to create redirect.', 'seovela' )
				);
			}

			$redirect_id = $wpdb->insert_id;
		}

		// Mark the 404 entry as resolved
		$this->mark_resolved( $log_id );

		do_action( 'seovela_404_redirect_created', $log_id, $redirect_id, $source_url, $target_url );

		return array(
			'redirect_id' => $redirect_id,
			'log_id'      => $log_id,
			'source_url'  => $source_url,
			'target_url'  => $target_url,
		);
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
	 * Get client IP address
	 *
	 * Uses REMOTE_ADDR as the trusted source. Proxy headers (X-Forwarded-For etc.)
	 * are easily spoofable and only consulted if a filter opts in.
	 *
	 * @return string IP address
	 */
	private function get_client_ip() {
		// Allow hosts behind a known reverse proxy to opt-in to trusted headers.
		$trusted_headers = apply_filters( 'seovela_trusted_proxy_headers', array() );

		foreach ( $trusted_headers as $key ) {
			$key = strtoupper( $key );
			if ( isset( $_SERVER[ $key ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				foreach ( explode( ',', $value ) as $ip ) {
					$ip = trim( $ip );
					if ( filter_var( $ip, FILTER_VALIDATE_IP ) !== false ) {
						return $ip;
					}
				}
			}
		}

		// Default to the direct connection IP.
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) !== false ) {
				return $ip;
			}
		}

		return '';
	}

	/**
	 * Check if URL should be ignored
	 *
	 * @param string $url URL to check
	 * @return bool Should ignore
	 */
	private function should_ignore_url( $url ) {
		$ignore_patterns = array(
			'/wp-content/',
			'/wp-includes/',
			'/wp-admin/',
			'/wp-json/',
			'/favicon.ico',
			'/.well-known/',
			'/robots.txt',
			'/sitemap',
			'.jpg',
			'.jpeg',
			'.png',
			'.gif',
			'.css',
			'.js',
			'.svg',
			'.woff',
			'.ttf',
		);

		$ignore_patterns = apply_filters( 'seovela_404_ignore_patterns', $ignore_patterns );

		foreach ( $ignore_patterns as $pattern ) {
			if ( strpos( $url, $pattern ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get 404 statistics
	 *
	 * @return array Statistics
	 */
	public function get_statistics() {
		global $wpdb;

		$stats = array();

		// Total 404s
		$stats['total'] = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );

		// Unresolved 404s
		$stats['unresolved'] = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} WHERE resolved = 0" );

		// Bot scan 404s
		$stats['bot_scans'] = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} WHERE resolved = 2" );

		// Total hits
		$stats['total_hits'] = $wpdb->get_var( "SELECT SUM(count) FROM {$this->table_name}" );

		// Top 404s
		$stats['top_404s'] = $wpdb->get_results(
			"SELECT url, count FROM {$this->table_name} WHERE resolved = 0 ORDER BY count DESC LIMIT 10"
		);

		// Recent 404s
		$stats['recent'] = $wpdb->get_results(
			"SELECT url, last_hit FROM {$this->table_name} WHERE resolved = 0 ORDER BY last_hit DESC LIMIT 10"
		);

		return $stats;
	}
}

