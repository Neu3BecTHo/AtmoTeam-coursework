<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('app','Пользователи - Админ-панель');

/**
 * @var \app\models\User[] $users
 */

// Подсчёт статистики
$adminCount = 0;
$blockedCount = 0;
$privateCount = 0;
$activeCount = 0;

foreach ($users as $user) {
    if ($user->username === 'admin') {
        $adminCount++;
    } elseif ($user->is_blocked ?? false) {
        $blockedCount++;
    } elseif ($user->is_private ?? false) {
        $privateCount++;
    } else {
        $activeCount++;
    }
}

?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">👥 <?= Yii::t('app','Управление пользователями') ?></h1>
        <p class="admin-subtitle"><?= Yii::t('app','Всего пользователей: {n}', ['n' => number_format(count($users))]) ?></p>
        <?= Html::a('← ' . Yii::t('app','Назад'), ['/admin/index'], ['class' => 'btn-back']) ?>
    </div>

    <!-- Навигация админки -->
    <nav class="admin-nav">
        <a href="<?= Url::to(['/admin']) ?>" class="admin-nav-item">📊 <?= Yii::t('app','Обзор') ?></a>
        <a href="<?= Url::to(['/admin/users']) ?>" class="admin-nav-item active">👥 <?= Yii::t('app','Пользователи') ?></a>
        <a href="<?= Url::to(['/admin/posts']) ?>" class="admin-nav-item">📝 <?= Yii::t('app','Посты') ?></a>
        <a href="<?= Url::to(['/admin/comments']) ?>" class="admin-nav-item">💬 <?= Yii::t('app','Комментарии') ?></a>
    </nav>

    <!-- Быстрая статистика -->
    <div class="quick-stats">
        <div class="quick-stat active"><span>✅ <?= Yii::t('app','Активных') ?></span><strong><?= $activeCount ?></strong></div>
        <div class="quick-stat blocked"><span>🔒 <?= Yii::t('app','Заблокированных') ?></span><strong><?= $blockedCount ?></strong></div>
        <div class="quick-stat private"><span>🔐 <?= Yii::t('app','Закрытых') ?></span><strong><?= $privateCount ?></strong></div>
        <?php if ($adminCount > 0): ?>
            <div class="quick-stat admin"><span>👑 <?= Yii::t('app','Администраторов') ?></span><strong><?= $adminCount ?></strong></div>
        <?php endif; ?>
    </div>

    <!-- Таблица пользователей -->
    <div class="admin-section">
            <div class="section-header">
            <h3 class="section-title">👥 <?= Yii::t('app','Список пользователей') ?></h3>
            <div class="section-actions">
                <input type="text" id="userSearch" class="search-input" placeholder="<?= Yii::t('app','🔎 Поиск по имени или email...') ?>"
                    style="width: 250px;">
                <button class="btn-refresh" onclick="location.reload()"><?= Yii::t('app','🔄 Обновить') ?></button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="admin-table" id="users-table">
                <thead>
                    <tr>
                        <th>👤 <?= Yii::t('app','Пользователь') ?></th>
                        <th>📧 <?= Yii::t('app','Email') ?></th>
                        <th>📅 <?= Yii::t('app','Регистрация') ?></th>
                        <th>📊 <?= Yii::t('app','Статус') ?></th>
                        <th>📝 <?= Yii::t('app','Посты') ?></th>
                        <th>👥 <?= Yii::t('app','Подписчики') ?></th>
                        <th>⚙️ <?= Yii::t('app','Действия') ?></th>
                    </tr>
                </thead>
                <tbody id="users-tbody">
                    <?php foreach ($users as $user):
                        $postsCount = $user->getPosts()->count();
                        $followersCount = $user->getFollowers()->count();
                        ?>
                        <tr class="user-row" data-user-id="<?= $user->id ?>"
                            data-username="<?= Html::encode(mb_strtolower($user->username)) ?>"
                            data-email="<?= Html::encode(mb_strtolower($user->email)) ?>"
                            data-status="<?= $user->username === 'admin' ? 'admin' : (($user->is_blocked ?? false) ? 'blocked' : (($user->is_private ?? false) ? 'private' : 'active')) ?>">

                            <!-- Пользователь -->
                            <td>
                                <div class="user-info-cell">
                                    <a href="<?= Url::to(['/profile/view', 'id' => $user->id]) ?>" target="_blank">
                                        <img class="user-avatar-small"
                                            src="<?= $user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $user->id ?>"
                                            alt="<?= Html::encode($user->username) ?>">
                                    </a>
                                    <div class="user-details">
                                        <div class="username"><?= Html::encode($user->username) ?></div>
                                        <div class="user-id">ID: <?= $user->id ?></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Email -->
                            <td>
                                <div class="email-cell">
                                    <?= Html::encode($user->email) ?>
                                </div>
                            </td>

                            <!-- Дата регистрации -->
                            <td>
                                <div class="date-cell"><?= date('d.m.Y H:i', $user->created_at) ?></div>
                            </td>

                            <!-- Статус -->
                            <td>
                                <div class="status-cell">
                                    <?php if ($user->username === 'admin'): ?>
                                        <span class="status-badge admin">👑 <?= Yii::t('app','Администратор') ?></span>
                                    <?php elseif ($user->is_blocked ?? false): ?>
                                        <span class="status-badge blocked">🔒 <?= Yii::t('app','Заблокирован') ?></span>
                                    <?php elseif ($user->is_private ?? false): ?>
                                        <span class="status-badge private">🔐 <?= Yii::t('app','Закрытый') ?></span>
                                    <?php else: ?>
                                        <span class="status-badge active">✅ <?= Yii::t('app','Активен') ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Посты -->
                            <td>
                                <div class="stats-cell"><?= number_format($postsCount) ?></div>
                            </td>

                            <!-- Подписчики -->
                            <td>
                                <div class="stats-cell"><?= number_format($followersCount) ?></div>
                            </td>

                            <!-- Действия -->
                            <td>
                                <div class="user-actions">
                                    <a href="<?= Url::to(['/profile/view', 'id' => $user->id]) ?>" class="action-btn view"
                                        target="_blank" title="<?= Yii::t('app','Посмотреть профиль') ?>">👁️</a>

                                    <?php if ($user->username !== 'admin'): ?>
                                        <?php if ($user->is_blocked ?? false): ?>
                                            <button type="button" class="action-btn unblock" data-user-id="<?= $user->id ?>"
                                                data-username="<?= Html::encode($user->username) ?>"
                                                title="<?= Yii::t('app','Разблокировать') ?>">🔓</button>
                                        <?php else: ?>
                                            <button type="button" class="action-btn block" data-user-id="<?= $user->id ?>"
                                                data-username="<?= Html::encode($user->username) ?>"
                                                title="<?= Yii::t('app','Заблокировать') ?>">🔒</button>
                                        <?php endif; ?>

                                        <button type="button" class="action-btn delete" data-user-id="<?= $user->id ?>"
                                            data-username="<?= Html::encode($user->username) ?>"
                                            title="<?= Yii::t('app','Удалить пользователя') ?>">🗑️</button>
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

<?php
$blockUserUrl = Url::to(['/admin/block-user']);
$unblockUserUrl = Url::to(['/admin/unblock-user']);
$deleteUserUrl = Url::to(['/admin/delete-user']);
$csrfToken = Yii::$app->request->csrfToken;

$script = <<<JS
function postWithCsrf(url, data) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify(data)
    });
}

function searchUsers() {
    const searchTerm = document.getElementById('userSearch')?.value.toLowerCase() || '';
    document.querySelectorAll('.user-row').forEach(row => {
        const username = row.dataset.username || '';
        const email = row.dataset.email || '';
        const matches = searchTerm === '' || username.includes(searchTerm) || email.includes(searchTerm);
        row.style.display = matches ? '' : 'none';
    });
}

async function blockUser(userId, username) {
    if (!confirm(`Заблокировать пользователя \${username}?`)) return;
    try {
        const response = await postWithCsrf('$blockUserUrl', { user_id: userId });
        const result = await response.json();
        if (result.success) {
            alert('Пользователь заблокирован');
            location.reload();
        } else {
            alert(result.error || 'Ошибка блокировки');
        }
    } catch (error) {
        alert('Ошибка блокировки');
    }
}

async function unblockUser(userId, username) {
    if (!confirm(`Разблокировать пользователя \${username}?`)) return;
    try {
        const response = await postWithCsrf('$unblockUserUrl', { user_id: userId });
        const result = await response.json();
        if (result.success) {
            alert('Пользователь разблокирован');
            location.reload();
        } else {
            alert(result.error || 'Ошибка разблокировки');
        }
    } catch (error) {
        alert('Ошибка разблокировки');
    }
}

async function deleteUser(userId, username) {
    if (!confirm(`Удалить пользователя \${username}? Все его данные будут удалены безвозвратно!`)) return;
    try {
        const response = await postWithCsrf('$deleteUserUrl', { user_id: userId });
        const result = await response.json();
        if (result.success) {
            alert('Пользователь удалён');
            const row = document.querySelector('.user-row[data-user-id=\"' + userId + '\"]');
            if (row) row.remove();
            searchUsers();
        } else {
            alert(result.error || 'Ошибка удаления');
        }
    } catch (error) {
        alert('Ошибка удаления');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('userSearch');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(searchUsers, 300);
        });
    }
});

document.addEventListener('click', function(e) {
    const blockBtn = e.target.closest('.action-btn.block');
    if (blockBtn) {
        const userId = blockBtn.dataset.userId;
        const username = blockBtn.dataset.username;
        if (userId && username) blockUser(userId, username);
        return;
    }
    
    const unblockBtn = e.target.closest('.action-btn.unblock');
    if (unblockBtn) {
        const userId = unblockBtn.dataset.userId;
        const username = unblockBtn.dataset.username;
        if (userId && username) unblockUser(userId, username);
        return;
    }
    
    const deleteBtn = e.target.closest('.action-btn.delete');
    if (deleteBtn) {
        const userId = deleteBtn.dataset.userId;
        const username = deleteBtn.dataset.username;
        if (userId && username) deleteUser(userId, username);
        return;
    }
});

window.blockUser = blockUser;
window.unblockUser = unblockUser;
window.deleteUser = deleteUser;
window.searchUsers = searchUsers;
window.postWithCsrf = postWithCsrf;
JS;
$this->registerJs($script);
?>