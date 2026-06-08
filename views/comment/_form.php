<?php
use yii\helpers\Html;
use yii\helpers\Url;

if (!Yii::$app->user->isGuest):
    $currentUserId = Yii::$app->user->id;
    $currentUser = Yii::$app->user->identity;
    $currentAvatar = $currentUser ? $currentUser->getAvatarUrl() : 'https://api.dicebear.com/7.x/avataaars/svg?seed=0';
    
    $postIdValue = isset($postId) ? (int)$postId : 0;
?>

<div class="comment-form" data-post-id="<?= $postIdValue ?>">
    <img src="<?= Html::encode($currentAvatar) ?>" class="comment-avatar" alt="Ваш аватар">
    <div class="comment-form__wrapper">
        <textarea class="comment-form__input modal-comment-input" 
                  placeholder="Написать комментарий..." 
                  rows="1" 
                  maxlength="1000"></textarea>
        <div class="comment-form__footer">
            <span class="comment-form__counter">0</span>
            <span class="comment-form__counter-max">/1000</span>
            <button class="comment-form__btn modal-comment-submit" data-post-id="<?= $postIdValue ?>">
                📤 Отправить
            </button>
        </div>
    </div>
</div>

<style>
.comment-form {
    display: flex;
    gap: var(--space-3);
    margin-top: var(--space-4);
    padding-top: var(--space-4);
    border-top: 1px solid var(--border-primary);
    align-items: flex-start;
}

.comment-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    border: 2px solid var(--border-primary);
}

.comment-form__wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.comment-form__input {
    width: 100%;
    padding: var(--space-2) var(--space-3);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-lg);
    background: var(--surface-0);
    color: var(--text-primary);
    font-size: var(--text-sm);
    font-family: var(--font-sans);
    resize: none;
    overflow-y: hidden;
    transition: all var(--transition-fast);
}

.comment-form__input:focus {
    outline: none;
    border-color: var(--primary-500);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.comment-form__input::placeholder {
    color: var(--text-tertiary);
}

.comment-form__footer {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    justify-content: flex-end;
}

.comment-form__counter {
    font-size: var(--text-xs);
    color: var(--text-tertiary);
}

.comment-form__counter-max {
    font-size: var(--text-xs);
    color: var(--text-tertiary);
}

.comment-form__btn {
    padding: var(--space-1) var(--space-4);
    background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
    color: white;
    border: none;
    border-radius: var(--radius-lg);
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.comment-form__btn:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

.comment-form__btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

@media (max-width: 480px) {
    .comment-avatar {
        width: 28px;
        height: 28px;
    }
    
    .comment-form__btn {
        padding: var(--space-1) var(--space-3);
        font-size: var(--text-xs);
    }
}
</style>

<?php endif; ?>