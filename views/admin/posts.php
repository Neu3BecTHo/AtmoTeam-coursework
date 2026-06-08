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
                            <th style="width: 180px;">👤 Автор</th>
                            <th style="width: 35%;">💬 Содержание</th>
                            <th style="width: 130px;">📅 Дата</th>
                            <th style="width: 100px;">📊 Статистика</th>
                            <th style="width: 100px;">🏷️ Тип</th>
                            <th style="width: 110px;">⚙️ Действия</th>
                        </tr>
                    </thead>
                    <tbody id="posts-tbody">
                        <?php foreach ($posts as $post): ?>
                            <tr class="post-row" data-post-id="<?= $post->id ?>" data-created-at="<?= $post->created_at ?>"
                                data-likes="<?= $post->likes_count ?>" data-comments="<?= $post->comments_count ?>"
                                data-type="<?= $post->image ? ($post->poll ? 'combined' : 'image') : ($post->poll ? 'poll' : 'text') ?>"
                                data-post-title="<?= Html::encode(mb_substr($post->content, 0, 50)) ?>">

                                <!-- Автор -->
                                <td style="width: 180px;">
                                    <div class="author-info">
                                        <a href="<?= Url::to(['/profile/view', 'id' => $post->user_id]) ?>" target="_blank">
                                            <img class="author-avatar"
                                                src="<?= $post->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $post->user_id ?>"
                                                alt="<?= Html::encode($post->user->username) ?>">
                                        </a>
                                        <div class="author-details">
                                            <div class="author-name"><?= Html::encode($post->user->username) ?></div>
                                            <div class="author-id">ID: <?= $post->user_id ?></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Содержание -->
                                <td style="width: 35%;">
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
                                </td>

                                <!-- Дата -->
                                <td style="width: 130px;">
                                    <div class="post-date"><?= date('d.m.Y H:i', $post->created_at) ?></div>
                                    <?php if ($post->updated_at > $post->created_at): ?>
                                        <div class="post-updated">✏️ <?= date('d.m.Y H:i', $post->updated_at) ?></div>
                                    <?php endif; ?>
                                </td>

                                <!-- Статистика -->
                                <td style="width: 100px;">
                                    <div class="stat-item">❤️ <?= $post->likes_count ?></div>
                                    <div class="stat-item">💬 <?= $post->comments_count ?></div>
                                    <div class="stat-item">🔄 <?= $post->getRepostsCount() ?></div>
                                </td>

                                <!-- Тип -->
                                <td style="width: 100px;">
                                    <?php if ($post->image && $post->poll): ?>
                                        <span class="type-badge combined">🖼️📊</span>
                                    <?php elseif ($post->image): ?>
                                        <span class="type-badge image">🖼️ Изображение</span>
                                    <?php elseif ($post->poll): ?>
                                        <span class="type-badge poll">📊 Опрос</span>
                                    <?php else: ?>
                                        <span class="type-badge text">📝 Текст</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Действия -->
                                <td style="width: 110px;">
                                    <div class="actions-cell">
                                        <button type="button" class="action-btn view" onclick="viewPost(<?= $post->id ?>)"
                                            title="Посмотреть пост">👁️</button>
                                        <button type="button" class="action-btn edit" onclick="editPost(<?= $post->id ?>)"
                                            title="Редактировать">✏️</button>
                                        <button type="button" class="action-btn delete" data-post-id="<?= $post->id ?>"
                                            data-post-title="<?= Html::encode(mb_substr($post->content, 0, 50)) ?>"
                                            title="Удалить пост">🗑️</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$deletePostUrl = Url::to(['/api/admin/delete-post']);
$csrfToken = Yii::$app->request->csrfToken;

$script = <<<JS
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
    
    posts.forEach(row => {
        const createdAt = parseInt(row.dataset.createdAt);
        let show = true;
        
        if (period === 'today' && createdAt < dayAgo) show = false;
        else if (period === 'week' && createdAt < weekAgo) show = false;
        else if (period === 'month' && createdAt < monthAgo) show = false;
        
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
    
    document.getElementById('stats-today').textContent = visibleCount;
    document.getElementById('stats-poll').textContent = pollCount;
    document.getElementById('stats-image').textContent = imageCount;
    document.getElementById('stats-likes').textContent = totalLikes.toLocaleString();
}

async function deletePost(postId, postTitle) {
    if (!confirm('Удалить пост "' + postTitle + '"?')) return;
    try {
        const response = await postWithCsrf('$deletePostUrl', { post_id: postId });
        const result = await response.json();
        if (result.success) {
            alert('Пост удалён');
            const row = document.querySelector('.post-row[data-post-id="' + postId + '"]');
            if (row) row.remove();
            filterPosts();
        } else {
            alert(result.error || 'Ошибка удаления');
        }
    } catch (error) {
        alert('Ошибка удаления');
    }
}

function editPost(postId) {
    alert('Функция редактирования постов будет добавлена позже');
}

function viewPost(postId) {
    window.open('/post/view?id=' + postId, '_blank');
}

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

document.addEventListener('click', function(e) {
    const deleteBtn = e.target.closest('.action-btn.delete[data-post-id]');
    if (deleteBtn) {
        const postId = deleteBtn.dataset.postId;
        const postTitle = deleteBtn.dataset.postTitle;
        if (postId) deletePost(postId, postTitle || postId);
    }
});

window.deletePost = deletePost;
window.editPost = editPost;
window.viewPost = viewPost;
window.filterPosts = filterPosts;
window.postWithCsrf = postWithCsrf;
JS;
$this->registerJs($script);
?>

<style>
    /* ============================================
   Admin Posts Page Styles
   ============================================ */

    .admin-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    .admin-header {
        margin-bottom: 30px;
        padding: 25px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border-radius: 16px;
        color: white;
    }

    body.dark-theme .admin-header {
        background: linear-gradient(135deg, #1d4ed8 0%, #1e3a8a 100%);
    }

    .admin-title {
        font-size: 28px;
        font-weight: bold;
        margin: 0 0 5px 0;
    }

    .admin-subtitle {
        font-size: 14px;
        opacity: 0.9;
        margin: 0;
    }

    .btn-back {
        display: inline-block;
        padding: 8px 16px;
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border-radius: 8px;
        text-decoration: none;
    }

    .btn-back:hover {
        background: rgba(255, 255, 255, 0.3);
        color: white;
    }

    /* Admin Navigation */
    .admin-nav {
        display: flex;
        gap: 10px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }

    .admin-nav-item {
        padding: 10px 20px;
        background: #f1f5f9;
        color: #475569;
        text-decoration: none;
        border-radius: 10px;
        transition: all 0.2s;
    }

    body.dark-theme .admin-nav-item {
        background: #1e293b;
        color: #94a3b8;
    }

    .admin-nav-item:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    body.dark-theme .admin-nav-item:hover {
        background: #334155;
        color: #f1f5f9;
    }

    .admin-nav-item.active {
        background: #3b82f6;
        color: white;
    }

    /* Filters */
    .admin-section {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        margin-bottom: 30px;
        overflow: hidden;
    }

    body.dark-theme .admin-section {
        background: #1e293b;
        border-color: #334155;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }

    body.dark-theme .section-header {
        background: #0f172a;
        border-bottom-color: #334155;
    }

    .section-title {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
        color: #1e293b;
    }

    body.dark-theme .section-title {
        color: #f1f5f9;
    }

    .filters-container {
        display: flex;
        gap: 20px;
        padding: 20px;
        flex-wrap: wrap;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .filter-label {
        font-size: 12px;
        font-weight: 500;
        color: #64748b;
    }

    body.dark-theme .filter-label {
        color: #94a3b8;
    }

    .filter-select,
    .filter-input {
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: white;
        color: #1e293b;
        min-width: 150px;
    }

    body.dark-theme .filter-select,
    body.dark-theme .filter-input {
        background: #0f172a;
        border-color: #475569;
        color: #f1f5f9;
    }

    .filter-select:focus,
    .filter-input:focus {
        outline: none;
        border-color: #3b82f6;
    }

    .btn-refresh {
        padding: 8px 16px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        cursor: pointer;
    }

    body.dark-theme .btn-refresh {
        background: #1e293b;
        border-color: #475569;
        color: #f1f5f9;
    }

    .btn-refresh:hover {
        background: #e2e8f0;
    }

    /* Table Styles */
    .table-responsive {
        overflow-x: auto;
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .admin-table th,
    .admin-table td {
        padding: 12px 15px;
        vertical-align: middle;
        word-break: break-word;
    }

    .admin-table th {
        background: #f8fafc;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        color: #475569;
        border-bottom: 2px solid #e2e8f0;
    }

    body.dark-theme .admin-table th {
        background: #0f172a;
        color: #94a3b8;
        border-bottom-color: #334155;
    }

    .admin-table td {
        border-bottom: 1px solid #e2e8f0;
    }

    body.dark-theme .admin-table td {
        border-bottom-color: #334155;
    }

    .admin-table tr:hover {
        background: #f8fafc;
    }

    body.dark-theme .admin-table tr:hover {
        background: #0f172a;
    }

    /* Author Cell */
    .author-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .author-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
    }

    .author-details {
        min-width: 0;
        flex: 1;
    }

    .author-details .author-name {
        font-weight: 600;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    body.dark-theme .author-details .author-name {
        color: #f1f5f9;
    }

    .author-details .author-id {
        font-size: 11px;
        color: #64748b;
    }

    body.dark-theme .author-details .author-id {
        color: #94a3b8;
    }

    /* Content Cell */
    .post-preview {
        font-size: 13px;
        color: #475569;
        line-height: 1.4;
        margin-bottom: 8px;
        word-break: break-word;
    }

    body.dark-theme .post-preview {
        color: #cbd5e1;
    }

    .post-image-preview img {
        max-width: 50px;
        max-height: 50px;
        border-radius: 6px;
    }

    .post-poll-preview {
        font-size: 12px;
        color: #3b82f6;
        background: rgba(59, 130, 246, 0.1);
        padding: 4px 8px;
        border-radius: 6px;
        display: inline-block;
        margin-top: 5px;
    }

    /* Date Cell */
    .post-date {
        font-size: 13px;
        color: #1e293b;
        white-space: nowrap;
    }

    body.dark-theme .post-date {
        color: #f1f5f9;
    }

    .post-updated {
        font-size: 11px;
        color: #64748b;
        margin-top: 3px;
        white-space: nowrap;
    }

    body.dark-theme .post-updated {
        color: #94a3b8;
    }

    /* Stats Cell */
    .stat-item {
        font-size: 13px;
        color: #475569;
        white-space: nowrap;
    }

    body.dark-theme .stat-item {
        color: #cbd5e1;
    }

    /* Type Cell */
    .type-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
    }

    .type-badge.text {
        background: rgba(100, 116, 139, 0.1);
        color: #64748b;
    }

    body.dark-theme .type-badge.text {
        background: rgba(148, 163, 184, 0.15);
        color: #94a3b8;
    }

    .type-badge.image {
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
    }

    .type-badge.poll {
        background: rgba(139, 92, 246, 0.1);
        color: #8b5cf6;
    }

    .type-badge.combined {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1));
        color: #3b82f6;
    }

    /* Actions Cell */
    .actions-cell {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .action-btn {
        width: 32px;
        height: 32px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        flex-shrink: 0;
    }

    body.dark-theme .action-btn {
        background: #1e293b;
        border-color: #475569;
        color: #cbd5e1;
    }

    .action-btn:hover {
        transform: scale(1.05);
    }

    .action-btn.view:hover {
        background: #3b82f6;
        border-color: #3b82f6;
        color: white;
    }

    .action-btn.edit:hover {
        background: #f59e0b;
        border-color: #f59e0b;
        color: white;
    }

    .action-btn.delete:hover {
        background: #ef4444;
        border-color: #ef4444;
        color: white;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .admin-container {
            padding: 15px;
        }

        .filters-container {
            flex-direction: column;
        }

        .filter-select,
        .filter-input {
            width: 100%;
        }

        .admin-table th,
        .admin-table td {
            padding: 8px 10px;
        }

        .actions-cell {
            flex-wrap: wrap;
        }
    }

    @media (max-width: 480px) {
        .admin-container {
            padding: 10px;
        }

        .admin-title {
            font-size: 22px;
        }

        .section-header {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }
    }
</style>