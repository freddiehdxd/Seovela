<?php
/**
 * Cache Management Admin View
 *
 * Display cache statistics and management options
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get cache statistics
$cache_stats = Seovela_Cache::get_cache_stats();

// Handle cache clear action
if ( isset( $_POST['seovela_clear_cache'] ) && check_admin_referer( 'seovela_clear_cache' ) ) {
	Seovela_Cache::flush_all();
	echo '<div class="notice notice-success"><p>' . esc_html__( 'Cache cleared successfully!', 'seovela' ) . '</p></div>';
	// Refresh stats
	$cache_stats = Seovela_Cache::get_cache_stats();
}
?>

<div class="seovela-cache-management">
	<h3><?php esc_html_e( 'Cache Management', 'seovela' ); ?></h3>
	
	<div class="seovela-cache-stats">
		<h4><?php esc_html_e( 'Cache Statistics', 'seovela' ); ?></h4>
		<table class="widefat">
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Transient Cache Entries', 'seovela' ); ?></strong></td>
					<td><?php echo esc_html( $cache_stats['transients'] ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Runtime Cache Entries', 'seovela' ); ?></strong></td>
					<td><?php echo esc_html( $cache_stats['runtime'] ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Options Cached', 'seovela' ); ?></strong></td>
					<td>
						<?php 
						echo wp_kses_post( $cache_stats['options_cached']
							? '<span style="color: green;">✓ ' . esc_html__( 'Yes', 'seovela' ) . '</span>'
							: '<span style="color: orange;">○ ' . esc_html__( 'Not Yet', 'seovela' ) . '</span>' );
						?>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="seovela-cache-info" style="margin-top: 20px;">
		<h4><?php esc_html_e( 'About Caching', 'seovela' ); ?></h4>
		<p><?php esc_html_e( 'SEOVela uses intelligent caching to reduce database queries and improve performance:', 'seovela' ); ?></p>
		<ul style="list-style: disc; margin-left: 20px;">
			<li><?php esc_html_e( 'Options are batch-loaded in a single query and cached for 1 hour', 'seovela' ); ?></li>
			<li><?php esc_html_e( 'Redirect lookups are cached to avoid repeated database queries', 'seovela' ); ?></li>
			<li><?php esc_html_e( 'Cache is automatically cleared when you update settings', 'seovela' ); ?></li>
			<li><?php esc_html_e( 'Runtime cache stores data for the current page load only', 'seovela' ); ?></li>
		</ul>
	</div>

	<div class="seovela-cache-actions" style="margin-top: 20px;">
		<h4><?php esc_html_e( 'Clear Cache', 'seovela' ); ?></h4>
		<p><?php esc_html_e( 'Clear all SEOVela caches. This will force fresh data to be loaded from the database.', 'seovela' ); ?></p>
		<form method="post" style="margin-top: 10px;">
			<?php wp_nonce_field( 'seovela_clear_cache' ); ?>
			<button type="submit" name="seovela_clear_cache" class="button button-secondary">
				<?php esc_html_e( 'Clear All Caches', 'seovela' ); ?>
			</button>
		</form>
	</div>

	<div class="seovela-performance-tips" style="margin-top: 30px; padding: 15px; background: #f0f0f1; border-left: 4px solid #2271b1;">
		<h4 style="margin-top: 0;"><?php esc_html_e( '🚀 Performance Tips', 'seovela' ); ?></h4>
		<ul style="list-style: disc; margin-left: 20px;">
			<li>
				<strong><?php esc_html_e( 'Use Object Caching:', 'seovela' ); ?></strong>
				<?php esc_html_e( 'Install Redis or Memcached for even better performance. SEOVela automatically uses object caching if available.', 'seovela' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Database Queries:', 'seovela' ); ?></strong>
				<?php esc_html_e( 'With caching enabled, SEOVela typically uses only 2-5 database queries per page load (down from 20-30 without caching).', 'seovela' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Automatic Optimization:', 'seovela' ); ?></strong>
				<?php esc_html_e( 'All plugin options are loaded in a single batch query and cached for optimal performance.', 'seovela' ); ?>
			</li>
		</ul>
	</div>
</div>

<?php
wp_register_style( 'seovela-cache-management-inline', false );
wp_enqueue_style( 'seovela-cache-management-inline' );
wp_add_inline_style( 'seovela-cache-management-inline', <<<'SEOVELA_CSS'
.seovela-cache-management h3 {
	font-size: 20px;
	margin-bottom: 20px;
}
.seovela-cache-management h4 {
	font-size: 16px;
	margin-bottom: 10px;
}
.seovela-cache-management .widefat td {
	padding: 10px;
}
.seovela-cache-management .widefat tr:nth-child(even) {
	background: #f9f9f9;
}
SEOVELA_CSS
);
?>

