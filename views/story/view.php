<?php
use yii\helpers\Html;

$author = $story->user;
$avatar = $author ? ($author->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $author->id) : '';
?>

<div class="story-view-wrapper">
    <div class="story-view-header">
        <img class="story-view-avatar" src="<?= $avatar ?>" alt="<?= $author ? Html::encode($author->username) : '' ?>">
        <div class="story-view-user">
            <div class="story-view-username"><?= $author ? Html::encode($author->username) : '' ?></div>
            <div class="story-view-time"><?= $story->toArray()['timeAgo'] ?? 'только что' ?></div>
        </div>
    </div>
    
    <img class="story-view-image" src="<?= $story->getImageUrl() ?>" alt="История">
    
    <?php if ($story->caption): ?>
        <div class="story-view-caption"><?= Html::encode($story->caption) ?></div>
    <?php endif; ?>
    
    <div class="story-view-meta">
        <span>⏰ <?= $story->getTimeLeft() ?></span>
        <?php if ($story->user_id === Yii::$app->user->id): ?>
            <button class="btn-delete-story" onclick="deleteStory(<?= $story->id ?>)">🗑 Удалить</button>
        <?php endif; ?>
    </div>
</div>
