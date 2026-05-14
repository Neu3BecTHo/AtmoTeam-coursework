<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Вход';
$this->registerCssFile('@web/css/auth.css');
?>

<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">👋 Вход</h1>
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

        <?= $form->field($model, 'username')->textInput([
            'placeholder' => 'Имя пользователя',
            'autofocus' => true,
        ]) ?>

        <?= $form->field($model, 'password')->passwordInput([
            'placeholder' => 'Пароль',
        ]) ?>

        <?= $form->field($model, 'rememberMe')->checkbox([
            'template' => '<div class="checkbox-wrapper">{input} {label}</div>',
            'labelOptions' => ['class' => 'checkbox-label'],
        ]) ?>

        <div class="form-actions">
            <?= Html::submitButton('Войти', ['class' => 'btn-primary', 'name' => 'login-button']) ?>
        </div>

        <?php ActiveForm::end(); ?>

        <div class="auth-footer">
            <p>Нет аккаунта? <?= Html::a('Зарегистрироваться', ['/site/register']) ?></p>
        </div>
    </div>
</div>
