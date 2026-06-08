<?php
use yii\helpers\Html;

if (!isset($comments) || empty($comments)) {
    echo '<div class="empty-comments"><div class="empty-comments-icon">💬</div><p>Нет комментариев</p><p class="empty-hint">Будьте первым, кто оставит комментарий!</p></div>';
    return;
}

$currentUserId = Yii::$app->user->id;
?>

<div class="comments-list">
    <?php foreach ($comments as $comment): 
        $cAvatar = $comment->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $comment->user_id;
        $isAuthor = $currentUserId && $comment->user_id == $currentUserId;
        $editedMark = $comment->updated_at && $comment->updated_at !== $comment->created_at ? '<span class="edited-mark">(ред.)</span>' : '';
        $timeAgo = $comment->getTimeAgo();
    ?>
        <div class="comment-item" data-comment-id="<?= $comment->id ?>">
            <img class="comment-avatar" src="<?= Html::encode($cAvatar) ?>" alt="<?= Html::encode($comment->user->username) ?>">
            <div class="comment-content">
                <div class="comment-header">
                    <div class="comment-author-info">
                        <span class="comment-author"><?= Html::encode($comment->user->username) ?></span>
                        <span class="comment-time"><?= Html::encode($timeAgo) ?></span>
                        <?= $editedMark ?>
                    </div>
                    <?php if ($isAuthor): ?>
                        <div class="comment-actions">
                            <button class="btn-edit-comment" data-comment-id="<?= $comment->id ?>" data-post-id="<?= $comment->post_id ?>" title="Редактировать">
                                ✏️
                            </button>
                            <button class="btn-delete-comment" data-comment-id="<?= $comment->id ?>" data-post-id="<?= $comment->post_id ?>" title="Удалить">
                                🗑️
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="comment-text"><?= nl2br(Html::encode($comment->content)) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
.empty-comments {
    text-align: center;
    padding: var(--space-8);
    color: var(--text-tertiary);
}

.empty-comments-icon {
    font-size: var(--text-4xl);
    margin-bottom: var(--space-3);
    opacity: 0.5;
}

.empty-comments p {
    margin-bottom: var(--space-1);
}

.empty-comments .empty-hint {
    font-size: var(--text-sm);
    opacity: 0.7;
}

.comment-item {
    display: flex;
    gap: var(--space-3);
    padding: var(--space-4);
    background: var(--surface-50);
    border-radius: var(--radius-lg);
    transition: all var(--transition-fast);
    margin-bottom: var(--space-3);
}

.comment-item:hover {
    background: var(--surface-100);
}

.comment-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    border: 2px solid var(--border-primary);
}

.comment-content {
    flex: 1;
    min-width: 0;
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--space-2);
    margin-bottom: var(--space-2);
}

.comment-author-info {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    flex-wrap: wrap;
}

.comment-author {
    font-weight: var(--font-semibold);
    font-size: var(--text-sm);
    color: var(--text-primary);
}

.comment-time {
    font-size: var(--text-xs);
    color: var(--text-tertiary);
}

.edited-mark {
    font-size: var(--text-xs);
    color: var(--text-tertiary);
    font-style: italic;
}

.comment-text {
    font-size: var(--text-sm);
    line-height: var(--leading-relaxed);
    color: var(--text-secondary);
    word-wrap: break-word;
    white-space: pre-wrap;
}

.comment-actions {
    display: flex;
    gap: var(--space-1);
}

.btn-edit-comment,
.btn-delete-comment {
    background: none;
    border: none;
    font-size: var(--text-sm);
    padding: var(--space-1);
    cursor: pointer;
    border-radius: var(--radius-sm);
    transition: all var(--transition-fast);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
}

.btn-edit-comment {
    color: var(--text-tertiary);
}

.btn-edit-comment:hover {
    background: var(--surface-200);
    color: var(--primary-600);
    transform: translateY(-1px);
}

.btn-delete-comment {
    color: var(--text-tertiary);
}

.btn-delete-comment:hover {
    background: var(--error-light);
    color: var(--error);
    transform: translateY(-1px);
}
</style>