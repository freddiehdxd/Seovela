/**
 * Seovela Dashboard v3 - Interactive JavaScript
 *
 * Handles score ring animation, counter animations, progress bars,
 * tip dismissal with AJAX persistence, and micro-interactions.
 *
 * @package Seovela
 */

(function($) {
    'use strict';

    var SeovelaDashboard = {

        /**
         * Initialize all dashboard features
         */
        init: function() {
            this.animateScoreRing();
            this.animateCounters();
            this.animateProgressBars();
            this.initTipDismiss();
        },

        /**
         * Animate the SVG score ring from 0 to target score
         */
        animateScoreRing: function() {
            var ring = document.querySelector('.seovela-score-ring-progress');
            if (!ring) return;

            var score = parseInt(ring.dataset.score, 10) || 0;
            var circumference = 2 * Math.PI * 85; // r = 85
            var offset = circumference - (score / 100) * circumference;

            // Delay to let CSS animation of the card finish first
            setTimeout(function() {
                ring.style.strokeDashoffset = offset;
            }, 200);
        },

        /**
         * Animate the main score number and stat counters
         */
        animateCounters: function() {
            // Score number
            var scoreEl = document.querySelector('.seovela-score-number[data-target]');
            if (scoreEl) {
                this._countUp(scoreEl, parseInt(scoreEl.dataset.target, 10) || 0, 1200, 300);
            }

            // Stat mini counters
            var counters = document.querySelectorAll('.seovela-stat-mini-number[data-counter]');
            for (var i = 0; i < counters.length; i++) {
                (function(el, delay) {
                    var target = parseInt(el.dataset.counter, 10) || 0;
                    if (target > 0) {
                        SeovelaDashboard._countUp(el, target, 1000, delay);
                    }
                })(counters[i], 400 + (i * 100));
            }
        },

        /**
         * Count up animation helper
         *
         * @param {HTMLElement} el      Target element
         * @param {number}      target  Target number
         * @param {number}      dur     Duration in ms
         * @param {number}      delay   Start delay in ms
         */
        _countUp: function(el, target, dur, delay) {
            setTimeout(function() {
                var start = performance.now();

                function tick(now) {
                    var elapsed = now - start;
                    var progress = Math.min(elapsed / dur, 1);
                    // Ease-out cubic
                    var eased = 1 - Math.pow(1 - progress, 3);
                    el.textContent = Math.round(eased * target);

                    if (progress < 1) {
                        requestAnimationFrame(tick);
                    }
                }

                requestAnimationFrame(tick);
            }, delay);
        },

        /**
         * Animate progress bars from 0 to their target width
         */
        animateProgressBars: function() {
            var bars = document.querySelectorAll('.seovela-bar-fill[data-width]');

            setTimeout(function() {
                for (var i = 0; i < bars.length; i++) {
                    var width = parseFloat(bars[i].dataset.width) || 0;
                    bars[i].style.width = Math.min(width, 100) + '%';
                }
            }, 400);
        },

        /**
         * Handle tip banner dismissal with AJAX persistence
         */
        initTipDismiss: function() {
            var banner = document.getElementById('seovela-tip-banner');
            if (!banner) return;

            var dismissBtn = banner.querySelector('.seovela-tip-dismiss');
            if (!dismissBtn) return;

            dismissBtn.addEventListener('click', function() {
                // Animate out
                banner.style.opacity = '0';
                banner.style.transform = 'translateY(-4px)';

                setTimeout(function() {
                    banner.style.display = 'none';
                }, 300);

                // Persist dismissal via AJAX
                var nonce = dismissBtn.dataset.nonce;
                if (nonce && typeof seovelaAdmin !== 'undefined') {
                    $.post(seovelaAdmin.ajaxUrl, {
                        action: 'seovela_dismiss_tip',
                        nonce: nonce
                    });
                }
            });
        }
    };

    /**
     * Initialize on DOM ready
     */
    $(document).ready(function() {
        if (!document.querySelector('.seovela-dashboard-v2')) return;
        SeovelaDashboard.init();
    });

})(jQuery);
