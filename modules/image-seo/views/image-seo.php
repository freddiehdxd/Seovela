<?php
/**
 * Image SEO Admin Page - Modern UI
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$image_seo = Seovela_Image_Seo::get_instance();
?>

<div class="seovela-image-seo-page">
	<style>
		.size-info{display:flex;flex-direction:column;gap:3px}
		.size-info .size-label{display:none!important}
		.size-info .size-original,.size-info .size-value.original{font-size:12px;font-weight:500;color:#94a3b8;text-decoration:line-through}
		.size-info .size-webp,.size-info .size-value.webp{font-size:13px;font-weight:600;color:#1e293b}
		.size-info .size-savings,.size-info .savings-badge{display:inline-flex;padding:3px 8px;background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:#fff;border-radius:4px;font-size:11px;font-weight:700;width:fit-content}
		.size-info .size-comparison{display:flex;flex-direction:column;gap:3px}
		.size-info .size-row{display:flex;align-items:baseline}
	</style>
	<!-- Header -->
	<div class="seovela-imgseo-header">
		<div class="seovela-imgseo-header-left">
			<div class="seovela-imgseo-header-icon">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
				</svg>
			</div>
			<div class="seovela-imgseo-header-text">
				<h1><?php _e( 'Image SEO', 'seovela' ); ?> <span class="seovela-imgseo-badge"><?php _e( 'Pro', 'seovela' ); ?></span></h1>
				<p><?php _e( 'Optimize images with smart alt text, titles, WebP conversion & more', 'seovela' ); ?></p>
			</div>
		</div>
		<div class="seovela-imgseo-header-actions">
			<button type="button" class="seovela-imgseo-btn seovela-imgseo-btn-secondary" id="refresh-stats">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
				</svg>
				<?php _e( 'Refresh', 'seovela' ); ?>
			</button>
			<button type="button" class="seovela-imgseo-btn seovela-imgseo-btn-primary" id="scan-all-images">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
				</svg>
				<?php _e( 'Scan All Images', 'seovela' ); ?>
			</button>
		</div>
	</div>

	<div class="seovela-imgseo-container">
		<!-- Stats Cards -->
		<div class="seovela-imgseo-stats-grid">
			<div class="seovela-imgseo-stat-card">
				<div class="seovela-imgseo-stat-icon blue">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
					</svg>
				</div>
				<div class="seovela-imgseo-stat-content">
					<span class="seovela-imgseo-stat-number" id="stat-total"><?php echo number_format( $stats['total_images'] ); ?></span>
					<span class="seovela-imgseo-stat-label"><?php _e( 'Total Images', 'seovela' ); ?></span>
				</div>
			</div>

			<div class="seovela-imgseo-stat-card">
				<div class="seovela-imgseo-stat-icon red">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
					</svg>
				</div>
				<div class="seovela-imgseo-stat-content">
					<span class="seovela-imgseo-stat-number" id="stat-missing-alt"><?php echo number_format( $stats['missing_alt'] ); ?></span>
					<span class="seovela-imgseo-stat-label"><?php _e( 'Missing Alt', 'seovela' ); ?></span>
				</div>
			</div>

			<div class="seovela-imgseo-stat-card">
				<div class="seovela-imgseo-stat-icon amber">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
					</svg>
				</div>
				<div class="seovela-imgseo-stat-content">
					<span class="seovela-imgseo-stat-number" id="stat-oversized"><?php echo number_format( $stats['oversized'] ); ?></span>
					<span class="seovela-imgseo-stat-label"><?php _e( 'Oversized', 'seovela' ); ?></span>
				</div>
			</div>

			<div class="seovela-imgseo-stat-card">
				<div class="seovela-imgseo-stat-icon green">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
					</svg>
				</div>
				<div class="seovela-imgseo-stat-content">
					<span class="seovela-imgseo-stat-number" id="stat-webp"><?php echo number_format( $stats['has_webp'] ); ?></span>
					<span class="seovela-imgseo-stat-label"><?php _e( 'WebP Ready', 'seovela' ); ?></span>
				</div>
			</div>

			<div class="seovela-imgseo-stat-card">
				<div class="seovela-imgseo-stat-icon purple">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5" />
					</svg>
				</div>
				<div class="seovela-imgseo-stat-content">
					<span class="seovela-imgseo-stat-number" id="stat-convertible"><?php echo number_format( $stats['convertible'] ); ?></span>
					<span class="seovela-imgseo-stat-label"><?php _e( 'Can Convert', 'seovela' ); ?></span>
			</div>
		</div>

			<div class="seovela-imgseo-stat-card savings-card">
				<div class="seovela-imgseo-stat-icon cyan">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
					</svg>
				</div>
				<div class="seovela-imgseo-stat-content">
					<span class="seovela-imgseo-stat-number" id="stat-savings"><?php echo size_format( $stats['webp_savings'] ); ?></span>
					<span class="seovela-imgseo-stat-label"><?php _e( 'Space Saved', 'seovela' ); ?></span>
				</div>
			</div>
		</div>

		<!-- Progress Bar (Hidden by default) -->
		<div class="seovela-imgseo-progress-container" id="progress-container" style="display: none;">
			<div class="seovela-imgseo-progress-bar">
				<div class="seovela-imgseo-progress-fill" id="progress-fill" style="width: 0%;"></div>
			</div>
			<p class="seovela-imgseo-progress-text" id="progress-text"><?php _e( 'Scanning...', 'seovela' ); ?></p>
		</div>

		<!-- Navigation Tabs -->
		<div class="seovela-imgseo-tabs">
			<button type="button" class="seovela-imgseo-tab active" data-tab="images"><?php _e( 'Images', 'seovela' ); ?></button>
			<button type="button" class="seovela-imgseo-tab" data-tab="settings"><?php _e( 'Auto-Fill Settings', 'seovela' ); ?></button>
			<button type="button" class="seovela-imgseo-tab" data-tab="webp"><?php _e( 'WebP Conversion', 'seovela' ); ?></button>
			<button type="button" class="seovela-imgseo-tab" data-tab="variables"><?php _e( 'Variables', 'seovela' ); ?></button>
	</div>

		<!-- Tab: Images List -->
		<div class="seovela-imgseo-tab-content active" id="tab-images">
			<div class="seovela-imgseo-section">
				<div class="seovela-imgseo-toolbar">
					<div class="seovela-imgseo-toolbar-left">
						<select id="filter-images" class="seovela-imgseo-select">
							<option value="all"><?php _e( 'All Images', 'seovela' ); ?></option>
							<option value="not_scanned"><?php _e( 'Not Scanned', 'seovela' ); ?></option>
							<option value="scanned"><?php _e( 'Scanned', 'seovela' ); ?></option>
							<option value="issues"><?php _e( 'With Issues', 'seovela' ); ?></option>
							<option value="missing_alt"><?php _e( 'Missing Alt Text', 'seovela' ); ?></option>
							<option value="missing_title"><?php _e( 'Missing Title', 'seovela' ); ?></option>
							<option value="missing_caption"><?php _e( 'Missing Caption', 'seovela' ); ?></option>
							<option value="missing_description"><?php _e( 'Missing Description', 'seovela' ); ?></option>
					<option value="oversized"><?php _e( 'Oversized Files', 'seovela' ); ?></option>
					<option value="poor_filename"><?php _e( 'Poor Filenames', 'seovela' ); ?></option>
							<option value="no_webp"><?php _e( 'No WebP Version', 'seovela' ); ?></option>
							<option value="has_webp"><?php _e( 'Has WebP', 'seovela' ); ?></option>
				</select>
						<div class="seovela-imgseo-search">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
							</svg>
							<input type="text" id="search-images" placeholder="<?php _e( 'Search images...', 'seovela' ); ?>">
						</div>
			</div>
					<div class="seovela-imgseo-toolbar-right">
						<div class="seovela-imgseo-bulk-actions">
							<select id="bulk-action" class="seovela-imgseo-select">
								<option value=""><?php _e( 'Bulk Actions', 'seovela' ); ?></option>
								<option value="apply_alt"><?php _e( 'Apply Alt Template', 'seovela' ); ?></option>
								<option value="apply_title"><?php _e( 'Apply Title Template', 'seovela' ); ?></option>
								<option value="apply_caption"><?php _e( 'Apply Caption Template', 'seovela' ); ?></option>
								<option value="apply_description"><?php _e( 'Apply Description Template', 'seovela' ); ?></option>
								<option value="apply_all"><?php _e( 'Apply All Templates', 'seovela' ); ?></option>
								<option value="convert_webp"><?php _e( 'Convert to WebP', 'seovela' ); ?></option>
							</select>
							<button type="button" class="seovela-imgseo-btn seovela-imgseo-btn-secondary" id="apply-bulk-action">
								<?php _e( 'Apply', 'seovela' ); ?>
				</button>
			</div>
		</div>
				</div>

				<div class="seovela-imgseo-table-container">
					<table class="seovela-imgseo-table" id="images-table">
					<thead>
						<tr>
							<th class="check-column"><input type="checkbox" id="select-all-images"></th>
								<th class="image-column"><?php _e( 'Image', 'seovela' ); ?></th>
								<th class="filename-column"><?php _e( 'File Name', 'seovela' ); ?></th>
								<th class="alt-column"><?php _e( 'Alt Text', 'seovela' ); ?></th>
								<th class="title-column"><?php _e( 'Title', 'seovela' ); ?></th>
								<th class="status-column"><?php _e( 'Status', 'seovela' ); ?></th>
								<th class="size-column"><?php _e( 'Size', 'seovela' ); ?></th>
								<th class="actions-column"><?php _e( 'Actions', 'seovela' ); ?></th>
						</tr>
					</thead>
						<tbody id="images-tbody">
							<tr class="loading-row">
								<td colspan="8">
									<div class="seovela-imgseo-loading">
										<div class="spinner"></div>
										<span><?php _e( 'Loading images...', 'seovela' ); ?></span>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<div class="seovela-imgseo-pagination" id="pagination">
					<span class="pagination-info" id="pagination-info"></span>
					<div class="pagination-buttons">
						<button type="button" class="seovela-imgseo-btn seovela-imgseo-btn-secondary" id="prev-page" disabled><?php _e( 'Previous', 'seovela' ); ?></button>
						<button type="button" class="seovela-imgseo-btn seovela-imgseo-btn-secondary" id="next-page"><?php _e( 'Next', 'seovela' ); ?></button>
					</div>
				</div>
			</div>
		</div>

		<!-- Tab: Auto-Fill Settings -->
		<div class="seovela-imgseo-tab-content" id="tab-settings">
			<form id="settings-form">
				<!-- General Settings -->
				<div class="seovela-imgseo-section">
					<div class="seovela-imgseo-section-header">
						<div class="seovela-imgseo-section-title">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
								<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
							</svg>
							<h2><?php _e( 'General Settings', 'seovela' ); ?></h2>
						</div>
					</div>

					<div class="seovela-imgseo-grid two-col">
						<div class="seovela-imgseo-field">
							<label class="seovela-imgseo-toggle">
								<input type="checkbox" name="enabled" <?php checked( $settings['enabled'] ); ?>>
								<span class="toggle-slider"></span>
								<span class="toggle-label"><?php _e( 'Enable Image SEO Module', 'seovela' ); ?></span>
							</label>
						</div>
						<div class="seovela-imgseo-field">
							<label class="seovela-imgseo-toggle">
								<input type="checkbox" name="auto_process_upload" <?php checked( $settings['auto_process_upload'] ); ?>>
								<span class="toggle-slider"></span>
								<span class="toggle-label"><?php _e( 'Auto-process on Upload', 'seovela' ); ?></span>
							</label>
						</div>
					</div>

					<div class="seovela-imgseo-grid two-col">
						<div class="seovela-imgseo-field">
							<label><?php _e( 'File Size Threshold (KB)', 'seovela' ); ?></label>
							<input type="number" name="size_threshold" value="<?php echo esc_attr( $settings['size_threshold'] ); ?>" min="50" max="5000" class="seovela-imgseo-input">
							<p class="field-hint"><?php _e( 'Images larger than this will be flagged as oversized', 'seovela' ); ?></p>
						</div>
						<div class="seovela-imgseo-field">
							<label><?php _e( 'Separator Character', 'seovela' ); ?></label>
							<input type="text" name="separator" value="<?php echo esc_attr( $settings['separator'] ); ?>" class="seovela-imgseo-input">
							<p class="field-hint"><?php _e( 'Used in the %separator% variable', 'seovela' ); ?></p>
						</div>
					</div>
				</div>

				<!-- Alt Text Settings -->
				<div class="seovela-imgseo-section">
					<div class="seovela-imgseo-section-header">
						<div class="seovela-imgseo-section-title">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
							</svg>
							<h2><?php _e( 'Alt Text Settings', 'seovela' ); ?></h2>
						</div>
						<span class="seovela-imgseo-badge-small green"><?php _e( 'SEO Critical', 'seovela' ); ?></span>
					</div>

					<div class="seovela-imgseo-grid two-col">
						<div class="seovela-imgseo-field">
							<label><?php _e( 'Alt Text Template', 'seovela' ); ?></label>
							<div class="seovela-imgseo-input-with-vars">
								<input type="text" name="alt_template" value="<?php echo esc_attr( $settings['alt_template'] ); ?>" class="seovela-imgseo-input" placeholder="%file_name_raw%">
								<button type="button" class="insert-variable-btn" data-target="alt_template">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />
									</svg>
								</button>
							</div>
							<p class="field-hint"><?php _e( 'Use variables like %file_name_raw%, %post_title%, %site_title%', 'seovela' ); ?></p>
						</div>
						<div class="seovela-imgseo-field">
							<label><?php _e( 'Alt Text Casing', 'seovela' ); ?></label>
							<select name="alt_casing" class="seovela-imgseo-select">
								<?php foreach ( $image_seo->casing_options as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['alt_casing'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

					<div class="seovela-imgseo-grid two-col">
						<div class="seovela-imgseo-field">
							<label class="seovela-imgseo-toggle">
								<input type="checkbox" name="add_missing_alt" <?php checked( $settings['add_missing_alt'] ); ?>>
								<span class="toggle-slider"></span>
								<span class="toggle-label"><?php _e( 'Add missing alt text on upload', 'seovela' ); ?></span>
							</label>
						</div>
						<div class="seovela-imgseo-field">
							<label class="seovela-imgseo-toggle">
								<input type="checkbox" name="overwrite_alt" <?php checked( $settings['overwrite_alt'] ); ?>>
								<span class="toggle-slider"></span>
								<span class="toggle-label"><?php _e( 'Overwrite existing alt text', 'seovela' ); ?></span>
							</label>
						</div>
					</div>
				</div>

				<!-- Title Settings -->
				<div class="seovela-imgseo-section">
					<div class="seovela-imgseo-section-header">
						<div class="seovela-imgseo-section-title">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" />
							</svg>
							<h2><?php _e( 'Title Settings', 'seovela' ); ?></h2>
						</div>
					</div>

					<div class="seovela-imgseo-grid two-col">
						<div class="seovela-imgseo-field">
							<label><?php _e( 'Title Template', 'seovela' ); ?></label>
							<div class="seovela-imgseo-input-with-vars">
								<input type="text" name="title_template" value="<?php echo esc_attr( $settings['title_template'] ); ?>" class="seovela-imgseo-input" placeholder="%file_name_raw%">
								<button type="button" class="insert-variable-btn" data-target="title_template">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />
									</svg>
								</button>
							</div>
						</div>
						<div class="seovela-imgseo-field">
							<label><?php _e( 'Title Casing', 'seovela' ); ?></label>
							<select name="title_casing" class="seovela-imgseo-select">
								<?php foreach ( $image_seo->casing_options as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['title_casing'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

					<div class="seovela-imgseo-grid two-col">
						<div class="seovela-imgseo-field">
							<label class="seovela-imgseo-toggle">
								<input type="checkbox" name="add_missing_title" <?php checked( $settings['add_missing_title'] ); ?>>
								<span class="toggle-slider"></span>
								<span class="toggle-label"><?php _e( 'Add missing title on upload', 'seovela' ); ?></span>
							</label>
						</div>
						<div class="seovela-imgseo-field">
							<label class="seovela-imgseo-toggle">
								<input type="checkbox" name="overwrite_title" <?php checked( $settings['overwrite_title'] ); ?>>
								<span class="toggle-slider"></span>
								<span class="toggle-label"><?php _e( 'Overwrite existing title', 'seovela' ); ?></span>
							</label>
						</div>
					</div>
				</div>

				<!-- Caption Settings -->
				<div class="seovela-imgseo-section">
					<div class="seovela-imgseo-section-header">
						<div class="seovela-imgseo-section-title">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12H12m-8.25 5.25h16.5" />
							</svg>
							<h2><?php _e( 'Caption Settings', 'seovela' ); ?></h2>
						</div>
					</div>

					<div class="seovela-imgseo-grid two-col">
						<div class="seovela-imgseo-field">
							<label><?php _e( 'Caption Template', 'seovela' ); ?></label>
							<div class="seovela-imgseo-input-with-vars">
								<input type="text" name="caption_template" value="<?php echo esc_attr( $settings['caption_template'] ); ?>" class="seovela-imgseo-input" placeholder="<?php _e( 'Leave empty to skip', 'seovela' ); ?>">
								<button type="button" class="insert-variable-btn" data-target="caption_template">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />
									</svg>
								</button>
							</div>
						</div>
						<div class="seovela-imgseo-field">
							<label><?php _e( 'Caption Casing', 'seovela' ); ?></label>
							<select name="caption_casing" class="seovela-imgseo-select">
								<?php foreach ( $image_seo->casing_options as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['caption_casing'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

					<div class="seovela-imgseo-grid two-col">
						<div class="seovela-imgseo-field">
							<label class="seovela-imgseo-toggle">
								<input type="checkbox" name="add_missing_caption" <?php checked( $settings['add_missing_caption'] ); ?>>
								<span class="toggle-slider"></span>
								<span class="toggle-label"><?php _e( 'Add missing caption on upload', 'seovela' ); ?></span>
							</label>
						</div>
						<div class="seovela-imgseo-field">
							<label class="seovela-imgseo-toggle">
								<input type="checkbox" name="overwrite_caption" <?php checked( $settings['overwrite_caption'] ); ?>>
								<span class="toggle-slider"></span>
								<span class="toggle-label"><?php _e( 'Overwrite existing caption', 'seovela' ); ?></span>
							</label>
						</div>
					</div>
				</div>

				<!-- Description Settings -->
				<div class="seovela-imgseo-section">
					<div class="seovela-imgseo-section-header">
						<div class="seovela-imgseo-section-title">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
							</svg>
							<h2><?php _e( 'Description Settings', 'seovela' ); ?></h2>
						</div>
					</div>

					<div class="seovela-imgseo-field">
						<label><?php _e( 'Description Template', 'seovela' ); ?></label>
						<div class="seovela-imgseo-input-with-vars">
							<textarea name="description_template" class="seovela-imgseo-textarea" rows="3" placeholder="<?php _e( 'Leave empty to skip', 'seovela' ); ?>"><?php echo esc_textarea( $settings['description_template'] ); ?></textarea>
							<button type="button" class="insert-variable-btn textarea-btn" data-target="description_template">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />
								</svg>
							</button>
						</div>
					</div>

					<div class="seovela-imgseo-grid three-col">
						<div class="seovela-imgseo-field">
							<label><?php _e( 'Description Casing', 'seovela' ); ?></label>
							<select name="description_casing" class="seovela-imgseo-select">
								<?php foreach ( $image_seo->casing_options as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['description_casing'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="seovela-imgseo-field">
							<label class="seovela-imgseo-toggle">
								<input type="checkbox" name="add_missing_description" <?php checked( $settings['add_missing_description'] ); ?>>
								<span class="toggle-slider"></span>
								<span class="toggle-label"><?php _e( 'Add missing', 'seovela' ); ?></span>
							</label>
						</div>
						<div class="seovela-imgseo-field">
							<label class="seovela-imgseo-toggle">
								<input type="checkbox" name="overwrite_description" <?php checked( $settings['overwrite_description'] ); ?>>
								<span class="toggle-slider"></span>
								<span class="toggle-label"><?php _e( 'Overwrite existing', 'seovela' ); ?></span>
							</label>
						</div>
					</div>
				</div>

				<div class="seovela-imgseo-form-actions">
					<button type="submit" class="seovela-imgseo-btn seovela-imgseo-btn-primary" id="save-settings">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
						</svg>
						<?php _e( 'Save Settings', 'seovela' ); ?>
					</button>
				</div>
			</form>
		</div>

		<!-- Tab: WebP Conversion -->
		<div class="seovela-imgseo-tab-content" id="tab-webp">
			<div class="seovela-imgseo-section">
				<div class="seovela-imgseo-section-header">
					<div class="seovela-imgseo-section-title">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5" />
						</svg>
						<h2><?php _e( 'WebP Conversion', 'seovela' ); ?></h2>
					</div>
				</div>

				<!-- Library Status -->
				<div class="seovela-imgseo-library-status">
					<div class="library-card <?php echo $libraries['imagick']['available'] && $libraries['imagick']['webp_support'] ? 'available' : 'unavailable'; ?>">
						<div class="library-icon">
							<?php if ( $libraries['imagick']['available'] && $libraries['imagick']['webp_support'] ) : ?>
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon-success">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
								</svg>
							<?php else : ?>
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon-error">
									<path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
								</svg>
							<?php endif; ?>
						</div>
						<div class="library-info">
							<h4>Imagick</h4>
							<?php if ( $libraries['imagick']['available'] ) : ?>
								<p><?php echo esc_html( $libraries['imagick']['version'] ); ?></p>
								<span class="library-badge <?php echo $libraries['imagick']['webp_support'] ? 'success' : 'warning'; ?>">
									<?php echo $libraries['imagick']['webp_support'] ? __( 'WebP Supported', 'seovela' ) : __( 'No WebP Support', 'seovela' ); ?>
								</span>
							<?php else : ?>
								<p><?php _e( 'Not installed', 'seovela' ); ?></p>
								<span class="library-badge error"><?php _e( 'Unavailable', 'seovela' ); ?></span>
							<?php endif; ?>
						</div>
					</div>

					<div class="library-card <?php echo $libraries['gd']['available'] && $libraries['gd']['webp_support'] ? 'available' : 'unavailable'; ?>">
						<div class="library-icon">
							<?php if ( $libraries['gd']['available'] && $libraries['gd']['webp_support'] ) : ?>
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon-success">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
								</svg>
							<?php else : ?>
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon-error">
									<path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
								</svg>
							<?php endif; ?>
						</div>
						<div class="library-info">
							<h4>GD Library</h4>
							<?php if ( $libraries['gd']['available'] ) : ?>
								<p><?php echo esc_html( $libraries['gd']['version'] ); ?></p>
								<span class="library-badge <?php echo $libraries['gd']['webp_support'] ? 'success' : 'warning'; ?>">
									<?php echo $libraries['gd']['webp_support'] ? __( 'WebP Supported', 'seovela' ) : __( 'No WebP Support', 'seovela' ); ?>
								</span>
							<?php else : ?>
								<p><?php _e( 'Not installed', 'seovela' ); ?></p>
								<span class="library-badge error"><?php _e( 'Unavailable', 'seovela' ); ?></span>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<!-- WebP Settings Form -->
				<form id="webp-settings-form">
					<div class="seovela-imgseo-grid two-col">
						<div class="seovela-imgseo-field">
							<label class="seovela-imgseo-toggle">
								<input type="checkbox" name="enable_webp_conversion" <?php checked( $settings['enable_webp_conversion'] ); ?>>
								<span class="toggle-slider"></span>
								<span class="toggle-label"><?php _e( 'Enable WebP Conversion', 'seovela' ); ?></span>
							</label>
						</div>
						<div class="seovela-imgseo-field">
							<label class="seovela-imgseo-toggle">
								<input type="checkbox" name="convert_on_upload" <?php checked( $settings['convert_on_upload'] ); ?>>
								<span class="toggle-slider"></span>
								<span class="toggle-label"><?php _e( 'Auto-convert on Upload', 'seovela' ); ?></span>
							</label>
						</div>
					</div>

					<div class="seovela-imgseo-grid two-col">
						<div class="seovela-imgseo-field">
							<label><?php _e( 'Preferred Library', 'seovela' ); ?></label>
							<select name="webp_library" class="seovela-imgseo-select">
								<option value="auto" <?php selected( $settings['webp_library'], 'auto' ); ?>><?php _e( 'Auto (Imagick > GD)', 'seovela' ); ?></option>
								<option value="imagick" <?php selected( $settings['webp_library'], 'imagick' ); ?> <?php disabled( ! $libraries['imagick']['webp_support'] ); ?>>Imagick</option>
								<option value="gd" <?php selected( $settings['webp_library'], 'gd' ); ?> <?php disabled( ! $libraries['gd']['webp_support'] ); ?>>GD Library</option>
							</select>
						</div>
						<div class="seovela-imgseo-field">
							<label><?php _e( 'WebP Quality', 'seovela' ); ?></label>
							<div class="seovela-imgseo-slider-container">
								<input type="range" name="webp_quality" min="50" max="100" value="<?php echo esc_attr( $settings['webp_quality'] ); ?>" class="seovela-imgseo-slider" id="webp-quality-slider">
								<span class="slider-value" id="webp-quality-value"><?php echo esc_html( $settings['webp_quality'] ); ?>%</span>
							</div>
						</div>
					</div>

					<!-- Replace Original Option -->
					<div class="seovela-imgseo-field" style="margin-top: 20px;">
						<label class="seovela-imgseo-toggle">
							<input type="checkbox" name="replace_original" <?php checked( $settings['replace_original'] ?? false ); ?>>
							<span class="toggle-slider"></span>
							<span class="toggle-label"><?php _e( 'Replace original with WebP (delete original)', 'seovela' ); ?></span>
						</label>
						<p class="field-description"><?php _e( 'When enabled, the original image will be deleted and replaced with the WebP version. This saves disk space but removes the original file permanently.', 'seovela' ); ?></p>
					</div>

					<div class="seovela-imgseo-warning-box" id="replace-original-warning" style="display: <?php echo ! empty( $settings['replace_original'] ) ? 'flex' : 'none'; ?>;">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
						</svg>
						<div>
							<strong><?php _e( 'Warning:', 'seovela' ); ?></strong>
							<p><?php _e( 'This action is irreversible! Original files will be permanently deleted. Make sure you have backups before enabling this option.', 'seovela' ); ?></p>
						</div>
					</div>

					<script>
						jQuery(document).ready(function($) {
							$('input[name="replace_original"]').on('change', function() {
								$('#replace-original-warning').toggle($(this).is(':checked'));
							});
						});
					</script>

					</form>
			</div>

			<!-- WebP Serving Section -->
			<div class="seovela-imgseo-section">
				<div class="seovela-imgseo-section-header">
					<div class="seovela-imgseo-section-title">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
						</svg>
						<h2><?php _e( 'WebP Serving', 'seovela' ); ?></h2>
					</div>
					<p class="seovela-imgseo-section-desc"><?php _e( 'Automatically serve WebP images to browsers that support them.', 'seovela' ); ?></p>
				</div>

				<form id="webp-serving-form">
					<div class="seovela-imgseo-field">
						<label class="seovela-imgseo-toggle">
							<input type="checkbox" name="serve_webp" <?php checked( $settings['serve_webp'] ?? false ); ?>>
							<span class="toggle-slider"></span>
							<span class="toggle-label"><?php _e( 'Enable WebP Serving', 'seovela' ); ?></span>
						</label>
						<p class="field-description"><?php _e( 'When enabled, WebP versions will be served to browsers that support them.', 'seovela' ); ?></p>
					</div>

					<div class="seovela-imgseo-field">
						<label><?php _e( 'Serving Method', 'seovela' ); ?></label>
						<div class="seovela-imgseo-radio-group">
							<label class="seovela-imgseo-radio">
								<input type="radio" name="webp_serving_method" value="php" <?php checked( ( $settings['webp_serving_method'] ?? 'php' ), 'php' ); ?>>
								<span class="radio-custom"></span>
								<span class="radio-label">
									<strong><?php _e( 'PHP Method', 'seovela' ); ?></strong>
									<span class="radio-desc"><?php _e( 'Filter image URLs through WordPress. Works on all servers.', 'seovela' ); ?></span>
								</span>
							</label>
							<label class="seovela-imgseo-radio">
								<input type="radio" name="webp_serving_method" value="htaccess" <?php checked( ( $settings['webp_serving_method'] ?? 'php' ), 'htaccess' ); ?>>
								<span class="radio-custom"></span>
								<span class="radio-label">
									<strong><?php _e( '.htaccess Rewrite', 'seovela' ); ?></strong>
									<span class="radio-desc"><?php _e( 'Server-level rewrite (Apache only). Faster but requires mod_rewrite.', 'seovela' ); ?></span>
								</span>
							</label>
						</div>
					</div>

					<div class="seovela-imgseo-info-box">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
						</svg>
						<div>
							<strong><?php _e( 'How it works:', 'seovela' ); ?></strong>
							<ul>
								<li><?php _e( 'WebP files are stored alongside original images (e.g., image.jpg → image.webp)', 'seovela' ); ?></li>
								<li><?php _e( 'Browser support is auto-detected via Accept header', 'seovela' ); ?></li>
								<li><?php _e( 'Original files are preserved as fallback for older browsers', 'seovela' ); ?></li>
							</ul>
						</div>
					</div>
				</form>
			</div>

			<!-- Conversion Actions Section -->
			<div class="seovela-imgseo-section">
				<div class="seovela-imgseo-section-header">
					<div class="seovela-imgseo-section-title">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
						</svg>
						<h2><?php _e( 'Bulk Convert', 'seovela' ); ?></h2>
					</div>
				</div>

				<!-- Conversion Actions -->
				<div class="seovela-imgseo-conversion-actions">
					<div class="conversion-stats">
						<div class="conversion-stat">
							<span class="stat-number" id="webp-convertible"><?php echo number_format( $stats['convertible'] ); ?></span>
							<span class="stat-label"><?php _e( 'Images can be converted', 'seovela' ); ?></span>
						</div>
						<div class="conversion-stat">
							<span class="stat-number" id="webp-converted"><?php echo number_format( $stats['has_webp'] ); ?></span>
							<span class="stat-label"><?php _e( 'Already have WebP', 'seovela' ); ?></span>
						</div>
						<div class="conversion-stat savings">
							<span class="stat-number" id="webp-total-savings"><?php echo size_format( $stats['webp_savings'] ); ?></span>
							<span class="stat-label"><?php _e( 'Total space saved', 'seovela' ); ?></span>
						</div>
					</div>

					<button type="button" class="seovela-imgseo-btn seovela-imgseo-btn-primary seovela-imgseo-btn-large" id="convert-all-webp" <?php disabled( $stats['convertible'] === 0 ); ?>>
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
						</svg>
						<?php _e( 'Convert All to WebP', 'seovela' ); ?>
					</button>
				</div>
			</div>
		</div>

		<!-- Tab: Variables Reference -->
		<div class="seovela-imgseo-tab-content" id="tab-variables">
			<div class="seovela-imgseo-section">
				<div class="seovela-imgseo-section-header">
					<div class="seovela-imgseo-section-title">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />
						</svg>
						<h2><?php _e( 'Available Variables', 'seovela' ); ?></h2>
					</div>
				</div>

				<p class="seovela-imgseo-section-description">
					<?php _e( 'Use these variables in your templates to dynamically generate alt text, titles, captions, and descriptions. Click on any variable to copy it to your clipboard.', 'seovela' ); ?>
				</p>

				<div class="seovela-imgseo-variables-grid">
					<?php
					$categories = array(
						'image'   => __( 'Image Variables', 'seovela' ),
						'post'    => __( 'Post Variables', 'seovela' ),
						'site'    => __( 'Site Variables', 'seovela' ),
						'date'    => __( 'Date Variables', 'seovela' ),
						'utility' => __( 'Utility Variables', 'seovela' ),
					);

					foreach ( $categories as $cat_key => $cat_label ) :
						$cat_variables = array_filter( $image_seo->variables, function( $var ) use ( $cat_key ) {
							return $var['category'] === $cat_key;
						});
						if ( empty( $cat_variables ) ) continue;
					?>
						<div class="variables-category">
							<h3><?php echo esc_html( $cat_label ); ?></h3>
							<div class="variables-list">
								<?php foreach ( $cat_variables as $var_key => $var_data ) : ?>
									<div class="variable-item" data-variable="%<?php echo esc_attr( $var_key ); ?>%">
										<code class="variable-code">%<?php echo esc_html( $var_key ); ?>%</code>
										<div class="variable-info">
											<span class="variable-label"><?php echo esc_html( $var_data['label'] ); ?></span>
											<span class="variable-description"><?php echo esc_html( $var_data['description'] ); ?></span>
										</div>
										<button type="button" class="copy-variable-btn" title="<?php _e( 'Copy to clipboard', 'seovela' ); ?>">
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
												<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" />
											</svg>
										</button>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<!-- Example Templates -->
				<div class="seovela-imgseo-examples">
					<h3><?php _e( 'Example Templates', 'seovela' ); ?></h3>
					<div class="examples-grid">
						<div class="example-card">
							<h4><?php _e( 'Basic Alt Text', 'seovela' ); ?></h4>
							<code>%file_name_raw%</code>
							<p><?php _e( 'Simply uses the filename, cleaned up', 'seovela' ); ?></p>
						</div>
						<div class="example-card">
							<h4><?php _e( 'Context-Aware Alt', 'seovela' ); ?></h4>
							<code>%file_name_raw% %separator% %post_title%</code>
							<p><?php _e( 'Combines image name with parent post title', 'seovela' ); ?></p>
						</div>
						<div class="example-card">
							<h4><?php _e( 'Brand Focused', 'seovela' ); ?></h4>
							<code>%file_name_raw% | %site_title%</code>
							<p><?php _e( 'Adds your site name for brand visibility', 'seovela' ); ?></p>
						</div>
						<div class="example-card">
							<h4><?php _e( 'Numbered Series', 'seovela' ); ?></h4>
							<code>%post_title% Image %counter%</code>
							<p><?php _e( 'Perfect for galleries with sequential numbering', 'seovela' ); ?></p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Variable Picker Modal -->
	<div class="seovela-imgseo-modal" id="variable-picker-modal" style="display: none;">
		<div class="modal-overlay"></div>
		<div class="modal-content">
			<div class="modal-header">
				<h3><?php _e( 'Insert Variable', 'seovela' ); ?></h3>
				<button type="button" class="modal-close">&times;</button>
			</div>
			<div class="modal-body">
				<div class="modal-search">
					<input type="text" id="variable-search" placeholder="<?php _e( 'Search variables...', 'seovela' ); ?>">
				</div>
				<div class="modal-variables-list" id="modal-variables-list">
					<?php foreach ( $image_seo->variables as $var_key => $var_data ) : ?>
						<div class="modal-variable-item" data-variable="%<?php echo esc_attr( $var_key ); ?>%">
							<code>%<?php echo esc_html( $var_key ); ?>%</code>
							<span><?php echo esc_html( $var_data['label'] ); ?></span>
						</div>
						<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>

	<!-- Edit Image Modal -->
	<div class="seovela-imgseo-modal" id="edit-image-modal" style="display: none;">
		<div class="modal-overlay"></div>
		<div class="modal-content modal-large">
			<div class="modal-header">
				<h3><?php _e( 'Edit Image Attributes', 'seovela' ); ?></h3>
				<button type="button" class="modal-close">&times;</button>
			</div>
			<div class="modal-body">
				<div class="edit-image-preview">
					<img id="edit-image-preview-img" src="" alt="">
					<div class="edit-image-info">
						<span id="edit-image-filename"></span>
						<span id="edit-image-dimensions"></span>
					</div>
				</div>
				<form id="edit-image-form">
					<input type="hidden" id="edit-image-id" name="attachment_id" value="">
					
					<div class="seovela-imgseo-field">
						<label><?php _e( 'Alt Text', 'seovela' ); ?></label>
						<div class="input-with-action">
							<input type="text" name="alt" id="edit-image-alt" class="seovela-imgseo-input">
							<button type="button" class="apply-template-btn" data-attribute="alt" title="<?php _e( 'Apply template', 'seovela' ); ?>">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
								</svg>
							</button>
						</div>
					</div>

					<div class="seovela-imgseo-field">
						<label><?php _e( 'Title', 'seovela' ); ?></label>
						<div class="input-with-action">
							<input type="text" name="title" id="edit-image-title" class="seovela-imgseo-input">
							<button type="button" class="apply-template-btn" data-attribute="title" title="<?php _e( 'Apply template', 'seovela' ); ?>">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
								</svg>
							</button>
						</div>
					</div>

					<div class="seovela-imgseo-field">
						<label><?php _e( 'Caption', 'seovela' ); ?></label>
						<div class="input-with-action">
							<input type="text" name="caption" id="edit-image-caption" class="seovela-imgseo-input">
							<button type="button" class="apply-template-btn" data-attribute="caption" title="<?php _e( 'Apply template', 'seovela' ); ?>">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
								</svg>
							</button>
						</div>
					</div>

					<div class="seovela-imgseo-field">
						<label><?php _e( 'Description', 'seovela' ); ?></label>
						<textarea name="description" id="edit-image-description" class="seovela-imgseo-textarea" rows="3"></textarea>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="seovela-imgseo-btn seovela-imgseo-btn-secondary modal-close-btn"><?php _e( 'Cancel', 'seovela' ); ?></button>
				<button type="button" class="seovela-imgseo-btn seovela-imgseo-btn-primary" id="save-image-edit"><?php _e( 'Save Changes', 'seovela' ); ?></button>
			</div>
		</div>
	</div>

	<!-- Toast Notifications -->
	<div class="seovela-imgseo-toast-container" id="toast-container"></div>
</div>
