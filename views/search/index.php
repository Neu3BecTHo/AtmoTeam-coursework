<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Поиск: ' . Html::encode($query);
$this->registerCssFile('@web/css/search.css');
$this->registerJsFile('@web/js/search.js', ['depends' => ['yii\web\JqueryAsset']]);
    $this->registerJsFile('@web/js/main.js', ['depends' => ['yii\web\JqueryAsset']]);
?>

<div class="search-container">
    <!-- Search Header -->
    <div class="search-header">
        <h1>🔍 Поиск</h1>
        <div class="search-box-large">
            <input type="text" id="search-input" placeholder="Поиск пользователей и постов..." 
                   value="<?= Html::encode($query) ?>" autocomplete="off">
            <button class="btn-search" onclick="performSearch()">Найти</button>
        </div>
    </div>

    <?php if (!empty($query)): ?>
        <!-- Results Tabs -->
        <div class="search-tabs">
            <button class="tab-btn active" data-tab="users">
                👤 Пользователи (<?= count($users) ?>)
            </button>
            <button class="tab-btn" data-tab="posts">
                📝 Посты (<?= count($posts) ?>)
            </button>
        </div>

        <!-- Users Results -->
        <div class="tab-content active" id="users-tab">
            <?php if (empty($users)): ?>
                <div class="no-results">
                    <div class="no-results-icon">👻</div>
                    <p>Пользователи не найдены</p>
                </div>
            <?php else: ?>
                <div class="users-grid">
                    <?php foreach ($users as $user): 
                        $avatar = $user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $user->id;
                    ?>
                        <a href="<?= Url::to(['/profile/view', 'id' => $user->id]) ?>" class="user-card">
                            <img src="<?= $avatar ?>" alt="avatar" class="user-avatar-large">
                            <div class="user-info">
                                <span class="user-name"><?= Html::encode($user->username) ?></span>
                                <span class="user-email"><?= Html::encode($user->email) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Posts Results -->
        <div class="tab-content" id="posts-tab" style="display: none;">
            <?php if (empty($posts)): ?>
                <div class="no-results">
                    <div class="no-results-icon">📝</div>
                    <p>Посты не найдены</p>
                </div>
            <?php else: ?>
                <div class="posts-results">
                    <?php foreach ($posts as $post): 
                        $pAvatar = $post->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $post->user_id;
                    ?>
                        <div class="search-post-card">
                            <a href="<?= Url::to(['/profile/view', 'id' => $post->user_id]) ?>" class="post-author-line">
                                <img src="<?= $pAvatar ?>" class="author-avatar-tiny">
                                <span class="author-name-tiny"><?= Html::encode($post->user->username) ?></span>
                                <span class="post-time-tiny"><?= $post->timeAgo ?></span>
                            </a>
                            <a href="<?= Url::to(['/post/view', 'id' => $post->id]) ?>" class="post-content-link">
                                <p><?= Html::encode(substr($post->content, 0, 200)) ?><?= strlen($post->content) > 200 ? '...' : '' ?></p>
                            </a>
                            <div class="post-meta">
                                <span>❤️ <?= $post->likes_count ?></span>
                                <span>💬 <?= $post->comments_count ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="search-empty">
            <div class="search-empty-icon">🔍</div>
            <p>Введите запрос для поиска</p>
            <div class="search-suggestions">
                <span class="suggestion-tag" onclick="quickSearch('admin')">admin</span>
                <span class="suggestion-tag" onclick="quickSearch('привет')">привет</span>
                <span class="suggestion-tag" onclick="quickSearch('тест')">тест</span>
            </div>
        </div>
    <?php endif; ?>
</div>

