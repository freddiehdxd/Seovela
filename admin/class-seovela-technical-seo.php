<?php
/**
 * Seovela Technical SEO Admin
 *
 * Manages Redirects and 404 Monitor admin pages
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seovela Technical SEO Class
 */
class Seovela_Technical_SEO_Admin {

	/**
	 * Redirects manager instance
	 *
	 * @var Seovela_Redirects
	 */
	private $redirects;

	/**
	 * 404 Monitor instance
	 *
	 * @var Seovela_404_Monitor
	 */
	private $monitor_404;

	/**
	 * Single instance
	 *
	 * @var Seovela_Technical_SEO_Admin
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return Seovela_Technical_SEO_Admin
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
		// Get singleton instances (don't create new ones to avoid duplicate hooks)
		$this->redirects = Seovela_Redirects::get_instance();
		$this->monitor_404 = Seovela_404_Monitor::get_instance();

		// Menu registration is handled centrally by Seovela_Admin::add_admin_menu().
		// The admin class delegates rendering back to this class via render_redirects_page()
		// and render_404_monitor_page().

		// Handle AJAX actions
		add_action( 'wp_ajax_seovela_add_redirect', array( $this, 'ajax_add_redirect' ) );
		add_action( 'wp_ajax_seovela_edit_redirect', array( $this, 'ajax_edit_redirect' ) );
		add_action( 'wp_ajax_seovela_delete_redirect', array( $this, 'ajax_delete_redirect' ) );
		add_action( 'wp_ajax_seovela_toggle_redirect', array( $this, 'ajax_toggle_redirect' ) );
		add_action( 'wp_ajax_seovela_delete_404_log', array( $this, 'ajax_delete_404_log' ) );
		add_action( 'wp_ajax_seovela_resolve_404', array( $this, 'ajax_resolve_404' ) );
		add_action( 'wp_ajax_seovela_create_redirect_from_404', array( $this, 'ajax_create_redirect_from_404' ) );
		add_action( 'wp_ajax_seovela_export_redirects', array( $this, 'ajax_export_redirects' ) );
		add_action( 'wp_ajax_seovela_import_redirects', array( $this, 'ajax_import_redirects' ) );
		add_action( 'wp_ajax_seovela_save_404_settings', array( $this, 'ajax_save_404_settings' ) );
		add_action( 'wp_ajax_seovela_delete_all_404', array( $this, 'ajax_delete_all_404' ) );
		add_action( 'wp_ajax_seovela_cleanup_resolved', array( $this, 'ajax_cleanup_resolved' ) );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		// Redirects page - only if module is enabled
		if ( get_option( 'seovela_redirects_enabled', true ) ) {
			add_submenu_page(
				'seovela',
				__( 'Redirects', 'seovela' ),
				__( 'Redirects', 'seovela' ),
				'manage_options',
				'seovela-redirects',
				array( $this, 'render_redirects_page' )
			);
		}

		// 404 Monitor page - only if module is enabled
		if ( get_option( 'seovela_404_monitor_enabled', true ) ) {
			add_submenu_page(
				'seovela',
				__( '404 Monitor', 'seovela' ),
				__( '404 Monitor', 'seovela' ),
				'manage_options',
				'seovela-404-monitor',
				array( $this, 'render_404_monitor_page' )
			);
		}
	}

	/**
	 * Render redirects page
	 */
	public function render_redirects_page() {
		// Get redirects
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Pagination/search params, no state change.
		$page = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
		$per_page = 20;
		$offset = ( $page - 1 ) * $per_page;

		$args = array(
			'limit'  => $per_page,
			'offset' => $offset,
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['search'] ) ) {
			$args['search'] = sanitize_text_field( wp_unslash( $_GET['search'] ) );
		}

		$redirects = $this->redirects->get_redirects( $args );
		$total = $this->redirects->get_total_count( $args );
		$total_pages = ceil( $total / $per_page );

		require_once SEOVELA_PLUGIN_DIR . 'admin/views/redirects.php';
	}

	/**
	 * Render 404 monitor page
	 */
	public function render_404_monitor_page() {
		// Get 404 logs
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Pagination/search params, no state change.
		$page = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
		$per_page = 20;
		$offset = ( $page - 1 ) * $per_page;

		$args = array(
			'limit'    => $per_page,
			'offset'   => $offset,
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'resolved' => isset( $_GET['status'] ) && sanitize_text_field( wp_unslash( $_GET['status'] ) ) === 'resolved' ? 1 : 0,
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['search'] ) ) {
			$args['search'] = sanitize_text_field( wp_unslash( $_GET['search'] ) );
		}

		$logs = $this->monitor_404->get_logs( $args );
		$total = $this->monitor_404->get_total_count( $args );
		$total_pages = ceil( $total / $per_page );
		$statistics = $this->monitor_404->get_statistics();
		
		// Get 404 settings
		$settings_404 = get_option( 'seovela_404_settings', array(
			'cleanup_days'     => 30,
			'redirect_url'     => '',
			'redirect_enabled' => 0,
		) );

		require_once SEOVELA_PLUGIN_DIR . 'admin/views/404-monitor.php';
	}

	/**
	 * AJAX: Add redirect
	 */
	public function ajax_add_redirect() {
		check_ajax_referer( 'seovela_redirects', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$data = array(
			'source_url'    => isset( $_POST['source_url'] ) ? sanitize_text_field( wp_unslash( $_POST['source_url'] ) ) : '',
			'target_url'    => isset( $_POST['target_url'] ) ? esc_url_raw( wp_unslash( $_POST['target_url'] ) ) : '',
			'redirect_type' => isset( $_POST['redirect_type'] ) ? sanitize_text_field( wp_unslash( $_POST['redirect_type'] ) ) : '301',
			'regex'         => ! empty( $_POST['regex'] ) ? 1 : 0,
			'enabled'       => ! empty( $_POST['enabled'] ) ? 1 : 0,
		);

		$result = $this->redirects->add_redirect( $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message'     => __( 'Redirect added successfully', 'seovela' ),
			'redirect_id' => $result,
		) );
	}

	/**
	 * AJAX: Edit redirect
	 */
	public function ajax_edit_redirect() {
		check_ajax_referer( 'seovela_redirects', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$redirect_id = isset( $_POST['redirect_id'] ) ? intval( wp_unslash( $_POST['redirect_id'] ) ) : 0;

		$data = array(
			'source_url'    => isset( $_POST['source_url'] ) ? sanitize_text_field( wp_unslash( $_POST['source_url'] ) ) : '',
			'target_url'    => isset( $_POST['target_url'] ) ? esc_url_raw( wp_unslash( $_POST['target_url'] ) ) : '',
			'redirect_type' => isset( $_POST['redirect_type'] ) ? sanitize_text_field( wp_unslash( $_POST['redirect_type'] ) ) : '301',
			'regex'         => ! empty( $_POST['regex'] ) ? 1 : 0,
			'enabled'       => ! empty( $_POST['enabled'] ) ? 1 : 0,
		);

		$result = $this->redirects->update_redirect( $redirect_id, $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Redirect updated successfully', 'seovela' ) ) );
	}

	/**
	 * AJAX: Delete redirect
	 */
	public function ajax_delete_redirect() {
		check_ajax_referer( 'seovela_redirects', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$redirect_id = isset( $_POST['redirect_id'] ) ? intval( wp_unslash( $_POST['redirect_id'] ) ) : 0;

		$result = $this->redirects->delete_redirect( $redirect_id );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete redirect', 'seovela' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Redirect deleted successfully', 'seovela' ) ) );
	}

	/**
	 * AJAX: Toggle redirect enabled status
	 */
	public function ajax_toggle_redirect() {
		check_ajax_referer( 'seovela_redirects', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$redirect_id = isset( $_POST['redirect_id'] ) ? intval( wp_unslash( $_POST['redirect_id'] ) ) : 0;
		$enabled = isset( $_POST['enabled'] ) ? intval( wp_unslash( $_POST['enabled'] ) ) : 0;

		$result = $this->redirects->update_redirect( $redirect_id, array( 'enabled' => $enabled ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Redirect status updated', 'seovela' ) ) );
	}

	/**
	 * AJAX: Delete 404 log
	 */
	public function ajax_delete_404_log() {
		check_ajax_referer( 'seovela_404_monitor', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$log_id = isset( $_POST['log_id'] ) ? intval( wp_unslash( $_POST['log_id'] ) ) : 0;

		$result = $this->monitor_404->delete_log( $log_id );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete log', 'seovela' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Log deleted successfully', 'seovela' ) ) );
	}

	/**
	 * AJAX: Resolve 404
	 */
	public function ajax_resolve_404() {
		check_ajax_referer( 'seovela_404_monitor', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$log_id = isset( $_POST['log_id'] ) ? intval( wp_unslash( $_POST['log_id'] ) ) : 0;

		$result = $this->monitor_404->mark_resolved( $log_id );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to mark as resolved', 'seovela' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Marked as resolved', 'seovela' ) ) );
	}

	/**
	 * AJAX: Create redirect from 404
	 */
	public function ajax_create_redirect_from_404() {
		check_ajax_referer( 'seovela_404_monitor', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$log_id = isset( $_POST['log_id'] ) ? intval( wp_unslash( $_POST['log_id'] ) ) : 0;
		$target_url = isset( $_POST['target_url'] ) ? esc_url_raw( wp_unslash( $_POST['target_url'] ) ) : '';

		// Get 404 log
		$log = $this->monitor_404->get_log( $log_id );
		if ( ! $log ) {
			wp_send_json_error( array( 'message' => __( 'Log not found', 'seovela' ) ) );
		}

		// Create redirect
		$data = array(
			'source_url'    => $log->url,
			'target_url'    => $target_url,
			'redirect_type' => '301',
			'regex'         => 0,
			'enabled'       => 1,
		);

		$result = $this->redirects->add_redirect( $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Mark 404 as resolved
		$this->monitor_404->mark_resolved( $log_id );

		wp_send_json_success( array( 'message' => __( 'Redirect created successfully', 'seovela' ) ) );
	}

	/**
	 * AJAX: Export redirects
	 */
	public function ajax_export_redirects() {
		check_ajax_referer( 'seovela_redirects', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied', 'seovela' ) );
		}

		$csv = $this->redirects->export_csv();

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="seovela-redirects-' . gmdate( 'Y-m-d' ) . '.csv"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * AJAX: Import redirects
	 */
	public function ajax_import_redirects() {
		check_ajax_referer( 'seovela_redirects', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		if ( empty( $_FILES['csv_file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded', 'seovela' ) ) );
		}

		$file = $_FILES['csv_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- File upload handled by PHP.

		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json_error( array( 'message' => __( 'File upload error', 'seovela' ) ) );
		}

		// Validate file extension.
		$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( 'csv' !== $ext ) {
			wp_send_json_error( array( 'message' => __( 'Invalid file type. Please upload a CSV file.', 'seovela' ) ) );
		}

		// Validate file size (max 2 MB).
		if ( $file['size'] > 2 * MB_IN_BYTES ) {
			wp_send_json_error( array( 'message' => __( 'File too large. Maximum size is 2 MB.', 'seovela' ) ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local temp upload file.
		$csv_content = file_get_contents( $file['tmp_name'] );
		$result = $this->redirects->import_csv( $csv_content );

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: %d: Number of imported redirects */
				__( '%d redirects imported successfully', 'seovela' ),
				$result['success']
			),
			'success' => $result['success'],
			'errors'  => $result['errors'],
		) );
	}

	/**
	 * AJAX: Save 404 settings
	 */
	public function ajax_save_404_settings() {
		check_ajax_referer( 'seovela_404_monitor', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$settings = array(
			'cleanup_days'    => isset( $_POST['cleanup_days'] ) ? intval( wp_unslash( $_POST['cleanup_days'] ) ) : 30,
			'redirect_url'    => isset( $_POST['redirect_url'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_url'] ) ) : '',
			'redirect_enabled' => ! empty( $_POST['redirect_enabled'] ) ? 1 : 0,
		);

		update_option( 'seovela_404_settings', $settings );

		wp_send_json_success( array( 'message' => __( 'Settings saved successfully', 'seovela' ) ) );
	}

	/**
	 * AJAX: Delete all 404 logs
	 */
	public function ajax_delete_all_404() {
		check_ajax_referer( 'seovela_404_monitor', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$result = $this->monitor_404->delete_all_logs( false );

		wp_send_json_success( array( 
			'message' => __( 'All 404 logs deleted successfully', 'seovela' ),
			'deleted' => $result
		) );
	}

	/**
	 * AJAX: Cleanup resolved 404 logs
	 */
	public function ajax_cleanup_resolved() {
		check_ajax_referer( 'seovela_404_monitor', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$result = $this->monitor_404->delete_all_logs( true );

		wp_send_json_success( array( 
			'message' => __( 'Resolved logs cleaned up successfully', 'seovela' ),
			'deleted' => $result
		) );
	}
}

