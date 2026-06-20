<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = Yii::t('app', 'Регистрация');
$this->registerCssFile('@web/css/auth.css');

?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-icon">✨</div>
        <h1 class="auth-title"><?= Yii::t('app', '🚀 Создать аккаунт') ?></h1>
        <p class="auth-subtitle"><?= Yii::t('app', 'Присоединяйтесь к сообществу и делитесь моментами') ?></p>

        <?php $form = ActiveForm::begin([
            'id' => 'register-form',
            'fieldConfig' => [
                'template' => "{label}\n{input}\n{error}",
                'labelOptions' => ['class' => 'form-label'],
                'inputOptions' => ['class' => 'form-input'],
                'errorOptions' => ['class' => 'form-error'],
            ],
        ]); ?>

        <div class="form-group">
            <?= $form->field($model, 'username')->textInput([
                'placeholder' => Yii::t('app', 'Придумайте имя пользователя'),
                'autofocus' => true,
                'autocomplete' => 'username'
            ]) ?>
        </div>

        <div class="form-group">
            <?= $form->field($model, 'email')->textInput([
                'placeholder' => Yii::t('app', 'Ваш email'),
                'type' => 'email',
                'autocomplete' => 'email'
            ]) ?>
        </div>

        <div class="form-group">
            <?= $form->field($model, 'password')->passwordInput([
                'placeholder' => Yii::t('app', 'Придумайте пароль (минимум 6 символов)'),
                'minlength' => 6,
                'autocomplete' => 'new-password'
            ]) ?>
            <div class="password-strength" id="password-strength" style="display: none;">
                <div class="strength-bar"></div>
                <span class="strength-text"></span>
            </div>
        </div>

        <div class="form-actions">
            <?= Html::submitButton(Yii::t('app', '✨ Создать аккаунт'), ['class' => 'btn btn-primary', 'name' => 'register-button']) ?>
        </div>

        <?php ActiveForm::end(); ?>

        <div class="auth-footer">
            <p><?= Yii::t('app', 'Уже есть аккаунт?') ?> <a href="<?= Url::to(['/site/login']) ?>"><?= Yii::t('app', '🔐 Войти') ?></a></p>
            <p class="auth-footer-hint"><?= Yii::t('app', 'Регистрируясь, вы соглашаетесь с правилами платформы') ?></p>
        </div>
    </div>
</div>

<style>
.auth-icon {
    font-size: var(--text-4xl);
    text-align: center;
    margin-bottom: var(--space-4);
}

.form-actions {
    display: flex;
    justify-content: center !important;
}

.password-strength {
    margin-top: var(--space-2);
    padding: var(--space-2);
    background: var(--surface-50);
    border-radius: var(--radius-md);
}

.strength-bar {
    height: 4px;
    background: var(--surface-200);
    border-radius: var(--radius-full);
    overflow: hidden;
    margin-bottom: var(--space-1);
}

.strength-bar .fill {
    width: 0%;
    height: 100%;
    transition: width 0.3s ease;
}

.strength-text {
    font-size: var(--text-xs);
    color: var(--text-tertiary);
}

.auth-footer-hint {
    font-size: var(--text-xs);
    color: var(--text-tertiary);
    margin-top: var(--space-3);
    padding-top: var(--space-3);
    border-top: 1px solid var(--border-primary);
}

@media (max-width: 480px) {
    .auth-card {
        padding: var(--space-6);
    }
    
    .auth-title {
        font-size: var(--text-2xl);
    }
    
    .btn-primary {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('registerform-password');
    const strengthContainer = document.getElementById('password-strength');
    const strengthBar = strengthContainer?.querySelector('.strength-bar');
    const strengthText = strengthContainer?.querySelector('.strength-text');
    
    if (!passwordInput || !strengthContainer) return;
    
    // Create fill div if not exists
    let fill = strengthBar?.querySelector('.fill');
    if (strengthBar && !fill) {
        fill = document.createElement('div');
        fill.className = 'fill';
        strengthBar.appendChild(fill);
    }
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        
        if (password.length === 0) {
            strengthContainer.style.display = 'none';
            return;
        }
        
        strengthContainer.style.display = 'block';
        
        let strength = 0;
        if (password.length >= 6) strength++;
        if (password.length >= 10) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        strength = Math.min(strength, 5);
        
        const width = (strength / 5) * 100;
        if (fill) fill.style.width = width + '%';
        
        const messages = ['<?= Yii::t('app', 'Очень слабый') ?>', '<?= Yii::t('app', 'Слабый') ?>', '<?= Yii::t('app', 'Средний') ?>', '<?= Yii::t('app', 'Хороший') ?>', '<?= Yii::t('app', 'Отличный') ?>'];
        const colors = ['#ef4444', '#f59e0b', '#eab308', '#10b981', '#10b981'];
        
        if (fill) fill.style.background = colors[strength - 1] || '#ef4444';
        if (strengthText) strengthText.textContent = messages[strength - 1] || '';
    });
});
</script>