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
            renderEmptyState('Ошибка загрузки');
        } else {
            renderEmptyState();
        }
    } catch (error) {
        renderEmptyState('Ошибка загрузки');
    }
}

// ==================== Render ====================
function renderEmptyState(message = 'У вас нет заблокированных пользователей') {
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
            <div class="user-email">Заблокирован ${getTimeAgo(user.blocked_at)}</div>
        </div>
        <button class="btn-unblock" data-user-id="${user.id}" data-username="${escapeHtml(user.username)}" title="Разблокировать">
            🔓
        </button>
    `;
    return div;
}

// ==================== Actions ====================
async function unblockUser(userId, username) {
    if (typeof showDeleteModal !== 'function') {
        if (typeof showNotification === 'function') {
            showNotification('Не загружены скрипты интерфейса', 'error');
        }
        return;
    }

    showDeleteModal(`Разблокировать пользователя ${username}?`, async () => {
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
                showNotification(`Пользователь ${username} разблокирован`, 'success');
            } else {
                showNotification(result.error || 'Ошибка разблокировки', 'error');
            }
        } catch (error) {
            showNotification('Ошибка разблокировки', 'error');
        }
    });
}

// ==================== Helpers ====================
function getTimeAgo(timestamp) {
    const diff = Math.floor(Date.now() / 1000) - timestamp;

    if (diff < 60) return 'только что';
    if (diff < 3600) return Math.floor(diff / 60) + ' мин. назад';
    if (diff < 86400) return Math.floor(diff / 3600) + ' ч. назад';
    if (diff < 2592000) return Math.floor(diff / 86400) + ' дн. назад';

    return new Date(timestamp * 1000).toLocaleDateString();
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