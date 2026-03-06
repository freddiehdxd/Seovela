<?php
/**
 * 404 Monitor View
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Default settings
$cleanup_days = isset( $settings_404['cleanup_days'] ) ? $settings_404['cleanup_days'] : 30;
$redirect_url = isset( $settings_404['redirect_url'] ) ? $settings_404['redirect_url'] : '';
$redirect_enabled = isset( $settings_404['redirect_enabled'] ) ? $settings_404['redirect_enabled'] : 0;
?>

<div class="seovela-premium-page seovela-404-premium">

	<!-- Premium Header -->
	<div class="seovela-page-header">
		<div class="seovela-page-header-bg"></div>
		<div class="seovela-page-header-content">
			<div class="seovela-page-header-top">
				<div class="seovela-page-header-text">
					<div class="seovela-page-breadcrumb">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela' ) ); ?>">Seovela</a>
						<span class="sep">/</span>
						<span class="current"><?php esc_html_e( '404 Monitor', 'seovela' ); ?></span>
					</div>
					<h1><?php esc_html_e( '404 Monitor', 'seovela' ); ?></h1>
					<p><?php esc_html_e( 'Track and resolve broken URLs to protect your SEO rankings and improve user experience.', 'seovela' ); ?></p>
				</div>
				<div class="seovela-page-header-actions" style="display: flex; align-items: flex-start; gap: 16px;">
					<div class="seovela-page-header-stats">
						<div class="seovela-header-stat">
							<div class="seovela-header-stat-number"><?php echo esc_html( number_format( $statistics['total'] ) ); ?></div>
							<div class="seovela-header-stat-label"><?php esc_html_e( 'Total', 'seovela' ); ?></div>
						</div>
						<div class="seovela-header-stat-divider"></div>
						<div class="seovela-header-stat">
							<div class="seovela-header-stat-number"><?php echo esc_html( number_format( $statistics['unresolved'] ) ); ?></div>
							<div class="seovela-header-stat-label"><?php esc_html_e( 'Unresolved', 'seovela' ); ?></div>
						</div>
						<div class="seovela-header-stat-divider"></div>
						<div class="seovela-header-stat">
							<div class="seovela-header-stat-number"><?php echo esc_html( number_format( $statistics['total_hits'] ? $statistics['total_hits'] : 0 ) ); ?></div>
							<div class="seovela-header-stat-label"><?php esc_html_e( 'Hits', 'seovela' ); ?></div>
						</div>
					</div>
					<button type="button" class="seovela-header-settings-btn seovela-open-settings">
						<span class="dashicons dashicons-admin-generic"></span>
					</button>
				</div>
			</div>

			<!-- Tabs in header -->
			<div class="seovela-page-header-tabs">
				<a href="<?php echo esc_url( add_query_arg( 'status', 'unresolved' ) ); ?>" class="seovela-header-tab <?php echo ( ! isset( $_GET['status'] ) || $_GET['status'] !== 'resolved' ) ? 'active' : ''; ?>">
					<span class="dashicons dashicons-flag"></span>
					<?php esc_html_e( 'Unresolved', 'seovela' ); ?>
					<span class="tab-badge"><?php echo esc_html( $statistics['unresolved'] ); ?></span>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'status', 'resolved' ) ); ?>" class="seovela-header-tab <?php echo ( isset( $_GET['status'] ) && $_GET['status'] === 'resolved' ) ? 'active' : ''; ?>">
					<span class="dashicons dashicons-yes-alt"></span>
					<?php esc_html_e( 'Resolved', 'seovela' ); ?>
				</a>
			</div>
		</div>
	</div>

	<div class="seovela-page-body">

		<!-- Search & Actions -->
		<div class="seovela-404-toolbar">
			<div class="seovela-search-box">
				<form method="get">
					<input type="hidden" name="page" value="seovela-404-monitor">
					<?php if ( isset( $_GET['status'] ) ) : ?>
						<input type="hidden" name="status" value="<?php echo esc_attr( $_GET['status'] ); ?>">
					<?php endif; ?>
					<input type="search" name="search" value="<?php echo esc_attr( isset( $_GET['search'] ) ? $_GET['search'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Search URLs...', 'seovela' ); ?>">
					<button type="submit" class="button">
						<span class="dashicons dashicons-search"></span>
					</button>
				</form>
			</div>
		</div>

		<!-- 404 Logs Table -->
		<div class="seovela-404-table-wrap">
			<table class="seovela-404-table">
				<thead>
					<tr>
						<th class="column-url"><?php esc_html_e( 'URL', 'seovela' ); ?></th>
						<th class="column-referer"><?php esc_html_e( 'Referer', 'seovela' ); ?></th>
						<th class="column-hits"><?php esc_html_e( 'Hits', 'seovela' ); ?></th>
						<th class="column-last-hit"><?php esc_html_e( 'Last Hit', 'seovela' ); ?></th>
						<th class="column-actions"><?php esc_html_e( 'Actions', 'seovela' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $logs ) ) : ?>
						<?php foreach ( $logs as $log ) : ?>
							<tr data-log-id="<?php echo esc_attr( $log->id ); ?>" class="<?php echo $log->resolved ? 'resolved' : ''; ?>">
								<td class="column-url">
									<div class="url-cell">
										<code><?php echo esc_html( $log->url ); ?></code>
										<a href="<?php echo esc_url( $log->url ); ?>" target="_blank" class="test-link" title="<?php esc_attr_e( 'Test URL', 'seovela' ); ?>">
											<span class="dashicons dashicons-external"></span>
										</a>
									</div>
								</td>
								<td class="column-referer">
									<?php if ( $log->referer ) : ?>
										<a href="<?php echo esc_url( $log->referer ); ?>" target="_blank" rel="noopener" class="referer-link">
											<?php echo esc_html( parse_url( $log->referer, PHP_URL_HOST ) ); ?>
										</a>
									<?php else : ?>
										<span class="seovela-na"><?php esc_html_e( 'Direct', 'seovela' ); ?></span>
									<?php endif; ?>
								</td>
								<td class="column-hits">
									<span class="hit-badge"><?php echo esc_html( number_format( $log->count ) ); ?></span>
								</td>
								<td class="column-last-hit">
									<span class="time-ago"><?php echo esc_html( human_time_diff( strtotime( $log->last_hit ), current_time( 'timestamp' ) ) ); ?> ago</span>
									<span class="time-exact"><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $log->last_hit ) ) ); ?></span>
								</td>
								<td class="column-actions">
									<div class="action-buttons">
										<?php if ( ! $log->resolved ) : ?>
											<button class="btn-action btn-primary seovela-create-redirect" data-log-id="<?php echo esc_attr( $log->id ); ?>" data-source-url="<?php echo esc_attr( $log->url ); ?>" title="<?php esc_attr_e( 'Create Redirect', 'seovela' ); ?>">
												<span class="dashicons dashicons-admin-links"></span>
											</button>
											<button class="btn-action btn-success seovela-resolve-404" data-log-id="<?php echo esc_attr( $log->id ); ?>" title="<?php esc_attr_e( 'Mark Resolved', 'seovela' ); ?>">
												<span class="dashicons dashicons-yes"></span>
											</button>
										<?php endif; ?>
										<button class="btn-action btn-danger seovela-delete-404" data-log-id="<?php echo esc_attr( $log->id ); ?>" title="<?php esc_attr_e( 'Delete', 'seovela' ); ?>">
											<span class="dashicons dashicons-trash"></span>
										</button>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="5" class="seovela-empty-state">
								<div class="empty-icon">
									<span class="dashicons dashicons-yes-alt"></span>
								</div>
								<p>
									<?php
									if ( isset( $_GET['status'] ) && $_GET['status'] === 'resolved' ) {
										esc_html_e( 'No resolved 404 logs found.', 'seovela' );
									} else {
										esc_html_e( 'No 404 errors logged yet. Great job!', 'seovela' );
									}
									?>
								</p>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<!-- Pagination -->
		<?php if ( $total_pages > 1 ) : ?>
			<div class="seovela-pagination">
				<?php
				$args = array( 'paged' => '%#%' );
				if ( isset( $_GET['status'] ) ) {
					$args['status'] = $_GET['status'];
				}
				if ( isset( $_GET['search'] ) ) {
					$args['search'] = $_GET['search'];
				}

				echo paginate_links( array(
					'base'      => add_query_arg( $args ),
					'format'    => '',
					'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span>',
					'next_text' => '<span class="dashicons dashicons-arrow-right-alt2"></span>',
					'total'     => $total_pages,
					'current'   => $page,
				) );
				?>
			</div>
		<?php endif; ?>

	</div><!-- .seovela-page-body -->
</div><!-- .seovela-premium-page -->

<!-- Settings Slide Panel -->
<div id="seovela-404-settings-panel" class="seovela-slide-panel">
	<div class="panel-overlay"></div>
	<div class="panel-content">
		<div class="panel-header">
			<h2><?php esc_html_e( '404 Settings', 'seovela' ); ?></h2>
			<button type="button" class="panel-close">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		
		<form id="seovela-404-settings-form" class="panel-body">
			<!-- Auto Cleanup -->
			<div class="settings-section">
				<h3><?php esc_html_e( 'Auto Cleanup', 'seovela' ); ?></h3>
				<p class="section-desc"><?php esc_html_e( 'Automatically delete old resolved 404 logs.', 'seovela' ); ?></p>
				
				<div class="setting-field">
					<label for="cleanup-days"><?php esc_html_e( 'Delete resolved logs after', 'seovela' ); ?></label>
					<div class="input-with-suffix">
						<input type="number" id="cleanup-days" name="cleanup_days" value="<?php echo esc_attr( $cleanup_days ); ?>" min="1" max="365">
						<span class="suffix"><?php esc_html_e( 'days', 'seovela' ); ?></span>
					</div>
				</div>
			</div>
			
			<!-- Global Redirect -->
			<div class="settings-section">
				<h3><?php esc_html_e( 'Global 404 Redirect', 'seovela' ); ?></h3>
				<p class="section-desc"><?php esc_html_e( 'Redirect all 404 pages to a specific URL.', 'seovela' ); ?></p>
				
				<div class="setting-field">
					<label class="toggle-label">
						<span class="toggle-switch">
							<input type="checkbox" id="redirect-enabled" name="redirect_enabled" value="1" <?php checked( $redirect_enabled, 1 ); ?>>
							<span class="toggle-slider"></span>
						</span>
						<span class="toggle-text"><?php esc_html_e( 'Enable global redirect', 'seovela' ); ?></span>
					</label>
				</div>
				
				<div class="setting-field redirect-url-field" style="<?php echo $redirect_enabled ? '' : 'opacity: 0.5;'; ?>">
					<label for="redirect-url"><?php esc_html_e( 'Redirect URL', 'seovela' ); ?></label>
					<input type="url" id="redirect-url" name="redirect_url" value="<?php echo esc_attr( $redirect_url ); ?>" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>" <?php echo $redirect_enabled ? '' : 'disabled'; ?>>
					<p class="field-hint"><?php esc_html_e( 'All 404 pages will redirect to this URL', 'seovela' ); ?></p>
				</div>
			</div>
			
			<!-- Danger Zone -->
			<div class="settings-section danger-zone">
				<h3><?php esc_html_e( 'Danger Zone', 'seovela' ); ?></h3>
				
				<div class="danger-actions">
					<div class="danger-action">
						<div>
							<strong><?php esc_html_e( 'Delete Resolved Logs', 'seovela' ); ?></strong>
							<p><?php esc_html_e( 'Remove all resolved 404 entries.', 'seovela' ); ?></p>
						</div>
						<button type="button" class="button seovela-cleanup-404"><?php esc_html_e( 'Delete Resolved', 'seovela' ); ?></button>
					</div>
					
					<div class="danger-action">
						<div>
							<strong><?php esc_html_e( 'Delete All Logs', 'seovela' ); ?></strong>
							<p><?php esc_html_e( 'Permanently remove all 404 entries.', 'seovela' ); ?></p>
						</div>
						<button type="button" class="button button-danger seovela-delete-all-404"><?php esc_html_e( 'Delete All', 'seovela' ); ?></button>
					</div>
				</div>
			</div>
			
			<div class="panel-footer">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'seovela' ); ?></button>
			</div>
		</form>
	</div>
</div>

<!-- Create Redirect Modal -->
<div id="seovela-create-redirect-modal" class="seovela-modal">
	<div class="seovela-modal-content">
		<span class="seovela-modal-close">&times;</span>
		<h2><?php esc_html_e( 'Create Redirect', 'seovela' ); ?></h2>
		<form id="seovela-create-redirect-form">
			<input type="hidden" id="404-log-id" name="log_id" value="">
			
			<div class="seovela-form-field">
				<label><?php esc_html_e( 'Source URL (404)', 'seovela' ); ?></label>
				<input type="text" id="404-source-url" readonly>
			</div>

			<div class="seovela-form-field">
				<label for="404-target-url"><?php esc_html_e( 'Target URL', 'seovela' ); ?> *</label>
				<input type="text" id="404-target-url" name="target_url" required placeholder="<?php esc_attr_e( '/new-page/ or https://...', 'seovela' ); ?>">
			</div>

			<div class="seovela-form-actions">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Create Redirect', 'seovela' ); ?></button>
				<button type="button" class="button seovela-modal-close"><?php esc_html_e( 'Cancel', 'seovela' ); ?></button>
			</div>
		</form>
	</div>
</div>

<script>
var seovela404Monitor = {
	ajaxUrl: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
	nonce: '<?php echo esc_js( wp_create_nonce( 'seovela_404_monitor' ) ); ?>'
};
</script>

<style>
/* 404 Monitor - Page-specific styles */
/* (Header, stats, breadcrumb handled by unified premium CSS in admin.css) */

/* Header settings button */
.seovela-header-settings-btn {
	width: 44px;
	height: 44px;
	border-radius: 12px;
	background: rgba(255, 255, 255, 0.1);
	backdrop-filter: blur(12px);
	-webkit-backdrop-filter: blur(12px);
	border: 1px solid rgba(255, 255, 255, 0.15);
	color: rgba(255, 255, 255, 0.7);
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.2s ease;
	flex-shrink: 0;
	margin-top: 8px;
}

.seovela-header-settings-btn:hover {
	background: rgba(255, 255, 255, 0.2);
	color: #ffffff;
	border-color: rgba(255, 255, 255, 0.3);
}

.seovela-header-settings-btn .dashicons {
	font-size: 20px;
	width: 20px;
	height: 20px;
}

/* Toolbar */
.seovela-404-premium .seovela-404-toolbar {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 24px;
	background: #fff;
	padding: 14px 20px;
	border-radius: 12px;
	border: 1px solid #e5e7eb;
}

.seovela-404-premium .seovela-search-box form {
	display: flex;
	gap: 0;
}

.seovela-404-premium .seovela-search-box input[type="search"] {
	padding: 9px 14px;
	border: 1px solid #e2e8f0;
	border-radius: 8px 0 0 8px;
	min-width: 280px;
	font-size: 14px;
	transition: border-color 0.2s ease;
}

.seovela-404-premium .seovela-search-box input[type="search"]:focus {
	outline: none;
	border-color: #3b82f6;
	box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.seovela-404-premium .seovela-search-box button {
	border-radius: 0 8px 8px 0;
	padding: 0 14px !important;
	border-left: none;
}

.seovela-404-premium .seovela-search-box .dashicons {
	font-size: 16px;
	width: 16px;
	height: 16px;
	margin-top: 2px;
}

/* Table */
.seovela-404-premium .seovela-404-table-wrap {
	background: #fff;
	border-radius: 16px;
	overflow: hidden;
	border: 1px solid #e5e7eb;
}

.seovela-404-premium .seovela-404-table {
	width: 100%;
	border-collapse: collapse;
}

.seovela-404-premium .seovela-404-table thead {
	background: #f8fafc;
}

.seovela-404-premium .seovela-404-table th {
	padding: 14px 20px;
	text-align: left;
	font-weight: 600;
	color: #475569;
	font-size: 12px;
	text-transform: uppercase;
	letter-spacing: 0.06em;
	border-bottom: 2px solid #e2e8f0;
}

.seovela-404-premium .seovela-404-table td {
	padding: 16px 20px;
	border-bottom: 1px solid #f1f5f9;
	vertical-align: middle;
}

.seovela-404-premium .seovela-404-table tbody tr:last-child td {
	border-bottom: none;
}

.seovela-404-premium .seovela-404-table tbody tr:hover {
	background: #f8fafc;
}

.seovela-404-premium .seovela-404-table tbody tr.resolved {
	opacity: 0.55;
}

.seovela-404-premium .column-url { width: 35%; }
.seovela-404-premium .column-referer { width: 18%; }
.seovela-404-premium .column-hits { width: 10%; text-align: center; }
.seovela-404-premium .column-last-hit { width: 17%; }
.seovela-404-premium .column-actions { width: 20%; text-align: right; }

.seovela-404-premium .url-cell {
	display: flex;
	align-items: center;
	gap: 8px;
}

.seovela-404-premium .url-cell code {
	background: #f1f5f9;
	padding: 5px 10px;
	border-radius: 6px;
	font-size: 13px;
	color: #334155;
	word-break: break-all;
	max-width: 300px;
	display: inline-block;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	border: 1px solid #e2e8f0;
}

.seovela-404-premium .test-link {
	color: #94a3b8;
	text-decoration: none;
	transition: color 0.2s;
}

.seovela-404-premium .test-link:hover {
	color: #3b82f6;
}

.seovela-404-premium .referer-link {
	color: #3b82f6;
	text-decoration: none;
	font-weight: 500;
}

.seovela-404-premium .referer-link:hover {
	text-decoration: underline;
}

.seovela-404-premium .seovela-na {
	color: #94a3b8;
	font-style: italic;
	font-size: 13px;
}

.seovela-404-premium .hit-badge {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	min-width: 32px;
	padding: 4px 12px;
	background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
	color: #fff;
	border-radius: 20px;
	font-weight: 600;
	font-size: 13px;
}

.seovela-404-premium .time-ago {
	display: block;
	color: #334155;
	font-weight: 500;
	font-size: 14px;
}

.seovela-404-premium .time-exact {
	display: block;
	color: #94a3b8;
	font-size: 12px;
	margin-top: 2px;
}

/* Action Buttons */
.seovela-404-premium .action-buttons {
	display: flex;
	gap: 6px;
	justify-content: flex-end;
}

.seovela-404-premium .btn-action {
	width: 34px;
	height: 34px;
	padding: 0;
	border: 1px solid #e2e8f0;
	border-radius: 8px;
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.2s ease;
	background: #f8fafc;
	color: #64748b;
}

.seovela-404-premium .btn-action:hover {
	transform: translateY(-1px);
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.seovela-404-premium .btn-action .dashicons {
	font-size: 16px;
	width: 16px;
	height: 16px;
}

.seovela-404-premium .btn-action.btn-primary {
	background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
	color: #fff;
	border-color: transparent;
}

.seovela-404-premium .btn-action.btn-primary:hover {
	background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
}

.seovela-404-premium .btn-action.btn-success {
	background: linear-gradient(135deg, #10b981 0%, #059669 100%);
	color: #fff;
	border-color: transparent;
}

.seovela-404-premium .btn-action.btn-success:hover {
	background: linear-gradient(135deg, #059669 0%, #047857 100%);
}

.seovela-404-premium .btn-action.btn-danger:hover {
	background: #ef4444;
	color: #fff;
	border-color: transparent;
}

/* Empty State */
.seovela-404-premium .seovela-empty-state {
	text-align: center;
	padding: 60px 20px !important;
}

.seovela-404-premium .empty-icon {
	width: 64px;
	height: 64px;
	margin: 0 auto 16px;
	background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
}

.seovela-404-premium .empty-icon .dashicons {
	font-size: 32px;
	width: 32px;
	height: 32px;
	color: #fff;
}

.seovela-404-premium .seovela-empty-state p {
	color: #64748b;
	font-size: 15px;
	margin: 0;
}

/* Pagination */
.seovela-404-premium .seovela-pagination {
	display: flex;
	justify-content: center;
	margin-top: 28px;
}

.seovela-404-premium .seovela-pagination .page-numbers {
	padding: 8px 14px;
	margin: 0 2px;
	border-radius: 8px;
	text-decoration: none;
	color: #64748b;
	background: #fff;
	border: 1px solid #e2e8f0;
	font-weight: 500;
	transition: all 0.2s ease;
}

.seovela-404-premium .seovela-pagination .page-numbers:hover {
	background: #f1f5f9;
	border-color: #cbd5e1;
}

.seovela-404-premium .seovela-pagination .page-numbers.current {
	background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
	color: #fff;
	border-color: transparent;
}

/* Slide Panel */
.seovela-slide-panel {
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

.seovela-slide-panel.active {
	visibility: visible;
	opacity: 1;
	transition: visibility 0s, opacity 0.3s;
}

.panel-overlay {
	position: absolute;
	top: 0;
	right: 0;
	bottom: 0;
	left: 0;
	background: rgba(0,0,0,0.5);
	backdrop-filter: blur(4px);
}

.panel-content {
	position: absolute;
	top: 0;
	right: -420px;
	bottom: 0;
	width: 420px;
	background: #fff;
	box-shadow: -4px 0 24px rgba(0,0,0,0.15);
	display: flex;
	flex-direction: column;
	transition: right 0.3s ease;
}

.seovela-slide-panel.active .panel-content {
	right: 0;
}

.panel-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 20px 24px;
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
	padding: 4px;
	cursor: pointer;
	color: #94a3b8;
	border-radius: 6px;
	transition: all 0.2s;
}

.panel-close:hover {
	background: #f1f5f9;
	color: #334155;
}

.panel-body {
	flex: 1;
	overflow-y: auto;
	padding: 24px;
}

.settings-section {
	margin-bottom: 32px;
}

.settings-section h3 {
	font-size: 15px;
	font-weight: 600;
	color: #1e293b;
	margin: 0 0 8px 0;
}

.section-desc {
	color: #64748b;
	font-size: 13px;
	margin: 0 0 16px 0;
}

.setting-field {
	margin-bottom: 16px;
}

.setting-field label {
	display: block;
	font-weight: 500;
	color: #475569;
	margin-bottom: 8px;
	font-size: 14px;
}

.setting-field input[type="number"],
.setting-field input[type="url"],
.setting-field input[type="text"] {
	width: 100%;
	padding: 10px 12px;
	border: 1px solid #e2e8f0;
	border-radius: 8px;
	font-size: 14px;
	transition: all 0.2s;
}

.setting-field input:focus {
	outline: none;
	border-color: #3b82f6;
	box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.input-with-suffix {
	display: flex;
	align-items: center;
	gap: 12px;
}

.input-with-suffix input {
	width: 100px !important;
}

.input-with-suffix .suffix {
	color: #64748b;
	font-size: 14px;
}

.field-hint {
	margin: 6px 0 0;
	font-size: 12px;
	color: #94a3b8;
}

/* Toggle */
.toggle-label {
	display: flex !important;
	align-items: center;
	gap: 12px;
	cursor: pointer;
	margin: 0;
}

.toggle-switch {
	position: relative;
	width: 44px;
	height: 24px;
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
	border-radius: 24px;
	transition: 0.3s;
}

.toggle-slider:before {
	position: absolute;
	content: "";
	height: 18px;
	width: 18px;
	left: 3px;
	bottom: 3px;
	background: #fff;
	border-radius: 50%;
	transition: 0.3s;
}

.toggle-switch input:checked + .toggle-slider {
	background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.toggle-switch input:checked + .toggle-slider:before {
	transform: translateX(20px);
}

.toggle-text {
	font-weight: 500;
	color: #334155;
}

/* Danger Zone */
.danger-zone {
	background: #fef2f2;
	margin: 0 -24px -24px;
	padding: 24px;
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
	padding: 12px;
	background: #fff;
	border-radius: 8px;
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
}

.button-danger:hover {
	background: #dc2626 !important;
	border-color: #dc2626 !important;
}

.panel-footer {
	padding: 20px 24px;
	border-top: 1px solid #e2e8f0;
	background: #f8fafc;
	margin: 24px -24px -24px;
}

.panel-footer .button-primary {
	width: 100%;
	padding: 12px !important;
	font-size: 14px;
	border-radius: 8px;
	background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
	border: none !important;
	box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2) !important;
	font-weight: 600;
	transition: all 0.2s ease;
}

.panel-footer .button-primary:hover {
	background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
	transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 960px) {
	.seovela-page-header-actions {
		flex-direction: column;
		align-items: flex-end;
	}
}

@media (max-width: 782px) {
	.seovela-404-premium .seovela-404-toolbar {
		flex-direction: column;
		gap: 12px;
		align-items: stretch;
	}
	
	.seovela-404-premium .seovela-search-box input[type="search"] {
		min-width: 0;
		flex: 1;
	}
	
	.panel-content {
		width: 100%;
		right: -100%;
	}
}
</style>
