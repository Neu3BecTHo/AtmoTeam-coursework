<?php
$this->title = 'Комментарии - Админ-панель';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">💬 Управление комментариями</h1>
        <p class="admin-subtitle">Всего комментариев: <?= count($comments) ?></p>
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
                    <label class="filter-label">Статус:</label>
                    <select class="filter-select" id="statusFilter">
                        <option value="all">Все</option>
                        <option value="normal">Обычные</option>
                        <option value="edited">Отредактированные</option>
                        <option value="reported">С жалобами</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Поиск:</label>
                    <input type="text" class="filter-input" id="searchFilter" placeholder="Текст комментария...">
                </div>
            </div>
        </div>

        <!-- Таблица комментариев -->
        <div class="admin-section">
            <div class="section-header">
                <h2 class="section-title">Список комментариев</h2>
                <div class="section-actions">
                    <button class="btn-action btn-refresh" onclick="location.reload()">
                        🔄 Обновить
                    </button>
                </div>
            </div>

            <div class="comments-table">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Автор</th>
                            <th>Комментарий</th>
                            <th>Пост</th>
                            <th>Дата</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comments as $comment): ?>
                            <tr class="comment-row" data-comment-id="<?= $comment->id ?>">
                                <td>
                                    <div class="author-cell">
                                        <img class="author-avatar-small" 
                                             src="<?= $comment->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $comment->user->id ?>" 
                                             alt="<?= \yii\helpers\Html::encode($comment->user->username) ?>">
                                        <div class="author-info">
                                            <div class="author-name"><?= \yii\helpers\Html::encode($comment->user->username) ?></div>
                                            <div class="author-id">ID: <?= $comment->user->id ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="comment-content-cell">
                                        <div class="comment-text">
                                            <?= \yii\helpers\Html::encode($comment->content) ?>
                                        </div>
                                        <?php if ($comment->updated_at > $comment->created_at): ?>
                                            <div class="comment-edited">
                                                <span class="edited-badge">Отредактирован</span>
                                                <span class="edited-date"><?= date('d.m.Y H:i', $comment->updated_at) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="post-cell">
                                        <div class="post-info">
                                            <div class="post-author">
                                                <img class="post-author-avatar" 
                                                     src="<?= $comment->post->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $comment->post->user->id ?>" 
                                                     alt="<?= \yii\helpers\Html::encode($comment->post->user->username) ?>">
                                                <span class="post-author-name"><?= \yii\helpers\Html::encode($comment->post->user->username) ?></span>
                                            </div>
                                            <div class="post-preview">
                                                <?= \yii\helpers\Html::encode(mb_substr($comment->post->content, 0, 80)) ?>
                                                <?php if (strlen($comment->post->content) > 80): ?>...<?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="date-cell">
                                        <div class="comment-date"><?= date('d.m.Y H:i', $comment->created_at) ?></div>
                                        <div class="comment-time-ago">
                                            <?= $this->context->getTimeAgo($comment->created_at) ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="status-cell">
                                        <?php if ($comment->is_reported): ?>
                                            <span class="status-badge reported">⚠️ Жалоба</span>
                                        <?php elseif ($comment->updated_at > $comment->created_at): ?>
                                            <span class="status-badge edited">✏️ Изменен</span>
                                        <?php else: ?>
                                            <span class="status-badge normal">✅ Обычный</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="actions-cell">
                                        <button class="btn-action btn-view" 
                                                onclick="window.open('/feed#post-<?= $comment->post_id ?>', '_blank')"
                                                title="Посмотреть пост">
                                            👁️
                                        </button>
                                        
                                        <button class="btn-action btn-edit" 
                                                onclick="editComment(<?= $comment->id ?>)"
                                                title="Редактировать">
                                            ✏️
                                        </button>
                                        
                                        <button type="button" class="btn-action btn-delete-comment" 
                                                data-comment-id="<?= $comment->id ?>"
                                                title="Удалить комментарий">
                                            🗑️
                                        </button>
                                        
                                        <?php if ($comment->is_reported): ?>
                                            <button class="btn-action btn-clear-report" 
                                                    onclick="clearReport(<?= $comment->id ?>)"
                                                    title="Снять жалобу">
                                                ✅
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn-action btn-block-user" 
                                                data-user-id="<?= (int) $comment->user_id ?>"
                                                title="Заблокировать автора">
                                            🔒
                                        </button>
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
            <h2 class="section-title">Статистика комментариев</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= count(array_filter($comments, fn($c) => $c->created_at > time() - 86400)) ?></div>
                        <div class="stat-label">За сегодня</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✏️</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= count(array_filter($comments, fn($c) => $c->updated_at > $c->created_at)) ?></div>
                        <div class="stat-label">Отредактировано</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= count(array_filter($comments, fn($c) => $c->is_reported)) ?></div>
                        <div class="stat-label">С жалобами</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= count(array_unique(array_map(fn($c) => $c->user_id, $comments))) ?></div>
                        <div class="stat-label">Уникальных авторов</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>

.btn-back {
    background: #6b7280;
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}

.btn-back:hover {
    background: #4b5563;
    transform: translateY(-1px);
}

.filters-container {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-label {
    font-size: 14px;
    font-weight: 500;
    color: #374151;
}

.filter-select, .filter-input {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    background: white;
    min-width: 150px;
}

.author-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.author-avatar-small {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e5e7eb;
}

.author-info {
    flex: 1;
}

.author-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 2px;
}

.author-id {
    font-size: 12px;
    color: #6b7280;
}

.comment-content-cell {
    max-width: 350px;
}

.comment-text {
    font-size: 14px;
    line-height: 1.4;
    color: #374151;
    margin-bottom: 8px;
}

.comment-edited {
    display: flex;
    align-items: center;
    gap: 8px;
}

.edited-badge {
    background: #f59e0b;
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 500;
}

.edited-date {
    font-size: 11px;
    color: #6b7280;
}

.post-cell {
    max-width: 250px;
}

.post-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.post-author {
    display: flex;
    align-items: center;
    gap: 8px;
}

.post-author-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    object-fit: cover;
}

.post-author-name {
    font-size: 12px;
    font-weight: 500;
    color: #1f2937;
}

.post-preview {
    font-size: 12px;
    color: #6b7280;
    line-height: 1.3;
}

.date-cell {
    font-size: 14px;
}

.comment-date {
    color: #1f2937;
    margin-bottom: 2px;
}

.comment-time-ago {
    font-size: 11px;
    color: #6b7280;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 500;
}

.status-badge.normal {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.edited {
    background: #fed7aa;
    color: #92400e;
}

.status-badge.reported {
    background: #fee2e2;
    color: #991b1b;
}

.actions-cell {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.btn-action {
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 6px 8px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.btn-action.btn-view {
    background: #6b7280;
}

.btn-action.btn-view:hover {
    background: #4b5563;
}

.btn-action.btn-edit {
    background: #f59e0b;
}

.btn-action.btn-edit:hover {
    background: #d97706;
}

.btn-action.btn-delete-comment {
    background: #ef4444;
}

.btn-action.btn-delete-comment:hover {
    background: #dc2626;
}

.btn-action.btn-clear-report {
    background: #10b981;
}

.btn-action.btn-clear-report:hover {
    background: #059669;
}

.btn-action.btn-block-user {
    background: #dc2626;
}

.btn-action.btn-block-user:hover {
    background: #b91c1c;
}

.btn-action.btn-refresh {
    background: #10b981;
}

</style>

<script>

function getTimeAgo(timestamp) {
    const seconds = Math.floor(Date.now() / 1000) - timestamp;
    const intervals = {
        year: 31536000,
        month: 2592000,
        week: 604800,
        day: 86400,
        hour: 3600,
        minute: 60
    };
    
    for (const [name, secondsInInterval] of Object.entries(intervals)) {
        const interval = Math.floor(seconds / secondsInInterval);
        if (interval >= 1) {
            const russianNames = {
                year: ['год', 'года', 'лет'],
                month: ['месяц', 'месяца', 'месяцев'],
                week: ['неделю', 'недели', 'недель'],
                day: ['день', 'дня', 'дней'],
                hour: ['час', 'часа', 'часов'],
                minute: ['минуту', 'минуты', 'минут']
            };
            
            let nameForm = russianNames[name][0];
            if (interval % 10 >= 2 && interval % 10 <= 4 && (interval % 100 < 10 || interval % 100 >= 20)) {
                nameForm = russianNames[name][1];
            } else if (interval % 10 === 1 && interval % 100 !== 11) {
                nameForm = russianNames[name][0];
            } else {
                nameForm = russianNames[name][2];
            }
            
            return `${interval} ${nameForm} назад`;
        }
    }
    
    return 'только что';
}

function editComment(commentId) {
    showNotification('Функция редактирования комментариев будет добавлена позже', 'info');
}

async function clearReport(commentId) {
    if (typeof showDeleteModal !== 'function' || typeof postWithCsrf !== 'function') {
        return;
    }
    showDeleteModal('Снять жалобу с этого комментария?', async () => {
        try {
            const response = await postWithCsrf('/api/admin/clear-comment-report', { comment_id: commentId });
            const result = await response.json();
            if (result.success) {
                showNotification('Жалоба снята', 'success');
                location.reload();
            } else {
                showNotification(result.error || 'Ошибка снятия жалобы', 'error');
            }
        } catch (error) {
            
            showNotification('Ошибка снятия жалобы', 'error');
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const periodFilter = document.getElementById('periodFilter');
    const statusFilter = document.getElementById('statusFilter');
    const searchFilter = document.getElementById('searchFilter');
    
    if (periodFilter) {
        periodFilter.addEventListener('change', function() {


        });
    }
    
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {


        });
    }
    
    if (searchFilter) {
        let searchTimeout;
        searchFilter.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {


            }, 500);
        });
    }
});
</script>

