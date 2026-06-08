// ==================== Admin Helpers ====================

function adminCall(action, data, confirmMessage, successMessage) {
    if (typeof showDeleteModal !== 'function' || typeof postWithCsrf !== 'function') {
        if (typeof showNotification === 'function') {
            showNotification('Не загружены скрипты интерфейса (common.js)', 'error');
        }
        return;
    }
    showDeleteModal(confirmMessage, async () => {
        try {
            const response = await postWithCsrf(action, data);
            const result = await response.json();
            if (result.success) {
                showNotification(successMessage, 'success');
                return true;
            } else {
                showNotification(result.error || 'Ошибка', 'error');
                return false;
            }
        } catch (error) {
            showNotification('Ошибка', 'error');
            return false;
        }
    });
}

function adminRemoveRow(element, selectors) {
    let target = element;
    if (typeof element === 'string') {
        target = document.querySelector(element);
    }
    if (target && target.closest) {
        const row = target.closest(selectors);
        if (row) row.remove();
        return;
    }
    const fallback = document.querySelector(selectors);
    if (fallback) fallback.remove();
}

// ==================== Admin Actions ====================

async function adminDeletePost(postId) {
    const result = await adminCall(
        '/api/admin/delete-post',
        { post_id: postId },
        'Удалить этот пост?',
        'Пост удален'
    );
    if (result) adminRemoveRow(`.btn-delete-post[data-post-id="${postId}"]`, '.recent-post-item, .post-row, tr.post-row');
}

async function adminDeleteUser(userId) {
    const result = await adminCall(
        '/api/admin/delete-user',
        { user_id: userId },
        'Удалить этого пользователя? Все его данные будут удалены!',
        'Пользователь удален'
    );
    if (result) adminRemoveRow(`.btn-delete-user[data-user-id="${userId}"], .btn-delete[data-user-id="${userId}"]`, '.user-row, tr.user-row');
}

async function adminDeleteComment(commentId) {
    const result = await adminCall(
        '/api/admin/delete-comment',
        { comment_id: commentId },
        'Удалить этот комментарий?',
        'Комментарий удален'
    );
    if (result) adminRemoveRow(`.btn-delete-comment[data-comment-id="${commentId}"]`, '.comment-row, tr.comment-row');
}

async function adminBlockSiteUser(userId) {
    await adminCall(
        '/api/admin/block-user',
        { user_id: userId },
        'Заблокировать этого пользователя на сайте?',
        'Пользователь заблокирован'
    );
    location.reload();
}

async function adminUnblockSiteUser(userId) {
    await adminCall(
        '/api/admin/unblock-user',
        { user_id: userId },
        'Разблокировать этого пользователя?',
        'Пользователь разблокирован'
    );
    location.reload();
}

// ==================== Stats Updater ====================

async function updateStats() {
    try {
        const response = await fetch('/api/admin/stats');
        const data = await response.json();
        const stats = data.stats || data;
        if (!stats || typeof stats.users === 'undefined') return;
        const selectors = [
            '.stat-card:nth-child(1) .stat-value',
            '.stat-card:nth-child(2) .stat-value',
            '.stat-card:nth-child(3) .stat-value',
            '.stat-card:nth-child(4) .stat-value',
        ];
        const values = [stats.users, stats.posts, stats.comments, stats.notifications];
        selectors.forEach((sel, i) => {
            const el = document.querySelector(sel);
            if (el && values[i] !== undefined) el.textContent = values[i];
        });
    } catch (e) {}
}

setInterval(() => {
    if (document.querySelector('.stat-card:nth-child(1) .stat-value')) updateStats();
}, 60000);

// ==================== Event Delegation ====================

document.addEventListener('click', (e) => {
    const root = e.target.closest('.admin-container');
    if (!root) return;

    const target = e.target;

    const delPost = target.closest('.btn-delete-post');
    if (delPost && root.contains(delPost)) {
        e.preventDefault();
        const postId = delPost.dataset.postId;
        if (postId) adminDeletePost(postId);
        return;
    }

    const delUser = target.closest('.btn-delete-user, .btn-delete[data-user-id]');
    if (delUser && root.contains(delUser)) {
        e.preventDefault();
        const userId = delUser.dataset.userId;
        if (userId) adminDeleteUser(userId);
        return;
    }

    const delComment = target.closest('.btn-delete-comment');
    if (delComment && root.contains(delComment)) {
        e.preventDefault();
        const commentId = delComment.dataset.commentId;
        if (commentId) adminDeleteComment(commentId);
        return;
    }

    const blockUser = target.closest('.btn-block[data-user-id]');
    if (blockUser && root.contains(blockUser)) {
        e.preventDefault();
        const userId = blockUser.dataset.userId;
        if (userId) adminBlockSiteUser(userId);
        return;
    }

    const unblockUser = target.closest('.btn-unblock[data-user-id]');
    if (unblockUser && root.contains(unblockUser)) {
        e.preventDefault();
        const userId = unblockUser.dataset.userId;
        if (userId) adminUnblockSiteUser(userId);
        return;
    }

    const blockAuthor = target.closest('.btn-block-user[data-user-id]');
    if (blockAuthor && root.contains(blockAuthor)) {
        e.preventDefault();
        const userId = blockAuthor.dataset.userId;
        if (userId) adminBlockSiteUser(userId);
        return;
    }
});

// ==================== Exports ====================
window.adminDeletePost = adminDeletePost;
window.adminDeleteUser = adminDeleteUser;
window.adminDeleteComment = adminDeleteComment;
window.adminBlockSiteUser = adminBlockSiteUser;
window.adminUnblockSiteUser = adminUnblockSiteUser;