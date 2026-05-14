<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = 'Редактирование профиля';
$this->registerCssFile('@web/css/auth.css');
$this->registerJsFile('@web/js/profile.js', ['position' => \yii\web\View::POS_END]);
$avatar = $user->getAvatarUrl();
?>

<div class="edit-profile-container">
    <div class="edit-card">
        <h1 class="edit-title">✏️ Редактирование профиля</h1>
        
        <?php $form = ActiveForm::begin([
            'id' => 'profile-form',
            'options' => ['enctype' => 'multipart/form-data'],
            'fieldConfig' => [
                'template' => "{label}\n{input}\n{error}",
                'labelOptions' => ['class' => 'form-label'],
                'inputOptions' => ['class' => 'form-input'],
                'errorOptions' => ['class' => 'form-error'],
            ],
        ]); ?>

        <div class="current-avatar">
            <img src="<?= $avatar ?>" alt="Текущий аватар" id="avatar-preview">
        </div>
        
        <div class="form-section">
            <h3>📸 Аватар</h3>
            <div class="file-upload">
                <?= $form->field($user, 'avatarFile')->fileInput([
                    'id' => 'avatar-input',
                    'accept' => 'image/*',
                    'onchange' => 'openAvatarCropper(this)'
                ])->label(false) ?>
                <label for="avatar-input" class="file-upload-label">
                    Выберите изображение (JPG, PNG, GIF, макс. 2MB)
                </label>
            </div>
            <input type="hidden" name="cropped_avatar" id="cropped-avatar-input">
        </div>
        
        <div class="form-section">
            <h3>👤 Основная информация</h3>
            <?= $form->field($user, 'username')->textInput([
                'maxlength' => 32,
                'placeholder' => 'Ваше имя пользователя'
            ]) ?>

            <?= $form->field($user, 'email')->textInput([
                'type' => 'email',
                'placeholder' => 'your@email.com'
            ]) ?>
        </div>

        <div class="form-section">
            <h3>📝 О себе</h3>
            <?= $form->field($user, 'bio')->textarea([
                'rows' => 4,
                'placeholder' => 'Расскажите немного о себе...',
                'maxlength' => 500
            ])->label(false) ?>
        </div>

        <div class="form-section">
            <h3>📍 Дополнительно</h3>
            <?= $form->field($user, 'location')->textInput([
                'placeholder' => 'Город, страна'
            ]) ?>
            
            <?= $form->field($user, 'website')->textInput([
                'placeholder' => 'https://example.com'
            ]) ?>
            
            <div class="form-group">
                <label class="form-label">Приватность профиля</label>
                <div class="privacy-toggle">
                    <?= $form->field($user, 'is_private')->checkbox([
                        'label' => 'Сделать профиль приватным',
                        'uncheck' => null,
                        'value' => 1
                    ])->label(false) ?>
                    <small class="help-text">Приватный профиль виден только вашим подписчикам</small>
                </div>
            </div>
        </div>
        
        <div class="form-section password-section">
            <h4>🔐 Смена пароля</h4>
            <p class="help-text">Оставьте пустым, если не хотите менять пароль</p>
            <?= $form->field($user, 'newPassword')->passwordInput([
                'placeholder' => 'Новый пароль (минимум 6 символов)'
            ])->label(false) ?>
        </div>

        <div class="form-actions">
            <a href="<?= Url::to(['/profile/view', 'id' => $user->id]) ?>" class="btn-cancel">
                Отмена
            </a>
            <?= Html::submitButton('Сохранить изменения', ['class' => 'btn-save']) ?>
        </div>

        <?php ActiveForm::end(); ?>

        <div class="danger-zone">
            <h3>⚠️ Опасная зона</h3>
            <p>Удаление аккаунта нельзя отменить</p>
            <button class="btn-delete" onclick="confirmDelete()">Удалить аккаунт</button>
        </div>
    </div>
</div>

<!-- Avatar Crop Modal -->
<div id="avatar-crop-modal" class="modal-overlay hidden">
    <div class="modal-dialog crop-dialog">
        <div class="modal-header">
            <h3>📸 Обрезка аватарки</h3>
            <button class="modal-close" onclick="closeAvatarCropper()">&times;</button>
        </div>
        <div class="modal-body crop-body">
            <div class="crop-container">
                <canvas id="crop-canvas" width="320" height="320"></canvas>
                <div class="crop-hint">Перетаскивайте изображение. Используйте ползунок для масштаба.</div>
            </div>
            <div class="crop-controls">
                <label>Масштаб</label>
                <input type="range" id="crop-scale" min="0.5" max="3" step="0.05" value="1">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeAvatarCropper()">Отмена</button>
            <button class="btn-save" onclick="applyAvatarCrop()">Применить</button>
        </div>
    </div>
</div>
