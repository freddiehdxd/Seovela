/**
 * Seovela Admin JavaScript
 *
 * @package Seovela
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Module toggle functionality (modern design)
        $('.seovela-module-toggle-input').on('change', function() {
            var $card = $(this).closest('.seovela-module-card-modern');
            
            if ($(this).is(':checked')) {
                $card.addClass('seovela-module-active');
            } else {
                $card.removeClass('seovela-module-active');
            }
        });

        // Old module toggle functionality (legacy support)
        $('.seovela-toggle input').on('change', function() {
            var $card = $(this).closest('.seovela-module-card');
            if ($(this).is(':checked')) {
                $card.removeClass('seovela-module-disabled');
            } else {
                $card.addClass('seovela-module-disabled');
            }
        });

        // Confirm deactivation
        $('button[name="seovela_deactivate_license"]').on('click', function(e) {
            if (!confirm('Are you sure you want to deactivate your license? Pro features will be disabled.')) {
                e.preventDefault();
            }
        });

        // Regenerate sitemap confirmation
        $('button[name="seovela_regenerate_sitemap"]').on('click', function(e) {
            var confirmed = confirm('Regenerate the sitemap? This will update the sitemap with current content.');
            if (!confirmed) {
                e.preventDefault();
            }
        });

        // Module card hover effect enhancement
        $('.seovela-module-card-modern').on('mouseenter', function() {
            $(this).find('.seovela-module-icon-wrapper').addClass('seovela-icon-hover');
        }).on('mouseleave', function() {
            $(this).find('.seovela-module-icon-wrapper').removeClass('seovela-icon-hover');
        });

        // Smooth scroll to saved message
        if ($('.updated, .notice-success').length) {
            $('html, body').animate({
                scrollTop: $('.updated, .notice-success').offset().top - 100
            }, 500);
        }
    });

})(jQuery);
