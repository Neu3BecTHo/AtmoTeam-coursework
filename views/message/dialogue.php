<?php
use app\assets\AppAsset;
AppAsset::register($this);

$this->title = 'Диалог с ' . \yii\helpers\Html::encode($otherUser->username);
$this->registerCssFile('@web/css/message.css');
?>

<div class="message-container">
    <div class="message-header">
        <div class="dialogue-header">
            <a href="<?= \yii\helpers\Url::to(['/profile/view', 'id' => $otherUser->id]) ?>" class="dialogue-avatar-link">
                <img class="dialogue-avatar" 
                     src="<?= $otherUser->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $otherUser->id ?>" 
                     alt="<?= \yii\helpers\Html::encode($otherUser->username) ?>">
            </a>
            <div class="dialogue-info">
                <h1 class="dialogue-title"><?= \yii\helpers\Html::encode($otherUser->username) ?></h1>
                <p class="dialogue-subtitle">Личные сообщения</p>
            </div>
            <a href="<?= \yii\helpers\Url::to(['/message/index']) ?>" class="btn-back">
                ← К списку диалогов
            </a>
        </div>
    </div>

    <div class="message-content">
        <div class="chat-container">
            <div class="messages-container" id="messages-container">
                <?php if (empty($messages)): ?>
                    <div class="empty-state">
                        <p>Начните диалог с <?= \yii\helpers\Html::encode($otherUser->username) ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?= $message->sender_id == Yii::$app->user->id ? 'sent' : 'received' ?>">
                            <a href="<?= \yii\helpers\Url::to(['/profile/view', 'id' => $message->sender_id]) ?>" class="message-avatar-link">
                                <img class="message-avatar" 
                                     src="<?= $message->sender->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $message->sender->id ?>" 
                                     alt="<?= \yii\helpers\Html::encode($message->sender->username) ?>">
                            </a>
                            <div class="message-bubble">
                                <div class="message-text"><?= \yii\helpers\Html::encode($message->content) ?></div>
                                <div class="message-time" data-timestamp="<?= $message->created_at ?>"><?= $message->timeAgo ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="message-form">
                <div class="message-input-container">
                    <input type="text" 
                           id="message-input" 
                           class="message-input" 
                           placeholder="Напишите сообщение... 📝😊" 
                           maxlength="1000"
                           data-receiver-id="<?= $otherUser->id ?>"
                           autocomplete="off"
                           autocorrect="off"
                           autocapitalize="off"
                           spellcheck="false">
                    <button id="send-message-btn" class="btn-send-message" type="button" title="Отправить">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.currentUserId = <?= Yii::$app->user->id ?>;
window.receiverId = <?= $otherUser->id ?>;
</script>

<?php $this->registerJsFile('@web/js/message.js', ['position' => \yii\web\View::POS_END]); ?>
