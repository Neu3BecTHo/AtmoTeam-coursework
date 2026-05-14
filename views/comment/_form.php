<?php
if (!Yii::$app->user->isGuest):
    $currentUserId = Yii::$app->user->id;
    $currentUser = Yii::$app->user->identity;
    $currentAvatar = $currentUser ? $currentUser->getAvatarUrl() : 'https://api.dicebear.com/7.x/avataaars/svg?seed=0';
?>
<div class="post-modal-comment-form">
    <img src="<?= $currentAvatar ?>" class="comment-avatar-full" alt="Ваш аватар">
    <div class="comment-input-wrapper">
        <textarea id="modal-comment-input" placeholder="Написать комментарий..." rows="2"></textarea>
        <button onclick="submitModalComment(<?= isset($postId) ? $postId : 0 ?>)">Отправить</button>
    </div>
</div>
<?php endif; ?>
