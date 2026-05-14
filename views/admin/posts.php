<?php
$this->title = 'Посты - Админ-панель';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">📝 Управление постами</h1>
        <p class="admin-subtitle">Всего постов: <?= count($posts) ?></p>
        <a href="<?= \yii\helpers\Url::to(['/admin/index']) ?>" class="btn-back">← Назад</a>
    </div>

    <div class="admin-content">
        <!-- Фильтры -->
        <div class="admin-section">
            <div class="section-header">
                <h2 class="section-title">Фильтры</h2>
            </div>
            <div class="filters-container">
                <div class="filter-group">
                    <label class="filter-label">Период:</label>
                    <select class="filter-select" id="periodFilter">
                        <option value="all">Все время</option>
                        <option value="today">Сегодня</option>
                        <option value="week">За неделю</option>
                        <option value="month">За месяц</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Сортировка:</label>
                    <select class="filter-select" id="sortFilter">
                        <option value="newest">Новые</option>
                        <option value="popular">Популярные</option>
                        <option value="comments">Комментарии</option>
                        <option value="likes">Лайки</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Тип:</label>
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
                <h2 class="section-title">Список постов</h2>
                <div class="section-actions">
                    <button class="btn-action btn-refresh" onclick="location.reload()">
                        🔄 Обновить
                    </button>
                </div>
            </div>

            <div class="posts-table">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Автор</th>
                            <th>Содержание</th>
                            <th>Дата</th>
                            <th>Статистика</th>
                            <th>Тип</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                            <tr class="post-row" data-post-id="<?= $post->id ?>">
                                <td data-label="Автор">
                                    <div class="author-cell">
                                        <img class="author-avatar-small" 
                                             src="<?= $post->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $post->user->id ?>" 
                                             alt="<?= \yii\helpers\Html::encode($post->user->username) ?>">
                                        <div class="author-info">
                                            <div class="author-name"><?= \yii\helpers\Html::encode($post->user->username) ?></div>
                                            <div class="author-id">ID: <?= $post->user->id ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Содержание">
                                    <div class="content-cell">
                                        <div class="post-preview">
                                            <?= \yii\helpers\Html::encode(mb_substr($post->content, 0, 150)) ?>
                                            <?php if (strlen($post->content) > 150): ?>...<?php endif; ?>
                                        </div>
                                        <?php if ($post->image): ?>
                                            <div class="post-image-preview">
                                                <img src="<?= '/' . $post->image ?>" alt="Изображение поста">
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($post->poll): ?>
                                            <div class="post-poll-preview">
                                                📊 Опрос: <?= \yii\helpers\Html::encode($post->poll->question) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td data-label="Дата">
                                    <div class="date-cell">
                                        <div class="post-date"><?= date('d.m.Y H:i', $post->created_at) ?></div>
                                        <?php if ($post->updated_at > $post->created_at): ?>
                                            <div class="post-updated">Изменен: <?= date('d.m.Y H:i', $post->updated_at) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td data-label="Статистика">
                                    <div class="stats-cell">
                                        <div class="stat-row">
                                            <span class="stat-icon">❤️</span>
                                            <span class="stat-value"><?= $post->likes_count ?></span>
                                        </div>
                                        <div class="stat-row">
                                            <span class="stat-icon">💬</span>
                                            <span class="stat-value"><?= $post->comments_count ?></span>
                                        </div>
                                        <div class="stat-row">
                                            <span class="stat-icon">🔄</span>
                                            <span class="stat-value"><?= $post->getRepostsCount() ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Тип">
                                    <div class="type-cell">
                                        <?php if ($post->image && $post->poll): ?>
                                            <span class="type-badge combined">🖼️📊</span>
                                        <?php elseif ($post->image): ?>
                                            <span class="type-badge image">🖼️</span>
                                        <?php elseif ($post->poll): ?>
                                            <span class="type-badge poll">📊</span>
                                        <?php else: ?>
                                            <span class="type-badge text">📝</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td data-label="Действия">
                                    <div class="actions-cell">
                                        <button class="btn-action btn-view" 
                                                onclick="window.open('/feed#post-<?= $post->id ?>', '_blank')"
                                                title="Посмотреть пост">
                                            👁️
                                        </button>
                                        
                                        <button class="btn-action btn-edit" 
                                                onclick="editPost(<?= $post->id ?>)"
                                                title="Редактировать">
                                            ✏️
                                        </button>
                                        
                                        <button type="button" class="btn-action btn-delete-post" 
                                                data-post-id="<?= $post->id ?>"
                                                onclick="event.stopPropagation()"
                                                title="Удалить пост">
                                            🗑️
                                        </button>
                                        
                                        <?php if ($post->is_reported): ?>
                                            <button class="btn-action btn-report" 
                                                    title="Есть жалобы">
                                                ⚠️
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Статистика -->
        <div class="admin-section">
            <h2 class="section-title">Статистика постов</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= count(array_filter($posts, fn($p) => $p->created_at > time() - 86400)) ?></div>
                        <div class="stat-label">За сегодня</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= count(array_filter($posts, fn($p) => $p->poll)) ?></div>
                        <div class="stat-label">С опросами</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🖼️</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= count(array_filter($posts, fn($p) => $p->image)) ?></div>
                        <div class="stat-label">С изображениями</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🔥</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= array_sum(array_map(fn($p) => $p->likes_count, $posts)) ?></div>
                        <div class="stat-label">Всего лайков</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

function editPost(postId) {
    showNotification('Функция редактирования постов будет добавлена позже', 'info');
}

async function filterPosts() {
    const periodFilter = document.getElementById('periodFilter');
    const sortFilter = document.getElementById('sortFilter');
    const typeFilter = document.getElementById('typeFilter');
    
    const params = new URLSearchParams();
    if (periodFilter && periodFilter.value !== 'all') {
        params.append('period', periodFilter.value);
    }
    if (sortFilter && sortFilter.value !== 'newest') {
        params.append('sort', sortFilter.value);
    }
    if (typeFilter && typeFilter.value !== 'all') {
        params.append('type', typeFilter.value);
    }
    
    try {
        const response = await fetchWithLoading(`/admin/api/posts?${params.toString()}`, {
            loadingMessage: 'Применяем фильтрацию...'
        });
        const data = await response.json();
        
        if (data.success) {
            updatePostsTable(data.posts);
            showNotification('Фильтрация применена', 'success');
        } else {
            showNotification(data.error || 'Ошибка фильтрации', 'error');
        }
    } catch (error) {

    }
}

function updatePostsTable(posts) {
    const tbody = document.querySelector('.admin-table tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    posts.forEach(post => {
        const row = document.createElement('tr');
        row.className = 'post-row';
        row.dataset.postId = post.id;
        
        row.innerHTML = `
            <td>
                <div class="author-cell">
                    <img class="author-avatar-small" 
                         src="${post.user.avatar || 'https://api.dicebear.com/7.x/avataaars/svg?seed=' + post.user.id}" 
                         alt="${post.user.username}">
                    <div class="author-info">
                        <div class="author-name">${post.user.username}</div>
                        <div class="author-id">ID: ${post.user.id}</div>
                    </div>
                </div>
            </td>
            <td>
                <div class="content-cell">
                    <div class="post-preview">
                        ${post.content.substring(0, 150)}${post.content.length > 150 ? '...' : ''}
                    </div>
                    ${post.image ? `
                        <div class="post-image-preview">
                            <img src="/${post.image}" alt="Изображение поста">
                        </div>
                    ` : ''}
                    ${post.poll ? `
                        <div class="post-poll-preview">
                            📊 Опрос: ${post.poll.question}
                        </div>
                    ` : ''}
                </div>
            </td>
            <td>
                <div class="date-cell">
                    <div class="post-date">${new Date(post.created_at * 1000).toLocaleString('ru-RU')}</div>
                    ${post.updated_at > post.created_at ? `
                        <div class="post-updated">Изменен: ${new Date(post.updated_at * 1000).toLocaleString('ru-RU')}</div>
                    ` : ''}
                </div>
            </td>
            <td>
                <div class="stats-cell">
                    <div class="stat-row">
                        <span class="stat-icon">❤️</span>
                        <span class="stat-value">${post.likes_count}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-icon">💬</span>
                        <span class="stat-value">${post.comments_count}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-icon">🔄</span>
                        <span class="stat-value">${post.reposts_count || 0}</span>
                    </div>
                </div>
            </td>
            <td>
                <div class="type-cell">
                    ${post.image && post.poll ? '<span class="type-badge combined">🖼️📊</span>' : 
                      post.image ? '<span class="type-badge image">🖼️</span>' : 
                      post.poll ? '<span class="type-badge poll">📊</span>' : 
                      '<span class="type-badge text">📝</span>'}
                </div>
            </td>
            <td>
                <div class="actions-cell">
                    <button class="btn-action btn-view" 
                            onclick="window.open('/feed#post-${post.id}', '_blank')"
                            title="Посмотреть пост">
                        👁️
                    </button>
                    
                    <button class="btn-action btn-edit" 
                            onclick="editPost(${post.id})"
                            title="Редактировать">
                        ✏️
                    </button>
                    
                    <button type="button" class="btn-action btn-delete-post" 
                            data-post-id="${post.id}"
                            title="Удалить пост">
                        🗑️
                    </button>
                    
                    ${post.is_reported ? `
                        <button class="btn-action btn-report" 
                                title="Есть жалобы">
                            ⚠️
                        </button>
                    ` : ''}
                </div>
            </td>
        `;
        
        tbody.appendChild(row);
    });
}

function showLoadingIndicator() {
    const tbody = document.querySelector('.admin-table tbody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px;">Загрузка...</td></tr>';
    }
}

function hideLoadingIndicator() {

}

document.addEventListener('DOMContentLoaded', function() {
    const periodFilter = document.getElementById('periodFilter');
    const sortFilter = document.getElementById('sortFilter');
    const typeFilter = document.getElementById('typeFilter');
    
    if (periodFilter) {
        periodFilter.addEventListener('change', filterPosts);
    }
    
    if (sortFilter) {
        sortFilter.addEventListener('change', filterPosts);
    }
    
    if (typeFilter) {
        typeFilter.addEventListener('change', filterPosts);
    }
});
</script>

