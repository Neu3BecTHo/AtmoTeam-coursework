<?php
$this->title = 'Заблокированные пользователи';
$this->registerCssFile('@web/css/profile.css');
$this->registerJsFile('@web/js/profile.js', ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile('@web/js/block.js', ['depends' => ['yii\web\JqueryAsset']]);
?>

<div class="profile-container">
    <div class="profile-header">
        <h1 class="profile-name">🚫 Заблокированные пользователи</h1>
        <p class="profile-email">Управление заблокированными пользователями</p>
    </div>

    <div class="profile-content">
        <div class="blocked-users-section">
            <div id="blocked-users-container">
                <!-- Список загружается через JS -->
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <p>Загрузка...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.currentUserId = <?= Yii::$app->user->id ?>;
</script>
