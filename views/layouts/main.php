<?php




use app\assets\AppAsset;
use app\widgets\Alert;
use yii\helpers\Html;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? 'Социальная сеть на Yii2']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? 'social network, yii2']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);

$currentUser = Yii::$app->user->identity;
$navAvatar = $currentUser ? ($currentUser->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $currentUser->id) : null;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <title><?= Html::encode($this->title) ?> | Social</title>
    
    <?php $this->head() ?>
    
    <!-- Prevent FOUC - inline styles applied immediately -->
    <style>
        body { background-color: #ffffff; }
        body.dark-theme { background-color: #0f172a; }
    </style>
    
    <?php $this->registerJsFile('@web/js/main.js'); ?>
    <?php $this->registerJsFile('@web/js/theme.js'); ?>
    
    <?php if (Yii::$app->user->isGuest): ?>
        <script>
            window.currentUserId = null;
            window.currentUsername = '';
            window.feedType = 'following';
        </script>
    <?php else: ?>
        <script>
            window.currentUserId = <?= Yii::$app->user->id ?>;
            window.currentUsername = <?= json_encode(Yii::$app->user->identity->username) ?>;
            window.feedType = 'following';
        </script>
    <?php endif; ?>
</head>
<body>
<?php $this->beginBody() ?>

<!-- Prevent FOUC - Apply theme class immediately before any content renders -->
<script>
    (function() {
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const theme = savedTheme === 'dark' || (!savedTheme && prefersDark) ? 'dark' : 'light';
        
        if (theme === 'dark') {
            document.body.classList.add('dark-theme');
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    })();
</script>

<header class="header">
    <div class="header-container">
        <div class="header-brand">
            <a href="<?= Yii::$app->urlManager->createUrl(['feed/index']) ?>" class="brand-link">
                <img src="<?= Yii::getAlias('@web/svg/logo.svg') ?>" alt="Social" class="site-logo" />
                <span class="site-title">AtmoTeam</span>
            </a>
        </div>
        
        <nav class="header-nav" role="navigation" aria-label="Основная навигация">
            <ul class="nav-list" role="menubar">
                <li class="nav-item" role="none">
                    <a href="<?= Yii::$app->urlManager->createUrl(['feed/index']) ?>" class="nav-link" aria-label="Перейти к ленте">
                        🏠 Лента
                    </a>
                </li>
                <li class="nav-item" role="none">
                    <a href="<?= Yii::$app->urlManager->createUrl(['search/index']) ?>" class="nav-link" aria-label="Перейти к поиску">
                        🔍 Поиск
                    </a>
                </li>
                
                <?php if (!Yii::$app->user->isGuest): ?>
                    <?php

                    $unreadCount = \app\models\Notification::getUnreadCount(Yii::$app->user->id);
                    $badge = $unreadCount > 0 ? ' <span class="nav-badge">' . $unreadCount . '</span>' : '';

                    $unreadMessages = \app\models\Message::getUnreadCount(Yii::$app->user->id);
                    $messagesBadge = $unreadMessages > 0 ? ' <span class="nav-badge messages-badge">' . $unreadMessages . '</span>' : '';
                    ?>
                    
                    <li class="nav-item" role="none">
                        <a href="<?= Yii::$app->urlManager->createUrl(['story/index']) ?>" class="nav-link" aria-label="Перейти к историям">
                            📸 Истории
                        </a>
                    </li>
                    
                    <li class="nav-item" role="none">
                        <a href="<?= Yii::$app->urlManager->createUrl(['message/index']) ?>" class="nav-link" aria-label="Перейти к сообщениям<?= $messagesBadge ? ' ' . $unreadMessages . ' непрочитанных сообщений' : '' ?>">
                            💬 Сообщения<?= $messagesBadge ?>
                        </a>
                    </li>
                    
                    <li class="nav-item nav-dropdown" role="none">
                        <button class="nav-user-menu" onclick="toggleUserMenu()" aria-expanded="false" aria-controls="user-menu" aria-label="Меню пользователя">
                            <img src="<?= $navAvatar ?>" class="nav-avatar" alt="Аватар пользователя">
                            <span class="nav-dropdown-arrow" aria-hidden="true">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="user-menu" role="menu" aria-hidden="true">
                            <a href="<?= Yii::$app->urlManager->createUrl(['profile/view']) ?>" class="dropdown-item" role="menuitem">
                                👤 Мой профиль
                            </a>
                            <a href="<?= Yii::$app->urlManager->createUrl(['profile/edit']) ?>" class="dropdown-item" role="menuitem">
                                ✏️ Редактировать
                            </a>
                            <?php 

                            $canAccessAdmin = Yii::$app->user->can('accessAdminPanel');
                            $username = Yii::$app->user->identity ? Yii::$app->user->identity->username : 'guest';
                            error_log("RBAC Debug: User=$username, CanAccessAdmin=" . ($canAccessAdmin ? 'true' : 'false'));
                        ?>
                        <?php if ($canAccessAdmin): ?>
                            <div class="dropdown-divider" role="separator"></div>
                            <a href="<?= Yii::$app->urlManager->createUrl(['admin/index']) ?>" class="dropdown-item admin-link" role="menuitem">
                                ⚙️ Админ-панель
                            </a>
                        <?php endif; ?>
                        <div class="dropdown-divider" role="separator"></div>
                        <a href="<?= Yii::$app->urlManager->createUrl(['site/logout']) ?>" class="dropdown-item logout-link" role="menuitem" data-method="post">
                            🚪 Выйти
                        </a>
                        </div>
                    </li>
                <?php else: ?>
                    <li class="nav-item" role="none">
                        <a href="<?= Yii::$app->urlManager->createUrl(['site/login']) ?>" class="nav-link" aria-label="Перейти на страницу входа">
                            🔐 Войти
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<main class="main">
    <div class="main-container">
        <?php if (!empty($this->params['breadcrumbs'])): ?>
            <div class="breadcrumbs">
                <?php foreach ($this->params['breadcrumbs'] as $index => $breadcrumb): ?>
                    <?php if ($index > 0): ?>
                        <span class="breadcrumb-separator">›</span>
                    <?php endif; ?>
                    <?php if (is_array($breadcrumb) && isset($breadcrumb['url'])): ?>
                        <a href="<?= Html::encode($breadcrumb['url']) ?>" class="breadcrumb-link">
                            <?= Html::encode($breadcrumb['label'] ?? '') ?>
                        </a>
                    <?php elseif (is_array($breadcrumb) && isset($breadcrumb['label'])): ?>
                        <span class="breadcrumb-current"><?= Html::encode($breadcrumb['label']) ?></span>
                    <?php elseif (is_string($breadcrumb)): ?>
                        <?php if ($index === count($this->params['breadcrumbs']) - 1): ?>
                            <span class="breadcrumb-current"><?= Html::encode($breadcrumb) ?></span>
                        <?php else: ?>
                            <a href="#" class="breadcrumb-link"><?= Html::encode($breadcrumb) ?></a>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif ?>
        
        <div class="content-wrapper">
            <?= Alert::widget() ?>
            <?= $content ?>
        </div>
    </div>
</main>

<?php if (!Yii::$app->user->isGuest): ?>
<!-- Notification Dropdown -->
<div class="notification-dropdown" id="notification-dropdown">
    <div class="notification-header">
        <h3>🔔 Уведомления</h3>
        <button class="btn-mark-all-read" onclick="markAllNotificationsRead()">
            Прочитать все
        </button>
    </div>
    <div class="notification-list" id="notification-list">
        <div class="notification-empty">Загрузка...</div>
    </div>
</div>

<?php endif; ?>

<?php $this->endBody() ?>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="modal-overlay hidden">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">🗑️ Удалить?</h3>
            <button class="modal-close" onclick="hideDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="delete-modal-text">Вы уверены, что хотите удалить?</p>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="hideDeleteModal()">Отмена</button>
            <button class="btn-danger" id="delete-modal-confirm">Удалить</button>
        </div>
    </div>
</div>

</body>
</html>
<?php $this->endPage() ?>
