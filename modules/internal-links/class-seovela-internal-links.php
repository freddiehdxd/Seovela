<?php
/**
 * Seovela Internal Links Suggestions
 *
 * Analyzes content and suggests relevant internal links
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seovela Internal Links Class
 */
class Seovela_Internal_Links {

	/**
	 * Single instance
	 *
	 * @var Seovela_Internal_Links
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
	 * @return Seovela_Internal_Links
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
		$this->table_name = $wpdb->prefix . 'seovela_link_suggestions';

		// Admin hooks
		add_action( 'admin_menu', array( $this, 'add_menu_page' ), 20 ); // Priority 20 to run after parent menu
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// AJAX hooks
		add_action( 'wp_ajax_seovela_get_link_suggestions', array( $this, 'ajax_get_suggestions' ) );
		add_action( 'wp_ajax_seovela_refresh_link_suggestions', array( $this, 'ajax_refresh_suggestions' ) );
		add_action( 'wp_ajax_seovela_get_orphan_pages', array( $this, 'ajax_get_orphan_pages' ) );
		
		// Dashboard widget
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
		
		// Metabox integration
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		
		// Auto-generate on publish
		add_action( 'publish_post', array( $this, 'auto_generate_on_publish' ), 10, 2 );
		add_action( 'publish_page', array( $this, 'auto_generate_on_publish' ), 10, 2 );
	}

	/**
	 * Create database table
	 */
	public static function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'seovela_link_suggestions';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			source_post_id bigint(20) NOT NULL,
			target_post_id bigint(20) NOT NULL,
			relevance_score decimal(3,2) NOT NULL DEFAULT 0.00,
			suggested_anchor varchar(255) DEFAULT NULL,
			match_reason varchar(100) DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY source_post_id (source_post_id),
			KEY target_post_id (target_post_id),
			KEY relevance_score (relevance_score),
			KEY status (status),
			UNIQUE KEY source_target (source_post_id, target_post_id)
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
		// Check for Internal Links page, post editors, or dashboard
		$is_internal_links_page = strpos( $hook, 'seovela-internal-links' ) !== false || strpos( $hook, 'seovela_page_seovela-internal-links' ) !== false;
		if ( ! $is_internal_links_page && 'post.php' !== $hook && 'post-new.php' !== $hook && 'index.php' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'seovela-internal-links',
			plugin_dir_url( __FILE__ ) . 'assets/js/internal-links.js',
			array( 'jquery' ),
			SEOVELA_VERSION,
			true
		);

		wp_localize_script(
			'seovela-internal-links',
			'seovelaInternalLinks',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'seovela_internal_links' ),
			)
		);

		wp_enqueue_style(
			'seovela-internal-links',
			plugin_dir_url( __FILE__ ) . 'assets/css/internal-links.css',
			array(),
			SEOVELA_VERSION
		);
	}

	/**
	 * Add metabox to post editor
	 */
	public function add_metabox() {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'seovela_internal_links',
				__( 'Internal Link Suggestions', 'seovela' ),
				array( $this, 'render_metabox' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render metabox
	 */
	public function render_metabox( $post ) {
		$suggestions = $this->get_suggestions_for_post( $post->ID );
		
		?>
		<div class="seovela-internal-links-metabox">
			<?php if ( empty( $suggestions ) ) : ?>
				<p class="description">
					<?php esc_html_e( 'No link suggestions yet. Publish this post to generate suggestions.', 'seovela' ); ?>
				</p>
			<?php else : ?>
				<div class="link-suggestions-list">
					<?php foreach ( $suggestions as $suggestion ) : 
						$target_post = get_post( $suggestion->target_post_id );
						if ( ! $target_post ) continue;
						?>
						<div class="suggestion-item" data-post-id="<?php echo esc_attr( $target_post->ID ); ?>">
							<div class="suggestion-score">
								<span class="score-badge score-<?php echo esc_attr( $this->get_score_class( $suggestion->relevance_score ) ); ?>">
									<?php echo esc_html( number_format( $suggestion->relevance_score * 100 ) ); ?>%
								</span>
							</div>
							<div class="suggestion-content">
								<strong><?php echo esc_html( $target_post->post_title ); ?></strong>
								<p class="description">
									<?php printf( 
										__( 'Anchor: %s | Reason: %s', 'seovela' ), 
										esc_html( $suggestion->suggested_anchor ),
										esc_html( $suggestion->match_reason )
									); ?>
								</p>
								<button type="button" class="button button-small insert-link-btn" 
									data-url="<?php echo esc_url( get_permalink( $target_post->ID ) ); ?>"
									data-title="<?php echo esc_attr( $target_post->post_title ); ?>"
									data-anchor="<?php echo esc_attr( $suggestion->suggested_anchor ); ?>">
									<?php esc_html_e( 'Insert Link', 'seovela' ); ?>
								</button>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button button-secondary button-block refresh-suggestions-btn" 
					data-post-id="<?php echo esc_attr( $post->ID ); ?>">
					<?php esc_html_e( 'Refresh Suggestions', 'seovela' ); ?>
				</button>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get score class for badge
	 */
	private function get_score_class( $score ) {
		if ( $score >= 0.7 ) {
			return 'high';
		} elseif ( $score >= 0.4 ) {
			return 'medium';
		}
		return 'low';
	}

	/**
	 * Add dashboard widget
	 */
	public function add_dashboard_widget() {
		if ( ! get_option( 'seovela_internal_links_enabled', true ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'seovela_internal_links_widget',
			__( 'Internal Linking Opportunities', 'seovela' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render dashboard widget
	 */
	public function render_dashboard_widget() {
		$stats = $this->get_linking_stats();
		
		?>
		<div class="seovela-dashboard-widget internal-links-widget">
			<div class="stats-grid">
				<div class="stat-item">
					<div class="stat-icon dashicons dashicons-admin-links"></div>
					<span class="stat-value"><?php echo esc_html( number_format( $stats['total_suggestions'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Opportunities', 'seovela' ); ?></span>
				</div>
				<div class="stat-item <?php echo $stats['orphan_pages'] > 0 ? 'stat-warning' : ''; ?>">
					<div class="stat-icon dashicons dashicons-warning"></div>
					<span class="stat-value"><?php echo esc_html( number_format( $stats['orphan_pages'] ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Orphan Pages', 'seovela' ); ?></span>
				</div>
				<div class="stat-item">
					<div class="stat-icon dashicons dashicons-analytics"></div>
					<span class="stat-value"><?php echo esc_html( number_format( $stats['avg_internal_links'], 1 ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Avg Links/Post', 'seovela' ); ?></span>
				</div>
			</div>
			
			<?php if ( $stats['total_suggestions'] > 0 ) : ?>
				<div class="widget-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-internal-links' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Review Suggestions', 'seovela' ); ?>
					</a>
				</div>
			<?php else : ?>
				<div class="widget-message">
					<p><?php esc_html_e( 'Great job! No pending link suggestions.', 'seovela' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get linking statistics
	 */
	private function get_linking_stats() {
		global $wpdb;
		
		$total_suggestions = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'pending'"
		);
		
		// Get orphan pages (posts with no internal links pointing to them)
		$orphan_pages = $wpdb->get_var(
			"SELECT COUNT(DISTINCT ID) 
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_seovela_incoming_links'
			WHERE p.post_status = 'publish' 
			AND p.post_type IN ('post', 'page')
			AND (pm.meta_value IS NULL OR pm.meta_value = '0')"
		);
		
		// Average internal links per post
		$avg_links = $wpdb->get_var(
			"SELECT AVG(CAST(meta_value AS UNSIGNED))
			FROM {$wpdb->postmeta}
			WHERE meta_key = '_seovela_outgoing_links'"
		);
		
		return array(
			'total_suggestions' => (int) $total_suggestions,
			'orphan_pages'      => (int) $orphan_pages,
			'avg_internal_links' => (float) $avg_links,
		);
	}

	/**
	 * Render admin page
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle settings save
		if ( isset( $_POST['seovela_internal_links_settings'] ) && 
			 check_admin_referer( 'seovela_internal_links_settings' ) ) {
			$this->save_settings();
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'seovela' ) . '</p></div>';
		}

		$settings = $this->get_settings();
		
		require_once plugin_dir_path( __FILE__ ) . 'views/internal-links.php';
	}

	/**
	 * Get settings
	 */
	private function get_settings() {
		return array(
			'enabled'           => get_option( 'seovela_internal_links_enabled', true ),
			'min_score'         => get_option( 'seovela_internal_links_min_score', 0.3 ),
			'max_suggestions'   => get_option( 'seovela_internal_links_max_suggestions', 5 ),
			'excluded_posts'    => get_option( 'seovela_internal_links_excluded_posts', array() ),
			'auto_refresh'      => get_option( 'seovela_internal_links_auto_refresh', true ),
		);
	}

	/**
	 * Save settings
	 */
	private function save_settings() {
		update_option( 'seovela_internal_links_enabled', isset( $_POST['enabled'] ) );
		update_option( 'seovela_internal_links_min_score', isset( $_POST['min_score'] ) ? floatval( $_POST['min_score'] ) : 0.3 );
		update_option( 'seovela_internal_links_max_suggestions', isset( $_POST['max_suggestions'] ) ? absint( $_POST['max_suggestions'] ) : 5 );
		update_option( 'seovela_internal_links_auto_refresh', isset( $_POST['auto_refresh'] ) );
	}

	/**
	 * Get suggestions for a post
	 */
	public function get_suggestions_for_post( $post_id, $limit = 5 ) {
		global $wpdb;
		
		$min_score = get_option( 'seovela_internal_links_min_score', 0.3 );
		
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name}
				WHERE source_post_id = %d
				AND status = 'pending'
				AND relevance_score >= %f
				ORDER BY relevance_score DESC
				LIMIT %d",
				$post_id,
				$min_score,
				$limit
			)
		);
	}

	/**
	 * Generate suggestions for a post
	 */
	public function generate_suggestions( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || $post->post_status !== 'publish' ) {
			return false;
		}

		// Clear existing suggestions
		global $wpdb;
		$wpdb->delete( $this->table_name, array( 'source_post_id' => $post_id ) );

		// Get published posts except current (bounded to prevent memory exhaustion on large sites).
		$max_posts = (int) apply_filters( 'seovela_internal_links_max_posts', 500 );
		$posts = get_posts( array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => $max_posts,
			'exclude'        => array( $post_id ),
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );

		$suggestions = array();
		$source_content = $this->prepare_content_for_analysis( $post );
		$source_keywords = $this->extract_keywords( $source_content );

		foreach ( $posts as $target_post_id ) {
			$score_data = $this->calculate_relevance( $post_id, $target_post_id, $source_keywords );
			
			if ( $score_data['score'] > 0 ) {
				$suggestions[] = array(
					'source_post_id'   => $post_id,
					'target_post_id'   => $target_post_id,
					'relevance_score'  => $score_data['score'],
					'suggested_anchor' => $score_data['anchor'],
					'match_reason'     => $score_data['reason'],
					'status'           => 'pending',
					'created_at'       => current_time( 'mysql', true ),
					'updated_at'       => current_time( 'mysql', true ),
				);
			}
		}

		// Sort by score and limit
		usort( $suggestions, function( $a, $b ) {
			return $b['relevance_score'] <=> $a['relevance_score'];
		} );

		$max_suggestions = get_option( 'seovela_internal_links_max_suggestions', 5 );
		$suggestions = array_slice( $suggestions, 0, $max_suggestions * 2 ); // Store 2x for variety

		// Insert suggestions
		foreach ( $suggestions as $suggestion ) {
			$wpdb->insert( $this->table_name, $suggestion );
		}

		return count( $suggestions );
	}

	/**
	 * Calculate relevance between two posts
	 */
	private function calculate_relevance( $source_id, $target_id, $source_keywords ) {
		$target_post = get_post( $target_id );
		$target_content = $this->prepare_content_for_analysis( $target_post );
		$target_keywords = $this->extract_keywords( $target_content );

		$score = 0;
		$reasons = array();
		$suggested_anchor = $target_post->post_title;

		// Check keyword overlap
		$common_keywords = array_intersect( $source_keywords, $target_keywords );
		if ( ! empty( $common_keywords ) ) {
			$keyword_score = count( $common_keywords ) / max( count( $source_keywords ), 1 );
			$score += $keyword_score * 0.4;
			$reasons[] = count( $common_keywords ) . ' common keywords';
			$suggested_anchor = current( $common_keywords );
		}

		// Check category overlap
		$source_categories = wp_get_post_categories( $source_id );
		$target_categories = wp_get_post_categories( $target_id );
		$common_categories = array_intersect( $source_categories, $target_categories );
		
		if ( ! empty( $common_categories ) ) {
			$score += 0.3;
			$reasons[] = 'Same category';
		}

		// Check tag overlap
		$source_tags = wp_get_post_tags( $source_id, array( 'fields' => 'ids' ) );
		$target_tags = wp_get_post_tags( $target_id, array( 'fields' => 'ids' ) );
		$common_tags = array_intersect( $source_tags, $target_tags );
		
		if ( ! empty( $common_tags ) ) {
			$score += 0.2;
			$reasons[] = 'Similar tags';
		}

		// Check title similarity
		$source_title = get_the_title( $source_id );
		$target_title = get_the_title( $target_id );
		similar_text( strtolower( $source_title ), strtolower( $target_title ), $similarity );
		
		if ( $similarity > 30 ) {
			$score += 0.1;
			$reasons[] = 'Similar titles';
		}

		return array(
			'score'  => min( $score, 1.0 ),
			'anchor' => $suggested_anchor,
			'reason' => implode( ', ', $reasons ),
		);
	}

	/**
	 * Prepare content for analysis
	 */
	private function prepare_content_for_analysis( $post ) {
		$content = $post->post_title . ' ' . $post->post_content;
		$content = strip_tags( $content );
		$content = strip_shortcodes( $content );
		return strtolower( $content );
	}

	/**
	 * Extract keywords from content
	 */
	private function extract_keywords( $content, $limit = 20 ) {
		// Remove common stop words
		$stop_words = array( 'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'is', 'was', 'are', 'were', 'been', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'can', 'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they' );
		
		// Split into words
		$words = preg_split( '/\s+/', $content );
		$words = array_map( 'trim', $words );
		$words = array_filter( $words, function( $word ) use ( $stop_words ) {
			return strlen( $word ) > 3 && ! in_array( $word, $stop_words );
		} );

		// Count frequency
		$word_counts = array_count_values( $words );
		arsort( $word_counts );

		// Return top keywords
		return array_keys( array_slice( $word_counts, 0, $limit ) );
	}

	/**
	 * Auto-generate suggestions on publish
	 */
	public function auto_generate_on_publish( $post_id, $post ) {
		// Check if auto-refresh is enabled
		if ( ! get_option( 'seovela_internal_links_auto_refresh', true ) ) {
			return;
		}
		
		// Skip revisions and autosaves
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		
		// Generate suggestions
		$this->generate_suggestions( $post_id );
	}

	/**
	 * AJAX: Get suggestions
	 */
	public function ajax_get_suggestions() {
		check_ajax_referer( 'seovela_internal_links', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		$suggestions = $this->get_suggestions_for_post( $post_id );

		wp_send_json_success( array(
			'suggestions' => $suggestions,
		) );
	}

	/**
	 * AJAX: Refresh suggestions
	 */
	public function ajax_refresh_suggestions() {
		check_ajax_referer( 'seovela_internal_links', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		$count = $this->generate_suggestions( $post_id );

		wp_send_json_success( array(
			'message' => sprintf( __( 'Generated %d suggestions.', 'seovela' ), $count ),
		) );
	}

	/**
	 * AJAX: Get orphan pages
	 */
	public function ajax_get_orphan_pages() {
		check_ajax_referer( 'seovela_internal_links', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'seovela' ) ) );
		}

		global $wpdb;
		
		$orphans = $wpdb->get_results(
			"SELECT p.ID, p.post_title, p.post_type, p.post_date
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_seovela_incoming_links'
			WHERE p.post_status = 'publish' 
			AND p.post_type IN ('post', 'page')
			AND (pm.meta_value IS NULL OR pm.meta_value = '0')
			ORDER BY p.post_date DESC
			LIMIT 50"
		);

		wp_send_json_success( array(
			'orphans' => $orphans,
		) );
	}
}

