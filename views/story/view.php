<?php

use yii\helpers\Html;
use yii\helpers\Url;

$author = $story->user;
$authorUsername = $author ? Html::encode($author->username) : '';
$authorAvatar = $author ? ($author->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $author->id) : '';
$timeAgo = $story->toArray()['timeAgo'] ?? 'только что';
$caption = $story->caption;
$timeLeft = $story->getTimeLeft();
$storyId = $story->id;
$isOwner = !Yii::$app->user->isGuest && $story->user_id == Yii::$app->user->id;

?>

<div class="story-view-wrapper" data-story-id="<?= $storyId ?>">
    <div class="story-view-header">
        <a href="<?= Url::to(['/profile/view', 'id' => $story->user_id]) ?>" class="story-view-avatar-link">
            <img class="story-view-avatar" 
                 src="<?= Html::encode($authorAvatar) ?>" 
                 alt="<?= $authorUsername ?>"
                 loading="lazy">
        </a>
        <div class="story-view-user">
            <a href="<?= Url::to(['/profile/view', 'id' => $story->user_id]) ?>" class="story-view-username-link">
                <div class="story-view-username"><?= $authorUsername ?></div>
            </a>
            <div class="story-view-time">🕐 <?= Html::encode($timeAgo) ?></div>
        </div>
    </div>
    
    <div class="story-view-image-container">
        <img class="story-view-image"
             src="<?= Html::encode($story->getImageUrl()) ?>"
             alt="История от <?= $authorUsername ?>"
             loading="lazy">
    </div>
    
    <?php if ($caption): ?>
        <div class="story-view-caption">
            <div class="caption-icon">💬</div>
            <div class="caption-text"><?= nl2br(Html::encode($caption)) ?></div>
        </div>
    <?php endif; ?>
    
    <div class="story-view-meta">
        <div class="meta-item">
            <span class="meta-icon">⏰</span>
            <span class="meta-text"><?= Html::encode($timeLeft) ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-icon">👁️</span>
            <span class="meta-text"><?= number_format($story->views_count ?? 0) ?> просмотров</span>
        </div>
        <?php if ($isOwner): ?>
            <button class="btn-delete-story" onclick="deleteStory(<?= $storyId ?>)">
                🗑️ Удалить
            </button>
        <?php endif; ?>
    </div>
</div>

<style>
.story-view-wrapper {
    padding: var(--space-4);
    display: flex;
    flex-direction: column;
    height: 100%;
    max-height: calc(95vh - 40px);
}

.story-view-header {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    margin-bottom: var(--space-4);
    flex-shrink: 0;
}

.story-view-avatar-link {
    flex-shrink: 0;
    transition: transform var(--transition-fast);
}

.story-view-avatar-link:hover {
    transform: scale(1.05);
}

.story-view-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--primary-500);
}

.story-view-user {
    flex: 1;
}

.story-view-username-link {
    text-decoration: none;
}

.story-view-username {
    font-weight: var(--font-bold);
    font-size: var(--text-base);
    color: var(--text-primary);
    transition: color var(--transition-fast);
}

.story-view-username-link:hover .story-view-username {
    color: var(--primary-600);
}

.story-view-time {
    font-size: var(--text-xs);
    color: var(--text-tertiary);
}

.story-view-image-container {
    position: relative;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 0;
    overflow: hidden;
    border-radius: var(--radius-lg);
    background: var(--surface-50);
    cursor: pointer;
    transition: transform var(--transition-fast);
}

.story-view-image-container:hover .image-overlay {
    opacity: 1;
}

.story-view-image {
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
    border-radius: var(--radius-lg);
    display: block;
}

.image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity var(--transition-fast);
    border-radius: var(--radius-lg);
}

.zoom-icon {
    font-size: 32px;
    color: white;
    background: rgba(0, 0, 0, 0.6);
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.story-view-caption {
    margin-top: var(--space-4);
    padding: var(--space-3) var(--space-4);
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.85) 0%, rgba(0, 0, 0, 0.75) 100%);
    color: white;
    border-radius: var(--radius-lg);
    backdrop-filter: blur(10px);
    display: flex;
    gap: var(--space-3);
    flex-shrink: 0;
}

.caption-icon {
    font-size: var(--text-xl);
    opacity: 0.7;
}

.caption-text {
    flex: 1;
    font-size: var(--text-sm);
    line-height: var(--leading-relaxed);
    word-wrap: break-word;
}

.story-view-meta {
    margin-top: var(--space-3);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: var(--space-4);
    flex-wrap: wrap;
    padding-top: var(--space-3);
    border-top: 1px solid var(--border-primary);
    flex-shrink: 0;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: var(--space-1);
    font-size: var(--text-sm);
    color: var(--text-tertiary);
}

.meta-icon {
    font-size: var(--text-sm);
}

.btn-delete-story {
    padding: var(--space-2) var(--space-4);
    background: linear-gradient(135deg, var(--error) 0%, #dc2626 100%);
    color: white;
    border: none;
    border-radius: var(--radius-lg);
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.btn-delete-story:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

@media (max-width: 768px) {
    .story-view-wrapper {
        padding: var(--space-3);
    }
    
    .story-view-avatar {
        width: 40px;
        height: 40px;
    }
    
    .story-view-username {
        font-size: var(--text-sm);
    }
    
    .zoom-icon {
        width: 36px;
        height: 36px;
        font-size: 24px;
    }
}
</style>