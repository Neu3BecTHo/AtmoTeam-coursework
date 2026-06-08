<?php

use yii\web\View;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $user->username;
$this->registerCssFile('@web/css/profile.css');
$this->registerCssFile('@web/css/feed.css');

$avatar = $user->getAvatarUrl();
$username = Html::encode($user->username);
$email = Html::encode($user->email);
$bio = $user->bio ? Html::encode($user->bio) : null;
$location = $user->location ? Html::encode($user->location) : null;
$website = $user->website ? Html::encode($user->website) : null;
$websiteHost = $website ? parse_url($website, PHP_URL_HOST) ?: $website : null;
$isPrivate = $user->isPrivate();
$isOwner = isset($isOwner) ? $isOwner : false;
$isFollowing = isset($isFollowing) ? $isFollowing : false;
$isBlocked = isset($isBlocked) ? $isBlocked : false;

// Подключаем JS - common.js уже загружен в layout
// Сначала переменные, потом post.js, потом profile.js
$this->registerJs(
    "window.profileUserId = " . (int) $user->id . "; window.currentUserId = " . (Yii::$app->user->id ?: 'null') . "; console.log('Profile vars:', window.profileUserId, window.currentUserId);",
    \yii\web\View::POS_END
);
$this->registerJsFile('@web/js/post.js', ['position' => \yii\web\View::POS_END]);
$this->registerJsFile('@web/js/profile.js', ['position' => \yii\web\View::POS_END]);
$this->registerJsFile('@web/js/feed.js', ['position' => \yii\web\View::POS_END]);

?>

<div class="profile-container">
    <div class="profile-header">
        <div class="profile-cover"></div>
        <div class="profile-info">
            <div class="profile-avatar-large">
                <img src="<?= Html::encode($avatar) ?>" alt="<?= $username ?>">
            </div>
            <div class="profile-details">
                <h1 class="profile-name">
                    <?= $username ?>
                    <?php if ($isPrivate): ?>
                        <span class="private-badge">🔒 Приватный</span>
                    <?php endif; ?>
                </h1>
                
                <?php if ($bio): ?>
                    <p class="profile-bio"><?= nl2br($bio) ?></p>
                <?php endif; ?>
                
                <div class="profile-meta">
                    <?php if ($location): ?>
                        <span>📍 <?= $location ?></span>
                    <?php endif; ?>
                    <?php if ($website && $websiteHost): ?>
                        <span>🔗 <a href="<?= $website ?>" target="_blank" rel="noopener noreferrer"><?= $websiteHost ?></a></span>
                    <?php endif; ?>
                </div>
                
                <div class="profile-stats">
                    <div class="stat">
                        <a href="<?= Url::to(['/profile/view', 'id' => $user->id]) ?>" class="stat-link">
                            <span class="stat-value"><?= number_format($stats['posts_count']) ?></span>
                            <span class="stat-label">постов</span>
                        </a>
                    </div>
                    <div class="stat">
                        <a href="<?= Url::to(['/profile/followers', 'id' => $user->id]) ?>" class="stat-link">
                            <span class="stat-value" id="followers-count"><?= number_format($stats['followers']) ?></span>
                            <span class="stat-label">подписчиков</span>
                        </a>
                    </div>
                    <div class="stat">
                        <a href="<?= Url::to(['/profile/following', 'id' => $user->id]) ?>" class="stat-link">
                            <span class="stat-value"><?= number_format($stats['following']) ?></span>
                            <span class="stat-label">подписок</span>
                        </a>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <?php if ($isOwner): ?>
                        <a href="<?= Url::to(['/profile/edit']) ?>" class="btn-edit-profile">
                            ✏️ Редактировать
                        </a>
                        <a href="<?= Url::to(['/block/list']) ?>" class="btn-blocked-users">
                            🚫 Заблокированные
                        </a>
                    <?php elseif (!Yii::$app->user->isGuest): ?>
                        <a href="<?= Url::to(['/message/dialogue', 'id' => $user->id]) ?>" class="btn-message">
                            💬 Написать
                        </a>
                        <button id="follow-btn" class="btn-follow <?= $isFollowing ? 'following' : '' ?>" 
                                data-user-id="<?= $user->id ?>"
                                data-username="<?= $username ?>">
                            <span class="btn-icon"><?= $isFollowing ? '🔓' : '🔔' ?></span>
                            <span class="btn-text"><?= $isFollowing ? 'Отписаться' : 'Подписаться' ?></span>
                        </button>
                        <?php if ($isBlocked): ?>
                            <button id="block-btn" class="btn-block unblock" 
                                    data-user-id="<?= $user->id ?>"
                                    data-username="<?= $username ?>">
                                ✅ Разблокировать
                            </button>
                        <?php else: ?>
                            <button id="block-btn" class="btn-block" 
                                    data-user-id="<?= $user->id ?>"
                                    data-username="<?= $username ?>">
                                🚫 Заблокировать
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="<?= Url::to(['/site/login']) ?>" class="btn-follow">Подписаться</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="profile-tabs">
        <button class="tab-btn active" data-tab="posts">📝 Посты</button>
        <?php if ($isOwner): ?>
            <button class="tab-btn" data-tab="saved">🔖 Сохранённые</button>
            <button class="tab-btn" data-tab="reposts">🔄 Репосты</button>
        <?php endif; ?>
        <button class="tab-btn" data-tab="followers">👥 Подписчики</button>
        <button class="tab-btn" data-tab="following">📋 Подписки</button>
        <?php if ($isOwner): ?>
            <button class="tab-btn" data-tab="settings">⚙️ Настройки</button>
        <?php endif; ?>
    </div>

    <div class="profile-content">
        <?php if ($isOwner): ?>
            <?= $this->render('/post/_create_form') ?>
        <?php endif; ?>

        <!-- Posts Tab -->
        <div class="tab-content active" id="posts-tab">
            <div class="posts-container" id="user-posts"></div>
            <div id="load-more-sentinel" data-offset="0" class="load-more-sentinel"></div>
            <div class="load-more-spinner hidden" id="load-more-spinner">
                <div class="spinner">
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                </div>
            </div>
        </div>

        <!-- Saved Tab -->
        <?php if ($isOwner): ?>
        <div class="tab-content" id="saved-tab" style="display: none;">
            <div class="posts-container" id="user-saved"></div>
            <div id="load-more-sentinel-saved" data-offset="0" class="load-more-sentinel"></div>
            <div class="load-more-spinner hidden" id="load-more-spinner-saved">
                <div class="spinner">
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reposts Tab -->
        <?php if ($isOwner): ?>
        <div class="tab-content" id="reposts-tab" style="display: none;">
            <div class="posts-container" id="user-reposts"></div>
            <div id="load-more-sentinel-reposts" data-offset="0" class="load-more-sentinel"></div>
            <div class="load-more-spinner hidden" id="load-more-spinner-reposts">
                <div class="spinner">
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Followers Tab -->
        <div class="tab-content" id="followers-tab" style="display: none;">
            <div class="users-container" id="user-followers"></div>
            <div id="load-more-sentinel-followers" data-offset="0" class="load-more-sentinel"></div>
            <div class="load-more-spinner hidden" id="load-more-spinner-followers">
                <div class="spinner">
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                </div>
            </div>
        </div>

        <!-- Following Tab -->
        <div class="tab-content" id="following-tab" style="display: none;">
            <div class="users-container" id="user-following"></div>
            <div id="load-more-sentinel-following" data-offset="0" class="load-more-sentinel"></div>
            <div class="load-more-spinner hidden" id="load-more-spinner-following">
                <div class="spinner">
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                </div>
            </div>
        </div>

        <!-- Settings Tab -->
        <?php if ($isOwner): ?>
        <div class="tab-content" id="settings-tab" style="display: none;">
            <div class="settings-card">
                <h3>⚙️ Быстрые ссылки</h3>
                <a href="<?= Url::to(['/profile/edit']) ?>" class="settings-link">
                    ✏️ Редактировать профиль
                </a>
                <a href="<?= Url::to(['/block/list']) ?>" class="settings-link">
                    🚫 Заблокированные пользователи
                </a>
                <div class="settings-divider"></div>
                <a href="<?= Url::to(['/site/logout']) ?>" class="settings-link logout" data-method="post">
                    🚪 Выйти из аккаунта
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Block Confirmation Modal -->
<div id="block-modal" class="modal-overlay hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">🚫 Заблокировать пользователя</h3>
            <button class="modal-close" onclick="hideBlockModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-icon">⚠️</div>
            <p>Вы уверены, что хотите заблокировать <strong id="block-modal-username"></strong>?</p>
            <p class="modal-hint">Заблокированный пользователь не сможет видеть ваши посты, подписаться на вас или писать вам сообщения.</p>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="hideBlockModal()">❌ Отмена</button>
            <button class="btn-danger" id="block-confirm-btn">🚫 Заблокировать</button>
        </div>
    </div>
</div>

<?= $this->render('/post/_post_modal') ?>

<style>
.load-more-sentinel {
    height: 20px;
}

.modal-hint {
    font-size: var(--text-sm);
    color: var(--text-tertiary);
    margin-top: var(--space-2);
    padding-top: var(--space-2);
    border-top: 1px solid var(--border-primary);
}

.settings-divider {
    height: 1px;
    background: var(--border-primary);
    margin: var(--space-3) 0;
}
</style>