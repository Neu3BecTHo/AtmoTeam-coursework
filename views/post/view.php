<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Пост от ' . $post->user->username;
$this->registerCssFile('@web/css/post.css');
$this->registerJsFile('@web/js/common.js');
$this->registerJsFile('@web/js/posts-unified.js');
$avatar = $post->user->getAvatarUrl();
$currentUserId = Yii::$app->user->id;
?>

<div class="post-detail-container">
    <!-- Back button -->
    <a href="<?= Url::to(['/feed/index']) ?>" class="btn-back">
        ← Назад к ленте
    </a>

    <!-- Post Card -->
    <?= $this->render('/post/_post_card', ['post' => $post]) ?>

    <!-- Comments Section -->
    <div class="comments-section-full">
        <h3>Комментарии (<?= count($comments) ?>)</h3>
        
        <?php if (!Yii::$app->user->isGuest): ?>
            <div class="comment-form-full">
                <img src="<?= Yii::$app->user->identity->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $currentUserId ?>" class="comment-avatar-full" alt="Аватар">
                <div class="comment-input-wrapper">
                    <textarea id="comment-input" placeholder="Напишите комментарий..." rows="2"></textarea>
                    <button class="btn-send" onclick="submitComment(<?= $post->id ?>)">Отправить</button>
                </div>
            </div>
        <?php else: ?>
            <div class="login-to-comment">
                <a href="<?= Url::to(['/site/login']) ?>">Войдите</a>, чтобы оставить комментарий
            </div>
        <?php endif; ?>

        <div class="comments-list-full" id="comments-list">
            <?php if (empty($comments)): ?>
                <div class="no-comments">
                    <p>Пока нет комментариев. Будьте первым!</p>
                </div>
            <?php else: ?>
                <?php foreach ($comments as $comment): 
                    $cAvatar = $comment->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $comment->user_id;
                ?>
                    <div class="comment-item" data-comment-id="<?= $comment->id ?>">
                        <img src="<?= $cAvatar ?>" class="comment-avatar-full">
                        <div class="comment-body">
                            <div class="comment-header">
                                <a href="<?= Url::to(['/profile/view', 'id' => $comment->user_id]) ?>" class="comment-author-name">
                                    <?= Html::encode($comment->user->username) ?>
                                </a>
                                <span class="comment-time"><?= $comment->timeAgo ?></span>
                                <?php if (!Yii::$app->user->isGuest && $comment->user_id == $currentUserId): ?>
                                    <div class="comment-actions">
                                        <button class="btn-edit-comment" onclick="editComment(<?= $comment->id ?>, <?= $post->id ?>)" title="Редактировать">✏️</button>
                                        <button class="btn-delete-comment" onclick="deleteComment(<?= $comment->id ?>, <?= $post->id ?>)" title="Удалить">🗑️</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <p class="comment-text"><?= Html::encode($comment->content) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<style>
.post-detail-container {
    max-width: 700px;
    margin: 0 auto;
    padding: 20px;
}

.btn-back {
    display: inline-block;
    margin-bottom: 20px;
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
}

.post-detail-card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.post-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.post-author {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: inherit;
}

.author-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
}

.author-info {
    display: flex;
    flex-direction: column;
}

.author-name {
    font-weight: 600;
    font-size: 16px;
    color: #1f2937;
}

.author-name:hover {
    color: #667eea;
}

.post-time {
    font-size: 14px;
    color: #6b7280;
}

.btn-delete {
    padding: 8px 16px;
    background: #fef2f2;
    color: #ef4444;
    border: 1px solid #fecaca;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-delete:hover {
    background: #ef4444;
    color: white;
}

.post-content-full {
    font-size: 18px;
    line-height: 1.7;
    color: #374151;
    margin-bottom: 24px;
    white-space: pre-wrap;
}

.post-stats-bar {
    display: flex;
    gap: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 16px;
    font-size: 14px;
    color: #6b7280;
}

.post-actions-bar {
    display: flex;
    gap: 12px;
}

.action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px;
    background: #f3f4f6;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    color: #4b5563;
    cursor: pointer;
    transition: all 0.2s;
}

.action-btn:hover {
    background: #e5e7eb;
}

.action-btn.liked {
    background: #fef2f2;
    color: #ef4444;
}

.comments-section-full {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.comments-section-full h3 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #1f2937;
}

.comment-form-full {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
}

.comment-avatar-full {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    flex-shrink: 0;
}

.comment-input-wrapper {
    flex: 1;
    display: flex;
    gap: 10px;
}

.comment-input-wrapper textarea {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    resize: vertical;
    min-height: 80px;
    font-family: inherit;
    font-size: 15px;
}

.comment-input-wrapper textarea:focus {
    outline: none;
    border-color: #667eea;
}

.btn-send {
    padding: 12px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-send:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.login-to-comment {
    text-align: center;
    padding: 20px;
    background: #f9fafb;
    border-radius: 12px;
    margin-bottom: 20px;
}

.login-to-comment a {
    color: #667eea;
    text-decoration: none;
}

.comments-list-full {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.comment-item {
    display: flex;
    gap: 12px;
}

.comment-body {
    flex: 1;
    background: #f9fafb;
    padding: 12px 16px;
    border-radius: 16px;
}

.comment-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}

.comment-author-name {
    font-weight: 600;
    font-size: 14px;
    color: #1f2937;
    text-decoration: none;
}

.comment-author-name:hover {
    color: #667eea;
}

.comment-time {
    font-size: 12px;
    color: #6b7280;
}

.comment-text {
    font-size: 15px;
    color: #374151;
    line-height: 1.5;
}

.no-comments {
    text-align: center;
    padding: 40px;
    color: #6b7280;
}

@media (max-width: 600px) {
    .comment-input-wrapper {
        flex-direction: column;
    }
    
    .btn-send {
        width: 100%;
    }
}
</style>

<script>
function addCommentToDOM(comment) {
    const list = document.getElementById('comments-list');
    const div = document.createElement('div');
    div.className = 'comment-item';
    div.setAttribute('data-comment-id', comment.id);
    div.innerHTML = `
        <img src="${comment.author?.avatar || 'https://api.dicebear.com/7.x/avataaars/svg?seed=' + comment.author?.id}" class="comment-avatar-full">
        <div class="comment-body">
            <div class="comment-header">
                <a href="/profile/${comment.author?.id}" class="comment-author-name">${comment.author?.username || 'Аноним'}</a>
                <span class="comment-time">${comment.timeAgo || 'только что'}</span>
            </div>
            <p class="comment-text">${comment.content}</p>
        </div>
    `;
    list.insertBefore(div, list.firstChild);
}
</script>

