<?php
$this->title = 'Админ-панель';
?>

<div class="admin-container">
    <div class="admin-header">
        <div class="admin-header-content">
            <h1 class="admin-title">🛠️ Админ-панель</h1>
            <p class="admin-subtitle">Управление сайтом</p>
        </div>
    </div>

    <!-- Навигация админки -->
    <nav class="admin-nav">
        <a href="<?= \yii\helpers\Url::to(['/admin']) ?>" class="admin-nav-item active">📊 Обзор</a>
        <a href="<?= \yii\helpers\Url::to(['/admin/users']) ?>" class="admin-nav-item">👥 Пользователи</a>
        <a href="<?= \yii\helpers\Url::to(['/admin/posts']) ?>" class="admin-nav-item">📝 Посты</a>
        <a href="<?= \yii\helpers\Url::to(['/admin/comments']) ?>" class="admin-nav-item">💬 Комментарии</a>
    </nav>

    <!-- Статистика -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['users'] ?></div>
                <div class="stat-label">Пользователи</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📝</div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['posts'] ?></div>
                <div class="stat-label">Посты</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💬</div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['comments'] ?></div>
                <div class="stat-label">Комментарии</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🔔</div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['notifications'] ?></div>
                <div class="stat-label">Уведомления</div>
            </div>
        </div>
    </div>

    <!-- Последние пользователи -->
    <div class="admin-section">
        <div class="section-header">
            <h2 class="section-title">👥 Последние пользователи</h2>
            <div class="section-stats">
                <span class="stat-item">Всего: <?= count($recentUsers) ?></span>
            </div>
        </div>
        <div class="recent-users">
            <?php foreach ($recentUsers as $user): ?>
                <div class="recent-user-item fade-in">
                    <img class="user-avatar-small" 
                         src="<?= $user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $user->id ?>" 
                         alt="<?= \yii\helpers\Html::encode($user->username) ?>">
                    <div class="user-details">
                        <div class="username"><?= \yii\helpers\Html::encode($user->username) ?></div>
                        <div class="user-bio"><?= \yii\helpers\Html::encode($user->bio ?? 'Нет биографии') ?></div>
                    </div>
                    <div class="user-time">
                        <?= date('d.m.Y H:i', $user->created_at) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Последние посты -->
    <div class="admin-section">
        <div class="section-header">
            <h2 class="section-title">📝 Последние посты</h2>
            <div class="section-stats">
                <span class="stat-item">Всего: <?= count($recentPosts) ?></span>
            </div>
        </div>
        <div class="recent-posts">
            <?php foreach ($recentPosts as $post): ?>
                <div class="recent-post-item fade-in">
                    <div class="post-info">
                        <div class="post-title"><?= \yii\helpers\Html::encode($post->content) ?></div>
                        <div class="post-meta">
                            <span>👤 <?= \yii\helpers\Html::encode($post->user->username ?? 'Удалён') ?></span>
                            <span>🕐 <?= date('d.m.Y H:i', $post->created_at) ?></span>
                        </div>
                    </div>
                    <div class="user-actions">
                        <a href="<?= \yii\helpers\Url::to(['/post/view', 'id' => $post->id]) ?>" 
                           class="btn-action btn-view"
                           target="_blank"
                           title="Посмотреть пост">
                            👁️
                        </a>
                        <button type="button" class="btn-action btn-delete-post" 
                                data-post-id="<?= $post->id ?>"
                                title="Удалить пост">
                            🗑️
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>