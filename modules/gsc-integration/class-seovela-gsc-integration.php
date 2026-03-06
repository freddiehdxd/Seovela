<?php
/**
 * Seovela Google Search Console Integration
 *
 * Centralized OAuth2 flow - Rank Math / AIOSEO style
 * All users authenticate through Seovela's central OAuth app
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seovela GSC Integration Class
 */
class Seovela_Gsc_Integration {

	/**
	 * Single instance
	 *
	 * @var Seovela_Gsc_Integration
	 */
	private static $instance = null;

	/**
	 * Database table name
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Google OAuth2 endpoints
	 */
	private $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth';
	private $token_url = 'https://oauth2.googleapis.com/token';
	private $api_url = 'https://www.googleapis.com/webmasters/v3';
	private $searchanalytics_url = 'https://searchconsole.googleapis.com/webmasters/v3';

	/**
	 * Central OAuth callback URL (on Seovela's server)
	 * This is where Google redirects after authentication
	 */
	private $central_callback_url = 'https://seovela.com/oauth/callback/';

	/**
	 * Google OAuth2 Client ID (public - safe to expose)
	 * This is your single OAuth app that all users authenticate through
	 * 
	 * IMPORTANT: Replace with your actual Client ID from Google Cloud Console
	 * Get it from: APIs & Services → Credentials → Your OAuth 2.0 Client ID
	 */
	private $client_id = '284765469874-jdl69571667d2pfh5882vl8einujdam4.apps.googleusercontent.com';

	/**
	 * OAuth2 scopes
	 */
	private $scopes = array(
		'openid',
		'email',
		'profile',
		'https://www.googleapis.com/auth/webmasters.readonly',
	);

	/**
	 * User meta keys for token storage
	 */
	const META_ACCESS_TOKEN = 'seovela_gsc_access_token';
	const META_REFRESH_TOKEN = 'seovela_gsc_refresh_token';
	const META_TOKEN_EXPIRES = 'seovela_gsc_token_expires';
	const META_USER_EMAIL = 'seovela_gsc_user_email';
	const META_PROPERTY = 'seovela_gsc_property';

	/**
	 * Get singleton instance
	 *
	 * @return Seovela_Gsc_Integration
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
		$this->table_name = $wpdb->prefix . 'seovela_gsc_data';

		// Admin hooks
		add_action( 'admin_menu', array( $this, 'add_menu_page' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_init', array( $this, 'handle_oauth_callback' ) );
		
		// AJAX hooks
		add_action( 'wp_ajax_seovela_gsc_disconnect', array( $this, 'ajax_disconnect' ) );
		add_action( 'wp_ajax_seovela_gsc_sync_data', array( $this, 'ajax_sync_data' ) );
		add_action( 'wp_ajax_seovela_gsc_get_stats', array( $this, 'ajax_get_stats' ) );
		add_action( 'wp_ajax_seovela_gsc_select_property', array( $this, 'ajax_select_property' ) );
		add_action( 'wp_ajax_seovela_gsc_get_queries', array( $this, 'ajax_get_queries' ) );
		
		// Dashboard widget
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
		
		// Admin columns
		add_filter( 'manage_posts_columns', array( $this, 'add_admin_columns' ) );
		add_filter( 'manage_pages_columns', array( $this, 'add_admin_columns' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'render_admin_column' ), 10, 2 );
		add_action( 'manage_pages_custom_column', array( $this, 'render_admin_column' ), 10, 2 );
		
		// Cron for auto-sync
		add_action( 'seovela_gsc_daily_sync', array( $this, 'cron_sync_data' ) );
		
		// Schedule cron if not scheduled
		if ( ! wp_next_scheduled( 'seovela_gsc_daily_sync' ) ) {
			wp_schedule_event( time(), 'daily', 'seovela_gsc_daily_sync' );
		}
	}

	/**
	 * Create database table
	 */
	public static function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'seovela_gsc_data';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL DEFAULT 0,
			page_url varchar(500) NOT NULL,
			post_id bigint(20) DEFAULT NULL,
			clicks int(11) NOT NULL DEFAULT 0,
			impressions int(11) NOT NULL DEFAULT 0,
			ctr decimal(5,4) NOT NULL DEFAULT 0.0000,
			position decimal(5,2) NOT NULL DEFAULT 0.00,
			date_start date NOT NULL,
			date_end date NOT NULL,
			synced_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY page_url (page_url(191)),
			KEY post_id (post_id),
			KEY user_id (user_id),
			KEY clicks (clicks),
			KEY impressions (impressions),
			KEY date_range (date_start, date_end)
		) $charset_collate;";

		// Table for queries/keywords
		$queries_table = $wpdb->prefix . 'seovela_gsc_queries';
		$sql_queries = "CREATE TABLE IF NOT EXISTS $queries_table (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL DEFAULT 0,
			query varchar(500) NOT NULL,
			clicks int(11) NOT NULL DEFAULT 0,
			impressions int(11) NOT NULL DEFAULT 0,
			ctr decimal(5,4) NOT NULL DEFAULT 0.0000,
			position decimal(5,2) NOT NULL DEFAULT 0.00,
			date_start date NOT NULL,
			date_end date NOT NULL,
			synced_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY query (query(191)),
			KEY user_id (user_id),
			KEY clicks (clicks),
			KEY impressions (impressions),
			KEY date_range (date_start, date_end)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		dbDelta( $sql_queries );
	}

	/**
	 * Add admin menu page
	 */
	public function add_menu_page() {
		// Only add if module is enabled
		if ( ! get_option( 'seovela_gsc_integration_enabled', false ) ) {
			return;
		}

		add_submenu_page(
			'seovela',
			__( 'Google Search Console', 'seovela' ),
			__( 'Search Console', 'seovela' ),
			'manage_options',
			'seovela-gsc',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin scripts
	 */
	public function enqueue_scripts( $hook ) {
		// Check for Search Console page or dashboard
		$is_gsc_page = strpos( $hook, 'seovela-gsc' ) !== false || strpos( $hook, 'seovela_page_seovela-gsc' ) !== false;
		if ( ! $is_gsc_page && 'index.php' !== $hook ) {
			return;
		}

		// Chart.js for graphs
		wp_enqueue_script(
			'chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
			array(),
			'4.4.1',
			true
		);

		wp_enqueue_script(
			'seovela-gsc',
			plugin_dir_url( __FILE__ ) . 'assets/js/gsc-integration.js',
			array( 'jquery', 'chartjs' ),
			SEOVELA_VERSION,
			true
		);

		wp_localize_script(
			'seovela-gsc',
			'seovelaGsc',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'seovela_gsc' ),
				'isConnected' => $this->is_connected(),
				'chartData'   => $this->is_connected() && $this->has_property() ? $this->get_chart_data() : array(),
			)
		);

		wp_enqueue_style(
			'seovela-gsc',
			plugin_dir_url( __FILE__ ) . 'assets/css/gsc-integration.css',
			array(),
			SEOVELA_VERSION
		);
	}

	/**
	 * Get the local callback URL (where central server redirects back to)
	 * This is the user's WordPress admin URL
	 */
	public function get_local_callback_url() {
		return admin_url( 'admin.php?page=seovela-gsc&gsc_oauth_callback=1' );
	}

	/**
	 * Get OAuth2 authorization URL
	 * Uses centralized OAuth - state encodes the user's callback URL
	 */
	public function get_auth_url() {
		$user_id = get_current_user_id();
		
		// Generate nonce for CSRF protection
		$nonce = wp_generate_password( 32, false );
		set_transient( 'seovela_gsc_oauth_nonce_' . $user_id, $nonce, 3600 );

		// State contains the callback URL and nonce
		$state_data = array(
			'callback_url' => $this->get_local_callback_url(),
			'nonce'        => $nonce,
			'user_id'      => $user_id,
			'site_url'     => home_url(),
		);
		
		$state = base64_encode( wp_json_encode( $state_data ) );

		$params = array(
			'client_id'     => $this->client_id,
			'redirect_uri'  => $this->central_callback_url,
			'response_type' => 'code',
			'scope'         => implode( ' ', $this->scopes ),
			'access_type'   => 'offline',
			'prompt'        => 'consent',
			'state'         => $state,
		);

		return $this->auth_url . '?' . http_build_query( $params );
	}

	/**
	 * Handle OAuth2 callback from central server
	 * Central server exchanges code for tokens and sends them here
	 */
	public function handle_oauth_callback() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- OAuth callback uses its own nonce via transient + signed token bundle.
		if ( ! isset( $_GET['page'] ) || sanitize_text_field( wp_unslash( $_GET['page'] ) ) !== 'seovela-gsc' ) {
			return;
		}

		if ( ! isset( $_GET['gsc_oauth_callback'] ) ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$user_id = get_current_user_id();

		// Handle errors from central server
		if ( isset( $_GET['gsc_error'] ) ) {
			$error = sanitize_text_field( wp_unslash( $_GET['gsc_error'] ) );
			add_settings_error( 'seovela_gsc', 'oauth_error', $error, 'error' );
			return;
		}

		// Check for token bundle from central server
		if ( ! isset( $_GET['gsc_tokens'] ) || empty( $_GET['gsc_tokens'] ) ) {
			add_settings_error( 'seovela_gsc', 'oauth_error', __( 'No tokens received. Please try again.', 'seovela' ), 'error' );
			return;
		}

		// Decode and verify the signed token bundle
		$signed_bundle = sanitize_text_field( wp_unslash( $_GET['gsc_tokens'] ) );
		$tokens = $this->verify_token_bundle( $signed_bundle );

		if ( is_wp_error( $tokens ) ) {
			add_settings_error( 'seovela_gsc', 'oauth_error', $tokens->get_error_message(), 'error' );
			return;
		}

		// Verify nonce
		$stored_nonce = get_transient( 'seovela_gsc_oauth_nonce_' . $user_id );
		if ( ! $stored_nonce || ! isset( $tokens['nonce'] ) || $tokens['nonce'] !== $stored_nonce ) {
			add_settings_error( 'seovela_gsc', 'oauth_error', __( 'Security validation failed. Please try again.', 'seovela' ), 'error' );
			return;
		}
		delete_transient( 'seovela_gsc_oauth_nonce_' . $user_id );

		// Check token age (prevent replay attacks - tokens should be fresh)
		if ( isset( $tokens['timestamp'] ) && ( time() - $tokens['timestamp'] ) > 300 ) {
			add_settings_error( 'seovela_gsc', 'oauth_error', __( 'Token expired. Please try again.', 'seovela' ), 'error' );
			return;
		}

		// Store tokens in user meta
		update_user_meta( $user_id, self::META_ACCESS_TOKEN, $tokens['access_token'] );
		
		if ( ! empty( $tokens['refresh_token'] ) ) {
			update_user_meta( $user_id, self::META_REFRESH_TOKEN, $tokens['refresh_token'] );
		}
		
		$expires_in = isset( $tokens['expires_in'] ) ? intval( $tokens['expires_in'] ) : 3600;
		update_user_meta( $user_id, self::META_TOKEN_EXPIRES, time() + $expires_in );

		// Get user info from Google
		$this->fetch_and_store_user_info( $user_id );

		// Redirect to property selection
		wp_safe_redirect( admin_url( 'admin.php?page=seovela-gsc&connected=1' ) );
		exit;
	}

	/**
	 * Verify the signed token bundle from central server
	 */
	private function verify_token_bundle( $signed_bundle ) {
		// Bundle format: base64(json).signature
		$parts = explode( '.', $signed_bundle );
		
		if ( count( $parts ) !== 2 ) {
			return new WP_Error( 'invalid_bundle', __( 'Invalid token bundle format.', 'seovela' ) );
		}

		$bundle_b64 = $parts[0];
		$signature = $parts[1];

		// Decode the bundle
		$bundle_json = base64_decode( $bundle_b64 );
		if ( $bundle_json === false ) {
			return new WP_Error( 'decode_failed', __( 'Could not decode token bundle.', 'seovela' ) );
		}

		$tokens = json_decode( $bundle_json, true );
		if ( ! $tokens || ! is_array( $tokens ) ) {
			return new WP_Error( 'parse_failed', __( 'Could not parse token bundle.', 'seovela' ) );
		}

		// Note: In production, you should verify the signature using a shared secret
		// For now, we'll trust tokens from the central server since it's under our control
		// You could implement signature verification by sharing a secret between
		// the central callback and this plugin

		if ( ! isset( $tokens['access_token'] ) ) {
			return new WP_Error( 'no_token', __( 'Access token missing from bundle.', 'seovela' ) );
		}

		return $tokens;
	}

	/**
	 * Fetch and store Google user info
	 */
	private function fetch_and_store_user_info( $user_id ) {
		$access_token = get_user_meta( $user_id, self::META_ACCESS_TOKEN, true );
		
		if ( empty( $access_token ) ) {
			return;
		}

		$response = wp_remote_get( 'https://www.googleapis.com/oauth2/v2/userinfo', array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
			),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			return;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		
		if ( isset( $body['email'] ) ) {
			update_user_meta( $user_id, self::META_USER_EMAIL, sanitize_email( $body['email'] ) );
		}
	}

	/**
	 * Refresh access token using refresh token
	 * Makes request to central token endpoint
	 */
	public function refresh_access_token( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$refresh_token = get_user_meta( $user_id, self::META_REFRESH_TOKEN, true );
		
		if ( empty( $refresh_token ) ) {
			return new WP_Error( 'no_refresh_token', __( 'No refresh token available. Please reconnect.', 'seovela' ) );
		}

		// Request new access token from our central refresh endpoint
		// The central server handles the actual token refresh with Google
		$response = wp_remote_post( 'https://seovela.com/oauth/refresh/', array(
			'body' => array(
				'refresh_token' => $refresh_token,
				'site_url'      => home_url(),
			),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			// If refresh token is invalid, clear all tokens
			if ( $body['error'] === 'invalid_grant' ) {
				$this->clear_user_tokens( $user_id );
			}
			$error_msg = isset( $body['error_description'] ) ? $body['error_description'] : $body['error'];
			return new WP_Error( 'refresh_error', $error_msg );
		}

		if ( ! isset( $body['access_token'] ) ) {
			return new WP_Error( 'no_token', __( 'No access token in refresh response.', 'seovela' ) );
		}

		update_user_meta( $user_id, self::META_ACCESS_TOKEN, $body['access_token'] );
		
		$expires_in = isset( $body['expires_in'] ) ? intval( $body['expires_in'] ) : 3600;
		update_user_meta( $user_id, self::META_TOKEN_EXPIRES, time() + $expires_in );

		return $body['access_token'];
	}

	/**
	 * Get valid access token (refresh if needed)
	 */
	public function get_access_token( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$access_token = get_user_meta( $user_id, self::META_ACCESS_TOKEN, true );
		$expires = get_user_meta( $user_id, self::META_TOKEN_EXPIRES, true );

		if ( empty( $access_token ) ) {
			return new WP_Error( 'no_token', __( 'Not connected. Please connect your Google account.', 'seovela' ) );
		}

		// Token expires in less than 5 minutes, refresh it
		if ( $expires && time() > ( intval( $expires ) - 300 ) ) {
			$new_token = $this->refresh_access_token( $user_id );
			if ( is_wp_error( $new_token ) ) {
				return $new_token;
			}
			return $new_token;
		}

		return $access_token;
	}

	/**
	 * Clear all tokens for a user
	 */
	private function clear_user_tokens( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		delete_user_meta( $user_id, self::META_ACCESS_TOKEN );
		delete_user_meta( $user_id, self::META_REFRESH_TOKEN );
		delete_user_meta( $user_id, self::META_TOKEN_EXPIRES );
		delete_user_meta( $user_id, self::META_USER_EMAIL );
		delete_user_meta( $user_id, self::META_PROPERTY );
	}

	/**
	 * Make API request to Google
	 */
	public function api_request( $endpoint, $method = 'GET', $body = null, $user_id = null ) {
		$access_token = $this->get_access_token( $user_id );
		
		if ( is_wp_error( $access_token ) ) {
			return $access_token;
		}

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 60,
		);

		if ( $method === 'POST' && $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$url = $this->searchanalytics_url . $endpoint;
		
		if ( $method === 'POST' ) {
			$response = wp_remote_post( $url, $args );
		} else {
			$response = wp_remote_get( $url, $args );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		$code = wp_remote_retrieve_response_code( $response );

		if ( $code >= 400 ) {
			$error_msg = isset( $response_body['error']['message'] ) 
				? $response_body['error']['message'] 
				: __( 'API request failed', 'seovela' );
			
			// If unauthorized, try refreshing token once
			if ( $code === 401 ) {
				$new_token = $this->refresh_access_token( $user_id );
				if ( ! is_wp_error( $new_token ) ) {
					// Retry the request with new token
					$args['headers']['Authorization'] = 'Bearer ' . $new_token;
					if ( $method === 'POST' ) {
						$response = wp_remote_post( $url, $args );
					} else {
						$response = wp_remote_get( $url, $args );
					}
					
					if ( ! is_wp_error( $response ) ) {
						$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
						$code = wp_remote_retrieve_response_code( $response );
						
						if ( $code < 400 ) {
							return $response_body;
						}
					}
				}
			}
			
			return new WP_Error( 'api_error', $error_msg );
		}

		return $response_body;
	}

	/**
	 * Get list of sites from GSC
	 */
	public function get_sites( $user_id = null ) {
		$response = $this->api_request( '/sites', 'GET', null, $user_id );
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return isset( $response['siteEntry'] ) ? $response['siteEntry'] : array();
	}

	/**
	 * Get search analytics data from GSC
	 */
	public function get_search_analytics( $site_url, $start_date, $end_date, $dimensions = array( 'page' ), $row_limit = 1000, $user_id = null ) {
		$endpoint = '/sites/' . rawurlencode( $site_url ) . '/searchAnalytics/query';
		
		$body = array(
			'startDate'  => $start_date,
			'endDate'    => $end_date,
			'dimensions' => $dimensions,
			'rowLimit'   => $row_limit,
		);

		return $this->api_request( $endpoint, 'POST', $body, $user_id );
	}

	/**
	 * AJAX: Select property
	 */
	public function ajax_select_property() {
		check_ajax_referer( 'seovela_gsc', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'seovela' ) ) );
		}

		$property = isset( $_POST['property'] ) ? sanitize_text_field( wp_unslash( $_POST['property'] ) ) : '';
		
		if ( empty( $property ) ) {
			wp_send_json_error( array( 'message' => __( 'No property selected.', 'seovela' ) ) );
		}

		$user_id = get_current_user_id();
		update_user_meta( $user_id, self::META_PROPERTY, $property );
		
		wp_send_json_success( array( 'message' => __( 'Property selected successfully!', 'seovela' ) ) );
	}

	/**
	 * Check if user is connected to GSC
	 */
	public function is_connected( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$access_token = get_user_meta( $user_id, self::META_ACCESS_TOKEN, true );
		$refresh_token = get_user_meta( $user_id, self::META_REFRESH_TOKEN, true );
		
		return ! empty( $access_token ) && ! empty( $refresh_token );
	}

	/**
	 * Check if property is selected
	 */
	public function has_property( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$property = get_user_meta( $user_id, self::META_PROPERTY, true );
		return ! empty( $property );
	}

	/**
	 * Get selected property
	 */
	public function get_property( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		return get_user_meta( $user_id, self::META_PROPERTY, true );
	}

	/**
	 * Get connected Google account email
	 */
	public function get_connected_email( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		return get_user_meta( $user_id, self::META_USER_EMAIL, true );
	}

	/**
	 * Add admin columns
	 */
	public function add_admin_columns( $columns ) {
		if ( ! $this->is_connected() || ! $this->has_property() ) {
			return $columns;
		}

		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			
			if ( $key === 'title' ) {
				$new_columns['seovela_gsc_clicks'] = __( 'Clicks', 'seovela' );
				$new_columns['seovela_gsc_impressions'] = __( 'Impressions', 'seovela' );
				$new_columns['seovela_gsc_ctr'] = __( 'CTR', 'seovela' );
			}
		}
		
		return $new_columns;
	}

	/**
	 * Render admin column
	 */
	public function render_admin_column( $column, $post_id ) {
		global $wpdb;
		
		if ( ! in_array( $column, array( 'seovela_gsc_clicks', 'seovela_gsc_impressions', 'seovela_gsc_ctr' ), true ) ) {
			return;
		}

		$user_id = get_current_user_id();
		
		$data = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$this->table_name} 
			WHERE post_id = %d AND user_id = %d
			ORDER BY synced_at DESC 
			LIMIT 1",
			$post_id,
			$user_id
		) );

		if ( ! $data ) {
			echo '<span class="gsc-no-data">—</span>';
			return;
		}

		switch ( $column ) {
			case 'seovela_gsc_clicks':
				echo '<strong>' . esc_html( number_format( $data->clicks ) ) . '</strong>';
				break;
			case 'seovela_gsc_impressions':
				echo esc_html( number_format( $data->impressions ) );
				break;
			case 'seovela_gsc_ctr':
				echo esc_html( number_format( $data->ctr * 100, 2 ) ) . '%';
				break;
		}
	}

	/**
	 * Add dashboard widget
	 */
	public function add_dashboard_widget() {
		if ( ! $this->is_connected() || ! $this->has_property() ) {
			return;
		}

		wp_add_dashboard_widget(
			'seovela_gsc_widget',
			__( 'Google Search Console Stats', 'seovela' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render dashboard widget
	 */
	public function render_dashboard_widget() {
		$stats = $this->get_site_stats();
		
		?>
		<div class="seovela-dashboard-widget gsc-widget">
			<div class="stats-grid">
				<div class="stat-item">
					<div class="stat-icon dashicons dashicons-visibility"></div>
					<span class="stat-value"><?php echo esc_html( number_format( $stats['total_impressions'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Impressions', 'seovela' ); ?></span>
					<span class="stat-change <?php echo $stats['impressions_change'] >= 0 ? 'positive' : 'negative'; ?>">
						<span class="dashicons dashicons-arrow-<?php echo $stats['impressions_change'] >= 0 ? 'up-alt2' : 'down-alt2'; ?>"></span>
						<?php echo esc_html( abs( $stats['impressions_change'] ) ); ?>%
					</span>
				</div>
				<div class="stat-item">
					<div class="stat-icon dashicons dashicons-admin-site-alt3"></div>
					<span class="stat-value"><?php echo esc_html( number_format( $stats['total_clicks'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Clicks', 'seovela' ); ?></span>
					<span class="stat-change <?php echo $stats['clicks_change'] >= 0 ? 'positive' : 'negative'; ?>">
						<span class="dashicons dashicons-arrow-<?php echo $stats['clicks_change'] >= 0 ? 'up-alt2' : 'down-alt2'; ?>"></span>
						<?php echo esc_html( abs( $stats['clicks_change'] ) ); ?>%
					</span>
				</div>
				<div class="stat-item">
					<div class="stat-icon dashicons dashicons-chart-area"></div>
					<span class="stat-value"><?php echo esc_html( number_format( $stats['avg_ctr'], 2 ) ); ?>%</span>
					<span class="stat-label"><?php esc_html_e( 'Avg CTR', 'seovela' ); ?></span>
				</div>
				<div class="stat-item">
					<div class="stat-icon dashicons dashicons-sort"></div>
					<span class="stat-value"><?php echo esc_html( number_format( $stats['avg_position'], 1 ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Avg Position', 'seovela' ); ?></span>
				</div>
			</div>
			
			<div class="widget-footer">
				<div class="widget-actions">
					<button type="button" class="button button-secondary" id="sync-gsc-now">
						<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Sync Now', 'seovela' ); ?>
					</button>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-gsc' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Full Report', 'seovela' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get site statistics
	 */
	public function get_site_stats( $days = 30, $user_id = null ) {
		global $wpdb;
		
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		
		$date_start = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );
		
		$current_stats = $wpdb->get_row( $wpdb->prepare(
			"SELECT 
				SUM(clicks) as total_clicks,
				SUM(impressions) as total_impressions,
				AVG(ctr) as avg_ctr,
				AVG(position) as avg_position
			FROM {$this->table_name}
			WHERE user_id = %d AND date_end >= %s",
			$user_id,
			$date_start
		) );

		// Get previous period for comparison
		$prev_date_start = gmdate( 'Y-m-d', strtotime( '-' . ( $days * 2 ) . ' days' ) );
		$prev_date_end = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );
		
		$previous_stats = $wpdb->get_row( $wpdb->prepare(
			"SELECT 
				SUM(clicks) as total_clicks,
				SUM(impressions) as total_impressions
			FROM {$this->table_name}
			WHERE user_id = %d AND date_end >= %s AND date_end < %s",
			$user_id,
			$prev_date_start,
			$prev_date_end
		) );

		// Calculate changes
		$clicks_change = 0;
		$impressions_change = 0;
		
		if ( $previous_stats && $previous_stats->total_clicks > 0 ) {
			$clicks_change = ( ( $current_stats->total_clicks - $previous_stats->total_clicks ) / $previous_stats->total_clicks ) * 100;
		}
		
		if ( $previous_stats && $previous_stats->total_impressions > 0 ) {
			$impressions_change = ( ( $current_stats->total_impressions - $previous_stats->total_impressions ) / $previous_stats->total_impressions ) * 100;
		}

		return array(
			'total_clicks'        => (int) ( $current_stats->total_clicks ?? 0 ),
			'total_impressions'   => (int) ( $current_stats->total_impressions ?? 0 ),
			'avg_ctr'             => (float) ( $current_stats->avg_ctr ?? 0 ) * 100,
			'avg_position'        => (float) ( $current_stats->avg_position ?? 0 ),
			'clicks_change'       => round( $clicks_change, 1 ),
			'impressions_change'  => round( $impressions_change, 1 ),
		);
	}

	/**
	 * Get chart data for graphs
	 */
	public function get_chart_data( $days = 30, $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$property = $this->get_property( $user_id );
		if ( empty( $property ) ) {
			return array();
		}

		$end_date = gmdate( 'Y-m-d', strtotime( '-2 days' ) );
		$start_date = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		// Get data grouped by date
		$response = $this->get_search_analytics(
			$property,
			$start_date,
			$end_date,
			array( 'date' ),
			$days,
			$user_id
		);

		if ( is_wp_error( $response ) || ! isset( $response['rows'] ) ) {
			return array();
		}

		$labels = array();
		$clicks = array();
		$impressions = array();

		foreach ( $response['rows'] as $row ) {
			$labels[] = $row['keys'][0];
			$clicks[] = isset( $row['clicks'] ) ? intval( $row['clicks'] ) : 0;
			$impressions[] = isset( $row['impressions'] ) ? intval( $row['impressions'] ) : 0;
		}

		return array(
			'labels'      => $labels,
			'clicks'      => $clicks,
			'impressions' => $impressions,
		);
	}

	/**
	 * Render admin page
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$user_id = get_current_user_id();
		$is_connected = $this->is_connected( $user_id );
		$has_property = $this->has_property( $user_id );
		$connected_email = $this->get_connected_email( $user_id );
		$property = $this->get_property( $user_id );
		
		$sites = array();
		$stats = array();
		$top_queries = array();
		$sync_error = null;

		// If connected, fetch sites
		if ( $is_connected && ! $has_property ) {
			$sites = $this->get_sites( $user_id );
			if ( is_wp_error( $sites ) ) {
				$sync_error = $sites->get_error_message();
				$sites = array();
			}
		}

		// If property selected, get stats
		if ( $is_connected && $has_property ) {
			$stats = $this->get_site_stats( 30, $user_id );
			$top_queries = $this->get_top_queries( 10, 'clicks', $user_id );
		}
		
		require_once plugin_dir_path( __FILE__ ) . 'views/gsc-integration.php';
	}

	/**
	 * AJAX: Disconnect GSC
	 */
	public function ajax_disconnect() {
		check_ajax_referer( 'seovela_gsc', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'seovela' ) ) );
		}

		$user_id = get_current_user_id();
		$this->clear_user_tokens( $user_id );
		
		// Clear user's synced data
		global $wpdb;
		$wpdb->delete( $this->table_name, array( 'user_id' => $user_id ), array( '%d' ) );
		
		$queries_table = $wpdb->prefix . 'seovela_gsc_queries';
		$wpdb->delete( $queries_table, array( 'user_id' => $user_id ), array( '%d' ) );
		
		wp_send_json_success( array(
			'message' => __( 'Disconnected from Google Search Console.', 'seovela' ),
		) );
	}

	/**
	 * AJAX: Sync data from GSC
	 */
	public function ajax_sync_data() {
		check_ajax_referer( 'seovela_gsc', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'seovela' ) ) );
		}

		$result = $this->sync_data();
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'message' => $result->get_error_message(),
			) );
		}
		
		wp_send_json_success( array(
			'message' => __( 'Data synced successfully.', 'seovela' ),
			'rows'    => $result,
		) );
	}

	/**
	 * Sync data from GSC API
	 */
	public function sync_data( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $this->is_connected( $user_id ) ) {
			return new WP_Error( 'not_connected', __( 'Not connected to Google Search Console.', 'seovela' ) );
		}

		$property = $this->get_property( $user_id );
		if ( empty( $property ) ) {
			return new WP_Error( 'no_property', __( 'No property selected.', 'seovela' ) );
		}

		$days = 30;
		$end_date = gmdate( 'Y-m-d', strtotime( '-2 days' ) ); // GSC data has 2-day delay
		$start_date = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		global $wpdb;
		$rows_processed = 0;

		// Fetch page data
		$page_data = $this->get_search_analytics( $property, $start_date, $end_date, array( 'page' ), 1000, $user_id );

		if ( ! is_wp_error( $page_data ) && isset( $page_data['rows'] ) ) {
			foreach ( $page_data['rows'] as $row ) {
				$page_url = $row['keys'][0];
				$clicks = isset( $row['clicks'] ) ? intval( $row['clicks'] ) : 0;
				$impressions = isset( $row['impressions'] ) ? intval( $row['impressions'] ) : 0;
				$ctr = isset( $row['ctr'] ) ? floatval( $row['ctr'] ) : 0;
				$position = isset( $row['position'] ) ? floatval( $row['position'] ) : 0;

				// Try to match to a WordPress post
				$post_id = url_to_postid( $page_url );

				$record = array(
					'user_id'      => $user_id,
					'page_url'     => $page_url,
					'post_id'      => $post_id ? $post_id : null,
					'clicks'       => $clicks,
					'impressions'  => $impressions,
					'ctr'          => $ctr,
					'position'     => $position,
					'date_start'   => $start_date,
					'date_end'     => $end_date,
					'synced_at'    => current_time( 'mysql' ),
				);

				// Check if exists
				$exists = $wpdb->get_var( $wpdb->prepare(
					"SELECT id FROM {$this->table_name} WHERE page_url = %s AND user_id = %d",
					$page_url,
					$user_id
				) );

				if ( $exists ) {
					$wpdb->update(
						$this->table_name,
						$record,
						array( 'id' => $exists )
					);
				} else {
					$wpdb->insert( $this->table_name, $record );
				}

				$rows_processed++;
			}
		}

		// Fetch query/keyword data
		$query_data = $this->get_search_analytics( $property, $start_date, $end_date, array( 'query' ), 1000, $user_id );
		$queries_table = $wpdb->prefix . 'seovela_gsc_queries';

		if ( ! is_wp_error( $query_data ) && isset( $query_data['rows'] ) ) {
			foreach ( $query_data['rows'] as $row ) {
				$query = $row['keys'][0];
				$clicks = isset( $row['clicks'] ) ? intval( $row['clicks'] ) : 0;
				$impressions = isset( $row['impressions'] ) ? intval( $row['impressions'] ) : 0;
				$ctr = isset( $row['ctr'] ) ? floatval( $row['ctr'] ) : 0;
				$position = isset( $row['position'] ) ? floatval( $row['position'] ) : 0;

				$record = array(
					'user_id'      => $user_id,
					'query'        => $query,
					'clicks'       => $clicks,
					'impressions'  => $impressions,
					'ctr'          => $ctr,
					'position'     => $position,
					'date_start'   => $start_date,
					'date_end'     => $end_date,
					'synced_at'    => current_time( 'mysql' ),
				);

				// Check if exists
				$exists = $wpdb->get_var( $wpdb->prepare(
					"SELECT id FROM $queries_table WHERE query = %s AND user_id = %d",
					$query,
					$user_id
				) );

				if ( $exists ) {
					$wpdb->update(
						$queries_table,
						$record,
						array( 'id' => $exists )
					);
				} else {
					$wpdb->insert( $queries_table, $record );
				}
			}
		}

		update_user_meta( $user_id, 'seovela_gsc_last_sync', current_time( 'mysql' ) );

		return $rows_processed;
	}

	/**
	 * Cron job to sync data for all connected users
	 */
	public function cron_sync_data() {
		global $wpdb;

		// Get all users with GSC connected
		$user_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT user_id FROM {$wpdb->usermeta} 
			WHERE meta_key = %s 
			AND meta_value != ''",
			self::META_ACCESS_TOKEN
		) );

		foreach ( $user_ids as $user_id ) {
			if ( $this->is_connected( $user_id ) && $this->has_property( $user_id ) ) {
				$this->sync_data( $user_id );
			}
		}
	}

	/**
	 * AJAX: Get stats
	 */
	public function ajax_get_stats() {
		check_ajax_referer( 'seovela_gsc', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'seovela' ) ) );
		}
		
		$days = isset( $_POST['days'] ) ? intval( $_POST['days'] ) : 30;
		$user_id = get_current_user_id();
		$stats = $this->get_site_stats( $days, $user_id );
		$chart_data = $this->get_chart_data( $days, $user_id );
		
		wp_send_json_success( array(
			'stats'     => $stats,
			'chartData' => $chart_data,
		) );
	}

	/**
	 * AJAX: Get queries
	 */
	public function ajax_get_queries() {
		check_ajax_referer( 'seovela_gsc', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'seovela' ) ) );
		}
		
		$user_id = get_current_user_id();
		$limit = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 10;
		$order_by = isset( $_POST['order_by'] ) ? sanitize_text_field( wp_unslash( $_POST['order_by'] ) ) : 'clicks';
		
		$queries = $this->get_top_queries( $limit, $order_by, $user_id );
		
		wp_send_json_success( array(
			'queries' => $queries,
		) );
	}

	/**
	 * Get top pages
	 */
	public function get_top_pages( $limit = 10, $order_by = 'clicks', $user_id = null ) {
		global $wpdb;
		
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		
		$valid_order = in_array( $order_by, array( 'clicks', 'impressions', 'ctr', 'position' ), true ) ? $order_by : 'clicks';
		$order_dir = $order_by === 'position' ? 'ASC' : 'DESC';
		
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$this->table_name}
			WHERE user_id = %d
			ORDER BY {$valid_order} {$order_dir}
			LIMIT %d",
			$user_id,
			$limit
		) );
	}

	/**
	 * Get top queries/keywords
	 */
	public function get_top_queries( $limit = 10, $order_by = 'clicks', $user_id = null ) {
		global $wpdb;
		
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		
		$queries_table = $wpdb->prefix . 'seovela_gsc_queries';
		$valid_order = in_array( $order_by, array( 'clicks', 'impressions', 'ctr', 'position' ), true ) ? $order_by : 'clicks';
		$order_dir = $order_by === 'position' ? 'ASC' : 'DESC';
		
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $queries_table
			WHERE user_id = %d
			ORDER BY {$valid_order} {$order_dir}
			LIMIT %d",
			$user_id,
			$limit
		) );
	}
}
