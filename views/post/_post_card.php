<?php

use yii\helpers\Url;
use yii\helpers\Html;

$avatar = $post->user->getAvatarUrl();
$username = Html::encode($post->user->username);
$isLiked = !Yii::$app->user->isGuest && $post->isLikedBy(Yii::$app->user->id);
$isSaved = !Yii::$app->user->isGuest && $post->isSavedBy(Yii::$app->user->id);
$isReposted = !Yii::$app->user->isGuest && $post->isRepostedBy(Yii::$app->user->id);
$likesCount = number_format($post->getLikes()->count());
$commentsCount = number_format($post->getComments()->count());
$repostsCount = number_format($post->getRepostsCount());
$timeAgo = $post->getTimeAgo();
$imageUrls = $post->getImageUrls();
$hasImages = !empty($imageUrls);

?>

<div class="post-card" data-post-id="<?= $post->id ?>">
    <div class="post-header">
        <div class="post-author">
            <a href="<?= Url::to(['profile/view', 'id' => $post->user_id]) ?>" class="author-avatar-link">
                <img class="author-avatar" src="<?= Html::encode($avatar) ?>" alt="<?= Yii::t('app', 'Аватар {username}', ['username' => $username]) ?>">
            </a>
            <div class="author-info">
                <a href="<?= Url::to(['profile/view', 'id' => $post->user_id]) ?>" class="author-name-link">
                    <span class="author-name"><?= $username ?></span>
                </a>
                <span class="post-time" data-timestamp="<?= $post->created_at ?>"><?= Html::encode($timeAgo) ?></span>
            </div>
        </div>
        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->id == $post->user_id): ?>
            <button class="btn-delete-post" onclick="deletePost(<?= $post->id ?>)" title="<?= Yii::t('app', 'Удалить пост') ?>">🗑️</button>
        <?php endif; ?>
    </div>
    
    <div class="post-content"><?= nl2br(Html::encode($post->content)) ?></div>
    
    <?php if ($hasImages): ?>
        <div class="post-image <?= count($imageUrls) > 1 ? 'multiple' : '' ?>">
            <?php foreach ($imageUrls as $index => $imageUrl): ?>
                <div class="post-image-item" data-image-index="<?= $index ?>" data-image-url="<?= Html::encode($imageUrl) ?>">
                    <img src="<?= Html::encode($imageUrl) ?>" alt="<?= Yii::t('app', 'Изображение поста {n}', ['n' => $index + 1]) ?>" class="clickable-image" loading="lazy" onclick="openImageFullscreen('<?= Html::encode($imageUrl) ?>', <?= count($imageUrls) ?>, <?= $index ?>)">
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($post->poll): ?>
        <?= $this->render('/poll/_poll', ['poll' => $post->poll, 'postId' => $post->id]) ?>
    <?php endif; ?>
    
    <div class="post-actions-bar">
        <button class="post-action btn-like <?= $isLiked ? 'liked' : '' ?>" data-post-id="<?= $post->id ?>">
            <span class="action-icon"><?= $isLiked ? '❤️' : '🤍' ?></span>
            <span class="action-count"><?= $likesCount ?></span>
        </button>
        <button class="post-action btn-comment-toggle" onclick="openPostModal(<?= $post->id ?>)" title="<?= Yii::t('app', 'Комментарии') ?>">
            💬 <span class="action-count"><?= $commentsCount ?></span>
        </button>
        <button class="post-action btn-save <?= $isSaved ? 'saved' : '' ?>" data-post-id="<?= $post->id ?>" title="<?= $isSaved ? Yii::t('app', 'Удалить из сохранённых') : Yii::t('app', 'Сохранить') ?>">
            <span class="action-icon"><?= $isSaved ? '🔖' : '📌' ?></span>
        </button>
        <button class="post-action btn-repost <?= $isReposted ? 'reposted' : '' ?>" data-post-id="<?= $post->id ?>" title="<?= $isReposted ? Yii::t('app', 'Отменить репост') : Yii::t('app', 'Сделать репост') ?>">
            🔄 <span class="action-count"><?= $repostsCount ?></span>
        </button>
    </div>
</div>
