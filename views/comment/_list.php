<?php
use yii\helpers\Html;

if (!isset($comments) || empty($comments)) {
    echo '<p>Нет комментариев</p>';
    return;
}

$currentUserId = Yii::$app->user->id;
?>

<div class="comments-list">
    <?php foreach ($comments as $comment): 
        $cAvatar = $comment->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $comment->user_id;
        $isAuthor = !$currentUserId || $comment->user_id == $currentUserId;
        $editedMark = $comment->updated_at && $comment->updated_at !== $comment->created_at ? ' <span class="edited-mark">(ред.)</span>' : '';
    ?>
        <div class="comment" data-comment-id="<?= $comment->id ?>">
            <img class="comment-avatar" src="<?= $cAvatar ?>" alt="">
            <div class="comment-content">
                <div class="comment-header">
                    <span class="comment-author"><?= Html::encode($comment->user->username) ?></span>
                    <span class="comment-time"><?= $comment->timeAgo ?></span>
                    <?php if ($isAuthor): ?>
                        <div class="comment-actions">
                            <button class="btn-edit-comment" data-comment-id="<?= $comment->id ?>" data-post-id="<?= $comment->post_id ?>" title="Редактировать">✏️</button>
                            <button class="btn-delete-comment" data-comment-id="<?= $comment->id ?>" data-post-id="<?= $comment->post_id ?>" title="Удалить">🗑️</button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="comment-text"><?= Html::encode($comment->content) ?><?= $editedMark ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
