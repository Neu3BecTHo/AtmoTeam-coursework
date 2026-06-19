<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$currentUserId = Yii::$app->user->id;
$currentUser = Yii::$app->user->identity;
$currentAvatar = $currentUser ? $currentUser->getAvatarUrl() : 'https://api.dicebear.com/7.x/avataaars/svg?seed=0';

if (!isset($postModel)) {
    $postModel = new \app\models\Post();
    $postModel->scenario = 'create';
}
?>

<div class="create-post-card">
    <?php $form = ActiveForm::begin([
        'id' => 'create-post-form',
        'action' => ['/api/post/create'],
        'options' => ['enctype' => 'multipart/form-data', 'class' => 'post-form', 'onsubmit' => 'return false;'],
        'enableAjaxValidation' => false,
        'enableClientValidation' => false,
    ]); ?>
    
    <div class="user-avatar">
        <img src="<?= Html::encode($currentAvatar) ?>" alt="Ваш аватар">
    </div>
    
        <div class="post-input-area">
            <div class="post-input-wrapper">
                <?= $form->field($postModel, 'content', ['options' => ['class' => 'post-input-wrapper'], 'template' => "{input}"])
                    ->textarea(['id' => 'post-content', 'class' => 'post-input-textarea', 'placeholder' => 'Что у вас нового? 📝', 'maxlength' => 5000, 'rows' => 3]) ?>
                <div class="post-input-focus-border"></div>
            </div>
            
        <?= $form->field($postModel, 'imageFiles[]', ['options' => ['style' => 'display:none;'], 'template' => "{input}"])
            ->fileInput(['id' => 'post-image', 'accept' => 'image/*', 'multiple' => true, 'class' => 'image-input']) ?>
        
        <div id="image-preview-container" class="image-preview-container" style="display: none;">
            <div id="image-previews" class="image-previews"></div>
            <button type="button" class="btn-remove-all-images" onclick="removeAllSelectedImages()">🗑️ Удалить все</button>
        </div>
        
        <div id="poll-container" class="poll-container" style="display: none;">
            <div class="poll-header">
                <input type="text" id="poll-question" name="poll_question" class="poll-question-input" placeholder="Вопрос опроса..." maxlength="255">
                <button type="button" class="btn-remove-poll" onclick="removePoll()" title="Удалить опрос">✕</button>
            </div>
            
            <div class="poll-options-container" id="poll-options">
                <div class="poll-option-input">
                    <input type="text" name="poll_options[]" class="option-input" placeholder="Вариант ответа 1..." maxlength="100">
                    <button type="button" class="btn-remove-option" onclick="removeOption(this)">✕</button>
                </div>
                <div class="poll-option-input">
                    <input type="text" name="poll_options[]" class="option-input" placeholder="Вариант ответа 2..." maxlength="100">
                    <button type="button" class="btn-remove-option" onclick="removeOption(this)">✕</button>
                </div>
            </div>
            
            <div class="poll-actions">
                <button type="button" class="btn-add-option" onclick="addPollOption()">➕ Добавить вариант</button>
                <label class="poll-multiple-label">
                    <input type="checkbox" id="poll-multiple" name="poll_multiple" value="1">
                    <span>✅ Разрешить несколько вариантов</span>
                </label>
            </div>
        </div>
        
        <div class="post-actions">
            <div class="post-actions-left">
                <label for="post-image" class="btn-attach-image" title="Прикрепить изображение">📷</label>
                <button type="button" class="btn-add-poll" onclick="addPoll()" title="Добавить опрос">📊</button>
                <span class="char-count" id="char-count">0/5000</span>
            </div>
            <button type="submit" id="btn-publish" class="btn-publish" disabled>📤 Опубликовать</button>
        </div>
    </div>
    
    <?php ActiveForm::end(); ?>
</div>