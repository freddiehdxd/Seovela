<?php
/**
 * Seovela AJAX Handler
 *
 * Handles AJAX requests for SEO analysis
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seovela AJAX Class
 */
class Seovela_Ajax {

	/**
	 * Hooks added flag
	 *
	 * @var bool
	 */
	private static $hooks_added = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Prevent duplicate hooks
		if ( self::$hooks_added ) {
			return;
		}
		self::$hooks_added = true;

		// Real-time SEO analysis
		add_action( 'wp_ajax_seovela_analyze_content', array( $this, 'analyze_content' ) );
		
		// Schema preview
		add_action( 'wp_ajax_seovela_preview_schema', array( $this, 'preview_schema' ) );

		// Dashboard tip dismissal
		add_action( 'wp_ajax_seovela_dismiss_tip', array( $this, 'dismiss_tip' ) );
	}

	/**
	 * Analyze content in real-time
	 */
	public function analyze_content() {
		// Check nonce
		check_ajax_referer( 'seovela_metabox_nonce', 'nonce' );

		// Check permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		// Get data from request
		$post_id = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : 0;
		$focus_keyword = isset( $_POST['focus_keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['focus_keyword'] ) ) : '';
		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		$content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';

		// Load SEO scorer
		require_once SEOVELA_PLUGIN_DIR . 'modules/content-analysis/class-seo-scorer.php';
		$scorer = new Seovela_SEO_Scorer();

		// Prepare data
		$data = array(
			'title'       => $title,
			'description' => $description,
			'content'     => $content,
			'url'         => $url,
		);

		// Perform analysis
		$results = $scorer->analyze_data( $data, $focus_keyword, $post_id );

		// Send response
		wp_send_json_success( $results );
	}

	/**
	 * Preview schema for current post
	 */
	public function preview_schema() {
		// Check nonce
		check_ajax_referer( 'seovela_metabox_nonce', 'nonce' );

		// Check permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		// Get post ID
		$post_id = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'seovela' ) ) );
		}

		// Load schema builder
		require_once SEOVELA_PLUGIN_DIR . 'modules/schema/class-schema-builder.php';

		// Get schema preview
		$preview = Seovela_Schema_Builder::get_schema_preview( $post_id );

		if ( empty( $preview ) ) {
			wp_send_json_success( array(
				'preview' => __( 'No schema generated. Make sure you have selected a schema type and filled in required fields.', 'seovela' ),
			) );
		}

		// Send response
		wp_send_json_success( array(
			'preview' => $preview,
		) );
	}

	/**
	 * Dismiss dashboard tip banner
	 *
	 * Stores dismissal in user meta so the tip stays hidden across page loads.
	 */
	public function dismiss_tip() {
		check_ajax_referer( 'seovela_dismiss_tip', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		update_user_meta( get_current_user_id(), 'seovela_tip_dismissed', 1 );

		wp_send_json_success();
	}
}

