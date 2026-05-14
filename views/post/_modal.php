<?php
use yii\helpers\Html;
use yii\helpers\Url;

if (!isset($post)) {
    return;
}

$avatar = $post->user->getAvatarUrl();
$isLiked = !Yii::$app->user->isGuest && $post->isLikedBy(Yii::$app->user->id);
$isSaved = !Yii::$app->user->isGuest && $post->isSavedBy(Yii::$app->user->id);
$isReposted = !Yii::$app->user->isGuest && $post->isRepostedBy(Yii::$app->user->id);
?>

<div class="post-modal-header">
    <img src="<?= $avatar ?>" class="post-modal-avatar" alt="avatar">
    <div>
        <div class="post-modal-author"><?= Html::encode($post->user->username) ?></div>
        <div class="post-modal-time"><?= $post->timeAgo ?></div>
    </div>
</div>
<div class="post-modal-text"><?= Html::encode($post->content) ?></div>
<?php if ($post->image): ?>
    <img src="<?= $post->getImageUrl() ?>" class="post-modal-image" alt="Post image">
<?php endif; ?>
<?php if ($post->poll): ?>
    <?= $this->render('/poll/_poll', ['poll' => $post->poll, 'postId' => $post->id]) ?>
<?php endif; ?>
<div class="post-modal-actions">
    <button class="post-action btn-like <?= $isLiked ? 'liked' : '' ?>" onclick="handleLike(<?= $post->id ?>)" title="Нравится">
        <svg class="action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
        </svg>
    </button>
</div>
<div class="post-modal-comments">
    <div class="post-modal-comments-header">Комментарии</div>
    <div id="modal-comments-list"></div>
    <?= $this->render('/comment/_form', ['postId' => $post->id]) ?>
</div>
