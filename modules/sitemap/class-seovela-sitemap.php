<?php
/**
 * Seovela Sitemap Module
 *
 * High-performance XML sitemap system with sitemap index, pagination,
 * caching, image entries, XSL stylesheet, and IndexNow support.
 *
 * @package Seovela
 * @since   2.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seovela Sitemap Class
 */
class Seovela_Sitemap {

	/**
	 * Maximum number of URLs per sitemap file (Google recommendation).
	 *
	 * @var int
	 */
	const URLS_PER_SITEMAP = 1000;

	/**
	 * Transient expiration in seconds (24 hours).
	 *
	 * @var int
	 */
	const CACHE_EXPIRATION = DAY_IN_SECONDS;

	/**
	 * Transient key prefix.
	 *
	 * @var string
	 */
	const CACHE_PREFIX = 'seovela_sitemap_';

	/**
	 * IndexNow API endpoint.
	 *
	 * @var string
	 */
	const INDEXNOW_ENDPOINT = 'https://api.indexnow.org/indexnow';

	/**
	 * Google ping URL.
	 *
	 * @var string
	 */
	const GOOGLE_PING_URL = 'https://www.google.com/ping?sitemap=';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_action( 'template_redirect', array( $this, 'serve_sitemap' ) );

		// Cache invalidation hooks.
		add_action( 'save_post', array( $this, 'invalidate_cache_on_post_change' ), 10, 2 );
		add_action( 'delete_post', array( $this, 'invalidate_cache_on_post_delete' ), 10, 1 );
		add_action( 'transition_post_status', array( $this, 'invalidate_cache_on_status_change' ), 10, 3 );
		add_action( 'edit_term', array( $this, 'invalidate_cache_on_term_change' ), 10, 3 );
		add_action( 'delete_term', array( $this, 'invalidate_cache_on_term_change' ), 10, 3 );
	}

	// ------------------------------------------------------------------
	//  Rewrite rules & query vars
	// ------------------------------------------------------------------

	/**
	 * Register rewrite rules for all sitemap endpoints.
	 */
	public function add_rewrite_rules() {
		// Sitemap index.
		add_rewrite_rule(
			'^sitemap\.xml$',
			'index.php?seovela_sitemap=index',
			'top'
		);

		// XSL stylesheet.
		add_rewrite_rule(
			'^sitemap-style\.xsl$',
			'index.php?seovela_sitemap=xsl',
			'top'
		);

		// Post-type sub-sitemaps: {post_type}-sitemap{page}.xml
		add_rewrite_rule(
			'^([a-zA-Z0-9_-]+)-sitemap([0-9]+)?\.xml$',
			'index.php?seovela_sitemap=posttype&seovela_sitemap_type=$matches[1]&seovela_sitemap_page=$matches[2]',
			'top'
		);

		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
	}

	/**
	 * Register custom query vars.
	 *
	 * @param array $vars Existing query vars.
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'seovela_sitemap';
		$vars[] = 'seovela_sitemap_type';
		$vars[] = 'seovela_sitemap_page';
		return $vars;
	}

	// ------------------------------------------------------------------
	//  Request routing
	// ------------------------------------------------------------------

	/**
	 * Route the request to the appropriate sitemap handler.
	 */
	public function serve_sitemap() {
		$sitemap = get_query_var( 'seovela_sitemap' );

		if ( ! $sitemap ) {
			return;
		}

		switch ( $sitemap ) {
			case 'index':
				$this->send_xml( $this->get_sitemap_index() );
				break;

			case 'xsl':
				$this->send_xsl();
				break;

			case 'posttype':
				$type = sanitize_key( get_query_var( 'seovela_sitemap_type', '' ) );
				$page = absint( get_query_var( 'seovela_sitemap_page', 1 ) );
				$page = max( 1, $page );

				$xml = $this->get_sub_sitemap( $type, $page );

				if ( false === $xml ) {
					// Not a valid sitemap request – let WP handle the 404.
					return;
				}

				$this->send_xml( $xml );
				break;

			default:
				return;
		}
	}

	/**
	 * Send XML response with proper headers and exit.
	 *
	 * @param string $xml The XML content.
	 */
	private function send_xml( $xml ) {
		if ( ! headers_sent() ) {
			status_header( 200 );
			header( 'Content-Type: application/xml; charset=UTF-8' );
			header( 'X-Robots-Tag: noindex, follow' );
			header( 'Cache-Control: public, max-age=3600' );
		}

		echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Send XSL stylesheet response.
	 */
	private function send_xsl() {
		if ( ! headers_sent() ) {
			status_header( 200 );
			header( 'Content-Type: text/xsl; charset=UTF-8' );
			header( 'Cache-Control: public, max-age=86400' );
		}

		echo $this->generate_xsl(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	// ------------------------------------------------------------------
	//  Sitemap index
	// ------------------------------------------------------------------

	/**
	 * Build or retrieve the cached sitemap index.
	 *
	 * @return string XML string.
	 */
	public function get_sitemap_index() {
		$cache_key = self::CACHE_PREFIX . 'index';
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$xml = $this->generate_sitemap_index();
		set_transient( $cache_key, $xml, self::CACHE_EXPIRATION );
		return $xml;
	}

	/**
	 * Generate the sitemap index XML.
	 *
	 * @return string
	 */
	private function generate_sitemap_index() {
		global $wpdb;

		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<?xml-stylesheet type="text/xsl" href="' . esc_url( home_url( '/sitemap-style.xsl' ) ) . '"?>' . "\n";
		$xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		// Post-type sitemaps.
		$post_types = $this->get_enabled_post_types();

		foreach ( $post_types as $post_type ) {
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					 FROM {$wpdb->posts} p
					 LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_seovela_noindex'
					 WHERE p.post_type  = %s
					   AND p.post_status = 'publish'
					   AND ( pm.meta_value IS NULL OR pm.meta_value = '' OR pm.meta_value = '0' )",
					$post_type
				)
			);

			if ( $count < 1 ) {
				continue;
			}

			$total_pages = (int) ceil( $count / self::URLS_PER_SITEMAP );

			// Determine the latest lastmod for this type.
			$lastmod = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX(p.post_modified_gmt)
					 FROM {$wpdb->posts} p
					 LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_seovela_noindex'
					 WHERE p.post_type  = %s
					   AND p.post_status = 'publish'
					   AND ( pm.meta_value IS NULL OR pm.meta_value = '' OR pm.meta_value = '0' )",
					$post_type
				)
			);

			for ( $page = 1; $page <= $total_pages; $page++ ) {
				$xml .= "\t<sitemap>\n";
				$xml .= "\t\t<loc>" . esc_url( home_url( '/' . $post_type . '-sitemap' . $page . '.xml' ) ) . "</loc>\n";

				if ( $lastmod ) {
					$xml .= "\t\t<lastmod>" . mysql2date( 'Y-m-d\TH:i:s+00:00', $lastmod ) . "</lastmod>\n";
				}

				$xml .= "\t</sitemap>\n";
			}
		}

		// Taxonomy sitemaps – one file each (taxonomies rarely exceed 1000 terms).
		$taxonomies = $this->get_enabled_taxonomies();

		foreach ( $taxonomies as $taxonomy ) {
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					 FROM {$wpdb->term_taxonomy}
					 WHERE taxonomy = %s
					   AND count > 0",
					$taxonomy
				)
			);

			if ( $count < 1 ) {
				continue;
			}

			$total_pages = (int) ceil( $count / self::URLS_PER_SITEMAP );

			for ( $page = 1; $page <= $total_pages; $page++ ) {
				$xml .= "\t<sitemap>\n";
				$xml .= "\t\t<loc>" . esc_url( home_url( '/' . $taxonomy . '-sitemap' . $page . '.xml' ) ) . "</loc>\n";
				$xml .= "\t</sitemap>\n";
			}
		}

		$xml .= '</sitemapindex>';

		return $xml;
	}

	// ------------------------------------------------------------------
	//  Sub-sitemaps (post types & taxonomies)
	// ------------------------------------------------------------------

	/**
	 * Get a sub-sitemap by type and page, with caching.
	 *
	 * @param string $type The post type or taxonomy slug.
	 * @param int    $page Page number (1-based).
	 * @return string|false XML string or false if invalid.
	 */
	public function get_sub_sitemap( $type, $page ) {
		$cache_key = self::CACHE_PREFIX . $type . '_' . $page;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		// Determine if this is a post-type or taxonomy sitemap.
		$post_types = $this->get_enabled_post_types();
		$taxonomies = $this->get_enabled_taxonomies();

		if ( in_array( $type, $post_types, true ) ) {
			$xml = $this->generate_post_type_sitemap( $type, $page );
		} elseif ( in_array( $type, $taxonomies, true ) ) {
			$xml = $this->generate_taxonomy_sitemap( $type, $page );
		} else {
			return false;
		}

		if ( false === $xml ) {
			return false;
		}

		set_transient( $cache_key, $xml, self::CACHE_EXPIRATION );
		return $xml;
	}

	/**
	 * Generate a post-type sub-sitemap page.
	 *
	 * Uses direct $wpdb queries with LIMIT/OFFSET for memory efficiency.
	 *
	 * @param string $post_type Post type slug.
	 * @param int    $page      Page number (1-based).
	 * @return string|false XML or false on empty/invalid page.
	 */
	private function generate_post_type_sitemap( $post_type, $page ) {
		global $wpdb;

		$offset = ( $page - 1 ) * self::URLS_PER_SITEMAP;

		// Fetch IDs, dates, and permalink components in a single query.
		$posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_modified_gmt, p.post_type, p.post_name, p.post_date_gmt
				 FROM {$wpdb->posts} p
				 LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_seovela_noindex'
				 WHERE p.post_type   = %s
				   AND p.post_status = 'publish'
				   AND ( pm.meta_value IS NULL OR pm.meta_value = '' OR pm.meta_value = '0' )
				 ORDER BY p.post_modified_gmt DESC
				 LIMIT %d OFFSET %d",
				$post_type,
				self::URLS_PER_SITEMAP,
				$offset
			)
		);

		if ( empty( $posts ) ) {
			return false;
		}

		// Batch-fetch featured image IDs for all posts in this page.
		$post_ids    = wp_list_pluck( $posts, 'ID' );
		$image_map   = $this->get_featured_images_batch( $post_ids );

		// Priority & changefreq per post type.
		$priority   = $this->get_priority_for_type( $post_type );
		$changefreq = $this->get_changefreq_for_type( $post_type );

		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<?xml-stylesheet type="text/xsl" href="' . esc_url( home_url( '/sitemap-style.xsl' ) ) . '"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
		$xml .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

		// Prepend homepage for the first page of the 'page' post type.
		if ( 'page' === $post_type && 1 === $page ) {
			$xml .= $this->build_homepage_entry();
		}

		foreach ( $posts as $post ) {
			$permalink = get_permalink( $post->ID );

			if ( ! $permalink ) {
				continue;
			}

			$xml .= "\t<url>\n";
			$xml .= "\t\t<loc>" . esc_url( $permalink ) . "</loc>\n";
			$xml .= "\t\t<lastmod>" . mysql2date( 'Y-m-d\TH:i:s+00:00', $post->post_modified_gmt ) . "</lastmod>\n";
			$xml .= "\t\t<changefreq>{$changefreq}</changefreq>\n";
			$xml .= "\t\t<priority>{$priority}</priority>\n";

			// Image entry.
			if ( isset( $image_map[ $post->ID ] ) ) {
				$img = $image_map[ $post->ID ];
				$xml .= "\t\t<image:image>\n";
				$xml .= "\t\t\t<image:loc>" . esc_url( $img['url'] ) . "</image:loc>\n";

				if ( ! empty( $img['title'] ) ) {
					$xml .= "\t\t\t<image:title>" . $this->xml_escape( $img['title'] ) . "</image:title>\n";
				}

				if ( ! empty( $img['alt'] ) ) {
					$xml .= "\t\t\t<image:caption>" . $this->xml_escape( $img['alt'] ) . "</image:caption>\n";
				}

				$xml .= "\t\t</image:image>\n";
			}

			$xml .= "\t</url>\n";
		}

		$xml .= '</urlset>';

		return $xml;
	}

	/**
	 * Generate a taxonomy sub-sitemap page.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param int    $page     Page number (1-based).
	 * @return string|false XML or false on empty.
	 */
	private function generate_taxonomy_sitemap( $taxonomy, $page ) {
		global $wpdb;

		$offset = ( $page - 1 ) * self::URLS_PER_SITEMAP;

		$terms = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.term_id, t.name, t.slug, tt.taxonomy
				 FROM {$wpdb->terms} t
				 INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
				 WHERE tt.taxonomy = %s
				   AND tt.count > 0
				 ORDER BY t.term_id ASC
				 LIMIT %d OFFSET %d",
				$taxonomy,
				self::URLS_PER_SITEMAP,
				$offset
			)
		);

		if ( empty( $terms ) ) {
			return false;
		}

		$priority   = $this->get_priority_for_taxonomy();
		$changefreq = 'weekly';

		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<?xml-stylesheet type="text/xsl" href="' . esc_url( home_url( '/sitemap-style.xsl' ) ) . '"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		foreach ( $terms as $term ) {
			$link = get_term_link( (int) $term->term_id, $term->taxonomy );

			if ( is_wp_error( $link ) ) {
				continue;
			}

			// Find the latest published post in this term for lastmod.
			$term_lastmod = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX(p.post_modified_gmt)
					 FROM {$wpdb->posts} p
					 INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
					 WHERE tr.term_taxonomy_id = %d
					   AND p.post_status = 'publish'",
					$term->term_id
				)
			);

			$xml .= "\t<url>\n";
			$xml .= "\t\t<loc>" . esc_url( $link ) . "</loc>\n";

			if ( $term_lastmod ) {
				$xml .= "\t\t<lastmod>" . mysql2date( 'Y-m-d\TH:i:s+00:00', $term_lastmod ) . "</lastmod>\n";
			}

			$xml .= "\t\t<changefreq>{$changefreq}</changefreq>\n";
			$xml .= "\t\t<priority>{$priority}</priority>\n";
			$xml .= "\t</url>\n";
		}

		$xml .= '</urlset>';

		return $xml;
	}

	// ------------------------------------------------------------------
	//  Homepage entry
	// ------------------------------------------------------------------

	/**
	 * Build the homepage URL entry with highest priority.
	 *
	 * @return string XML fragment.
	 */
	private function build_homepage_entry() {
		$xml  = "\t<url>\n";
		$xml .= "\t\t<loc>" . esc_url( home_url( '/' ) ) . "</loc>\n";
		$xml .= "\t\t<lastmod>" . gmdate( 'Y-m-d\TH:i:s+00:00' ) . "</lastmod>\n";
		$xml .= "\t\t<changefreq>daily</changefreq>\n";
		$xml .= "\t\t<priority>1.0</priority>\n";
		$xml .= "\t</url>\n";

		return $xml;
	}

	// ------------------------------------------------------------------
	//  Image helpers
	// ------------------------------------------------------------------

	/**
	 * Batch-fetch featured images for a list of post IDs.
	 *
	 * Performs two queries total instead of one per post.
	 *
	 * @param array $post_ids Array of post IDs.
	 * @return array Keyed by post ID => [ 'url', 'title', 'alt' ].
	 */
	private function get_featured_images_batch( array $post_ids ) {
		global $wpdb;

		if ( empty( $post_ids ) ) {
			return array();
		}

		// Step 1: Get thumbnail IDs for all posts.
		$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$thumb_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value AS thumbnail_id
				 FROM {$wpdb->postmeta}
				 WHERE meta_key = '_thumbnail_id'
				   AND post_id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				...$post_ids
			)
		);

		if ( empty( $thumb_rows ) ) {
			return array();
		}

		// Map post_id => thumbnail_id.
		$post_thumb_map = array();
		$thumb_ids      = array();

		foreach ( $thumb_rows as $row ) {
			$thumb_id = absint( $row->thumbnail_id );

			if ( $thumb_id ) {
				$post_thumb_map[ (int) $row->post_id ] = $thumb_id;
				$thumb_ids[]                            = $thumb_id;
			}
		}

		if ( empty( $thumb_ids ) ) {
			return array();
		}

		$thumb_ids    = array_unique( $thumb_ids );
		$placeholders = implode( ',', array_fill( 0, count( $thumb_ids ), '%d' ) );

		// Step 2: Get attachment URLs (from guid) and alt text.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$attachments = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.guid, p.post_title,
				        pm_alt.meta_value AS alt_text
				 FROM {$wpdb->posts} p
				 LEFT JOIN {$wpdb->postmeta} pm_alt ON p.ID = pm_alt.post_id AND pm_alt.meta_key = '_wp_attachment_image_alt'
				 WHERE p.ID IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				...$thumb_ids
			)
		);

		// Map attachment_id => data.
		$attachment_map = array();
		foreach ( $attachments as $att ) {
			$attachment_map[ (int) $att->ID ] = array(
				'url'   => $att->guid,
				'title' => $att->post_title,
				'alt'   => $att->alt_text,
			);
		}

		// Step 3: Build result keyed by post_id.
		$result = array();
		foreach ( $post_thumb_map as $post_id => $thumb_id ) {
			if ( isset( $attachment_map[ $thumb_id ] ) ) {
				$result[ $post_id ] = $attachment_map[ $thumb_id ];
			}
		}

		return $result;
	}

	// ------------------------------------------------------------------
	//  Priority & changefreq helpers
	// ------------------------------------------------------------------

	/**
	 * Get priority value for a post type.
	 *
	 * @param string $post_type Post type slug.
	 * @return string
	 */
	private function get_priority_for_type( $post_type ) {
		$map = array(
			'post' => '0.8',
			'page' => '0.6',
		);

		return isset( $map[ $post_type ] ) ? $map[ $post_type ] : '0.5';
	}

	/**
	 * Get changefreq value for a post type.
	 *
	 * @param string $post_type Post type slug.
	 * @return string
	 */
	private function get_changefreq_for_type( $post_type ) {
		$map = array(
			'post' => 'weekly',
			'page' => 'monthly',
		);

		return isset( $map[ $post_type ] ) ? $map[ $post_type ] : 'weekly';
	}

	/**
	 * Get priority value for taxonomy archive pages.
	 *
	 * @return string
	 */
	private function get_priority_for_taxonomy() {
		return '0.4';
	}

	// ------------------------------------------------------------------
	//  Configuration helpers
	// ------------------------------------------------------------------

	/**
	 * Get the list of enabled post types for the sitemap.
	 *
	 * @return array
	 */
	private function get_enabled_post_types() {
		$saved = get_option( 'seovela_sitemap_post_types', array( 'post', 'page' ) );
		return is_array( $saved ) ? array_map( 'sanitize_key', $saved ) : array( 'post', 'page' );
	}

	/**
	 * Get the list of enabled taxonomies for the sitemap.
	 *
	 * @return array
	 */
	private function get_enabled_taxonomies() {
		$saved = get_option( 'seovela_sitemap_taxonomies', array( 'category' ) );
		return is_array( $saved ) ? array_map( 'sanitize_key', $saved ) : array( 'category' );
	}

	// ------------------------------------------------------------------
	//  Cache invalidation
	// ------------------------------------------------------------------

	/**
	 * Invalidate sitemap cache when a post is saved.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function invalidate_cache_on_post_change( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$this->flush_sitemap_cache_for_type( $post->post_type );
		$this->schedule_ping();
	}

	/**
	 * Invalidate sitemap cache when a post is deleted.
	 *
	 * @param int $post_id Post ID.
	 */
	public function invalidate_cache_on_post_delete( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		$this->flush_sitemap_cache_for_type( $post->post_type );
		$this->schedule_ping();
	}

	/**
	 * Invalidate cache when post status transitions.
	 *
	 * @param string   $new_status New status.
	 * @param string   $old_status Old status.
	 * @param \WP_Post $post       Post object.
	 */
	public function invalidate_cache_on_status_change( $new_status, $old_status, $post ) {
		// Only care about transitions involving 'publish'.
		if ( 'publish' !== $new_status && 'publish' !== $old_status ) {
			return;
		}

		$this->flush_sitemap_cache_for_type( $post->post_type );
		$this->schedule_ping();
	}

	/**
	 * Invalidate cache when a taxonomy term is edited or deleted.
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function invalidate_cache_on_term_change( $term_id, $tt_id, $taxonomy ) {
		$this->flush_sitemap_cache_for_type( $taxonomy );
	}

	/**
	 * Delete all transient caches for a specific sitemap type, plus the index.
	 *
	 * @param string $type Post type or taxonomy slug.
	 */
	private function flush_sitemap_cache_for_type( $type ) {
		global $wpdb;

		// Delete all pages for this type.
		$like = $wpdb->esc_like( '_transient_' . self::CACHE_PREFIX . $type . '_' ) . '%';
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$like
			)
		);

		// Delete timeout companions.
		$like_timeout = $wpdb->esc_like( '_transient_timeout_' . self::CACHE_PREFIX . $type . '_' ) . '%';
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$like_timeout
			)
		);

		// Always clear the index cache too.
		delete_transient( self::CACHE_PREFIX . 'index' );
	}

	/**
	 * Flush all sitemap caches (used by the "Regenerate" button).
	 */
	public function flush_all_sitemap_caches() {
		global $wpdb;

		$like = $wpdb->esc_like( '_transient_' . self::CACHE_PREFIX ) . '%';
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$like
			)
		);

		$like_timeout = $wpdb->esc_like( '_transient_timeout_' . self::CACHE_PREFIX ) . '%';
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$like_timeout
			)
		);

		flush_rewrite_rules();
	}

	// ------------------------------------------------------------------
	//  Ping services
	// ------------------------------------------------------------------

	/**
	 * Schedule a non-blocking ping to Google after sitemap update.
	 *
	 * Avoids multiple pings during batch operations by using a short-lived
	 * transient as a debounce mechanism.
	 */
	private function schedule_ping() {
		if ( get_transient( 'seovela_sitemap_ping_debounce' ) ) {
			return;
		}

		set_transient( 'seovela_sitemap_ping_debounce', 1, 60 );

		// Fire asynchronously via shutdown hook to avoid slowing requests.
		add_action( 'shutdown', array( $this, 'ping_google' ) );
	}

	/**
	 * Ping Google about the sitemap update.
	 */
	public function ping_google() {
		$sitemap_url = home_url( '/sitemap.xml' );

		wp_remote_get(
			self::GOOGLE_PING_URL . rawurlencode( $sitemap_url ),
			array(
				'timeout'   => 3,
				'blocking'  => false,
				'sslverify' => true,
			)
		);
	}

	/**
	 * Submit a URL (or list of URLs) via the IndexNow protocol.
	 *
	 * Requires an API key stored in the seovela_indexnow_key option.
	 * The key file should be placed at the site root (handled separately).
	 *
	 * @param string|array $urls Single URL or array of URLs.
	 * @return array|\WP_Error Response or error.
	 */
	public function submit_indexnow( $urls ) {
		$api_key = get_option( 'seovela_indexnow_key', '' );

		if ( empty( $api_key ) ) {
			return new \WP_Error(
				'indexnow_no_key',
				__( 'IndexNow API key not configured.', 'seovela' )
			);
		}

		if ( ! is_array( $urls ) ) {
			$urls = array( $urls );
		}

		$urls = array_map( 'esc_url_raw', $urls );

		$body = array(
			'host'    => wp_parse_url( home_url(), PHP_URL_HOST ),
			'key'     => sanitize_text_field( $api_key ),
			'urlList' => $urls,
		);

		$response = wp_remote_post(
			self::INDEXNOW_ENDPOINT,
			array(
				'timeout' => 5,
				'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'    => wp_json_encode( $body ),
			)
		);

		return $response;
	}

	// ------------------------------------------------------------------
	//  XSL Stylesheet
	// ------------------------------------------------------------------

	/**
	 * Generate the XSL stylesheet for human-readable sitemap display.
	 *
	 * @return string XSL content.
	 */
	private function generate_xsl() {
		$site_name   = esc_html( get_bloginfo( 'name' ) );
		$seovela_url = 'https://seovela.com';

		$xsl  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xsl .= '<xsl:stylesheet version="2.0"' . "\n";
		$xsl .= '	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"' . "\n";
		$xsl .= '	xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
		$xsl .= '	xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
		$xsl .= "\n";
		$xsl .= '<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes" />' . "\n";
		$xsl .= "\n";
		$xsl .= '<xsl:template match="/">' . "\n";
		$xsl .= '<html xmlns="http://www.w3.org/1999/xhtml">' . "\n";
		$xsl .= '<head>' . "\n";
		$xsl .= '	<title>Seovela XML Sitemap</title>' . "\n";
		$xsl .= '	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . "\n";
		$xsl .= '	<meta name="robots" content="noindex, follow" />' . "\n";
		$xsl .= '	<style type="text/css">' . "\n";
		$xsl .= '		* { margin: 0; padding: 0; box-sizing: border-box; }' . "\n";
		$xsl .= '		body {' . "\n";
		$xsl .= '			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, sans-serif;' . "\n";
		$xsl .= '			color: #333;' . "\n";
		$xsl .= '			background: #f4f6f9;' . "\n";
		$xsl .= '			padding: 0;' . "\n";
		$xsl .= '		}' . "\n";
		$xsl .= '		.header {' . "\n";
		$xsl .= '			background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);' . "\n";
		$xsl .= '			color: #fff;' . "\n";
		$xsl .= '			padding: 30px 40px;' . "\n";
		$xsl .= '		}' . "\n";
		$xsl .= '		.header h1 {' . "\n";
		$xsl .= '			font-size: 24px;' . "\n";
		$xsl .= '			font-weight: 700;' . "\n";
		$xsl .= '			margin-bottom: 6px;' . "\n";
		$xsl .= '		}' . "\n";
		$xsl .= '		.header p {' . "\n";
		$xsl .= '			font-size: 14px;' . "\n";
		$xsl .= '			opacity: 0.85;' . "\n";
		$xsl .= '		}' . "\n";
		$xsl .= '		.header a { color: #93c5fd; text-decoration: none; }' . "\n";
		$xsl .= '		.header a:hover { text-decoration: underline; }' . "\n";
		$xsl .= '		.content {' . "\n";
		$xsl .= '			max-width: 1200px;' . "\n";
		$xsl .= '			margin: 30px auto;' . "\n";
		$xsl .= '			padding: 0 20px;' . "\n";
		$xsl .= '		}' . "\n";
		$xsl .= '		.summary {' . "\n";
		$xsl .= '			background: #fff;' . "\n";
		$xsl .= '			border: 1px solid #e2e8f0;' . "\n";
		$xsl .= '			border-radius: 8px;' . "\n";
		$xsl .= '			padding: 16px 24px;' . "\n";
		$xsl .= '			margin-bottom: 20px;' . "\n";
		$xsl .= '			font-size: 14px;' . "\n";
		$xsl .= '			color: #64748b;' . "\n";
		$xsl .= '		}' . "\n";
		$xsl .= '		.summary strong { color: #1e3a5f; }' . "\n";
		$xsl .= '		table {' . "\n";
		$xsl .= '			width: 100%;' . "\n";
		$xsl .= '			background: #fff;' . "\n";
		$xsl .= '			border-collapse: collapse;' . "\n";
		$xsl .= '			border-radius: 8px;' . "\n";
		$xsl .= '			overflow: hidden;' . "\n";
		$xsl .= '			border: 1px solid #e2e8f0;' . "\n";
		$xsl .= '			font-size: 14px;' . "\n";
		$xsl .= '		}' . "\n";
		$xsl .= '		th {' . "\n";
		$xsl .= '			background: #f8fafc;' . "\n";
		$xsl .= '			text-align: left;' . "\n";
		$xsl .= '			padding: 12px 16px;' . "\n";
		$xsl .= '			font-weight: 600;' . "\n";
		$xsl .= '			color: #475569;' . "\n";
		$xsl .= '			border-bottom: 2px solid #e2e8f0;' . "\n";
		$xsl .= '		}' . "\n";
		$xsl .= '		td {' . "\n";
		$xsl .= '			padding: 10px 16px;' . "\n";
		$xsl .= '			border-bottom: 1px solid #f1f5f9;' . "\n";
		$xsl .= '			word-break: break-all;' . "\n";
		$xsl .= '		}' . "\n";
		$xsl .= '		tr:hover td { background: #f8fafc; }' . "\n";
		$xsl .= '		td a { color: #2563eb; text-decoration: none; }' . "\n";
		$xsl .= '		td a:hover { text-decoration: underline; }' . "\n";
		$xsl .= '		.footer {' . "\n";
		$xsl .= '			text-align: center;' . "\n";
		$xsl .= '			padding: 30px;' . "\n";
		$xsl .= '			font-size: 13px;' . "\n";
		$xsl .= '			color: #94a3b8;' . "\n";
		$xsl .= '		}' . "\n";
		$xsl .= '		.badge {' . "\n";
		$xsl .= '			display: inline-block;' . "\n";
		$xsl .= '			background: #e0f2fe;' . "\n";
		$xsl .= '			color: #0369a1;' . "\n";
		$xsl .= '			font-size: 11px;' . "\n";
		$xsl .= '			padding: 2px 8px;' . "\n";
		$xsl .= '			border-radius: 10px;' . "\n";
		$xsl .= '			font-weight: 600;' . "\n";
		$xsl .= '		}' . "\n";
		$xsl .= '	</style>' . "\n";
		$xsl .= '</head>' . "\n";
		$xsl .= '<body>' . "\n";
		$xsl .= '	<div class="header">' . "\n";
		$xsl .= '		<h1>Seovela XML Sitemap</h1>' . "\n";
		$xsl .= '		<p>' . "\n";

		$xsl .= 'Generated by <a href="' . esc_url( $seovela_url ) . '">Seovela SEO Plugin</a> for ' . $site_name;

		$xsl .= '</p>' . "\n";
		$xsl .= '	</div>' . "\n";
		$xsl .= '	<div class="content">' . "\n";
		$xsl .= "\n";
		$xsl .= '	<!-- Sitemap Index -->' . "\n";
		$xsl .= '	<xsl:if test="sitemap:sitemapindex">' . "\n";
		$xsl .= '		<div class="summary">' . "\n";
		$xsl .= '			This is the main <strong>sitemap index</strong>. It references' . "\n";
		$xsl .= '			<strong><xsl:value-of select="count(sitemap:sitemapindex/sitemap:sitemap)" /></strong>' . "\n";
		$xsl .= '			sub-sitemaps.' . "\n";
		$xsl .= '		</div>' . "\n";
		$xsl .= '		<table>' . "\n";
		$xsl .= '			<thead>' . "\n";
		$xsl .= '				<tr>' . "\n";
		$xsl .= '					<th>#</th>' . "\n";
		$xsl .= '					<th>Sitemap URL</th>' . "\n";
		$xsl .= '					<th>Last Modified</th>' . "\n";
		$xsl .= '				</tr>' . "\n";
		$xsl .= '			</thead>' . "\n";
		$xsl .= '			<tbody>' . "\n";
		$xsl .= '				<xsl:for-each select="sitemap:sitemapindex/sitemap:sitemap">' . "\n";
		$xsl .= '					<tr>' . "\n";
		$xsl .= '						<td><xsl:value-of select="position()" /></td>' . "\n";
		$xsl .= '						<td><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc" /></a></td>' . "\n";
		$xsl .= '						<td>' . "\n";
		$xsl .= '							<xsl:if test="sitemap:lastmod">' . "\n";
		$xsl .= '								<xsl:value-of select="substring(sitemap:lastmod, 1, 10)" />' . "\n";
		$xsl .= '							</xsl:if>' . "\n";
		$xsl .= '						</td>' . "\n";
		$xsl .= '					</tr>' . "\n";
		$xsl .= '				</xsl:for-each>' . "\n";
		$xsl .= '			</tbody>' . "\n";
		$xsl .= '		</table>' . "\n";
		$xsl .= '	</xsl:if>' . "\n";
		$xsl .= "\n";
		$xsl .= '	<!-- URL Set -->' . "\n";
		$xsl .= '	<xsl:if test="sitemap:urlset">' . "\n";
		$xsl .= '		<div class="summary">' . "\n";
		$xsl .= '			This sitemap contains' . "\n";
		$xsl .= '			<strong><xsl:value-of select="count(sitemap:urlset/sitemap:url)" /></strong>' . "\n";
		$xsl .= '			URLs.' . "\n";
		$xsl .= '		</div>' . "\n";
		$xsl .= '		<table>' . "\n";
		$xsl .= '			<thead>' . "\n";
		$xsl .= '				<tr>' . "\n";
		$xsl .= '					<th>#</th>' . "\n";
		$xsl .= '					<th>URL</th>' . "\n";
		$xsl .= '					<th>Images</th>' . "\n";
		$xsl .= '					<th>Last Modified</th>' . "\n";
		$xsl .= '					<th>Change Freq</th>' . "\n";
		$xsl .= '					<th>Priority</th>' . "\n";
		$xsl .= '				</tr>' . "\n";
		$xsl .= '			</thead>' . "\n";
		$xsl .= '			<tbody>' . "\n";
		$xsl .= '				<xsl:for-each select="sitemap:urlset/sitemap:url">' . "\n";
		$xsl .= '					<tr>' . "\n";
		$xsl .= '						<td><xsl:value-of select="position()" /></td>' . "\n";
		$xsl .= '						<td><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc" /></a></td>' . "\n";
		$xsl .= '						<td>' . "\n";
		$xsl .= '							<xsl:if test="count(image:image) &gt; 0">' . "\n";
		$xsl .= '								<span class="badge"><xsl:value-of select="count(image:image)" /></span>' . "\n";
		$xsl .= '							</xsl:if>' . "\n";
		$xsl .= '						</td>' . "\n";
		$xsl .= '						<td>' . "\n";
		$xsl .= '							<xsl:if test="sitemap:lastmod">' . "\n";
		$xsl .= '								<xsl:value-of select="substring(sitemap:lastmod, 1, 10)" />' . "\n";
		$xsl .= '							</xsl:if>' . "\n";
		$xsl .= '						</td>' . "\n";
		$xsl .= '						<td><xsl:value-of select="sitemap:changefreq" /></td>' . "\n";
		$xsl .= '						<td><xsl:value-of select="sitemap:priority" /></td>' . "\n";
		$xsl .= '					</tr>' . "\n";
		$xsl .= '				</xsl:for-each>' . "\n";
		$xsl .= '			</tbody>' . "\n";
		$xsl .= '		</table>' . "\n";
		$xsl .= '	</xsl:if>' . "\n";
		$xsl .= "\n";
		$xsl .= '	</div>' . "\n";
		$xsl .= '	<div class="footer">' . "\n";
		$xsl .= '		Seovela XML Sitemap — generated dynamically.' . "\n";
		$xsl .= '	</div>' . "\n";
		$xsl .= '</body>' . "\n";
		$xsl .= '</html>' . "\n";
		$xsl .= '</xsl:template>' . "\n";
		$xsl .= '</xsl:stylesheet>';

		return $xsl;
	}

	// ------------------------------------------------------------------
	//  Utility
	// ------------------------------------------------------------------

	/**
	 * Escape a string for safe inclusion in XML.
	 *
	 * @param string $string Raw string.
	 * @return string
	 */
	private function xml_escape( $string ) {
		return esc_html( wp_strip_all_tags( $string ) );
	}

	/**
	 * Regenerate sitemaps (public helper called from settings page).
	 */
	public function regenerate() {
		$this->flush_all_sitemap_caches();
	}
}
