/**
 * Seovela AI Editor - Classic Editor Integration with Streaming
 *
 * @package Seovela
 */

(function($) {
    'use strict';

    var SeovelaAIEditor = {
        panel: null,
        fab: null,
        isOpen: false,
        isStreaming: false,
        streamedContent: '',

        init: function() {
            this.panel = $('#seovela-ai-panel');
            this.fab = $('#seovela-ai-fab');
            
            if (!this.panel.length || !this.fab.length) {
                return;
            }

            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            // Toggle panel
            this.fab.on('click', function() {
                self.togglePanel();
            });

            // Close panel
            $('.seovela-ai-panel-close').on('click', function() {
                self.closePanel();
            });

            // Tab switching
            $('.seovela-ai-panel-tab').on('click', function() {
                var tab = $(this).data('tab');
                $('.seovela-ai-panel-tab').removeClass('active');
                $(this).addClass('active');
                $('.seovela-ai-panel-content').removeClass('active');
                $('.seovela-ai-panel-content[data-tab="' + tab + '"]').addClass('active');
            });

            // Improve actions
            $('.seovela-ai-btn').on('click', function() {
                var action = $(this).data('action');
                self.streamImproveContent(action);
            });

            // Generate content
            $('.seovela-ai-generate').on('click', function() {
                self.streamWriteContent();
            });

            // Output actions
            $('.seovela-ai-insert-content').on('click', function() {
                self.insertContent(false);
            });

            $('.seovela-ai-replace-content').on('click', function() {
                self.insertContent(true);
            });

            $('.seovela-ai-discard-content').on('click', function() {
                self.discardContent();
            });

            // Close on escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.isOpen) {
                    self.closePanel();
                }
            });
        },

        togglePanel: function() {
            if (this.isOpen) {
                this.closePanel();
            } else {
                this.openPanel();
            }
        },

        openPanel: function() {
            this.panel.fadeIn(200);
            this.fab.addClass('active');
            this.isOpen = true;
        },

        closePanel: function() {
            this.panel.fadeOut(200);
            this.fab.removeClass('active');
            this.isOpen = false;
        },

        showLoading: function(message) {
            message = message || 'AI is thinking...';
            $('.seovela-ai-panel-content').hide();
            $('.seovela-ai-panel-output').hide();
            $('.seovela-ai-panel-loading p').text(message);
            $('.seovela-ai-panel-loading').show();
        },

        hideLoading: function() {
            $('.seovela-ai-panel-loading').hide();
            $('.seovela-ai-panel-content.active').show();
        },

        showStreamingOutput: function() {
            this.streamedContent = '';
            $('.seovela-ai-panel-output-content').html('<span class="seovela-streaming-cursor"></span>');
            $('.seovela-ai-panel-content').hide();
            $('.seovela-ai-panel-loading').hide();
            $('.seovela-ai-panel-output').show();
        },

        appendStreamedContent: function(text) {
            this.streamedContent += text;
            // Update display with cursor
            var displayContent = this.streamedContent + '<span class="seovela-streaming-cursor"></span>';
            $('.seovela-ai-panel-output-content').html(displayContent);
            
            // Auto-scroll to bottom
            var $content = $('.seovela-ai-panel-output-content');
            $content.scrollTop($content[0].scrollHeight);
        },

        finishStreaming: function() {
            // Remove cursor
            $('.seovela-streaming-cursor').remove();
            this.isStreaming = false;
            $('.seovela-ai-btn, .seovela-ai-generate').prop('disabled', false);
        },

        discardContent: function() {
            this.streamedContent = '';
            $('.seovela-ai-panel-output').hide();
            $('.seovela-ai-panel-output-content').empty();
            $('.seovela-ai-panel-content.active').show();
        },

        getEditorContent: function() {
            // Check if TinyMCE is active
            if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                var editor = tinymce.get('content');
                
                // Check if there's selected content
                var selectedContent = editor.selection.getContent();
                if (selectedContent && selectedContent.length > 0) {
                    return {
                        content: selectedContent,
                        isSelection: true
                    };
                }
                
                return {
                    content: editor.getContent(),
                    isSelection: false
                };
            }
            
            // Fallback to textarea
            var $textarea = $('#content');
            var content = $textarea.val() || '';
            
            return {
                content: content,
                isSelection: false
            };
        },

        /**
         * Stream improve content using SSE
         */
        streamImproveContent: function(actionType) {
            var self = this;
            var editorData = this.getEditorContent();
            
            if (!editorData.content || editorData.content.length < 20) {
                alert('Please add more content to improve (at least 20 characters).');
                return;
            }

            if (this.isStreaming) {
                return;
            }

            this.isStreaming = true;
            $('.seovela-ai-btn, .seovela-ai-generate').prop('disabled', true);
            this.showStreamingOutput();

            this.streamFromAPI({
                action_type: actionType,
                content: editorData.content,
                focus_keyword: ''
            });
        },

        /**
         * Stream write content using SSE
         */
        streamWriteContent: function() {
            var self = this;
            var topic = $('#seovela-ai-topic').val();
            var contentType = $('#seovela-ai-type').val();
            var tone = $('#seovela-ai-tone').val();

            if (!topic) {
                alert('Please enter a topic to write about.');
                $('#seovela-ai-topic').focus();
                return;
            }

            if (this.isStreaming) {
                return;
            }

            this.isStreaming = true;
            $('.seovela-ai-btn, .seovela-ai-generate').prop('disabled', true);
            this.showStreamingOutput();

            this.streamFromAPI({
                action_type: 'write',
                topic: topic,
                content_type: contentType,
                tone: tone,
                focus_keyword: ''
            });
        },

        /**
         * Stream from REST API using fetch and ReadableStream
         */
        streamFromAPI: function(params) {
            var self = this;
            var url = seovelaAI.restUrl + 'seovela/v1/ai-stream';

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': seovelaAI.restNonce
                },
                body: JSON.stringify(params)
            }).then(function(response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                if (!response.body) {
                    throw new Error('Streaming not supported');
                }

                var reader = response.body.getReader();
                var decoder = new TextDecoder();

                function readStream() {
                    reader.read().then(function(result) {
                        if (result.done) {
                            self.finishStreaming();
                            return;
                        }

                        var chunk = decoder.decode(result.value, { stream: true });
                        var lines = chunk.split('\n');

                        for (var i = 0; i < lines.length; i++) {
                            var line = lines[i].trim();
                            
                            if (!line.startsWith('data:')) {
                                continue;
                            }

                            var data = line.substring(5).trim();
                            
                            if (data === '[DONE]') {
                                self.finishStreaming();
                                return;
                            }

                            try {
                                var parsed = JSON.parse(data);
                                
                                if (parsed.error) {
                                    alert(parsed.error);
                                    self.finishStreaming();
                                    self.hideLoading();
                                    return;
                                }

                                if (parsed.content) {
                                    self.appendStreamedContent(parsed.content);
                                }
                            } catch (e) {
                                // Skip invalid JSON
                            }
                        }

                        // Continue reading
                        readStream();
                    }).catch(function(error) {
                        console.error('Stream read error:', error);
                        self.finishStreaming();
                        alert('Error reading stream.');
                    });
                }

                readStream();

            }).catch(function(error) {
                console.error('Fetch error:', error);
                self.finishStreaming();
                self.hideLoading();
                alert('Error connecting to AI service.');
            });
        },

        insertContent: function(replaceAll) {
            var content = this.streamedContent;
            
            if (!content) {
                return;
            }

            if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                var editor = tinymce.get('content');
                
                if (replaceAll) {
                    editor.setContent(content);
                } else {
                    // Insert at cursor position or at end
                    editor.execCommand('mceInsertContent', false, content);
                }
            } else {
                // Plain textarea
                var $textarea = $('#content');
                if (replaceAll) {
                    $textarea.val(content);
                } else {
                    var currentContent = $textarea.val();
                    $textarea.val(currentContent + '\n\n' + content);
                }
            }

            // Hide output and show success
            this.discardContent();
            this.closePanel();
            
            // Show a brief success message
            this.showNotification('Content added successfully!');
        },

        showNotification: function(message) {
            var $notification = $('<div class="seovela-ai-toast">' + message + '</div>');
            $('body').append($notification);
            
            setTimeout(function() {
                $notification.addClass('show');
            }, 10);
            
            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 3000);
        }
    };

    // Add toast notification and streaming cursor styles dynamically
    var dynamicStyles = '<style>' +
        '.seovela-ai-toast {' +
            'position: fixed;' +
            'bottom: 100px;' +
            'left: 50%;' +
            'transform: translateX(-50%) translateY(20px);' +
            'background: #10b981;' +
            'color: #ffffff;' +
            'padding: 12px 24px;' +
            'border-radius: 8px;' +
            'font-size: 14px;' +
            'font-weight: 600;' +
            'box-shadow: 0 4px 20px rgba(16, 185, 129, 0.4);' +
            'z-index: 999999;' +
            'opacity: 0;' +
            'transition: all 0.3s ease;' +
        '}' +
        '.seovela-ai-toast.show {' +
            'opacity: 1;' +
            'transform: translateX(-50%) translateY(0);' +
        '}' +
        '.seovela-streaming-cursor {' +
            'display: inline-block;' +
            'width: 2px;' +
            'height: 1em;' +
            'background: #6366f1;' +
            'margin-left: 2px;' +
            'animation: seovela-blink 0.7s infinite;' +
        '}' +
        '@keyframes seovela-blink {' +
            '0%, 50% { opacity: 1; }' +
            '51%, 100% { opacity: 0; }' +
        '}' +
    '</style>';
    $('head').append(dynamicStyles);

    // Initialize on document ready
    $(document).ready(function() {
        SeovelaAIEditor.init();
    });

})(jQuery);
