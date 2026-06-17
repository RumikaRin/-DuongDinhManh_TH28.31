// ========================================
// MODERN JAVASCRIPT - NXB KIM ĐỒNG
// ========================================

// ===== UTILITY FUNCTIONS =====

// Helper to escape HTML tags for XSS protection
function escapeHTML(str) {
    if (!str) return '';
    return str.toString().replace(/[&<>'"]/g, 
        tag => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[tag] || tag)
    );
}

// Debounce function for performance
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

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function initializeDismissibleDetails() {
    const dropdowns = [...document.querySelectorAll('details.category-menu, details.user-menu')];
    if (!dropdowns.length) return;

    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('toggle', () => {
            if (!dropdown.open) return;
            dropdowns.forEach(other => {
                if (other !== dropdown) other.open = false;
            });
        });
    });

    document.addEventListener('pointerdown', event => {
        dropdowns.forEach(dropdown => {
            if (dropdown.open && !dropdown.contains(event.target)) {
                dropdown.open = false;
            }
        });
    });

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        dropdowns.forEach(dropdown => {
            dropdown.open = false;
        });
    });
}

function initializeHeroSlider() {
    const slider = document.querySelector('[data-hero-slider]');
    if (!slider) return;

    const slides = [...slider.querySelectorAll('.hero-slide')];
    const dots = [...slider.querySelectorAll('[data-slider-dot]')];
    const previous = slider.querySelector('[data-slider-prev]');
    const next = slider.querySelector('[data-slider-next]');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let activeIndex = 0;
    let timer = null;

    const showSlide = index => {
        activeIndex = (index + slides.length) % slides.length;
        slides.forEach((slide, slideIndex) => {
            const active = slideIndex === activeIndex;
            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', active ? 'false' : 'true');
            slide.tabIndex = active ? 0 : -1;
        });
        dots.forEach((dot, dotIndex) => {
            const active = dotIndex === activeIndex;
            dot.classList.toggle('is-active', active);
            dot.setAttribute('aria-current', active ? 'true' : 'false');
        });
    };

    const stop = () => {
        if (timer) window.clearInterval(timer);
        timer = null;
    };
    const start = () => {
        stop();
        if (!reduceMotion && slides.length > 1) {
            timer = window.setInterval(() => showSlide(activeIndex + 1), 5000);
        }
    };

    previous?.addEventListener('click', () => {
        showSlide(activeIndex - 1);
        start();
    });
    next?.addEventListener('click', () => {
        showSlide(activeIndex + 1);
        start();
    });
    dots.forEach(dot => {
        dot.addEventListener('click', () => {
            showSlide(Number(dot.dataset.sliderDot));
            start();
        });
    });
    slider.addEventListener('pointerenter', stop);
    slider.addEventListener('pointerleave', start);
    slider.addEventListener('focusin', stop);
    slider.addEventListener('focusout', start);

    showSlide(0);
    start();
}

function initializeScrollReveals() {
    const revealItems = [...document.querySelectorAll('[data-reveal]')];
    if (!revealItems.length) return;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    revealItems.forEach((item, index) => {
        item.style.setProperty('--reveal-index', String(index % 8));
        if (reduceMotion) {
            item.classList.add('is-revealed');
        }
    });

    if (reduceMotion || !('IntersectionObserver' in window)) {
        revealItems.forEach(item => item.classList.add('is-revealed'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-revealed');
            observer.unobserve(entry.target);
        });
    }, {
        threshold: 0.12,
        rootMargin: '0px 0px -8% 0px'
    });

    revealItems.forEach(item => observer.observe(item));
}

function initializeKineticPointer() {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion) return;

    const targets = [...document.querySelectorAll('.hero-stage, .book-card-shell, .news-card, .story-band-media')];
    targets.forEach(target => {
        target.addEventListener('pointermove', event => {
            const rect = target.getBoundingClientRect();
            const x = ((event.clientX - rect.left) / rect.width - 0.5).toFixed(3);
            const y = ((event.clientY - rect.top) / rect.height - 0.5).toFixed(3);
            target.style.setProperty('--pointer-x', x);
            target.style.setProperty('--pointer-y', y);
        });

        target.addEventListener('pointerleave', () => {
            target.style.setProperty('--pointer-x', '0');
            target.style.setProperty('--pointer-y', '0');
        });
    });
}

// Smooth scroll to element
function smoothScrollTo(element) {
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Show notification
function showNotification(message, type = 'info', duration = 3000) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i data-lucide="${type === 'success' ? 'check-circle' : type === 'error' ? 'x-circle' : 'info'}" class="notification-icon"></i>
            <span>${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <i data-lucide="x"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Initialize icons for the notification
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Auto remove after duration
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, duration);
}

// ===== USER AUTHENTICATION =====

function userLoggedIn(username) {
    // Update UI for logged in user
    updateAuthUI(true, username);
    
    // Bỏ thông báo chào mừng theo yêu cầu
    
    // Load user preferences
    loadUserPreferences();
}

function userLoggedOut() {
    // Update UI for logged out user
    updateAuthUI(false);
    
    // Clear user data
    clearUserData();
}

function updateAuthUI(isLoggedIn, username = '') {
    const authSection = document.getElementById('auth-section');
    if (!authSection) return;
    
    if (isLoggedIn) {
        // Update cart count if available
        updateCartCount();
    }
}

function loadUserPreferences() {
    // Remove dark mode completely
    document.body.setAttribute('data-theme', 'light');
    localStorage.removeItem('theme');
    
    // Load other preferences
    const language = localStorage.getItem('language') || 'vi';
    // Apply language settings if needed
}

function clearUserData() {
    // Clear sensitive data but keep preferences
    // localStorage.removeItem('cartItems');
    // Keep darkMode, language preferences
}

// ===== DARK MODE =====

function toggleDarkMode() {
    const currentTheme = document.body.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    document.body.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateDarkModeToggle(newTheme === 'dark');
    
    // Animate the toggle
    const toggle = document.querySelector('.dark-toggle');
    if (toggle) {
        toggle.style.transform = 'scale(0.9)';
        setTimeout(() => {
            toggle.style.transform = 'scale(1)';
        }, 150);
    }
}

function updateDarkModeToggle(isDark) {
    const darkIcon = document.querySelector('.dark-icon');
    const lightIcon = document.querySelector('.light-icon');
    const themeLabel = document.querySelector('.theme-label');
    
    if (darkIcon) {
        darkIcon.style.display = isDark ? 'none' : 'block';
    }
    if (lightIcon) {
        lightIcon.style.display = isDark ? 'block' : 'none';
    }
    if (themeLabel) {
        themeLabel.textContent = isDark ? 'Sáng' : 'Tối';
    }
}

// ===== SHOPPING CART =====

let cartItems = []; // local cache is optional when server session is source of truth

function addToCart(productId, type = 'sanpham', quantity = 1) {
    try {
        const fd = new FormData();
        fd.append('id_sp', productId);
        fd.append('loai', type);
        fd.append('soluong', quantity);
        fd.append('action', 'add');
        fd.append('csrf_token', getCsrfToken());

        fetch('giohang.php', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(res => {
            if (res && res.ok) {
                // Update cart badge if present
                const badge = document.getElementById('cart-badge') || document.querySelector('.cart-count');
                if (badge) {
                    badge.textContent = res.cart_count ?? '';
                    badge.style.display = (res.cart_count && res.cart_count > 0) ? 'inline-flex' : 'none';
                }
                showNotification('Đã thêm sản phẩm vào giỏ hàng', 'success');
                animateCartIcon();
            } else {
                showNotification('Không thể thêm vào giỏ. Vui lòng đăng nhập hoặc thử lại.', 'error');
            }
        })
        .catch(() => showNotification('Lỗi mạng khi thêm vào giỏ', 'error'));
    } catch (e) {
        console.error('addToCart error:', e);
        showNotification('Lỗi khi thêm vào giỏ', 'error');
    }
}

function removeFromCart(productId) {
    cartItems = cartItems.filter(item => item.id !== productId);
    localStorage.setItem('cartItems', JSON.stringify(cartItems));
    updateCartCount();
    showNotification('Đã xóa sản phẩm khỏi giỏ hàng', 'info');
}

function updateCartCount(countFromServer) {
    const cartCountElement = document.querySelector('.cart-count');
    if (cartCountElement) {
        const total = typeof countFromServer === 'number' ? countFromServer : (parseInt(cartCountElement.textContent || '0', 10) || 0);
        cartCountElement.textContent = total;
        cartCountElement.style.display = total > 0 ? 'flex' : 'none';
    }
}

function animateCartIcon() {
    const cartIcon = document.querySelector('.quick-action[title="Giỏ hàng"]');
    if (cartIcon) {
        cartIcon.style.transform = 'scale(1.2)';
        cartIcon.style.background = 'var(--success-color)';
        cartIcon.style.color = 'white';
        
        setTimeout(() => {
            cartIcon.style.transform = 'scale(1)';
            cartIcon.style.background = '';
            cartIcon.style.color = '';
        }, 300);
    }
}

// ===== DROPDOWN MENU (Legacy Support) =====

window.addEventListener('load', function() {
    const toggle = document.querySelector(".username-display");
    const menu = document.querySelector(".dropdown-content");
    const container = document.querySelector(".dropdown");

    if (toggle && menu && container) {
        // Remove any existing event listeners by cloning the element
        const newToggle = toggle.cloneNode(true);
        toggle.parentNode.replaceChild(newToggle, toggle);
        
        newToggle.addEventListener("click", function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle dropdown
            const isOpen = menu.classList.contains("show");
            menu.classList.toggle("show");
            
            // Add visual feedback
            newToggle.style.transform = isOpen ? 'scale(1)' : 'scale(0.98)';
            setTimeout(() => {
                newToggle.style.transform = 'scale(1)';
            }, 150);
        });

        // Close dropdown when clicking outside
        document.addEventListener("click", function(event) {
            if (!container.contains(event.target)) {
                menu.classList.remove("show");
            }
        });

        // Close dropdown on escape key
        document.addEventListener("keydown", function(event) {
            if (event.key === 'Escape' && menu.classList.contains("show")) {
                menu.classList.remove("show");
            }
        });
    }
});

// ===== SEARCH FUNCTIONALITY =====

function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;
    
    searchInput.addEventListener('input', debounce(function() {
        const query = this.value.toLowerCase().trim();
        performSearch(query);
    }, 300));
}

function performSearch(query) {
    const products = document.querySelectorAll('.conten');
    let visibleCount = 0;
    
    products.forEach(product => {
        const title = product.querySelector('.tensp')?.textContent?.toLowerCase() || '';
        const author = product.querySelector('.product-author')?.textContent?.toLowerCase() || '';
        
        if (query === '' || title.includes(query) || author.includes(query)) {
            product.style.display = 'block';
            product.classList.add('fade-in-up');
            visibleCount++;
        } else {
            product.style.display = 'none';
        }
    });
    
    // Show no results message if needed
    updateSearchResults(visibleCount, query);
}

function updateSearchResults(count, query) {
    let noResultsElement = document.querySelector('.no-search-results');
    
    if (count === 0 && query !== '') {
        if (!noResultsElement) {
            noResultsElement = document.createElement('div');
            noResultsElement.className = 'no-search-results';
            noResultsElement.innerHTML = `
                <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                    <i data-lucide="search-x" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>
                    <h3>Không tìm thấy kết quả</h3>
                    <p>Không có sản phẩm nào phù hợp với từ khóa "${escapeHTML(query)}"</p>
                </div>
            `;
            
            const productsGrid = document.querySelector('.conten-1');
            if (productsGrid) {
                productsGrid.appendChild(noResultsElement);
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }
        }
    } else if (noResultsElement) {
        noResultsElement.remove();
    }
}

// ===== LAZY LOADING =====

function initializeLazyLoading() {
    const images = document.querySelectorAll('img[loading="lazy"]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src || img.src;
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    }
}

function initializeImageFallbacks() {
    const fallback = 'img/sp/logo.webp';
    document.querySelectorAll('img').forEach(image => {
        const applyFallback = () => {
            if (image.dataset.fallbackApplied === 'true') return;
            image.dataset.fallbackApplied = 'true';
            image.classList.add('image-fallback');
            image.src = fallback;
        };

        image.addEventListener('error', applyFallback, { once: true });
        if (image.complete && image.naturalWidth === 0) {
            applyFallback();
        }
    });
}

function initializeDynamicStars() {
    document.querySelectorAll('.stars .star').forEach(icon => {
        icon.setAttribute('width', '15');
        icon.setAttribute('height', '15');
    });
}

// ===== ANIMATIONS =====

function initializeAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
            }
        });
    }, observerOptions);
    
    // Observe all product cards
    document.querySelectorAll('.conten').forEach(card => {
        observer.observe(card);
    });
}

// ===== INITIALIZATION =====

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all features
    loadUserPreferences();
    updateCartCount();
    initializeSearch();
    initializeImageFallbacks();
    initializeLazyLoading();
    initializeAnimations();
    initializeDismissibleDetails();
    initializeHeroSlider();
    initializeScrollReveals();
    initializeKineticPointer();
    initializeDynamicStars();
    
    // Initialize Lucide icons if available
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
});

// ===== GLOBAL ERROR HANDLING =====

window.addEventListener('error', function(e) {
    console.error('JavaScript error:', e.error);
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled promise rejection:', e.reason);
});

// ===== COMMENT SYSTEM =====

function submitComment(productId, productType) {
    const rating = document.querySelector('input[name="rating"]:checked')?.value;
    const content = document.querySelector('#comment-content')?.value;
    
    if (!rating) {
        showNotification('Vui lòng chọn số sao đánh giá', 'error');
        return;
    }
    
    if (!content || content.trim() === '') {
        showNotification('Vui lòng nhập nội dung đánh giá', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'submit');
    formData.append('product_id', productId);
    formData.append('product_type', productType);
    formData.append('rating', rating);
    formData.append('content', content);
    formData.append('csrf_token', getCsrfToken());
    
    fetch('comment_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok) {
            showNotification(data.message, 'success');
            // Reset form
            document.querySelector('#comment-form')?.reset();
            // Reload comments
            loadComments(productId, productType);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error submitting comment:', error);
        showNotification('Có lỗi xảy ra khi gửi đánh giá', 'error');
    });
}

function loadComments(productId, productType, page = 1) {
    fetch(`comment_handler.php?action=load&product_id=${productId}&product_type=${productType}&page=${page}`)
        .then(response => response.json())
        .then(data => {
            if (data.ok) {
                displayComments(data.comments);
                updatePagination(data.pages, data.current_page, productId, productType);
            }
        })
        .catch(error => console.error('Error loading comments:', error));
}

function displayComments(comments) {
    const container = document.querySelector('#comments-list');
    if (!container) return;
    
    if (comments.length === 0) {
        container.innerHTML = '<p class="no-comments">Chưa có đánh giá nào.</p>';
        return;
    }
    
    container.innerHTML = comments.map(comment => `
        <div class="comment-item" data-comment-id="${comment.id}">
            <div class="comment-header">
                <span class="comment-author">${escapeHTML(comment.username)}</span>
                <div class="comment-rating">
                    ${'★'.repeat(comment.rating)}${'☆'.repeat(5 - comment.rating)}
                </div>
                <span class="comment-date">${comment.created_at_formatted}</span>
            </div>
            <div class="comment-content">${escapeHTML(comment.content)}</div>
            <div class="comment-actions">
                <button onclick="markHelpful(${comment.id})" class="helpful-btn">
                    👍 Hữu ích (${comment.helpful_count || 0})
                </button>
                ${comment.is_owner ? `<button onclick="deleteComment(${comment.id})" class="delete-btn">🗑️ Xóa</button>` : ''}
            </div>
            ${comment.reply ? `
                <div class="comment-reply">
                    <strong>Phản hồi từ admin:</strong>
                    <p>${escapeHTML(comment.reply)}</p>
                </div>
            ` : ''}
        </div>
    `).join('');
}

function markHelpful(commentId) {
    const formData = new FormData();
    formData.append('action', 'helpful');
    formData.append('comment_id', commentId);
    formData.append('csrf_token', getCsrfToken());
    
    fetch('comment_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok) {
            showNotification(data.message, 'info');
            // Refresh comment to update count
            location.reload();
        }
    })
    .catch(error => console.error('Error marking helpful:', error));
}

function deleteComment(commentId) {
    if (!confirm('Bạn có chắc muốn xóa bình luận này?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('comment_id', commentId);
    formData.append('csrf_token', getCsrfToken());
    
    fetch('comment_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok) {
            showNotification(data.message, 'success');
            document.querySelector(`[data-comment-id="${commentId}"]`)?.remove();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => console.error('Error deleting comment:', error));
}

function updatePagination(totalPages, currentPage, productId, productType) {
    const container = document.querySelector('#comments-pagination');
    if (!container || totalPages <= 1) return;
    
    let html = '';
    for (let i = 1; i <= totalPages; i++) {
        html += `<button onclick="loadComments(${productId}, '${productType}', ${i})" 
                 class="${i === currentPage ? 'active' : ''}">${i}</button>`;
    }
    container.innerHTML = html;
}

// ===== PRODUCT STATS =====

function loadProductStats(productId, productType) {
    fetch(`comment_handler.php?action=stats&product_id=${productId}&product_type=${productType}`)
        .then(response => response.json())
        .then(data => {
            if (data.ok) {
                displayProductStats(data.stats);
            }
        })
        .catch(error => console.error('Error loading stats:', error));
}

function displayProductStats(stats) {
    const avgRating = document.querySelector('#avg-rating');
    const totalReviews = document.querySelector('#total-reviews');
    const ratingBars = document.querySelector('#rating-bars');
    
    if (avgRating) {
        avgRating.innerHTML = `
            <span class="big-rating">${stats.avg_rating || 0}</span>
            <div class="stars">${'★'.repeat(Math.round(stats.avg_rating || 0))}${'☆'.repeat(5 - Math.round(stats.avg_rating || 0))}</div>
            <span>${stats.total_reviews || 0} đánh giá</span>
        `;
    }
    
    if (ratingBars) {
        ratingBars.innerHTML = `
            <div class="rating-bar">
                <span>5★</span>
                <div class="bar"><div class="fill" style="width:${stats.five_star_percent || 0}%"></div></div>
                <span>${stats.five_star || 0}</span>
            </div>
            <div class="rating-bar">
                <span>4★</span>
                <div class="bar"><div class="fill" style="width:${stats.four_star_percent || 0}%"></div></div>
                <span>${stats.four_star || 0}</span>
            </div>
            <div class="rating-bar">
                <span>3★</span>
                <div class="bar"><div class="fill" style="width:${stats.three_star_percent || 0}%"></div></div>
                <span>${stats.three_star || 0}</span>
            </div>
            <div class="rating-bar">
                <span>2★</span>
                <div class="bar"><div class="fill" style="width:${stats.two_star_percent || 0}%"></div></div>
                <span>${stats.two_star || 0}</span>
            </div>
            <div class="rating-bar">
                <span>1★</span>
                <div class="bar"><div class="fill" style="width:${stats.one_star_percent || 0}%"></div></div>
                <span>${stats.one_star || 0}</span>
            </div>
        `;
    }
}

// ===== EXPORT FUNCTIONS FOR GLOBAL USE =====

window.modernWebsite = {
    toggleDarkMode,
    loadUserPreferences,
    addToCart,
    removeFromCart,
    updateCartCount,
    showNotification,
    initializeScrollReveals,
    initializeKineticPointer,
    initializeImageFallbacks,
    userLoggedIn,
    userLoggedOut,
    submitComment,
    loadComments,
    markHelpful,
    deleteComment,
    loadProductStats
};
