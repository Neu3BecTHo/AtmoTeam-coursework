

let blockedUsers = [];
let currentPage = 1;
const usersPerPage = 20;

async function loadBlockedUsers(page = 1, append = false) {
    try {
        const response = await fetch(`/api/block/list?page=${page}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.success) {
            blockedUsers = data.blocked_users;
            renderBlockedUsers(append);
        }
    } catch (error) {
        
    }
}

function renderBlockedUsers(append = false) {
    const container = document.getElementById('blocked-users-container');
    
    if (!append) {
        container.innerHTML = '';
    }
    
    if (blockedUsers.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">🚫</div>
                <p>У вас нет заблокированных пользователей</p>
            </div>
        `;
        return;
    }
    
    blockedUsers.forEach(user => {
        const userCard = createUserCard(user);
        if (append) {
            container.appendChild(userCard);
        } else {
            container.innerHTML += userCard.outerHTML;
        }
    });
}

function createUserCard(user) {
    const div = document.createElement('div');
    div.className = 'user-card';
    div.innerHTML = `
        <img class="user-avatar" src="${user.avatar}" alt="${user.username}">
        <div class="user-info">
            <div class="user-name">${user.username}</div>
            <div class="user-email">Заблокирован ${getTimeAgo(user.blocked_at)}</div>
        </div>
        <button class="btn-unblock" onclick="unblockUser(${user.id}, '${user.username}')" title="Разблокировать">
            🔓
        </button>
    `;
    return div;
}

async function unblockUser(userId, username) {
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

function getTimeAgo(timestamp) {
    const diff = Math.floor(Date.now() / 1000) - timestamp;
    
    if (diff < 60) return 'только что';
    if (diff < 3600) return Math.floor(diff / 60) + ' мин. назад';
    if (diff < 86400) return Math.floor(diff / 3600) + ' ч. назад';
    if (diff < 2592000) return Math.floor(diff / 86400) + ' дн. назад';
    
    return new Date(timestamp * 1000).toLocaleDateString();
}

document.addEventListener('DOMContentLoaded', () => {
    loadBlockedUsers();
});

