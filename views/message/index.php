<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('app','Сообщения');
$this->registerCssFile('@web/css/message.css');
$this->registerJsFile('@web/js/message.js', ['depends' => [\yii\web\JqueryAsset::class]]);

function getTimeAgo($timestamp) {
    $diff = time() - (int)$timestamp;
    
    if ($diff < 60) return Yii::t('app', 'только что');
    if ($diff < 3600) return floor($diff / 60) . ' ' . Yii::t('app', 'мин. назад');
    if ($diff < 86400) return floor($diff / 3600) . ' ' . Yii::t('app', 'ч. назад');
    if ($diff < 2592000) return floor($diff / 86400) . ' ' . Yii::t('app', 'дн. назад');
    
    return date('d.m.Y', $timestamp);
}
?>

<div class="message-container">
    <div class="message-header">
        <h1 class="message-title">💬 <?= Yii::t('app', 'Сообщения') ?></h1>
        <p class="message-subtitle"><?= Yii::t('app', 'Ваши диалоги с другими пользователями') ?></p>
    </div>

    <div class="message-content">
        <div class="dialogues-container">
            <?php if (empty($dialogues)): ?>
                <div class="empty-state">
                    <div class="empty-icon">💬</div>
                    <p><?= Yii::t('app', 'У вас пока нет сообщений') ?></p>
                    <p class="empty-hint"><?= Yii::t('app', 'Начните общение с другими пользователями') ?></p>
                    <a href="<?= Url::to(['/search/index']) ?>" class="btn-find-users">
                        🔍 <?= Yii::t('app', 'Найти пользователей') ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="dialogues-list">
                    <?php foreach ($dialogues as $dialogue): ?>
                        <div class="dialogue-item" data-user-id="<?= $dialogue['user']['id'] ?>" data-last-message-time="<?= $dialogue['last_message_time'] ?? 0 ?>">
                            <a href="<?= Url::to(['/profile/view', 'id' => $dialogue['user']['id']]) ?>" 
                               class="dialogue-avatar-link" 
                               onclick="event.stopPropagation()"
                               title="<?= Html::encode($dialogue['user']['username']) ?>">
                                <img class="dialogue-avatar" 
                                     src="<?= Html::encode($dialogue['user']['avatar']) ?>" 
                                     alt="<?= Html::encode($dialogue['user']['username']) ?>">
                            </a>
                            
                            <div class="dialogue-content" 
                                 onclick="window.location.href='<?= Url::to(['/message/dialogue', 'id' => $dialogue['user']['id']]) ?>'">
                                <div class="dialogue-header-info">
                                    <div class="dialogue-user"><?= Html::encode($dialogue['user']['username']) ?></div>
                                    <div class="dialogue-time" data-timestamp="<?= $dialogue['last_message_time'] ?? 0 ?>">
                                        <?= Html::encode(getTimeAgo($dialogue['last_message_time'] ?? 0)) ?>
                                    </div>
                                </div>
                                <div class="dialogue-preview">
                                    <?= Html::encode($dialogue['last_message'] ?? Yii::t('app', 'Начните диалог...')) ?>
                                </div>
                                <?php if (($dialogue['unread_count'] ?? 0) > 0): ?>
                                    <span class="unread-badge" title="<?= Yii::t('app', 'Непрочитанных: {count}', ['count' => $dialogue['unread_count']]) ?>">
                                        <?= $dialogue['unread_count'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>