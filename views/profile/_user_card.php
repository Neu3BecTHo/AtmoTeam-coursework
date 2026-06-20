<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Follow;
use Yii;

/**
 * @var \app\models\User $user
 * @var \app\models\User|null $currentUser
 */

$avatar = $user->getAvatarUrl();
$username = Html::encode($user->username);
$email = Html::encode($user->email);
$isCurrentUser = $currentUser && !Yii::$app->user->isGuest && $currentUser->id != $user->id;
$isFollowing = $isCurrentUser ? Follow::isFollowing($currentUser->id, $user->id) : false;

?>

<div class="user-card-item" data-user-id="<?= $user->id ?>">
    <a href="<?= Url::to(['/profile/view', 'id' => $user->id]) ?>" class="user-card-link">
        <img src="<?= Html::encode($avatar) ?>" 
             alt="<?= $username ?>" 
             class="user-avatar-medium"
             loading="lazy">
        <div class="user-info-medium">
            <span class="user-name-medium"><?= $username ?></span>
            <span class="user-email-medium"><?= $email ?></span>
        </div>
    </a>
    
    <?php if ($isCurrentUser): ?>
        <button class="btn-follow-small <?= $isFollowing ? 'following' : '' ?>" 
                data-user-id="<?= $user->id ?>"
                data-username="<?= $username ?>"
                data-following="<?= $isFollowing ? 'true' : 'false' ?>"
                title="<?= $isFollowing ? Yii::t('app', 'Отписаться от {username}', ['username' => $username]) : Yii::t('app', 'Подписаться на {username}', ['username' => $username]) ?>">
            <span class="btn-icon"><?= $isFollowing ? '🔓' : '🔔' ?></span>
            <span class="btn-text"><?= $isFollowing ? Yii::t('app', 'Отписаться') : Yii::t('app', 'Подписаться') ?></span>
        </button>
    <?php endif; ?>
</div>