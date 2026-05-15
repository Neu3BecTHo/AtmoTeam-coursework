<?php
use yii\helpers\Html;
use yii\helpers\Url;

$currentUserId = Yii::$app->user->id;
$currentUser = Yii::$app->user->identity;
$currentAvatar = $currentUser ? $currentUser->getAvatarUrl() : 'https://api.dicebear.com/7.x/avataaars/svg?seed=0';
?>

<div class="create-post-card">
    <div class="post-form">
        <div class="user-avatar">
            <img src="<?= $currentAvatar ?>" alt="avatar">
        </div>
        <div class="post-input-area">
            <div class="post-input-wrapper">
                <textarea id="post-content" placeholder="Что у вас нового?" maxlength="2000" class="post-input-textarea"></textarea>
                <div class="post-input-focus-border"></div>
            </div>
            
            <!-- Опрос -->
            <div id="poll-container" class="poll-container" style="display: none;">
                <div class="poll-header">
                    <input type="text" id="poll-question" placeholder="Вопрос опроса..." maxlength="255" class="poll-question-input">
                    <button type="button" class="btn-remove-poll" onclick="removePoll()">✕</button>
                </div>
                <div class="poll-options-container" id="poll-options">
                    <div class="poll-option-input">
                        <input type="text" placeholder="Вариант ответа 1..." maxlength="100" class="option-input">
                        <button type="button" class="btn-remove-option" onclick="removeOption(this)">✕</button>
                    </div>
                    <div class="poll-option-input">
                        <input type="text" placeholder="Вариант ответа 2..." maxlength="100" class="option-input">
                        <button type="button" class="btn-remove-option" onclick="removeOption(this)">✕</button>
                    </div>
                </div>
                <div class="poll-actions">
                    <button type="button" class="btn-add-option" onclick="addPollOption()">+ Добавить вариант</button>
                    <label class="poll-multiple-label">
                        <input type="checkbox" id="poll-multiple" class="poll-multiple-checkbox">
                        <span>Разрешить несколько вариантов</span>
                    </label>
                </div>
            </div>
            
            <!-- Предпросмотр изображения -->
            <div id="image-preview-container" class="image-preview-container" style="display: none;">
                <img id="image-preview" src="" alt="Preview">
                <button type="button" class="btn-remove-image" onclick="removeSelectedImage()">✕</button>
            </div>
            
            <div class="post-actions">
                <div class="post-actions-left">
                    <label for="post-image" class="btn-attach-image" title="Прикрепить изображение">
                        📷
                    </label>
                    <input type="file" id="post-image" name="image" accept="image/*" style="display: none;">
                    <button type="button" class="btn-add-poll" onclick="addPoll()" title="Добавить опрос">
                        📊
                    </button>
                    <span class="char-count" id="char-count">0/2000</span>
                </div>
                <button id="btn-publish" class="btn-publish" disabled>
                    Опубликовать
                </button>
            </div>
        </div>
    </div>
</div>
