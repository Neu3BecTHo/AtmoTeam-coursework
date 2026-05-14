<?php
$this->title = 'Пользователи - Админ-панель';
?>

<div class="admin-container">
    <div class="admin-header">
        <div class="admin-header-content">
            <h1 class="admin-title">👥 Управление пользователями</h1>
            <p class="admin-subtitle">Всего пользователей: <?= count($users) ?></p>
            <a href="<?= \yii\helpers\Url::to(['/admin/index']) ?>" class="btn-back">← Назад</a>
        </div>
    </div>

    <!-- Навигация админки -->
    <nav class="admin-nav">
        <a href="<?= \yii\helpers\Url::to(['/admin']) ?>" class="admin-nav-item">📊 Обзор</a>
        <a href="<?= \yii\helpers\Url::to(['/admin/users']) ?>" class="admin-nav-item active">👥 Пользователи</a>
        <a href="<?= \yii\helpers\Url::to(['/admin/posts']) ?>" class="admin-nav-item">📝 Посты</a>
        <a href="<?= \yii\helpers\Url::to(['/admin/comments']) ?>" class="admin-nav-item">💬 Комментарии</a>
    </nav>

    <div class="admin-section">
        <div class="section-header">
            <h2 class="section-title">👥 Список пользователей</h2>
            <div class="section-stats">
                <span class="stat-item">Всего пользователей: <?= count($users) ?></span>
            </div>
        </div>

        <div class="users-table">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Пользователь</th>
                        <th>Email</th>
                        <th>Регистрация</th>
                        <th>Статус</th>
                        <th>Посты</th>
                        <th>Подписчики</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr class="user-row" data-user-id="<?= $user->id ?>">
                            <td>
                                <div class="user-info-cell">
                                    <img class="user-avatar-small" 
                                         src="<?= $user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $user->id ?>" 
                                         alt="<?= \yii\helpers\Html::encode($user->username) ?>">
                                    <div class="user-details">
                                        <div class="username"><?= \yii\helpers\Html::encode($user->username) ?></div>
                                        <div class="user-bio"><?= \yii\helpers\Html::encode($user->bio ?: 'Нет биографии') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="email-cell">
                                    <?= \yii\helpers\Html::encode($user->email) ?>
                                    <?php if (isset($user->email_verified) && $user->email_verified): ?>
                                        <span class="verified-badge">✓</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="date-cell">
                                    <?= date('d.m.Y H:i', $user->created_at) ?>
                                </div>
                            </td>
                            <td>
                                <div class="status-cell">
                                    <?php if ($user->username === 'admin'): ?>
                                        <span class="status-badge admin">Админ</span>
                                    <?php elseif (isset($user->is_blocked) && $user->is_blocked): ?>
                                        <span class="status-badge blocked">Заблокирован</span>
                                    <?php elseif (isset($user->is_private) && $user->is_private): ?>
                                        <span class="status-badge private">Закрытый</span>
                                    <?php else: ?>
                                        <span class="status-badge active">Активен</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="stats-cell">
                                    <?= \app\models\Post::find()->where(['user_id' => $user->id])->count() ?>
                                </div>
                            </td>
                            <td>
                                <div class="stats-cell">
                                    <?= \app\models\Follow::find()->where(['following_id' => $user->id])->count() ?>
                                </div>
                            </td>
                            <td>
                                <div class="user-actions">
                                    <a href="<?= \yii\helpers\Url::to(['/profile/view', 'id' => $user->id]) ?>" 
                                       class="btn-action btn-view"
                                       target="_blank"
                                       onclick="event.stopPropagation()"
                                       title="Посмотреть профиль">
                                        👁️
                                    </a>
                                    
                                    <?php if (isset($user->is_blocked) && $user->is_blocked): ?>
                                        <button type="button" class="btn-action btn-unblock" 
                                                data-user-id="<?= $user->id ?>"
                                                onclick="event.stopPropagation()"
                                                title="Разблокировать">
                                            🔓
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn-action btn-block" 
                                                data-user-id="<?= $user->id ?>"
                                                onclick="event.stopPropagation()"
                                                title="Заблокировать">
                                            🔒
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($user->username !== 'admin'): ?>
                                        <button type="button" class="btn-action btn-delete-user" 
                                                data-user-id="<?= $user->id ?>"
                                                onclick="event.stopPropagation()"
                                                title="Удалить пользователя">
                                            🗑️
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

