<?php

use yii\helpers\Html;
use yii\helpers\Url;

if (!isset($post)) return;

$avatar = $post->user->getAvatarUrl();
$username = Html::encode($post->user->username);
$isLiked = !Yii::$app->user->isGuest && $post->isLikedBy(Yii::$app->user->id);
$isSaved = !Yii::$app->user->isGuest && $post->isSavedBy(Yii::$app->user->id);
$isReposted = !Yii::$app->user->isGuest && $post->isRepostedBy(Yii::$app->user->id);
$timeAgo = $post->getTimeAgo();
$imageUrls = $post->getImageUrls();
$imagesCount = count($imageUrls);
$currentUser = Yii::$app->user->identity;
$currentAvatar = $currentUser ? $currentUser->getAvatarUrl() : '';
?>

<div class="post-modal__header">
    <div class="post-modal__author">
        <img src="<?= Html::encode($avatar) ?>" class="post-modal__avatar" alt="<?= $username ?>">
        <div>
            <div class="post-modal__name"><?= $username ?></div>
            <div class="post-modal__time"><?= Html::encode($timeAgo) ?></div>
        </div>
    </div>
    <div class="post-modal__actions">
        <button class="action-btn action-btn--like <?= $isLiked ? 'is-active' : '' ?>" data-post-id="<?= $post->id ?>">
            <span><?= $isLiked ? '❤️' : '🤍' ?></span>
            <span><?= number_format($post->likes_count) ?></span>
        </button>
        <button class="action-btn action-btn--save <?= $isSaved ? 'is-active' : '' ?>" data-post-id="<?= $post->id ?>">
            <span><?= $isSaved ? '🔖' : '📌' ?></span>
        </button>
        <button class="action-btn action-btn--repost <?= $isReposted ? 'is-active' : '' ?>" data-post-id="<?= $post->id ?>">
            <span>🔄</span>
            <span><?= number_format($post->getRepostsCount()) ?></span>
        </button>
    </div>
</div>

<div class="post-modal__body">
    <div class="post-modal__text"><?= nl2br(Html::encode($post->content)) ?></div>
    
    <?php if ($imageUrls): ?>
        <div class="post-modal__gallery gallery gallery--<?= $imagesCount > 1 ? 'multi' : 'single' ?>">
            <?php foreach ($imageUrls as $index => $url): ?>
                <div class="gallery__item" onclick="openImageFullscreen('<?= Html::encode($url) ?>', <?= $imagesCount ?>, <?= $index ?>)">
                    <img src="<?= Html::encode($url) ?>" alt="<?= Yii::t('app','Изображение') ?> <?= $index + 1 ?>" loading="lazy">
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($post->poll): ?>
        <div class="post-modal__poll">
            <?= $this->render('/poll/_poll', ['poll' => $post->poll, 'postId' => $post->id]) ?>
        </div>
    <?php endif; ?>
</div>

<div class="post-modal__comments">
    <div class="comments-header">
        <span>💬 <?= Yii::t('app','Комментарии') ?></span>
        <span class="comments-header__count"><?= number_format($post->comments_count) ?></span>
    </div>
    
    <div id="modal-comments-list" class="comments-list">
        <?php foreach ($post->comments as $comment): ?>
            <div class="comment-item" data-comment-id="<?= $comment->id ?>">
                <img class="comment-avatar" src="<?= Html::encode($comment->user->getAvatarUrl()) ?>" alt="<?= Html::encode($comment->user->username) ?>">
                <div class="comment-content">
                    <div class="comment-header">
                        <div class="comment-author-info">
                            <span class="comment-author"><?= Html::encode($comment->user->username) ?></span>
                            <span class="comment-time"><?= $comment->getTimeAgo() ?></span>
                            <?php if ($comment->updated_at > $comment->created_at): ?>
                                <span class="edited-mark"><?= Yii::t('app','(ред.)') ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->id == $comment->user_id): ?>
                            <div class="comment-actions">
                                <button class="btn-edit-comment" data-comment-id="<?= $comment->id ?>" data-post-id="<?= $post->id ?>" title="<?= Yii::t('app','Редактировать') ?>">✏️</button>
                                <button class="btn-delete-comment" data-comment-id="<?= $comment->id ?>" data-post-id="<?= $post->id ?>" title="<?= Yii::t('app','Удалить') ?>">🗑️</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="comment-text"><?= nl2br(Html::encode($comment->content)) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($post->comments)): ?>
            <div class="empty-comments">
                <div class="empty-comments-icon">💬</div>
                <p><?= Yii::t('app','Нет комментариев') ?></p>
                <p class="empty-hint"><?= Yii::t('app','Будьте первым, кто оставит комментарий!') ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (!Yii::$app->user->isGuest): ?>
        <div class="comment-form" data-post-id="<?= $post->id ?>">
            <img src="<?= Html::encode($currentAvatar) ?>" class="comment-avatar" alt="<?= Yii::t('app','Ваш аватар') ?>">
            <div class="comment-form__wrapper">
                <textarea class="comment-form__input modal-comment-input" placeholder="<?= Yii::t('app','Написать комментарий...') ?>" rows="1" maxlength="1000"></textarea>
                <div class="comment-form__footer">
                    <span class="comment-form__counter">0</span>
                    <span class="comment-form__counter-max">/1000</span>
                    <button class="comment-form__btn modal-comment-submit" data-post-id="<?= $post->id ?>">📤 <?= Yii::t('app','Отправить') ?></button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="login-to-comment">
            <p><a href="<?= Url::to(['/site/login']) ?>"><?= Yii::t('app','Войдите') ?></a><?= Yii::t('app', ', чтобы оставить комментарий') ?></p>
        </div>
    <?php endif; ?>
</div>