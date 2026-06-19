<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/**
 * @var \yii\web\View $this
 * @var \app\models\User $user
 */

$this->title = 'Редактирование профиля';
$this->registerCssFile('@web/css/auth.css');
$this->registerJsFile('@web/js/profile.js', ['position' => \yii\web\View::POS_END]);

$avatar = $user->getAvatarUrl();
$username = Html::encode($user->username);

?>

<div class="edit-profile-container">
    <div class="edit-card">
        <div class="edit-header">
            <a href="<?= Url::to(['/profile/view', 'id' => $user->id]) ?>" class="btn-back">
                ← Назад в профиль
            </a>
            <h1 class="edit-title">✏️ Редактирование профиля</h1>
            <p class="edit-subtitle">Настройте свой профиль <?= $username ?></p>
        </div>
        
        <?php $form = ActiveForm::begin([
            'id' => 'profile-form',
            'options' => ['enctype' => 'multipart/form-data', 'class' => 'edit-form'],
            'fieldConfig' => [
                'template' => "{label}\n{input}\n{error}",
                'labelOptions' => ['class' => 'form-label'],
                'inputOptions' => ['class' => 'form-input'],
                'errorOptions' => ['class' => 'form-error'],
            ],
        ]); ?>

        <div class="avatar-section">
            <div class="current-avatar">
                <img src="<?= Html::encode($avatar) ?>" alt="Текущий аватар" id="avatar-preview">
                <div class="avatar-overlay">📸</div>
            </div>
            
            <div class="avatar-upload-section">
                <div class="file-upload">
                    <input type="file" id="avatar-input" name="User[avatarFile]" accept="image/*" style="display: none;" onchange="openAvatarCropper(this)">
                    <label for="avatar-input" class="file-upload-label">
                        <span class="label-icon">📷</span>
                        <span class="label-text">Выбрать изображение</span>
                    </label>
                </div>
                <p class="avatar-hint">JPG, PNG, GIF, WebP. Максимум 2MB</p>
                <input type="hidden" name="cropped_avatar" id="cropped-avatar-input">
            </div>
        </div>
        
        <!-- Остальные поля формы -->
        <div class="form-section">
            <h3 class="section-title">👤 Основная информация</h3>
            <div class="form-row">
                <?= $form->field($user, 'username')->textInput([
                    'maxlength' => 32,
                    'placeholder' => 'Ваше имя пользователя',
                    'class' => 'form-input'
                ]) ?>
            </div>
            
            <div class="form-row">
                <?= $form->field($user, 'email')->textInput([
                    'type' => 'email',
                    'placeholder' => 'example@mail.ru',
                    'class' => 'form-input'
                ]) ?>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">📝 О себе</h3>
            <?= $form->field($user, 'bio')->textarea([
                'rows' => 4,
                'placeholder' => 'Расскажите немного о себе...',
                'maxlength' => 500,
                'class' => 'form-textarea',
                'id' => 'bio-textarea'
            ])->label(false) ?>
            <div class="char-counter">
                <span id="bio-counter">0</span>/500
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">📍 Дополнительно</h3>
            <?= $form->field($user, 'location')->textInput([
                'placeholder' => 'Город, страна',
                'class' => 'form-input'
            ]) ?>
            
            <?= $form->field($user, 'website')->textInput([
                'placeholder' => 'https://example.com',
                'class' => 'form-input'
            ]) ?>
            
            <div class="form-group privacy-group">
                <label class="form-label">🔒 Приватность профиля</label>
                <div class="privacy-toggle">
                    <?= $form->field($user, 'is_private')->checkbox([
                        'label' => 'Сделать профиль приватным',
                        'uncheck' => null,
                        'value' => 1,
                        'class' => 'checkbox-input'
                    ]) ?>
                    <div class="privacy-info">
                        <span class="info-icon">ℹ️</span>
                        <small class="help-text">Приватный профиль виден только вашим подписчикам</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-section password-section">
            <h3 class="section-title">🔐 Смена пароля</h3>
            <p class="help-text">Оставьте пустым, если не хотите менять пароль</p>
            <?= $form->field($user, 'newPassword')->passwordInput([
                'placeholder' => 'Новый пароль (минимум 6 символов)',
                'class' => 'form-input',
                'id' => 'new-password'
            ])->label(false) ?>
            <div class="password-strength" id="password-strength" style="display: none;">
                <div class="strength-bar"></div>
                <span class="strength-text"></span>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= Url::to(['/profile/view', 'id' => $user->id]) ?>" class="btn-secondary">
                ❌ Отмена
            </a>
            <?= Html::submitButton('💾 Сохранить изменения', ['class' => 'btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

        <div class="danger-zone">
    <div class="danger-zone-header">
        <div class="danger-zone-icon">⚠️</div>
        <div class="danger-zone-title">
            <h3>Опасная зона</h3>
            <p>Удаление аккаунта — необратимое действие</p>
        </div>
    </div>
    <div class="danger-zone-content">
        <div class="danger-zone-info">
            <div class="info-item">
                <span class="info-icon">🗑️</span>
                <span>Все ваши посты, комментарии и лайки будут удалены</span>
            </div>
            <div class="info-item">
                <span class="info-icon">💬</span>
                <span>Все диалоги и сообщения будут безвозвратно удалены</span>
            </div>
            <div class="info-item">
                <span class="info-icon">📸</span>
                <span>Фотографии и аватар будут удалены с сервера</span>
            </div>
            <div class="info-item">
                <span class="info-icon">👥</span>
                <span>Подписчики и подписки будут потеряны</span>
            </div>
        </div>
        <div class="danger-zone-warning">
            <div class="warning-icon">⚠️</div>
            <div class="warning-text">
                <strong>Это действие нельзя отменить!</strong>
                <span>Все ваши данные будут удалены безвозвратно</span>
            </div>
        </div>
        <button class="btn-delete-account" id="delete-account-btn">
            🗑️ Удалить аккаунт навсегда
        </button>
    </div>
</div>
    </div>
</div>

<!-- Avatar Crop Modal -->
<div id="avatar-crop-modal" class="modal-overlay hidden">
    <div class="modal-content crop-dialog">
        <div class="modal-header">
            <h3 class="modal-title">📸 Обрезка аватарки</h3>
            <button class="modal-close" onclick="closeAvatarCropper()" aria-label="Закрыть">&times;</button>
        </div>
        <div class="modal-body crop-body">
            <div class="crop-container">
                <canvas id="crop-canvas" width="320" height="320"></canvas>
            </div>
            <div class="crop-hint">
                <span>🖱️ Перетащите изображение для перемещения</span>
                <span>🔍 Используйте ползунок для масштаба</span>
            </div>
            <div class="crop-controls">
                <label class="crop-label">🔍 Масштаб</label>
                <input type="range" id="crop-scale" min="0.5" max="3" step="0.05" value="1">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeAvatarCropper()">❌ Отмена</button>
            <button class="btn-primary" onclick="applyAvatarCrop()">✅ Применить</button>
        </div>
    </div>
</div>

<style>
.edit-profile-container {
    max-width: 800px;
    margin: 0 auto;
    padding: var(--space-6);
}

.edit-card {
    background: var(--surface-0);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-2xl);
    padding: var(--space-8);
    box-shadow: var(--shadow-lg);
}

.edit-header {
    text-align: center;
    margin-bottom: var(--space-8);
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    margin-bottom: var(--space-4);
    padding: var(--space-2) var(--space-4);
    background: var(--surface-100);
    color: var(--text-primary);
    border-radius: var(--radius-lg);
    text-decoration: none;
    font-size: var(--text-sm);
}

.btn-back:hover {
    background: var(--surface-200);
}

.edit-title {
    font-size: var(--text-3xl);
    font-weight: var(--font-bold);
    color: var(--text-primary);
    margin-bottom: var(--space-2);
}

.edit-subtitle {
    color: var(--text-secondary);
    font-size: var(--text-sm);
}

.avatar-section {
    display: flex;
    align-items: center;
    gap: var(--space-6);
    margin-bottom: var(--space-8);
    flex-wrap: wrap;
}

.current-avatar {
    position: relative;
}

.current-avatar img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--border-primary);
    box-shadow: var(--shadow-md);
}

.current-avatar:hover .avatar-overlay {
    opacity: 1;
}

.avatar-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    opacity: 0;
    transition: opacity var(--transition-fast);
}

.avatar-upload-section {
    flex: 1;
}

.file-upload {
    position: relative;
}

.file-upload-label {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-3) var(--space-5);
    background: var(--surface-100);
    color: var(--text-primary);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-lg);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.file-upload-label:hover {
    background: var(--surface-200);
    transform: translateY(-2px);
}

.avatar-hint {
    font-size: var(--text-xs);
    color: var(--text-tertiary);
    margin-top: var(--space-2);
}

.form-section {
    margin-bottom: var(--space-8);
}

.section-title {
    font-size: var(--text-xl);
    font-weight: var(--font-semibold);
    margin-bottom: var(--space-4);
    padding-bottom: var(--space-3);
    border-bottom: 1px solid var(--border-primary);
}

.form-row {
    margin-bottom: var(--space-4);
}

.char-counter {
    text-align: right;
    font-size: var(--text-xs);
    color: var(--text-tertiary);
    margin-top: var(--space-1);
}

.privacy-group {
    margin-top: var(--space-4);
}

.privacy-toggle {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    flex-wrap: wrap;
}

.checkbox-input {
    width: 18px;
    height: 18px;
    accent-color: var(--primary-600);
}

.privacy-info {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.help-text {
    font-size: var(--text-xs);
    color: var(--text-tertiary);
}

.password-section {
    background: var(--surface-50);
    border-radius: var(--radius-xl);
    padding: var(--space-4);
    margin-bottom: var(--space-8);
}

.password-strength {
    margin-top: var(--space-2);
}

.strength-bar {
    height: 4px;
    background: var(--surface-200);
    border-radius: var(--radius-full);
    overflow: hidden;
}

.strength-bar.active {
    background: var(--success);
    width: 100%;
}

.strength-text {
    font-size: var(--text-xs);
    color: var(--text-tertiary);
}

.form-actions {
    display: flex;
    gap: var(--space-4);
    justify-content: flex-end;
    margin-top: var(--space-8);
    padding-top: var(--space-6);
    border-top: 1px solid var(--border-primary);
}

.danger-zone {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    background: linear-gradient(135deg, var(--error-lighter) 0%, rgba(239,68,68,0.1) 100%);
    border: 2px solid var(--error);
    border-radius: var(--radius-xl);
    padding: var(--space-6);
    margin-top: var(--space-8);
}

.danger-zone-icon {
    font-size: 32px;
}

.danger-zone-content {
    flex: 1;
}

.danger-zone h3 {
    font-size: var(--text-lg);
    font-weight: var(--font-semibold);
    color: var(--error);
    margin-bottom: var(--space-2);
}

.danger-zone p {
    font-size: var(--text-sm);
    color: var(--error);
    margin-bottom: var(--space-3);
}

.btn-danger {
    background: var(--error);
    color: white;
    border: none;
    padding: var(--space-2) var(--space-5);
    border-radius: var(--radius-lg);
    font-weight: var(--font-medium);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.btn-danger:hover {
    background: #dc2626;
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Crop Modal */
.crop-dialog {
    max-width: 450px;
}

.crop-container {
    text-align: center;
    margin-bottom: var(--space-4);
}

#crop-canvas {
    max-width: 100%;
    height: auto;
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-lg);
}

.crop-hint {
    display: flex;
    justify-content: center;
    gap: var(--space-4);
    font-size: var(--text-xs);
    color: var(--text-tertiary);
    margin-bottom: var(--space-4);
    flex-wrap: wrap;
}

.crop-controls {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.crop-label {
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

#crop-scale {
    width: 100%;
    height: 4px;
    border-radius: 2px;
    background: var(--surface-200);
    -webkit-appearance: none;
}

#crop-scale::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 16px;
    height: 16px;
    background: var(--primary-500);
    border-radius: 50%;
    cursor: pointer;
}

@media (max-width: 768px) {
    .edit-profile-container { padding: var(--space-4); }
    .edit-card { padding: var(--space-6); }
    .avatar-section { flex-direction: column; align-items: center; text-align: center; }
    .form-actions { flex-direction: column; }
    .danger-zone { flex-direction: column; text-align: center; }
}

@media (max-width: 480px) {
    .edit-profile-container { padding: var(--space-3); }
    .edit-card { padding: var(--space-4); }
    .edit-title { font-size: var(--text-2xl); }
    .current-avatar img { width: 100px; height: 100px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bioTextarea = document.getElementById('bio-textarea');
    const bioCounter = document.getElementById('bio-counter');
    
    if (bioTextarea && bioCounter) {
        function updateBioCounter() {
            const length = bioTextarea.value.length;
            bioCounter.textContent = length;
            bioCounter.style.color = length > 450 ? '#ef4444' : length > 400 ? '#f59e0b' : 'inherit';
        }
        bioTextarea.addEventListener('input', updateBioCounter);
        updateBioCounter();
    }
    
    const passwordInput = document.getElementById('new-password');
    const strengthContainer = document.getElementById('password-strength');
    
    if (passwordInput && strengthContainer) {
        passwordInput.addEventListener('input', function() {
            const value = this.value;
            if (value.length === 0) {
                strengthContainer.style.display = 'none';
                return;
            }
            strengthContainer.style.display = 'block';
            const strength = checkPasswordStrength(value);
            updateStrengthUI(strength);
        });
    }
    
    // ========== УДАЛЕНИЕ АККАУНТА ==========
    const deleteBtn = document.getElementById('delete-account-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (typeof showDeleteModal === 'function') {
                showDeleteModal(
                    '⚠️ Удаление аккаунта\n\nВы уверены, что хотите удалить свой аккаунт?\n\nЭто действие НЕОБРАТИМО!\nВсе ваши данные будут удалены безвозвратно.',
                    async () => {
                        // Показываем индикатор загрузки
                        deleteBtn.disabled = true;
                        deleteBtn.textContent = '⏳ Удаление...';
                        
                        try {
                            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                            const headers = csrfToken ? { 'X-CSRF-Token': csrfToken, 'Content-Type': 'application/json' } : { 'Content-Type': 'application/json' };
                            
                            const response = await fetch('/api/profile/delete-account', {
                                method: 'POST',
                                headers: headers,
                                body: JSON.stringify({ confirm: true })
                            });
                            
                            const result = await response.json();
                            
                            if (result.success) {
                                if (typeof showNotification === 'function') {
                                    showNotification('Аккаунт успешно удалён', 'success');
                                }
                                // Перенаправляем на главную через 2 секунды
                                setTimeout(() => {
                                    window.location.href = '/';
                                }, 2000);
                            } else {
                                if (typeof showNotification === 'function') {
                                    showNotification(result.error || 'Ошибка при удалении аккаунта', 'error');
                                }
                                deleteBtn.disabled = false;
                                deleteBtn.textContent = '🗑️ Удалить аккаунт навсегда';
                            }
                        } catch (error) {
                            console.error('Delete account error:', error);
                            if (typeof showNotification === 'function') {
                                showNotification('Ошибка сети. Попробуйте позже.', 'error');
                            }
                            deleteBtn.disabled = false;
                            deleteBtn.textContent = '🗑️ Удалить аккаунт навсегда';
                        }
                    }
                );
            } else {
                if (confirm('Вы уверены, что хотите удалить свой аккаунт? Это действие необратимо!')) {
                    window.location.href = '/profile/delete-account';
                }
            }
        });
    }
});

function checkPasswordStrength(password) {
    let score = 0;
    if (password.length >= 6) score++;
    if (password.length >= 10) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    return Math.min(score, 5);
}

function updateStrengthUI(strength) {
    const bar = document.querySelector('.strength-bar');
    const text = document.querySelector('.strength-text');
    if (!bar || !text) return;
    
    const width = (strength / 5) * 100;
    bar.style.width = width + '%';
    bar.classList.add('active');
    
    const messages = ['Очень слабый', 'Слабый', 'Средний', 'Хороший', 'Отличный'];
    const colors = ['#ef4444', '#f59e0b', '#eab308', '#10b981', '#10b981'];
    
    bar.style.background = colors[strength - 1] || '#ef4444';
    text.textContent = messages[strength - 1] || '';
}
</script>