<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Комментарии - Админ-панель';

$todayCount = count(array_filter($comments, fn($c) => $c->created_at > time() - 86400));
$editedCount = count(array_filter($comments, fn($c) => $c->updated_at > $c->created_at));
$reportedCount = count(array_filter($comments, fn($c) => $c->is_reported));
$uniqueAuthors = count(array_unique(array_map(fn($c) => $c->user_id, $comments)));
?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">💬 Управление комментариями</h1>
        <p class="admin-subtitle">Всего комментариев: <?= count($comments) ?></p>
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
                    <label class="filter-label">🏷️ Статус:</label>
                    <select class="filter-select" id="statusFilter">
                        <option value="all">Все</option>
                        <option value="normal">Обычные</option>
                        <option value="edited">Отредактированные</option>
                        <option value="reported">С жалобами</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">🔎 Поиск:</label>
                    <input type="text" class="filter-input" id="searchFilter" placeholder="Текст комментария...">
                </div>
            </div>
        </div>

        <!-- Таблица комментариев -->
        <div class="admin-section">
            <div class="section-header">
                <h3 class="section-title">📋 Список комментариев</h3>
                <div class="section-actions">
                    <button class="btn-refresh" onclick="location.reload()">🔄 Обновить</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="admin-table" id="comments-table">
                    <thead>
                        <tr>
                            <th>👤 Автор</th>
                            <th>💬 Комментарий</th>
                            <th>📝 Пост</th>
                            <th>📅 Дата</th>
                            <th>📊 Статус</th>
                            <th>⚙️ Действия</th>
                        </tr>
                    </thead>
                    <tbody id="comments-tbody">
                        <?php foreach ($comments as $comment): ?>
                            <tr class="comment-row" 
                                data-comment-id="<?= $comment->id ?>"
                                data-user-id="<?= $comment->user_id ?>"
                                data-created-at="<?= $comment->created_at ?>"
                                data-is-edited="<?= ($comment->updated_at > $comment->created_at) ? 'true' : 'false' ?>"
                                data-is-reported="<?= ($comment->is_reported ?? false) ? 'true' : 'false' ?>"
                                data-content="<?= Html::encode(mb_strtolower($comment->content)) ?>">
                                
                                <!-- Автор -->
                                <td>
                                    <div class="author-cell">
                                        <a href="<?= Url::to(['/profile/view', 'id' => $comment->user_id]) ?>" target="_blank">
                                            <img class="author-avatar-small"
                                                 src="<?= $comment->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $comment->user_id ?>"
                                                 alt="<?= Html::encode($comment->user->username) ?>">
                                        </a>
                                        <div class="author-info">
                                            <div class="author-name"><?= Html::encode($comment->user->username) ?></div>
                                            <div class="author-id">ID: <?= $comment->user_id ?></div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Комментарий -->
                                <td>
                                    <div class="comment-content-cell">
                                        <div class="comment-text"><?= nl2br(Html::encode($comment->content)) ?></div>
                                        <?php if ($comment->updated_at > $comment->created_at): ?>
                                            <div class="comment-edited">
                                                <span class="edited-badge">✏️ Отредактирован</span>
                                                <span class="edited-date"><?= date('d.m.Y H:i', $comment->updated_at) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <!-- Пост -->
                                <td>
                                    <div class="post-cell">
                                        <div class="post-info">
                                            <div class="post-author">
                                                <a href="<?= Url::to(['/profile/view', 'id' => $comment->post->user_id]) ?>" target="_blank">
                                                    <img class="post-author-avatar"
                                                         src="<?= $comment->post->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $comment->post->user_id ?>"
                                                         alt="<?= Html::encode($comment->post->user->username) ?>">
                                                </a>
                                                <span class="post-author-name"><?= Html::encode($comment->post->user->username) ?></span>
                                            </div>
                                            <div class="post-preview">
                                                <?= Html::encode(mb_substr($comment->post->content, 0, 80)) ?>
                                                <?= strlen($comment->post->content) > 80 ? '...' : '' ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Дата -->
                                <td>
                                    <div class="date-cell">
                                        <div class="comment-date"><?= date('d.m.Y H:i', $comment->created_at) ?></div>
                                        <div class="comment-time-ago"><?= $comment->getTimeAgo() ?></div>
                                    </div>
                                </td>
                                
                                <!-- Статус -->
                                <td>
                                    <div class="status-cell">
                                        <?php if ($comment->is_reported ?? false): ?>
                                            <span class="status-badge reported">⚠️ Жалоба</span>
                                        <?php elseif ($comment->updated_at > $comment->created_at): ?>
                                            <span class="status-badge edited">✏️ Изменен</span>
                                        <?php else: ?>
                                            <span class="status-badge normal">✅ Обычный</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <!-- Действия -->
                                <td>
                                    <div class="actions-cell">
                                        <button class="action-btn view" onclick="viewPost(<?= $comment->post_id ?>)" title="Посмотреть пост">👁️</button>
                                        <button class="action-btn edit" onclick="editComment(<?= $comment->id ?>)" title="Редактировать">✏️</button>
                                        <button class="action-btn delete" onclick="deleteComment(<?= $comment->id ?>)" title="Удалить">🗑️</button>
                                        <?php if ($comment->is_reported ?? false): ?>
                                            <button class="action-btn clear" onclick="clearReport(<?= $comment->id ?>)" title="Снять жалобу">✅</button>
                                        <?php endif; ?>
                                        <button class="action-btn block" onclick="blockUser(<?= $comment->user_id ?>)" title="Заблокировать автора">🔒</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Статистика -->
        <div class="stats-section">
            <h3 class="section-title">📊 Статистика комментариев</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-info">
                        <div class="stat-value" id="stats-today"><?= $todayCount ?></div>
                        <div class="stat-label">За сегодня</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✏️</div>
                    <div class="stat-info">
                        <div class="stat-value" id="stats-edited"><?= $editedCount ?></div>
                        <div class="stat-label">Отредактировано</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-info">
                        <div class="stat-value" id="stats-reported"><?= $reportedCount ?></div>
                        <div class="stat-label">С жалобами</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <div class="stat-value" id="stats-unique"><?= $uniqueAuthors ?></div>
                        <div class="stat-label">Уникальных авторов</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$csrfToken = Yii::$app->request->csrfToken;
$clearReportUrl = Url::to(['/api/admin/clear-comment-report']);
$deleteCommentUrl = Url::to(['/api/admin/delete-comment']);
$blockUserUrl = Url::to(['/api/admin/block-user']);

$script = <<<JS
// ==================== Filter Functions ====================
function filterComments() {
    const period = document.getElementById('periodFilter')?.value || 'all';
    const status = document.getElementById('statusFilter')?.value || 'all';
    const search = document.getElementById('searchFilter')?.value.toLowerCase() || '';
    const now = Math.floor(Date.now() / 1000);
    const dayAgo = now - 86400;
    const weekAgo = now - 604800;
    const monthAgo = now - 2592000;
    
    let visibleCount = 0;
    let editedCount = 0;
    let reportedCount = 0;
    const uniqueAuthors = new Set();
    
    document.querySelectorAll('.comment-row').forEach(row => {
        const createdAt = parseInt(row.dataset.createdAt);
        const isEdited = row.dataset.isEdited === 'true';
        const isReported = row.dataset.isReported === 'true';
        const content = row.dataset.content?.toLowerCase() || '';
        
        let show = true;
        
        if (period === 'today' && createdAt < dayAgo) show = false;
        else if (period === 'week' && createdAt < weekAgo) show = false;
        else if (period === 'month' && createdAt < monthAgo) show = false;
        
        if (show && status !== 'all') {
            if (status === 'edited' && !isEdited) show = false;
            else if (status === 'reported' && !isReported) show = false;
            else if (status === 'normal' && (isEdited || isReported)) show = false;
        }
        
        if (show && search && !content.includes(search)) show = false;
        
        row.style.display = show ? '' : 'none';
        
        if (show) {
            visibleCount++;
            if (isEdited) editedCount++;
            if (isReported) reportedCount++;
            uniqueAuthors.add(row.dataset.userId);
        }
    });
    
    document.getElementById('stats-today').textContent = visibleCount;
    document.getElementById('stats-edited').textContent = editedCount;
    document.getElementById('stats-reported').textContent = reportedCount;
    document.getElementById('stats-unique').textContent = uniqueAuthors.size;
}

// ==================== Actions ====================
async function deleteComment(commentId) {
    if (typeof showDeleteModal !== 'function') return;
    showDeleteModal('Удалить этот комментарий?', async () => {
        try {
            const res = await postWithCsrf('$deleteCommentUrl', { comment_id: commentId });
            const data = await res.json();
            if (data.success) {
                showNotification('Комментарий удалён', 'success');
                document.querySelector('.comment-row[data-comment-id="' + commentId + '"]')?.remove();
                filterComments();
            } else {
                showNotification(data.error || 'Ошибка удаления', 'error');
            }
        } catch (e) { showNotification('Ошибка удаления', 'error'); }
    });
}

async function clearReport(commentId) {
    if (typeof showDeleteModal !== 'function') return;
    showDeleteModal('Снять жалобу с этого комментария?', async () => {
        try {
            const res = await postWithCsrf('$clearReportUrl', { comment_id: commentId });
            const data = await res.json();
            if (data.success) {
                showNotification('Жалоба снята', 'success');
                location.reload();
            } else {
                showNotification(data.error || 'Ошибка снятия жалобы', 'error');
            }
        } catch (e) { showNotification('Ошибка снятия жалобы', 'error'); }
    });
}

async function blockUser(userId) {
    if (typeof showDeleteModal !== 'function') return;
    showDeleteModal('Заблокировать этого пользователя на сайте?', async () => {
        try {
            const res = await postWithCsrf('$blockUserUrl', { user_id: userId });
            const data = await res.json();
            if (data.success) {
                showNotification('Пользователь заблокирован', 'success');
                location.reload();
            } else {
                showNotification(data.error || 'Ошибка блокировки', 'error');
            }
        } catch (e) { showNotification('Ошибка блокировки', 'error'); }
    });
}

function editComment(commentId) {
    showNotification('Функция редактирования комментариев будет добавлена позже', 'info');
}

function viewPost(postId) {
    window.open('/post/view?id=' + postId, '_blank');
}

// ==================== Event Listeners ====================
document.addEventListener('DOMContentLoaded', () => {
    const periodFilter = document.getElementById('periodFilter');
    const statusFilter = document.getElementById('statusFilter');
    const searchFilter = document.getElementById('searchFilter');
    let searchTimeout;
    
    periodFilter?.addEventListener('change', filterComments);
    statusFilter?.addEventListener('change', filterComments);
    searchFilter?.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterComments, 300);
    });
    
    filterComments();
});

// ==================== Exports ====================
window.deleteComment = deleteComment;
window.clearReport = clearReport;
window.blockUser = blockUser;
window.editComment = editComment;
window.viewPost = viewPost;
window.filterComments = filterComments;
JS;
$this->registerJs($script);
?>