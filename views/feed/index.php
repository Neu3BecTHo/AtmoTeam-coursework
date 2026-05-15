<?php
use yii\helpers\Html;

$this->title = 'Лента новостей';
$this->registerCssFile('@web/css/feed.css');
$this->registerCssFile('@web/css/story.css');
$this->registerJsFile('@web/js/common.js');
$this->registerJsFile('@web/js/posts-unified.js');
$this->registerJsFile('@web/js/feed.js');
$this->registerJsFile('@web/js/story.js');

$currentUserId = Yii::$app->user->id;
$currentUser = Yii::$app->user->identity;
$currentUsername = $currentUser ? $currentUser->username : 'Гость';
$currentAvatar = $currentUser ? $currentUser->getAvatarUrl() : 'https://api.dicebear.com/7.x/avataaars/svg?seed=0';
$feedType = Yii::$app->user->isGuest ? 'explore' : 'following';
?>

<script>
window.currentUserId = <?= json_encode($currentUserId) ?>;
window.currentUsername = <?= json_encode($currentUsername) ?>;
window.feedType = <?= json_encode($feedType) ?>;
</script>

<div class="feed-container">
    <div class="feed-header">
        <h1 class="feed-title">Лента</h1>
        <div class="feed-filters">
            <button class="feed-filter <?= $type === 'all' ? 'active' : '' ?>" data-type="all">Все</button>
            <button class="feed-filter <?= $type === 'following' ? 'active' : '' ?>" data-type="following">Подписки</button>
            <button class="feed-filter <?= $type === 'popular' ? 'active' : '' ?>" data-type="popular">Популярное</button>
        </div>
    </div>
    
    <!-- Stories Section -->
    <?php if (!Yii::$app->user->isGuest && !empty($storiesByUser)): ?>
    <div class="feed-stories-section">
        <div class="stories-scroll-wrapper">
            <button class="scroll-btn scroll-btn-left" onclick="scrollStories(-1)" aria-label="Прокрутить влево">
                ‹
            </button>
            <div class="stories-grid" id="feed-stories-grid">
                <?php foreach ($storiesByUser as $userId => $userStories): ?>
                    <?php $firstStory = $userStories[0]; $author = $firstStory->user; ?>
                    <div class="user-stories" data-user-id="<?= $userId ?>">
                        <div class="user-header">
                            <a href="<?= \yii\helpers\Url::to(['/profile/view', 'id' => $userId]) ?>" class="user-avatar-link">
                                <img class="user-avatar" 
                                     src="<?= $author ? ($author->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $author->id) : '' ?>" 
                                     alt="<?= $author ? \yii\helpers\Html::encode($author->username) : '' ?>">
                            </a>
                            <div class="user-info">
                                <div class="username"><?= $author ? \yii\helpers\Html::encode($author->username) : '' ?></div>
                                <div class="stories-count"><?= count($userStories) ?> историй</div>
                            </div>
                        </div>
                        <div class="stories-list">
                            <?php foreach ($userStories as $story): ?>
                                <div class="story-item" data-story-id="<?= $story->id ?>">
                                    <div class="story-image-container">
                                        <img class="story-image" 
                                             src="<?= $story->getImageUrl() ?>" 
                                             alt="История"
                                             onclick="viewStory(<?= $story->id ?>)">
                                        <div class="story-time-left"><?= $story->getTimeLeft() ?></div>
                                        <?php if ($story->caption): ?>
                                            <div class="story-caption"><?= \yii\helpers\Html::encode($story->caption) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="scroll-btn scroll-btn-right" onclick="scrollStories(1)" aria-label="Прокрутить вправо">
                ›
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="feed-content">
        <?php if (!Yii::$app->user->isGuest): ?>
            <?= $this->render('/post/_create_form') ?>
        <?php else: ?>
        <div class="guest-notice">
            <p>Войдите или зарегистрируйтесь, чтобы публиковать посты и ставить лайки</p>
        </div>
        <?php endif; ?>

        <!-- Лента постов -->
        <div id="posts-container" class="posts-container">
            <!-- Посты будут загружаться через JavaScript -->
        </div>
        <div id="feed-sentinel" style="height: 20px;"></div>
        <div class="feed-loading hidden" id="feed-spinner">
            <div class="spinner">
                <div class="spinner-ring"></div>
                <div class="spinner-ring"></div>
                <div class="spinner-ring"></div>
            </div>
        </div>

        <?= $this->render('/post/_post_modal') ?>

    <!-- Story View Modal -->
    <div class="story-view-modal" id="story-view-modal">
        <div class="modal-content">
            <div class="modal-header">
                <button class="btn-close" onclick="hideStoryView()">×</button>
            </div>
            <div class="modal-body">
                <div class="story-view-content" id="story-view-content">
                    <!-- Контент загружается через JS -->
                </div>
            </div>
        </div>
    </div>
</div>

