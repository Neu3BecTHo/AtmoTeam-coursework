<?php

use app\assets\AppAsset;
use yii\helpers\Html;

AppAsset::register($this);

$this->params['breadcrumbs'][] = $this->title;
$this->title = 'Истории';
$this->registerCssFile('@web/css/story.css');
$this->registerJsFile('@web/js/common.js');
$this->registerJsFile('@web/js/story.js', ['position' => \yii\web\View::POS_END]);
?>

<div class="story-container">
    <div class="story-header">
        <h1 class="story-title">📸 Истории</h1>
        <p class="story-subtitle">Истории ваших подписок (24 часа)</p>
        <button class="btn-create-story" onclick="showStoryUpload()">
            + Добавить историю
        </button>
    </div>

    <div class="story-content">
        <?php if (empty($storiesByUser)): ?>
            <div class="empty-state">
                <div class="empty-icon">📖</div>
                <h3>Нет активных историй</h3>
                <p>Истории появляются здесь, когда ваши подписки делятся моментами из жизни</p>
            </div>
        <?php else: ?>
            <div class="stories-scroll-wrapper">
                <button class="scroll-btn scroll-btn-left" onclick="scrollStories(-1)" aria-label="Прокрутить влево">
                    ‹
                </button>
                <div class="stories-grid" id="stories-grid">
                    <?php foreach ($storiesByUser as $userId => $userStories): ?>
                        <?php $firstStory = $userStories[0]; $author = $firstStory->user; ?>
                        <div class="user-stories" data-user-id="<?= $userId ?>">
                            <div class="user-header">
                                <a href="<?= \yii\helpers\Url::to(['/profile/view', 'id' => $userId]) ?>" class="user-avatar-link">
                                    <img class="user-avatar" 
                                         src="<?= $author ? ($author->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $author->id) : '' ?>" 
                                         alt="<?= $author ? \yii\helpers\Html::encode($author->username) : '' ?>">
                                </a>
                                <div class="user-info">
                                    <div class="username"><?= $author ? \yii\helpers\Html::encode($author->username) : '' ?></div>
                                    <div class="stories-count"><?= count($userStories) ?> историй</div>
                                </div>
                            </div>
                            <div class="stories-list">
                                <?php foreach ($userStories as $story): ?>
                                    <div class="story-item" data-story-id="<?= $story->id ?>">
                                        <div class="story-image-container">
                                            <img class="story-image" 
                                                 src="<?= $story->getImageUrl() ?>" 
                                                 alt="История">
                                            <div class="story-time-left"><?= $story->getTimeLeft() ?></div>
                                            <?php if ($story->caption): ?>
                                                <div class="story-caption"><?= \yii\helpers\Html::encode($story->caption) ?></div>
                                            <?php endif; ?>
                                            <?php if (!Yii::$app->user->isGuest && $story->user_id == Yii::$app->user->id): ?>
                                                <button class="story-delete-btn" onclick="event.stopPropagation(); deleteStory(<?= $story->id ?>)" title="Удалить">🗑️</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="scroll-btn scroll-btn-right" onclick="scrollStories(1)" aria-label="Прокрутить вправо">
                    ›
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Модальное окно загрузки истории -->
<div class="story-upload-modal" id="story-upload-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>📸 Добавить историю</h3>
            <button class="btn-close" onclick="hideStoryUpload()">×</button>
        </div>
        <div class="modal-body">
            <div class="upload-area" id="upload-area">
                <div class="upload-placeholder">
                    <div class="upload-icon">📸</div>
                    <p>Нажмите для выбора изображения</p>
                    <p class="upload-hint">или перетащите файл сюда</p>
                </div>
                <input type="file" id="story-image-input" accept="image/*" style="display: none;">
                <img id="preview-image" style="display: none;">
            </div>
            <div class="story-form" id="story-form" style="display: none;">
                <textarea id="story-caption" 
                          placeholder="Добавьте подпись (необязательно)" 
                          maxlength="200"
                          rows="3"></textarea>
                <div class="form-actions">
                    <button class="btn-cancel" onclick="hideStoryUpload()">Отмена</button>
                    <button class="btn-upload" onclick="uploadStory()">Опубликовать</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно просмотра истории -->
<div class="story-view-modal" id="story-view-modal">
    <div class="modal-content">
        <div class="modal-header">
            <button class="btn-close" onclick="hideStoryView()">×</button>
        </div>
        <div class="modal-body">
            <div class="story-view-content" id="story-view-content">
                <!-- Контент загружается через JS -->
            </div>
        </div>
        <!-- Кнопка выхода из полноэкранного режима -->
        <button class="fullscreen-exit-btn" onclick="exitFullscreenStory()" style="display: none;">✕ Выйти</button>
    </div>
</div>

<script>
window.currentUserId = <?= Yii::$app->user->id ?>;
</script>
