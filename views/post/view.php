<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Пост от ' . $post->user->username;
$this->registerCssFile('@web/css/post.css');
$this->registerJsFile('@web/js/post.js');
$this->registerJsFile('@web/js/feed.js', ['position' => \yii\web\View::POS_END]);

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
    <a href="<?= Url::to(['/feed/index']) ?>" class="btn-back">← Назад к ленте</a>

    <?= $this->render('/post/_post_card', ['post' => $post]) ?>

    <div class="comments-section-full">
        <div class="comments-header">
            <h3>💬 Комментарии <span class="comments-count-badge"><?= $commentsCount ?></span></h3>
        </div>
        
        <?php if (!Yii::$app->user->isGuest): ?>
            <div class="comment-form-full">
                <img src="<?= Html::encode($currentAvatar) ?>" class="comment-avatar-full" alt="Ваш аватар">
                <div class="comment-input-wrapper">
                    <textarea id="comment-input" class="comment-textarea" placeholder="Напишите комментарий..." rows="2" maxlength="1000"></textarea>
                    <div class="comment-form-footer">
                        <span class="comment-char-counter">0/1000</span>
                        <button class="btn-send" onclick="submitComment(<?= $post->id ?>)">📤 Отправить</button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="login-to-comment">
                <div class="login-to-comment-icon">🔒</div>
                <p><a href="<?= Url::to(['/site/login']) ?>">Войдите</a>, чтобы оставить комментарий</p>
            </div>
        <?php endif; ?>

        <div id="comments-list" class="comments-list-full">
            <?php if (empty($comments)): ?>
                <div class="empty-comments">
                    <div class="empty-comments-icon">💬</div>
                    <p>Пока нет комментариев</p>
                    <p class="empty-hint">Будьте первым, кто оставит комментарий!</p>
                </div>
            <?php else: ?>
                <?php foreach ($comments as $comment): 
                    $cAvatar = $comment->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $comment->user_id;
                    $isAuthor = $currentUserId && $comment->user_id == $currentUserId;
                    $timeAgo = $comment->getTimeAgo();
                ?>
                    <div class="comment-item" data-comment-id="<?= $comment->id ?>">
                        <img src="<?= Html::encode($cAvatar) ?>" class="comment-avatar-full" alt="<?= Html::encode($comment->user->username) ?>">
                        <div class="comment-body">
                            <div class="comment-header">
                                <a href="<?= Url::to(['/profile/view', 'id' => $comment->user_id]) ?>" class="comment-author-name"><?= Html::encode($comment->user->username) ?></a>
                                <span class="comment-time"><?= Html::encode($timeAgo) ?></span>
                                <?php if ($comment->updated_at > $comment->created_at): ?><span class="edited-mark">(ред.)</span><?php endif; ?>
                                <?php if ($isAuthor): ?>
                                    <div class="comment-actions">
                                        <button class="btn-edit-comment" onclick="editComment(<?= $comment->id ?>, <?= $post->id ?>)" title="Редактировать">✏️</button>
                                        <button class="btn-delete-comment" onclick="deleteComment(<?= $comment->id ?>, <?= $post->id ?>)" title="Удалить">🗑️</button>
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
document.addEventListener('DOMContentLoaded', function() {
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

function submitComment(postId) {
    const textarea = document.getElementById('comment-input');
    const content = textarea?.value.trim();
    if (!content) { showNotification('Напишите комментарий', 'error'); return; }
    const submitBtn = document.querySelector('.btn-send');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = '⏳ Отправка...'; }
    postWithCsrf('/api/comment/create', { post_id: postId, content })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                textarea.value = '';
                const counter = document.querySelector('.comment-char-counter');
                if (counter) counter.textContent = '0/1000';
                location.reload();
            } else { showNotification(data.error || 'Ошибка', 'error'); }
        })
        .catch(() => showNotification('Ошибка сети', 'error'))
        .finally(() => { if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = '📤 Отправить'; } });
}
</script>