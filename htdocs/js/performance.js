// Performance Optimizations for NXB Kim Đồng
(function() {
    'use strict';

    // ===== LAZY LOADING IMAGES =====
    function initLazyLoading() {
        // Check if native lazy loading is supported
        if ('loading' in HTMLImageElement.prototype) {
            const images = document.querySelectorAll('img[loading="lazy"]');
            images.forEach(img => {
                // If src is already set, native lazy loading will handle it
                if (!img.src && img.dataset.src) {
                    img.src = img.dataset.src;
                }
            });
        } else {
            // Fallback for browsers that don't support native lazy loading
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/lazysizes@5.3.2/lazysizes.min.js';
            document.body.appendChild(script);
        }
    }

    // ===== OPTIMIZE ANIMATIONS =====
    function optimizeAnimations() {
        // Pause animations when not visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                } else {
                    entry.target.style.animationPlayState = 'paused';
                }
            });
        });

        document.querySelectorAll('[class*="animate"]').forEach(el => {
            observer.observe(el);
        });
    }

    // ===== DEBOUNCE SCROLL EVENTS =====
    function debounce(func, wait) {
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

    // ===== OPTIMIZE SCROLL PERFORMANCE =====
    function optimizeScroll() {
        let ticking = false;
        function updateScrollPosition() {
            // Header scroll effect
            const header = document.getElementById('header');
            if (header) {
                if (window.scrollY > 50) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            }
            ticking = false;
        }

        function requestTick() {
            if (!ticking) {
                window.requestAnimationFrame(updateScrollPosition);
                ticking = true;
            }
        }

        window.addEventListener('scroll', requestTick, { passive: true });
    }

    // ===== OPTIMIZE IMAGES =====
    function optimizeImages() {
        const images = document.querySelectorAll('img:not([loading])');
        images.forEach(img => {
            // Add loading attribute to images without it
            img.loading = 'lazy';
            
            // Add dimensions if missing to prevent layout shift
            if (!img.width && img.naturalWidth) {
                img.width = img.naturalWidth;
            }
            if (!img.height && img.naturalHeight) {
                img.height = img.naturalHeight;
            }
        });
    }

    // ===== CACHE API RESPONSES =====
    const cache = new Map();
    const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

    window.cachedFetch = function(url, options = {}) {
        const cacheKey = url + JSON.stringify(options);
        const cached = cache.get(cacheKey);

        if (cached && Date.now() - cached.timestamp < CACHE_DURATION) {
            return Promise.resolve(cached.data);
        }

        return fetch(url, options)
            .then(response => response.clone().json())
            .then(data => {
                cache.set(cacheKey, {
                    data: data,
                    timestamp: Date.now()
                });
                return data;
            });
    };

    // ===== REDUCE REPAINTS/REFLOWS =====
    function batchDOMUpdates(updates) {
        requestAnimationFrame(() => {
            updates.forEach(update => update());
        });
    }

    // ===== INITIALIZE ALL OPTIMIZATIONS =====
    function initPerformanceOptimizations() {
        // Run immediately
        initLazyLoading();
        optimizeImages();
        // Run when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                optimizeAnimations();
                optimizeScroll();
            });
        } else {
            optimizeAnimations();
            optimizeScroll();
        }
    }

    // Start optimizations
    initPerformanceOptimizations();

    // Export for global use
    window.performanceUtils = {
        debounce,
        batchDOMUpdates,
        cachedFetch
    };

})();
