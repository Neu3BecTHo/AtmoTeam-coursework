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
                    <img src="<?= Html::encode($url) ?>" alt="Изображение <?= $index + 1 ?>" loading="lazy">
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
        <span>💬 Комментарии</span>
        <span class="comments-header__count"><?= number_format($post->comments_count) ?></span>
    </div>
    <div id="modal-comments-list" class="comments-list"></div>
    <?= $this->render('/comment/_form', ['postId' => $post->id]) ?>
</div>

<style>
    /* ============================================
   Post Modal Styles
   ============================================ */

/* Modal Base */
#post-modal .post-modal-content {
    max-width: 600px;
    width: 90%;
    background: var(--surface-0);
    border-radius: var(--radius-2xl);
}

/* Header */
.post-modal__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: var(--space-4);
    padding: var(--space-4) var(--space-6);
    border-bottom: 1px solid var(--border-primary);
    background: var(--surface-50);
    flex-wrap: wrap;
}

.post-modal__author {
    display: flex;
    gap: var(--space-3);
    align-items: center;
}

.post-modal__avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}

.post-modal__name {
    font-weight: var(--font-semibold);
    font-size: var(--text-base);
    color: var(--text-primary);
}

.post-modal__time {
    font-size: var(--text-xs);
    color: var(--text-tertiary);
}

/* Actions */
.post-modal__actions {
    display: flex;
    gap: var(--space-2);
}

.action-btn {
    display: flex;
    align-items: center;
    gap: var(--space-1);
    padding: var(--space-1) var(--space-3);
    background: var(--surface-100);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-lg);
    cursor: pointer;
    transition: all var(--transition-fast);
    font-size: var(--text-sm);
}

.action-btn:hover {
    background: var(--surface-200);
    transform: translateY(-1px);
}

.action-btn--like.is-active {
    background: var(--error-light);
    color: var(--error);
    border-color: var(--error);
}

.action-btn--save.is-active {
    background: var(--success-lighter);
    color: var(--success);
    border-color: var(--success);
}

.action-btn--repost.is-active {
    background: var(--info-lighter);
    color: var(--info);
    border-color: var(--info);
}

/* Body */
.post-modal__body {
    padding: var(--space-6);
}

.post-modal__text {
    font-size: var(--text-base);
    line-height: var(--leading-relaxed);
    color: var(--text-secondary);
    margin-bottom: var(--space-4);
    white-space: pre-wrap;
    word-wrap: break-word;
}

/* Gallery */
.post-modal__gallery {
    display: flex;
    gap: var(--space-2);
    flex-wrap: wrap;
    margin-bottom: var(--space-4);
}

.gallery--single .gallery__item {
    flex: 1;
}

.gallery--single .gallery__item img {
    max-height: 400px;
    object-fit: contain;
}

.gallery--multi .gallery__item {
    flex: 1;
    min-width: 120px;
    max-width: calc(50% - var(--space-2));
}

.gallery__item {
    cursor: pointer;
    border-radius: var(--radius-lg);
    overflow: hidden;
    background: var(--surface-100);
}

.gallery__item img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    transition: transform var(--transition-fast);
}

.gallery__item:hover img {
    transform: scale(1.05);
}

/* Poll */
.post-modal__poll {
    margin-top: var(--space-4);
    padding-top: var(--space-4);
    border-top: 1px solid var(--border-primary);
}

/* Comments */
.post-modal__comments {
    margin-top: var(--space-4);
    padding: var(--space-4) var(--space-6);
    border-top: 1px solid var(--border-primary);
    background: var(--surface-50);
}

.comments-header {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    font-weight: var(--font-semibold);
    font-size: var(--text-lg);
    color: var(--text-primary);
    margin-bottom: var(--space-4);
}

.comments-header__count {
    font-size: var(--text-xs);
    font-weight: var(--font-normal);
    background: var(--surface-100);
    padding: var(--space-1) var(--space-2);
    border-radius: var(--radius-full);
    color: var(--text-tertiary);
}

/* Comments List */
.comments-list {
    max-height: 300px;
    overflow-y: auto;
    margin-bottom: var(--space-4);
}

/* Loading Spinner */
.loading-spinner {
    text-align: center;
    padding: var(--space-8);
    color: var(--text-tertiary);
}

.spinner {
    width: 48px;
    height: 48px;
    position: relative;
    margin: 0 auto var(--space-4);
}

.spinner-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 3px solid transparent;
    animation: spin-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
}

.spinner-ring:nth-child(1) {
    border-top-color: var(--primary-500);
    animation-delay: -0.45s;
}

.spinner-ring:nth-child(2) {
    border-right-color: var(--primary-400);
    animation-delay: -0.3s;
}

.spinner-ring:nth-child(3) {
    border-bottom-color: var(--primary-600);
    animation-delay: -0.15s;
}

@keyframes spin-ring {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .post-modal__header {
        padding: var(--space-3) var(--space-4);
    }
    
    .post-modal__body {
        padding: var(--space-4);
    }
    
    .post-modal__comments {
        padding: var(--space-3) var(--space-4);
    }
    
    .post-modal__avatar {
        width: 40px;
        height: 40px;
    }
    
    .gallery--multi .gallery__item {
        min-width: 100px;
    }
}

@media (max-width: 480px) {
    .post-modal__header {
        flex-direction: column;
    }
    
    .post-modal__actions {
        width: 100%;
        justify-content: flex-end;
    }
    
    .gallery--multi .gallery__item {
        min-width: calc(50% - var(--space-2));
    }
    
    .gallery__item img {
        height: 150px;
    }
    
    .comments-list {
        max-height: 250px;
    }
}
</style>