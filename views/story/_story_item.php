<?php
/**
 * @var \app\models\Story $story
 * @var bool $showDeleteButton
 * @var string|null $onClick
 */

use yii\helpers\Html;
use yii\helpers\Url;

$onClickAttr = $onClick ?: "viewStory({$story->id})";
$storyImageUrl = $story->getImageUrl();
$timeLeft = $story->getTimeLeft();
$caption = $story->caption;
$storyId = $story->id;
$username = $story->user->username ?? Yii::t('app','Пользователь');
?>

<div class="story-item" data-story-id="<?= $storyId ?>" data-user-id="<?= $story->user_id ?>">
    <div class="story-image-container">
        <img class="story-image" src="<?= Html::encode($storyImageUrl) ?>"
            alt="<?= Yii::t('app','История от {username}', ['username' => Html::encode($username)]) ?>" loading="lazy" onclick="viewStory(<?= $storyId ?>)">

        <div class="story-time-left" title="<?= Yii::t('app','Время до истечения') ?>">
            ⏱️ <?= Html::encode($timeLeft) ?>
        </div>

        <?php if ($caption): ?>
            <div class="story-caption" title="<?= Html::encode($caption) ?>">
                <?= Html::encode($caption) ?>
            </div>
        <?php endif; ?>

        <div class="story-overlay-buttons">
            <button class="story-view-btn" onclick="event.stopPropagation(); viewStory(<?= $storyId ?>)"
                title="<?= Yii::t('app','Просмотр истории') ?>">
                👁️
            </button>

            <?php if ($showDeleteButton): ?>
                <button class="story-delete-btn" onclick="event.stopPropagation(); deleteStory(<?= $storyId ?>)"
                    title="<?= Yii::t('app','Удалить историю') ?>">
                    🗑️
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>