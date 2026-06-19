<?php

use yii\helpers\Html;

$this->title = 'Лента новостей';

// Регистрация CSS
$this->registerCssFile('@web/css/feed.css');
$this->registerCssFile('@web/css/story.css');

// Регистрация JS (порядок важен)
$this->registerJsFile('@web/js/feed.js', ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerJsFile('@web/js/story.js', ['depends' => [\yii\web\JqueryAsset::class]]);

$currentUserId = Yii::$app->user->id;
$currentUser = Yii::$app->user->identity;
$currentUsername = $currentUser ? $currentUser->username : 'Гость';
$currentAvatar = $currentUser ? $currentUser->getAvatarUrl() : 'https://api.dicebear.com/7.x/avataaars/svg?seed=0';
$feedType = Yii::$app->user->isGuest ? 'explore' : 'following';
$type = Yii::$app->request->get('type', 'following');

?>

<script>
window.currentUserId = <?= json_encode($currentUserId) ?>;
window.currentUsername = <?= json_encode($currentUsername) ?>;
window.currentAvatar = <?= json_encode($currentAvatar) ?>;
window.feedType = <?= json_encode($feedType) ?>;
</script>

<div class="feed-container">
    <div class="feed-header">
        <h1 class="feed-title">📱 Лента новостей</h1>
        <div class="feed-filters">
            <button class="feed-filter <?= $type === 'all' ? 'active' : '' ?>" data-type="all">🌍 Все</button>
            <button class="feed-filter <?= $type === 'following' ? 'active' : '' ?>" data-type="following">👥 Подписки</button>
            <button class="feed-filter <?= $type === 'popular' ? 'active' : '' ?>" data-type="popular">🔥 Популярное</button>
        </div>
    </div>

    <div class="feed-content">
        <!-- Форма создания поста -->
        <?php if (!Yii::$app->user->isGuest): ?>
            <?= $this->render('/post/_create_form', ['currentAvatar' => $currentAvatar]) ?>
        <?php else: ?>
            <div class="guest-notice">
                <div class="guest-notice-icon">🔒</div>
                <p>Войдите или зарегистрируйтесь, чтобы публиковать посты и ставить лайки</p>
                <div class="guest-notice-actions">
                    <a href="/login" class="btn btn-primary">Войти</a>
                    <a href="/register" class="btn btn-secondary">Регистрация</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Лента постов -->
        <div id="posts-container" class="posts-container">
            <!-- Посты будут загружаться через JavaScript -->
        </div>

        <!-- Sentinel для бесконечной загрузки -->
        <div id="feed-sentinel" class="feed-sentinel" <?= Yii::$app->user->isGuest ? 'style="display: none;"' : '' ?>></div>

        <!-- Спиннер загрузки -->
        <div class="feed-loading hidden" id="feed-spinner" <?= Yii::$app->user->isGuest ? 'style="display: none;"' : '' ?>>
            <div class="spinner">
                <div class="spinner-ring"></div>
                <div class="spinner-ring"></div>
                <div class="spinner-ring"></div>
            </div>
        </div>
    </div>
</div>

<!-- Fullscreen Image Viewer Modal -->
<div class="fullscreen-image-modal" id="fullscreen-image-modal" style="display: none;">
    <div class="fullscreen-image-overlay" onclick="closeFullscreenImage()"></div>
    <div class="fullscreen-image-container">
        <button class="fullscreen-image-close" onclick="closeFullscreenImage()">&times;</button>
        <div class="fullscreen-image-navigation">
            <button class="fullscreen-image-prev" onclick="prevFullscreenImage()">‹</button>
            <button class="fullscreen-image-next" onclick="nextFullscreenImage()">›</button>
        </div>
        <div class="fullscreen-image-content">
            <img id="fullscreen-image" src="" alt="Fullscreen image">
            <div class="fullscreen-image-info">
                <span id="fullscreen-image-counter">1 / 1</span>
            </div>
        </div>
    </div>
</div>

<?php
// Дополнительные стили для компонентов
$css = <<<CSS
/* ============================================
   Create Post Form Styles
   ============================================ */

.create-post-card {
    background: linear-gradient(135deg, var(--surface-0) 0%, var(--surface-50) 100%);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-xl);
    padding: var(--space-6);
    margin-bottom: var(--space-6);
    box-shadow: var(--card-shadow);
}

.post-form {
    display: flex;
    gap: var(--space-4);
}

.user-avatar {
    flex-shrink: 0;
}

.user-avatar img {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--border-primary);
}

.post-input-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}

.post-input-wrapper {
    position: relative;
    width: 100%;
}

.post-input-textarea {
    width: 100%;
    min-height: 80px;
    padding: var(--space-4);
    border: 2px solid var(--border-primary);
    border-radius: var(--radius-lg);
    background: linear-gradient(135deg, var(--surface-0) 0%, var(--surface-50) 100%);
    color: var(--text-primary);
    font-size: var(--text-base);
    line-height: var(--leading-relaxed);
    resize: vertical;
    transition: all var(--transition-normal);
    font-family: inherit;
}

.post-input-textarea:focus {
    outline: none;
    border-color: var(--primary-600);
    background: linear-gradient(135deg, var(--surface-0) 0%, var(--surface-100) 100%);
    transform: translateY(-1px);
}

.post-input-textarea::placeholder {
    color: var(--text-tertiary);
    font-style: italic;
}

/* Image Preview Container */
.image-preview-container {
    background: var(--surface-50);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-lg);
    padding: var(--space-3);
    margin-top: var(--space-2);
}

.image-previews {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: var(--space-2);
    margin-bottom: var(--space-2);
}

.image-preview-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: var(--radius-md);
    overflow: hidden;
    background: var(--surface-100);
    border: 1px solid var(--border-primary);
}

.image-preview-thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.btn-remove-preview {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 24px;
    height: 24px;
    background: var(--error);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition-fast);
}

.btn-remove-preview:hover {
    background: #dc2626;
    transform: scale(1.1);
}

.btn-remove-all-images {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    padding: var(--space-2) var(--space-3);
    background: var(--surface-200);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-md);
    font-size: var(--text-sm);
    color: var(--text-secondary);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.btn-remove-all-images:hover {
    background: var(--error-light);
    color: var(--error);
    border-color: var(--error);
}

/* Poll Container */
.poll-container {
    background: var(--surface-50);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-lg);
    padding: var(--space-4);
    margin-top: var(--space-2);
    animation: none;
}

.poll-header {
    display: flex;
    gap: var(--space-2);
    margin-bottom: var(--space-3);
}

.poll-question-input {
    flex: 1;
    padding: var(--space-2) var(--space-3);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-md);
    background: var(--surface-0);
    color: var(--text-primary);
}

.btn-remove-poll {
    width: 36px;
    height: 36px;
    background: var(--error-light);
    color: var(--error);
    border: none;
    border-radius: var(--radius-md);
    cursor: pointer;
    font-size: 18px;
    transition: all var(--transition-fast);
}

.btn-remove-poll:hover {
    background: var(--error);
    color: white;
    transform: scale(1.05);
}

.poll-options-container {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    margin-bottom: var(--space-3);
}

.poll-option-input {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.poll-option-input input {
    flex: 1;
    padding: var(--space-2) var(--space-3);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-md);
    background: var(--surface-0);
    color: var(--text-primary);
}

.btn-remove-option {
    width: 32px;
    height: 32px;
    background: var(--error-light);
    color: var(--error);
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: 16px;
    transition: all var(--transition-fast);
}

.btn-remove-option:hover {
    background: var(--error);
    color: white;
    transform: scale(1.05);
}

.poll-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: var(--space-2);
    flex-wrap: wrap;
}

.btn-add-option {
    padding: var(--space-2) var(--space-4);
    background: var(--primary-500);
    color: white;
    border: none;
    border-radius: var(--radius-md);
    cursor: pointer;
    font-size: var(--text-sm);
    transition: all var(--transition-fast);
}

.btn-add-option:hover {
    background: var(--primary-600);
    transform: translateY(-1px);
}

.poll-multiple-label {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--text-sm);
    color: var(--text-secondary);
    cursor: pointer;
}

.poll-multiple-checkbox {
    width: 16px;
    height: 16px;
    accent-color: var(--primary-500);
}

/* Post Actions */
.post-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: var(--space-3);
}

.post-actions-left {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.btn-attach-image,
.btn-add-poll {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--surface-100) 0%, var(--surface-200) 100%);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-lg);
    cursor: pointer;
    font-size: 18px;
    transition: all var(--transition-fast);
}

.btn-attach-image:hover,
.btn-add-poll:hover {
    background: linear-gradient(135deg, var(--surface-200) 0%, var(--surface-300) 100%);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-attach-image.active,
.btn-add-poll.active {
    background: var(--primary-500);
    color: white;
    border-color: var(--primary-500);
}

.char-count {
    padding: var(--space-2) var(--space-3);
    background: var(--surface-100);
    border-radius: var(--radius-md);
    font-size: var(--text-sm);
    color: var(--text-tertiary);
    font-weight: var(--font-medium);
}

.char-count.warning {
    color: var(--warning);
    background: var(--warning-lighter);
}

.char-count.danger {
    color: var(--error);
    background: var(--error-lighter);
}

.btn-publish {
    padding: var(--space-3) var(--space-6);
    background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-700) 100%);
    color: var(--text-inverse);
    border: none;
    border-radius: var(--radius-lg);
    font-size: var(--text-base);
    font-weight: var(--font-semibold);
    cursor: pointer;
    transition: all var(--transition-normal);
}

.btn-publish:hover:not(:disabled) {
    background: linear-gradient(135deg, var(--primary-700) 0%, var(--primary-800) 100%);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.btn-publish:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Responsive */
@media (max-width: 768px) {
    .create-post-card {
        padding: var(--space-4);
    }
    
    .post-form {
        flex-direction: column;
        gap: var(--space-3);
    }
    
    .user-avatar {
        display: none;
    }
    
    .post-actions {
        flex-direction: column;
        gap: var(--space-3);
    }
    
    .post-actions-left {
        width: 100%;
        justify-content: space-between;
    }
    
    .btn-publish {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .create-post-card {
        padding: var(--space-3);
    }
    
    .post-input-textarea {
        padding: var(--space-3);
        font-size: var(--text-sm);
    }
    
    .image-previews {
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    }
    
    .btn-attach-image,
    .btn-add-poll {
        width: 36px;
        height: 36px;
        font-size: 16px;
    }
}

/* Stories empty state */
.stories-empty {
    background: var(--surface-0);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-xl);
    padding: var(--space-6);
    text-align: center;
    margin-bottom: var(--space-6);
}

.stories-empty-icon {
    font-size: var(--text-4xl);
    margin-bottom: var(--space-3);
    opacity: 0.5;
}

.stories-empty p {
    color: var(--text-secondary);
    margin-bottom: var(--space-4);
}

/* Guest notice */
.guest-notice {
    background: linear-gradient(135deg, var(--surface-0) 0%, var(--surface-50) 100%);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-xl);
    padding: var(--space-6);
    text-align: center;
    margin-bottom: var(--space-6);
}

.guest-notice-icon {
    font-size: var(--text-4xl);
    margin-bottom: var(--space-3);
    opacity: 0.5;
}

.guest-notice p {
    color: var(--text-secondary);
    margin-bottom: var(--space-4);
}

.guest-notice-actions {
    display: flex;
    gap: var(--space-3);
    justify-content: center;
}

/* Feed sentinel */
.feed-sentinel {
    height: 20px;
}

/* Scroll buttons */
.scroll-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: var(--surface-0);
    border: 1px solid var(--border-primary);
    border-radius: 50%;
    cursor: pointer;
    transition: all var(--transition-fast);
    font-size: 18px;
    color: var(--text-primary);
    flex-shrink: 0;
}

.scroll-btn:hover {
    background: var(--primary-500);
    color: white;
    border-color: var(--primary-500);
}

.scroll-btn-left {
    margin-right: var(--space-2);
}

.scroll-btn-right {
    margin-left: var(--space-2);
}

@media (max-width: 768px) {
    .scroll-btn {
        display: none;
    }
}
CSS;
$this->registerCss($css);
?>