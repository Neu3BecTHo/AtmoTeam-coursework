<?php

use app\assets\AppAsset;
use yii\helpers\Html;
use yii\helpers\Url;

AppAsset::register($this);

$this->title = 'Истории';
$this->registerCssFile('@web/css/story.css');
$this->registerJsFile('@web/js/story.js', ['position' => \yii\web\View::POS_END]);

$currentUserId = Yii::$app->user->id;
$isGuest = Yii::$app->user->isGuest;
$storiesByUser = $storiesByUser ?? [];

?>

<div class="story-page">
    <div class="story-header">
        <div class="story-header-info">
            <h1 class="story-title">📸 Истории</h1>
            <p class="story-subtitle">Истории ваших подписок (24 часа)</p>
        </div>
        <?php if (!$isGuest): ?>
            <button class="btn-create-story" onclick="showStoryUpload()">
                ✨ Добавить историю
            </button>
        <?php endif; ?>
    </div>

    <div class="story-content">
        <?php if ($isGuest): ?>
            <div class="guest-state">
                <div class="guest-icon">🔒</div>
                <h3>Войдите, чтобы смотреть истории</h3>
                <p>Истории появляются здесь, когда ваши подписки делятся моментами из жизни</p>
                <div class="guest-actions">
                    <a href="<?= Url::to(['/site/login']) ?>" class="btn btn-primary">Войти</a>
                    <a href="<?= Url::to(['/site/register']) ?>" class="btn btn-secondary">Регистрация</a>
                </div>
            </div>
        <?php elseif (empty($storiesByUser)): ?>
            <div class="empty-state">
                <div class="empty-icon">📖</div>
                <h3>Нет активных историй</h3>
                <p>Истории появляются здесь, когда ваши подписки делятся моментами из жизни</p>
                <button class="btn-create-story" onclick="showStoryUpload()">
                    ✨ Создать первую историю
                </button>
            </div>
        <?php else: ?>
            <div class="stories-scroll-wrapper">
                <button class="scroll-btn scroll-btn-left" onclick="scrollStories(-1)" aria-label="Прокрутить влево">
                    ‹
                </button>
                <div class="stories-grid" id="stories-grid">
                    <?php foreach ($storiesByUser as $userId => $userStories): ?>
                        <?php $firstStory = $userStories[0]; $author = $firstStory->user; ?>
                        <?= $this->render('_user_stories', [
                            'userId' => $userId,
                            'userStories' => $userStories,
                            'author' => $author,
                            'context' => 'story',
                        ]) ?>
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
            <h3 class="modal-title">📸 Добавить историю</h3>
            <button class="modal-close" onclick="hideStoryUpload()" aria-label="Закрыть">&times;</button>
        </div>
        <div class="modal-body">
            <div class="upload-area" id="upload-area">
                <div class="upload-placeholder">
                    <div class="upload-icon">📸</div>
                    <p>Нажмите для выбора изображения</p>
                    <p class="upload-hint">или перетащите файл сюда</p>
                </div>
                <input type="file" id="story-image-input" accept="image/*" style="display: none;">
                <img id="preview-image" class="preview-image" style="display: none;">
            </div>
            <div class="story-form" id="story-form" style="display: none;">
                <textarea id="story-caption" 
                          class="story-caption-input"
                          placeholder="Добавьте подпись (необязательно)" 
                          maxlength="200"
                          rows="3"></textarea>
                <div class="story-info">
                    <span class="info-icon">ℹ️</span>
                    <span class="info-text">История будет доступна 24 часа</span>
                </div>
                <div class="form-actions">
                    <button class="btn-secondary" onclick="hideStoryUpload()">❌ Отмена</button>
                    <button class="btn-primary" onclick="uploadStory()">📤 Опубликовать</button>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
window.currentUserId = <?= (int) $currentUserId ?>;
</script>