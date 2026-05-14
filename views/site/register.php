<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Регистрация';
$this->registerCssFile('@web/css/auth.css');
?>

<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">🚀 Регистрация</h1>
        <p class="auth-subtitle">Создайте аккаунт, чтобы делиться постами</p>

        <?php $form = ActiveForm::begin([
            'id' => 'register-form',
            'fieldConfig' => [
                'template' => "{label}\n{input}\n{error}",
                'labelOptions' => ['class' => 'form-label'],
                'inputOptions' => ['class' => 'form-input'],
                'errorOptions' => ['class' => 'form-error'],
            ],
        ]); ?>

        <?= $form->field($model, 'username')->textInput([
            'placeholder' => 'Придумайте имя пользователя',
            'autofocus' => true,
        ]) ?>

        <?= $form->field($model, 'email')->textInput([
            'placeholder' => 'Ваш email',
            'type' => 'email',
        ]) ?>

        <?= $form->field($model, 'password')->passwordInput([
            'placeholder' => 'Придумайте пароль',
            'minlength' => 6,
        ]) ?>

        <div class="form-actions">
            <?= Html::submitButton('Создать аккаунт', ['class' => 'btn-primary', 'name' => 'register-button']) ?>
        </div>

        <?php ActiveForm::end(); ?>

        <div class="auth-footer">
            <p>Уже есть аккаунт? <?= Html::a('Войти', ['/site/login']) ?></p>
        </div>
    </div>
</div>
