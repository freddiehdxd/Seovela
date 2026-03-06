/**
 * Seovela Metabox JavaScript
 *
 * Handles all metabox interactions: tabs, counters, previews,
 * SEO analysis, AI features (AJAX + streaming), schema, and editor integration.
 *
 * @package Seovela
 */

(function ($) {
    'use strict';

    /* ---------------------------------------------------------------
     * 0. Localized data alias
     * ------------------------------------------------------------- */
    var config = window.seovelaMetabox || {};

    /* ---------------------------------------------------------------
     * 1. Helpers
     * ------------------------------------------------------------- */
    var Helpers = {
        debounce: function (fn, delay) {
            var timer;
            return function () {
                var ctx = this, args = arguments;
                clearTimeout(timer);
                timer = setTimeout(function () { fn.apply(ctx, args); }, delay);
            };
        },

        stripHTML: function (html) {
            var tmp = document.createElement('div');
            tmp.innerHTML = html;
            return tmp.textContent || tmp.innerText || '';
        },

        getEditorContent: function () {
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                return wp.data.select('core/editor').getEditedPostContent();
            }
            if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                return tinymce.get('content').getContent();
            }
            return $('#content').val() || '';
        },

        getEditorText: function () {
            return Helpers.stripHTML(Helpers.getEditorContent());
        },

        getPostTitle: function () {
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                return wp.data.select('core/editor').getEditedPostAttribute('title') || '';
            }
            return $('#title').val() || '';
        },

        getPermalink: function () {
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                var url = wp.data.select('core/editor').getPermalink();
                if (url) return url;
            }
            var $sample = $('#sample-permalink');
            if ($sample.length) {
                var $a = $sample.find('a');
                if ($a.length) return $a.attr('href');
            }
            return $('.seovela-preview-url').text();
        },

        notify: function ($anchor, type, message) {
            $('.seovela-ai-notification').remove();
            var icon = type === 'success' ? 'yes-alt' : 'warning';
            var $el = $(
                '<div class="seovela-ai-notification seovela-ai-' + type + '">' +
                    '<span class="dashicons dashicons-' + icon + '"></span> ' + message +
                '</div>'
            );
            $anchor.closest('.seovela-field, .seovela-field-group, .seovela-ai-panel').append($el);
            setTimeout(function () { $el.addClass('show'); }, 10);
            setTimeout(function () {
                $el.removeClass('show');
                setTimeout(function () { $el.remove(); }, 300);
            }, 4000);
        }
    };

    /* ---------------------------------------------------------------
     * 2. Tabs
     * ------------------------------------------------------------- */
    var Tabs = {
        init: function () {
            // Main tabs (already handled by inline <script>; rebind for safety)
            $(document).on('click', '.seovela-tab-btn[data-tab]', function () {
                var tab = $(this).data('tab');
                var $box = $(this).closest('.seovela-metabox');
                $box.find('.seovela-tab-btn').removeClass('active');
                $(this).addClass('active');
                $box.find('.seovela-tab-panel').removeClass('active')
                    .filter('[data-panel="' + tab + '"]').addClass('active');
            });

            // Social sub-tabs
            $(document).on('click', '.seovela-social-tab[data-social]', function () {
                var key = $(this).data('social');
                var $wrap = $(this).closest('.seovela-tab-panel');
                $wrap.find('.seovela-social-tab').removeClass('active');
                $(this).addClass('active');
                $wrap.find('.seovela-social-panel').removeClass('active')
                    .filter('[data-social-panel="' + key + '"]').addClass('active');
            });

        }
    };

    /* ---------------------------------------------------------------
     * 3. Character Counters
     * ------------------------------------------------------------- */
    var Counters = {
        init: function () {
            this.update();
            $(document).on('input', '.seovela-title-input, .seovela-description-input', function () {
                Counters.update();
            });
        },

        update: function () {
            this._count('.seovela-title-input', 'title', 30, 60);
            this._count('.seovela-description-input', 'description', 120, 160);
        },

        _count: function (selector, field, min, max) {
            var $input = $(selector);
            if (!$input.length) return;
            var len = $input.val().length;
            var $counter = $('.seovela-count[data-field="' + field + '"]');
            var $status = $counter.siblings('.seovela-status');

            $counter.text(len)
                .removeClass('seovela-count-short seovela-count-good seovela-count-long');

            if (len < min) {
                $counter.addClass('seovela-count-short');
                $status.text('Too short');
            } else if (len > max) {
                $counter.addClass('seovela-count-long');
                $status.text('Too long');
            } else {
                $counter.addClass('seovela-count-good');
                $status.text('Good length');
            }
        }
    };

    /* ---------------------------------------------------------------
     * 4. Google Preview
     * ------------------------------------------------------------- */
    var Preview = {
        init: function () {
            this.update();
            $(document).on('input', '.seovela-title-input, .seovela-description-input', function () {
                Preview.update();
            });
        },

        update: function () {
            var title = $('.seovela-title-input').val() || '';
            var desc  = $('.seovela-description-input').val() || '';
            var url   = Helpers.getPermalink();

            $('.seovela-preview-title').text(title);
            $('.seovela-preview-description').text(desc);
            if (url) $('.seovela-preview-url').text(url);
        }
    };

    /* ---------------------------------------------------------------
     * 5. OG Preview
     * ------------------------------------------------------------- */
    var OGPreview = {
        init: function () {
            this.update();
            $(document).on(
                'input',
                '#seovela_og_title, #seovela_og_description, .seovela-title-input, .seovela-description-input',
                function () { OGPreview.update(); }
            );
        },

        update: function () {
            var ogTitle = $('#seovela_og_title').val() || $('.seovela-title-input').val() || '';
            var ogDesc  = $('#seovela_og_description').val() || $('.seovela-description-input').val() || '';
            $('.seovela-og-preview-title').text(ogTitle);
            $('.seovela-og-preview-desc').text(ogDesc);
        }
    };

    /* ---------------------------------------------------------------
     * 6. SEO Analysis
     * ------------------------------------------------------------- */
    var Analysis = {
        _timer: null,

        init: function () {
            var self = this;

            // Manual refresh
            $(document).on('click', '.seovela-refresh-analysis', function () {
                self.run();
            });

            // Toggle results panel
            $(document).on('click', '.seovela-toggle-analysis', function () {
                var $results = $('.seovela-analysis-results');
                var $btn = $(this);
                $results.slideToggle(300, function () {
                    $btn.text($results.is(':visible') ? 'Hide Analysis' : 'View Analysis');
                });
            });

            // Inputs schedule auto-analysis
            $(document).on(
                'input',
                '.seovela-title-input, .seovela-description-input, .seovela-keyword-input',
                Helpers.debounce(function () { self.run(); }, 1500)
            );

            // Animate score circle on load
            this._animateScore();
        },

        schedule: function () {
            var self = this;
            clearTimeout(this._timer);
            this._timer = setTimeout(function () { self.run(); }, 1500);
        },

        run: function () {
            if (!config.ajaxUrl) return;

            var data = {
                action:        'seovela_analyze_content',
                nonce:         config.analysisNonce,
                post_id:       $('#post_ID').val(),
                focus_keyword: $('.seovela-keyword-input').val(),
                title:         $('.seovela-title-input').val(),
                description:   $('.seovela-description-input').val(),
                content:       Helpers.getEditorContent(),
                url:           Helpers.getPermalink()
            };

            $.post(config.ajaxUrl, data, function (res) {
                if (res.success) Analysis._updateUI(res.data);
            });
        },

        _updateUI: function (d) {
            var score  = d.score || 0;
            var status = d.status || 'unknown';

            // Score number
            var $num = $('.seovela-score-number');
            $num.text(score);

            // Label
            var labels = { good: 'Good', warning: 'Needs Improvement', error: 'Poor' };
            $('.seovela-score-label').text(labels[status] || 'Unknown');

            // Circle
            var circumference = 339.29;
            var offset = circumference - (score / 100) * circumference;
            var colors = { good: '#10b981', warning: '#f59e0b', error: '#ef4444' };
            var color  = colors[status] || '#94a3b8';

            $('.seovela-score-progress').css('stroke-dashoffset', offset).attr('stroke', color);
            $('.seovela-score-circle').attr({ 'data-score': score, 'data-status': status });

            // Tab badge
            var $badge = $('.seovela-tab-score');
            $badge.find('.seovela-tab-score-number').text(score);
            $badge.css('color', color);

            // Results HTML
            if (d.errors || d.warnings || d.good) {
                var html = '';
                if (d.errors && d.errors.length) {
                    html += '<div class="seovela-analysis-section seovela-errors"><h4>Errors (' + d.errors.length + ')</h4><ul>';
                    $.each(d.errors, function (_, m) { html += '<li>' + $('<span>').text(m).html() + '</li>'; });
                    html += '</ul></div>';
                }
                if (d.warnings && d.warnings.length) {
                    html += '<div class="seovela-analysis-section seovela-warnings"><h4>Warnings (' + d.warnings.length + ')</h4><ul>';
                    $.each(d.warnings, function (_, m) { html += '<li>' + $('<span>').text(m).html() + '</li>'; });
                    html += '</ul></div>';
                }
                if (d.good && d.good.length) {
                    html += '<div class="seovela-analysis-section seovela-good"><h4>Good (' + d.good.length + ')</h4><ul>';
                    $.each(d.good, function (_, m) { html += '<li>' + $('<span>').text(m).html() + '</li>'; });
                    html += '</ul></div>';
                }
                $('.seovela-analysis-results').html(html);
            }
        },

        _animateScore: function () {
            var $circle = $('.seovela-score-circle');
            if (!$circle.length) return;
            var score = parseInt($circle.attr('data-score'), 10) || 0;
            var circumference = 339.29;
            var offset = circumference - (score / 100) * circumference;
            $('.seovela-score-progress').css('stroke-dashoffset', circumference);
            setTimeout(function () {
                $('.seovela-score-progress').css('stroke-dashoffset', offset);
            }, 150);
        }
    };

    /* ---------------------------------------------------------------
     * 7. AI – Keyword Suggestions
     * ------------------------------------------------------------- */
    var AIKeywords = {
        init: function () {
            if (!config.aiEnabled) return;

            $(document).on('click', '.seovela-suggest-keywords', function (e) {
                e.preventDefault();
                AIKeywords.suggest($(this));
            });

            $(document).on('click', '.seovela-keyword-chip', function (e) {
                e.preventDefault();
                $('.seovela-keyword-input').val($(this).data('keyword')).trigger('input');
                $('.seovela-keyword-suggestions').slideUp();
            });
        },

        suggest: function ($btn) {
            var text  = Helpers.getEditorText();
            var title = Helpers.getPostTitle();
            if (!text && !title) { alert('Please add some content or a title first.'); return; }

            var original = $btn.html();
            $btn.prop('disabled', true).html('<span class="seovela-spinner"></span>');

            $.post(config.ajaxUrl, {
                action: 'seovela_suggest_keywords',
                nonce:  config.aiNonce,
                content: text.substring(0, 3000),
                title:   title
            }, function (res) {
                if (res.success && res.data.keywords) {
                    var $list = $('.seovela-suggestions-list').empty();
                    $.each(res.data.keywords, function (_, kw) {
                        $list.append(
                            '<span class="seovela-keyword-chip" data-keyword="' +
                            $('<span>').text(kw).html() + '">' + $('<span>').text(kw).html() + '</span>'
                        );
                    });
                    $('.seovela-keyword-suggestions').slideDown();
                } else {
                    alert((res.data && res.data.message) || 'Failed to get keyword suggestions.');
                }
            }).fail(function () {
                alert('Error connecting to AI service.');
            }).always(function () {
                $btn.prop('disabled', false).html(original);
            });
        }
    };

    /* ---------------------------------------------------------------
     * 8. AI – Generate Title / Description
     * ------------------------------------------------------------- */
    var AIOptimize = {
        init: function () {
            if (!config.aiEnabled) return;

            $(document).on('click', '.seovela-ai-optimize[data-field]', function (e) {
                e.preventDefault();
                AIOptimize.generate($(this).data('field'), $(this));
            });
        },

        generate: function (field, $btn) {
            var text = Helpers.getEditorText();
            if (!text || text.trim().length < 50) {
                alert('Please add more content (at least 50 characters) before generating AI suggestions.');
                return;
            }

            var $input = field === 'title' ? $('.seovela-title-input') : $('.seovela-description-input');
            var original = $btn.html();
            $btn.prop('disabled', true).html('<span class="seovela-spinner"></span> Generating...');

            $.post(config.ajaxUrl, {
                action:        'seovela_generate_ai_content',
                nonce:         config.aiNonce,
                post_id:       $('#post_ID').val(),
                type:          field,
                content:       text.substring(0, 3000),
                focus_keyword: $('.seovela-keyword-input').val()
            }, function (res) {
                if (res.success && res.data.content) {
                    var val = res.data.content.replace(/^["']|["']$/g, '').trim();
                    $input.val(val).trigger('input');
                    $input.addClass('seovela-ai-updated');
                    setTimeout(function () { $input.removeClass('seovela-ai-updated'); }, 2000);
                    Helpers.notify($btn, 'success', field === 'title' ? 'Title generated!' : 'Description generated!');
                } else {
                    Helpers.notify($btn, 'error', (res.data && res.data.message) || 'Failed to generate content');
                }
            }).fail(function (xhr) {
                var msg = 'Network error. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.data) msg = xhr.responseJSON.data.message || msg;
                Helpers.notify($btn, 'error', msg);
            }).always(function () {
                $btn.prop('disabled', false).html(original);
            });
        }
    };

    /* ---------------------------------------------------------------
     * 9. Robot Chips
     * ------------------------------------------------------------- */
    var RobotChips = {
        init: function () {
            $(document).on('change', '.seovela-robot-chip input[type="checkbox"]', function () {
                $(this).closest('.seovela-robot-chip').toggleClass('checked', this.checked);
            });
        }
    };

    /* ---------------------------------------------------------------
     * 10. Media Uploader (OG Image)
     * ------------------------------------------------------------- */
    var MediaUploader = {
        frame: null,

        init: function () {
            var self = this;
            $(document).on('click', '.seovela-upload-og-image', function (e) {
                e.preventDefault();
                self.open();
            });
        },

        open: function () {
            if (this.frame) { this.frame.open(); return; }

            this.frame = wp.media({
                title:    'Select OG Image',
                button:   { text: 'Use this image' },
                multiple: false,
                library:  { type: 'image' }
            });

            this.frame.on('select', function () {
                var attachment = this.frame.state().get('selection').first().toJSON();
                $('#seovela_og_image').val(attachment.url).trigger('input');

                // Update preview image
                var $imgWrap = $('.seovela-og-preview-image');
                $imgWrap.html('<img src="' + attachment.url + '" alt="" />');
            }.bind(this));

            this.frame.open();
        }
    };

    /* ---------------------------------------------------------------
     * 11. Editor Integration (Gutenberg + TinyMCE auto-analysis)
     * ------------------------------------------------------------- */
    var EditorBridge = {
        init: function () {
            var scheduleAnalysis = Helpers.debounce(function () {
                Analysis.schedule();
            }, 2000);

            // Gutenberg subscribe
            if (typeof wp !== 'undefined' && wp.data && wp.data.subscribe) {
                var prevContent = '';
                var prevTitle   = '';
                try {
                    prevContent = wp.data.select('core/editor').getEditedPostContent();
                    prevTitle   = wp.data.select('core/editor').getEditedPostAttribute('title');
                } catch (_) { /* editor not ready */ }

                wp.data.subscribe(function () {
                    try {
                        var c = wp.data.select('core/editor').getEditedPostContent();
                        var t = wp.data.select('core/editor').getEditedPostAttribute('title');
                        if (c !== prevContent || t !== prevTitle) {
                            prevContent = c;
                            prevTitle   = t;
                            Preview.update();
                            scheduleAnalysis();
                        }
                    } catch (_) {}
                });
            }

            // Classic Editor – TinyMCE
            $(document).on('tinymce-editor-init', function (_e, editor) {
                if (editor.id === 'content') {
                    editor.on('keyup change paste setcontent input', scheduleAnalysis);
                }
            });
            if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                tinymce.get('content').on('keyup change paste setcontent input', scheduleAnalysis);
            }

            // Classic fallback textarea
            $(document).on('input', '#content', scheduleAnalysis);
        }
    };

    /* ---------------------------------------------------------------
     * 12. Schema
     * ------------------------------------------------------------- */
    var Schema = {
        initialized: false,

        descriptions: {
            'Article':       'Suitable for news articles, blog posts, and editorial content.',
            'FAQ':           'Frequently Asked Questions. Displays Q&A directly in search results.',
            'HowTo':         'Step-by-step instructions for tutorials, recipes, and guides.',
            'LocalBusiness': 'For local businesses. Shows hours, location, contact info in search and maps.',
            'Person':        'For author profiles and people pages. Shows person info in knowledge panels.',
            'Product':       'For product pages. Shows price, availability, and ratings in search results.'
        },

        compatibilityRules: {
            'Article': ['FAQ', 'HowTo'],
            'FAQ': ['Article', 'LocalBusiness', 'Product'],
            'HowTo': ['Product'],
            'LocalBusiness': ['FAQ'],
            'Person': [],
            'Product': ['FAQ', 'HowTo']
        },

        init: function () {
            if (this.initialized) return;
            this.initialized = true;
            var self = this;

            // Header toggle
            $('#seovela-schema-header').on('click', function (e) {
                e.preventDefault();
                $(this).toggleClass('open');
                $('#seovela-schema-content').slideToggle(300);
            });

            // Accordion headers
            $(document).on('click', '.seovela-accordion-header', function (e) {
                e.preventDefault();
                $(this).toggleClass('open');
                $('#' + $(this).data('target')).toggleClass('open').slideToggle(250);
            });

            $(document).on('click', '.seovela-schema-accordion-header', function (e) {
                e.preventDefault();
                $(this).toggleClass('open');
                $('#' + $(this).data('target')).toggleClass('open').slideToggle(250);
            });

            // Type chip selection
            $(document).on('change', '.seovela-schema-type-chip input', function () {
                $(this).closest('.seovela-schema-type-chip').toggleClass('selected', this.checked);
                var count = $('.seovela-schema-type-chip.selected').length;
                $('.seovela-accordion-badge').text(count);
                self._checkCompat();
                self._toggleFields();
            });

            // Primary selector
            $('#seovela_schema_type').on('change', function () {
                self._updateDesc();
                self._toggleFields();
                self._checkCompat();
            });

            // Disable toggle
            $('#seovela_disable_schema').on('change', function () {
                var off = $(this).is(':checked');
                $('#seovela-schema-selector, #seovela-schema-fields-wrapper').slideToggle(!off);
                var $s = $('.seovela-schema-status');
                $s.toggleClass('active', !off).toggleClass('disabled', off)
                    .text(off ? 'Disabled' : 'Active');
            });

            // Preview
            $('#seovela-preview-schema').on('click', function (e) { e.preventDefault(); self._preview(); });
            $('#seovela-preview-close').on('click', function (e) { e.preventDefault(); $('#seovela-schema-preview').slideUp(200); });

            // FAQ repeater
            $(document).on('click', '.seovela-add-faq', function (e) { e.preventDefault(); self._addFAQ(); });
            $(document).on('click', '.seovela-remove-faq', function (e) {
                e.preventDefault();
                $(this).closest('.seovela-faq-item').fadeOut(300, function () { $(this).remove(); self._reindex('.seovela-faq-item', '.seovela-faq-item-number'); });
            });

            // HowTo repeater
            $(document).on('click', '.seovela-add-howto-step', function (e) { e.preventDefault(); self._addStep(); });
            $(document).on('click', '.seovela-remove-howto-step', function (e) {
                e.preventDefault();
                $(this).closest('.seovela-howto-step').fadeOut(300, function () { $(this).remove(); self._reindex('.seovela-howto-step', '.seovela-howto-step-number'); });
            });

            // Init chips
            $('.seovela-schema-type-chip input:checked').each(function () {
                $(this).closest('.seovela-schema-type-chip').addClass('selected');
            });

            this._updateDesc();
            this._toggleFields();
            this._checkCompat();
        },

        _updateDesc: function () {
            var type = $('#seovela_schema_type').val();
            var desc = (type && type !== 'auto') ? (this.descriptions[type] || '') : '';
            var $el  = $('#seovela-schema-description');
            desc ? $el.html(desc).show() : $el.hide();
        },

        _toggleFields: function () {
            var type = $('#seovela_schema_type').val();
            $('.seovela-schema-accordion').hide();
            if (type && type !== 'auto') {
                $('.seovela-schema-accordion[data-schema="' + type + '"]').show();
            }
            $('.seovela-additional-schema:checked').each(function () {
                $('.seovela-schema-accordion[data-schema="' + $(this).data('schema-type') + '"]').show();
            });
        },

        _checkCompat: function () {
            var primary = $('#seovela_schema_type').val();
            if (!primary || primary === 'auto') { $('#seovela-schema-warnings').empty().hide(); return; }
            var compat = this.compatibilityRules[primary] || [];
            var warnings = [];
            $('.seovela-additional-schema:checked').each(function () {
                var t = $(this).data('schema-type');
                if (t !== primary && compat.indexOf(t) === -1) {
                    warnings.push(primary + ' and ' + t + ' schemas may not be compatible.');
                }
            });
            var $w = $('#seovela-schema-warnings');
            if (warnings.length) {
                $w.html(warnings.map(function (w) { return '<div class="seovela-schema-warning">' + w + '</div>'; }).join('')).show();
            } else {
                $w.empty().hide();
            }
        },

        _preview: function () {
            var postId = $('#post_ID').val();
            if (!postId) { alert('Please save the post first.'); return; }
            var $btn = $('#seovela-preview-schema').prop('disabled', true).text('Loading...');
            $.post(config.ajaxUrl, {
                action: 'seovela_preview_schema', nonce: config.analysisNonce, post_id: postId
            }, function (res) {
                if (res.success && res.data.preview) {
                    $('#seovela-schema-preview').find('.seovela-schema-json').text(res.data.preview).end().slideDown(300);
                } else {
                    alert('No schema data available.');
                }
            }).always(function () { $btn.prop('disabled', false).text('Preview Schema'); });
        },

        _addFAQ: function () {
            var i = $('.seovela-faq-repeater .seovela-faq-item').length;
            var html =
                '<div class="seovela-faq-item" data-index="' + i + '" style="display:none">' +
                    '<div class="seovela-faq-item-header"><span class="seovela-faq-item-number">' + (i + 1) + '</span>' +
                        '<button type="button" class="seovela-remove-faq" title="Remove"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>' +
                    '<div class="seovela-faq-fields-group">' +
                        '<div class="seovela-input-wrapper"><label class="seovela-input-label">Question</label><input type="text" name="seovela_faq_items[' + i + '][question]" value="" placeholder="e.g., What is your return policy?" class="seovela-input seovela-faq-question"/></div>' +
                        '<div class="seovela-input-wrapper"><label class="seovela-input-label">Answer</label><textarea name="seovela_faq_items[' + i + '][answer]" rows="3" placeholder="Provide a clear and helpful answer..." class="seovela-textarea seovela-faq-answer"></textarea></div>' +
                    '</div></div>';
            $('.seovela-faq-repeater').append(html).find('.seovela-faq-item:last').fadeIn(300);
        },

        _addStep: function () {
            var i = $('.seovela-howto-repeater .seovela-howto-step').length;
            var html =
                '<div class="seovela-howto-step" data-index="' + i + '" style="display:none">' +
                    '<div class="seovela-howto-step-header"><span class="seovela-howto-step-number">' + (i + 1) + '</span>' +
                        '<button type="button" class="seovela-remove-howto-step" title="Remove"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>' +
                    '<div class="seovela-howto-fields-group">' +
                        '<div class="seovela-input-wrapper"><label class="seovela-input-label">Step Title</label><input type="text" name="seovela_howto_steps[' + i + '][name]" value="" placeholder="e.g., Mix the ingredients" class="seovela-input seovela-howto-step-name"/></div>' +
                        '<div class="seovela-input-wrapper"><label class="seovela-input-label">Instructions</label><textarea name="seovela_howto_steps[' + i + '][text]" rows="2" placeholder="Describe what to do in this step..." class="seovela-textarea seovela-howto-step-text"></textarea></div>' +
                    '</div></div>';
            $('.seovela-howto-repeater').append(html).find('.seovela-howto-step:last').fadeIn(300);
        },

        _reindex: function (itemSel, numSel) {
            $(itemSel).each(function (idx) {
                $(this).attr('data-index', idx).find(numSel).text(idx + 1);
                $(this).find('input, textarea').each(function () {
                    var name = $(this).attr('name');
                    if (name) $(this).attr('name', name.replace(/\[\d+\]/, '[' + idx + ']'));
                });
            });
        }
    };

    /* ---------------------------------------------------------------
     * 13. Boot
     * ------------------------------------------------------------- */
    function boot() {
        Tabs.init();
        Counters.init();
        Preview.init();
        OGPreview.init();
        Analysis.init();
        AIKeywords.init();
        AIOptimize.init();
        RobotChips.init();
        MediaUploader.init();
        EditorBridge.init();
        Schema.init();
    }

    $(document).ready(boot);

})(jQuery);
