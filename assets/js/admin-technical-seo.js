/**
 * Technical SEO Admin JavaScript
 * Handles Redirects & 404 Monitor interactions
 *
 * @package Seovela
 */

(function($) {
    'use strict';

    // =========================================================================
    // REDIRECTS MANAGER
    // =========================================================================

    var redirectsManager = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            // Add redirect button
            $(document).on('click', '.seovela-add-redirect-btn', function(e) {
                e.preventDefault();
                self.openAddModal();
            });

            // Edit redirect button
            $(document).on('click', '.seovela-edit-redirect', function() {
                var redirectId = $(this).data('redirect-id');
                self.openEditModal(redirectId);
            });

            // Delete redirect button
            $(document).on('click', '.seovela-delete-redirect', function() {
                if (confirm('Are you sure you want to delete this redirect?')) {
                    var redirectId = $(this).data('redirect-id');
                    self.deleteRedirect(redirectId, $(this).closest('tr'));
                }
            });

            // Toggle redirect enabled status
            $(document).on('change', '.seovela-toggle-redirect', function() {
                var redirectId = $(this).data('redirect-id');
                var enabled = $(this).is(':checked') ? 1 : 0;
                self.toggleRedirect(redirectId, enabled, $(this));
            });

            // Submit redirect form
            $('#seovela-redirect-form').on('submit', function(e) {
                e.preventDefault();
                self.saveRedirect();
            });

            // Export redirects
            $('.seovela-export-redirects').on('click', function() {
                self.exportRedirects();
            });

            // Import redirects
            $('.seovela-import-redirects').on('click', function() {
                $('#seovela-import-modal').addClass('active');
            });

            // Submit import form
            $('#seovela-import-form').on('submit', function(e) {
                e.preventDefault();
                self.importRedirects();
            });

            // Modal close buttons
            $('.seovela-modal-close').on('click', function() {
                $(this).closest('.seovela-modal').removeClass('active');
            });

            // Close modal on outside click
            $('.seovela-modal').on('click', function(e) {
                if ($(e.target).is('.seovela-modal')) {
                    $(this).removeClass('active');
                }
            });
        },

        openAddModal: function() {
            $('#seovela-modal-title').text('Add Redirect');
            $('#redirect-id').val('');
            $('#source-url').val('');
            $('#target-url').val('');
            $('#redirect-type').val('301');
            $('#regex-enabled').prop('checked', false);
            $('#enabled').prop('checked', true);
            $('#seovela-redirect-modal').addClass('active');
        },

        openEditModal: function(redirectId) {
            var $row = $('tr[data-redirect-id="' + redirectId + '"]');
            var sourceUrl = $row.find('.column-source strong').text().trim();
            var targetUrl = $row.find('.column-target a').attr('href');
            var redirectType = $row.find('.seovela-badge-301, .seovela-badge-302, .seovela-badge-307').text().trim();
            var isRegex = $row.find('.seovela-badge-regex').length > 0;
            var isEnabled = $row.find('.seovela-toggle-redirect').is(':checked');

            $('#seovela-modal-title').text('Edit Redirect');
            $('#redirect-id').val(redirectId);
            $('#source-url').val(sourceUrl);
            $('#target-url').val(targetUrl);
            $('#redirect-type').val(redirectType);
            $('#regex-enabled').prop('checked', isRegex);
            $('#enabled').prop('checked', isEnabled);
            $('#seovela-redirect-modal').addClass('active');
        },

        saveRedirect: function() {
            var self = this;
            var $form = $('#seovela-redirect-form');
            var $button = $form.find('button[type="submit"]');
            var redirectId = $('#redirect-id').val();
            var action = redirectId ? 'seovela_edit_redirect' : 'seovela_add_redirect';

            var data = {
                action: action,
                nonce: seovelaRedirects.nonce,
                redirect_id: redirectId,
                source_url: $('#source-url').val(),
                target_url: $('#target-url').val(),
                redirect_type: $('#redirect-type').val(),
                regex: $('#regex-enabled').is(':checked') ? 1 : 0,
                enabled: $('#enabled').is(':checked') ? 1 : 0
            };

            $button.prop('disabled', true).text('Saving...');

            $.ajax({
                url: seovelaRedirects.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        self.showNotice(response.data.message, 'success');
                        $('#seovela-redirect-modal').removeClass('active');
                        // Reload page to show changes
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        self.showNotice(response.data.message || 'An error occurred', 'error');
                    }
                },
                error: function() {
                    self.showNotice('Failed to save redirect. Please try again.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Save Redirect');
                }
            });
        },

        deleteRedirect: function(redirectId, $row) {
            var self = this;

            $.ajax({
                url: seovelaRedirects.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'seovela_delete_redirect',
                    nonce: seovelaRedirects.nonce,
                    redirect_id: redirectId
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                        });
                        self.showNotice(response.data.message, 'success');
                    } else {
                        self.showNotice(response.data.message || 'Failed to delete redirect', 'error');
                    }
                },
                error: function() {
                    self.showNotice('An error occurred. Please try again.', 'error');
                }
            });
        },

        toggleRedirect: function(redirectId, enabled, $checkbox) {
            var self = this;

            $.ajax({
                url: seovelaRedirects.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'seovela_toggle_redirect',
                    nonce: seovelaRedirects.nonce,
                    redirect_id: redirectId,
                    enabled: enabled
                },
                success: function(response) {
                    if (response.success) {
                        $checkbox.closest('tr').toggleClass('disabled', !enabled);
                        self.showNotice(response.data.message, 'success');
                    } else {
                        // Revert checkbox on error
                        $checkbox.prop('checked', !enabled);
                        self.showNotice(response.data.message || 'Failed to update status', 'error');
                    }
                },
                error: function() {
                    // Revert checkbox on error
                    $checkbox.prop('checked', !enabled);
                    self.showNotice('An error occurred. Please try again.', 'error');
                }
            });
        },

        exportRedirects: function() {
            window.location.href = seovelaRedirects.ajaxUrl + '?action=seovela_export_redirects&nonce=' + seovelaRedirects.nonce;
        },

        importRedirects: function() {
            var self = this;
            var $form = $('#seovela-import-form');
            var $button = $form.find('button[type="submit"]');
            var formData = new FormData();
            var file = $('#csv-file')[0].files[0];

            if (!file) {
                self.showNotice('Please select a CSV file', 'error');
                return;
            }

            formData.append('action', 'seovela_import_redirects');
            formData.append('nonce', seovelaRedirects.nonce);
            formData.append('csv_file', file);

            $button.prop('disabled', true).text('Importing...');

            $.ajax({
                url: seovelaRedirects.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        var message = response.data.message;
                        if (response.data.errors && response.data.errors.length > 0) {
                            message += '<br><br>Errors:<br>' + response.data.errors.join('<br>');
                        }
                        self.showNotice(message, 'info');
                        $('#seovela-import-modal').removeClass('active');
                        // Reload page
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        self.showNotice(response.data.message || 'Import failed', 'error');
                    }
                },
                error: function() {
                    self.showNotice('An error occurred during import', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Import');
                }
            });
        },

        showNotice: function(message, type) {
            var $notice = $('<div class="seovela-notice seovela-notice-' + type + '">' + message + '</div>');
            $('.seovela-redirects-page h1').after($notice);
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // =========================================================================
    // 404 MONITOR
    // =========================================================================

    var monitor404 = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            // Delete 404 log
            $(document).on('click', '.seovela-delete-404', function() {
                if (confirm('Are you sure you want to delete this log?')) {
                    var logId = $(this).data('log-id');
                    self.deleteLog(logId, $(this).closest('tr'));
                }
            });

            // Resolve 404
            $(document).on('click', '.seovela-resolve-404', function() {
                var logId = $(this).data('log-id');
                self.resolveLog(logId, $(this).closest('tr'));
            });

            // Create redirect from 404
            $(document).on('click', '.seovela-create-redirect', function() {
                var logId = $(this).data('log-id');
                var sourceUrl = $(this).data('source-url');
                self.openCreateRedirectModal(logId, sourceUrl);
            });

            // Submit create redirect form
            $('#seovela-create-redirect-form').on('submit', function(e) {
                e.preventDefault();
                self.createRedirectFrom404();
            });

            // Cleanup resolved logs
            $('.seovela-cleanup-404').on('click', function() {
                if (confirm('Are you sure you want to delete all resolved 404 logs?')) {
                    self.cleanupResolved();
                }
            });

            // Open settings panel
            $('.seovela-open-settings').on('click', function() {
                $('#seovela-404-settings-panel').addClass('active');
            });

            // Close settings panel
            $('.panel-close, .panel-overlay').on('click', function() {
                $('#seovela-404-settings-panel').removeClass('active');
            });

            // Toggle redirect URL field
            $('#redirect-enabled').on('change', function() {
                var $urlField = $('.redirect-url-field');
                var $urlInput = $('#redirect-url');
                if ($(this).is(':checked')) {
                    $urlField.css('opacity', '1');
                    $urlInput.prop('disabled', false);
                } else {
                    $urlField.css('opacity', '0.5');
                    $urlInput.prop('disabled', true);
                }
            });

            // Save settings form
            $('#seovela-404-settings-form').on('submit', function(e) {
                e.preventDefault();
                self.saveSettings();
            });

            // Delete all 404 logs
            $('.seovela-delete-all-404').on('click', function() {
                if (confirm('Are you sure you want to delete ALL 404 logs? This cannot be undone.')) {
                    self.deleteAllLogs();
                }
            });

            // Modal close buttons
            $('.seovela-modal-close').on('click', function() {
                $(this).closest('.seovela-modal').removeClass('active');
            });

            // Close modal on outside click
            $('.seovela-modal').on('click', function(e) {
                if ($(e.target).is('.seovela-modal')) {
                    $(this).removeClass('active');
                }
            });
        },

        deleteLog: function(logId, $row) {
            var self = this;

            $.ajax({
                url: seovela404Monitor.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'seovela_delete_404_log',
                    nonce: seovela404Monitor.nonce,
                    log_id: logId
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                        });
                        self.showNotice(response.data.message, 'success');
                    } else {
                        self.showNotice(response.data.message || 'Failed to delete log', 'error');
                    }
                },
                error: function() {
                    self.showNotice('An error occurred', 'error');
                }
            });
        },

        resolveLog: function(logId, $row) {
            var self = this;

            $.ajax({
                url: seovela404Monitor.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'seovela_resolve_404',
                    nonce: seovela404Monitor.nonce,
                    log_id: logId
                },
                success: function(response) {
                    if (response.success) {
                        $row.addClass('resolved').fadeOut(300, function() {
                            $(this).remove();
                        });
                        self.showNotice(response.data.message, 'success');
                    } else {
                        self.showNotice(response.data.message || 'Failed to mark as resolved', 'error');
                    }
                },
                error: function() {
                    self.showNotice('An error occurred', 'error');
                }
            });
        },

        openCreateRedirectModal: function(logId, sourceUrl) {
            var self = this;
            $('#404-log-id').val(logId);
            $('#404-source-url').val(sourceUrl);
            $('#404-target-url').val('');
            $('#404-suggestions').hide();
            $('#seovela-create-redirect-modal').addClass('active');

            // Load suggestions
            self.loadSuggestions(sourceUrl);
        },

        loadSuggestions: function(sourceUrl) {
            // For now, just show the suggestions box
            // In a future enhancement, could make AJAX call to get suggestions
            $('#404-suggestions').show();
            $('.seovela-suggestions-list').html('<p>Loading suggestions...</p>');
            
            // Mock suggestions (in production, this would be an AJAX call)
            setTimeout(function() {
                $('.seovela-suggestions-list').html(
                    '<div class="seovela-suggestion-item" data-url="/">' +
                        '<div>' +
                            '<div class="seovela-suggestion-title">Homepage</div>' +
                            '<div class="seovela-suggestion-url">/</div>' +
                        '</div>' +
                        '<span class="seovela-suggestion-type">page</span>' +
                    '</div>'
                );

                // Click suggestion to use it
                $('.seovela-suggestion-item').on('click', function() {
                    var url = $(this).data('url');
                    $('#404-target-url').val(url);
                    $('.seovela-suggestion-item').removeClass('selected');
                    $(this).addClass('selected');
                });
            }, 500);
        },

        createRedirectFrom404: function() {
            var self = this;
            var $form = $('#seovela-create-redirect-form');
            var $button = $form.find('button[type="submit"]');

            var data = {
                action: 'seovela_create_redirect_from_404',
                nonce: seovela404Monitor.nonce,
                log_id: $('#404-log-id').val(),
                target_url: $('#404-target-url').val()
            };

            $button.prop('disabled', true).text('Creating...');

            $.ajax({
                url: seovela404Monitor.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        self.showNotice(response.data.message, 'success');
                        $('#seovela-create-redirect-modal').removeClass('active');
                        // Reload page
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        self.showNotice(response.data.message || 'Failed to create redirect', 'error');
                    }
                },
                error: function() {
                    self.showNotice('An error occurred', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Create Redirect');
                }
            });
        },

        cleanupResolved: function() {
            var self = this;

            $.ajax({
                url: seovela404Monitor.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'seovela_cleanup_resolved',
                    nonce: seovela404Monitor.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Resolved logs cleaned up successfully', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        self.showNotice('Failed to cleanup logs', 'error');
                    }
                },
                error: function() {
                    self.showNotice('An error occurred', 'error');
                }
            });
        },

        saveSettings: function() {
            var self = this;
            var $form = $('#seovela-404-settings-form');
            var $button = $form.find('button[type="submit"]');

            var data = {
                action: 'seovela_save_404_settings',
                nonce: seovela404Monitor.nonce,
                cleanup_days: $('#cleanup-days').val(),
                redirect_url: $('#redirect-url').val(),
                redirect_enabled: $('#redirect-enabled').is(':checked') ? 1 : 0
            };

            $button.prop('disabled', true).text('Saving...');

            $.ajax({
                url: seovela404Monitor.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        self.showNotice(response.data.message, 'success');
                        $('#seovela-404-settings-panel').removeClass('active');
                    } else {
                        self.showNotice(response.data.message || 'Failed to save settings', 'error');
                    }
                },
                error: function() {
                    self.showNotice('An error occurred', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Save Settings');
                }
            });
        },

        deleteAllLogs: function() {
            var self = this;
            var $button = $('.seovela-delete-all-404');

            $button.prop('disabled', true).text('Deleting...');

            $.ajax({
                url: seovela404Monitor.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'seovela_delete_all_404',
                    nonce: seovela404Monitor.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice(response.data.message, 'success');
                        $('#seovela-404-settings-panel').removeClass('active');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        self.showNotice(response.data.message || 'Failed to delete logs', 'error');
                    }
                },
                error: function() {
                    self.showNotice('An error occurred', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Delete All');
                }
            });
        },

        showNotice: function(message, type) {
            var $notice = $('<div class="seovela-notice seovela-notice-' + type + '">' + message + '</div>');
            $('.seovela-404-monitor-page .seovela-404-header').after($notice);
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // =========================================================================
    // INITIALIZATION
    // =========================================================================

    $(document).ready(function() {
        // Initialize on appropriate pages
        if ($('.seovela-redirects-page').length) {
            redirectsManager.init();
        }

        if ($('.seovela-404-monitor-page').length) {
            monitor404.init();
        }
    });

})(jQuery);

