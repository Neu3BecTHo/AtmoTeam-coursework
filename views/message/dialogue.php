<?php

use app\assets\AppAsset;
use yii\helpers\Html;
use yii\helpers\Url;

AppAsset::register($this);

/**
 * @var \yii\web\View $this
 * @var \app\models\User $otherUser
 * @var \app\models\Message[] $messages
 * @var \app\models\User $currentUser
 */

$this->title = 'Диалог с ' . Html::encode($otherUser->username);
$this->registerCssFile('@web/css/message.css');

$currentUserId = Yii::$app->user->id;
$currentUser = Yii::$app->user->identity;
$currentAvatar = $currentUser ? $currentUser->getAvatarUrl() : 'https://api.dicebear.com/7.x/avataaars/svg?seed=0';
$otherUserId = $otherUser->id;
$otherUsername = Html::encode($otherUser->username);
$otherAvatar = $otherUser->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $otherUser->id;

?>

<div class="message-container">
    <div class="message-header">
        <div class="dialogue-header">
            <a href="<?= Url::to(['/profile/view', 'id' => $otherUserId]) ?>" class="dialogue-avatar-link">
                <img class="dialogue-avatar" 
                     src="<?= $otherAvatar ?>" 
                     alt="<?= $otherUsername ?>">
            </a>
            <div class="dialogue-info">
                <h1 class="dialogue-title"><?= $otherUsername ?></h1>
                <p class="dialogue-subtitle">Личные сообщения</p>
            </div>
            <a href="<?= Url::to(['/message/index']) ?>" class="btn-back">
                ← К списку диалогов
            </a>
        </div>
    </div>

    <div class="message-content">
        <div class="chat-container">
            <div class="messages-container" id="messages-container">
                <?php if (empty($messages)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">💬</div>
                        <p>Начните диалог с <?= $otherUsername ?></p>
                        <p class="empty-hint">Напишите первое сообщение</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): 
                        $isSent = $message->sender_id == $currentUserId;
                        $hasImages = $message->hasImages();
                        $imageUrls = $message->getImageUrls();
                        $senderUsername = Html::encode($message->sender->username);
                        $senderAvatar = $message->sender->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $message->sender->id;
                    ?>
                        <div class="message <?= $isSent ? 'sent' : 'received' ?>" data-message-id="<?= $message->id ?>">
                            <a href="<?= Url::to(['/profile/view', 'id' => $message->sender_id]) ?>" class="message-avatar-link">
                                <img class="message-avatar" 
                                     src="<?= $senderAvatar ?>" 
                                     alt="<?= $senderUsername ?>">
                            </a>
                            <div class="message-bubble">
                                <?php if ($message->content): ?>
                                    <div class="message-text"><?= nl2br(Html::encode($message->content)) ?></div>
                                <?php endif; ?>
                                
                                <?php if ($hasImages): ?>
                                    <?php if (count($imageUrls) === 1): ?>
                                        <div class="message-image-bubble">
                                            <img class="message-image-content" 
                                                src="<?= Html::encode($imageUrls[0]) ?>" 
                                                alt="Изображение"
                                                loading="lazy">
                                        </div>
                                    <?php else: ?>
                                        <div class="message-grouped-bubble">
                                            <?php foreach ($imageUrls as $index => $imageUrl): ?>
                                                <img class="grouped-image" 
                                                    src="<?= Html::encode($imageUrl) ?>" 
                                                    alt="Изображение <?= $index + 1 ?>"
                                                    loading="lazy">
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <div class="message-time" data-timestamp="<?= $message->created_at ?>">
                                    <?= $message->getTimeAgo() ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="message-form">
                <div id="message-image-preview" class="message-image-preview"></div>
                <div class="message-input-container">
                    <button class="btn-upload-image" id="btn-upload-image" type="button" title="Загрузить изображение">
                        📷
                    </button>
                    <input type="file" id="message-image-input" accept="image/*" multiple style="display: none;">
                    <textarea id="message-input" 
                              class="message-input"
                              placeholder="Напишите сообщение... 📝😊"
                              maxlength="1000"
                              data-receiver-id="<?= $otherUserId ?>"
                              rows="1"
                              autocomplete="off"
                              autocorrect="off"
                              autocapitalize="off"
                              spellcheck="false"></textarea>
                    <button id="send-message-btn" class="btn-send-message" type="button" title="Отправить">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
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
window.currentUserId = <?= (int) $currentUserId ?>;
window.currentUsername = <?= json_encode(Yii::$app->user->identity->username ?? '') ?>;
window.receiverId = <?= (int) $otherUserId ?>;
</script>

<?php $this->registerJsFile('@web/js/message.js', ['position' => \yii\web\View::POS_END]); ?>