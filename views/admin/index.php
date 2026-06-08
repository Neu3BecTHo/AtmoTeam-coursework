<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Админ-панель';

?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">🛠️ Админ-панель</h1>
        <p class="admin-subtitle">Управление сайтом</p>
    </div>

    <!-- Навигация -->
    <nav class="admin-nav">
        <a href="<?= Url::to(['/admin']) ?>" class="admin-nav-item active">📊 Обзор</a>
        <a href="<?= Url::to(['/admin/users']) ?>" class="admin-nav-item">👥 Пользователи</a>
        <a href="<?= Url::to(['/admin/posts']) ?>" class="admin-nav-item">📝 Посты</a>
        <a href="<?= Url::to(['/admin/comments']) ?>" class="admin-nav-item">💬 Комментарии</a>
    </nav>

    <!-- Статистика -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format($stats['users']) ?></div>
                <div class="stat-label">Пользователи</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📝</div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format($stats['posts']) ?></div>
                <div class="stat-label">Посты</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💬</div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format($stats['comments']) ?></div>
                <div class="stat-label">Комментарии</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🔔</div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format($stats['notifications']) ?></div>
                <div class="stat-label">Уведомления</div>
            </div>
        </div>
    </div>

    <!-- Последние пользователи -->
    <div class="admin-section">
        <div class="section-header">
            <h2 class="section-title">👥 Последние пользователи</h2>
            <a href="<?= Url::to(['/admin/users']) ?>" class="section-link">Все пользователи →</a>
        </div>
        <div class="recent-users">
            <?php if (empty($recentUsers)): ?>
                <div class="empty-state">Нет пользователей</div>
            <?php else: ?>
                <?php foreach ($recentUsers as $user): ?>
                    <div class="recent-user-item">
                        <img class="user-avatar-small" 
                             src="<?= $user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $user->id ?>" 
                             alt="<?= Html::encode($user->username) ?>">
                        <div class="user-details">
                            <div class="username">
                                <a href="<?= Url::to(['/admin/users/view', 'id' => $user->id]) ?>">
                                    <?= Html::encode($user->username) ?>
                                </a>
                            </div>
                            <div class="user-email"><?= Html::encode($user->email) ?></div>
                        </div>
                        <div class="user-time">
                            <?= date('d.m.Y H:i', $user->created_at) ?>
                        </div>
                        <div class="user-actions">
                            <button type="button" class="action-btn delete" 
                                    data-user-id="<?= $user->id ?>"
                                    data-username="<?= Html::encode($user->username) ?>"
                                    title="Удалить пользователя">
                                🗑️
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Последние посты -->
    <div class="admin-section">
        <div class="section-header">
            <h2 class="section-title">📝 Последние посты</h2>
            <a href="<?= Url::to(['/admin/posts']) ?>" class="section-link">Все посты →</a>
        </div>
        <div class="recent-posts">
            <?php if (empty($recentPosts)): ?>
                <div class="empty-state">Нет постов</div>
            <?php else: ?>
                <?php foreach ($recentPosts as $post): ?>
                    <div class="recent-post-item">
                        <div class="post-avatar">
                            <img class="user-avatar-tiny" 
                                 src="<?= $post->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $post->user_id ?>" 
                                 alt="<?= Html::encode($post->user->username) ?>">
                        </div>
                        <div class="post-info">
                            <div class="post-title">
                                <a href="<?= Url::to(['/post/view', 'id' => $post->id]) ?>" target="_blank">
                                    <?= Html::encode(mb_substr($post->content, 0, 80)) ?><?= mb_strlen($post->content) > 80 ? '...' : '' ?>
                                </a>
                            </div>
                            <div class="post-meta">
                                <span>👤 <?= Html::encode($post->user->username ?? 'Удалён') ?></span>
                                <span>💬 <?= $post->comments_count ?? 0 ?></span>
                                <span>❤️ <?= $post->likes_count ?? 0 ?></span>
                                <span>🕐 <?= date('d.m.Y H:i', $post->created_at) ?></span>
                            </div>
                        </div>
                        <div class="post-actions">
                            <a href="<?= Url::to(['/post/view', 'id' => $post->id]) ?>" 
                               class="action-btn view"
                               target="_blank"
                               title="Посмотреть пост">
                                👁️
                            </a>
                            <button type="button" class="action-btn delete" 
                                    data-post-id="<?= $post->id ?>"
                                    data-post-title="<?= Html::encode(mb_substr($post->content, 0, 50)) ?>"
                                    title="Удалить пост">
                                🗑️
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$deleteUserUrl = Url::to(['/api/admin/delete-user']);
$deletePostUrl = Url::to(['/api/admin/delete-post']);

$script = <<<JS
// ==================== Delete User ====================
async function adminDeleteUser(userId, username) {
    if (typeof showDeleteModal !== 'function') return;
    
    showDeleteModal(`Удалить пользователя "${username}"? Все его данные будут удалены безвозвратно!`, async () => {
        try {
            const response = await postWithCsrf('$deleteUserUrl', { user_id: userId });
            const result = await response.json();
            if (result.success) {
                showNotification(`Пользователь "${username}" удалён`, 'success');
                const row = document.querySelector(`[data-user-id="${userId}"]`)?.closest('.recent-user-item');
                if (row) row.remove();
            } else {
                showNotification(result.error || 'Ошибка удаления', 'error');
            }
        } catch (error) {
            showNotification('Ошибка удаления', 'error');
        }
    });
}

// ==================== Delete Post ====================
async function adminDeletePost(postId, postTitle) {
    if (typeof showDeleteModal !== 'function') return;
    
    showDeleteModal(`Удалить пост "${postTitle}"?`, async () => {
        try {
            const response = await postWithCsrf('$deletePostUrl', { post_id: postId });
            const result = await response.json();
            if (result.success) {
                showNotification('Пост удалён', 'success');
                const row = document.querySelector(`[data-post-id="${postId}"]`)?.closest('.recent-post-item');
                if (row) row.remove();
            } else {
                showNotification(result.error || 'Ошибка удаления', 'error');
            }
        } catch (error) {
            showNotification('Ошибка удаления', 'error');
        }
    });
}

// ==================== Event Delegation ====================
document.addEventListener('click', function(e) {
    const deleteUserBtn = e.target.closest('[data-user-id]');
    if (deleteUserBtn && deleteUserBtn.classList.contains('delete')) {
        const userId = deleteUserBtn.dataset.userId;
        const username = deleteUserBtn.dataset.username;
        if (userId && username) adminDeleteUser(userId, username);
        return;
    }
    
    const deletePostBtn = e.target.closest('[data-post-id]');
    if (deletePostBtn && deletePostBtn.classList.contains('delete')) {
        const postId = deletePostBtn.dataset.postId;
        const postTitle = deletePostBtn.dataset.postTitle;
        if (postId) adminDeletePost(postId, postTitle || postId);
        return;
    }
});

// ==================== Exports ====================
window.adminDeleteUser = adminDeleteUser;
window.adminDeletePost = adminDeletePost;
JS;
$this->registerJs($script);
?>