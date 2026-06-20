<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('app','Комментарии - Админ-панель');
$deleteCommentUrl = Url::to(['/admin/delete-comment']);
$blockUserUrl = Url::to(['/admin/block-user']);
$csrfToken = Yii::$app->request->csrfToken;
$themeCss = <<<CSS
:root {
    --space-1: 0.25rem;
    --space-2: 0.5rem;
    --space-3: 0.75rem;
    --space-4: 1rem;
    --space-5: 1.25rem;
    --space-6: 1.5rem;
    --space-8: 2rem;
    
    --text-xs: 0.75rem;
    --text-sm: 0.875rem;
    --text-base: 1rem;
    --text-lg: 1.125rem;
    --text-xl: 1.25rem;
    --text-2xl: 1.5rem;
    --text-3xl: 1.875rem;
    
    --font-sans: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    --font-mono: 'SF Mono', Monaco, Consolas, monospace;
    --font-medium: 500;
    --font-semibold: 600;
    --font-bold: 700;
    
    --leading-relaxed: 1.625;
    
    --radius-sm: 0.25rem;
    --radius-md: 0.375rem;
    --radius-lg: 0.5rem;
    --radius-xl: 0.75rem;
    --radius-2xl: 1rem;
    --radius-full: 9999px;
    
    --transition-fast: 0.15s ease;
    --transition-normal: 0.2s ease;
    
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
    
    /* Светлая тема */
    --primary-50: #eff6ff;
    --primary-100: #dbeafe;
    --primary-200: #bfdbfe;
    --primary-300: #93c5fd;
    --primary-400: #60a5fa;
    --primary-500: #3b82f6;
    --primary-600: #2563eb;
    --primary-700: #1d4ed8;
    
    --surface-0: #ffffff;
    --surface-50: #f8fafc;
    --surface-100: #f1f5f9;
    --surface-200: #e2e8f0;
    --surface-300: #cbd5e1;
    --surface-400: #94a3b8;
    --surface-500: #64748b;
    --surface-600: #475569;
    --surface-700: #334155;
    --surface-800: #1e293b;
    --surface-900: #0f172a;
    
    --text-primary: #0f172a;
    --text-secondary: #334155;
    --text-tertiary: #64748b;
    --text-quaternary: #94a3b8;
    --text-inverse: #ffffff;
    
    --border-primary: #e2e8f0;
    --border-secondary: #cbd5e1;
    --border-tertiary: #94a3b8;
    
    --success: #10b981;
    --success-light: #d1fae5;
    --error: #ef4444;
    --error-light: #fee2e2;
    --warning: #f59e0b;
    --warning-light: #fed7aa;
    --info: #06b6d4;
    --info-light: #cffafe;
    
    --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    --card-hover-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
}

.dark-theme,
[data-theme="dark"],
body.dark-theme {
    --surface-0: #0f172a;
    --surface-50: #1e293b;
    --surface-100: #334155;
    --surface-200: #475569;
    --surface-300: #64748b;
    --surface-400: #94a3b8;
    --surface-500: #cbd5e1;
    --surface-600: #e2e8f0;
    --surface-700: #f1f5f9;
    --surface-800: #f8fafc;
    --surface-900: #ffffff;
    
    --text-primary: #f1f5f9;
    --text-secondary: #e2e8f0;
    --text-tertiary: #cbd5e1;
    --text-quaternary: #94a3b8;
    --text-inverse: #0f172a;
    
    --border-primary: #334155;
    --border-secondary: #475569;
    --border-tertiary: #64748b;
    
    --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3), 0 1px 2px 0 rgba(0, 0, 0, 0.2);
    --card-hover-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
}

/* Автоопределение тёмной темы */
@media (prefers-color-scheme: dark) {
    :root:not(.light-theme):not([data-theme="light"]) {
        --surface-0: #0f172a;
        --surface-50: #1e293b;
        --surface-100: #334155;
        --surface-200: #475569;
        --surface-300: #64748b;
        --surface-400: #94a3b8;
        
        --text-primary: #f1f5f9;
        --text-secondary: #e2e8f0;
        --text-tertiary: #cbd5e1;
        --text-quaternary: #94a3b8;
        --text-inverse: #0f172a;
        
        --border-primary: #334155;
        --border-secondary: #475569;
    }
}

body {
    background: var(--surface-50);
    font-family: var(--font-sans);
    margin: 0;
    padding: 0;
    color: var(--text-primary);
    transition: background-color var(--transition-normal), color var(--transition-normal);
}

/* ============================================
   Стили админ-панели
   ============================================ */
.admin-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.admin-header {
    background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
    color: white;
    padding: 25px 30px;
    border-radius: 20px;
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}

.admin-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    pointer-events: none;
}

.admin-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 8px 0;
}

.admin-subtitle {
    font-size: 14px;
    opacity: 0.9;
    margin: 0 0 20px 0;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.2);
    color: white;
    padding: 8px 16px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-back:hover {
    background: rgba(255,255,255,0.3);
    color: white;
    transform: translateX(-2px);
}

/* Кнопка переключения темы */
.theme-toggle {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--surface-0);
    border: 1px solid var(--border-primary);
    border-radius: 30px;
    padding: 8px 18px;
    cursor: pointer;
    color: var(--text-primary);
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
    margin-bottom: 20px;
}

.theme-toggle:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

/* Секции */
.admin-section {
    background: var(--surface-0);
    border: 1px solid var(--border-primary);
    border-radius: 16px;
    margin-bottom: 30px;
    overflow: hidden;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    background: var(--surface-50);
    border-bottom: 1px solid var(--border-primary);
    flex-wrap: wrap;
    gap: 12px;
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
    color: var(--text-primary);
}

.section-actions {
    display: flex;
    gap: 12px;
}

/* Фильтры */
.filters-container {
    display: flex;
    gap: 20px;
    padding: 20px 24px;
    flex-wrap: wrap;
    background: var(--surface-0);
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.filter-label {
    font-size: 12px;
    font-weight: 500;
    color: var(--text-tertiary);
}

.filter-select,
.filter-input {
    padding: 8px 14px;
    border: 1px solid var(--border-primary);
    border-radius: 10px;
    background: var(--surface-0);
    color: var(--text-primary);
    min-width: 160px;
    font-size: 14px;
    transition: all 0.2s ease;
}

.filter-select:focus,
.filter-input:focus {
    outline: none;
    border-color: var(--primary-500);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.btn-refresh {
    padding: 8px 18px;
    background: var(--surface-100);
    border: 1px solid var(--border-primary);
    border-radius: 10px;
    cursor: pointer;
    color: var(--text-primary);
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-refresh:hover {
    background: var(--surface-200);
    transform: translateY(-1px);
}

/* Таблица */
.table-responsive {
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th {
    background: var(--surface-50);
    padding: 14px 16px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    color: var(--text-secondary);
    border-bottom: 2px solid var(--border-primary);
}

.admin-table td {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border-primary);
    color: var(--text-secondary);
    vertical-align: middle;
}

.admin-table tr:hover {
    background: var(--surface-50);
}

/* Ячейка автора */
.author-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.author-avatar-small {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--border-primary);
}

.author-name {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 2px;
}

.author-id {
    font-size: 11px;
    color: var(--text-tertiary);
}

/* Ячейка комментария */
.comment-content-cell {
    max-width: 380px;
}

.comment-text {
    font-size: 13px;
    line-height: 1.5;
    color: var(--text-secondary);
    margin-bottom: 8px;
    word-break: break-word;
}

.comment-edited {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.edited-badge {
    background: var(--warning-light);
    color: #92400e;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
}

.dark-theme .edited-badge {
    background: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
}

.edited-date {
    font-size: 10px;
    color: var(--text-tertiary);
}

/* Ячейка поста */
.post-cell {
    max-width: 280px;
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
    width: 28px;
    height: 28px;
    border-radius: 50%;
    object-fit: cover;
}

.post-author-name {
    font-size: 12px;
    font-weight: 500;
    color: var(--text-primary);
}

.post-preview {
    font-size: 12px;
    color: var(--text-tertiary);
    line-height: 1.4;
}

/* Ячейка даты */
.date-cell {
    white-space: nowrap;
}

.comment-date {
    font-size: 13px;
    color: var(--text-primary);
    margin-bottom: 3px;
}

.comment-time-ago {
    font-size: 11px;
    color: var(--text-tertiary);
}

/* Статус бейджи */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.normal {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.status-badge.edited {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
}

/* Кнопки действий */
.actions-cell {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.action-btn {
    width: 34px;
    height: 34px;
    background: var(--surface-100);
    border: 1px solid var(--border-primary);
    border-radius: 8px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    color: var(--text-secondary);
    font-size: 14px;
}

.action-btn:hover {
    transform: scale(1.05);
}

.action-btn.view:hover {
    background: var(--primary-500);
    color: white;
    border-color: var(--primary-500);
}

.action-btn.edit:hover {
    background: var(--warning);
    color: white;
    border-color: var(--warning);
}

.action-btn.delete:hover {
    background: var(--error);
    color: white;
    border-color: var(--error);
}

.action-btn.block:hover {
    background: var(--error);
    color: white;
    border-color: var(--error);
}

/* Статистика */
.stats-section {
    background: var(--surface-0);
    border: 1px solid var(--border-primary);
    border-radius: 16px;
    padding: 20px 24px;
    margin-top: 10px;
}

.stats-section .section-title {
    margin-bottom: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
}

.stat-card {
    background: var(--surface-50);
    border: 1px solid var(--border-primary);
    border-radius: 14px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}

.stat-icon {
    font-size: 36px;
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--surface-100);
    border-radius: 14px;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 12px;
    color: var(--text-tertiary);
}

/* Пустая строка */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-tertiary);
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

/* Responsive */
@media (max-width: 1024px) {
    .admin-table th,
    .admin-table td {
        padding: 10px 12px;
    }
}

@media (max-width: 768px) {
    .admin-container {
        padding: 15px;
    }
    
    .admin-header {
        padding: 20px;
    }
    
    .admin-title {
        font-size: 22px;
    }
    
    .filters-container {
        flex-direction: column;
    }
    
    .filter-select,
    .filter-input {
        width: 100%;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .section-header {
        flex-direction: column;
        text-align: center;
    }
    
    .actions-cell {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .admin-container {
        padding: 10px;
    }
    
    .admin-header {
        padding: 16px;
    }
    
    .admin-title {
        font-size: 18px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-section {
        padding: 16px;
    }
    
    .stat-card {
        padding: 12px 16px;
    }
    
    .stat-icon {
        font-size: 28px;
        width: 48px;
        height: 48px;
    }
    
    .stat-value {
        font-size: 22px;
    }
    
    .author-cell {
        flex-direction: column;
        text-align: center;
    }
    
    .admin-table th,
    .admin-table td {
        padding: 8px 10px;
        font-size: 11px;
    }
}
CSS;

$this->registerCss($themeCss);
$todayCount = count(array_filter($comments, fn($c) => $c->created_at > time() - 86400));
$editedCount = count(array_filter($comments, fn($c) => $c->updated_at > $c->created_at));
$reportedCount = 0;
try {
    if (!empty($comments) && property_exists(reset($comments), 'is_reported')) {
        $reportedCount = count(array_filter($comments, fn($c) => $c->is_reported));
    }
} catch (\Exception $e) {
    $reportedCount = 0;
}
$uniqueAuthors = count(array_unique(array_map(fn($c) => $c->user_id, $comments)));
?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">💬 <?= Yii::t('app','Управление комментариями') ?></h1>
        <p class="admin-subtitle"><?= Yii::t('app','Всего комментариев: {n}', ['n' => number_format(count($comments))]) ?></p>
        <?= Html::a('← ' . Yii::t('app','Назад'), ['/admin/index'], ['class' => 'btn-back']) ?>
    </div>

    <!-- Кнопка переключения темы -->
    <div style="display: flex; justify-content: flex-end;">
        <button onclick="toggleTheme()" class="theme-toggle">
            🌓 <?= Yii::t('app','Сменить тему') ?>
        </button>
    </div>

    <div class="admin-content">
        <!-- Фильтры -->
        <div class="admin-section">
            <div class="section-header">
                <h3 class="section-title">🔍 <?= Yii::t('app','Фильтры') ?></h3>
            </div>
            <div class="filters-container">
                <div class="filter-group">
                    <label class="filter-label">📅 <?= Yii::t('app','Период') ?>:</label>
                    <select class="filter-select" id="periodFilter">
                        <option value="all"><?= Yii::t('app','Все время') ?></option>
                        <option value="today"><?= Yii::t('app','Сегодня') ?></option>
                        <option value="week"><?= Yii::t('app','За неделю') ?></option>
                        <option value="month"><?= Yii::t('app','За месяц') ?></option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">🏷️ <?= Yii::t('app','Статус') ?>:</label>
                    <select class="filter-select" id="statusFilter">
                        <option value="all"><?= Yii::t('app','Все') ?></option>
                        <option value="normal"><?= Yii::t('app','Обычные') ?></option>
                        <option value="edited"><?= Yii::t('app','Отредактированные') ?></option>
                        <option value="reported"><?= Yii::t('app','С жалобами') ?></option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">🔎 <?= Yii::t('app','Поиск') ?>:</label>
                    <input type="text" class="filter-input" id="searchFilter" placeholder="<?= Yii::t('app','Текст комментария...') ?>">
                </div>
            </div>
        </div>

        <!-- Таблица комментариев -->
        <div class="admin-section">
            <div class="section-header">
                <h3 class="section-title">📋 <?= Yii::t('app','Список комментариев') ?></h3>
                <div class="section-actions">
                    <button class="btn-refresh" onclick="location.reload()">🔄 <?= Yii::t('app','Обновить') ?></button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="admin-table" id="comments-table">
                    <thead>
                        <tr>
                            <th>👤 <?= Yii::t('app','Автор') ?></th>
                            <th>💬 <?= Yii::t('app','Комментарий') ?></th>
                            <th>📝 <?= Yii::t('app','Пост') ?></th>
                            <th>📅 <?= Yii::t('app','Дата') ?></th>
                            <th>📊 <?= Yii::t('app','Статус') ?></th>
                            <th>⚙️ <?= Yii::t('app','Действия') ?></th>
                        </tr>
                    </thead>
                    <tbody id="comments-tbody">
                        <?php if (empty($comments)): ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <div class="empty-icon">💬</div>
                                    <p><?= Yii::t('app','Комментариев не найдено') ?></p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <tr class="comment-row" data-comment-id="<?= $comment->id ?>"
                                    data-user-id="<?= $comment->user_id ?>" data-created-at="<?= $comment->created_at ?>"
                                    data-is-edited="<?= ($comment->updated_at > $comment->created_at) ? 'true' : 'false' ?>"
                                    data-is-reported="false"
                                    data-content="<?= Html::encode(mb_strtolower($comment->content)) ?>">

                                    <!-- Автор -->
                                    <td>
                                        <div class="author-cell">
                                            <a href="<?= Url::to(['/profile/view', 'id' => $comment->user_id]) ?>"
                                                target="_blank">
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
                                                    <span class="edited-badge">✏️ <?= Yii::t('app','Отредактирован') ?></span>
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
                                                    <a href="<?= Url::to(['/profile/view', 'id' => $comment->post->user_id]) ?>"
                                                        target="_blank">
                                                        <img class="post-author-avatar"
                                                            src="<?= $comment->post->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $comment->post->user_id ?>"
                                                            alt="<?= Html::encode($comment->post->user->username) ?>">
                                                    </a>
                                                    <span
                                                        class="post-author-name"><?= Html::encode($comment->post->user->username) ?></span>
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
                                            <?php if ($comment->updated_at > $comment->created_at): ?>
                                                <span class="status-badge edited">✏️ <?= Yii::t('app','Изменен') ?></span>
                                            <?php else: ?>
                                                <span class="status-badge normal">✅ <?= Yii::t('app','Обычный') ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Действия -->
                                    <td>
                                        <div class="actions-cell">
                                            <button type="button" class="action-btn view"
                                                onclick="viewPost(<?= $comment->post_id ?>)"
                                                title="<?= Yii::t('app','Посмотреть пост') ?>">👁️</button>
                                            <button type="button" class="action-btn edit"
                                                onclick="editComment(<?= $comment->id ?>)" title="<?= Yii::t('app','Редактировать') ?>">✏️</button>
                                            <button type="button" class="action-btn delete"
                                                data-comment-id="<?= $comment->id ?>" title="<?= Yii::t('app','Удалить') ?>">🗑️</button>
                                            <button type="button" class="action-btn block"
                                                onclick="blockUser(<?= $comment->user_id ?>)"
                                                title="<?= Yii::t('app','Заблокировать автора') ?>">🔒</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Статистика -->
        <div class="stats-section">
            <h3 class="section-title">📊 <?= Yii::t('app','Статистика комментариев') ?></h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-info">
                        <div class="stat-value" id="stats-today"><?= number_format($todayCount) ?></div>
                        <div class="stat-label"><?= Yii::t('app','За сегодня') ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✏️</div>
                    <div class="stat-info">
                        <div class="stat-value" id="stats-edited"><?= number_format($editedCount) ?></div>
                        <div class="stat-label"><?= Yii::t('app','Отредактировано') ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-info">
                        <div class="stat-value" id="stats-reported"><?= number_format($reportedCount) ?></div>
                        <div class="stat-label"><?= Yii::t('app','С жалобами') ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <div class="stat-value" id="stats-unique"><?= number_format($uniqueAuthors) ?></div>
                        <div class="stat-label"><?= Yii::t('app','Уникальных авторов') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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
        const uniqueAuthors = new Set();

        document.querySelectorAll('.comment-row').forEach(row => {
            const createdAt = parseInt(row.dataset.createdAt);
            const isEdited = row.dataset.isEdited === 'true';
            const content = row.dataset.content?.toLowerCase() || '';

            let show = true;

            if (period === 'today' && createdAt < dayAgo) show = false;
            else if (period === 'week' && createdAt < weekAgo) show = false;
            else if (period === 'month' && createdAt < monthAgo) show = false;

            if (show && status !== 'all') {
                if (status === 'edited' && !isEdited) show = false;
                else if (status === 'normal' && isEdited) show = false;
            }

            if (show && search && !content.includes(search)) show = false;

            row.style.display = show ? '' : 'none';

            if (show) {
                visibleCount++;
                if (isEdited) editedCount++;
                uniqueAuthors.add(row.dataset.userId);
            }
        });

        document.getElementById('stats-today').textContent = visibleCount.toLocaleString();
        document.getElementById('stats-edited').textContent = editedCount.toLocaleString();
        document.getElementById('stats-unique').textContent = uniqueAuthors.size.toLocaleString();
    }

    async function deleteComment(commentId) {
    if (!confirm('Удалить этот комментарий?')) return;
    try {
        const response = await postWithCsrf('<?= $deleteCommentUrl ?>', { comment_id: commentId });
                const result = await response.json();
                if (result.success) {
                    showNotification('Комментарий удалён', 'success');
                    const row = document.querySelector('.comment-row[data-comment-id="' + commentId + '"]');
                    if (row) row.remove();
                    filterComments();
                } else {
                    showNotification(result.error || 'Ошибка удаления', 'error');
                }
            } catch (error) {
                showNotification('Ошибка удаления', 'error');
            }
        }

        async function blockUser(userId) {
            if (!confirm('Заблокировать этого пользователя?')) return;
            try {
                const response = await postWithCsrf('<?= $blockUserUrl ?>', { user_id: userId });
                const result = await response.json();
                if (result.success) {
                    showNotification('Пользователь заблокирован', 'success');
                    location.reload();
                } else {
                    showNotification(result.error || 'Ошибка блокировки', 'error');
                }
            } catch (error) {
                showNotification('Ошибка блокировки', 'error');
            }
        }

    function editComment(commentId) {
        alert('✏️ <?= Yii::t('app','Функция редактирования комментариев будет добавлена позже') ?>');
    }

    function viewPost(postId) {
        window.open('/post/view?id=' + postId, '_blank');
    }

    // Инициализация при загрузке
    document.addEventListener('DOMContentLoaded', () => {
        loadTheme();

        const periodFilter = document.getElementById('periodFilter');
        const statusFilter = document.getElementById('statusFilter');
        const searchFilter = document.getElementById('searchFilter');
        let searchTimeout;

        if (periodFilter) periodFilter.addEventListener('change', filterComments);
        if (statusFilter) statusFilter.addEventListener('change', filterComments);
        if (searchFilter) {
            searchFilter.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(filterComments, 300);
            });
        }

        filterComments();
    });

    // Делегирование событий для кнопок удаления
    document.addEventListener('click', function (e) {
        const deleteBtn = e.target.closest('.action-btn.delete[data-comment-id]');
        if (deleteBtn) {
            const commentId = deleteBtn.dataset.commentId;
            if (commentId) deleteComment(commentId);
        }
    });

    // Экспорт глобальных функций
    window.deleteComment = deleteComment;
    window.blockUser = blockUser;
    window.editComment = editComment;
    window.viewPost = viewPost;
    window.filterComments = filterComments;
    window.postWithCsrf = postWithCsrf;
    window.toggleTheme = toggleTheme;
    window.loadTheme = loadTheme;
</script>