<?php
/**
 * Seovela Cache Helper Class
 *
 * Provides caching utilities to reduce database queries
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seovela Cache Class
 */
class Seovela_Cache {

	/**
	 * Cache group name
	 */
	const CACHE_GROUP = 'seovela';

	/**
	 * In-memory cache for current request
	 *
	 * @var array
	 */
	private static $runtime_cache = array();

	/**
	 * Cached plugin options
	 *
	 * @var array|null
	 */
	private static $plugin_options = null;

	/**
	 * Get cached data
	 *
	 * @param string $key Cache key
	 * @param string $type Cache type (transient, runtime, object)
	 * @return mixed|false Cached data or false
	 */
	public static function get( $key, $type = 'transient' ) {
		$full_key = self::get_cache_key( $key );

		// Check runtime cache first (fastest)
		if ( isset( self::$runtime_cache[ $full_key ] ) ) {
			return self::$runtime_cache[ $full_key ];
		}

		// Check transient/object cache
		if ( $type === 'transient' ) {
			$value = get_transient( $full_key );
			
			// Store in runtime cache for this request
			if ( false !== $value ) {
				self::$runtime_cache[ $full_key ] = $value;
			}
			
			return $value;
		}

		return false;
	}

	/**
	 * Set cached data
	 *
	 * @param string $key Cache key
	 * @param mixed  $value Value to cache
	 * @param int    $expiration Expiration in seconds (default 1 hour)
	 * @param string $type Cache type (transient, runtime, object)
	 * @return bool Success
	 */
	public static function set( $key, $value, $expiration = HOUR_IN_SECONDS, $type = 'transient' ) {
		$full_key = self::get_cache_key( $key );

		// Always store in runtime cache
		self::$runtime_cache[ $full_key ] = $value;

		// Store in transient/object cache
		if ( $type === 'transient' ) {
			return set_transient( $full_key, $value, $expiration );
		}

		return true;
	}

	/**
	 * Delete cached data
	 *
	 * @param string $key Cache key
	 * @param string $type Cache type (transient, runtime, object)
	 * @return bool Success
	 */
	public static function delete( $key, $type = 'transient' ) {
		$full_key = self::get_cache_key( $key );

		// Remove from runtime cache
		unset( self::$runtime_cache[ $full_key ] );

		// Remove from transient/object cache
		if ( $type === 'transient' ) {
			return delete_transient( $full_key );
		}

		return true;
	}

	/**
	 * Flush all plugin caches
	 *
	 * @return void
	 */
	public static function flush_all() {
		global $wpdb;

		// Clear runtime cache
		self::$runtime_cache = array();
		self::$plugin_options = null;

		// Clear all seovela transients
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_seovela_%' 
			OR option_name LIKE '_transient_timeout_seovela_%'"
		);

		// Clear object cache if available
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( self::CACHE_GROUP );
		}

		do_action( 'seovela_cache_flushed' );
	}

	/**
	 * Get all plugin options at once (batch load)
	 *
	 * @param bool $force Force reload
	 * @return array Plugin options
	 */
	public static function get_all_plugin_options( $force = false ) {
		if ( null !== self::$plugin_options && ! $force ) {
			return self::$plugin_options;
		}

		// Load all known plugin options individually via get_option().
		// This leverages WordPress's autoloaded options (already in memory)
		// and the object cache for non-autoloaded options, instead of a raw
		// SQL LIKE query that bypasses both caches.
		$option_keys = array(
			'seovela_meta_enabled',
			'seovela_sitemap_enabled',
			'seovela_schema_enabled',
			'seovela_optimizer_enabled',
			'seovela_ai_enabled',
			'seovela_redirects_enabled',
			'seovela_404_monitor_enabled',
			'seovela_internal_links_enabled',
			'seovela_image_seo_enabled',
			'seovela_gsc_integration_enabled',
			'seovela_llms_txt_enabled',
			'seovela_sitemap_post_types',
			'seovela_sitemap_taxonomies',
			'seovela_robots_index',
			'seovela_robots_max_snippet',
			'seovela_robots_max_video_preview',
			'seovela_robots_max_image_preview',
			'seovela_noindex_empty_archives',
			'seovela_robots_nofollow',
			'seovela_robots_noarchive',
			'seovela_robots_noimageindex',
			'seovela_robots_nosnippet',
			'seovela_separator_character',
			'seovela_home_title',
			'seovela_home_description',
			'seovela_titles_post_title',
			'seovela_titles_page_title',
			'seovela_titles_post_description',
			'seovela_titles_page_description',
			'seovela_titles_category_title',
			'seovela_titles_post_tag_title',
			'seovela_titles_category_description',
			'seovela_titles_post_tag_description',
			'seovela_titles_post_robots',
			'seovela_titles_page_robots',
			'seovela_default_og_image',
			'seovela_twitter_card_type',
		);

		/**
		 * Filter the list of plugin option keys to load.
		 *
		 * @param array $option_keys Option keys to load.
		 */
		$option_keys = apply_filters( 'seovela_plugin_option_keys', $option_keys );

		$options = array();
		foreach ( $option_keys as $key ) {
			$value = get_option( $key, null );
			if ( null !== $value ) {
				$options[ $key ] = $value;
			}
		}

		self::$plugin_options = $options;

		return $options;
	}

	/**
	 * Get a plugin option with caching
	 *
	 * @param string $option_name Option name (with or without seovela_ prefix)
	 * @param mixed  $default Default value
	 * @return mixed Option value
	 */
	public static function get_option( $option_name, $default = false ) {
		// Ensure seovela_ prefix
		if ( strpos( $option_name, 'seovela_' ) !== 0 ) {
			$option_name = 'seovela_' . $option_name;
		}

		// Get all options
		$all_options = self::get_all_plugin_options();

		// Return from cache or default
		return isset( $all_options[ $option_name ] ) ? $all_options[ $option_name ] : $default;
	}

	/**
	 * Invalidate options cache when option is updated
	 *
	 * @param string $option_name Option name
	 */
	public static function invalidate_option_cache( $option_name ) {
		if ( strpos( $option_name, 'seovela_' ) === 0 ) {
			self::$plugin_options = null;
		}
	}

	/**
	 * Get cache key with prefix
	 *
	 * @param string $key Cache key
	 * @return string Prefixed cache key
	 */
	private static function get_cache_key( $key ) {
		return 'seovela_' . $key;
	}

	/**
	 * Get cache statistics for admin display
	 *
	 * @return array Cache stats
	 */
	public static function get_cache_stats() {
		global $wpdb;

		$transient_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_seovela_%'"
		);

		$runtime_count = count( self::$runtime_cache );

		return array(
			'transients' => (int) $transient_count,
			'runtime'    => $runtime_count,
			'options_cached' => null !== self::$plugin_options,
		);
	}

	/**
	 * Initialize cache hooks
	 */
	public static function init() {
		// Invalidate cache when options are updated
		add_action( 'updated_option', array( __CLASS__, 'invalidate_option_cache' ), 10, 1 );
		add_action( 'added_option', array( __CLASS__, 'invalidate_option_cache' ), 10, 1 );
		add_action( 'deleted_option', array( __CLASS__, 'invalidate_option_cache' ), 10, 1 );

		// Clear cache on post save (for meta-dependent caching)
		add_action( 'save_post', array( __CLASS__, 'on_post_save' ), 10, 1 );
		
		// Clear redirect cache when redirects are modified
		add_action( 'seovela_redirect_added', array( __CLASS__, 'clear_redirect_cache' ) );
		add_action( 'seovela_redirect_updated', array( __CLASS__, 'clear_redirect_cache' ) );
		add_action( 'seovela_redirect_deleted', array( __CLASS__, 'clear_redirect_cache' ) );
	}

	/**
	 * Clear redirect cache
	 */
	public static function clear_redirect_cache() {
		global $wpdb;
		
		// Delete all redirect-related transients
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_seovela_redirect_%' 
			OR option_name LIKE '_transient_timeout_seovela_redirect_%'"
		);
	}

	/**
	 * Handle post save event
	 *
	 * @param int $post_id Post ID
	 */
	public static function on_post_save( $post_id ) {
		// Clear any post-specific caches if needed
		// For now, we don't cache post-specific data, but this is here for future use
		do_action( 'seovela_post_cache_cleared', $post_id );
	}
}

