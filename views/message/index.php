<?php
$this->title = 'Сообщения';
$this->registerCssFile('@web/css/message.css');
$this->registerJsFile('@web/js/message.js', ['depends' => ['yii\web\JqueryAsset']]);
?>

<div class="message-container">
    <div class="message-header">
        <h1 class="message-title">💬 Сообщения</h1>
        <p class="message-subtitle">Ваши диалоги с другими пользователями</p>
    </div>

    <div class="message-content">
        <div class="dialogues-container">
            <?php if (empty($dialogues)): ?>
                <div class="empty-state">
                    <div class="empty-icon">💬</div>
                    <p>У вас пока нет сообщений</p>
                    <a href="<?= \yii\helpers\Url::to(['/search/index']) ?>" class="btn-find-users">
                        Найти пользователей для общения
                    </a>
                </div>
            <?php else: ?>
                <div class="dialogues-list">
                    <?php foreach ($dialogues as $dialogue): ?>
                        <div class="dialogue-item">
                            <a href="<?= \yii\helpers\Url::to(['/profile/view', 'id' => $dialogue['user']['id']]) ?>" 
                               class="dialogue-avatar-link" onclick="event.stopPropagation()">
                                <img class="dialogue-avatar" 
                                     src="<?= $dialogue['user']['avatar'] ?>" 
                                     alt="<?= \yii\helpers\Html::encode($dialogue['user']['username']) ?>">
                            </a>
                            <div class="dialogue-content" onclick="window.location.href='<?= \yii\helpers\Url::to(['/message/dialogue', 'id' => $dialogue['user']['id']]) ?>'">
                                <div class="dialogue-user">
                                    <?= \yii\helpers\Html::encode($dialogue['user']['username']) ?>
                                </div>
                                <div class="dialogue-preview">
                                    <?= \yii\helpers\Html::encode($dialogue['last_message'] ?? 'Начните диалог...') ?>
                                </div>
                            </div>
                            <div class="dialogue-right">
                                <div class="dialogue-time">
                                    <?= getTimeAgo($dialogue['last_message_time']) ?>
                                </div>
                                <?php if ($dialogue['unread_count'] > 0): ?>
                                    <span class="unread-badge"><?= $dialogue['unread_count'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
function getTimeAgo($timestamp) {
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'только что';
    if ($diff < 3600) return floor($diff / 60) . ' мин. назад';
    if ($diff < 86400) return floor($diff / 3600) . ' ч. назад';
    if ($diff < 2592000) return floor($diff / 86400) . ' дн. назад';
    
    return date('d.m.Y', $timestamp);
}
?>
