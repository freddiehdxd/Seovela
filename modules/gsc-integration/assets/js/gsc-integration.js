/**
 * Seovela GSC Integration JavaScript - Centralized OAuth Version
 *
 * @package Seovela
 */

(function($) {
	'use strict';

	const GscIntegration = {
		syncing: false,
		chart: null,

		init: function() {
			this.bindEvents();
			this.initChart();
		},

		bindEvents: function() {
			// Disconnect from GSC
			$(document).on('click', '#disconnect-gsc', this.disconnectGsc.bind(this));
			
			// Sync data - multiple buttons
			$(document).on('click', '#sync-gsc-data, #sync-gsc-now, #sync-gsc-empty', this.syncData.bind(this));
			
			// Date range selector
			$(document).on('change', '#date-range-selector', this.changeDateRange.bind(this));
			
			// Property selection
			$(document).on('click', '.select-property', this.selectProperty.bind(this));
		},

		/**
		 * Initialize the performance chart
		 */
		initChart: function() {
			const canvas = document.getElementById('gsc-performance-chart');
			if (!canvas || typeof Chart === 'undefined') {
				return;
			}

			const chartData = seovelaGsc.chartData || {};
			
			if (!chartData.labels || chartData.labels.length === 0) {
				// No data yet - show message
				const container = canvas.parentElement;
				container.innerHTML = '<div class="gsc-chart-empty" style="display:flex;align-items:center;justify-content:center;height:100%;color:#64748b;font-size:14px;">No chart data available. Click "Sync Now" to fetch data.</div>';
				return;
			}

			const ctx = canvas.getContext('2d');
			
			// Format labels to show shorter dates
			const formattedLabels = chartData.labels.map(function(date) {
				const d = new Date(date);
				return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
			});

			this.chart = new Chart(ctx, {
				type: 'line',
				data: {
					labels: formattedLabels,
					datasets: [
						{
							label: 'Clicks',
							data: chartData.clicks || [],
							borderColor: '#4285F4',
							backgroundColor: 'rgba(66, 133, 244, 0.1)',
							borderWidth: 3,
							fill: true,
							tension: 0.4,
							pointRadius: 0,
							pointHoverRadius: 6,
							pointHoverBackgroundColor: '#4285F4',
							pointHoverBorderColor: '#fff',
							pointHoverBorderWidth: 2,
						},
						{
							label: 'Impressions',
							data: chartData.impressions || [],
							borderColor: '#34A853',
							backgroundColor: 'rgba(52, 168, 83, 0.1)',
							borderWidth: 3,
							fill: true,
							tension: 0.4,
							pointRadius: 0,
							pointHoverRadius: 6,
							pointHoverBackgroundColor: '#34A853',
							pointHoverBorderColor: '#fff',
							pointHoverBorderWidth: 2,
							yAxisID: 'y1',
						}
					]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					interaction: {
						mode: 'index',
						intersect: false,
					},
					plugins: {
						legend: {
							position: 'top',
							align: 'end',
							labels: {
								boxWidth: 12,
								boxHeight: 12,
								borderRadius: 3,
								useBorderRadius: true,
								padding: 20,
								font: {
									size: 13,
									weight: '600',
								},
							},
						},
						tooltip: {
							backgroundColor: 'rgba(30, 41, 59, 0.95)',
							titleFont: {
								size: 14,
								weight: '600',
							},
							bodyFont: {
								size: 13,
							},
							padding: 14,
							cornerRadius: 10,
							displayColors: true,
							boxWidth: 10,
							boxHeight: 10,
							boxPadding: 4,
							callbacks: {
								label: function(context) {
									let label = context.dataset.label || '';
									if (label) {
										label += ': ';
									}
									label += context.parsed.y.toLocaleString();
									return label;
								}
							}
						},
					},
					scales: {
						x: {
							grid: {
								display: false,
							},
							ticks: {
								font: {
									size: 11,
								},
								color: '#64748b',
								maxRotation: 0,
								maxTicksLimit: 10,
							},
						},
						y: {
							type: 'linear',
							display: true,
							position: 'left',
							grid: {
								color: 'rgba(0, 0, 0, 0.05)',
							},
							ticks: {
								font: {
									size: 11,
								},
								color: '#64748b',
								callback: function(value) {
									return value.toLocaleString();
								}
							},
							title: {
								display: true,
								text: 'Clicks',
								font: {
									size: 12,
									weight: '600',
								},
								color: '#4285F4',
							},
						},
						y1: {
							type: 'linear',
							display: true,
							position: 'right',
							grid: {
								drawOnChartArea: false,
							},
							ticks: {
								font: {
									size: 11,
								},
								color: '#64748b',
								callback: function(value) {
									return value.toLocaleString();
								}
							},
							title: {
								display: true,
								text: 'Impressions',
								font: {
									size: 12,
									weight: '600',
								},
								color: '#34A853',
							},
						},
					},
				},
			});
		},

		/**
		 * Update chart with new data
		 */
		updateChart: function(chartData) {
			if (!this.chart || !chartData || !chartData.labels) {
				return;
			}

			const formattedLabels = chartData.labels.map(function(date) {
				const d = new Date(date);
				return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
			});

			this.chart.data.labels = formattedLabels;
			this.chart.data.datasets[0].data = chartData.clicks || [];
			this.chart.data.datasets[1].data = chartData.impressions || [];
			this.chart.update('active');
		},

		/**
		 * Select a property
		 */
		selectProperty: function(e) {
			e.preventDefault();
			
			const $btn = $(e.currentTarget);
			const propertyUrl = $btn.data('url');
			const originalText = $btn.text();
			
			$btn.prop('disabled', true).text('Selecting...');
			
			$.ajax({
				url: seovelaGsc.ajaxUrl,
				type: 'POST',
				data: {
					action: 'seovela_gsc_select_property',
					nonce: seovelaGsc.nonce,
					property: propertyUrl
				},
				success: (response) => {
					if (response.success) {
						this.showSuccess('Property selected! Redirecting...');
						setTimeout(() => {
							// Reload the page with connected parameter
							const url = new URL(window.location.href);
							url.searchParams.set('page', 'seovela-gsc');
							url.searchParams.set('connected', '1');
							window.location.href = url.toString();
						}, 1000);
					} else {
						this.showError(response.data?.message || 'Failed to select property.');
						$btn.prop('disabled', false).text(originalText);
					}
				},
				error: () => {
					this.showError('An error occurred.');
					$btn.prop('disabled', false).text(originalText);
				}
			});
		},

		/**
		 * Disconnect from Google Search Console
		 */
		disconnectGsc: function(e) {
			e.preventDefault();
			
			const confirmed = confirm(
				'Are you sure you want to disconnect from Google Search Console?\n\n' +
				'All synced data will be removed. You will need to reconnect to fetch new data.'
			);
			
			if (!confirmed) return;
			
			const $btn = $('#disconnect-gsc');
			const originalText = $btn.text();
			
			$btn.prop('disabled', true).text('Disconnecting...');
			
			$.ajax({
				url: seovelaGsc.ajaxUrl,
				type: 'POST',
				data: {
					action: 'seovela_gsc_disconnect',
					nonce: seovelaGsc.nonce
				},
				success: (response) => {
					if (response.success) {
						this.showSuccess('Disconnected successfully!');
						setTimeout(() => {
							window.location.reload();
						}, 1000);
					} else {
						this.showError('Failed to disconnect.');
						$btn.prop('disabled', false).text(originalText);
					}
				},
				error: () => {
					this.showError('An error occurred.');
					$btn.prop('disabled', false).text(originalText);
				}
			});
		},

		/**
		 * Sync data from Google Search Console
		 */
		syncData: function(e) {
			e.preventDefault();
			
			if (this.syncing) return;
			
			this.syncing = true;
			
			const $btn = $(e.currentTarget);
			const $allSyncBtns = $('#sync-gsc-data, #sync-gsc-now, #sync-gsc-empty');
			
			// Store original content
			const originalContents = [];
			$allSyncBtns.each(function() {
				originalContents.push($(this).html());
			});
			
			// Show loading state
			$allSyncBtns.prop('disabled', true);
			$btn.html(`
				<svg class="spin" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M21 12a9 9 0 1 1-6.219-8.56"/>
				</svg>
				<span>Syncing...</span>
			`);
			
			$.ajax({
				url: seovelaGsc.ajaxUrl,
				type: 'POST',
				data: {
					action: 'seovela_gsc_sync_data',
					nonce: seovelaGsc.nonce
				},
				success: (response) => {
					this.syncing = false;
					
					if (response.success) {
						const rowCount = response.data?.rows || 0;
						$btn.html(`
							<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
								<polyline points="20 6 9 17 4 12"/>
							</svg>
							<span>Synced ${rowCount} pages!</span>
						`);
						
						this.showSuccess(`Successfully synced ${rowCount} pages from Google Search Console!`);
						
						setTimeout(() => {
							window.location.reload();
						}, 2000);
					} else {
						this.showError(response.data?.message || 'Failed to sync data.');
						$allSyncBtns.each(function(i) {
							$(this).prop('disabled', false).html(originalContents[i]);
						});
					}
				},
				error: () => {
					this.syncing = false;
					this.showError('An error occurred during sync.');
					$allSyncBtns.each(function(i) {
						$(this).prop('disabled', false).html(originalContents[i]);
					});
				}
			});
		},

		/**
		 * Change date range and fetch new data
		 */
		changeDateRange: function() {
			const days = $('#date-range-selector').val();
			
			// Add loading state
			$('.gsc-stats-section, .gsc-chart-section').css('opacity', '0.5');
			
			$.ajax({
				url: seovelaGsc.ajaxUrl,
				type: 'POST',
				data: {
					action: 'seovela_gsc_get_stats',
					nonce: seovelaGsc.nonce,
					days: days
				},
				success: (response) => {
					$('.gsc-stats-section, .gsc-chart-section').css('opacity', '1');
					
					if (response.success) {
						const stats = response.data.stats;
						const chartData = response.data.chartData;
						
						// Update stat values with animation
						this.animateValue('#stat-clicks', stats.total_clicks);
						this.animateValue('#stat-impressions', stats.total_impressions);
						$('#stat-ctr').text(stats.avg_ctr.toFixed(2) + '%');
						$('#stat-position').text(stats.avg_position.toFixed(1));
						
						// Update chart
						this.updateChart(chartData);
					}
				},
				error: () => {
					$('.gsc-stats-section, .gsc-chart-section').css('opacity', '1');
					this.showError('Failed to fetch data.');
				}
			});
		},

		/**
		 * Animate a numeric value change
		 */
		animateValue: function(selector, newValue) {
			const $el = $(selector);
			const currentValue = parseInt($el.text().replace(/,/g, '')) || 0;
			
			$({ value: currentValue }).animate({ value: newValue }, {
				duration: 500,
				easing: 'swing',
				step: function(now) {
					$el.text(Math.round(now).toLocaleString());
				}
			});
		},

		/**
		 * Show success notification
		 */
		showSuccess: function(message) {
			this.showNotice(message, 'success');
		},

		/**
		 * Show error notification
		 */
		showError: function(message) {
			this.showNotice(message, 'error');
		},

		/**
		 * Show notification toast
		 */
		showNotice: function(message, type) {
			// Remove existing notices
			$('.gsc-toast').remove();
			
			const bgColor = type === 'success' 
				? 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)'
				: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
			
			const icon = type === 'success'
				? '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>'
				: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
			
			const $toast = $(`
				<div class="gsc-toast" style="
					position: fixed;
					top: 50px;
					right: 30px;
					z-index: 999999;
					display: flex;
					align-items: center;
					gap: 12px;
					background: ${bgColor};
					color: white;
					padding: 16px 24px;
					border-radius: 12px;
					box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
					font-size: 14px;
					font-weight: 600;
					transform: translateX(120%);
					transition: transform 0.3s ease;
				">
					${icon}
					<span>${message}</span>
				</div>
			`);
			
			$('body').append($toast);
			
			// Animate in
			setTimeout(() => {
				$toast.css('transform', 'translateX(0)');
			}, 10);
			
			// Animate out
			setTimeout(() => {
				$toast.css('transform', 'translateX(120%)');
				setTimeout(() => $toast.remove(), 300);
			}, 5000);
		}
	};

	// Add CSS for spin animation
	$('<style>')
		.text(`
			@keyframes spin {
				from { transform: rotate(0deg); }
				to { transform: rotate(360deg); }
			}
			.spin {
				animation: spin 1s linear infinite;
			}
		`)
		.appendTo('head');

	// Initialize on document ready
	$(document).ready(function() {
		GscIntegration.init();
	});

})(jQuery);
