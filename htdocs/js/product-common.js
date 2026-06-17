/**
 * SHARED PRODUCT JAVASCRIPT
 * Dùng chung cho sanpham.php và sales.php
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    const grid = document.querySelector('.conten-1');
    if (!grid) return;
    
    const cards = Array.from(grid.querySelectorAll('.conten'));
    const searchInput = document.getElementById('searchInput');
    const authorInput = document.getElementById('authorInput');
    const sortSelect = document.getElementById('sortSelect');
    const filterTabs = document.querySelectorAll('.filter-tab');

    function updateUrlParams(extraParams = {}) {
        const url = new URL(window.location.href);
        
        if (searchInput) {
            const q = searchInput.value.trim();
            if (q) {
                url.searchParams.set('q', q);
            } else {
                url.searchParams.delete('q');
            }
        }
        
        if (authorInput) {
            const author = authorInput.value.trim();
            if (author) {
                url.searchParams.set('author', author);
            } else {
                url.searchParams.delete('author');
            }
        }
        
        if (sortSelect) {
            const sort = sortSelect.value;
            url.searchParams.set('sort', sort);
        }

        // Apply any extra overrides (e.g. from filter tabs)
        Object.keys(extraParams).forEach(key => {
            if (extraParams[key] === null || extraParams[key] === '') {
                url.searchParams.delete(key);
            } else {
                url.searchParams.set(key, extraParams[key]);
            }
        });
        
        // Reset to page 1 on filter/sort change
        url.searchParams.delete('page');
        
        window.location.href = url.toString();
    }
    
    // Event listeners
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                updateUrlParams();
            }
        });
        searchInput.addEventListener('change', function() {
            updateUrlParams();
        });
    }
    
    if (authorInput) {
        authorInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                updateUrlParams();
            }
        });
        authorInput.addEventListener('change', function() {
            updateUrlParams();
        });
    }
    
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            updateUrlParams();
        });
    }
    
    if (filterTabs.length) {
        filterTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                filterTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                const minDiscount = this.dataset.minDiscount || '0';
                updateUrlParams({ min_discount: minDiscount });
            });
        });
    }
});
