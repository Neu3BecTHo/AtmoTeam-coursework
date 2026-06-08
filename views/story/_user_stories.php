<?php
/**
 * @var int $userId
 * @var \app\models\Story[] $userStories
 * @var \app\models\User $author
 * @var string $context 'feed' или 'story'
 */

use yii\helpers\Html;
use yii\helpers\Url;

$currentUserId = Yii::$app->user->id;
$isGuest = Yii::$app->user->isGuest;
$authorUsername = $author ? Html::encode($author->username) : '';
$authorAvatar = $author ? ($author->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $author->id) : '';
$storiesCount = count($userStories);

?>

<div class="user-stories" data-user-id="<?= $userId ?>" data-stories-count="<?= $storiesCount ?>">
    <div class="user-header">
        <a href="<?= Url::to(['/profile/view', 'id' => $userId]) ?>" class="user-avatar-link" title="<?= $authorUsername ?>">
            <img class="user-avatar"
                 src="<?= Html::encode($authorAvatar) ?>"
                 alt="<?= $authorUsername ?>"
                 loading="lazy">
        </a>
        <div class="user-info">
            <div class="username"><?= $authorUsername ?></div>
            <div class="stories-count">
                📸 <?= number_format($storiesCount) ?> <?= $storiesCount === 1 ? 'история' : ($storiesCount <= 4 ? 'истории' : 'историй') ?>
            </div>
        </div>
    </div>
    <div class="stories-list">
        <?php foreach ($userStories as $story): 
            $isOwner = !$isGuest && $story->user_id == $currentUserId;
            $isOwner = !$isGuest && (int) $story->user_id === (int) $currentUserId;
            $onClick = $context === 'feed' ? "toggleFullscreenStory({$story->id})" : "viewStory({$story->id})";
            ?>
            <?= $this->render('_story_item', [
            'story' => $story,
            'showDeleteButton' => $isOwner,
            'onClick' => $onClick,
        ]) ?>
        <?php endforeach; ?>
    </div>
</div>