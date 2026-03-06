/**
 * Seovela Internal Links JavaScript
 *
 * @package Seovela
 */

(function($) {
	'use strict';

	const InternalLinks = {
		init: function() {
			this.bindEvents();
			this.initAnimations();
		},

		bindEvents: function() {
			// Insert link button in metabox
			$(document).on('click', '.insert-link-btn', this.insertLink);
			
			// Refresh suggestions button
			$(document).on('click', '.refresh-suggestions-btn', this.refreshSuggestions);
		},

		initAnimations: function() {
			// Animate elements on page load
			$('.suggestion-item').each(function(index) {
				$(this).css({
					'opacity': '0',
					'transform': 'translateY(15px)'
				}).delay(index * 50).animate({
					'opacity': '1'
				}, 300).css('transform', 'translateY(0)');
			});
		},

		insertLink: function(e) {
			e.preventDefault();
			
			const $btn = $(this);
			const url = $btn.data('url');
			const title = $btn.data('title');
			const anchor = $btn.data('anchor');
			const $item = $btn.closest('.suggestion-item');

			// Add loading state
			$btn.addClass('loading');
			$item.addClass('loading');

			// Check if we're in Gutenberg or Classic Editor
			if (typeof wp !== 'undefined' && wp.data) {
				// Gutenberg
				InternalLinks.insertLinkGutenberg(url, anchor || title);
			} else if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
				// Classic Editor
				InternalLinks.insertLinkClassic(url, anchor || title);
			} else {
				alert('Please place your cursor in the editor where you want to insert the link.');
				$btn.removeClass('loading');
				$item.removeClass('loading');
				return;
			}

			// Visual feedback with animation
			$btn.html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Inserted');
			$btn.prop('disabled', true);
			
			setTimeout(function() {
				$btn.removeClass('loading').html('Insert Link').prop('disabled', false);
				$item.removeClass('loading');
			}, 2500);
		},

		insertLinkGutenberg: function(url, text) {
			// Create a link in Gutenberg
			const { dispatch, select } = wp.data;
			const selectedBlock = select('core/block-editor').getSelectedBlock();
			
			if (selectedBlock && selectedBlock.name === 'core/paragraph') {
				// Insert link into selected paragraph
				const content = selectedBlock.attributes.content;
				const newContent = content + ' <a href="' + url + '">' + text + '</a>';
				
				dispatch('core/block-editor').updateBlockAttributes(
					selectedBlock.clientId,
					{ content: newContent }
				);
			} else {
				// Create new paragraph with link
				const newBlock = wp.blocks.createBlock('core/paragraph', {
					content: '<a href="' + url + '">' + text + '</a>'
				});
				
				dispatch('core/block-editor').insertBlock(newBlock);
			}
		},

		insertLinkClassic: function(url, text) {
			const editor = tinymce.activeEditor;
			const selectedText = editor.selection.getContent({ format: 'text' });
			const linkText = selectedText || text;
			
			editor.execCommand(
				'mceInsertContent',
				false,
				'<a href="' + url + '">' + linkText + '</a>'
			);
		},

		refreshSuggestions: function(e) {
			e.preventDefault();
			
			const $btn = $(this);
			const postId = $btn.data('post-id');
			const originalText = $btn.text();
			
			// Disable button and show loading
			$btn.prop('disabled', true);
			$btn.html('<svg class="spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Refreshing...');
			
			$.ajax({
				url: seovelaInternalLinks.ajaxUrl,
				type: 'POST',
				data: {
					action: 'seovela_refresh_link_suggestions',
					nonce: seovelaInternalLinks.nonce,
					post_id: postId
				},
				success: function(response) {
					if (response.success) {
						// Show success message
						$btn.html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Done!');
						
						// Reload after brief delay
						setTimeout(function() {
							window.location.reload();
						}, 800);
					} else {
						InternalLinks.showError('Failed to refresh suggestions.');
						$btn.prop('disabled', false).text(originalText);
					}
				},
				error: function() {
					InternalLinks.showError('An error occurred.');
					$btn.prop('disabled', false).text(originalText);
				}
			});
		},

		showError: function(message) {
			// Create a toast notification
			const $toast = $('<div class="seovela-toast error">' + message + '</div>');
			$('body').append($toast);
			
			setTimeout(function() {
				$toast.addClass('show');
			}, 10);
			
			setTimeout(function() {
				$toast.removeClass('show');
				setTimeout(function() {
					$toast.remove();
				}, 300);
			}, 3000);
		},

		showSuccess: function(message) {
			const $toast = $('<div class="seovela-toast success">' + message + '</div>');
			$('body').append($toast);
			
			setTimeout(function() {
				$toast.addClass('show');
			}, 10);
			
			setTimeout(function() {
				$toast.removeClass('show');
				setTimeout(function() {
					$toast.remove();
				}, 300);
			}, 3000);
		}
	};

	// Toast styles
	const toastStyles = `
		<style>
		.seovela-toast {
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
		.seovela-toast.show {
			transform: translateY(0);
			opacity: 1;
		}
		.seovela-toast.success {
			background: linear-gradient(135deg, #10b981 0%, #059669 100%);
		}
		.seovela-toast.error {
			background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
		}
		.spin {
			animation: spin 1s linear infinite;
		}
		@keyframes spin {
			from { transform: rotate(0deg); }
			to { transform: rotate(360deg); }
		}
		</style>
	`;

	// Initialize on document ready
	$(document).ready(function() {
		$('head').append(toastStyles);
		InternalLinks.init();
	});

})(jQuery);
