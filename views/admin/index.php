<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Админ-панель';

/**
 * @var array $stats
 * @var \app\models\User[] $recentUsers
 */

// Определяем URL для API
$deleteUserUrl = Url::to(['/api/admin/delete-user']);
$deletePostUrl = Url::to(['/api/admin/delete-post']);
$csrfToken = Yii::$app->request->csrfToken;

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
                            <div class="recent-user-item" data-user-id="<?= $user->id ?>">
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
                                            <?= Html::encode(mb_substr($post->content, 0, 80)) ?>        <?= mb_strlen($post->content) > 80 ? '...' : '' ?>
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

<script>
// ==================== Helper function for CSRF ====================
function postWithCsrf(url, data) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify(data)
    });
}

// ==================== Delete User ====================
async function adminDeleteUser(userId, username) {
    if (typeof window.showDeleteModal !== 'function') {
        if (!confirm(`Удалить пользователя "${username}"? Все его данные будут удалены безвозвратно!`)) return;
    } else {
        window.showDeleteModal(`Удалить пользователя "${username}"? Все его данные будут удалены безвозвратно!`, async () => {
            await performUserDeletion(userId, username);
        });
        return;
    }
    await performUserDeletion(userId, username);
}

async function performUserDeletion(userId, username) {
    try {
        const response = await postWithCsrf('<?= $deleteUserUrl ?>', { user_id: userId });
            const result = await response.json();
            if (result.success) {
                window.showNotification?.('Пользователь удалён', 'success');
                const userRow = document.querySelector(`.recent-user-item[data-user-id="${userId}"]`);
                if (userRow) {
                    userRow.remove();
                }
                updateStatsAfterDeletion();
            } else {
                window.showNotification?.(result.error || 'Ошибка удаления', 'error');
            }
        } catch (error) {
            console.error(error);
            window.showNotification?.('Ошибка удаления', 'error');
        }
    }

// ==================== Delete Post ====================
async function adminDeletePost(postId, postTitle) {
    if (typeof window.showDeleteModal !== 'function') {
        if (!confirm(`Удалить пост "${postTitle}"?`)) return;
    } else {
        window.showDeleteModal(`Удалить пост "${postTitle}"?`, async () => {
            await performPostDeletion(postId);
        });
        return;
    }
    await performPostDeletion(postId);
}

function updateStatsAfterDeletion() {
    // Обновляем счётчики в статистике
    const usersCountEl = document.querySelector('.stat-card:first-child .stat-value');
    const postsCountEl = document.querySelector('.stat-card:nth-child(2) .stat-value');
    
    if (usersCountEl) {
        const current = parseInt(usersCountEl.textContent.replace(/[^\d]/g, '')) || 0;
        usersCountEl.textContent = (current - 1).toLocaleString();
    }
    if (postsCountEl) {
        const current = parseInt(postsCountEl.textContent.replace(/[^\d]/g, '')) || 0;
        postsCountEl.textContent = (current - 1).toLocaleString();
    }
}

async function performPostDeletion(postId) {
    try {
        const response = await postWithCsrf('<?= $deletePostUrl ?>', { post_id: postId });
        const result = await response.json();
        if (result.success) {
            window.showNotification?.('Пост удалён', 'success');
            const postBtn = document.querySelector(`.action-btn.delete[data-post-id="${postId}"]`);
            if (postBtn) {
                const postRow = postBtn.closest('.recent-post-item');
                if (postRow) postRow.remove();
            }

            updateStatsAfterDeletion();
        } else {
            window.showNotification?.(result.error || 'Ошибка удаления', 'error');
        }
    } catch (error) {
        console.error(error);
        window.showNotification?.('Ошибка удаления', 'error');
    }
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
window.postWithCsrf = postWithCsrf;
</script>

<style>
/* ============================================
   Admin Panel Styles
   ============================================ */

.admin-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: var(--space-6);
}

.admin-header {
    margin-bottom: var(--space-6);
}

.admin-title {
    font-size: var(--text-3xl);
    font-weight: var(--font-bold);
    color: var(--text-primary);
    margin-bottom: var(--space-2);
}

.admin-subtitle {
    color: var(--text-secondary);
    font-size: var(--text-base);
}

/* Admin Navigation */
.admin-nav {
    display: flex;
    gap: var(--space-2);
    margin-bottom: var(--space-6);
    padding-bottom: var(--space-3);
    border-bottom: 1px solid var(--border-primary);
    flex-wrap: wrap;
}

.admin-nav-item {
    padding: var(--space-2) var(--space-4);
    color: var(--text-secondary);
    text-decoration: none;
    border-radius: var(--radius-lg);
    transition: all var(--transition-fast);
    font-weight: var(--font-medium);
}

.admin-nav-item:hover {
    background-color: var(--surface-100);
    color: var(--text-primary);
}

.admin-nav-item.active {
    background-color: var(--primary-50);
    color: var(--primary-600);
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--space-4);
    margin-bottom: var(--space-8);
}

.stat-card {
    background: linear-gradient(135deg, var(--surface-0) 0%, var(--surface-50) 100%);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-xl);
    padding: var(--space-4);
    display: flex;
    align-items: center;
    gap: var(--space-4);
    transition: all var(--transition-fast);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.stat-icon {
    font-size: 32px;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--primary-50) 0%, var(--primary-100) 100%);
    border-radius: var(--radius-lg);
}

.stat-info {
    flex: 1;
}

.stat-value {
    font-size: var(--text-2xl);
    font-weight: var(--font-bold);
    color: var(--text-primary);
    line-height: 1;
    margin-bottom: var(--space-1);
}

.stat-label {
    font-size: var(--text-sm);
    color: var(--text-tertiary);
}

/* Admin Section */
.admin-section {
    margin-bottom: var(--space-8);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: var(--space-4);
    padding-bottom: var(--space-2);
    border-bottom: 1px solid var(--border-primary);
}

.section-title {
    font-size: var(--text-xl);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
    margin: 0;
}

.section-link {
    font-size: var(--text-sm);
    color: var(--primary-600);
    text-decoration: none;
}

.section-link:hover {
    text-decoration: underline;
}

/* Recent Users */
.recent-users {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.recent-user-item {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3);
    background: var(--surface-0);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-lg);
    transition: all var(--transition-fast);
}

.recent-user-item:hover {
    background-color: var(--surface-50);
    transform: translateX(4px);
}

.user-avatar-small {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--border-primary);
}

.user-details {
    flex: 1;
}

.user-details .username {
    font-weight: var(--font-semibold);
    font-size: var(--text-sm);
    color: var(--text-primary);
}

.user-details .username a {
    color: inherit;
    text-decoration: none;
}

.user-details .username a:hover {
    color: var(--primary-600);
}

.user-email {
    font-size: var(--text-xs);
    color: var(--text-tertiary);
}

.user-time {
    font-size: var(--text-xs);
    color: var(--text-tertiary);
}

.user-actions {
    display: flex;
    gap: var(--space-2);
}

.action-btn {
    width: 32px;
    height: 32px;
    background: var(--surface-100);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-md);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition-fast);
}

.action-btn:hover {
    transform: scale(1.05);
}

.action-btn.delete:hover {
    background: var(--error-light);
    border-color: var(--error);
    color: var(--error);
}

.action-btn.view:hover {
    background: var(--primary-50);
    border-color: var(--primary-500);
    color: var(--primary-600);
}

/* Recent Posts */
.recent-posts {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.recent-post-item {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3);
    background: var(--surface-0);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-lg);
    transition: all var(--transition-fast);
}

.recent-post-item:hover {
    background-color: var(--surface-50);
    transform: translateX(4px);
}

.user-avatar-tiny {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--border-primary);
}

.post-info {
    flex: 1;
    min-width: 0;
}

.post-title {
    font-weight: var(--font-medium);
    font-size: var(--text-sm);
    color: var(--text-primary);
    margin-bottom: var(--space-1);
}

.post-title a {
    color: inherit;
    text-decoration: none;
}

.post-title a:hover {
    color: var(--primary-600);
}

.post-meta {
    display: flex;
    gap: var(--space-3);
    font-size: var(--text-xs);
    color: var(--text-tertiary);
    flex-wrap: wrap;
}

.post-actions {
    display: flex;
    gap: var(--space-2);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: var(--space-8);
    color: var(--text-tertiary);
    background: var(--surface-0);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-xl);
}

/* Responsive */
@media (max-width: 768px) {
    .admin-container {
        padding: var(--space-4);
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .recent-user-item,
    .recent-post-item {
        flex-wrap: wrap;
    }
    
    .user-actions,
    .post-actions {
        width: 100%;
        justify-content: flex-end;
    }
}

@media (max-width: 480px) {
    .admin-container {
        padding: var(--space-3);
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .post-meta {
        flex-direction: column;
        gap: var(--space-1);
    }
}
</style>