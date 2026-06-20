// ==================== State ====================
let blockedUsers = [];

// ==================== API Calls ====================
async function loadBlockedUsers() {
    const container = document.getElementById('blocked-users-container');
    if (!container) return;

    try {
        const response = await fetch('/api/block/list', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();

        if (data.success) {
            blockedUsers = data.blocked_users;
            renderBlockedUsers();
        } else if (data.error) {
            renderEmptyState(window.t('loading_error'));
        } else {
            renderEmptyState();
        }
    } catch (error) {
        renderEmptyState(window.t('loading_error'));
    }
}

// ==================== Render ====================
function renderEmptyState(message) {
    if (message === undefined) message = window.t('no_blocked_users');
    const container = document.getElementById('blocked-users-container');
    if (!container) return;
    container.innerHTML = `
        <div class="empty-state">
            <div class="empty-icon">🚫</div>
            <p>${message}</p>
        </div>
    `;
}

function renderBlockedUsers() {
    const container = document.getElementById('blocked-users-container');
    if (!container) return;

    if (!blockedUsers.length) {
        renderEmptyState();
        return;
    }

    container.innerHTML = '';
    blockedUsers.forEach(user => {
        container.appendChild(createUserCard(user));
    });
}

function createUserCard(user) {
    const div = document.createElement('div');
    div.className = 'user-card';
    div.innerHTML = `
        <img class="user-avatar" src="${escapeHtml(user.avatar)}" alt="${escapeHtml(user.username)}">
        <div class="user-info">
            <div class="user-name">${escapeHtml(user.username)}</div>
            <div class="user-email">${window.t('blocked_at', { time: getTimeAgo(user.blocked_at) })}</div>
        </div>
        <button class="btn-unblock" data-user-id="${user.id}" data-username="${escapeHtml(user.username)}" title="${window.t('unblock_title')}">
            🔓
        </button>
    `;
    return div;
}

// ==================== Actions ====================
async function unblockUser(userId, username) {
    if (typeof showDeleteModal !== 'function') {
        if (typeof showNotification === 'function') {
            showNotification(window.t('scripts_not_loaded_short'), 'error');
        }
        return;
    }

    showDeleteModal(window.t('unblock_user_question', { username }), async () => {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const headers = { 'Content-Type': 'application/json' };
            if (csrfToken) headers['X-CSRF-Token'] = csrfToken;

            const response = await fetch('/api/block/unblock', {
                method: 'POST',
                headers: headers,
                body: JSON.stringify({ user_id: userId })
            });

            const result = await response.json();
            if (result.success) {
                blockedUsers = blockedUsers.filter(u => u.id !== userId);
                renderBlockedUsers();
                showNotification(window.t('user_unblocked', { username }), 'success');
            } else {
                showNotification(result.error || window.t('unblock_error'), 'error');
            }
        } catch (error) {
            showNotification(window.t('unblock_error'), 'error');
        }
    });
}

// ==================== Helpers ====================
function getTimeAgo(timestamp) {
    const diff = Math.floor(Date.now() / 1000) - timestamp;

    if (diff < 60) return window.t('just_now');
    if (diff < 3600) return Math.floor(diff / 60) + ' ' + window.t('minutes_ago');
    if (diff < 86400) return Math.floor(diff / 3600) + ' ' + window.t('hours_ago');
    if (diff < 2592000) return Math.floor(diff / 86400) + ' ' + window.t('days_ago');

    return new Date(timestamp * 1000).toLocaleDateString(document.documentElement.lang || undefined);
}

// ==================== Event Delegation ====================
document.addEventListener('click', (e) => {
    const unblockBtn = e.target.closest('.btn-unblock');
    if (unblockBtn) {
        e.preventDefault();
        const userId = unblockBtn.dataset.userId;
        const username = unblockBtn.dataset.username;
        if (userId && username) {
            unblockUser(userId, username);
        }
    }
});

// ==================== Init ====================
document.addEventListener('DOMContentLoaded', () => {
    loadBlockedUsers();
});
