<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Follow;

/**
 * @var \yii\web\View $this
 * @var \app\models\User $user
 * @var string $title
 * @var array $users
 */

$this->title = $title . ' - ' . $user->username;
$this->registerCssFile('@web/css/profile.css');

$currentUserId = Yii::$app->user->id;
$isGuest = Yii::$app->user->isGuest;
$username = Html::encode($user->username);
$avatar = $user->getAvatarUrl();
$isEmpty = empty($users);
$titleLower = $title === Yii::t('app','Подписчики') ? Yii::t('app','подписчиков') : Yii::t('app','подписок');
$emptyIcon = $title === Yii::t('app','Подписчики') ? '👥' : '📋';

?>

<div class="profile-container">
    <div class="profile-header">
        <div class="profile-cover"></div>
        <div class="profile-info">
            <div class="profile-avatar-large">
                <img src="<?= Html::encode($avatar) ?>" alt="<?= $username ?>">
            </div>
            <div class="profile-details">
                <h1 class="profile-name"><?= $username ?></h1>
                <p class="profile-bio"><?= Html::encode($title) ?></p>
                <a href="<?= Url::to(['/profile/view', 'id' => $user->id]) ?>" class="btn-back">
                    ← <?= Yii::t('app','Назад к профилю') ?>
                </a>
            </div>
        </div>
    </div>

    <div class="profile-content">
        <?php if ($isEmpty): ?>
            <div class="empty-state">
                <div class="empty-icon"><?= $emptyIcon ?></div>
                <h3 class="empty-title"><?= Yii::t('app','Пока нет') ?> <?= $titleLower ?></h3>
                <?php if ($title === Yii::t('app','Подписчики')): ?>
                    <p class="empty-description"><?= Yii::t('app','Когда кто-то подпишется на вас, они появятся здесь') ?></p>
                <?php else: ?>
                    <p class="empty-description"><?= Yii::t('app','Когда вы подпишетесь на кого-то, они появятся здесь') ?></p>
                <?php endif; ?>
                <a href="<?= Url::to(['/search/index']) ?>" class="btn-primary">
                    🔍 <?= Yii::t('app','Найти пользователей') ?>
                </a>
            </div>
        <?php else: ?>
            <div class="users-grid">
                <?php foreach ($users as $u): 
                    $uAvatar = $u->getAvatarUrl();
                    $uUsername = Html::encode($u->username);
                    $uEmail = Html::encode($u->email);
                    $isFollowing = !$isGuest && $currentUserId != $u->id ? Follow::isFollowing($currentUserId, $u->id) : false;
                ?>
                    <div class="user-card-item" data-user-id="<?= $u->id ?>">
                        <a href="<?= Url::to(['/profile/view', 'id' => $u->id]) ?>" class="user-card-link">
                            <img src="<?= Html::encode($uAvatar) ?>" 
                                 alt="<?= $uUsername ?>" 
                                 class="user-avatar-medium"
                                 loading="lazy">
                            <div class="user-info-medium">
                                <span class="user-name-medium"><?= $uUsername ?></span>
                                <span class="user-email-medium"><?= $uEmail ?></span>
                            </div>
                        </a>
                        
                        <?php if (!$isGuest && $currentUserId != $u->id): ?>
                            <button class="btn-follow-small <?= $isFollowing ? 'following' : '' ?>" 
                                    data-user-id="<?= $u->id ?>"
                                    data-username="<?= $uUsername ?>"
                                    data-following="<?= $isFollowing ? 'true' : 'false' ?>">
                                <span class="btn-icon"><?= $isFollowing ? '🔓' : '🔔' ?></span>
                                <span class="btn-text"><?= $isFollowing ? Yii::t('app','Отписаться') : Yii::t('app','Подписаться') ?></span>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>