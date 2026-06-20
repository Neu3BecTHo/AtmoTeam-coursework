<?php

use Dom\Comment;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var \yii\web\View $this
 * @var \app\models\Post $post
 * @var \app\models\Comment[] $comments
 */

$this->title = Yii::t('app','Post by {username}', ['username' => $post->user->username]);

// Подключаем стили
$this->registerCssFile('@web/css/post.css');
$this->registerCssFile('@web/css/feed.css'); // если есть общие стили для ленты

// Регистрируем глобальные CSS-переменные
$css = <<<CSS
/* ============================================
   Глобальные CSS-переменные (светлая тема по умолчанию)
   ============================================ */
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
    
    /* Цвета светлой темы */
    --primary-50: #eff6ff;
    --primary-100: #dbeafe;
    --primary-200: #bfdbfe;
    --primary-300: #93c5fd;
    --primary-400: #60a5fa;
    --primary-500: #3b82f6;
    --primary-600: #2563eb;
    --primary-700: #1d4ed8;
    --primary-800: #1e40af;
    --primary-900: #1e3a8a;
    
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
    --success-dark: #059669;
    --error: #ef4444;
    --error-light: #fee2e2;
    --error-dark: #dc2626;
    --warning: #f59e0b;
    --warning-light: #fed7aa;
    --warning-dark: #d97706;
    --info: #06b6d4;
    --info-light: #cffafe;
    --info-dark: #0891b2;
    
    --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    --card-hover-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
}

/* ============================================
   Тёмная тема (принудительно по классу или системным настройкам)
   ============================================ */
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

/* Автоматическое определение темы из системы */
@media (prefers-color-scheme: dark) {
    :root:not(.light-theme):not([data-theme="light"]) {
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
}

body {
    background: var(--surface-50);
    font-family: var(--font-sans);
    margin: 0;
    padding: 0;
    color: var(--text-primary);
    transition: background-color var(--transition-normal), color var(--transition-normal);
}

/* Адаптация post-card для тёмной темы */
.post-card {
    background: var(--surface-0);
    border-color: var(--border-primary);
}

.comments-section-full {
    background: var(--surface-0);
    border-color: var(--border-primary);
}

.comment-form-full {
    background: var(--surface-50);
    border-color: var(--border-primary);
}

.comment-input-wrapper textarea {
    background: var(--surface-0);
    color: var(--text-primary);
    border-color: var(--border-primary);
}

.comment-input-wrapper textarea:focus {
    border-color: var(--primary-400);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.comment-item {
    border-bottom-color: var(--border-primary);
}

.comment-author-name {
    color: var(--text-primary);
}

.comment-time,
.edited-mark {
    color: var(--text-tertiary);
}

.comment-text {
    color: var(--text-secondary);
}

.empty-comments {
    color: var(--text-tertiary);
}

.login-to-comment {
    background: var(--surface-50);
    border-color: var(--border-primary);
    color: var(--text-secondary);
}
CSS;

$this->registerCss($css);

$this->registerJsFile('@web/js/feed.js', ['position' => \yii\web\View::POS_END]);
$this->registerJsFile('@web/js/post.js', ['position' => \yii\web\View::POS_END]);

$avatar = $post->user->getAvatarUrl();
$username = Html::encode($post->user->username);
$currentUserId = Yii::$app->user->id;
$currentUser = Yii::$app->user->identity;
$currentAvatar = $currentUser ? $currentUser->getAvatarUrl() : 'https://api.dicebear.com/7.x/avataaars/svg?seed=0';
$commentsCount = count($comments);
$isLiked = !Yii::$app->user->isGuest && $post->isLikedBy($currentUserId);
$isSaved = !Yii::$app->user->isGuest && $post->isSavedBy($currentUserId);
$isReposted = !Yii::$app->user->isGuest && $post->isRepostedBy($currentUserId);
$likesCount = number_format($post->likes_count);
$repostsCount = number_format($post->getRepostsCount());
$timeAgo = $post->getTimeAgo();
$imageUrls = $post->getImageUrls();

?>

<div class="post-detail-container">
    <a href="<?= Url::to(['/feed/index']) ?>" class="btn-back"><?= Yii::t('app','← Назад к ленте') ?></a>

    <?= $this->render('/post/_post_card', ['post' => $post]) ?>

    <div class="comments-section-full">
        <div class="comments-header">
            <h3><?= Yii::t('app','💬 Комментарии') ?> <span class="comments-count-badge"><?= $commentsCount ?></span></h3>
        </div>

        <div id="comments-list" class="comments-list-full">
            <?php if (empty($comments)): ?>
                <div class="empty-comments">
                    <div class="empty-comments-icon">💬</div>
                    <p><?= Yii::t('app','Пока нет комментариев') ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($comments as $comment):
                    $cAvatar = $comment->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $comment->user_id;
                    $isAuthor = $currentUserId && $comment->user_id == $currentUserId;
                    $timeAgo = $comment->getTimeAgo();
                    ?>
                    <div class="comment-item" data-comment-id="<?= $comment->id ?>">
                        <img src="<?= Html::encode($cAvatar) ?>" class="comment-avatar-full"
                            alt="<?= Html::encode($comment->user->username) ?>">
                        <div class="comment-body">
                            <div class="comment-header">
                                <a href="<?= Url::to(['/profile/view', 'id' => $comment->user_id]) ?>"
                                    class="comment-author-name"><?= Html::encode($comment->user->username) ?></a>
                                <span class="comment-time"><?= Html::encode($timeAgo) ?></span>
                                <?php if ($comment->updated_at > $comment->created_at): ?><span
                                        class="edited-mark"><?= Yii::t('app','(ред.)') ?></span><?php endif; ?>
                                <?php if ($isAuthor): ?>
                                    <div class="comment-actions">
                                        <button class="btn-edit-comment"
                                            onclick="editComment(<?= $comment->id ?>, <?= $post->id ?>)"
                                            title="<?= Yii::t('app','Редактировать') ?>">✏️</button>
                                        <button class="btn-delete-comment"
                                            onclick="deleteComment(<?= $comment->id ?>, <?= $post->id ?>)"
                                            title="<?= Yii::t('app','Удалить') ?>">🗑️</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="comment-text"><?= nl2br(Html::encode($comment->content)) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const textarea = document.getElementById('comment-input');
        const charCounter = document.querySelector('.comment-char-counter');
        if (textarea && charCounter) {
            const updateCharCount = () => {
                const len = textarea.value.length;
                charCounter.textContent = len + '/1000';
                charCounter.style.color = len > 900 ? '#ef4444' : len > 800 ? '#f59e0b' : 'inherit';
            };
            textarea.addEventListener('input', updateCharCount);
            updateCharCount();
            textarea.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    submitComment(<?= $post->id ?>);
                }
            });
        }
        initPostPageHandlers(<?= $post->id ?>);
    });

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    function initPostPageHandlers(postId) {
        const likeBtn = document.querySelector('.post-card .btn-like');
        if (likeBtn && !likeBtn.dataset.inited) {
            likeBtn.dataset.inited = 'true';
            likeBtn.onclick = (e) => { e.preventDefault(); handleLike(postId, likeBtn); };
        }
        const saveBtn = document.querySelector('.post-card .btn-save');
        if (saveBtn && !saveBtn.dataset.inited) {
            saveBtn.dataset.inited = 'true';
            saveBtn.onclick = (e) => { e.preventDefault(); handleSave(postId); };
        }
        const repostBtn = document.querySelector('.post-card .btn-repost');
        if (repostBtn && !repostBtn.dataset.inited) {
            repostBtn.dataset.inited = 'true';
            repostBtn.onclick = (e) => { e.preventDefault(); toggleRepost(postId); };
        }
    }

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

    function submitComment(postId) {
        const textarea = document.getElementById('comment-input');
        const content = textarea?.value.trim();
        if (!content) { showNotification(<?= json_encode(Yii::t('app','Напишите комментарий')) ?>, 'error'); return; }
        const submitBtn = document.querySelector('.btn-send');
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = window.t('sending'); }
        postWithCsrf('/api/comment/create', { post_id: postId, content })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    textarea.value = '';
                    const counter = document.querySelector('.comment-char-counter');
                    if (counter) counter.textContent = '0/1000';
                    location.reload();
                } else { showNotification(data.error || window.t('error'), 'error'); }
            })
            .catch(() => showNotification(window.t('network_error'), 'error'))
            .finally(() => { if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = window.t('send'); } });
    }
</script>