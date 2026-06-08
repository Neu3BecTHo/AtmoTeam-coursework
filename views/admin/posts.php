<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Посты - Админ-панель';

$todayCount = count(array_filter($posts, fn($p) => $p->created_at > time() - 86400));
$pollCount = count(array_filter($posts, fn($p) => $p->poll));
$imageCount = count(array_filter($posts, fn($p) => $p->image));
$totalLikes = array_sum(array_map(fn($p) => $p->likes_count, $posts));

?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">📝 Управление постами</h1>
        <p class="admin-subtitle">Всего постов: <?= number_format(count($posts)) ?></p>
        <?= Html::a('← Назад', ['/admin/index'], ['class' => 'btn-back']) ?>
    </div>

    <div class="admin-content">
        <!-- Фильтры -->
        <div class="admin-section">
            <div class="section-header">
                <h3 class="section-title">🔍 Фильтры</h3>
            </div>
            <div class="filters-container">
                <div class="filter-group">
                    <label class="filter-label">📅 Период:</label>
                    <select class="filter-select" id="periodFilter">
                        <option value="all">Все время</option>
                        <option value="today">Сегодня</option>
                        <option value="week">За неделю</option>
                        <option value="month">За месяц</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">📊 Сортировка:</label>
                    <select class="filter-select" id="sortFilter">
                        <option value="newest">Новые</option>
                        <option value="popular">Популярные</option>
                        <option value="comments">Комментарии</option>
                        <option value="likes">Лайки</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">🏷️ Тип:</label>
                    <select class="filter-select" id="typeFilter">
                        <option value="all">Все</option>
                        <option value="text">Текст</option>
                        <option value="image">С изображением</option>
                        <option value="poll">С опросом</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Таблица постов -->
        <div class="admin-section">
            <div class="section-header">
                <h3 class="section-title">📋 Список постов</h3>
                <div class="section-actions">
                    <button class="btn-refresh" onclick="location.reload()">🔄 Обновить</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="admin-table" id="posts-table">
                    <thead>
                        <tr>
                            <th>👤 Автор</th>
                            <th>💬 Содержание</th>
                            <th>📅 Дата</th>
                            <th>📊 Статистика</th>
                            <th>🏷️ Тип</th>
                            <th>⚙️ Действия</th>
                        </tr>
                    </thead>
                    <tbody id="posts-tbody">
                        <?php foreach ($posts as $post): ?>
                            <tr class="post-row" 
                                data-post-id="<?= $post->id ?>"
                                data-created-at="<?= $post->created_at ?>"
                                data-likes="<?= $post->likes_count ?>"
                                data-comments="<?= $post->comments_count ?>"
                                data-type="<?= $post->image ? ($post->poll ? 'combined' : 'image') : ($post->poll ? 'poll' : 'text') ?>">
                                
                                <!-- Автор -->
                                <td>
                                    <div class="author-cell">
                                        <a href="<?= Url::to(['/profile/view', 'id' => $post->user_id]) ?>" target="_blank">
                                            <img class="author-avatar-small"
                                                 src="<?= $post->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $post->user_id ?>"
                                                 alt="<?= Html::encode($post->user->username) ?>">
                                        </a>
                                        <div class="author-info">
                                            <div class="author-name"><?= Html::encode($post->user->username) ?></div>
                                            <div class="author-id">ID: <?= $post->user_id ?></div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Содержание -->
                                <td>
                                    <div class="content-cell">
                                        <div class="post-preview" title="<?= Html::encode($post->content) ?>">
                                            <?= Html::encode(mb_substr($post->content, 0, 100)) ?>
                                            <?= mb_strlen($post->content) > 100 ? '...' : '' ?>
                                        </div>
                                        <?php if ($post->image): ?>
                                            <div class="post-image-preview">
                                                <img src="<?= $post->image ?>" alt="Изображение поста">
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($post->poll): ?>
                                            <div class="post-poll-preview">
                                                📊 <?= Html::encode($post->poll->question) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <!-- Дата -->
                                <td>
                                    <div class="date-cell">
                                        <div class="post-date"><?= date('d.m.Y H:i', $post->created_at) ?></div>
                                        <?php if ($post->updated_at > $post->created_at): ?>
                                            <div class="post-updated">✏️ <?= date('d.m.Y H:i', $post->updated_at) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <!-- Статистика -->
                                <td>
                                    <div class="stats-cell">
                                        <div class="stat-row">❤️ <span class="likes-count"><?= $post->likes_count ?></span></div>
                                        <div class="stat-row">💬 <span class="comments-count"><?= $post->comments_count ?></span></div>
                                        <div class="stat-row">🔄 <span class="reposts-count"><?= $post->getRepostsCount() ?></span></div>
                                    </div>
                                </td>
                                
                                <!-- Тип -->
                                <td>
                                    <div class="type-cell">
                                        <?php if ($post->image && $post->poll): ?>
                                            <span class="type-badge combined">🖼️📊</span>
                                        <?php elseif ($post->image): ?>
                                            <span class="type-badge image">🖼️ Изображение</span>
                                        <?php elseif ($post->poll): ?>
                                            <span class="type-badge poll">📊 Опрос</span>
                                        <?php else: ?>
                                            <span class="type-badge text">📝 Текст</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                </td>
                                
                                <!-- Действия -->
                                <td>
                                    <div class="actions-cell">
                                        <button class="action-btn view" onclick="viewPost(<?= $post->id ?>)" title="Посмотреть пост">👁️</button>
                                        <button class="action-btn edit" onclick="editPost(<?= $post->id ?>)" title="Редактировать">✏️</button>
                                        <button class="action-btn delete" data-post-id="<?= $post->id ?>" data-post-title="<?= Html::encode(mb_substr($post->content, 0, 50)) ?>" title="Удалить пост">🗑️</button>
                                    </div>
                                </div>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Статистика -->
        <div class="stats-section">
            <h3 class="section-title">📊 Статистика постов</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-info">
                        <div class="stat-value" id="stats-today"><?= $todayCount ?></div>
                        <div class="stat-label">За сегодня</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <div class="stat-value" id="stats-poll"><?= $pollCount ?></div>
                        <div class="stat-label">С опросами</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🖼️</div>
                    <div class="stat-info">
                        <div class="stat-value" id="stats-image"><?= $imageCount ?></div>
                        <div class="stat-label">С изображениями</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🔥</div>
                    <div class="stat-info">
                        <div class="stat-value" id="stats-likes"><?= number_format($totalLikes) ?></div>
                        <div class="stat-label">Всего лайков</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$deletePostUrl = Url::to(['/api/admin/delete-post']);

$script = <<<JS
// ==================== Filter Functions ====================
function filterPosts() {
    const period = document.getElementById('periodFilter')?.value || 'all';
    const sort = document.getElementById('sortFilter')?.value || 'newest';
    const type = document.getElementById('typeFilter')?.value || 'all';
    const now = Math.floor(Date.now() / 1000);
    const dayAgo = now - 86400;
    const weekAgo = now - 604800;
    const monthAgo = now - 2592000;
    
    let posts = Array.from(document.querySelectorAll('.post-row'));
    let visibleCount = 0;
    let pollCount = 0;
    let imageCount = 0;
    let totalLikes = 0;
    
    // Filter by period
    posts.forEach(row => {
        const createdAt = parseInt(row.dataset.createdAt);
        let show = true;
        
        if (period === 'today' && createdAt < dayAgo) show = false;
        else if (period === 'week' && createdAt < weekAgo) show = false;
        else if (period === 'month' && createdAt < monthAgo) show = false;
        
        // Filter by type
        if (show && type !== 'all') {
            const postType = row.dataset.type;
            if (type === 'image' && !['image', 'combined'].includes(postType)) show = false;
            else if (type === 'poll' && !['poll', 'combined'].includes(postType)) show = false;
            else if (type === 'text' && postType !== 'text') show = false;
        }
        
        row.style.display = show ? '' : 'none';
        
        if (show) {
            visibleCount++;
            const postType = row.dataset.type;
            if (postType === 'poll' || postType === 'combined') pollCount++;
            if (postType === 'image' || postType === 'combined') imageCount++;
            totalLikes += parseInt(row.dataset.likes) || 0;
        }
    });
    
    // Sort
    const tbody = document.getElementById('posts-tbody');
    const visibleRows = Array.from(tbody.children).filter(row => row.style.display !== 'none');
    
    visibleRows.sort((a, b) => {
        if (sort === 'newest') return parseInt(b.dataset.createdAt) - parseInt(a.dataset.createdAt);
        if (sort === 'popular') return (parseInt(b.dataset.likes) + parseInt(b.dataset.comments)) - (parseInt(a.dataset.likes) + parseInt(a.dataset.comments));
        if (sort === 'comments') return parseInt(b.dataset.comments) - parseInt(a.dataset.comments);
        if (sort === 'likes') return parseInt(b.dataset.likes) - parseInt(a.dataset.likes);
        return 0;
    });
    
    visibleRows.forEach(row => tbody.appendChild(row));
    
    // Update stats
    document.getElementById('stats-today').textContent = visibleCount;
    document.getElementById('stats-poll').textContent = pollCount;
    document.getElementById('stats-image').textContent = imageCount;
    document.getElementById('stats-likes').textContent = totalLikes.toLocaleString();
}

// ==================== Actions ====================
async function deletePost(postId, postTitle) {
    if (typeof showDeleteModal !== 'function') return;
    
    showDeleteModal(`Удалить пост "${postTitle}"?`, async () => {
        try {
            const response = await postWithCsrf('$deletePostUrl', { post_id: postId });
            const result = await response.json();
            if (result.success) {
                showNotification('Пост удалён', 'success');
                const row = document.querySelector(`.post-row[data-post-id="${postId}"]`);
                if (row) row.remove();
                filterPosts();
            } else {
                showNotification(result.error || 'Ошибка удаления', 'error');
            }
        } catch (error) {
            showNotification('Ошибка удаления', 'error');
        }
    });
}

function editPost(postId) {
    showNotification('Функция редактирования постов будет добавлена позже', 'info');
}

function viewPost(postId) {
    window.open('/post/view?id=' + postId, '_blank');
}

// ==================== Event Listeners ====================
document.addEventListener('DOMContentLoaded', function() {
    const periodFilter = document.getElementById('periodFilter');
    const sortFilter = document.getElementById('sortFilter');
    const typeFilter = document.getElementById('typeFilter');
    
    let filterTimeout;
    const applyFilters = () => {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(filterPosts, 100);
    };
    
    periodFilter?.addEventListener('change', applyFilters);
    sortFilter?.addEventListener('change', applyFilters);
    typeFilter?.addEventListener('change', applyFilters);
    
    filterPosts();
});

// ==================== Event Delegation for Delete ====================
document.addEventListener('click', function(e) {
    const deleteBtn = e.target.closest('.action-btn.delete[data-post-id]');
    if (deleteBtn) {
        const postId = deleteBtn.dataset.postId;
        const postTitle = deleteBtn.dataset.postTitle;
        if (postId) deletePost(postId, postTitle || postId);
    }
});

// ==================== Exports ====================
window.deletePost = deletePost;
window.editPost = editPost;
window.viewPost = viewPost;
window.filterPosts = filterPosts;
JS;
$this->registerJs($script);
?>