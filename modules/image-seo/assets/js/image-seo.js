/**
 * Seovela Image SEO - Advanced JavaScript
 * Handles all UI interactions, AJAX operations, and dynamic updates
 *
 * @package Seovela
 */

(function($) {
	'use strict';

	const ImageSeo = {
		// State
		scanning: false,
		converting: false,
		currentPage: 0,
		perPage: 50,
		totalImages: 0,
		selectedImages: [],
		currentFilter: 'all',
		searchQuery: '',
		currentVariableTarget: null,

		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
			this.loadImages();
			this.initSliders();
		},

		/**
		 * Bind all events
		 */
		bindEvents: function() {
			// Tabs
			$('.seovela-imgseo-tab').on('click', this.handleTabClick.bind(this));

			// Scan
			$('#scan-all-images').on('click', this.scanAllImages.bind(this));
			$('#refresh-stats').on('click', this.refreshStats.bind(this));

			// Filter and Search
			$('#filter-images').on('change', this.handleFilterChange.bind(this));
			$('#search-images').on('input', this.debounce(this.handleSearch.bind(this), 300));

			// Pagination
			$('#prev-page').on('click', () => this.changePage(-1));
			$('#next-page').on('click', () => this.changePage(1));

			// Select all
			$('#select-all-images').on('change', this.toggleSelectAll.bind(this));
			$(document).on('change', '.image-checkbox', this.handleCheckboxChange.bind(this));

			// Bulk actions
			$('#apply-bulk-action').on('click', this.applyBulkAction.bind(this));

			// WebP conversion
			$('#convert-all-webp').on('click', this.convertAllWebP.bind(this));

			// Settings forms
			$('#settings-form').on('submit', this.saveSettings.bind(this));
			$('#webp-settings-form').on('change', 'input, select', this.saveSettings.bind(this));
			$('#webp-serving-form').on('change', 'input, select', this.saveSettings.bind(this));

			// Edit image
			$(document).on('click', '.edit-image-btn', this.openEditModal.bind(this));
			$('#save-image-edit').on('click', this.saveImageEdit.bind(this));
			$(document).on('click', '.apply-template-btn', this.applyTemplate.bind(this));

			// Modals
			$('.modal-close, .modal-close-btn, .modal-overlay').on('click', this.closeModal);
			
			// Variable picker
			$('.insert-variable-btn').on('click', this.openVariablePicker.bind(this));
			$(document).on('click', '.modal-variable-item', this.insertVariable.bind(this));
			$('#variable-search').on('input', this.filterVariables.bind(this));

			// Copy variable
			$(document).on('click', '.copy-variable-btn', this.copyVariable.bind(this));
			$(document).on('click', '.variable-item', this.copyVariable.bind(this));

			// Single image actions
			$(document).on('click', '.convert-webp-btn', this.convertSingleWebP.bind(this));

			// Keyboard shortcuts
			$(document).on('keydown', this.handleKeydown.bind(this));
		},

		/**
		 * Handle tab click
		 */
		handleTabClick: function(e) {
			const $tab = $(e.currentTarget);
			const tabId = $tab.data('tab');

			$('.seovela-imgseo-tab').removeClass('active');
			$tab.addClass('active');

			$('.seovela-imgseo-tab-content').removeClass('active');
			$('#tab-' + tabId).addClass('active');
		},

		/**
		 * Initialize sliders
		 */
		initSliders: function() {
			$('#webp-quality-slider').on('input', function() {
				$('#webp-quality-value').text($(this).val() + '%');
			});
		},

		/**
		 * Load images list
		 */
		loadImages: function() {
			const $tbody = $('#images-tbody');
			
			$tbody.html(`
				<tr class="loading-row">
					<td colspan="8">
						<div class="seovela-imgseo-loading">
							<div class="spinner"></div>
							<span>${seovelaImageSeo.strings.scanning || 'Loading images...'}</span>
						</div>
					</td>
				</tr>
			`);

			$.ajax({
				url: seovelaImageSeo.ajaxUrl,
				type: 'POST',
				data: {
					action: 'seovela_image_get_list',
					nonce: seovelaImageSeo.nonce,
					filter: this.currentFilter,
					search: this.searchQuery,
					limit: this.perPage,
					offset: this.currentPage * this.perPage
				},
				success: (response) => {
					if (response.success) {
						this.totalImages = response.data.total;
						this.renderImages(response.data.images);
						this.updatePagination();
					} else {
						this.showToast('Error loading images', 'error');
					}
				},
				error: () => {
					this.showToast('Failed to load images', 'error');
				}
			});
		},

		/**
		 * Render images table
		 */
		renderImages: function(images) {
			const $tbody = $('#images-tbody');

			if (!images || !Array.isArray(images) || images.length === 0) {
				$tbody.html(`
					<tr>
						<td colspan="8" style="text-align: center; padding: 60px 20px; color: #64748b;">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 48px; height: 48px; margin-bottom: 16px; opacity: 0.5;">
								<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
							</svg>
							<p style="font-size: 16px; font-weight: 600; margin: 0 0 8px;">No images found</p>
							<p style="font-size: 14px; margin: 0;">Click "Scan All Images" to analyze your media library</p>
						</td>
					</tr>
				`);
				return;
			}

			let html = '';
			try {
			images.forEach((image) => {
				const statusBadges = this.getStatusBadges(image);
				const altPreview = image.current_alt 
					? `<span class="text-preview">${this.escapeHtml(image.current_alt)}</span>`
					: '<span class="text-empty">Not set</span>';
				const titlePreview = image.current_title 
					? `<span class="text-preview">${this.escapeHtml(image.current_title)}</span>`
					: '<span class="text-empty">Not set</span>';

				html += `
					<tr data-attachment-id="${image.attachment_id}">
						<td class="check-column">
							<input type="checkbox" class="image-checkbox" value="${image.attachment_id}">
						</td>
						<td class="image-column">
							<img src="${image.thumbnail_url || ''}" alt="" loading="lazy">
						</td>
						<td class="filename-column">
							<span class="filename">${this.escapeHtml(image.file_name)}</span>
							<span class="dimensions">${image.width}×${image.height}px</span>
						</td>
						<td class="alt-column">${altPreview}</td>
						<td class="title-column">${titlePreview}</td>
						<td class="status-column">
							<div class="status-badges">${statusBadges}</div>
						</td>
						<td class="size-column">${this.getSizeDisplay(image)}</td>
						<td class="actions-column">
							<div class="action-buttons">
								<button type="button" class="action-btn edit-image-btn" title="Edit" data-id="${image.attachment_id}">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
									</svg>
								</button>
								${parseInt(image.has_webp) !== 1 && this.canConvert(image.file_type) ? `
								<button type="button" class="action-btn convert-webp-btn" title="Convert to WebP" data-id="${image.attachment_id}">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
									</svg>
								</button>
								` : ''}
								<a href="${image.edit_url}" class="action-btn" title="Edit in Media Library" target="_blank">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
									</svg>
								</a>
							</div>
						</td>
					</tr>
				`;
			});
			} catch (e) {
				console.error('Error rendering images:', e);
			}

			$tbody.html(html);
		},

		/**
		 * Get status badges for image
		 */
		getStatusBadges: function(image) {
			let badges = [];

			// Check if not scanned
			if (!image.is_scanned) {
				badges.push('<span class="status-badge info">Not Scanned</span>');
			}

			if (!image.has_alt && image.has_alt !== null) {
				badges.push('<span class="status-badge error">No Alt</span>');
			}
			if (!image.has_title && image.has_title !== null) {
				badges.push('<span class="status-badge warning">No Title</span>');
			}
			if (parseInt(image.is_oversized) === 1) {
				badges.push('<span class="status-badge warning">Large</span>');
			}
			if (parseInt(image.has_webp) === 1) {
				badges.push('<span class="status-badge success">WebP</span>');
			}
			if (!image.is_descriptive && image.is_scanned) {
				badges.push('<span class="status-badge info">Poor Name</span>');
			}

			if (badges.length === 0 && image.is_scanned) {
				badges.push('<span class="status-badge success">OK</span>');
			}

			return badges.join('');
		},

		/**
		 * Check if file type can be converted to WebP
		 */
		canConvert: function(fileType) {
			return ['image/jpeg', 'image/png', 'image/gif'].includes(fileType);
		},

		/**
		 * Get size display with WebP info
		 */
		getSizeDisplay: function(image) {
			const originalSize = this.formatSize(image.file_size);
			const hasWebp = parseInt(image.has_webp) === 1;
			const webpSize = parseInt(image.webp_size) || 0;
			
			if (hasWebp && webpSize > 0) {
				const webpSizeFormatted = this.formatSize(webpSize);
				const savings = parseInt(image.file_size) - webpSize;
				const savingsPercent = Math.round((savings / parseInt(image.file_size)) * 100);
				
				return `<div class="size-info"><div class="size-original">${originalSize}</div><div class="size-webp">${webpSizeFormatted}</div><div class="size-savings">${savingsPercent}% smaller</div></div>`;
			}
			
			return `<span class="size-simple">${originalSize}</span>`;
		},

		/**
		 * Update pagination
		 */
		updatePagination: function() {
			const start = this.currentPage * this.perPage + 1;
			const end = Math.min(start + this.perPage - 1, this.totalImages);
			const totalPages = Math.ceil(this.totalImages / this.perPage);

			$('#pagination-info').text(`Showing ${start}-${end} of ${this.totalImages} images`);
			$('#prev-page').prop('disabled', this.currentPage === 0);
			$('#next-page').prop('disabled', this.currentPage >= totalPages - 1);
		},

		/**
		 * Change page
		 */
		changePage: function(direction) {
			this.currentPage += direction;
			this.loadImages();
		},

		/**
		 * Handle filter change
		 */
		handleFilterChange: function(e) {
			this.currentFilter = $(e.target).val();
			this.currentPage = 0;
			this.loadImages();
		},

		/**
		 * Handle search
		 */
		handleSearch: function(e) {
			this.searchQuery = $(e.target).val();
			this.currentPage = 0;
			this.loadImages();
		},

		/**
		 * Toggle select all
		 */
		toggleSelectAll: function(e) {
			const checked = $(e.target).prop('checked');
			$('.image-checkbox').prop('checked', checked);
			this.updateSelectedImages();
		},

		/**
		 * Handle checkbox change
		 */
		handleCheckboxChange: function() {
			this.updateSelectedImages();
			
			const allChecked = $('.image-checkbox').length === $('.image-checkbox:checked').length;
			$('#select-all-images').prop('checked', allChecked);
		},

		/**
		 * Update selected images list
		 */
		updateSelectedImages: function() {
			this.selectedImages = [];
			$('.image-checkbox:checked').each((i, el) => {
				this.selectedImages.push($(el).val());
			});
		},

		/**
		 * Scan all images
		 */
		scanAllImages: function() {
			if (this.scanning) return;

			this.scanning = true;
			const $btn = $('#scan-all-images');
			const $progress = $('#progress-container');
			
			$btn.addClass('loading').prop('disabled', true);
			$progress.show();
			$('#progress-text').text('Starting scan...');
			$('#progress-fill').css('width', '0%');

			this.scanBatch(0);
		},

		/**
		 * Scan batch of images
		 */
		scanBatch: function(offset) {
			$.ajax({
				url: seovelaImageSeo.ajaxUrl,
				type: 'POST',
				data: {
					action: 'seovela_image_scan',
					nonce: seovelaImageSeo.nonce,
					offset: offset,
					limit: 50
				},
				success: (response) => {
					if (response.success) {
						$('#progress-fill').css('width', response.data.progress + '%');
						$('#progress-text').text(`Scanned ${response.data.offset} of ${response.data.total} images...`);

						if (!response.data.completed) {
							this.scanBatch(response.data.offset);
						} else {
							this.scanning = false;
							$('#scan-all-images').removeClass('loading').prop('disabled', false);
							$('#progress-text').text('Scan complete!');
							
							this.showToast('Scan complete! All images have been analyzed.', 'success');
							this.refreshStats();
							this.loadImages();

							setTimeout(() => {
								$('#progress-container').fadeOut();
							}, 2000);
						}
					} else {
						this.handleScanError();
					}
				},
				error: () => {
					this.handleScanError();
				}
			});
		},

		/**
		 * Handle scan error
		 */
		handleScanError: function() {
			this.scanning = false;
			$('#scan-all-images').removeClass('loading').prop('disabled', false);
			$('#progress-container').hide();
			this.showToast('Error during scanning. Please try again.', 'error');
		},

		/**
		 * Refresh stats
		 */
		refreshStats: function() {
			const $btn = $('#refresh-stats');
			$btn.addClass('loading').prop('disabled', true);

			$.ajax({
				url: seovelaImageSeo.ajaxUrl,
				type: 'POST',
				data: {
					action: 'seovela_image_get_stats',
					nonce: seovelaImageSeo.nonce
				},
				success: (response) => {
					if (response.success) {
						const stats = response.data.stats;
						$('#stat-total').text(this.formatNumber(stats.total_images));
						$('#stat-missing-alt').text(this.formatNumber(stats.missing_alt));
						$('#stat-oversized').text(this.formatNumber(stats.oversized));
						$('#stat-webp').text(this.formatNumber(stats.has_webp));
						$('#stat-convertible').text(this.formatNumber(stats.convertible));
						$('#stat-savings').text(this.formatSize(stats.webp_savings));
						
						// Update WebP section stats
						$('#webp-convertible').text(this.formatNumber(stats.convertible));
						$('#webp-converted').text(this.formatNumber(stats.has_webp));
						$('#webp-total-savings').text(this.formatSize(stats.webp_savings));

						$('#convert-all-webp').prop('disabled', stats.convertible === 0);
					}
				},
				complete: () => {
					$btn.removeClass('loading').prop('disabled', false);
				}
			});
		},

		/**
		 * Apply bulk action
		 */
		applyBulkAction: function() {
			const action = $('#bulk-action').val();
			
			if (!action) {
				this.showToast('Please select an action', 'warning');
				return;
			}

			if (this.selectedImages.length === 0) {
				this.showToast('Please select at least one image', 'warning');
				return;
			}

			if (!confirm(seovelaImageSeo.strings.confirm_bulk || 'Apply changes to selected images?')) {
				return;
			}

			if (action === 'convert_webp') {
				this.convertSelectedWebP();
				return;
			}

			const $btn = $('#apply-bulk-action');
			$btn.addClass('loading').prop('disabled', true);

			$.ajax({
				url: seovelaImageSeo.ajaxUrl,
				type: 'POST',
				data: {
					action: 'seovela_image_bulk_update',
					nonce: seovelaImageSeo.nonce,
					action_type: action,
					attachment_ids: this.selectedImages
				},
				success: (response) => {
					if (response.success) {
						this.showToast(response.data.message, 'success');
						this.loadImages();
						this.refreshStats();
					} else {
						this.showToast(response.data.message || 'Error occurred', 'error');
					}
				},
				error: () => {
					this.showToast('Error applying bulk action', 'error');
				},
				complete: () => {
					$btn.removeClass('loading').prop('disabled', false);
					$('#bulk-action').val('');
				}
			});
		},

		/**
		 * Convert all to WebP
		 */
		convertAllWebP: function() {
			if (this.converting) return;

			if (!confirm('Convert all eligible images to WebP? This may take a while.')) {
				return;
			}

			this.converting = true;
			const $btn = $('#convert-all-webp');
			$btn.addClass('loading').prop('disabled', true);

			$.ajax({
				url: seovelaImageSeo.ajaxUrl,
				type: 'POST',
				data: {
					action: 'seovela_image_convert_webp',
					nonce: seovelaImageSeo.nonce
				},
				success: (response) => {
					if (response.success) {
						this.showToast(response.data.message, 'success');
						this.refreshStats();
						this.loadImages();
					} else {
						this.showToast(response.data.message || 'Conversion failed', 'error');
					}
				},
				error: () => {
					this.showToast('Error during conversion', 'error');
				},
				complete: () => {
					this.converting = false;
					$btn.removeClass('loading').prop('disabled', false);
				}
			});
		},

		/**
		 * Convert selected to WebP
		 */
		convertSelectedWebP: function() {
			const $btn = $('#apply-bulk-action');
			$btn.addClass('loading').prop('disabled', true);

			$.ajax({
				url: seovelaImageSeo.ajaxUrl,
				type: 'POST',
				data: {
					action: 'seovela_image_convert_webp',
					nonce: seovelaImageSeo.nonce,
					attachment_ids: this.selectedImages
				},
				success: (response) => {
					if (response.success) {
						this.showToast(response.data.message, 'success');
						this.refreshStats();
						this.loadImages();
					} else {
						this.showToast(response.data.message || 'Conversion failed', 'error');
					}
				},
				error: () => {
					this.showToast('Error during conversion', 'error');
				},
				complete: () => {
					$btn.removeClass('loading').prop('disabled', false);
					$('#bulk-action').val('');
				}
			});
		},

		/**
		 * Convert single image to WebP
		 */
		convertSingleWebP: function(e) {
			const $btn = $(e.currentTarget);
			const attachmentId = $btn.data('id');

			$btn.addClass('loading').prop('disabled', true);

			$.ajax({
				url: seovelaImageSeo.ajaxUrl,
				type: 'POST',
				data: {
					action: 'seovela_image_convert_webp',
					nonce: seovelaImageSeo.nonce,
					attachment_ids: [attachmentId]
				},
				success: (response) => {
					if (response.success) {
						this.showToast(response.data.message, 'success');
						this.loadImages();
						this.refreshStats();
					} else {
						this.showToast(response.data.message || 'Conversion failed', 'error');
					}
				},
				error: () => {
					this.showToast('Error during conversion', 'error');
				},
				complete: () => {
					$btn.removeClass('loading').prop('disabled', false);
				}
			});
		},

		/**
		 * Save settings
		 */
		saveSettings: function(e) {
			e.preventDefault();

			const $form = $('#settings-form');
			const $btn = $('#save-settings');
			$btn.addClass('loading').prop('disabled', true);

			const formData = $form.serializeArray();
			const data = {
				action: 'seovela_image_save_settings',
				nonce: seovelaImageSeo.nonce
			};

			// Add checkboxes (unchecked ones aren't included in serializeArray)
			$form.find('input[type="checkbox"]').each(function() {
				const name = $(this).attr('name');
				data[name] = $(this).is(':checked') ? 1 : 0;
			});

			// Add other form fields
			formData.forEach(item => {
				data[item.name] = item.value;
			});

			// Also include WebP conversion form
			$('#webp-settings-form').find('input, select').each(function() {
				const $el = $(this);
				const name = $el.attr('name');
				if ($el.attr('type') === 'checkbox') {
					data[name] = $el.is(':checked') ? 1 : 0;
				} else {
					data[name] = $el.val();
				}
			});

			// Also include WebP serving form
			$('#webp-serving-form').find('input, select').each(function() {
				const $el = $(this);
				const name = $el.attr('name');
				if (!name) return;
				if ($el.attr('type') === 'checkbox') {
					data[name] = $el.is(':checked') ? 1 : 0;
				} else if ($el.attr('type') === 'radio') {
					if ($el.is(':checked')) {
						data[name] = $el.val();
					}
				} else {
					data[name] = $el.val();
				}
			});

			$.ajax({
				url: seovelaImageSeo.ajaxUrl,
				type: 'POST',
				data: data,
				success: (response) => {
					if (response.success) {
						this.showToast(response.data.message, 'success');
					} else {
						this.showToast(response.data.message || 'Error saving settings', 'error');
					}
				},
				error: () => {
					this.showToast('Error saving settings', 'error');
				},
				complete: () => {
					$btn.removeClass('loading').prop('disabled', false);
				}
			});
		},

		/**
		 * Open edit modal
		 */
		openEditModal: function(e) {
			const attachmentId = $(e.currentTarget).data('id');
			const $row = $(`tr[data-attachment-id="${attachmentId}"]`);
			
			// Find the image data
			const $img = $row.find('.image-column img');
			const filename = $row.find('.filename').text();
			const dimensions = $row.find('.dimensions').text();
			const alt = $row.find('.alt-column .text-preview').text() || '';
			const title = $row.find('.title-column .text-preview').text() || '';

			// Populate modal
			$('#edit-image-preview-img').attr('src', $img.attr('src'));
			$('#edit-image-filename').text(filename);
			$('#edit-image-dimensions').text(dimensions);
			$('#edit-image-id').val(attachmentId);
			$('#edit-image-alt').val(alt);
			$('#edit-image-title').val(title);
			$('#edit-image-caption').val('');
			$('#edit-image-description').val('');

			$('#edit-image-modal').fadeIn(200);
		},

		/**
		 * Save image edit
		 */
		saveImageEdit: function() {
			const $btn = $('#save-image-edit');
			$btn.addClass('loading').prop('disabled', true);

			const attachmentId = $('#edit-image-id').val();
			
			$.ajax({
				url: seovelaImageSeo.ajaxUrl,
				type: 'POST',
				data: {
					action: 'seovela_image_update',
					nonce: seovelaImageSeo.nonce,
					attachment_id: attachmentId,
					alt: $('#edit-image-alt').val(),
					title: $('#edit-image-title').val(),
					caption: $('#edit-image-caption').val(),
					description: $('#edit-image-description').val()
				},
				success: (response) => {
					if (response.success) {
						this.showToast(response.data.message, 'success');
						this.closeModal();
						this.loadImages();
						this.refreshStats();
					} else {
						this.showToast(response.data.message || 'Error saving', 'error');
					}
				},
				error: () => {
					this.showToast('Error saving image', 'error');
				},
				complete: () => {
					$btn.removeClass('loading').prop('disabled', false);
				}
			});
		},

		/**
		 * Apply template to field
		 */
		applyTemplate: function(e) {
			const $btn = $(e.currentTarget);
			const attribute = $btn.data('attribute');
			const attachmentId = $('#edit-image-id').val();

			$btn.addClass('loading');

			$.ajax({
				url: seovelaImageSeo.ajaxUrl,
				type: 'POST',
				data: {
					action: 'seovela_image_apply_template',
					nonce: seovelaImageSeo.nonce,
					attachment_id: attachmentId,
					attribute: attribute
				},
				success: (response) => {
					if (response.success) {
						$(`#edit-image-${attribute}`).val(response.data.value);
						this.showToast('Template applied', 'success');
					}
				},
				complete: () => {
					$btn.removeClass('loading');
				}
			});
		},

		/**
		 * Open variable picker
		 */
		openVariablePicker: function(e) {
			this.currentVariableTarget = $(e.currentTarget).data('target');
			$('#variable-picker-modal').fadeIn(200);
			$('#variable-search').val('').focus();
			$('.modal-variable-item').show();
		},

		/**
		 * Insert variable into target field
		 */
		insertVariable: function(e) {
			const variable = $(e.currentTarget).data('variable');
			const $target = $(`[name="${this.currentVariableTarget}"]`);
			
			if ($target.length) {
				const currentVal = $target.val();
				const cursorPos = $target[0].selectionStart || currentVal.length;
				const newVal = currentVal.slice(0, cursorPos) + variable + currentVal.slice(cursorPos);
				$target.val(newVal).focus();
			}

			this.closeModal();
		},

		/**
		 * Filter variables in modal
		 */
		filterVariables: function(e) {
			const search = $(e.target).val().toLowerCase();
			
			$('.modal-variable-item').each(function() {
				const text = $(this).text().toLowerCase();
				$(this).toggle(text.includes(search));
			});
		},

		/**
		 * Copy variable to clipboard
		 */
		copyVariable: function(e) {
			e.stopPropagation();
			const $item = $(e.currentTarget).closest('.variable-item');
			const variable = $item.data('variable') || $(e.currentTarget).data('variable');
			
			navigator.clipboard.writeText(variable).then(() => {
				this.showToast('Copied to clipboard!', 'success');
			});
		},

		/**
		 * Close modal
		 */
		closeModal: function() {
			$('.seovela-imgseo-modal').fadeOut(200);
		},

		/**
		 * Handle keyboard shortcuts
		 */
		handleKeydown: function(e) {
			// Escape to close modal
			if (e.key === 'Escape') {
				this.closeModal();
			}
		},

		/**
		 * Show toast notification
		 */
		showToast: function(message, type = 'success') {
			const icons = {
				success: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>',
				error: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>',
				warning: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>'
			};

			const $toast = $(`
				<div class="seovela-imgseo-toast ${type}">
					<span class="toast-icon">${icons[type]}</span>
					<span class="toast-message">${this.escapeHtml(message)}</span>
					<button type="button" class="toast-close">&times;</button>
				</div>
			`);

			$('#toast-container').append($toast);

			$toast.find('.toast-close').on('click', function() {
				$toast.addClass('toast-out');
				setTimeout(() => $toast.remove(), 300);
			});

			setTimeout(() => {
				$toast.addClass('toast-out');
				setTimeout(() => $toast.remove(), 300);
			}, 5000);
		},

		/**
		 * Format file size
		 */
		formatSize: function(bytes) {
			if (!bytes || bytes === 0) return '0 B';
			const k = 1024;
			const sizes = ['B', 'KB', 'MB', 'GB'];
			const i = Math.floor(Math.log(bytes) / Math.log(k));
			return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
		},

		/**
		 * Format number with commas
		 */
		formatNumber: function(num) {
			return (num || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
		},

		/**
		 * Escape HTML
		 */
		escapeHtml: function(text) {
			if (!text) return '';
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		},

		/**
		 * Debounce function
		 */
		debounce: function(func, wait) {
			let timeout;
			return function executedFunction(...args) {
				const later = () => {
					clearTimeout(timeout);
					func(...args);
				};
				clearTimeout(timeout);
				timeout = setTimeout(later, wait);
			};
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		if ($('.seovela-image-seo-page').length) {
			ImageSeo.init();
		}
	});

})(jQuery);
