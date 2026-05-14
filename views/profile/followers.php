<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $title . ' - ' . $user->username;
$this->registerCssFile('@web/css/profile.css');
$this->registerJsFile('@web/js/profile.js', ['depends' => ['yii\web\JqueryAsset']]);
?>

<div class="profile-container">
    <!-- Header -->
    <div class="profile-header">
        <div class="profile-cover"></div>
        <div class="profile-info">
            <div class="profile-avatar-large">
                <img src="<?= $user->getAvatarUrl() ?>" alt="<?= Html::encode($user->username) ?>">
            </div>
            <div class="profile-details">
                <h1 class="profile-name"><?= Html::encode($user->username) ?></h1>
                <p class="profile-email"><?= $title ?></p>
                <a href="<?= Url::to(['/profile/view', 'id' => $user->id]) ?>" class="btn-edit-profile">
                    ← Назад к профилю
                </a>
            </div>
        </div>
    </div>

    <!-- Users List -->
    <div class="profile-content">
        <?php if (empty($users)): ?>
            <div class="empty-profile">
                <div class="empty-icon">👥</div>
                <p>Пока нет <?= $title === 'Подписчики' ? 'подписчиков' : 'подписок' ?></p>
            </div>
        <?php else: ?>
            <div class="users-grid">
                <?php foreach ($users as $u): ?>
                    <div class="user-card-item">
                        <a href="<?= Url::to(['/profile/view', 'id' => $u->id]) ?>" class="user-card-link">
                            <img src="<?= $u->getAvatarUrl() ?>" alt="" class="user-avatar-medium">
                            <div class="user-info-medium">
                                <span class="user-name-medium"><?= Html::encode($u->username) ?></span>
                                <span class="user-email-medium"><?= Html::encode($u->email) ?></span>
                            </div>
                        </a>
                        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->id != $u->id): ?>
                            <?php 
                            $isFollowing = \app\models\Follow::isFollowing(Yii::$app->user->id, $u->id);
                            ?>
                            <button class="btn-follow-small <?= $isFollowing ? 'following' : '' ?>" 
                                    onclick="toggleFollowUser(this, <?= $u->id ?>)">
                                <?= $isFollowing ? 'Отписаться' : 'Подписаться' ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
