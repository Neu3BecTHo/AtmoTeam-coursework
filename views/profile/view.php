<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $user->username;
$this->registerCssFile('@web/css/profile.css');
$this->registerCssFile('@web/css/feed.css');
$this->registerJsFile('@web/js/common.js');
$this->registerJsFile('@web/js/posts-unified.js');
$this->registerJsFile('@web/js/profile.js');
$avatar = $user->getAvatarUrl();
?>

<div class="profile-container">
    <!-- Header -->
    <div class="profile-header">
        <div class="profile-cover"></div>
        <div class="profile-info">
            <div class="profile-avatar-large">
                <img src="<?= $avatar ?>" alt="<?= Html::encode($user->username) ?>">
            </div>
            <div class="profile-details">
                <h1 class="profile-name">
                    <?= Html::encode($user->username) ?>
                    <?php if ($user->isPrivate()): ?>
                        <span class="private-badge">🔒 Приватный</span>
                    <?php endif; ?>
                </h1>
                <p class="profile-email"><?= Html::encode($user->email) ?></p>
                
                <?php if ($user->bio): ?>
                    <p class="profile-bio"><?= Html::encode($user->bio) ?></p>
                <?php endif; ?>
                
                <div class="profile-meta">
                    <?php if ($user->location): ?>
                        <span>📍 <?= Html::encode($user->location) ?></span>
                    <?php endif; ?>
                    <?php if ($user->website): ?>
                        <span>🔗 <a href="<?= Html::encode($user->website) ?>" target="_blank"><?= Html::encode(parse_url($user->website, PHP_URL_HOST) ?: $user->website) ?></a></span>
                    <?php endif; ?>
                </div>
                
                <div class="profile-stats">
                    <div class="stat">
                        <a href="<?= Url::to(['/profile/view', 'id' => $user->id]) ?>" class="stat-link">
                            <span class="stat-value"><?= $stats['posts_count'] ?></span>
                            <span class="stat-label">постов</span>
                        </a>
                    </div>
                    <div class="stat">
                        <a href="<?= Url::to(['/profile/followers', 'id' => $user->id]) ?>" class="stat-link">
                            <span class="stat-value" id="followers-count"><?= $stats['followers'] ?></span>
                            <span class="stat-label">подписчиков</span>
                        </a>
                    </div>
                    <div class="stat">
                        <a href="<?= Url::to(['/profile/following', 'id' => $user->id]) ?>" class="stat-link">
                            <span class="stat-value"><?= $stats['following'] ?></span>
                            <span class="stat-label">подписок</span>
                        </a>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <?php if ($isOwner): ?>
                        <a href="<?= Url::to(['/profile/edit']) ?>" class="btn-edit-profile">
                            Редактировать профиль
                        </a>
                        <a href="<?= Url::to(['/block/list']) ?>" class="btn-blocked-users">
                            🚫 Заблокированные
                        </a>
                    <?php elseif (!Yii::$app->user->isGuest): ?>
                        <a href="<?= Url::to(['/message/dialogue', 'id' => $user->id]) ?>" class="btn-message">
                            💬 Написать
                        </a>
                        <button id="follow-btn" class="btn-follow <?= $isFollowing ? 'following' : '' ?>" 
                                onclick="toggleFollow(<?= $user->id ?>)" data-user-id="<?= $user->id ?>">
                            <?= $isFollowing ? 'Отписаться' : 'Подписаться' ?>
                        </button>
                        <?php if ($isBlocked): ?>
                            <button id="block-btn" class="btn-block" 
                                    onclick="unblockUser(<?= $user->id ?>)" 
                                    data-user-id="<?= $user->id ?>"
                                    data-username="<?= Html::encode($user->username) ?>">
                                ✅ Разблокировать
                            </button>
                        <?php else: ?>
                            <button id="block-btn" class="btn-block" 
                                    onclick="showBlockModal(<?= $user->id ?>, '<?= Html::encode($user->username) ?>')" 
                                    data-user-id="<?= $user->id ?>"
                                    data-username="<?= Html::encode($user->username) ?>">
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

    <!-- Tabs -->
    <div class="profile-tabs">
        <button class="tab-btn active" data-tab="posts">📝 Посты</button>
        <?php if ($isOwner): ?>
            <button class="tab-btn" data-tab="saved">🔖 Сохранённые</button>
            <button class="tab-btn" data-tab="reposts">🔄 Репосты</button>
        <?php endif; ?>
        <button class="tab-btn" onclick="location.href='<?= Url::to(['/profile/followers', 'id' => $user->id]) ?>'">
            👥 Подписчики
        </button>
        <button class="tab-btn" onclick="location.href='<?= Url::to(['/profile/following', 'id' => $user->id]) ?>'">
            📋 Подписки
        </button>
        <?php if ($isOwner): ?>
            <button class="tab-btn" data-tab="settings">⚙️ Настройки</button>
        <?php endif; ?>
    </div>

    <!-- Content -->
    <div class="profile-content">
        <?php if ($isOwner): ?>
            <?= $this->render('/post/_create_form') ?>
        <?php endif; ?>

        <!-- Posts Tab -->
        <div class="tab-content active" id="posts-tab">
            <div class="posts-container" id="user-posts">
                <!-- Посты будут загружаться через JavaScript -->
            </div>
            <div id="load-more-sentinel" data-offset="0" style="height: 20px;"></div>
            <div class="load-more-spinner hidden" id="load-more-spinner">
                <div class="spinner">
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                </div>
            </div>
        </div>

        <!-- Saved Tab (only for owner) -->
        <?php if ($isOwner): ?>
        <div class="tab-content" id="saved-tab" style="display: none;">
            <div class="posts-container" id="user-saved">
                <!-- Сохранённые посты -->
            </div>
            <div id="load-more-sentinel-saved" data-offset="0" style="height: 20px;"></div>
            <div class="load-more-spinner hidden" id="load-more-spinner-saved">
                <div class="spinner">
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reposts Tab (only for owner) -->
        <?php if ($isOwner): ?>
        <div class="tab-content" id="reposts-tab" style="display: none;">
            <div class="posts-container" id="user-reposts">
                <!-- Репосты -->
            </div>
            <div id="load-more-sentinel-reposts" data-offset="0" style="height: 20px;"></div>
            <div class="load-more-spinner hidden" id="load-more-spinner-reposts">
                <div class="spinner">
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Settings Tab (only for owner) -->
        <?php if ($isOwner): ?>
            <div class="tab-content" id="settings-tab" style="display: none;">
                <div class="settings-card">
                    <h3>Быстрые ссылки</h3>
                    <a href="<?= Url::to(['/profile/edit']) ?>" class="settings-link">
                        <span>✏️</span> Редактировать профиль
                    </a>
                    <a href="<?= Url::to(['/site/logout']) ?>" class="settings-link logout" data-method="post">
                        <span>🚪</span> Выйти
                    </a>
                </div>
            </div>
        <?php endif; ?>



</div>

<!-- Block Confirmation Modal -->
<div id="block-modal" class="modal-overlay hidden">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>🚫 Заблокировать пользователя</h3>
            <button class="modal-close" onclick="hideBlockModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Вы уверены, что хотите заблокировать <strong id="block-modal-username"></strong>?</p>
            <p class="modal-hint">Заблокированный пользователь не сможет видеть ваши посты, подписаться на вас или писать вам сообщения.</p>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="hideBlockModal()">Отмена</button>
            <button class="btn-block-confirm" id="block-confirm-btn">Заблокировать</button>
        </div>
    </div>
</div>

<?= $this->render('/post/_post_modal') ?>


<script>
    window.profileUserId = <?= $user->id ?>;
</script>
