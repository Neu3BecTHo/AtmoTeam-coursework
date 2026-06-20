<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('app','Поиск') . ': ' . Html::encode($query);
$this->registerCssFile('@web/css/search.css');
$this->registerJsFile('@web/js/search.js', ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerJsFile('@web/js/main.js', ['depends' => [\yii\web\JqueryAsset::class]]);

$hasQuery = !empty($query);
$usersCount = count($users);
$postsCount = count($posts);

?>

<div class="search-container">
    <div class="search-header">
        <h1 class="search-title">🔍 <?= Yii::t('app','Поиск') ?></h1>
        <p class="search-subtitle"><?= Yii::t('app','Найдите пользователей или интересные посты') ?></p>
        <div class="search-box-large">
            <input type="text" id="search-input" 
                   class="search-input-field"
                   placeholder="<?= Yii::t('app','Поиск пользователей и постов...') ?>" 
                   value="<?= Html::encode($query) ?>" 
                   autocomplete="off">
            <button class="btn-search" onclick="performSearch()">🔍 <?= Yii::t('app','Найти') ?></button>
        </div>
    </div>

    <?php if ($hasQuery): ?>
        <div class="search-tabs">
            <button class="tab-btn active" data-tab="users">
                👤 <?= Yii::t('app','Пользователи') ?> <span class="tab-count"><?= number_format($usersCount) ?></span>
            </button>
            <button class="tab-btn" data-tab="posts">
                📝 <?= Yii::t('app','Посты') ?> <span class="tab-count"><?= number_format($postsCount) ?></span>
            </button>
        </div>

        <!-- Users Results -->
        <div class="tab-content active" id="users-tab">
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <div class="empty-icon">👻</div>
                    <h3 class="empty-title"><?= Yii::t('app','Пользователи не найдены') ?></h3>
                    <p class="empty-description"><?= Yii::t('app','Попробуйте изменить поисковый запрос') ?></p>
                </div>
            <?php else: ?>
                <div class="users-grid">
                    <?php foreach ($users as $user): 
                        $userAvatar = $user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $user->id;
                        $userUsername = Html::encode($user->username);
                        $userEmail = Html::encode($user->email);
                    ?>
                        <a href="<?= Url::to(['/profile/view', 'id' => $user->id]) ?>" class="user-card">
                            <img src="<?= Html::encode($userAvatar) ?>" 
                                 alt="<?= $userUsername ?>" 
                                 class="user-avatar-large"
                                 loading="lazy">
                            <div class="user-info">
                                <span class="user-name"><?= $userUsername ?></span>
                                <span class="user-email"><?= $userEmail ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Posts Results -->
        <div class="tab-content" id="posts-tab" style="display: none;">
            <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <h3 class="empty-title"><?= Yii::t('app','Посты не найдены') ?></h3>
                    <p class="empty-description"><?= Yii::t('app','Попробуйте изменить поисковый запрос') ?></p>
                </div>
            <?php else: ?>
                <div class="posts-results">
                    <?php foreach ($posts as $post): 
                        $postAvatar = $post->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $post->user_id;
                        $postUsername = Html::encode($post->user->username);
                        $postContent = Html::encode($post->content);
                        $postPreview = mb_substr($postContent, 0, 200);
                        $hasMore = mb_strlen($postContent) > 200;
                        $timeAgo = $post->getTimeAgo();
                        $likesCount = number_format($post->likes_count);
                        $commentsCount = number_format($post->comments_count);
                    ?>
                        <div class="search-post-card">
                            <a href="<?= Url::to(['/profile/view', 'id' => $post->user_id]) ?>" class="post-author-line">
                                <img src="<?= Html::encode($postAvatar) ?>" 
                                     class="author-avatar-tiny" 
                                     alt="<?= $postUsername ?>"
                                     loading="lazy">
                                <span class="author-name-tiny"><?= $postUsername ?></span>
                                <span class="post-time-tiny"><?= Html::encode($timeAgo) ?></span>
                            </a>
                            <a href="<?= Url::to(['/post/view', 'id' => $post->id]) ?>" class="post-content-link">
                                <p><?= $postPreview ?><?= $hasMore ? '...' : '' ?></p>
                            </a>
                            <div class="post-meta">
                                <span class="post-meta-item" title="<?= Yii::t('app','Лайки') ?>">❤️ <?= $likesCount ?></span>
                                <span class="post-meta-item" title="<?= Yii::t('app','Комментарии') ?>">💬 <?= $commentsCount ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">🔍</div>
            <h3 class="empty-title"><?= Yii::t('app','Начните поиск') ?></h3>
            <p class="empty-description"><?= Yii::t('app','Введите запрос, чтобы найти пользователей или посты') ?></p>
            <div class="search-suggestions">
                <span class="suggestion-title"><?= Yii::t('app','Популярные запросы:') ?></span>
                <div class="suggestion-tags">
                    <button class="suggestion-tag" onclick="quickSearch('admin')">admin</button>
                    <button class="suggestion-tag" onclick="quickSearch('привет')">привет</button>
                    <button class="suggestion-tag" onclick="quickSearch('тест')">тест</button>
                    <button class="suggestion-tag" onclick="quickSearch('новости')">новости</button>
                    <button class="suggestion-tag" onclick="quickSearch('фото')">фото</button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.search-title {
    font-size: var(--text-3xl);
    font-weight: var(--font-bold);
    margin-bottom: var(--space-2);
}

.search-subtitle {
    color: var(--text-secondary);
    margin-bottom: var(--space-6);
}

.search-input-field {
    flex: 1;
    padding: var(--space-4) var(--space-5);
    border: none;
    background: transparent;
    font-size: var(--text-lg);
    color: var(--text-primary);
    outline: none;
}

.search-input-field::placeholder {
    color: var(--text-quaternary);
    font-style: italic;
}

.tab-count {
    background: var(--surface-100);
    padding: var(--space-1) var(--space-2);
    border-radius: var(--radius-full);
    font-size: var(--text-xs);
    margin-left: var(--space-2);
}

.post-meta-item {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
}

.search-suggestions {
    margin-top: var(--space-6);
    text-align: center;
}

.suggestion-title {
    display: block;
    font-size: var(--text-sm);
    color: var(--text-secondary);
    margin-bottom: var(--space-3);
}

.suggestion-tags {
    display: flex;
    gap: var(--space-2);
    justify-content: center;
    flex-wrap: wrap;
}

.suggestion-tag {
    padding: var(--space-2) var(--space-4);
    background: var(--surface-100);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-full);
    font-size: var(--text-sm);
    color: var(--text-secondary);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.suggestion-tag:hover {
    background: var(--primary-500);
    color: white;
    border-color: var(--primary-500);
    transform: translateY(-2px);
}

.empty-state {
    text-align: center;
    padding: var(--space-12) var(--space-6);
    background: var(--surface-0);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-xl);
}

.empty-icon {
    font-size: var(--text-5xl);
    margin-bottom: var(--space-4);
    opacity: 0.5;
}

.empty-title {
    font-size: var(--text-xl);
    font-weight: var(--font-semibold);
    margin-bottom: var(--space-2);
}

.empty-description {
    color: var(--text-secondary);
}

@media (max-width: 768px) {
    .search-container { padding: var(--space-4); }
    .search-box-large { flex-direction: column; }
    .search-input-field { padding: var(--space-3); font-size: var(--text-base); }
    .btn-search { width: 100%; border-radius: var(--radius-lg); }
    .users-grid { grid-template-columns: 1fr; }
    .posts-results { grid-template-columns: 1fr; }
}

@media (max-width: 480px) {
    .search-container { padding: var(--space-3); }
    .search-title { font-size: var(--text-2xl); }
    .tab-btn { padding: var(--space-2) var(--space-3); font-size: var(--text-sm); }
}
</style>