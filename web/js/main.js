

let notificationsOpen = false;


function showNotification(message, type = 'info') {
    showAnimatedNotification(message, type);
}

function getNotificationIcon(type) {
    const icons = {
        'success': '✅',
        'error': '❌',
        'warning': '⚠️',
        'info': 'ℹ️',
        'like': '❤️',
        'comment': '💬',
        'follow': '👤',
        'mention': '@',
        'system': '📢'
    };
    return icons[type] || 'ℹ️';
}

function supportsWebP() {
    const canvas = document.createElement('canvas');
    canvas.width = 1;
    canvas.height = 1;
    return canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
}

function getOptimizedImageUrl(originalUrl) {

    if (originalUrl.includes('.webp') || originalUrl.startsWith('http')) {
        return originalUrl;
    }

    if (supportsWebP()) {
        return originalUrl.replace(/\.(jpg|jpeg|png)$/i, '.webp');
    }
    
    return originalUrl;
}

function createOptimizedImage(src, alt, className = '') {
    const img = document.createElement('img');
    const optimizedSrc = getOptimizedImageUrl(src);
    
    img.dataset.src = src; // Оригинальный источник для lazy loading
    img.alt = alt || '';
    img.className = `optimized-image ${className}`;

    img.style.cssText = `
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: var(--border-radius-md);
        min-height: 200px;
        transition: opacity 0.3s ease;
    `;
    
    return img;
}

window.alert = showNotification;

let serviceWorkerRegistration = null;

function initServiceWorker() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/js/service-worker.js')
            .then(registration => {
                serviceWorkerRegistration = registration;

                navigator.serviceWorker.addEventListener('message', handleServiceWorkerMessage);
            })
            .catch(error => {
                
            });
    }
}

function handleServiceWorkerMessage(event) {
    const { type, data } = event.data;
    
    switch (type) {
        case 'CACHE_UPDATED':
            showNotification('Кэш обновлен', 'success');
            break;
        case 'OFFLINE_MODE':
            showNotification('Офлайн режим активирован', 'warning');
            break;
        case 'ONLINE_MODE':
            showNotification('Подключение восстановлено', 'success');
            break;
    }
}

function updateServiceWorkerCache() {
    if (serviceWorkerRegistration) {
        serviceWorkerRegistration.active.postMessage({
            type: 'CACHE_UPDATE'
        });
    }
}

function skipServiceWorkerWaiting() {
    if (serviceWorkerRegistration && serviceWorkerRegistration.waiting) {
        serviceWorkerRegistration.waiting.postMessage({
            type: 'SKIP_WAITING'
        });
    }
}

let loadingCount = 0;

function showLoading(message = 'Загрузка...') {
    loadingCount++;

    let container = document.getElementById('loading-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'loading-container';
        container.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9998;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(2px);
        `;
        document.body.appendChild(container);
    }

    const loader = document.createElement('div');
    loader.className = 'loading-indicator';
    loader.style.cssText = `
        background: var(--surface-color);
        color: var(--text-color);
        padding: 16px 24px;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-lg);
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: var(--font-size-sm);
        font-weight: 500;
        min-width: 200px;
        animation: slideIn 0.3s ease;
    `;
    
    loader.innerHTML = `
        <div class="global-loading-spinner"><div class="spinner"><div class="spinner-ring"></div><div class="spinner-ring"></div><div class="spinner-ring"></div></div></div>
        <span class="loading-text">${message}</span>
    `;

    const style = document.createElement('style');
    style.textContent = `
        .global-loading-spinner {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .global-loading-spinner .spinner {
            width: 20px;
            height: 20px;
        }
        .global-loading-spinner .spinner-ring {
            border-width: 2px;
        }
        
        .global-loading-spinner .spinner-ring:nth-child(1) {
            border-top-color: var(--primary-color);
            animation-delay: -0.45s;
        }
        
        .global-loading-spinner .spinner-ring:nth-child(2) {
            border-right-color: var(--primary-color);
            animation-delay: -0.3s;
        }
        
        .global-loading-spinner .spinner-ring:nth-child(3) {
            border-bottom-color: var(--primary-color);
            animation-delay: -0.15s;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(style);
    
    container.appendChild(loader);
}

function hideLoading() {
    loadingCount = Math.max(0, loadingCount - 1);
    
    if (loadingCount === 0) {
        const container = document.getElementById('loading-container');
        if (container) {
            const loaders = container.querySelectorAll('.loading-indicator');
            loaders.forEach(loader => {
                loader.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    if (loader.parentNode) {
                        loader.parentNode.removeChild(loader);
                    }
                }, 300);
            });

            setTimeout(() => {
                if (container.children.length === 0) {
                    container.remove();
                }
            }, 350);
        }
    }
}

async function fetchWithLoading(url, options = {}) {
    const showLoader = options.showLoader !== false;
    const timeout = options.timeout || 30000; // 30 секунд по умолчанию
    
    if (showLoader) {
        showLoading(options.loadingMessage);
    }
    
    try {

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout);
        
        const response = await fetch(url, {
            ...options,
            signal: controller.signal,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers
            }
        });
        
        clearTimeout(timeoutId);
        
        if (showLoader) {
            hideLoading();
        }

        if (!response.ok) {
            const errorMessage = await getErrorMessage(response);
            throw new Error(errorMessage);
        }
        
        return response;
    } catch (error) {
        if (showLoader) {
            hideLoading();
        }

        if (error.name === 'AbortError') {
            showNotification('Запрос отменен по таймауту', 'warning');
        } else if (error.message.includes('Failed to fetch')) {
            showNotification('Отсутствует подключение к интернету', 'error');
        } else if (error.message.includes('NetworkError')) {
            showNotification('Ошибка сети. Проверьте подключение', 'error');
        } else {
            showNotification(error.message || 'Произошла ошибка', 'error');
        }
        
        throw error;
    }
}

async function getErrorMessage(response) {
    try {
        const errorData = await response.clone().json();
        
        if (errorData.message) {
            return errorData.message;
        } else if (errorData.error) {
            return errorData.error;
        }
    } catch (e) {

        switch (response.status) {
            case 400:
                return 'Неверный запрос. Проверьте данные';
            case 401:
                return 'Требуется авторизация';
            case 403:
                return 'Доступ запрещен';
            case 404:
                return 'Ресурс не найден';
            case 429:
                return 'Слишком много запросов. Попробуйте позже';
            case 500:
                return 'Внутренняя ошибка сервера';
            case 502:
                return 'Сервер перегружен';
            case 503:
                return 'Сервер временно недоступен';
            case 504:
                return 'Время ожидания истекло';
            default:
                return `Ошибка HTTP ${response.status}`;
        }
    }
}

function toggleNotifications(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const menu = document.getElementById('notification-dropdown');
    if (!menu) return;
    
    const isOpen = menu.classList.contains('show');

    document.querySelectorAll('.nav-dropdown-menu').forEach(m => {
        m.classList.remove('show');
    });
    document.querySelectorAll('.notification-dropdown').forEach(m => {
        m.classList.remove('show');
    });
    
    if (isOpen) {
        menu.classList.remove('show');
        notificationsOpen = false;
    } else {
        menu.classList.add('show');
        notificationsOpen = true;
        loadNotifications();

        markNotificationsAsRead();
    }
}

async function loadNotifications() {
    try {
        const response = await fetch('/api/notifications');
        const data = await response.json();
        
        const list = document.getElementById('notification-list');
        if (!list) return;
        
        if (data.notifications && data.notifications.length > 0) {
            list.innerHTML = data.notifications.map(n => `
                <div class="notification-item ${n.is_read ? '' : 'unread'}" data-id="${n.id}">
                    <div class="notification-type ${n.type}">
                        ${n.type === 'like' ? '❤️' : n.type === 'comment' ? '💬' : n.type === 'follow' ? '👤' : '📢'}
                    </div>
                    <div class="notification-content">
                        <div class="notification-avatar">
                            <img src="${n.avatar || 'https://api.dicebear.com/7.x/avataaars/svg?seed=' + n.id}" alt="${n.username}">
                        </div>
                        <div>
                            <div class="notification-title">${n.title || 'Уведомление'}</div>
                            <div class="notification-message">${n.message}</div>
                            <div class="notification-time">
                                🕐 ${n.time_ago}
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');

            document.querySelectorAll('.notification-item').forEach(item => {
                item.addEventListener('click', () => handleNotificationClick(item.dataset.id));
            });
        } else {
            list.innerHTML = '<div class="notification-empty">Нет уведомлений</div>';
        }

        document.querySelectorAll('.nav-badge').forEach(badge => badge.remove());
    } catch (error) {
        
    }
}

async function handleNotificationClick(notificationId) {
    try {
        await fetch('/api/notifications/read', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: notificationId })
        });
        
        loadNotifications();
    } catch (error) {
        
    }
}

async function markNotificationsAsRead() {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const headers = {};
        if (csrfToken) headers['X-CSRF-Token'] = csrfToken;
        
        await fetch('/api/notifications/read-all', { 
            method: 'POST',
            headers
        });
        document.querySelectorAll('.nav-badge').forEach(badge => badge.remove());
    } catch (error) {
        
    }
}

async function markAllNotificationsRead() {
    await markNotificationsAsRead();
    loadNotifications();
}



function toggleUserMenu(e) {
    e && e.preventDefault();
    e && e.stopPropagation();
    
    const menu = document.getElementById('user-menu');
    if (!menu) return;
    
    const isOpen = menu.classList.contains('show');
    const button = document.querySelector('.nav-user-menu');

    document.querySelectorAll('.nav-dropdown-menu').forEach(m => {
        m.classList.remove('show');
        m.setAttribute('aria-hidden', 'true');
    });
    document.querySelectorAll('.notification-dropdown').forEach(m => {
        m.classList.add('hidden');
    });
    
    if (!isOpen) {
        menu.classList.add('show');
        menu.setAttribute('aria-hidden', 'false');
        if (button) button.setAttribute('aria-expanded', 'true');
    } else {
        menu.classList.remove('show');
        menu.setAttribute('aria-hidden', 'true');
        if (button) button.setAttribute('aria-expanded', 'false');
    }
}

function closeMenus(e) {
    if (!e.target.closest('.nav-dropdown') && !e.target.closest('.nav-notification-link')) {
        const notifDropdown = document.getElementById('notification-dropdown');
        if (notifDropdown) notifDropdown.classList.remove('show');
        
        document.querySelectorAll('.nav-dropdown-menu').forEach(m => {
            m.classList.remove('show');
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {

    document.body.classList.add('theme-loaded');

    document.addEventListener('click', closeMenus);

    setInterval(async () => {
        if (!notificationsOpen && typeof currentUserId !== 'undefined' && currentUserId) {
            try {
                const response = await fetch('/api/notifications');
                const data = await response.json();

                if (data.unread_count > 0) {
                    const notifLink = document.querySelector('.nav-notification-link');
                    if (notifLink && !notifLink.querySelector('.nav-badge')) {
                        const badge = document.createElement('span');
                        badge.className = 'nav-badge';
                        badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                        notifLink.appendChild(badge);
                    }
                }
            } catch (error) {

            }
        }
    }, 30000); // Каждые 30 секунд
});

function animateElement(element, animationClass, duration = null) {
    if (!element) return;

    element.classList.remove('fade-in', 'fade-out', 'slide-up', 'slide-down', 'bounce-in', 'scale-in', 'rotate-in');

    element.classList.add(animationClass);

    if (duration) {
        element.style.animationDuration = duration;
    }

    element.addEventListener('animationend', () => {
        element.classList.remove(animationClass);
        element.style.animationDuration = '';
    });
}

function fadeIn(element, duration = null) {
    animateElement(element, 'fade-in', duration);
}

function fadeOut(element, duration = null) {
    animateElement(element, 'fade-out', duration);
}

function slideUp(element, duration = null) {
    animateElement(element, 'slide-up', duration);
}

function slideDown(element, duration = null) {
    animateElement(element, 'slide-down', duration);
}

function bounceIn(element, duration = null) {
    animateElement(element, 'bounce-in', duration);
}

function scaleIn(element, duration = null) {
    animateElement(element, 'scale-in', duration);
}

function addHoverEffect(element, effectClass = 'hover-lift') {
    if (!element) return;
    element.classList.add(effectClass);
}

function removeHoverEffect(element, effectClass = 'hover-lift') {
    if (!element) return;
    element.classList.remove(effectClass);
}

function showAnimatedNotification(message, type = 'info') {

    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        container.className = 'notification-container';
        document.body.appendChild(container);
    }

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;

    const icon = document.createElement('span');
    icon.className = 'notification-icon';
    icon.textContent = getNotificationIcon(type);

    const text = document.createElement('span');
    text.className = 'notification-text';
    text.textContent = message;

    const closeBtn = document.createElement('button');
    closeBtn.className = 'notification-close';
    closeBtn.innerHTML = '×';
    closeBtn.setAttribute('aria-label', 'Закрыть уведомление');

    notification.appendChild(icon);
    notification.appendChild(text);
    notification.appendChild(closeBtn);

    container.appendChild(notification);

    setTimeout(() => {
        notification.classList.add('notification-enter');
    }, 10);

    const autoClose = setTimeout(() => {
        closeNotification(notification);
    }, 5000);

    closeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        clearTimeout(autoClose);
        closeNotification(notification);
    });

    notification.addEventListener('click', () => {
        clearTimeout(autoClose);
        closeNotification(notification);
    });
}

function closeNotification(notification) {
    if (!notification || !notification.parentNode) return;
    
    notification.classList.add('notification-exit');
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

let touchStartX = 0;
let touchStartY = 0;
let touchEndX = 0;
let touchEndY = 0;
let isSwiping = false;

function initSwipeGestures() {

    if (!('ontouchstart' in window)) {
        return;
    }
    
    const feedContainer = document.querySelector('.feed-container');
    if (!feedContainer) return;

    feedContainer.addEventListener('touchstart', handleTouchStart, { passive: true });
    feedContainer.addEventListener('touchmove', handleTouchMove, { passive: true });
    feedContainer.addEventListener('touchend', handleTouchEnd, { passive: true });

    document.addEventListener('touchstart', handlePostSwipeStart, { passive: true });
    document.addEventListener('touchend', handlePostSwipeEnd, { passive: true });
}

function handleTouchStart(e) {
    const touch = e.touches[0];
    if (!touch) return;
    
    touchStartX = touch.clientX;
    touchStartY = touch.clientY;
    isSwiping = false;
}

function handleTouchMove(e) {
    if (!isSwiping) {
        const touch = e.touches[0];
        if (!touch) return;
        
        const deltaX = touch.clientX - touchStartX;
        const deltaY = touch.clientY - touchStartY;

        if (Math.abs(deltaX) > Math.abs(deltaY)) {
            isSwiping = true;
        }
    }
}

function handleTouchEnd(e) {
    if (!isSwiping) return;
    
    const touch = e.changedTouches[0];
    if (!touch) return;
    
    touchEndX = touch.clientX;
    touchEndY = touch.clientY;
    
    const deltaX = touchEndX - touchStartX;
    const deltaY = touchEndY - touchStartY;

    if (Math.abs(deltaX) > 50) {
        if (deltaX > 0) {
            handleSwipeRight();
        } else {
            handleSwipeLeft();
        }
    }

    if (Math.abs(deltaY) > 50) {
        if (deltaY > 0) {
            handleSwipeDown();
        } else {
            handleSwipeUp();
        }
    }
}
    
function handlePostSwipeStart(e) {
    const postElement = e.target.closest('.post');
    if (!postElement) return;
    
    const touch = e.touches[0];
    if (!touch) return;
    
    postElement.dataset.swipeStartX = touch.clientX;
    postElement.dataset.swipeStartY = touch.clientY;
}

function handlePostSwipeEnd(e) {
    const postElement = e.target.closest('.post');
    if (!postElement) return;
    
    const touch = e.changedTouches[0];
    if (!touch) return;
    
    const swipeEndX = touch.clientX;
    const swipeEndY = touch.clientY;
    
    const swipeStartX = parseFloat(postElement.dataset.swipeStartX) || 0;
    const swipeStartY = parseFloat(postElement.dataset.swipeStartY) || 0;
    
    const deltaX = swipeEndX - swipeStartX;

    if (deltaX < -50) {
        likePost(postElement.dataset.postId);
    }

    else if (deltaX > 50) {
        repostPost(postElement.dataset.postId);
    }
}

function handleSwipeLeft() {

    showNotification('Свайп влево', 'info');
}

function handleSwipeRight() {

    showNotification('Свайп вправо', 'info');
}

function handleSwipeUp() {

    showNotification('Свайп вверх', 'info');
}

function handleSwipeDown() {

    showNotification('Свайп вниз', 'info');
}

function likePost(postId, btn) {
    if (!postId) return;
    if (!window.currentUserId) {
        showNotification('Войдите, чтобы поставить лайк', 'error');
        return;
    }
    
    const postEl = btn?.closest('[data-post-id]') || document.querySelector(`[data-post-id="${postId}"]`);
    if (!postEl && !btn) return;
    
    fetch('/api/post/like', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ post_id: postId })
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {

            if (btn) {
                btn.classList.toggle('liked', result.liked);
                const iconSpan = btn.querySelector('span:first-child');
                if (iconSpan) iconSpan.textContent = result.liked ? '❤️' : '🤍';
                const countSpan = btn.querySelector('span:last-child');
                if (countSpan) countSpan.textContent = result.likes_count;
            }

            if (postEl) {
                const likesEl = postEl.querySelector('.likes-count');
                if (likesEl) likesEl.textContent = `${result.likes_count} лайков`;
                const likeBtn = postEl.querySelector('.btn-like');
                if (likeBtn) likeBtn.classList.toggle('liked', result.liked);
            }
        }
    })
    .catch(err => {});

}

function repostPost(postId) {

    showNotification('Репостнут пост ' + postId, 'success');
}

function initTheme() {

    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);

    createThemeToggle();
}

function setTheme(theme) {

    document.documentElement.setAttribute('data-theme', theme);

    localStorage.setItem('theme', theme);

    updateThemeToggleIcon(theme);

    document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';

    showNotification(`Тема изменена на ${theme === 'dark' ? 'темную' : 'светлую'}`, 'info');
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    setTheme(newTheme);
}

function createThemeToggle() {

    let themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        return; // Кнопка уже существует
    }

    const button = document.createElement('button');
    button.id = 'theme-toggle';
    button.className = 'theme-toggle';
    button.textContent = '🌙';
    button.title = 'Переключить тему';
    button.style.cssText = `
        position: fixed;
        bottom: 20px;
        left: 20px;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        font-size: 20px;
        cursor: pointer;
        box-shadow: var(--shadow-lg);
        transition: all var(--animation-normal);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
    `;

    button.addEventListener('click', toggleTheme);
    button.addEventListener('mouseenter', () => {
        button.style.transform = 'scale(1.1)';
        button.style.boxShadow = 'var(--shadow-xl)';
    });
    button.addEventListener('mouseleave', () => {
        button.style.transform = 'scale(1)';
        button.style.boxShadow = 'var(--shadow-lg)';
    });

    document.body.appendChild(button);

    updateThemeToggleIcon(document.documentElement.getAttribute('data-theme') || 'light');
}

function updateThemeToggleIcon(theme) {
    const button = document.getElementById('theme-toggle');
    if (!button) return;

    button.style.transform = 'scale(0.8) rotate(180deg)';
    
    setTimeout(() => {
        button.textContent = theme === 'dark' ? '☀️' : '🌙';
        button.style.transform = 'scale(1) rotate(0deg)';
    }, 150);
}

function detectAutoTheme() {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const savedTheme = localStorage.getItem('theme');

    if (!savedTheme) {
        setTheme(prefersDark ? 'dark' : 'light');
    }
}

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    const savedTheme = localStorage.getItem('theme');
    if (!savedTheme) {
        setTheme(e.matches ? 'dark' : 'light');
    }
});

let linkPreviewTimeout;
let searchResults = [];

function initSearchFeatures() {
    const searchInput = document.getElementById('global-search');
    if (!searchInput) return;

    searchInput.addEventListener('input', handleSearchInput);
    searchInput.addEventListener('keydown', handleSearchKeydown);

    createSearchResultsContainer();
}

function handleSearchInput(e) {
    const query = e.target.value.trim();

    if (linkPreviewTimeout) {
        clearTimeout(linkPreviewTimeout);
    }

    if (query.length < 2) {
        hideSearchResults();
        return;
    }

    linkPreviewTimeout = setTimeout(() => {
        performSearch(query);
    }, 300);
}

function handleSearchKeydown(e) {
    const resultsContainer = document.getElementById('search-results');
    if (!resultsContainer || resultsContainer.style.display === 'none') return;
    
    const items = resultsContainer.querySelectorAll('.search-result-item');
    const activeItem = resultsContainer.querySelector('.search-result-item.active');
    let activeIndex = -1;
    
    if (activeItem) {
        activeIndex = Array.from(items).indexOf(activeItem);
    }
    
    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            if (activeIndex < items.length - 1) {
                if (activeItem) activeItem.classList.remove('active');
                items[activeIndex + 1].classList.add('active');
                items[activeIndex + 1].scrollIntoView({ block: 'nearest' });
            }
            break;
            
        case 'ArrowUp':
            e.preventDefault();
            if (activeIndex > 0) {
                activeItem.classList.remove('active');
                items[activeIndex - 1].classList.add('active');
                items[activeIndex - 1].scrollIntoView({ block: 'nearest' });
            }
            break;
            
        case 'Enter':
            e.preventDefault();
            if (activeItem) {
                activeItem.click();
            } else {
                performSearch(e.target.value.trim());
            }
            break;
            
        case 'Escape':
            hideSearchResults();
            e.target.blur();
            break;
    }
}
    
function createSearchResultsContainer() {

    let container = document.getElementById('search-results');
    if (container) return;
    
    container = document.createElement('div');
    container.id = 'search-results';
    container.className = 'search-results';
    container.style.cssText = `
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: var(--surface-color);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius-md);
        box-shadow: var(--shadow-lg);
        max-height: 400px;
        overflow-y: auto;
        z-index: var(--z-dropdown);
        display: none;
        margin-top: 4px;
    `;
    
    const searchInput = document.getElementById('global-search');
    if (searchInput && searchInput.parentElement) {
        searchInput.parentElement.style.position = 'relative';
        searchInput.parentElement.appendChild(container);
    }
}
    
async function performSearch(query) {
    try {
        showLoading('Поиск...');

        const searchType = detectSearchType(query);
        
        let searchUrl;
        switch (searchType) {
            case 'hashtag':
                searchUrl = `/api/search/hashtags?q=${encodeURIComponent(query)}`;
                break;
            case 'mention':
                searchUrl = `/api/search/users?q=${encodeURIComponent(query.substring(1))}`;
                break;
            default:
                searchUrl = `/api/search?q=${encodeURIComponent(query)}`;
        }
        
        const response = await fetchWithLoading(searchUrl, { showLoader: false });
        const data = await response.json();
        
        if (data.success) {
            searchResults = data.results || [];
            displaySearchResults(searchResults, searchType);
        } else {
            showNotification(data.error || 'Ошибка поиска', 'error');
            hideSearchResults();
        }
    } catch (error) {
        
        showNotification('Ошибка поиска', 'error');
        hideSearchResults();
    } finally {
        hideLoading();
    }
}

function detectSearchType(query) {
    if (!query || typeof query !== 'string') {
        return 'general';
    }
    if (query.startsWith('#')) {
        return 'hashtag';
    } else if (query.startsWith('@')) {
        return 'mention';
    }
    return 'general';
}

function displaySearchResults(results, searchType) {
    const container = document.getElementById('search-results');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (results.length === 0) {
        container.innerHTML = `
            <div class="search-result-empty">
                <p>Ничего не найдено</p>
            </div>
        `;
        container.style.display = 'block';
        return;
    }

    const groupedResults = groupSearchResults(results, searchType);

    let html = '';
    
    for (const [type, items] of Object.entries(groupedResults)) {
        html += `
            <div class="search-result-section">
                <div class="search-result-header">${getSearchResultTitle(type)}</div>
                <div class="search-result-items">
                    ${items.map(item => createSearchResultItem(item, type)).join('')}
                </div>
            </div>
        `;
    }
    
    container.innerHTML = html;
    container.style.display = 'block';

    container.querySelectorAll('.search-result-item').forEach(item => {
        item.addEventListener('click', handleSearchResultClick);
    });
}

function groupSearchResults(results, searchType) {
    const grouped = {};
    
    if (searchType === 'hashtag') {
        grouped.hashtags = results;
    } else if (searchType === 'mention') {
        grouped.users = results;
    } else {
        results.forEach(result => {
            if (!grouped[result.type]) {
                grouped[result.type] = [];
            }
            grouped[result.type].push(result);
        });
    }
    
    return grouped;
}

function getSearchResultTitle(type) {
    const titles = {
        'posts': 'Посты',
        'users': 'Пользователи',
        'hashtags': 'Хэштеги',
        'mentions': 'Упоминания'
    };
    return titles[type] || type;
}

function createSearchResultItem(item, type) {
    switch (type) {
        case 'posts':
            return `
                <div class="search-result-item" data-type="post" data-id="${item.id}">
                    <div class="search-result-content">
                        <div class="search-result-title">${item.title || 'Без заголовка'}</div>
                        <div class="search-result-text">${item.content || ''}</div>
                        <div class="search-result-meta">
                            <span class="search-result-author">${item.author}</span>
                            <span class="search-result-date">${formatDate(item.created_at)}</span>
                        </div>
                    </div>
                </div>
            `;
            
        case 'users':
            return `
                <div class="search-result-item" data-type="user" data-id="${item.id}">
                    <div class="search-result-avatar">
                        <img src="${item.avatar || '/images/default-avatar.png'}" alt="${item.username}" />
                    </div>
                    <div class="search-result-content">
                        <div class="search-result-title">${item.name || item.username}</div>
                        <div class="search-result-text">@${item.username}</div>
                        ${item.bio ? `<div class="search-result-bio">${item.bio}</div>` : ''}
                    </div>
                </div>
            `;
            
        case 'hashtags':
            return `
                <div class="search-result-item" data-type="hashtag" data-tag="${item.tag}">
                    <div class="search-result-content">
                        <div class="search-result-title">#${item.tag}</div>
                        <div class="search-result-text">${item.count} постов</div>
                    </div>
                </div>
            `;
            
        default:
            return '';
    }
}

function handleSearchResultClick(e) {
    const item = e.currentTarget;
    const type = item.dataset.type;
    const id = item.dataset.id;
    const tag = item.dataset.tag;
    
    switch (type) {
        case 'post':
            window.location.href = `/post/view/${id}`;
            break;
        case 'user':
            window.location.href = `/profile/view/${id}`;
            break;
        case 'hashtag':
            window.location.href = `/search/hashtag/${encodeURIComponent(tag)}`;
            break;
    }
    
    hideSearchResults();
}

function hideSearchResults() {
    const container = document.getElementById('search-results');
    if (container) {
        container.style.display = 'none';
        container.innerHTML = '';
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    
    if (diff < 60000) {
        return 'только что';
    } else if (diff < 3600000) {
        return `${Math.floor(diff / 60000)} мин назад`;
    } else if (diff < 86400000) {
        return `${Math.floor(diff / 3600000)} ч назад`;
    } else {
        return date.toLocaleDateString();
    }
}

let linkPreviewCache = new Map();

function initLinkPreviews() {

    processAllPosts();

    const feedContainer = document.querySelector('.feed-container');
    if (feedContainer) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            processPostForLinks(node);
                        }
                    });
                }
            });
        });
        
        observer.observe(feedContainer, {
            childList: true,
            subtree: true
        });
    }
}

function processAllPosts() {
    const posts = document.querySelectorAll('.post');
    posts.forEach(processPostForLinks);
}

function processPostForLinks(postElement) {
    const contentElement = postElement.querySelector('.post-content, .post-text');
    if (!contentElement) return;
    
    const links = findLinks(contentElement);
    links.forEach(link => {
        if (isValidLinkForPreview(link.href)) {
            addLinkPreview(link);
        }
    });
}

function findLinks(element) {
    const links = [];
    const walker = document.createTreeWalker(
        element,
        NodeFilter.SHOW_ELEMENT,
        {
            acceptNode: (node) => {
                return node.tagName === 'A' && node.href;
            }
        }
    );
    
    let node;
    while (node = walker.nextNode()) {
        links.push(node);
    }
    
    return links;
}

function isValidLinkForPreview(url) {
    try {
        const urlObj = new URL(url);

        if (urlObj.hostname === window.location.hostname) {
            return false;
        }

        if (!['http:', 'https:'].includes(urlObj.protocol)) {
            return false;
        }

        const excludeExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.pdf', '.zip', '.rar'];
        const pathname = urlObj.pathname.toLowerCase();
        if (excludeExtensions.some(ext => pathname.endsWith(ext))) {
            return false;
        }
        
        return true;
    } catch (e) {
        return false;
    }
}

function addLinkPreview(linkElement) {

    if (linkElement.dataset.previewProcessed) {
        return;
    }
    
    linkElement.dataset.previewProcessed = 'true';

    const loadingIndicator = createLoadingIndicator();
    linkElement.appendChild(loadingIndicator);

    clearTimeout(linkPreviewTimeout);
    linkPreviewTimeout = setTimeout(() => {
        loadLinkPreview(linkElement, loadingIndicator);
    }, 1000);
}

function createLoadingIndicator() {
    const indicator = document.createElement('span');
    indicator.className = 'link-preview-loading';
    indicator.innerHTML = '🔗';
    indicator.style.cssText = `
        margin-left: 4px;
        opacity: 0.6;
        animation: pulse 1.5s ease-in-out infinite;
    `;
    return indicator;
}

async function loadLinkPreview(linkElement, loadingIndicator) {
    const url = linkElement.href;

    if (linkPreviewCache.has(url)) {
        const cachedData = linkPreviewCache.get(url);
        if (cachedData) {
            displayLinkPreview(linkElement, cachedData, loadingIndicator);
            return;
        }
    }
        
    try {
        const response = await fetch(`/api/link-preview?url=${encodeURIComponent(url)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch preview');
        }
        
        const data = await response.json();
        
        if (data.success) {

            linkPreviewCache.set(url, data);

            if (linkPreviewCache.size > 100) {
                const firstKey = linkPreviewCache.keys().next().value;
                linkPreviewCache.delete(firstKey);
            }
            
            displayLinkPreview(linkElement, data, loadingIndicator);
        } else {
            removeLoadingIndicator(loadingIndicator);
        }
    } catch (error) {
        
        removeLoadingIndicator(loadingIndicator);
    }
}
    
function displayLinkPreview(linkElement, previewData, loadingIndicator) {

    removeLoadingIndicator(loadingIndicator);

    const previewContainer = createPreviewContainer(previewData);

    linkElement.insertAdjacentElement('afterend', previewContainer);

    setTimeout(() => {
        previewContainer.classList.add('link-preview-visible');
    }, 10);
}

function createPreviewContainer(data) {
    const container = document.createElement('div');
    container.className = 'link-preview-container';
    container.style.cssText = `
        margin: 12px 0;
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius-md);
        overflow: hidden;
        background: var(--surface-color);
        opacity: 0;
        transform: translateY(10px);
        transition: all var(--animation-normal);
    `;
    
    let html = '';
    
    if (data.image) {
        html += `
            <div class="link-preview-image">
                <img src="${data.image}" alt="${data.title || 'Preview'}" loading="lazy" />
            </div>
        `;
    }
    
    html += `
        <div class="link-preview-content">
            <div class="link-preview-title">${data.title || 'Без заголовка'}</div>
            ${data.description ? `<div class="link-preview-description">${data.description}</div>` : ''}
            <div class="link-preview-url">${formatUrlForDisplay(data.url)}</div>
        </div>
    `;
    
    container.innerHTML = html;

    container.addEventListener('click', () => {
        window.open(data.url, '_blank');
    });
    
    container.style.cursor = 'pointer';
    
    return container;
}

function removeLoadingIndicator(indicator) {
    if (indicator && indicator.parentNode) {
        indicator.parentNode.removeChild(indicator);
    }
}

function formatUrlForDisplay(url) {
    try {
        const urlObj = new URL(url);
        return urlObj.hostname + urlObj.pathname;
    } catch (e) {
        return url;
    }
}

function clearLinkPreviewCache() {
    linkPreviewCache.clear();
}

class RequestCache {
    constructor(maxSize = 100, defaultTTL = 5 * 60 * 1000) { // 5 минут по умолчанию
        this.cache = new Map();
        this.maxSize = maxSize;
        this.defaultTTL = defaultTTL;
    }
    
    set(key, data, ttl = this.defaultTTL) {
        const expires = Date.now() + ttl;
        this.cache.set(key, { data, expires });

        if (this.cache.size > this.maxSize) {
            this.cleanup();
        }
    }
    
    get(key) {
        const item = this.cache.get(key);
        if (!item) return null;
        
        if (Date.now() > item.expires) {
            this.cache.delete(key);
            return null;
        }
        
        return item.data;
    }
    
    has(key) {
        const item = this.cache.get(key);
        if (!item) return false;
        
        if (Date.now() > item.expires) {
            this.cache.delete(key);
            return false;
        }
        
        return true;
    }
    
    delete(key) {
        this.cache.delete(key);
    }
    
    clear() {
        this.cache.clear();
    }
    
    cleanup() {
        const now = Date.now();
        for (const [key, item] of this.cache.entries()) {
            if (now > item.expires) {
                this.cache.delete(key);
            }
        }

        if (this.cache.size > this.maxSize) {
            const entries = Array.from(this.cache.entries());
            entries.sort((a, b) => a[1].expires - b[1].expires);
            
            const toDelete = entries.slice(0, this.cache.size - this.maxSize);
            toDelete.forEach(([key]) => this.cache.delete(key));
        }
    }
    
    size() {
        return this.cache.size;
    }
    
    getStats() {
        const now = Date.now();
        let expired = 0;
        let valid = 0;
        
        for (const [key, item] of this.cache.entries()) {
            if (now > item.expires) {
                expired++;
            } else {
                valid++;
            }
        }
        
        return {
            total: this.cache.size,
            valid,
            expired,
            maxSize: this.maxSize
        };
    }
}

const apiCache = new RequestCache(200, 10 * 60 * 1000); // 200 записей, 10 минут
const imageCache = new RequestCache(50, 30 * 60 * 1000);  // 50 записей, 30 минут
const searchCache = new RequestCache(100, 5 * 60 * 1000);  // 100 записей, 5 минут

async function cachedFetch(url, options = {}) {
    const {
        cache = apiCache,
        ttl = cache.defaultTTL,
        bypassCache = false,
        ...fetchOptions
    } = options;

    const cacheKey = generateCacheKey(url, fetchOptions);

    if (!bypassCache && cache.has(cacheKey)) {
        const cachedData = cache.get(cacheKey);

        return Promise.resolve({
            ok: true,
            status: 200,
            cached: true,
            json: () => Promise.resolve(cachedData),
            text: () => Promise.resolve(JSON.stringify(cachedData))
        });
    }
    
    try {
        const response = await fetch(url, fetchOptions);

        if (response.ok && (!fetchOptions.method || fetchOptions.method === 'GET')) {
            try {
                const data = await response.clone().json();
                cache.set(cacheKey, data, ttl);
            } catch (e) {

                
            }
        }
        
        return response;
    } catch (error) {
        
        throw error;
    }
}

function generateCacheKey(url, options) {
    const method = options.method || 'GET';
    const body = options.body ? JSON.stringify(options.body) : '';
    const headers = options.headers ? JSON.stringify(options.headers) : '';
    
    return `${method}:${url}:${body}:${headers}`;
}

function clearAllCaches() {
    apiCache.clear();
    imageCache.clear();
    searchCache.clear();
    showNotification('Кэш очищен', 'success');
}

function getCacheStats() {
    const apiStats = apiCache.getStats();
    const imageStats = imageCache.getStats();
    const searchStats = searchCache.getStats();
    
    return {
        api: apiStats,
        images: imageStats,
        search: searchStats,
        total: apiStats.total + imageStats.total + searchStats.total
    };
}

function initCacheCleanup() {

    setInterval(() => {
        apiCache.cleanup();
        imageCache.cleanup();
        searchCache.cleanup();
    }, 5 * 60 * 1000);

    window.addEventListener('beforeunload', () => {

        const stats = getCacheStats();
        sessionStorage.setItem('cacheStats', JSON.stringify(stats));
    });
}

class FormValidator {
    constructor(form, rules = {}) {
        this.form = form;
        this.rules = rules;
        this.errors = {};
        this.serverValidationEnabled = true;
        this.uniqueCache = null;
        this.init();
    }
    
    init() {

        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        this.form.addEventListener('input', (e) => this.handleInput(e));
        this.form.addEventListener('blur', (e) => this.handleBlur(e), true);

        this.createErrorContainer();
    }
    
    createErrorContainer() {
        let container = this.form.querySelector('.form-errors');
        if (!container) {
            container = document.createElement('div');
            container.className = 'form-errors';
            container.style.cssText = `
                margin-bottom: 16px;
                padding: 12px;
                border-radius: var(--border-radius-md);
                background: var(--error-color);
                color: white;
                font-size: var(--font-size-sm);
                display: none;
            `;
            this.form.insertBefore(container, this.form.firstChild);
        }
        this.errorContainer = container;
    }
    
    handleSubmit(e) {
        e.preventDefault();
        
        if (this.validate()) {

            this.form.dispatchEvent(new CustomEvent('validSubmit', {
                detail: { formData: this.getFormData() }
            }));
        } else {

            this.showErrors();
        }
    }
    
    handleInput(e) {
        const field = e.target;
        if (field.name && this.isFormField(field)) {
            this.clearFieldError(field.name);
            this.validateField(field.name);
        }
    }
    
    handleBlur(e) {
        const field = e.target;
        if (field.name && this.isFormField(field)) {
            this.validateField(field.name);
        }
    }
    
    isFormField(element) {
        const tag = element.tagName.toLowerCase();
        return tag === 'input' || tag === 'textarea' || tag === 'select';
    }
    
    validate() {
        this.errors = {};
        let isValid = true;
        
        for (const [fieldName, rules] of Object.entries(this.rules)) {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (!field) continue;
            
            const fieldErrors = this.validateField(fieldName, field, rules);
            if (fieldErrors.length > 0) {
                this.errors[fieldName] = fieldErrors;
                isValid = false;
            }
        }
        
        return isValid;
    }
    
    validateField(fieldName, field, rules) {

        if (arguments.length === 1) {
            field = this.form.querySelector(`[name="${fieldName}"]`);
            rules = this.rules[fieldName];
        }
        
        if (!field || !rules) return [];
        
        const value = field.value.trim();
        const errors = [];
        
        for (const rule of rules) {
            const error = this.applyRule(rule, value, field);
            if (error) {
                errors.push(error);
            }
        }
        
        return errors;
    }
    
    applyRule(rule, value, field) {
        const { type, params = {} } = rule;
        
        switch (type) {
            case 'required':
                return !value ? 'Это поле обязательно для заполнения' : null;
                
            case 'email':
                return value && !this.isValidEmail(value) ? 'Введите корректный email' : null;
                
            case 'minLength':
                return value && value.length < params.length ? `Минимум ${params.length} символов` : null;
                
            case 'maxLength':
                return value && value.length > params.length ? `Максимум ${params.length} символов` : null;
                
            case 'pattern':
                return value && !params.regex.test(value) ? params.message || 'Неверный формат' : null;
                
            case 'password':
                return value && !this.isValidPassword(value) ? params.message || 'Слишком простой пароль' : null;
                
            case 'confirm':
                const confirmField = this.form.querySelector(`[name="${params.field}"]`);
                const confirmValue = confirmField ? confirmField.value : '';
                return value !== confirmValue ? 'Пароли не совпадают' : null;
                
            case 'username':
                return value && !this.isValidUsername(value) ? params.message || 'Неверный формат имени пользователя' : null;
                
            case 'url':
                return value && !this.isValidUrl(value) ? 'Введите корректный URL' : null;
                
            case 'unique':
                return this.validateUnique(field, value, params);
                
            case 'server':
                return this.validateWithServer(field, value, params);
                
            default:
                return null;
        }
    }
    
    async validateUnique(field, value, params) {
        if (!value || !params.endpoint) return null;

        const cacheKey = `unique:${params.endpoint}:${value}`;
        if (this.uniqueCache && this.uniqueCache.has(cacheKey)) {
            return this.uniqueCache.get(cacheKey);
        }
        
        try {
            const response = await cachedFetch(`${params.endpoint}?value=${encodeURIComponent(value)}`, {
                cache: searchCache,
                ttl: 5 * 60 * 1000 // 5 минут
            });
            
            const data = await response.json();
            
            if (!data.unique) {
                const error = params.message || 'Значение уже используется';

                if (!this.uniqueCache) this.uniqueCache = new Map();
                this.uniqueCache.set(cacheKey, error);
                
                return error;
            }
            
            return null;
        } catch (error) {
            
            return null; // Не блокируем отправку при ошибке сети
        }
    }
    
    async validateWithServer(field, value, params) {
        if (!this.serverValidationEnabled) return null;

        const validationKey = `server:${params.field}:${Date.now()}`;
        
        try {
            const formData = new FormData();
            formData.set(params.field, value);
            formData.set('validate', '1');
            formData.set('validation_key', validationKey);
            
            const response = await fetch(this.form.action || window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.errors && data.errors[params.field]) {
                return Array.isArray(data.errors[params.field]) 
                    ? data.errors[params.field][0] 
                    : data.errors[params.field];
            }
            
            return null;
        } catch (error) {
            
            return null;
        }
    }
    
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    isValidPassword(password) {

        return password.length >= 8 && 
               /[a-zA-Z]/.test(password) && 
               /\d/.test(password);
    }
    
    isValidUsername(username) {

        const usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;
        return usernameRegex.test(username);
    }
    
    isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }
    
    showErrors() {
        if (!this.errorContainer) return;
        
        const errorMessages = Object.values(this.errors).flat();
        if (errorMessages.length > 0) {
            this.errorContainer.innerHTML = `
                <div class="error-title">Исправьте следующие ошибки:</div>
                <ul class="error-list">
                    ${errorMessages.map(error => `<li>${error}</li>`).join('')}
                </ul>
            `;
            this.errorContainer.style.display = 'block';

            this.errorContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
    
    clearErrors() {
        if (this.errorContainer) {
            this.errorContainer.style.display = 'none';
            this.errorContainer.innerHTML = '';
        }

        this.form.querySelectorAll('.field-error').forEach(field => {
            field.classList.remove('field-error');
        });

        this.form.querySelectorAll('.field-error-message').forEach(msg => {
            msg.remove();
        });
    }
    
    clearFieldError(fieldName) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.classList.remove('field-error');
            
            const errorMsg = field.parentNode.querySelector('.field-error-message');
            if (errorMsg) {
                errorMsg.remove();
            }
        }
    }
    
    showFieldError(fieldName, message) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (!field) return;
        
        field.classList.add('field-error');
        
        let errorMsg = field.parentNode.querySelector('.field-error-message');
        if (!errorMsg) {
            errorMsg = document.createElement('div');
            errorMsg.className = 'field-error-message';
            errorMsg.style.cssText = `
                color: var(--error-color);
                font-size: var(--font-size-xs);
                margin-top: 4px;
                display: block;
            `;
            field.parentNode.appendChild(errorMsg);
        }
        
        errorMsg.textContent = message;
    }
    
    getFormData() {
        const formData = new FormData(this.form);
        const data = {};
        
        for (const [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        return data;
    }
    
    addRule(fieldName, rule) {
        if (!this.rules[fieldName]) {
            this.rules[fieldName] = [];
        }
        this.rules[fieldName].push(rule);
    }
    
    removeRule(fieldName, ruleType) {
        if (this.rules[fieldName]) {
            this.rules[fieldName] = this.rules[fieldName].filter(rule => rule.type !== ruleType);
        }
    }
}

const validationRules = {
    email: [
        { type: 'required' },
        { type: 'email' },
        { type: 'unique', endpoint: '/api/validate/email', message: 'Такой email уже используется' },
        { type: 'server', field: 'email' }
    ],
    password: [
        { type: 'required' },
        { type: 'minLength', params: { length: 8 } },
        { type: 'password' },
        { type: 'server', field: 'password' }
    ],
    passwordConfirm: [
        { type: 'required' },
        { type: 'confirm', params: { field: 'password' } },
        { type: 'server', field: 'password_confirm' }
    ],
    username: [
        { type: 'required' },
        { type: 'username' },
        { type: 'unique', endpoint: '/api/validate/username', message: 'Такой логин уже занят' },
        { type: 'server', field: 'username' }
    ],
    postContent: [
        { type: 'required' },
        { type: 'minLength', params: { length: 1 } },
        { type: 'maxLength', params: { length: 1000 } },
        { type: 'server', field: 'content' }
    ],
    comment: [
        { type: 'required' },
        { type: 'minLength', params: { length: 1 } },
        { type: 'maxLength', params: { length: 500 } },
        { type: 'server', field: 'content' }
    ],
    profile: {
        name: [
            { type: 'required' },
            { type: 'maxLength', params: { length: 50 } },
            { type: 'server', field: 'name' }
        ],
        bio: [
            { type: 'maxLength', params: { length: 200 } },
            { type: 'server', field: 'bio' }
        ],
        website: [
            { type: 'url' },
            { type: 'server', field: 'website' }
        ]
    }
};

function initFormValidation() {

    const loginForm = document.querySelector('#login-form');
    if (loginForm) {
        new FormValidator(loginForm, {
            email: [
                { type: 'required' },
                { type: 'email' }
            ],
            password: [
                { type: 'required' }
            ]
        });
    }

    const registerForm = document.querySelector('#register-form');
    if (registerForm) {
        new FormValidator(registerForm, {
            username: validationRules.username,
            email: validationRules.email,
            password: validationRules.password,
            passwordConfirm: validationRules.passwordConfirm
        });
    }

    const postForm = document.querySelector('#post-form');
    if (postForm) {
        new FormValidator(postForm, {
            content: validationRules.postContent
        });
    }

    const commentForm = document.querySelector('#comment-form');
    if (commentForm) {
        new FormValidator(commentForm, {
            content: validationRules.comment
        });
    }

    const profileForm = document.querySelector('#profile-form');
    if (profileForm) {
        new FormValidator(profileForm, validationRules.profile);
    }

    const settingsForm = document.querySelector('#settings-form');
    if (settingsForm) {
        new FormValidator(settingsForm, {
            email: validationRules.email,
            name: validationRules.profile.name,
            bio: validationRules.profile.bio,
            website: validationRules.profile.website
        });
    }
}

function initYii2FormValidation() {

    const yii2Forms = document.querySelectorAll('.yii2-form');
    
    yii2Forms.forEach(form => {
        const formId = form.id;
        const yii2Data = window.yii2 && window.yii2[formId];
        
        if (yii2Data) {

            const convertedRules = convertYii2Rules(yii2Data.validationRules || {});
            
            new FormValidator(form, convertedRules);
        }
    });
}

function convertYii2Rules(yii2Rules) {
    const convertedRules = {};
    
    for (const [field, rules] of Object.entries(yii2Rules)) {
        convertedRules[field] = [];
        
        rules.forEach(rule => {
            switch (rule) {
                case 'required':
                    convertedRules[field].push({ type: 'required' });
                    break;
                case 'email':
                    convertedRules[field].push({ type: 'email' });
                    break;
                case 'string':
                    if (rule.min !== undefined) {
                        convertedRules[field].push({ 
                            type: 'minLength', 
                            params: { length: rule.min } 
                        });
                    }
                    if (rule.max !== undefined) {
                        convertedRules[field].push({ 
                            type: 'maxLength', 
                            params: { length: rule.max } 
                        });
                    }
                    break;
                case 'url':
                    convertedRules[field].push({ type: 'url' });
                    break;
                default:

                    convertedRules[field].push({ 
                        type: 'server', 
                        field: field 
                    });
            }
        });
    }
    
    return convertedRules;
}

class DragDropUploader {
    constructor(options = {}) {
        this.options = {
            maxFiles: 5,
            maxFileSize: 5 * 1024 * 1024, // 5MB
            allowedTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            dropZone: null,
            input: null,
            onFilesSelected: null,
            onUploadStart: null,
            onUploadProgress: null,
            onUploadComplete: null,
            onError: null,
            ...options
        };
        
        this.init();
    }
    
    init() {
        this.setupDropZone();
        this.setupFileInput();
        this.setupEventListeners();
    }
    
    setupDropZone() {
        const dropZone = this.options.dropZone;
        if (!dropZone) return;

        dropZone.setAttribute('role', 'button');
        dropZone.setAttribute('aria-label', 'Загрузить изображения перетаскиванием');
        dropZone.setAttribute('tabindex', '0');

        dropZone.innerHTML = `
            <div class="drop-zone-content">
                <div class="drop-zone-icon">📁</div>
                <div class="drop-zone-text">
                    <div class="drop-zone-title">Перетащите изображения сюда</div>
                    <div class="drop-zone-subtitle">или нажмите для выбора</div>
                </div>
                <div class="drop-zone-progress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <div class="progress-text">0%</div>
                </div>
            </div>
        `;

        dropZone.style.cssText = `
            border: 2px dashed var(--border-color);
            border-radius: var(--border-radius-lg);
            background: var(--surface-color);
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all var(--animation-normal);
            position: relative;
            overflow: hidden;
        `;
        
        this.dropZone = dropZone;
    }
    
    setupFileInput() {
        const input = this.options.input;
        if (!input) return;
        
        input.type = 'file';
        input.multiple = true;
        input.accept = this.options.allowedTypes.join(',');
        input.style.display = 'none';
        
        this.fileInput = input;
    }
    
    setupEventListeners() {
        const dropZone = this.dropZone;
        const input = this.fileInput;
        
        if (!dropZone || !input) return;

        dropZone.addEventListener('dragenter', this.handleDragEnter.bind(this));
        dropZone.addEventListener('dragover', this.handleDragOver.bind(this));
        dropZone.addEventListener('dragleave', this.handleDragLeave.bind(this));
        dropZone.addEventListener('drop', this.handleDrop.bind(this));

        dropZone.addEventListener('click', this.handleClick.bind(this));
        dropZone.addEventListener('keydown', this.handleKeyDown.bind(this));

        input.addEventListener('change', this.handleFileSelect.bind(this));

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            document.addEventListener(eventName, this.preventDefaults.bind(this), false);
        });
    }
    
    preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    handleDragEnter(e) {
        this.preventDefaults(e);
        this.dropZone.classList.add('drag-over');
        this.dropZone.style.borderColor = 'var(--primary-color)';
        this.dropZone.style.background = 'var(--background-color)';
    }
    
    handleDragOver(e) {
        this.preventDefaults(e);
        this.dropZone.classList.add('drag-over');
        this.dropZone.style.borderColor = 'var(--primary-color)';
        this.dropZone.style.background = 'var(--background-color)';
    }
    
    handleDragLeave(e) {
        this.preventDefaults(e);
        this.dropZone.classList.remove('drag-over');
        this.dropZone.style.borderColor = 'var(--border-color)';
        this.dropZone.style.background = 'var(--surface-color)';
    }
    
    handleDrop(e) {
        this.preventDefaults(e);
        this.dropZone.classList.remove('drag-over');
        this.dropZone.style.borderColor = 'var(--border-color)';
        this.dropZone.style.background = 'var(--surface-color)';
        
        const files = Array.from(e.dataTransfer.files);
        this.processFiles(files);
    }
    
    handleClick() {
        this.fileInput.click();
    }
    
    handleKeyDown(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.fileInput.click();
        }
    }
    
    handleFileSelect(e) {
        const files = Array.from(e.target.files);
        this.processFiles(files);
    }
    
    processFiles(files) {
        const validFiles = this.validateFiles(files);
        
        if (validFiles.length === 0) {
            return;
        }
        
        if (this.options.onFilesSelected) {
            this.options.onFilesSelected(validFiles);
        }
        
        this.uploadFiles(validFiles);
    }
    
    validateFiles(files) {
        const validFiles = [];
        
        for (const file of files) {

            if (!this.options.allowedTypes.includes(file.type)) {
                this.showError(`Файл ${file.name} имеет неподдерживаемый тип`);
                continue;
            }

            if (file.size > this.options.maxFileSize) {
                this.showError(`Файл ${file.name} слишком большой (максимум ${this.formatFileSize(this.options.maxFileSize)})`);
                continue;
            }
            
            validFiles.push(file);
        }

        if (validFiles.length > this.options.maxFiles) {
            this.showError(`Максимум ${this.options.maxFiles} файлов за раз`);
            return validFiles.slice(0, this.options.maxFiles);
        }
        
        return validFiles;
    }
    
    async uploadFiles(files) {
        const progressContainer = this.dropZone.querySelector('.drop-zone-progress');
        const progressFill = this.dropZone.querySelector('.progress-fill');
        const progressText = this.dropZone.querySelector('.progress-text');
        
        progressContainer.style.display = 'block';
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            
            if (this.options.onUploadStart) {
                this.options.onUploadStart(file, i);
            }
            
            try {
                await this.uploadFile(file, (progress) => {
                    const totalProgress = ((i + progress / 100) / files.length) * 100;
                    progressFill.style.width = `${totalProgress}%`;
                    progressText.textContent = `${Math.round(totalProgress)}%`;
                    
                    if (this.options.onUploadProgress) {
                        this.options.onUploadProgress(file, progress, totalProgress);
                    }
                });
                
                if (this.options.onUploadComplete) {
                    this.options.onUploadComplete(file, i);
                }
            } catch (error) {
                if (this.options.onError) {
                    this.options.onError(file, error);
                }
                this.showError(`Ошибка загрузки ${file.name}: ${error.message}`);
            }
        }

        setTimeout(() => {
            progressContainer.style.display = 'none';
            progressFill.style.width = '0%';
            progressText.textContent = '0%';
        }, 2000);
    }
    
    async uploadFile(file, onProgress) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', file);
            
            const xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const progress = (e.loaded / e.total) * 100;
                    onProgress(progress);
                }
            });
            
            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve(xhr.response);
                } else {
                    reject(new Error(`HTTP ${xhr.status}`));
                }
            });
            
            xhr.addEventListener('error', () => {
                reject(new Error('Network error'));
            });
            
            xhr.open('POST', '/api/upload');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(formData);
        });
    }
    
    showError(message) {
        showNotification(message, 'error');
    }
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    reset() {
        const progressContainer = this.dropZone.querySelector('.drop-zone-progress');
        if (progressContainer) {
            progressContainer.style.display = 'none';
        }
        
        const progressFill = this.dropZone.querySelector('.progress-fill');
        if (progressFill) {
            progressFill.style.width = '0%';
        }
        
        const progressText = this.dropZone.querySelector('.progress-text');
        if (progressText) {
            progressText.textContent = '0%';
        }
    }
}

function initDragDropUploaders() {

    const postDropZone = document.querySelector('#post-drop-zone');
    const postFileInput = document.querySelector('#post-file-input');
    
    if (postDropZone && postFileInput) {
        new DragDropUploader({
            dropZone: postDropZone,
            input: postFileInput,
            maxFiles: 5,
            maxFileSize: 5 * 1024 * 1024,
            onFilesSelected: (files) => {
                showNotification(`Выбрано ${files.length} файлов`, 'info');
            },
            onUploadComplete: (file, index) => {
                showNotification(`Файл ${file.name} загружен`, 'success');
            },
            onError: (file, error) => {
                showNotification(`Ошибка загрузки ${file.name}`, 'error');
            }
        });
    }

    const commentDropZone = document.querySelector('#comment-drop-zone');
    const commentFileInput = document.querySelector('#comment-file-input');
    
    if (commentDropZone && commentFileInput) {
        new DragDropUploader({
            dropZone: commentDropZone,
            input: commentFileInput,
            maxFiles: 1,
            maxFileSize: 2 * 1024 * 1024,
            onFilesSelected: (files) => {
                showNotification('Файл для комментария выбран', 'info');
            },
            onUploadComplete: (file) => {
                showNotification('Файл для комментария загружен', 'success');
            },
            onError: (file, error) => {
                showNotification(`Ошибка загрузки файла комментария`, 'error');
            }
        });
    }

    const avatarDropZone = document.querySelector('#avatar-drop-zone');
    const avatarFileInput = document.querySelector('#avatar-file-input');
    
    if (avatarDropZone && avatarFileInput) {
        new DragDropUploader({
            dropZone: avatarDropZone,
            input: avatarFileInput,
            maxFiles: 1,
            maxFileSize: 2 * 1024 * 1024,
            allowedTypes: ['image/jpeg', 'image/png', 'image/webp'],
            onFilesSelected: (files) => {
                showNotification('Аватар выбран', 'info');
            },
            onUploadComplete: (file) => {
                showNotification('Аватар загружен', 'success');
            },
            onError: (file, error) => {
                showNotification(`Ошибка загрузки аватара`, 'error');
            }
        });
    }
}

function updateOnlineCount() {
    fetch('/api/poll')
        .then(response => response.json())
        .then(data => {
            if (data.online_count !== undefined) {
                const onlineCountElement = document.getElementById('online-count');
                if (onlineCountElement) {
                    onlineCountElement.textContent = data.online_count;
                }
            }
        })
        .catch(error => {
            
        });
}

setInterval(updateOnlineCount, 30000);

document.addEventListener('DOMContentLoaded', () => {
    initServiceWorker();
    initSwipeGestures();
    initTheme();
    detectAutoTheme();
    initSearchFeatures();
    initLinkPreviews();
    initCacheCleanup();
    initFormValidation();
    initYii2FormValidation();
    initDragDropUploaders();
    updateOnlineCount(); // Обновляем онлайн-счетчик при загрузке
});
