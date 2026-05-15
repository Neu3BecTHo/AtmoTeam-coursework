<?php
use yii\helpers\Url;
use yii\helpers\Html;

$avatar = $post->user->getAvatarUrl();
$isLiked = !Yii::$app->user->isGuest && $post->isLikedBy(Yii::$app->user->id);
$isSaved = !Yii::$app->user->isGuest && $post->isSavedBy(Yii::$app->user->id);
$isReposted = !Yii::$app->user->isGuest && $post->isRepostedBy(Yii::$app->user->id);
?>

<div class="post-card" data-post-id="<?= $post->id ?>">
    <div class="post-header">
        <div class="post-author">
            <a href="<?= Url::to(['profile/view', 'id' => $post->user_id]) ?>" class="author-avatar-link">
                <img class="author-avatar" src="<?= $avatar ?>" alt="avatar">
            </a>
            <div class="author-info">
                <a href="<?= Url::to(['profile/view', 'id' => $post->user_id]) ?>" class="author-name-link">
                    <span class="author-name"><?= Html::encode($post->user->username) ?></span>
                </a>
                <span class="post-time"><?= $post->getTimeAgo() ?></span>
            </div>
        </div>
        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->id == $post->user_id): ?>
            <button class="btn-delete-post" onclick="deletePost(<?= $post->id ?>)" title="Удалить">
                🗑️
            </button>
        <?php endif; ?>
    </div>
    
    <div class="post-content"><?= Html::encode($post->content) ?></div>
    
    <?php if ($post->image): ?>
    <div class="post-image">
        <img src="<?= $post->getImageUrl() ?>" alt="Post image">
    </div>
    <?php else: ?>
    <div class="post-image" style="display: none;">
        <img src="" alt="Post image">
    </div>
    <?php endif; ?>
    
    <?php if ($post->poll): ?>
        <?= $this->render('/poll/_poll', ['poll' => $post->poll, 'postId' => $post->id]) ?>
    <?php else: ?>
        <div class="post-poll" style="display: none;">
            <!-- Опрос будет добавлен через JS -->
        </div>
    <?php endif; ?>
    
<div class="post-stats">
        <span class="likes-count"><?= $post->getLikes()->count() ?> лайков</span>
        <span class="comments-count"><?= $post->getComments()->count() ?> комментариев</span>
        <span class="reposts-count"><?= $post->getRepostsCount() ?> репостов</span>
    </div>
    
    <div class="post-actions-bar">
        <button class="post-action btn-like <?= $isLiked ? 'liked' : '' ?>" title="Нравится" data-post-id="<?= $post->id ?>">
            <svg class="action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
        </button>
        <button class="post-action btn-comment-toggle" title="Комментарии" onclick="toggleComments(<?= $post->id ?>)">
            <svg class="action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
            </svg>
        </button>
        <button class="post-action btn-save <?= $isSaved ? 'saved' : '' ?>" title="Сохранить" data-post-id="<?= $post->id ?>">
            <svg class="action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
            </svg>
        </button>
        <button class="post-action btn-repost <?= $isReposted ? 'reposted' : '' ?>" title="Репост" data-post-id="<?= $post->id ?>">
            <svg class="action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="17 1 21 5 17 9"></polyline>
                <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                <polyline points="7 23 3 19 7 15"></polyline>
                <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
            </svg>
        </button>
    </div>
</div>
