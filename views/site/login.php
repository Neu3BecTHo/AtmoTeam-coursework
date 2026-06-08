<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = 'Вход';
$this->registerCssFile('@web/css/auth.css');

?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-icon">🔐</div>
        <h1 class="auth-title">👋 Добро пожаловать</h1>
        <p class="auth-subtitle">Войдите в свой аккаунт</p>

        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'fieldConfig' => [
                'template' => "{label}\n{input}\n{error}",
                'labelOptions' => ['class' => 'form-label'],
                'inputOptions' => ['class' => 'form-input'],
                'errorOptions' => ['class' => 'form-error'],
            ],
        ]); ?>

        <div class="form-group">
            <?= $form->field($model, 'username')->textInput([
                'placeholder' => 'Введите имя пользователя',
                'autofocus' => true,
                'autocomplete' => 'username'
            ]) ?>
        </div>

        <div class="form-group">
            <?= $form->field($model, 'password')->passwordInput([
                'placeholder' => 'Введите пароль',
                'autocomplete' => 'current-password'
            ]) ?>
        </div>

        <div class="form-options">
            <?= $form->field($model, 'rememberMe')->checkbox([
                'template' => '<div class="checkbox-wrapper">{input} {label}</div>',
                'labelOptions' => ['class' => 'checkbox-label'],
            ]) ?>
        </div>

        <div class="form-actions">
            <?= Html::submitButton('🚀 Войти', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
        </div>

        <?php ActiveForm::end(); ?>

        <div class="auth-footer">
            <p>Нет аккаунта? <a href="<?= Url::to(['/site/register']) ?>">📝 Зарегистрироваться</a></p>
            <p class="auth-footer-hint">После регистрации вы сможете создавать посты, комментировать и общаться с друзьями</p>
        </div>
    </div>
</div>

<style>
.auth-icon {
    font-size: var(--text-4xl);
    text-align: center;
    margin-bottom: var(--space-4);
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: var(--space-4) 0;
}

.form-actions {
    display: flex;
    justify-content: center;
}

.checkbox-wrapper {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.checkbox-wrapper input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--primary-600);
}

.checkbox-label {
    font-size: var(--text-sm);
    color: var(--text-primary);
    cursor: pointer;
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