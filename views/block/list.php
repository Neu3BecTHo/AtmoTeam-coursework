<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Заблокированные пользователи';

// Регистрация CSS и JS
$this->registerCssFile('@web/css/profile.css');
$this->registerJsFile('@web/js/profile.js', ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerJsFile('@web/js/block.js', ['depends' => [\yii\web\JqueryAsset::class]]);

?>

<div class="profile-container">
    <div class="profile-header">
        <div class="profile-info">
            <h1 class="profile-name">🚫 Заблокированные пользователи</h1>
            <p class="profile-bio">Управление заблокированными пользователями</p>
            <?= Html::a('← Назад в профиль', ['/profile/view', 'id' => Yii::$app->user->id], ['class' => 'btn-back']) ?>
        </div>
    </div>

    <div class="profile-content">
        <div class="blocked-users-section">
            <div class="section-header">
                <h2 class="section-title">🔒 Список заблокированных</h2>
                <div class="section-stats" id="blocked-count">
                    <span class="stat-badge">0 пользователей</span>
                </div>
            </div>

            <div id="blocked-users-container">
                <div class="loading-spinner">
                    <div class="spinner">
                        <div class="spinner-ring"></div>
                        <div class="spinner-ring"></div>
                        <div class="spinner-ring"></div>
                    </div>
                    <p>Загрузка...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.currentUserId = <?= (int) Yii::$app->user->id ?>;
window.profileUserId = <?= (int) Yii::$app->user->id ?>;
</script>