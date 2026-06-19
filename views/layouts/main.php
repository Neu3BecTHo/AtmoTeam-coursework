<?php

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var \yii\web\View $this
 * @var string $content
 */

AppAsset::register($this);

$this->title = "AtmoTeam - современная социальная сеть для общения, обмена фото и видео, создания постов и историй";

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? 'AtmoTeam - современная социальная сеть для общения, обмена фото и видео, создания постов и историй']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? 'AtmoTeam, АтомТим, атмосфера, социальная сеть, social network, yii2, общение, фото, видео, истории, stories, посты, лайки, комментарии, репосты, мессенджер, чат, сообщения, профиль пользователя, поиск друзей, онлайн общение, мобильное приложение, веб приложение, php, yii framework']);

// Favicon
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/svg/logo.svg')]);

// Theme color
$this->registerMetaTag(['name' => 'theme-color', 'content' => '#3b82f6', 'media' => '(prefers-color-scheme: light)']);
$this->registerMetaTag(['name' => 'theme-color', 'content' => '#1e293b', 'media' => '(prefers-color-scheme: dark)']);

// Open Graph
$ogTitle = $this->title ? Html::encode($this->title) . ' | AtmoTeam' : 'AtmoTeam Social';
$ogDescription = $this->params['meta_description'] ?? 'Современная социальная сеть для общения, обмена фото и видео';
$ogImage = Yii::getAlias('@web/svg/logo.svg');
$ogUrl = Url::to('', true);

$this->registerMetaTag(['property' => 'og:title', 'content' => $ogTitle]);
$this->registerMetaTag(['property' => 'og:description', 'content' => $ogDescription]);
$this->registerMetaTag(['property' => 'og:image', 'content' => $ogImage]);
$this->registerMetaTag(['property' => 'og:url', 'content' => $ogUrl]);
$this->registerMetaTag(['property' => 'og:type', 'content' => 'website']);
$this->registerMetaTag(['property' => 'og:site_name', 'content' => 'AtmoTeam']);
$this->registerMetaTag(['property' => 'og:locale', 'content' => 'ru_RU']);

// Twitter Card
$this->registerMetaTag(['name' => 'twitter:card', 'content' => 'summary']);
$this->registerMetaTag(['name' => 'twitter:title', 'content' => $ogTitle]);
$this->registerMetaTag(['name' => 'twitter:description', 'content' => $ogDescription]);
$this->registerMetaTag(['name' => 'twitter:image', 'content' => $ogImage]);

// Canonical & Robots
$this->registerLinkTag(['rel' => 'canonical', 'href' => $ogUrl]);
$this->registerMetaTag(['name' => 'robots', 'content' => 'index, follow']);

$currentUser = Yii::$app->user->identity;
$navAvatar = $currentUser ? ($currentUser->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $currentUser->id) : null;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">

<head>
    <!-- Тема применяется мгновенно, ДО загрузки любых стилей -->
    <script>
        (function () {
            const theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
            if (document.body) {
                if (theme === 'dark') {
                    document.body.classList.add('dark-theme');
                } else {
                    document.body.classList.remove('dark-theme');
                }
            } else {
                document.addEventListener('DOMContentLoaded', function () {
                    if (theme === 'dark') {
                        document.body.classList.add('dark-theme');
                    } else {
                        document.body.classList.remove('dark-theme');
                    }
                });
            }
        })();
    </script>

    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= Html::encode($this->title) ?> | AtmoTeam</title>
    <?php $this->head() ?>

    <style>
        body {
            transition: background-color 0.2s ease;
        }
    </style>
</head>

<body>
    <?php $this->beginBody() ?>

    <!-- Core JS -->
    <?php $this->registerJsFile('@web/js/common.js'); ?>
    <?php $this->registerJsFile('@web/js/main.js'); ?>
    <?php $this->registerJsFile('@web/js/theme.js'); ?>

    <header class="header">
        <div class="header-container">
            <div class="header-brand">
                <a href="<?= Url::to(['feed/index']) ?>" class="brand-link">
                    <img src="<?= Yii::getAlias('@web/svg/logo.svg') ?>" alt="AtmoTeam" class="site-logo">
                    <span class="site-title">AtmoTeam</span>
                </a>
            </div>

            <!-- Кнопка меню (аватарка) для мобильных -->
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()" aria-label="Меню">
                <?php if (!Yii::$app->user->isGuest): ?>
                    <img src="<?= $navAvatar ?>" class="mobile-menu-avatar" alt="Меню">
                <?php else: ?>
                    <span class="mobile-menu-burger-icon">☰</span>
                <?php endif; ?>
            </button>

            <nav class="header-nav">
                <ul class="nav-list">
                    <li><a href="<?= Url::to(['feed/index']) ?>" class="nav-link">🏠 Лента</a></li>
                    <li><a href="<?= Url::to(['search/index']) ?>" class="nav-link">🔍 Поиск</a></li>

                    <?php if (!Yii::$app->user->isGuest): ?>
                        <?php
                        $unreadMessages = \app\models\Message::getUnreadCount(Yii::$app->user->id);
                        ?>
                        <li><a href="<?= Url::to(['story/index']) ?>" class="nav-link">📸 Истории</a></li>
                        <li>
                            <a href="<?= Url::to(['message/index']) ?>" class="nav-link">
                                💬 Сообщения
                                <?php if ($unreadMessages > 0): ?>
                                    <span class="nav-badge messages-badge"><?= $unreadMessages ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <!-- Кнопка уведомлений -->
                        <li class="nav-notification">
                            <button class="nav-notification-link" onclick="toggleNotifications(event)"
                                aria-label="Уведомления">
                                🔔
                                <span id="notification-badge" class="nav-badge" style="display: none;">0</span>
                            </button>
                        </li>
                        <li class="nav-dropdown">
                            <button class="nav-user-menu" onclick="toggleUserMenu()" aria-label="Меню пользователя">
                                <img src="<?= $navAvatar ?>" class="nav-avatar" alt="Аватар">
                                <span class="nav-dropdown-arrow">▼</span>
                            </button>
                            <div class="nav-dropdown-menu" id="user-menu">
                                <a href="<?= Url::to(['profile/view']) ?>" class="dropdown-item">👤 Мой профиль</a>
                                <a href="<?= Url::to(['profile/edit']) ?>" class="dropdown-item">✏️ Редактировать</a>
                                <?php if (Yii::$app->user->can('accessAdminPanel')): ?>
                                    <div class="dropdown-divider"></div>
                                    <a href="<?= Url::to(['admin/index']) ?>" class="dropdown-item admin-link">⚙️
                                        Админ-панель</a>
                                <?php endif; ?>
                                <div class="dropdown-divider"></div>
                                <?= Html::beginForm(['site/logout'], 'post', ['class' => 'dropdown-item logout-link-form']) ?>
                                <button type="submit" style="background: none; border: none; cursor: pointer; width: 100%; text-align: left; color: inherit; display: flex; align-items: center; gap: var(--space-2);">🚪 Выйти</button>
                                <?= Html::endForm() ?>
                            </div>
                        </li>
                    <?php else: ?>
                        <li><a href="<?= Url::to(['site/login']) ?>" class="nav-link btn-login">🔐 <span>Войти</span></a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Мобильное меню (вне header, поверх всего контента) -->
    <div class="mobile-menu-overlay" id="mobile-menu-overlay" onclick="closeMobileMenu()">
        <div class="mobile-menu-content" onclick="event.stopPropagation()">
            <div class="mobile-menu-header">
                <div class="mobile-menu-user">
                    <img src="<?= $navAvatar ?>" class="mobile-menu-avatar-large" alt="Аватар">
                    <span class="site-title">Меню</span>
                </div>
                <button class="mobile-menu-close" onclick="closeMobileMenu()">&times;</button>
            </div>
            <ul class="mobile-nav-list">
                <li><a href="<?= Url::to(['feed/index']) ?>" class="mobile-nav-link" onclick="closeMobileMenu()">🏠 Лента</a></li>
                <li><a href="<?= Url::to(['search/index']) ?>" class="mobile-nav-link" onclick="closeMobileMenu()">🔍 Поиск</a></li>
                <?php if (!Yii::$app->user->isGuest): ?>
                    <li><a href="<?= Url::to(['story/index']) ?>" class="mobile-nav-link" onclick="closeMobileMenu()">📸 Истории</a></li>
                    <li>
                        <a href="<?= Url::to(['message/index']) ?>" class="mobile-nav-link" onclick="closeMobileMenu()">
                            💬 Сообщения
                            <?php
                            $unreadMessages = \app\models\Message::getUnreadCount(Yii::$app->user->id);
                            if ($unreadMessages > 0): ?>
                                <span class="nav-badge messages-badge"><?= $unreadMessages ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <button class="mobile-nav-link mobile-notification-btn" onclick="toggleMobileNotifications()" style="background: none; border: none; cursor: pointer; width: 100%; text-align: left; color: inherit; display: flex; align-items: center; gap: var(--space-3); padding: var(--space-3) var(--space-4); font-size: var(--text-base);">
                            🔔 Уведомления
                            <span id="mobile-notification-badge" class="nav-badge" style="display: none;">0</span>
                        </button>
                    </li>
                    <li><a href="<?= Url::to(['profile/view']) ?>" class="mobile-nav-link" onclick="closeMobileMenu()">👤 Мой профиль</a></li>
                    <li><a href="<?= Url::to(['profile/edit']) ?>" class="mobile-nav-link" onclick="closeMobileMenu()">✏️ Редактировать</a></li>
                    <?php if (Yii::$app->user->can('accessAdminPanel')): ?>
                        <li><a href="<?= Url::to(['admin/index']) ?>" class="mobile-nav-link" onclick="closeMobileMenu()">⚙️ Админ-панель</a></li>
                    <?php endif; ?>
                    <li>
                        <?= Html::beginForm(['site/logout'], 'post', ['class' => 'mobile-nav-link logout-link']) ?>
                        <button type="submit" onclick="closeMobileMenu()" style="background: none; border: none; cursor: pointer; width: 100%; text-align: left; color: inherit; display: flex; align-items: center; gap: var(--space-3);">🚪 Выйти</button>
                        <?= Html::endForm() ?>
                    </li>
                <?php else: ?>
                    <li><a href="<?= Url::to(['site/login']) ?>" class="mobile-nav-link" onclick="closeMobileMenu()">🔐 Войти</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <main class="main">
        <div class="main-container">
            <?php if (!empty($this->params['breadcrumbs'])): ?>
                <div class="breadcrumbs">
                    <?php foreach ($this->params['breadcrumbs'] as $i => $crumb): ?>
                        <?php if ($i > 0): ?><span class="breadcrumb-separator">›</span><?php endif; ?>
                        <?php if (isset($crumb['url'])): ?>
                            <a href="<?= Html::encode($crumb['url']) ?>"
                                class="breadcrumb-link"><?= Html::encode($crumb['label'] ?? '') ?></a>
                        <?php else: ?>
                            <span class="breadcrumb-current"><?= Html::encode($crumb['label'] ?? $crumb) ?></span>
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
        <div class="notification-dropdown" id="notification-dropdown">
            <div class="notification-header">
                <h3>🔔 Уведомления</h3>
                <button class="btn-mark-all-read" onclick="markAllNotificationsRead()">Прочитать все</button>
            </div>
            <div class="notification-list" id="notification-list">
                <div class="notification-empty">Загрузка...</div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Delete Modal -->
    <?= $this->render('//post/_delete_modal') ?>

    <!-- Post Modal -->
    <div id="post-modal" class="modal-overlay hidden">
        <div class="modal-content post-modal-content">
            <div class="modal-header">
                <h3 class="modal-title">📝 Пост</h3>
                <button class="modal-close" onclick="closePostModal()">&times;</button>
            </div>
            <div id="post-modal-body" class="post-modal-body">
                <div class="loading-spinner">Загрузка...</div>
            </div>
        </div>
    </div>

    <!-- Generic Modal -->
    <?= $this->render('//layouts/_generic_modal') ?>
    <?= $this->render('//story/_upload_modal') ?>

    <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage(); ?>