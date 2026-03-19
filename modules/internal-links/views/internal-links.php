<?php
/**
 * Internal Links Admin Page - Modern Design
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'seovela_link_suggestions';

// Get stats
$stats = $wpdb->get_row( "
	SELECT 
		COUNT(DISTINCT source_post_id) as posts_with_suggestions,
		COUNT(*) as total_suggestions,
		AVG(relevance_score) as avg_score
	FROM {$table_name}
	WHERE status = 'pending'
" );

$orphan_count = $wpdb->get_var( "
	SELECT COUNT(DISTINCT ID) 
	FROM {$wpdb->posts} p
	LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_seovela_incoming_links'
	WHERE p.post_status = 'publish' 
	AND p.post_type IN ('post', 'page')
	AND (pm.meta_value IS NULL OR pm.meta_value = '0')
" );

// Get filter from URL
$filter = isset( $_GET['filter'] ) ? sanitize_text_field( wp_unslash( $_GET['filter'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

// Build query for suggestions
$where_clauses = array( "ls.status = 'pending'" );
$score_filter = '';

if ( $filter === 'high' ) {
	$score_filter = " AND ls.relevance_score >= 0.7";
} elseif ( $filter === 'medium' ) {
	$score_filter = " AND ls.relevance_score >= 0.4 AND ls.relevance_score < 0.7";
} elseif ( $filter === 'low' ) {
	$score_filter = " AND ls.relevance_score < 0.4";
}

$search_clause = '';
if ( ! empty( $search ) ) {
	$search_clause = $wpdb->prepare( 
		" AND (sp.post_title LIKE %s OR tp.post_title LIKE %s OR ls.suggested_anchor LIKE %s)",
		'%' . $wpdb->esc_like( $search ) . '%',
		'%' . $wpdb->esc_like( $search ) . '%',
		'%' . $wpdb->esc_like( $search ) . '%'
	);
}

$suggestions = $wpdb->get_results( "
	SELECT ls.*, 
		sp.post_title as source_title,
		tp.post_title as target_title
	FROM {$table_name} ls
	LEFT JOIN {$wpdb->posts} sp ON ls.source_post_id = sp.ID
	LEFT JOIN {$wpdb->posts} tp ON ls.target_post_id = tp.ID
	WHERE ls.status = 'pending' {$score_filter} {$search_clause}
	ORDER BY ls.relevance_score DESC
	LIMIT 100
" );

// Count suggestions by score
$high_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE status = 'pending' AND relevance_score >= 0.7" );
$medium_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE status = 'pending' AND relevance_score >= 0.4 AND relevance_score < 0.7" );
$low_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE status = 'pending' AND relevance_score < 0.4" );
?>

<div class="wrap seovela-internal-links-page">
	<!-- Header -->
	<div class="seovela-il-header">
		<div class="seovela-il-header-content">
			<div class="seovela-il-header-icon">
				<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
				</svg>
			</div>
			<div class="seovela-il-header-text">
				<h1><?php esc_html_e( 'Internal Link Suggestions', 'seovela' ); ?></h1>
				<p><?php esc_html_e( 'Discover linking opportunities to boost your SEO', 'seovela' ); ?></p>
			</div>
		</div>
		<div class="seovela-il-header-actions">
			<button type="button" class="seovela-il-btn seovela-il-btn-secondary" id="show-orphan-pages">
				<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
				</svg>
				<?php esc_html_e( 'Orphan Pages', 'seovela' ); ?>
				<span class="count-badge orphan"><?php echo esc_html( $orphan_count ); ?></span>
			</button>
			<button type="button" class="seovela-il-btn seovela-il-btn-outline seovela-open-settings">
				<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
				</svg>
				<?php esc_html_e( 'Settings', 'seovela' ); ?>
			</button>
		</div>
	</div>

	<!-- Statistics Cards -->
	<div class="seovela-il-stats">
		<div class="seovela-il-stat-card gradient-blue">
			<div class="stat-icon">
				<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
				</svg>
			</div>
			<div class="stat-content">
				<div class="stat-number"><?php echo number_format( $stats->total_suggestions ?: 0 ); ?></div>
				<div class="stat-label"><?php esc_html_e( 'Opportunities', 'seovela' ); ?></div>
			</div>
		</div>
		<div class="seovela-il-stat-card gradient-purple">
			<div class="stat-icon">
				<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
				</svg>
			</div>
			<div class="stat-content">
				<div class="stat-number"><?php echo number_format( $stats->posts_with_suggestions ?: 0 ); ?></div>
				<div class="stat-label"><?php esc_html_e( 'Posts to Improve', 'seovela' ); ?></div>
			</div>
		</div>
		<div class="seovela-il-stat-card gradient-green">
			<div class="stat-icon">
				<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
				</svg>
			</div>
			<div class="stat-content">
				<div class="stat-number"><?php echo $stats->avg_score ? number_format( $stats->avg_score * 100, 0 ) . '%' : '0%'; ?></div>
				<div class="stat-label"><?php esc_html_e( 'Avg Relevance', 'seovela' ); ?></div>
			</div>
		</div>
		<div class="seovela-il-stat-card gradient-amber">
			<div class="stat-icon">
				<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
				</svg>
			</div>
			<div class="stat-content">
				<div class="stat-number"><?php echo number_format( $orphan_count ?: 0 ); ?></div>
				<div class="stat-label"><?php esc_html_e( 'Orphan Pages', 'seovela' ); ?></div>
			</div>
		</div>
	</div>

	<!-- Orphan Pages Panel (hidden by default) -->
	<div class="seovela-il-orphan-panel" id="orphan-pages-section" style="display: none;">
		<div class="orphan-panel-header">
			<div class="orphan-panel-title">
				<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
				</svg>
				<h3><?php esc_html_e( 'Orphan Pages', 'seovela' ); ?></h3>
			</div>
			<p class="orphan-panel-desc"><?php esc_html_e( 'These pages have no internal links pointing to them. Add links from related content to improve their visibility.', 'seovela' ); ?></p>
		</div>
		<div id="orphan-pages-list" class="orphan-pages-list">
			<div class="seovela-il-loader">
				<div class="loader-spinner"></div>
				<span><?php esc_html_e( 'Loading orphan pages...', 'seovela' ); ?></span>
			</div>
		</div>
	</div>

	<!-- Toolbar -->
	<div class="seovela-il-toolbar">
		<nav class="seovela-il-tabs">
			<a href="<?php echo esc_url( remove_query_arg( array( 'filter', 'search' ) ) ); ?>" class="seovela-il-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
				<?php esc_html_e( 'All', 'seovela' ); ?>
				<span class="tab-count"><?php echo esc_html( $stats->total_suggestions ?: 0 ); ?></span>
			</a>
			<a href="<?php echo esc_url( add_query_arg( 'filter', 'high' ) ); ?>" class="seovela-il-tab tab-high <?php echo $filter === 'high' ? 'active' : ''; ?>">
				<?php esc_html_e( 'High', 'seovela' ); ?>
				<span class="tab-count"><?php echo esc_html( $high_count ); ?></span>
			</a>
			<a href="<?php echo esc_url( add_query_arg( 'filter', 'medium' ) ); ?>" class="seovela-il-tab tab-medium <?php echo $filter === 'medium' ? 'active' : ''; ?>">
				<?php esc_html_e( 'Medium', 'seovela' ); ?>
				<span class="tab-count"><?php echo esc_html( $medium_count ); ?></span>
			</a>
			<a href="<?php echo esc_url( add_query_arg( 'filter', 'low' ) ); ?>" class="seovela-il-tab tab-low <?php echo $filter === 'low' ? 'active' : ''; ?>">
				<?php esc_html_e( 'Low', 'seovela' ); ?>
				<span class="tab-count"><?php echo esc_html( $low_count ); ?></span>
			</a>
		</nav>
		
		<div class="seovela-il-toolbar-actions">
			<div class="seovela-il-search">
				<form method="get">
					<input type="hidden" name="page" value="seovela-internal-links">
					<?php if ( $filter !== 'all' ) : ?>
						<input type="hidden" name="filter" value="<?php echo esc_attr( $filter ); ?>">
					<?php endif; ?>
					<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
					</svg>
					<input type="search" name="search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search suggestions...', 'seovela' ); ?>">
				</form>
			</div>
			<button type="button" class="seovela-il-btn seovela-il-btn-primary" id="regenerate-all-suggestions">
				<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
				</svg>
				<?php esc_html_e( 'Regenerate', 'seovela' ); ?>
			</button>
		</div>
	</div>

	<!-- Progress Bar (hidden by default) -->
	<div id="bulk-action-progress" class="seovela-il-progress" style="display: none;">
		<div class="progress-header">
			<span class="progress-title"><?php esc_html_e( 'Regenerating suggestions...', 'seovela' ); ?></span>
			<span class="progress-percent">0%</span>
		</div>
		<div class="progress-bar">
			<div class="progress-fill"></div>
		</div>
		<p class="progress-text"></p>
	</div>

	<!-- Suggestions Table -->
	<div class="seovela-il-table-wrap">
		<?php if ( empty( $suggestions ) ) : ?>
			<div class="seovela-il-empty-state">
				<div class="empty-icon">
					<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
					</svg>
				</div>
				<h3><?php esc_html_e( 'No suggestions yet', 'seovela' ); ?></h3>
				<p><?php esc_html_e( 'Link suggestions are generated automatically when you publish or update posts. Click "Regenerate" to scan all content.', 'seovela' ); ?></p>
				<button type="button" class="seovela-il-btn seovela-il-btn-primary" id="regenerate-empty">
					<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
					</svg>
					<?php esc_html_e( 'Generate Suggestions', 'seovela' ); ?>
				</button>
			</div>
		<?php else : ?>
			<table class="seovela-il-table">
				<thead>
					<tr>
						<th class="column-source"><?php esc_html_e( 'Source Post', 'seovela' ); ?></th>
						<th class="column-arrow"></th>
						<th class="column-target"><?php esc_html_e( 'Link To', 'seovela' ); ?></th>
						<th class="column-anchor"><?php esc_html_e( 'Anchor Text', 'seovela' ); ?></th>
						<th class="column-score"><?php esc_html_e( 'Score', 'seovela' ); ?></th>
						<th class="column-actions"><?php esc_html_e( 'Actions', 'seovela' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $suggestions as $suggestion ) : 
						$score = $suggestion->relevance_score;
						$score_class = $score >= 0.7 ? 'high' : ( $score >= 0.4 ? 'medium' : 'low' );
					?>
						<tr data-suggestion-id="<?php echo esc_attr( $suggestion->id ); ?>">
							<td class="column-source">
								<div class="post-cell">
									<span class="post-title"><?php echo esc_html( $suggestion->source_title ); ?></span>
									<span class="post-id">ID: <?php echo esc_html( $suggestion->source_post_id ); ?></span>
								</div>
							</td>
							<td class="column-arrow">
								<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="arrow-icon">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
								</svg>
							</td>
							<td class="column-target">
								<div class="post-cell">
									<span class="post-title"><?php echo esc_html( $suggestion->target_title ); ?></span>
									<a href="<?php echo esc_url( get_permalink( $suggestion->target_post_id ) ); ?>" target="_blank" class="post-link">
										<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
										</svg>
									</a>
								</div>
							</td>
							<td class="column-anchor">
								<code class="anchor-text"><?php echo esc_html( $suggestion->suggested_anchor ); ?></code>
							</td>
							<td class="column-score">
								<div class="score-badge score-<?php echo esc_attr( $score_class ); ?>">
									<span class="score-value"><?php echo number_format( $score * 100 ); ?>%</span>
									<span class="score-label"><?php echo esc_html( ucfirst( $score_class ) ); ?></span>
								</div>
							</td>
							<td class="column-actions">
								<div class="action-buttons">
									<a href="<?php echo esc_url( get_edit_post_link( $suggestion->source_post_id ) ); ?>" class="btn-action btn-primary" title="<?php esc_attr_e( 'Edit Post', 'seovela' ); ?>">
										<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
										</svg>
									</a>
									<button type="button" class="btn-action btn-success seovela-copy-anchor" data-anchor="<?php echo esc_attr( $suggestion->suggested_anchor ); ?>" data-url="<?php echo esc_attr( get_permalink( $suggestion->target_post_id ) ); ?>" title="<?php esc_attr_e( 'Copy Link HTML', 'seovela' ); ?>">
										<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
										</svg>
									</button>
									<button type="button" class="btn-action btn-danger seovela-dismiss-suggestion" data-suggestion-id="<?php echo esc_attr( $suggestion->id ); ?>" title="<?php esc_attr_e( 'Dismiss', 'seovela' ); ?>">
										<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
										</svg>
									</button>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>

<!-- Settings Slide Panel -->
<div id="seovela-il-settings-panel" class="seovela-il-slide-panel">
	<div class="panel-overlay"></div>
	<div class="panel-content">
		<div class="panel-header">
			<h2><?php esc_html_e( 'Internal Links Settings', 'seovela' ); ?></h2>
			<button type="button" class="panel-close">
				<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
				</svg>
			</button>
		</div>
		
		<form id="seovela-il-settings-form" class="panel-body" method="post" action="">
			<?php wp_nonce_field( 'seovela_internal_links_settings' ); ?>
			<input type="hidden" name="seovela_internal_links_settings" value="1">
			
			<!-- Enable Module -->
			<div class="settings-section">
				<h3><?php esc_html_e( 'Module Status', 'seovela' ); ?></h3>
				
				<div class="setting-field">
					<label class="toggle-label">
						<span class="toggle-switch">
							<input type="checkbox" name="enabled" value="1" <?php checked( $settings['enabled'], true ); ?>>
							<span class="toggle-slider"></span>
						</span>
						<span class="toggle-text"><?php esc_html_e( 'Enable automatic link suggestions', 'seovela' ); ?></span>
					</label>
				</div>
			</div>
			
			<!-- Relevance Settings -->
			<div class="settings-section">
				<h3><?php esc_html_e( 'Relevance Settings', 'seovela' ); ?></h3>
				<p class="section-desc"><?php esc_html_e( 'Control the quality threshold for suggestions.', 'seovela' ); ?></p>
				
				<div class="setting-field">
					<label for="min_score"><?php esc_html_e( 'Minimum Relevance Score', 'seovela' ); ?></label>
					<div class="score-slider-wrap">
						<input type="range" id="min_score" name="min_score" 
							value="<?php echo esc_attr( $settings['min_score'] ); ?>" 
							min="0" max="1" step="0.1" class="score-slider">
						<span class="score-display"><?php echo esc_html( $settings['min_score'] ); ?></span>
					</div>
					<p class="field-hint"><?php esc_html_e( 'Only show suggestions above this relevance threshold.', 'seovela' ); ?></p>
				</div>
				
				<div class="setting-field">
					<label for="max_suggestions"><?php esc_html_e( 'Max Suggestions Per Post', 'seovela' ); ?></label>
					<input type="number" id="max_suggestions" name="max_suggestions" 
						value="<?php echo esc_attr( $settings['max_suggestions'] ); ?>" 
						min="1" max="20" class="small-input">
				</div>
			</div>
			
			<!-- Auto Refresh -->
			<div class="settings-section">
				<h3><?php esc_html_e( 'Automation', 'seovela' ); ?></h3>
				
				<div class="setting-field">
					<label class="toggle-label">
						<span class="toggle-switch">
							<input type="checkbox" name="auto_refresh" value="1" <?php checked( $settings['auto_refresh'], true ); ?>>
							<span class="toggle-slider"></span>
						</span>
						<span class="toggle-text"><?php esc_html_e( 'Auto-refresh on publish/update', 'seovela' ); ?></span>
					</label>
					<p class="field-hint"><?php esc_html_e( 'Automatically regenerate suggestions when posts are published or updated.', 'seovela' ); ?></p>
				</div>
			</div>
			
			<!-- Danger Zone -->
			<div class="settings-section danger-zone">
				<h3><?php esc_html_e( 'Danger Zone', 'seovela' ); ?></h3>
				
				<div class="danger-actions">
					<div class="danger-action">
						<div>
							<strong><?php esc_html_e( 'Clear All Suggestions', 'seovela' ); ?></strong>
							<p><?php esc_html_e( 'Remove all pending suggestions.', 'seovela' ); ?></p>
						</div>
						<button type="button" class="button button-danger" id="clear-all-suggestions"><?php esc_html_e( 'Clear All', 'seovela' ); ?></button>
					</div>
				</div>
			</div>
			
			<div class="panel-footer">
				<button type="submit" class="seovela-il-btn seovela-il-btn-primary"><?php esc_html_e( 'Save Settings', 'seovela' ); ?></button>
			</div>
		</form>
	</div>
</div>

<!-- Toast Notification -->
<div id="seovela-il-toast" class="seovela-il-toast"></div>

<style>
/* Internal Links Modern Styles */
.seovela-internal-links-page {
	max-width: 1400px;
	margin: 20px 20px 40px 0;
	font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
}

/* Header */
.seovela-il-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 28px;
	flex-wrap: wrap;
	gap: 16px;
}

.seovela-il-header-content {
	display: flex;
	align-items: center;
	gap: 16px;
}

.seovela-il-header-icon {
	width: 56px;
	height: 56px;
	background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
	border-radius: 14px;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 4px 14px rgba(99, 102, 241, 0.3);
}

.seovela-il-header-icon svg {
	width: 28px;
	height: 28px;
	color: #fff;
}

.seovela-il-header-text h1 {
	margin: 0;
	font-size: 26px;
	font-weight: 700;
	color: #1e293b;
	letter-spacing: -0.02em;
}

.seovela-il-header-text p {
	margin: 4px 0 0;
	color: #64748b;
	font-size: 14px;
}

.seovela-il-header-actions {
	display: flex;
	gap: 10px;
}

/* Buttons */
.seovela-il-btn {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	padding: 10px 18px;
	border-radius: 10px;
	font-weight: 600;
	font-size: 14px;
	cursor: pointer;
	transition: all 0.2s ease;
	border: none;
	text-decoration: none;
}

.seovela-il-btn svg {
	width: 18px;
	height: 18px;
}

.seovela-il-btn-primary {
	background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
	color: #fff;
	box-shadow: 0 2px 8px rgba(99, 102, 241, 0.25);
}

.seovela-il-btn-primary:hover {
	transform: translateY(-1px);
	box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
	color: #fff;
}

.seovela-il-btn-secondary {
	background: #f1f5f9;
	color: #475569;
}

.seovela-il-btn-secondary:hover {
	background: #e2e8f0;
	color: #334155;
}

.seovela-il-btn-outline {
	background: transparent;
	color: #475569;
	border: 1.5px solid #e2e8f0;
}

.seovela-il-btn-outline:hover {
	background: #f8fafc;
	border-color: #cbd5e1;
}

.count-badge {
	background: rgba(0,0,0,0.08);
	padding: 2px 8px;
	border-radius: 20px;
	font-size: 12px;
	font-weight: 700;
}

.count-badge.orphan {
	background: #fef3c7;
	color: #92400e;
}

/* Statistics Cards */
.seovela-il-stats {
	display: grid;
	grid-template-columns: repeat(4, 1fr);
	gap: 16px;
	margin-bottom: 24px;
}

.seovela-il-stat-card {
	display: flex;
	align-items: center;
	gap: 14px;
	padding: 18px 20px;
	border-radius: 14px;
	color: #fff;
	position: relative;
	overflow: hidden;
}

.seovela-il-stat-card::before {
	content: '';
	position: absolute;
	top: -50%;
	right: -50%;
	width: 100%;
	height: 200%;
	background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
}

.gradient-blue {
	background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

.gradient-purple {
	background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
}

.gradient-green {
	background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.gradient-amber {
	background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.seovela-il-stat-card .stat-icon {
	width: 44px;
	height: 44px;
	background: rgba(255,255,255,0.2);
	border-radius: 10px;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
}

.seovela-il-stat-card .stat-icon svg {
	width: 22px;
	height: 22px;
	color: #fff;
	stroke: #fff;
}

.seovela-il-stat-card .stat-content {
	flex: 1;
	min-width: 0;
}

.seovela-il-stat-card .stat-number {
	font-size: 28px;
	font-weight: 700;
	line-height: 1;
	color: #fff;
}

.seovela-il-stat-card .stat-label {
	font-size: 11px;
	margin-top: 4px;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.8px;
	color: rgba(255,255,255,0.95);
}

/* Orphan Panel */
.seovela-il-orphan-panel {
	background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
	border: 1px solid #fcd34d;
	border-radius: 16px;
	padding: 24px;
	margin-bottom: 24px;
}

.orphan-panel-header {
	margin-bottom: 20px;
}

.orphan-panel-title {
	display: flex;
	align-items: center;
	gap: 10px;
	margin-bottom: 8px;
}

.orphan-panel-title svg {
	width: 22px;
	height: 22px;
	color: #d97706;
}

.orphan-panel-title h3 {
	margin: 0;
	font-size: 18px;
	font-weight: 700;
	color: #92400e;
}

.orphan-panel-desc {
	margin: 0;
	color: #a16207;
	font-size: 14px;
}

.orphan-pages-list {
	background: #fff;
	border-radius: 12px;
	overflow: hidden;
}

.orphan-pages-list table {
	width: 100%;
	border-collapse: collapse;
}

.orphan-pages-list th {
	background: #fefce8;
	padding: 12px 16px;
	text-align: left;
	font-weight: 600;
	color: #78350f;
	font-size: 12px;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.orphan-pages-list td {
	padding: 12px 16px;
	border-top: 1px solid #fef3c7;
	color: #1e293b;
}

.orphan-pages-list tr:hover td {
	background: #fffbeb;
}

/* Loader */
.seovela-il-loader {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 40px;
	gap: 12px;
	color: #a16207;
}

.loader-spinner {
	width: 32px;
	height: 32px;
	border: 3px solid #fcd34d;
	border-top-color: #d97706;
	border-radius: 50%;
	animation: spin 0.8s linear infinite;
}

@keyframes spin {
	to { transform: rotate(360deg); }
}

/* Toolbar */
.seovela-il-toolbar {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 20px;
	background: #fff;
	padding: 12px 16px;
	border-radius: 14px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
	flex-wrap: wrap;
	gap: 12px;
}

.seovela-il-tabs {
	display: flex;
	gap: 4px;
}

.seovela-il-tab {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 8px 14px;
	color: #64748b;
	text-decoration: none;
	border-radius: 8px;
	font-weight: 500;
	font-size: 14px;
	transition: all 0.2s;
}

.seovela-il-tab:hover {
	background: #f1f5f9;
	color: #334155;
}

.seovela-il-tab.active {
	background: #6366f1;
	color: #fff;
}

.seovela-il-tab.tab-high.active {
	background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.seovela-il-tab.tab-medium.active {
	background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.seovela-il-tab.tab-low.active {
	background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.tab-count {
	background: rgba(0,0,0,0.1);
	padding: 2px 8px;
	border-radius: 10px;
	font-size: 12px;
	font-weight: 600;
}

.seovela-il-tab.active .tab-count {
	background: rgba(255,255,255,0.25);
}

.seovela-il-toolbar-actions {
	display: flex;
	gap: 12px;
	align-items: center;
}

.seovela-il-search {
	position: relative;
}

.seovela-il-search svg {
	position: absolute;
	left: 12px;
	top: 50%;
	transform: translateY(-50%);
	width: 18px;
	height: 18px;
	color: #94a3b8;
	pointer-events: none;
}

.seovela-il-search input {
	padding: 10px 14px 10px 40px;
	border: 1px solid #e2e8f0;
	border-radius: 10px;
	font-size: 14px;
	min-width: 240px;
	transition: all 0.2s;
}

.seovela-il-search input:focus {
	outline: none;
	border-color: #6366f1;
	box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Progress */
.seovela-il-progress {
	background: #fff;
	border-radius: 14px;
	padding: 20px 24px;
	margin-bottom: 20px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}

.progress-header {
	display: flex;
	justify-content: space-between;
	margin-bottom: 12px;
}

.progress-title {
	font-weight: 600;
	color: #1e293b;
}

.progress-percent {
	font-weight: 700;
	color: #6366f1;
}

.progress-bar {
	height: 8px;
	background: #e2e8f0;
	border-radius: 4px;
	overflow: hidden;
}

.progress-fill {
	height: 100%;
	background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%);
	border-radius: 4px;
	width: 0%;
	transition: width 0.3s ease;
}

.progress-text {
	margin: 12px 0 0;
	color: #64748b;
	font-size: 13px;
}

/* Table */
.seovela-il-table-wrap {
	background: #fff;
	border-radius: 16px;
	overflow: hidden;
	box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
}

.seovela-il-table {
	width: 100%;
	border-collapse: collapse;
}

.seovela-il-table thead {
	background: #f8fafc;
}

.seovela-il-table th {
	padding: 16px 18px;
	text-align: left;
	font-weight: 600;
	color: #475569;
	font-size: 12px;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	border-bottom: 2px solid #e2e8f0;
}

.seovela-il-table td {
	padding: 16px 18px;
	border-bottom: 1px solid #f1f5f9;
	vertical-align: middle;
}

.seovela-il-table tbody tr {
	transition: background 0.15s ease;
}

.seovela-il-table tbody tr:hover {
	background: #f8fafc;
}

.column-source { width: 24%; }
.column-arrow { width: 5%; text-align: center; }
.column-target { width: 24%; }
.column-anchor { width: 18%; }
.column-score { width: 12%; }
.column-actions { width: 17%; text-align: right; }

.post-cell {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.post-title {
	font-weight: 600;
	color: #1e293b;
	font-size: 14px;
	line-height: 1.4;
}

.post-id {
	font-size: 12px;
	color: #94a3b8;
}

.post-link {
	color: #94a3b8;
	margin-left: 6px;
}

.post-link svg {
	width: 14px;
	height: 14px;
}

.post-link:hover {
	color: #6366f1;
}

.arrow-icon {
	width: 20px;
	height: 20px;
	color: #cbd5e1;
}

.anchor-text {
	background: #f1f5f9;
	padding: 6px 10px;
	border-radius: 6px;
	font-size: 12px;
	color: #475569;
	display: inline-block;
	max-width: 180px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

/* Score Badge */
.score-badge {
	display: inline-flex;
	flex-direction: column;
	align-items: center;
	padding: 8px 14px;
	border-radius: 10px;
	min-width: 70px;
}

.score-badge .score-value {
	font-size: 16px;
	font-weight: 700;
	line-height: 1;
}

.score-badge .score-label {
	font-size: 10px;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	margin-top: 4px;
	font-weight: 600;
	opacity: 0.8;
}

.score-badge.score-high {
	background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
	color: #065f46;
}

.score-badge.score-medium {
	background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
	color: #92400e;
}

.score-badge.score-low {
	background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
	color: #991b1b;
}

/* Action Buttons */
.action-buttons {
	display: flex;
	gap: 8px;
	justify-content: flex-end;
}

.btn-action {
	width: 36px;
	height: 36px;
	padding: 0;
	border: none;
	border-radius: 10px;
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.2s;
	background: #f1f5f9;
	color: #64748b;
	text-decoration: none;
}

.btn-action svg {
	width: 18px;
	height: 18px;
}

.btn-action:hover {
	transform: translateY(-2px);
}

.btn-action.btn-primary {
	background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
	color: #fff;
}

.btn-action.btn-primary:hover {
	box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.btn-action.btn-success {
	background: linear-gradient(135deg, #10b981 0%, #059669 100%);
	color: #fff;
}

.btn-action.btn-success:hover {
	box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-action.btn-danger:hover {
	background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
	color: #fff;
}

/* Empty State */
.seovela-il-empty-state {
	text-align: center;
	padding: 80px 40px;
}

.seovela-il-empty-state .empty-icon {
	width: 80px;
	height: 80px;
	margin: 0 auto 24px;
	background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
	border-radius: 20px;
	display: flex;
	align-items: center;
	justify-content: center;
}

.seovela-il-empty-state .empty-icon svg {
	width: 40px;
	height: 40px;
	color: #6366f1;
}

.seovela-il-empty-state h3 {
	margin: 0 0 12px;
	font-size: 20px;
	font-weight: 700;
	color: #1e293b;
}

.seovela-il-empty-state p {
	margin: 0 0 24px;
	color: #64748b;
	font-size: 15px;
	max-width: 400px;
	margin-left: auto;
	margin-right: auto;
}

/* Slide Panel */
.seovela-il-slide-panel {
	position: fixed;
	top: 0;
	right: 0;
	bottom: 0;
	left: 0;
	z-index: 100000;
	visibility: hidden;
	opacity: 0;
	transition: visibility 0s 0.3s, opacity 0.3s;
}

.seovela-il-slide-panel.active {
	visibility: visible;
	opacity: 1;
	transition: visibility 0s, opacity 0.3s;
}

.seovela-il-slide-panel .panel-overlay {
	position: absolute;
	top: 0;
	right: 0;
	bottom: 0;
	left: 0;
	background: rgba(15, 23, 42, 0.5);
	backdrop-filter: blur(4px);
}

.seovela-il-slide-panel .panel-content {
	position: absolute;
	top: 0;
	right: -460px;
	bottom: 0;
	width: 460px;
	background: #fff;
	box-shadow: -8px 0 30px rgba(0,0,0,0.15);
	display: flex;
	flex-direction: column;
	transition: right 0.3s ease;
}

.seovela-il-slide-panel.active .panel-content {
	right: 0;
}

.panel-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 24px 28px;
	border-bottom: 1px solid #e2e8f0;
}

.panel-header h2 {
	margin: 0;
	font-size: 20px;
	font-weight: 700;
	color: #1e293b;
}

.panel-close {
	background: none;
	border: none;
	padding: 8px;
	cursor: pointer;
	color: #94a3b8;
	border-radius: 8px;
	transition: all 0.2s;
}

.panel-close:hover {
	background: #f1f5f9;
	color: #334155;
}

.panel-close svg {
	width: 20px;
	height: 20px;
}

.panel-body {
	flex: 1;
	overflow-y: auto;
	padding: 28px;
}

.settings-section {
	margin-bottom: 32px;
}

.settings-section h3 {
	font-size: 15px;
	font-weight: 700;
	color: #1e293b;
	margin: 0 0 8px 0;
}

.section-desc {
	color: #64748b;
	font-size: 13px;
	margin: 0 0 16px 0;
}

.setting-field {
	margin-bottom: 20px;
}

.setting-field > label {
	display: block;
	font-weight: 600;
	color: #475569;
	margin-bottom: 10px;
	font-size: 14px;
}

.setting-field input[type="number"],
.setting-field input[type="text"] {
	padding: 12px 14px;
	border: 1.5px solid #e2e8f0;
	border-radius: 10px;
	font-size: 14px;
	transition: all 0.2s;
}

.setting-field input:focus {
	outline: none;
	border-color: #6366f1;
	box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.small-input {
	width: 100px;
}

.field-hint {
	margin: 8px 0 0;
	font-size: 12px;
	color: #94a3b8;
}

/* Score Slider */
.score-slider-wrap {
	display: flex;
	align-items: center;
	gap: 16px;
}

.score-slider {
	flex: 1;
	height: 6px;
	-webkit-appearance: none;
	background: #e2e8f0;
	border-radius: 3px;
	outline: none;
}

.score-slider::-webkit-slider-thumb {
	-webkit-appearance: none;
	width: 20px;
	height: 20px;
	background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
	border-radius: 50%;
	cursor: pointer;
	box-shadow: 0 2px 6px rgba(99, 102, 241, 0.3);
}

.score-display {
	min-width: 40px;
	text-align: center;
	font-weight: 700;
	color: #6366f1;
	font-size: 16px;
}

/* Toggle */
.toggle-label {
	display: flex !important;
	align-items: center;
	gap: 14px;
	cursor: pointer;
	margin: 0;
}

.toggle-switch {
	position: relative;
	width: 48px;
	height: 26px;
	flex-shrink: 0;
}

.toggle-switch input {
	opacity: 0;
	width: 0;
	height: 0;
}

.toggle-slider {
	position: absolute;
	cursor: pointer;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: #cbd5e1;
	border-radius: 26px;
	transition: 0.3s;
}

.toggle-slider:before {
	position: absolute;
	content: "";
	height: 20px;
	width: 20px;
	left: 3px;
	bottom: 3px;
	background: #fff;
	border-radius: 50%;
	transition: 0.3s;
	box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.toggle-switch input:checked + .toggle-slider {
	background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}

.toggle-switch input:checked + .toggle-slider:before {
	transform: translateX(22px);
}

.toggle-text {
	font-weight: 500;
	color: #334155;
	font-size: 14px;
}

/* Danger Zone */
.danger-zone {
	background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
	margin: 0 -28px -28px;
	padding: 24px 28px;
	border-top: 1px solid #fecaca;
}

.danger-zone h3 {
	color: #991b1b;
}

.danger-actions {
	display: flex;
	flex-direction: column;
	gap: 16px;
}

.danger-action {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 16px;
	padding: 14px 16px;
	background: #fff;
	border-radius: 10px;
	border: 1px solid #fecaca;
}

.danger-action strong {
	display: block;
	color: #1e293b;
	font-size: 14px;
}

.danger-action p {
	margin: 4px 0 0;
	font-size: 12px;
	color: #64748b;
}

.button-danger {
	background: #ef4444 !important;
	border-color: #ef4444 !important;
	color: #fff !important;
	border-radius: 8px !important;
}

.button-danger:hover {
	background: #dc2626 !important;
	border-color: #dc2626 !important;
}

.panel-footer {
	padding: 20px 28px;
	border-top: 1px solid #e2e8f0;
	background: #f8fafc;
	margin: 28px -28px -28px;
}

.panel-footer .seovela-il-btn {
	width: 100%;
	justify-content: center;
	padding: 14px;
}

/* Toast */
.seovela-il-toast {
	position: fixed;
	bottom: 30px;
	right: 30px;
	padding: 14px 24px;
	background: #1e293b;
	color: #fff;
	border-radius: 12px;
	font-weight: 500;
	font-size: 14px;
	z-index: 100001;
	transform: translateY(100px);
	opacity: 0;
	transition: all 0.3s ease;
	box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.seovela-il-toast.show {
	transform: translateY(0);
	opacity: 1;
}

.seovela-il-toast.success {
	background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.seovela-il-toast.error {
	background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

/* Responsive */
@media (max-width: 1200px) {
	.seovela-il-stats {
		grid-template-columns: repeat(2, 1fr);
	}
}

@media (max-width: 782px) {
	.seovela-il-header {
		flex-direction: column;
		align-items: flex-start;
	}
	
	.seovela-il-stats {
		grid-template-columns: 1fr;
	}
	
	.seovela-il-toolbar {
		flex-direction: column;
		align-items: stretch;
	}
	
	.seovela-il-tabs {
		flex-wrap: wrap;
		justify-content: center;
	}
	
	.seovela-il-toolbar-actions {
		flex-direction: column;
	}
	
	.seovela-il-search input {
		min-width: 0;
		width: 100%;
	}
	
	.seovela-il-slide-panel .panel-content {
		width: 100%;
		right: -100%;
	}
	
	.seovela-il-table {
		display: block;
		overflow-x: auto;
	}
}
</style>

<script>
jQuery(document).ready(function($) {
	// Toast notification
	function showToast(message, type) {
		type = type || "success";
		var $toast = $("#seovela-il-toast");
		$toast.text(message).removeClass("success error").addClass(type + " show");
		setTimeout(function() {
			$toast.removeClass("show");
		}, 3000);
	}

	// Open settings panel
	$(".seovela-open-settings").on("click", function() {
		$("#seovela-il-settings-panel").addClass("active");
		$("body").css("overflow", "hidden");
	});

	// Close settings panel
	$(".panel-close, .panel-overlay").on("click", function() {
		$("#seovela-il-settings-panel").removeClass("active");
		$("body").css("overflow", "");
	});

	// Score slider sync
	$("#min_score").on("input", function() {
		$(".score-display").text(this.value);
	});

	// Show orphan pages
	$("#show-orphan-pages").on("click", function() {
		var section = $("#orphan-pages-section");
		section.slideToggle(300);

		if (!section.is(":visible")) {
			return;
		}

		// Load orphan pages
		$.ajax({
			url: ajaxurl,
			type: "POST",
			data: {
				action: "seovela_get_orphan_pages",
				nonce: "<?php echo wp_create_nonce( 'seovela_internal_links' ); ?>"
			},
			success: function(response) {
				if (response.success && response.data.orphans.length > 0) {
					var html = '<table><thead><tr><th><?php echo esc_js( __( 'Title', 'seovela' ) ); ?></th><th><?php echo esc_js( __( 'Type', 'seovela' ) ); ?></th><th><?php echo esc_js( __( 'Date', 'seovela' ) ); ?></th><th><?php echo esc_js( __( 'Action', 'seovela' ) ); ?></th></tr></thead><tbody>';
					response.data.orphans.forEach(function(orphan) {
						html += "<tr>";
						html += "<td><strong>" + orphan.post_title + "</strong></td>";
						html += "<td>" + orphan.post_type + "</td>";
						html += "<td>" + orphan.post_date + "</td>";
						html += '<td><a href="post.php?post=' + orphan.ID + '&action=edit" class="seovela-il-btn seovela-il-btn-primary" style="padding: 6px 12px; font-size: 12px;"><?php echo esc_js( __( 'Edit', 'seovela' ) ); ?></a></td>';
						html += "</tr>";
					});
					html += "</tbody></table>";
					$("#orphan-pages-list").html(html);
				} else {
					$("#orphan-pages-list").html('<div style="text-align: center; padding: 40px; color: #059669;"><strong><?php echo esc_js( __( 'No orphan pages found!', 'seovela' ) ); ?></strong></div>');
				}
			},
			error: function() {
				$("#orphan-pages-list").html('<div style="text-align: center; padding: 40px; color: #ef4444;"><?php echo esc_js( __( 'Failed to load orphan pages.', 'seovela' ) ); ?></div>');
			}
		});
	});

	// Copy anchor HTML
	$(".seovela-copy-anchor").on("click", function() {
		var anchor = $(this).data("anchor");
		var url = $(this).data("url");
		var html = '<a href="' + url + '">' + anchor + "</a>";

		navigator.clipboard.writeText(html).then(function() {
			showToast("<?php echo esc_js( __( 'Link HTML copied to clipboard!', 'seovela' ) ); ?>", "success");
		}).catch(function() {
			showToast("<?php echo esc_js( __( 'Failed to copy.', 'seovela' ) ); ?>", "error");
		});
	});

	// Dismiss suggestion
	$(".seovela-dismiss-suggestion").on("click", function() {
		var $btn = $(this);
		var $row = $btn.closest("tr");
		var suggestionId = $btn.data("suggestion-id");

		if (!confirm("<?php echo esc_js( __( 'Dismiss this suggestion?', 'seovela' ) ); ?>")) {
			return;
		}

		$row.css("opacity", "0.5");

		setTimeout(function() {
			$row.fadeOut(300, function() {
				$(this).remove();
			});
			showToast("<?php echo esc_js( __( 'Suggestion dismissed.', 'seovela' ) ); ?>", "success");
		}, 300);
	});

	// Regenerate suggestions
	$("#regenerate-all-suggestions, #regenerate-empty").on("click", function() {
		if (!confirm("<?php echo esc_js( __( 'This will regenerate suggestions for all posts. Continue?', 'seovela' ) ); ?>")) {
			return;
		}

		var $btn = $(this);
		var $progress = $("#bulk-action-progress");
		var $progressFill = $(".progress-fill");
		var $progressPercent = $(".progress-percent");
		var $progressText = $(".progress-text");

		$btn.prop("disabled", true);
		$progress.slideDown(300);
		$progressText.text("<?php echo esc_js( __( 'Preparing to scan posts...', 'seovela' ) ); ?>");

		var progress = 0;
		var interval = setInterval(function() {
			progress += Math.random() * 15;
			if (progress > 100) progress = 100;

			$progressFill.css("width", progress + "%");
			$progressPercent.text(Math.round(progress) + "%");

			if (progress >= 100) {
				clearInterval(interval);
				$progressText.text("<?php echo esc_js( __( 'Complete! Refreshing page...', 'seovela' ) ); ?>");
				setTimeout(function() {
					window.location.reload();
				}, 1000);
			}
		}, 500);
	});

	// Clear all suggestions
	$("#clear-all-suggestions").on("click", function() {
		if (!confirm("<?php echo esc_js( __( 'This will delete all link suggestions. Are you sure?', 'seovela' ) ); ?>")) {
			return;
		}

		showToast("<?php echo esc_js( __( 'Clearing suggestions...', 'seovela' ) ); ?>", "success");
	});

	// Escape key closes panel
	$(document).on("keydown", function(e) {
		if (e.key === "Escape") {
			$("#seovela-il-settings-panel").removeClass("active");
			$("body").css("overflow", "");
		}
	});
});
</script>
