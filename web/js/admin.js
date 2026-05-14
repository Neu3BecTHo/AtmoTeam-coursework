

function adminRemovePostRow(postId) {
    const btn = document.querySelector(`.btn-delete-post[data-post-id="${postId}"]`);
    const row = btn
        ? btn.closest('.recent-post-item, .post-row')
        : document.querySelector(`tr.post-row[data-post-id="${postId}"]`);
    if (row) {
        row.remove();
    }
}

function adminRemoveUserRow(userId) {
    const btn = document.querySelector(`.btn-delete-user[data-user-id="${userId}"], .btn-delete[data-user-id="${userId}"]`);
    const row = btn ? btn.closest('.user-row') : document.querySelector(`tr.user-row[data-user-id="${userId}"]`);
    if (row) {
        row.remove();
    }
}

function adminRemoveCommentRow(commentId) {
    const btn = document.querySelector(`.btn-delete-comment[data-comment-id="${commentId}"]`);
    const row = btn ? btn.closest('.comment-row') : document.querySelector(`tr.comment-row[data-comment-id="${commentId}"]`);
    if (row) {
        row.remove();
    }
}

async function adminDeletePost(postId) {
    if (typeof showDeleteModal !== 'function' || typeof postWithCsrf !== 'function') {
        if (typeof showNotification === 'function') {
            showNotification('Не загружены скрипты интерфейса (common.js)', 'error');
        }
        return;
    }
    showDeleteModal('Удалить этот пост?', async () => {
        try {
            const response = await postWithCsrf('/api/admin/delete-post', { post_id: postId });
            const result = await response.json();

            if (result.success) {
                showNotification('Пост удален', 'success');
                adminRemovePostRow(postId);
            } else {
                showNotification(result.error || 'Ошибка удаления', 'error');
            }
        } catch (error) {
            
            showNotification('Ошибка удаления', 'error');
        }
    });
}

async function adminDeleteUser(userId) {
    if (typeof showDeleteModal !== 'function' || typeof postWithCsrf !== 'function') {
        if (typeof showNotification === 'function') {
            showNotification('Не загружены скрипты интерфейса (common.js)', 'error');
        }
        return;
    }
    showDeleteModal('Удалить этого пользователя? Все его данные будут удалены!', async () => {
        try {
            const response = await postWithCsrf('/api/admin/delete-user', { user_id: userId });
            const result = await response.json();

            if (result.success) {
                showNotification('Пользователь удален', 'success');
                adminRemoveUserRow(userId);
            } else {
                showNotification(result.error || 'Ошибка удаления', 'error');
            }
        } catch (error) {
            
            showNotification('Ошибка удаления', 'error');
        }
    });
}

async function adminDeleteComment(commentId) {
    if (typeof showDeleteModal !== 'function' || typeof postWithCsrf !== 'function') {
        if (typeof showNotification === 'function') {
            showNotification('Не загружены скрипты интерфейса (common.js)', 'error');
        }
        return;
    }
    showDeleteModal('Удалить этот комментарий?', async () => {
        try {
            const response = await postWithCsrf('/api/admin/delete-comment', { comment_id: commentId });
            const result = await response.json();

            if (result.success) {
                showNotification('Комментарий удален', 'success');
                adminRemoveCommentRow(commentId);
            } else {
                showNotification(result.error || 'Ошибка удаления', 'error');
            }
        } catch (error) {
            
            showNotification('Ошибка удаления', 'error');
        }
    });
}

async function adminBlockSiteUser(userId) {
    if (typeof showDeleteModal !== 'function' || typeof postWithCsrf !== 'function') {
        return;
    }
    showDeleteModal('Заблокировать этого пользователя на сайте?', async () => {
        try {
            const response = await postWithCsrf('/api/admin/block-user', { user_id: userId });
            const result = await response.json();
            if (result.success) {
                showNotification(result.message || 'Пользователь заблокирован', 'success');
                location.reload();
            } else {
                showNotification(result.error || 'Ошибка блокировки', 'error');
            }
        } catch (e) {
            
            showNotification('Ошибка блокировки', 'error');
        }
    });
}

async function adminUnblockSiteUser(userId) {
    if (typeof showDeleteModal !== 'function' || typeof postWithCsrf !== 'function') {
        return;
    }
    showDeleteModal('Разблокировать этого пользователя?', async () => {
        try {
            const response = await postWithCsrf('/api/admin/unblock-user', { user_id: userId });
            const result = await response.json();
            if (result.success) {
                showNotification(result.message || 'Пользователь разблокирован', 'success');
                location.reload();
            } else {
                showNotification(result.error || 'Ошибка разблокировки', 'error');
            }
        } catch (e) {
            
            showNotification('Ошибка разблокировки', 'error');
        }
    });
}

window.adminDeletePost = adminDeletePost;
window.adminDeleteUser = adminDeleteUser;
window.adminDeleteComment = adminDeleteComment;
window.adminBlockSiteUser = adminBlockSiteUser;
window.adminUnblockSiteUser = adminUnblockSiteUser;

setInterval(() => {
    const first = document.querySelector('.stat-card:nth-child(1) .stat-value');
    if (!first) {
        return;
    }
    updateStats();
}, 60000);

async function updateStats() {
    try {
        const response = await fetch('/api/admin/stats');
        const data = await response.json();
        const stats = data.stats || data;
        if (!stats || typeof stats.users === 'undefined') {
            return;
        }
        const selectors = [
            '.stat-card:nth-child(1) .stat-value',
            '.stat-card:nth-child(2) .stat-value',
            '.stat-card:nth-child(3) .stat-value',
            '.stat-card:nth-child(4) .stat-value',
        ];
        const values = [stats.users, stats.posts, stats.comments, stats.notifications];
        selectors.forEach((sel, i) => {
            const el = document.querySelector(sel);
            if (el && values[i] !== undefined) {
                el.textContent = values[i];
            }
        });
    } catch (error) {
        
    }
}

document.addEventListener('click', (e) => {
    const root = e.target.closest('.admin-container');
    if (!root) {
        return;
    }

    const delPost = e.target.closest('.btn-delete-post');
    if (delPost && root.contains(delPost)) {
        e.preventDefault();
        const postId = delPost.getAttribute('data-post-id');
        if (postId) {
            adminDeletePost(postId);
        }
        return;
    }

    const delUser = e.target.closest('.btn-delete-user, .btn-delete[data-user-id]');
    if (delUser && root.contains(delUser) && delUser.matches('.btn-delete-user, .btn-delete')) {
        e.preventDefault();
        const userId = delUser.getAttribute('data-user-id');
        if (userId) {
            adminDeleteUser(userId);
        }
        return;
    }

    const delComment = e.target.closest('.btn-delete-comment');
    if (delComment && root.contains(delComment)) {
        e.preventDefault();
        const commentId = delComment.getAttribute('data-comment-id');
        if (commentId) {
            adminDeleteComment(commentId);
        }
        return;
    }

    const blockUserBtn = e.target.closest('.btn-block[data-user-id]');
    if (blockUserBtn && root.contains(blockUserBtn)) {
        e.preventDefault();
        const userId = blockUserBtn.getAttribute('data-user-id');
        if (userId) {
            adminBlockSiteUser(userId);
        }
        return;
    }

    const unblockUserBtn = e.target.closest('.btn-unblock[data-user-id]');
    if (unblockUserBtn && root.contains(unblockUserBtn)) {
        e.preventDefault();
        const userId = unblockUserBtn.getAttribute('data-user-id');
        if (userId) {
            adminUnblockSiteUser(userId);
        }
        return;
    }

    const delUserAlt = e.target.closest('.btn-delete[data-user-id]');
    if (delUserAlt && root.contains(delUserAlt)) {
        e.preventDefault();
        const userId = delUserAlt.getAttribute('data-user-id');
        if (userId) {
            adminDeleteUser(userId);
        }
        return;
    }

    const blockAuthorBtn = e.target.closest('.btn-block-user[data-user-id]');
    if (blockAuthorBtn && root.contains(blockAuthorBtn)) {
        e.preventDefault();
        const userId = blockAuthorBtn.getAttribute('data-user-id');
        if (userId) {
            adminBlockSiteUser(userId);
        }
        return;
    }
});
