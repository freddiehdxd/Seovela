<?php
/**
 * Redirects Manager View
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="seovela-premium-page seovela-redirects-premium">

	<!-- Premium Header -->
	<div class="seovela-page-header">
		<div class="seovela-page-header-bg"></div>
		<div class="seovela-page-header-content">
			<div class="seovela-page-header-top">
				<div class="seovela-page-header-text">
					<div class="seovela-page-breadcrumb">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela' ) ); ?>">Seovela</a>
						<span class="sep">/</span>
						<span class="current"><?php esc_html_e( 'Redirects', 'seovela' ); ?></span>
					</div>
					<h1><?php esc_html_e( 'Redirects Manager', 'seovela' ); ?></h1>
					<p><?php esc_html_e( 'Create and manage URL redirects to maintain SEO value and fix broken links.', 'seovela' ); ?></p>
				</div>
				<div class="seovela-page-header-stats">
					<div class="seovela-header-stat">
						<div class="seovela-header-stat-number"><?php echo esc_html( $total ); ?></div>
						<div class="seovela-header-stat-label"><?php esc_html_e( 'Total', 'seovela' ); ?></div>
					</div>
					<div class="seovela-header-stat-divider"></div>
					<div class="seovela-header-stat">
						<div class="seovela-header-stat-number"><?php echo esc_html( count( array_filter( $redirects, function( $r ) { return $r->enabled; } ) ) ); ?></div>
						<div class="seovela-header-stat-label"><?php esc_html_e( 'Active', 'seovela' ); ?></div>
					</div>
					<div class="seovela-header-stat-divider"></div>
					<div class="seovela-header-stat">
						<div class="seovela-header-stat-number"><?php echo esc_html( array_sum( array_column( $redirects, 'hits' ) ) ); ?></div>
						<div class="seovela-header-stat-label"><?php esc_html_e( 'Hits', 'seovela' ); ?></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="seovela-page-body">

	<!-- Actions Bar -->
	<div class="seovela-actions-bar">
		<div class="seovela-search-box">
			<form method="get">
				<input type="hidden" name="page" value="seovela-redirects">
				<input type="search" name="search" value="<?php echo esc_attr( isset( $_GET['search'] ) ? $_GET['search'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Search redirects...', 'seovela' ); ?>">
				<button type="submit" class="button"><?php esc_html_e( 'Search', 'seovela' ); ?></button>
			</form>
		</div>
		<div class="seovela-bulk-actions">
			<button class="button seovela-import-redirects"><?php esc_html_e( 'Import CSV', 'seovela' ); ?></button>
			<button class="button seovela-export-redirects"><?php esc_html_e( 'Export CSV', 'seovela' ); ?></button>
			<button class="button button-primary seovela-add-redirect-btn">
				<span class="dashicons dashicons-plus-alt2" style="margin-top: 3px; margin-right: 2px;"></span>
				<?php esc_html_e( 'Add New', 'seovela' ); ?>
			</button>
		</div>
	</div>

	<!-- Redirects Table -->
	<table class="wp-list-table widefat fixed striped seovela-redirects-table">
		<thead>
			<tr>
				<th class="column-source"><?php esc_html_e( 'Source URL', 'seovela' ); ?></th>
				<th class="column-target"><?php esc_html_e( 'Target URL', 'seovela' ); ?></th>
				<th class="column-type"><?php esc_html_e( 'Type', 'seovela' ); ?></th>
				<th class="column-hits"><?php esc_html_e( 'Hits', 'seovela' ); ?></th>
				<th class="column-status"><?php esc_html_e( 'Status', 'seovela' ); ?></th>
				<th class="column-actions"><?php esc_html_e( 'Actions', 'seovela' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $redirects ) ) : ?>
				<?php foreach ( $redirects as $redirect ) : ?>
					<tr data-redirect-id="<?php echo esc_attr( $redirect->id ); ?>" class="<?php echo $redirect->enabled ? '' : 'disabled'; ?>">
						<td class="column-source">
							<strong><?php echo esc_html( $redirect->source_url ); ?></strong>
							<?php if ( $redirect->regex ) : ?>
								<span class="seovela-badge seovela-badge-regex"><?php esc_html_e( 'Regex', 'seovela' ); ?></span>
							<?php endif; ?>
						</td>
						<td class="column-target">
							<a href="<?php echo esc_url( $redirect->target_url ); ?>" target="_blank" rel="noopener">
								<?php echo esc_html( $redirect->target_url ); ?>
							</a>
						</td>
						<td class="column-type">
							<span class="seovela-badge seovela-badge-<?php echo esc_attr( $redirect->redirect_type ); ?>">
								<?php echo esc_html( $redirect->redirect_type ); ?>
							</span>
						</td>
						<td class="column-hits">
							<?php echo esc_html( number_format( $redirect->hits ) ); ?>
							<?php if ( $redirect->last_hit ) : ?>
								<br><small><?php echo esc_html( human_time_diff( strtotime( $redirect->last_hit ), current_time( 'timestamp' ) ) ); ?> ago</small>
							<?php endif; ?>
						</td>
						<td class="column-status">
							<label class="seovela-toggle">
								<input type="checkbox" class="seovela-toggle-redirect" <?php checked( $redirect->enabled, 1 ); ?> data-redirect-id="<?php echo esc_attr( $redirect->id ); ?>">
								<span class="seovela-toggle-slider"></span>
							</label>
						</td>
						<td class="column-actions">
							<button class="button button-small seovela-edit-redirect" data-redirect-id="<?php echo esc_attr( $redirect->id ); ?>"><?php esc_html_e( 'Edit', 'seovela' ); ?></button>
							<button class="button button-small button-link-delete seovela-delete-redirect" data-redirect-id="<?php echo esc_attr( $redirect->id ); ?>"><?php esc_html_e( 'Delete', 'seovela' ); ?></button>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="6" class="seovela-empty-state">
						<p><?php esc_html_e( 'No redirects found.', 'seovela' ); ?></p>
						<button class="button button-primary seovela-add-redirect-btn"><?php esc_html_e( 'Add Your First Redirect', 'seovela' ); ?></button>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>

	<!-- Pagination -->
	<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<?php
				echo paginate_links( array(
					'base'      => add_query_arg( 'paged', '%#%' ),
					'format'    => '',
					'prev_text' => __( '&laquo;', 'seovela' ),
					'next_text' => __( '&raquo;', 'seovela' ),
					'total'     => $total_pages,
					'current'   => $page,
				) );
				?>
			</div>
		</div>
	<?php endif; ?>

	</div><!-- .seovela-page-body -->
</div><!-- .seovela-premium-page -->

<!-- Add/Edit Redirect Modal -->
<div id="seovela-redirect-modal" class="seovela-modal">
	<div class="seovela-modal-content">
		<span class="seovela-modal-close">&times;</span>
		<h2 id="seovela-modal-title"><?php esc_html_e( 'Add Redirect', 'seovela' ); ?></h2>
		<form id="seovela-redirect-form">
			<input type="hidden" id="redirect-id" name="redirect_id" value="">
			
			<div class="seovela-form-field">
				<label for="source-url"><?php esc_html_e( 'Source URL', 'seovela' ); ?> *</label>
				<input type="text" id="source-url" name="source_url" required placeholder="<?php esc_attr_e( '/old-page/', 'seovela' ); ?>">
				<p class="description"><?php esc_html_e( 'The URL to redirect FROM. Can be relative or absolute.', 'seovela' ); ?></p>
			</div>

			<div class="seovela-form-field">
				<label for="target-url"><?php esc_html_e( 'Target URL', 'seovela' ); ?> *</label>
				<input type="text" id="target-url" name="target_url" required placeholder="<?php esc_attr_e( '/new-page/', 'seovela' ); ?>">
				<p class="description"><?php esc_html_e( 'The URL to redirect TO. Can be relative or absolute.', 'seovela' ); ?></p>
			</div>

			<div class="seovela-form-row">
				<div class="seovela-form-field">
					<label for="redirect-type"><?php esc_html_e( 'Redirect Type', 'seovela' ); ?></label>
					<select id="redirect-type" name="redirect_type">
						<option value="301"><?php esc_html_e( '301 Permanent', 'seovela' ); ?></option>
						<option value="302"><?php esc_html_e( '302 Temporary', 'seovela' ); ?></option>
						<option value="307"><?php esc_html_e( '307 Temporary (POST)', 'seovela' ); ?></option>
					</select>
				</div>

				<div class="seovela-form-field">
					<label class="seovela-checkbox-label">
						<input type="checkbox" id="regex-enabled" name="regex" value="1">
						<?php esc_html_e( 'Use Regular Expression', 'seovela' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Enable regex pattern matching for source URL', 'seovela' ); ?></p>
				</div>
			</div>

			<div class="seovela-form-field">
				<label class="seovela-checkbox-label">
					<input type="checkbox" id="enabled" name="enabled" value="1" checked>
					<?php esc_html_e( 'Enable this redirect', 'seovela' ); ?>
				</label>
			</div>

			<div class="seovela-form-actions">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Redirect', 'seovela' ); ?></button>
				<button type="button" class="button seovela-modal-close"><?php esc_html_e( 'Cancel', 'seovela' ); ?></button>
			</div>
		</form>
	</div>
</div>

<!-- Import Modal -->
<div id="seovela-import-modal" class="seovela-modal">
	<div class="seovela-modal-content">
		<span class="seovela-modal-close">&times;</span>
		<h2><?php esc_html_e( 'Import Redirects', 'seovela' ); ?></h2>
		<form id="seovela-import-form" enctype="multipart/form-data">
			<div class="seovela-form-field">
				<label for="csv-file"><?php esc_html_e( 'CSV File', 'seovela' ); ?></label>
				<input type="file" id="csv-file" name="csv_file" accept=".csv" required>
				<p class="description">
					<?php esc_html_e( 'CSV format: Source URL, Target URL, Type, Regex, Enabled', 'seovela' ); ?>
				</p>
			</div>
			<div class="seovela-form-actions">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Import', 'seovela' ); ?></button>
				<button type="button" class="button seovela-modal-close"><?php esc_html_e( 'Cancel', 'seovela' ); ?></button>
			</div>
		</form>
	</div>
</div>

<script>
// Fallback if localization fails
if (typeof seovelaRedirects === 'undefined') {
    var seovelaRedirects = {
        ajaxUrl: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
        nonce: '<?php echo esc_js( wp_create_nonce( 'seovela_redirects' ) ); ?>'
    };
}
</script>


