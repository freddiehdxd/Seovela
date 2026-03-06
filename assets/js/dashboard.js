/**
 * Seovela Dashboard v2 - Interactive JavaScript
 *
 * Handles animations, score ring progress, counters, and micro-interactions
 *
 * @package Seovela
 */

(function($) {
    'use strict';

    /**
     * Dashboard Controller
     */
    const SeovelaDashboard = {
        
        /**
         * Initialize dashboard
         */
        init: function() {
            this.animateScoreRing();
            this.animateCounters();
            this.initTipBanner();
            this.initModuleCards();
            this.initStatCards();
        },

        /**
         * Animate the SEO score ring
         */
        animateScoreRing: function() {
            const ring = document.querySelector('.seovela-score-ring-progress');
            if (!ring) return;

            const score = parseInt(ring.dataset.score) || 0;
            const circumference = 2 * Math.PI * 85; // r = 85
            const offset = circumference - (score / 100) * circumference;

            // Trigger animation after a short delay
            setTimeout(function() {
                ring.style.strokeDashoffset = offset;
            }, 300);
        },

        /**
         * Animate counter numbers with easing
         */
        animateCounters: function() {
            const counters = document.querySelectorAll('.seovela-score-number[data-target]');
            
            counters.forEach(function(counter) {
                const target = parseInt(counter.dataset.target) || 0;
                const duration = 1500;
                const startTime = performance.now();

                function updateCounter(currentTime) {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    
                    // Easing function (ease-out cubic)
                    const easeOut = 1 - Math.pow(1 - progress, 3);
                    const current = Math.round(easeOut * target);
                    
                    counter.textContent = current;

                    if (progress < 1) {
                        requestAnimationFrame(updateCounter);
                    }
                }

                // Start animation after delay
                setTimeout(function() {
                    requestAnimationFrame(updateCounter);
                }, 600);
            });
        },

        /**
         * Initialize tip banner dismissal
         */
        initTipBanner: function() {
            const dismissBtn = document.querySelector('.seovela-tip-dismiss');
            const banner = document.querySelector('.seovela-dash-tip-banner');
            
            if (!dismissBtn || !banner) return;

            dismissBtn.addEventListener('click', function() {
                banner.style.transition = 'all 0.3s ease';
                banner.style.opacity = '0';
                banner.style.transform = 'translateX(20px)';
                
                setTimeout(function() {
                    banner.style.display = 'none';
                }, 300);
            });
        },

        /**
         * Add hover effects to module cards
         */
        initModuleCards: function() {
            const cards = document.querySelectorAll('.seovela-module-quick-card');
            
            cards.forEach(function(card) {
                card.addEventListener('mouseenter', function() {
                    const icon = card.querySelector('.seovela-module-quick-icon');
                    if (icon) {
                        icon.style.transform = 'scale(1.1) rotate(5deg)';
                    }
                });

                card.addEventListener('mouseleave', function() {
                    const icon = card.querySelector('.seovela-module-quick-icon');
                    if (icon) {
                        icon.style.transform = 'scale(1) rotate(0deg)';
                    }
                });
            });
        },

        /**
         * Add hover effects to stat cards
         */
        initStatCards: function() {
            const cards = document.querySelectorAll('.seovela-stat-card');
            
            cards.forEach(function(card) {
                card.addEventListener('mouseenter', function() {
                    const icon = card.querySelector('.seovela-stat-icon');
                    if (icon) {
                        icon.style.transform = 'scale(1.2)';
                        icon.style.transition = 'transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
                    }
                });

                card.addEventListener('mouseleave', function() {
                    const icon = card.querySelector('.seovela-stat-icon');
                    if (icon) {
                        icon.style.transform = 'scale(1)';
                    }
                });
            });
        },

        /**
         * Intersection Observer for scroll animations
         */
        initScrollAnimations: function() {
            if (!('IntersectionObserver' in window)) return;

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('seovela-animated');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            document.querySelectorAll('.seovela-dash-section, .seovela-resource-card').forEach(function(el) {
                observer.observe(el);
            });
        },

        /**
         * Particle effect for score (optional enhancement)
         */
        addScoreParticles: function() {
            const container = document.querySelector('.seovela-score-ring-container');
            if (!container) return;

            const score = parseInt(document.querySelector('.seovela-score-ring-progress')?.dataset.score) || 0;
            
            // Only add particles for good scores
            if (score < 60) return;

            // Create particles after score animation completes
            setTimeout(function() {
                for (let i = 0; i < 8; i++) {
                    const particle = document.createElement('div');
                    particle.className = 'seovela-score-particle';
                    particle.style.cssText = `
                        position: absolute;
                        width: 6px;
                        height: 6px;
                        background: linear-gradient(135deg, #8b5cf6, #3b82f6);
                        border-radius: 50%;
                        top: 50%;
                        left: 50%;
                        opacity: 0;
                        animation: particleBurst 1s ease-out forwards;
                        animation-delay: ${i * 0.1}s;
                        --angle: ${(i * 45)}deg;
                    `;
                    container.appendChild(particle);

                    // Remove particle after animation
                    setTimeout(function() {
                        particle.remove();
                    }, 1200 + (i * 100));
                }
            }, 1800);
        }
    };

    /**
     * Add particle burst animation keyframes
     */
    function addParticleStyles() {
        if (document.getElementById('seovela-particle-styles')) return;

        const style = document.createElement('style');
        style.id = 'seovela-particle-styles';
        style.textContent = `
            @keyframes particleBurst {
                0% {
                    opacity: 1;
                    transform: translate(-50%, -50%) rotate(var(--angle)) translateY(0);
                }
                100% {
                    opacity: 0;
                    transform: translate(-50%, -50%) rotate(var(--angle)) translateY(-80px);
                }
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Initialize on DOM ready
     */
    $(document).ready(function() {
        // Only run on dashboard page
        if (!document.querySelector('.seovela-dashboard-v2')) return;

        addParticleStyles();
        SeovelaDashboard.init();
        
        // Add particles after main animations
        setTimeout(function() {
            SeovelaDashboard.addScoreParticles();
        }, 100);
    });

    /**
     * Also handle window load for full initialization
     */
    $(window).on('load', function() {
        if (!document.querySelector('.seovela-dashboard-v2')) return;
        SeovelaDashboard.initScrollAnimations();
    });

})(jQuery);

