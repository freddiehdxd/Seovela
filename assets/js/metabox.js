/**
 * Seovela Metabox JavaScript
 *
 * @package Seovela
 */

(function($) {
    'use strict';

    var seovelaMetabox = {
        analysisTimeout: null,

        init: function() {
            this.bindEvents();
            this.updateCounters();
            this.updatePreview();
            this.animateScore();
            
            // Initial analysis for Gutenberg (waits for editor to load)
            if (typeof wp !== 'undefined' && wp.data && wp.domReady) {
                var self = this;
                wp.domReady(function() {
                    // Give it a moment to fully settle
                    setTimeout(function() {
                        self.updatePreview(); // Update preview URL from Gutenberg data
                        self.scheduleAnalysis();
                    }, 1000);
                });
            }
        },

        bindEvents: function() {
            var self = this;

            // Update counters, preview, and analyze on input
            $('.seovela-title-input, .seovela-description-input, .seovela-keyword-input').off('input').on('input', function() {
                self.updateCounters();
                self.updatePreview();
                self.scheduleAnalysis();
            });

            // Also analyze when content changes (for Gutenberg and Classic Editor)
            if (typeof wp !== 'undefined' && wp.data && wp.data.subscribe) {
                // Gutenberg
                var initialContent = wp.data.select('core/editor').getEditedPostContent();
                var initialTitle = wp.data.select('core/editor').getEditedPostAttribute('title');
                
                wp.data.subscribe(function() {
                    var newContent = wp.data.select('core/editor').getEditedPostContent();
                    var newTitle = wp.data.select('core/editor').getEditedPostAttribute('title');
                    
                    if (newContent !== initialContent || newTitle !== initialTitle) {
                        initialContent = newContent;
                        initialTitle = newTitle;
                        self.updatePreview(); // Update preview immediately
                        self.scheduleAnalysis();
                    }
                });
            }

            // Classic Editor (TinyMCE)
            $(document).on('tinymce-editor-init', function(event, editor) {
                if (editor.id === 'content') {
                    // Listen for specific events that change content
                    editor.on('keyup change paste setcontent input', function() {
                        self.scheduleAnalysis();
                    });
                }
            });

            // Also check if TinyMCE is already initialized (re-binding safety)
            if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                var editor = tinymce.get('content');
                editor.off('keyup change paste setcontent input').on('keyup change paste setcontent input', function() {
                    self.scheduleAnalysis();
                });
            }

            // Fallback for non-TinyMCE textareas
            $(document).off('input.seovela').on('input.seovela', '#content, .editor-post-text-editor', function() {
                self.scheduleAnalysis();
            });

            // Toggle analysis results
            $('.seovela-toggle-analysis').off('click').on('click', function(e) {
                e.preventDefault();
                var $results = $('.seovela-analysis-results');
                var $button = $(this);
                
                $results.slideToggle(300, function() {
                if ($results.is(':visible')) {
                    $button.text('Hide Analysis');
                } else {
                    $button.text('View Analysis');
                }
                });
            });

            // AI optimization buttons
            if (typeof window.seovelaMetabox !== 'undefined' && window.seovelaMetabox.aiEnabled) {
                $('.seovela-ai-optimize').off('click').on('click', function(e) {
                    e.preventDefault();
                    var field = $(this).data('field');
                    self.optimizeWithAI(field, $(this));
                });

                // Suggest keywords button
                $('.seovela-suggest-keywords').off('click').on('click', function(e) {
                    e.preventDefault();
                    self.suggestKeywords($(this));
                });

                // Keyword suggestion clicks
                $(document).off('click.keyword').on('click.keyword', '.seovela-keyword-chip', function(e) {
                    e.preventDefault();
                    var keyword = $(this).data('keyword');
                    $('.seovela-keyword-input').val(keyword).trigger('input');
                    $('.seovela-keyword-suggestions').slideUp();
                });

            }
        },

        updateCounters: function() {
            var self = this;

            // Title counter
            var titleInput = $('.seovela-title-input');
            if (titleInput.length) {
                var titleLength = titleInput.val().length;
                var titleCounter = $('.seovela-count[data-field="title"]');
                titleCounter.text(titleLength);
                titleCounter.removeClass('seovela-count-short seovela-count-good seovela-count-long');
                
                if (titleLength < 30) {
                    titleCounter.addClass('seovela-count-short');
                    titleCounter.next('.seovela-status').text('Too short');
                } else if (titleLength > 60) {
                    titleCounter.addClass('seovela-count-long');
                    titleCounter.next('.seovela-status').text('Too long');
                } else {
                    titleCounter.addClass('seovela-count-good');
                    titleCounter.next('.seovela-status').text('Good length');
                }
            }

            // Description counter
            var descInput = $('.seovela-description-input');
            if (descInput.length) {
                var descLength = descInput.val().length;
                var descCounter = $('.seovela-count[data-field="description"]');
                descCounter.text(descLength);
                descCounter.removeClass('seovela-count-short seovela-count-good seovela-count-long');
                
                if (descLength < 120) {
                    descCounter.addClass('seovela-count-short');
                    descCounter.next('.seovela-status').text('Too short');
                } else if (descLength > 160) {
                    descCounter.addClass('seovela-count-long');
                    descCounter.next('.seovela-status').text('Too long');
                } else {
                    descCounter.addClass('seovela-count-good');
                    descCounter.next('.seovela-status').text('Good length');
                }
            }
        },

        updatePreview: function() {
            var title = $('.seovela-title-input').val();
            var description = $('.seovela-description-input').val();
            var url = '';

            // Try to get URL from Gutenberg
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                url = wp.data.select('core/editor').getPermalink();
            } 
            
            // Fallback to Classic Editor / DOM
            if (!url) {
                var $samplePermalink = $('#sample-permalink');
                if ($samplePermalink.length) {
                    // Get the actual permalink href first (this is the real URL)
                    var $link = $samplePermalink.find('a');
                    if ($link.length) {
                        url = $link.attr('href');
                        
                        // If href gives us ?p=XX (draft/preview URL), try to construct pretty URL
                        if (url && url.indexOf('?p=') !== -1 || url.indexOf('?page_id=') !== -1) {
                            // Get the editable slug
                            var slug = $('#editable-post-name').text().trim() || $('#editable-post-name-full').text().trim();
                            if (slug) {
                                // Get base URL from the link
                                var baseUrl = url.split('?')[0];
                                // Construct pretty permalink
                                url = baseUrl.replace(/\/$/, '') + '/' + slug + '/';
                            }
                        }
                    }
                    
                    // Fallback: try to get from visible text if href failed
                    if (!url || url === 'undefined') {
                        var $clone = $samplePermalink.clone();
                        $clone.find('#edit-slug-buttons, button, .screen-reader-text').remove();
                        url = $clone.text().replace('Permalink:', '').trim();
                    }
                }
                
                // Last resort fallback
                if (!url || url === 'undefined') {
                    url = $('.seovela-preview-url').text();
                }
            }

            $('.seovela-preview-title').text(title);
            $('.seovela-preview-description').text(description);
            
            // Update URL if we found a valid one
            if (url && url !== 'undefined') {
                $('.seovela-preview-url').text(url);
            }
        },

        scheduleAnalysis: function() {
            var self = this;
            
            // Clear existing timeout
            if (this.analysisTimeout) {
                clearTimeout(this.analysisTimeout);
            }
            
            // Add updating state immediately
            $('.seovela-score-widget').addClass('seovela-updating');
            
            // Schedule analysis after 500ms (more responsive)
            this.analysisTimeout = setTimeout(function() {
                self.analyzeContent();
            }, 500);
        },

        analyzeContent: function() {
            if (!window.seovelaMetabox || !window.seovelaMetabox.ajaxUrl) {
                return;
            }

            var postId = $('#post_ID').val();
            var keyword = $('.seovela-keyword-input').val();
            var title = $('.seovela-title-input').val();
            var description = $('.seovela-description-input').val();
            var url = '';
            
            // Get content and URL
            var content = '';
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                // Gutenberg
                var editor = wp.data.select('core/editor');
                content = editor.getEditedPostContent();
                url = editor.getPermalink();
            } else {
                // Classic Editor
                if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                    content = tinymce.get('content').getContent();
            } else {
                content = $('#content').val() || '';
                }
                
                // Get URL from Classic Editor permalink box  
                var $permalink = $('#sample-permalink');
                if ($permalink.length) {
                    var $link = $permalink.find('a');
                    if ($link.length) {
                        url = $link.attr('href');
                        
                        // If href gives us ?p=XX (draft/preview URL), try to construct pretty URL
                        if (url && (url.indexOf('?p=') !== -1 || url.indexOf('?page_id=') !== -1)) {
                            var slug = $('#editable-post-name').text().trim() || $('#editable-post-name-full').text().trim();
                            if (slug) {
                                var baseUrl = url.split('?')[0];
                                url = baseUrl.replace(/\/$/, '') + '/' + slug + '/';
                            }
                        }
                    } else {
                        // Fallback to text content
                        url = $permalink.text().replace('Permalink:', '').trim();
                    }
                }
            }

            // Show loading state
            var $scoreWidget = $('.seovela-score-widget');
            
            // Don't add 'seovela-loading' here as we want a subtle update, 
            // but keep it for the initial load or button clicks if needed.
            // We use 'seovela-updating' for the typing feedback.

            $.ajax({
                url: window.seovelaMetabox.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'seovela_analyze_content',
                    nonce: window.seovelaMetabox.analysisNonce,
                    post_id: postId,
                    focus_keyword: keyword,
                    title: title,
                    description: description,
                    content: content,
                    url: url
                },
                success: function(response) {
                    if (response.success) {
                        seovelaMetabox.updateAnalysisUI(response.data);
                    }
                },
                complete: function() {
                    $scoreWidget.removeClass('seovela-loading seovela-updating');
                }
            });
        },

        updateAnalysisUI: function(data) {
            var score = data.score;
            var status = data.status;
            var $scoreNum = $('.seovela-score-number');
            var currentScore = parseInt($scoreNum.text(), 10);
            
            // Update score circle
            var $circle = $('.seovela-score-circle');
            $circle.attr('data-score', score);
            $circle.attr('data-status', status);
            
            // Update score number with animation if changed
            if (currentScore !== score) {
                $scoreNum.addClass('seovela-pulse');
                setTimeout(function() {
                    $scoreNum.removeClass('seovela-pulse');
                }, 300);
                $scoreNum.text(score);
            }
            
            $('.seovela-score-label').text(this.getStatusLabel(status));
            
            // Animate progress circle
            var circumference = 339.29; // 2 * PI * 54
            var offset = circumference - (score / 100) * circumference;
            $('.seovela-score-progress').css('stroke-dashoffset', offset);
            
            // Update analysis sections (if results panel is visible)
            // Note: Full implementation would rebuild the analysis sections here
            // For now, we're just updating the score
        },

        getStatusLabel: function(status) {
            var labels = {
                'good': 'Good',
                'warning': 'Needs Improvement',
                'error': 'Poor'
            };
            return labels[status] || 'Unknown';
        },

        animateScore: function() {
            var $circle = $('.seovela-score-circle');
            if ($circle.length === 0) return;
            
            var score = parseInt($circle.attr('data-score'), 10) || 0;
            var circumference = 339.29; // 2 * PI * 54
            var offset = circumference - (score / 100) * circumference;
            
            // Set initial state (full circle hidden)
            $('.seovela-score-progress').css('stroke-dashoffset', circumference);
            
            // Animate to current score after short delay
            setTimeout(function() {
                $('.seovela-score-progress').css('stroke-dashoffset', offset);
            }, 150);
        },

        optimizeWithAI: function(field, $button) {
            var self = this;
            var postId = $('#post_ID').val();
            var fieldInput = field === 'title' ? $('.seovela-title-input') : $('.seovela-description-input');
            var focusKeyword = $('.seovela-keyword-input').val();
            var originalButtonText = $button.text();

            // Get content from editor
            var content = '';
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                content = wp.data.select('core/editor').getEditedPostContent();
            } else if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                content = tinymce.get('content').getContent();
            } else {
                content = $('#content').val() || '';
            }

            // Strip HTML tags for cleaner content
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = content;
            content = tempDiv.textContent || tempDiv.innerText || '';

            if (!content || content.trim().length < 50) {
                alert('Please add more content to your post before generating AI suggestions. At least 50 characters are required.');
                return;
            }

            // Disable button and show loading
            $button.prop('disabled', true).addClass('seovela-ai-loading');
            $button.html('<span class="seovela-spinner"></span> Generating...');

            $.ajax({
                url: window.seovelaMetabox.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'seovela_generate_ai_content',
                    nonce: window.seovelaMetabox.aiNonce,
                    post_id: postId,
                    type: field,
                    content: content.substring(0, 3000),
                    focus_keyword: focusKeyword
                },
                success: function(response) {
                    if (response.success && response.data.content) {
                        var generatedContent = response.data.content;
                        
                        // Clean up any quotes or extra whitespace
                        generatedContent = generatedContent.replace(/^["']|["']$/g, '').trim();
                        
                        // Set the value and trigger updates
                        fieldInput.val(generatedContent).trigger('input');
                        
                        // Show success notification
                        self.showAINotification(fieldInput, 'success', field === 'title' ? 'Title generated!' : 'Description generated!');
                        
                        // Highlight the field briefly
                        fieldInput.addClass('seovela-ai-updated');
                        setTimeout(function() {
                            fieldInput.removeClass('seovela-ai-updated');
                        }, 2000);
                    } else {
                        var errorMsg = response.data && response.data.message ? response.data.message : 'Failed to generate content';
                        self.showAINotification($button, 'error', errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    var errorMsg = 'Network error. Please check your connection and try again.';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    }
                    self.showAINotification($button, 'error', errorMsg);
                },
                complete: function() {
                    $button.prop('disabled', false).removeClass('seovela-ai-loading');
                    $button.html(originalButtonText);
                }
            });
        },

        showAINotification: function($element, type, message) {
            // Remove any existing notifications
            $('.seovela-ai-notification').remove();
            
            var $notification = $('<div class="seovela-ai-notification seovela-ai-' + type + '">' +
                '<span class="dashicons dashicons-' + (type === 'success' ? 'yes-alt' : 'warning') + '"></span>' +
                '<span class="seovela-notification-text">' + message + '</span>' +
            '</div>');
            
            $element.closest('.seovela-field').append($notification);
            
            // Animate in
            setTimeout(function() {
                $notification.addClass('show');
            }, 10);
            
            // Auto-remove after 4 seconds
            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 4000);
        },

        suggestKeywords: function($button) {
            var self = this;
            var originalText = $button.html();

            // Get content
            var content = this.getEditorContent();
            var title = this.getPostTitle();

            if (!content && !title) {
                alert('Please add some content or a title first.');
                return;
            }

            $button.prop('disabled', true).html('<span class="seovela-spinner"></span>');

            $.ajax({
                url: window.seovelaMetabox.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'seovela_suggest_keywords',
                    nonce: window.seovelaMetabox.aiNonce,
                    content: content.substring(0, 3000),
                    title: title
                },
                success: function(response) {
                    if (response.success && response.data.keywords) {
                        var $suggestions = $('.seovela-keyword-suggestions');
                        var $list = $suggestions.find('.seovela-suggestions-list');
                        $list.empty();

                        response.data.keywords.forEach(function(keyword) {
                            $list.append('<span class="seovela-keyword-chip" data-keyword="' + keyword + '">' + keyword + '</span>');
                        });

                        $suggestions.slideDown();
                    } else {
                        alert(response.data.message || 'Failed to get keyword suggestions.');
                    }
                },
                error: function() {
                    alert('Error connecting to AI service.');
                },
                complete: function() {
                    $button.prop('disabled', false).html(originalText);
                }
            });
        },

        getEditorContent: function() {
            var content = '';
            
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                content = wp.data.select('core/editor').getEditedPostContent();
            } else if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                content = tinymce.get('content').getContent();
            } else {
                content = $('#content').val() || '';
            }

            // Strip HTML for text content
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = content;
            return tempDiv.textContent || tempDiv.innerText || '';
        },

        getPostTitle: function() {
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                return wp.data.select('core/editor').getEditedPostAttribute('title') || '';
                }
            return $('#title').val() || '';
        }
    };

    // Schema selector functionality
    var seovelaSchema = {
        initialized: false,
        
        schemaDescriptions: {
            'Article': 'Suitable for news articles, blog posts, and editorial content. Provides rich search results.',
            'FAQ': 'Frequently Asked Questions. Displays Q&A directly in search results with expandable answers.',
            'HowTo': 'Step-by-step instructions. Perfect for tutorials, recipes, and guides. Shows steps in search results.',
            'LocalBusiness': 'For local businesses. Shows business hours, location, contact info in search and maps. Uses Local SEO settings.',
            'Person': 'For author profiles and people pages. Shows person info, job title, and social profiles in knowledge panels.',
            'Product': 'For product pages. Shows price, availability, and ratings in search results. Not for WooCommerce (use WooCommerce schema).'
        },

        compatibilityRules: {
            'Article': ['FAQ', 'HowTo'],
            'FAQ': ['Article', 'LocalBusiness', 'Product'],
            'HowTo': ['Product'],
            'LocalBusiness': ['FAQ'],
            'Person': [],
            'Product': ['FAQ', 'HowTo']
        },

        init: function() {
            // Prevent duplicate initialization
            if (this.initialized) {
                return;
            }
            this.initialized = true;
            
            var self = this;
            
            // Main schema header toggle (collapse/expand the whole section)
            // Use .off().on() to prevent duplicate handlers
            $('#seovela-schema-header').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $header = $(this);
                var $content = $('#seovela-schema-content');
                
                $header.toggleClass('open');
                $content.slideToggle(300);
            });
            
            // Additional schema types accordion toggle
            $('.seovela-accordion-header').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $header = $(this);
                var targetId = $header.data('target');
                var $content = $('#' + targetId);
                
                $header.toggleClass('open');
                $content.toggleClass('open').slideToggle(250);
            });
            
            // Schema type-specific accordions (FAQ, HowTo, Product, Person)
            $(document).off('click.schemaAccordion').on('click.schemaAccordion', '.seovela-schema-accordion-header', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $header = $(this);
                var targetId = $header.data('target');
                var $content = $('#' + targetId);
                
                $header.toggleClass('open');
                $content.toggleClass('open').slideToggle(250);
            });
            
            // Schema type chip selection
            $(document).on('change', '.seovela-schema-type-chip input', function() {
                var $chip = $(this).closest('.seovela-schema-type-chip');
                
                if ($(this).is(':checked')) {
                    $chip.addClass('selected');
                } else {
                    $chip.removeClass('selected');
                }
                
                // Update badge count
                var selectedCount = $('.seovela-schema-type-chip.selected').length;
                $('.seovela-accordion-badge').text(selectedCount);
                
                self.checkCompatibility();
                self.toggleSchemaFields();
            });

            // Schema type selector change
            $('#seovela_schema_type').on('change', function() {
                self.updateSchemaDescription();
                self.toggleSchemaFields();
                self.checkCompatibility();
            });

            // Disable schema checkbox with status update
            $('#seovela_disable_schema').on('change', function() {
                var isDisabled = $(this).is(':checked');
                $('#seovela-schema-selector').slideToggle(!isDisabled);
                $('#seovela-schema-fields-wrapper').slideToggle(!isDisabled);
                
                // Update status badge
                var $status = $('.seovela-schema-status');
                if (isDisabled) {
                    $status.removeClass('active').addClass('disabled').text('Disabled');
                } else {
                    $status.removeClass('disabled').addClass('active').text('Active');
                }
            });

            // Additional schema types (legacy support)
            $('.seovela-additional-schema').on('change', function() {
                self.checkCompatibility();
                self.toggleSchemaFields();
            });

            // Preview schema button
            $('#seovela-preview-schema').on('click', function(e) {
                e.preventDefault();
                self.previewSchema();
            });
            
            // Close preview button
            $('#seovela-preview-close').on('click', function(e) {
                e.preventDefault();
                $('#seovela-schema-preview').slideUp(200);
            });

            // FAQ repeater
            $('.seovela-add-faq').on('click', function(e) {
                e.preventDefault();
                self.addFAQItem();
            });

            $(document).on('click', '.seovela-remove-faq', function(e) {
                e.preventDefault();
                $(this).closest('.seovela-faq-item').fadeOut(300, function() {
                    $(this).remove();
                    self.reindexFAQItems();
                    self.updateFAQCount();
                });
            });

            // HowTo repeater
            $('.seovela-add-howto-step').on('click', function(e) {
                e.preventDefault();
                self.addHowToStep();
            });

            $(document).on('click', '.seovela-remove-howto-step', function(e) {
                e.preventDefault();
                $(this).closest('.seovela-howto-step').fadeOut(300, function() {
                    $(this).remove();
                    self.reindexHowToSteps();
                    self.updateHowToCount();
                });
            });

            // Initial state
            this.updateSchemaDescription();
            this.toggleSchemaFields();
            this.checkCompatibility();
            
            // Initialize chip states
            $('.seovela-schema-type-chip input:checked').each(function() {
                $(this).closest('.seovela-schema-type-chip').addClass('selected');
            });
        },
        
        updateFAQCount: function() {
            var count = $('.seovela-faq-item').filter(function() {
                return $(this).find('.seovela-faq-question').val() !== '';
            }).length;
            
            $('.seovela-faq-fields .seovela-schema-count').text(count > 0 ? count + ' items' : '');
        },
        
        updateHowToCount: function() {
            var count = $('.seovela-howto-step').filter(function() {
                return $(this).find('.seovela-howto-step-name').val() !== '';
            }).length;
            
            $('.seovela-howto-fields .seovela-schema-count').text(count > 0 ? count + ' steps' : '');
        },

        updateSchemaDescription: function() {
            var selectedType = $('#seovela_schema_type').val();
            var description = '';
            
            if (selectedType && selectedType !== 'auto' && this.schemaDescriptions[selectedType]) {
                description = this.schemaDescriptions[selectedType];
            }
            
            if (description) {
                $('#seovela-schema-description').html(description).show();
            } else {
                $('#seovela-schema-description').hide();
            }
        },

        toggleSchemaFields: function() {
            var selectedType = $('#seovela_schema_type').val();
            
            // Hide all schema-specific accordions
            $('.seovela-schema-accordion').hide();
            
            // Show fields for selected type
            if (selectedType && selectedType !== 'auto') {
                $('.seovela-schema-accordion[data-schema="' + selectedType + '"]').show();
            }

            // Also check additional types
            $('.seovela-additional-schema:checked').each(function() {
                var type = $(this).data('schema-type');
                $('.seovela-schema-accordion[data-schema="' + type + '"]').show();
            });
        },

        checkCompatibility: function() {
            var primaryType = $('#seovela_schema_type').val();
            var warnings = [];
            
            if (!primaryType || primaryType === 'auto') {
                $('#seovela-schema-warnings').empty();
                return;
            }

            var compatibleTypes = this.compatibilityRules[primaryType] || [];
            
            $('.seovela-additional-schema:checked').each(function() {
                var additionalType = $(this).data('schema-type');
                
                if (additionalType === primaryType) {
                    return; // Skip same type
                }
                
                if (compatibleTypes.indexOf(additionalType) === -1) {
                    warnings.push(primaryType + ' and ' + additionalType + ' schemas may not be compatible. Choose one primary schema type.');
                }
            });

            // Display warnings
            if (warnings.length > 0) {
                var warningsHTML = '';
                warnings.forEach(function(warning) {
                    warningsHTML += '<div class="seovela-schema-warning">' + warning + '</div>';
                });
                $('#seovela-schema-warnings').html(warningsHTML).show();
            } else {
                $('#seovela-schema-warnings').empty().hide();
            }
        },

        previewSchema: function() {
            var self = this;
            var $button = $('#seovela-preview-schema');
            var $container = $('#seovela-schema-preview');
            
            // Get post ID
            var postId = $('#post_ID').val();
            
            if (!postId) {
                alert('Please save the post first before previewing schema.');
                return;
            }

            $button.prop('disabled', true).text('⏳ Loading...');

            $.ajax({
                url: seovelaMetabox.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'seovela_preview_schema',
                    nonce: seovelaMetabox.analysisNonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success && response.data.preview) {
                        $container.find('.seovela-schema-json').text(response.data.preview);
                        $container.slideDown(300);
                    } else {
                        alert('No schema data available. Please configure schema settings and save the post.');
                    }
                },
                error: function() {
                    alert('Error loading schema preview. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('👁 Preview Schema');
                }
            });
        },

        addFAQItem: function() {
            var $repeater = $('.seovela-faq-repeater');
            var index = $repeater.find('.seovela-faq-item').length;
            
            var html = '<div class="seovela-faq-item" data-index="' + index + '" style="display:none;">' +
                '<div class="seovela-faq-item-header">' +
                    '<span class="seovela-faq-item-number">' + (index + 1) + '</span>' +
                    '<button type="button" class="seovela-remove-faq" title="Remove">' +
                        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                            '<line x1="18" y1="6" x2="6" y2="18"/>' +
                            '<line x1="6" y1="6" x2="18" y2="18"/>' +
                        '</svg>' +
                    '</button>' +
                '</div>' +
                '<div class="seovela-faq-fields-group">' +
                    '<div class="seovela-input-wrapper">' +
                        '<label class="seovela-input-label">Question</label>' +
                        '<input type="text" name="seovela_faq_items[' + index + '][question]" value="" placeholder="e.g., What is your return policy?" class="seovela-input seovela-faq-question" />' +
                    '</div>' +
                    '<div class="seovela-input-wrapper">' +
                        '<label class="seovela-input-label">Answer</label>' +
                        '<textarea name="seovela_faq_items[' + index + '][answer]" rows="3" placeholder="Provide a clear and helpful answer..." class="seovela-textarea seovela-faq-answer"></textarea>' +
                    '</div>' +
                '</div>' +
            '</div>';
            
            $repeater.append(html);
            $repeater.find('.seovela-faq-item:last').fadeIn(300);
            this.updateFAQCount();
        },

        reindexFAQItems: function() {
            $('.seovela-faq-item').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('.seovela-faq-item-number').text(index + 1);
                $(this).find('input, textarea').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/\[\d+\]/, '[' + index + ']');
                        $(this).attr('name', name);
                    }
                });
            });
        },

        addHowToStep: function() {
            var $repeater = $('.seovela-howto-repeater');
            var index = $repeater.find('.seovela-howto-step').length;
            
            var html = '<div class="seovela-howto-step" data-index="' + index + '" style="display:none;">' +
                '<div class="seovela-howto-step-header">' +
                    '<span class="seovela-howto-step-number">' + (index + 1) + '</span>' +
                    '<button type="button" class="seovela-remove-howto-step" title="Remove">' +
                        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                            '<line x1="18" y1="6" x2="6" y2="18"/>' +
                            '<line x1="6" y1="6" x2="18" y2="18"/>' +
                        '</svg>' +
                    '</button>' +
                '</div>' +
                '<div class="seovela-howto-fields-group">' +
                    '<div class="seovela-input-wrapper">' +
                        '<label class="seovela-input-label">Step Title</label>' +
                        '<input type="text" name="seovela_howto_steps[' + index + '][name]" value="" placeholder="e.g., Mix the ingredients" class="seovela-input seovela-howto-step-name" />' +
                    '</div>' +
                    '<div class="seovela-input-wrapper">' +
                        '<label class="seovela-input-label">Instructions</label>' +
                        '<textarea name="seovela_howto_steps[' + index + '][text]" rows="2" placeholder="Describe what to do in this step..." class="seovela-textarea seovela-howto-step-text"></textarea>' +
                    '</div>' +
                '</div>' +
            '</div>';
            
            $repeater.append(html);
            $repeater.find('.seovela-howto-step:last').fadeIn(300);
            this.updateHowToCount();
        },

        reindexHowToSteps: function() {
            $('.seovela-howto-step').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('.seovela-howto-step-number').text(index + 1);
                $(this).find('input, textarea').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/\[\d+\]/, '[' + index + ']');
                        $(this).attr('name', name);
                    }
                });
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        seovelaMetabox.init();
        seovelaSchema.init();
    });

    // Also initialize for Gutenberg
    if (typeof wp !== 'undefined' && wp.domReady) {
        wp.domReady(function() {
            setTimeout(function() {
                seovelaMetabox.init();
                seovelaSchema.init();
            }, 1000);
        });
    }

})(jQuery);


