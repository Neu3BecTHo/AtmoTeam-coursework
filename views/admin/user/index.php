<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/**
 * @var \yii\web\View $this
 * @var \yii\data\ActiveDataProvider $dataProvider
 */

$this->title = Yii::t('app','Управление пользователями');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app','Админ панель'), 'url' => ['/admin']];
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">👥 <?= Html::encode($this->title) ?></h1>
        <p class="admin-subtitle"><?= Yii::t('app','Просмотр, создание и управление пользователями системы') ?></p>
    </div>

    <div class="admin-content">
        <div class="admin-actions">
            <?= Html::a('➕ ' . Yii::t('app','Создать пользователя'), ['create'], ['class' => 'btn btn-primary']) ?>
            <?= Html::button('🗑️ ' . Yii::t('app','Удалить выбранные'), [
                'class' => 'btn btn-danger bulk-delete-btn',
                'id' => 'bulk-delete',
                'style' => 'display:none;'
            ]) ?>
        </div>

        <?php Pjax::begin(['id' => 'users-pjax', 'timeout' => 5000]); ?>
        
        <div class="admin-card">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'tableOptions' => ['class' => 'admin-table data-table'],
                'layout' => "{items}\n<div class=\"pagination-container\">{pager}</div>",
                'columns' => [
                    [
                        'class' => 'yii\grid\CheckboxColumn',
                        'checkboxOptions' => function ($model) {
                            return [
                                'value' => $model->id,
                                'class' => 'user-checkbox',
                                'data-username' => $model->username,
                            ];
                        },
                        'headerOptions' => ['style' => 'width: 40px;'],
                    ],
                    
                    [
                        'attribute' => 'id',
                        'label' => 'ID',
                        'headerOptions' => ['style' => 'width: 60px;'],
                        'contentOptions' => ['class' => 'text-center'],
                    ],
                    
                    [
                        'attribute' => 'username',
                        'label' => Yii::t('app','Имя пользователя'),
                        'format' => 'raw',
                        'value' => function ($model) {
                            $avatar = $model->getAvatarUrl();
                            return Html::a(
                                '<img src="' . Html::encode($avatar) . '" class="user-avatar-tiny" alt="avatar"> ' . Html::encode($model->username),
                                ['view', 'id' => $model->id],
                                ['class' => 'user-link']
                            );
                        },
                    ],
                    
                    [
                        'attribute' => 'email',
                        'label' => 'Email',
                        'format' => 'email',
                        'contentOptions' => ['class' => 'user-email'],
                    ],
                    
                    [
                        'attribute' => 'status',
                        'label' => Yii::t('app','Статус'),
                        'filter' => [10 => Yii::t('app','Активен'), 0 => Yii::t('app','Заблокирован')],
                        'format' => 'raw',
                        'value' => function ($model) {
                            if ($model->username === 'admin') {
                                return '<span class="badge badge-super-admin">👑 ' . Yii::t('app','Супер админ') . '</span>';
                            }
                            if ($model->status == 10) {
                                return '<span class="badge badge-success">✅ ' . Yii::t('app','Активен') . '</span>';
                            }
                            return '<span class="badge badge-danger">🔒 ' . Yii::t('app','Заблокирован') . '</span>';
                        },
                    ],
                    
                    [
                        'attribute' => 'created_at',
                        'label' => Yii::t('app','Дата регистрации'),
                        'format' => 'datetime',
                        'filter' => false,
                        'headerOptions' => ['style' => 'width: 180px;'],
                    ],
                    
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => Yii::t('app','Действия'),
                        'headerOptions' => ['style' => 'width: 140px;'],
                        'template' => '{view} {update} {toggle} {delete}',
                        'buttons' => [
                            'view' => function ($url, $model) {
                                return Html::a('👁️', $url, [
                                    'title' => Yii::t('app','Просмотр'),
                                    'class' => 'action-btn action-btn-view',
                                    'data-pjax' => 0,
                                ]);
                            },
                            'update' => function ($url, $model) {
                                if ($model->username === 'admin') return '';
                                return Html::a('✏️', $url, [
                                    'title' => Yii::t('app','Редактировать'),
                                    'class' => 'action-btn action-btn-edit',
                                    'data-pjax' => 0,
                                ]);
                            },
                            'toggle' => function ($url, $model) {
                                if ($model->username === 'admin') return '';
                                $icon = $model->status == 10 ? '🔒' : '🔓';
                                $title = $model->status == 10 ? Yii::t('app','Заблокировать') : Yii::t('app','Активировать');
                                $class = $model->status == 10 ? 'action-btn action-btn-block' : 'action-btn action-btn-unblock';
                                return Html::a($icon, ['toggle-status', 'id' => $model->id], [
                                    'title' => $title,
                                    'class' => $class,
                                    'data-pjax' => 0,
                                ]);
                            },
                            'delete' => function ($url, $model) {
                                if ($model->username === 'admin') return '';
                                $confirm = Yii::t('app','Вы уверены, что хотите удалить пользователя {username} и все связанные с ним данные?', ['username' => Html::encode($model->username)]);
                                return Html::beginForm(['delete', 'id' => $model->id], 'post', [
                                    'data-confirm' => $confirm,
                                    'class' => 'delete-form',
                                    'style' => 'display: inline;',
                                ]) . 
                                Html::button('🗑️', [
                                    'type' => 'submit',
                                    'title' => Yii::t('app','Удалить'),
                                    'class' => 'action-btn action-btn-delete',
                                ]) .
                                Html::endForm();
                            },
                        ],
                    ],
                ],
            ]); ?>
        </div>
        
        <?php Pjax::end(); ?>
    </div>
</div>

<!-- Bulk Actions Modal -->
<div id="bulkActionsModal" class="modal bulk-modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>📦 <?= Yii::t('app','Массовые действия') ?></h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p><?= Yii::t('app','Выберите действие для {n} выбранных пользователей:', ['n' => '<strong id="selected-count">0</strong>']) ?></p>
            <div class="bulk-actions-options">
                <label class="bulk-action-option">
                    <input type="radio" name="bulk_action" value="activate">
                    <span>✅ <?= Yii::t('app','Активировать') ?></span>
                </label>
                <label class="bulk-action-option">
                    <input type="radio" name="bulk_action" value="deactivate">
                    <span>🔒 <?= Yii::t('app','Заблокировать') ?></span>
                </label>
                <label class="bulk-action-option">
                    <input type="radio" name="bulk_action" value="delete">
                    <span>🗑️ <?= Yii::t('app','Удалить') ?></span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelBulk"><?= Yii::t('app','Отмена') ?></button>
            <button type="button" class="btn btn-danger" id="confirmBulk"><?= Yii::t('app','Выполнить') ?></button>
        </div>
    </div>
</div>

<?php
$csrfToken = Yii::$app->request->csrfToken;
$bulkUrl = \yii\helpers\Url::to(['/admin/user/bulk-actions']);
$script = <<<JS
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    const bulkDeleteBtn = document.getElementById('bulk-delete');
    const bulkModal = document.getElementById('bulkActionsModal');
    const cancelBtn = document.getElementById('cancelBulk');
    const confirmBtn = document.getElementById('confirmBulk');
    const closeBtn = bulkModal.querySelector('.modal-close');
    const selectedCountSpan = document.getElementById('selected-count');

    function updateBulkButton() {
        const checked = document.querySelectorAll('.user-checkbox:checked');
        const count = checked.length;
        bulkDeleteBtn.style.display = count > 0 ? 'inline-flex' : 'none';
        if (selectedCountSpan) selectedCountSpan.textContent = count;
    }

    function openModal() {
        bulkModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        bulkModal.style.display = 'none';
        document.body.style.overflow = '';
    }

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkButton);
    });

    if (bulkDeleteBtn) bulkDeleteBtn.addEventListener('click', openModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            const checked = document.querySelectorAll('.user-checkbox:checked');
            const action = document.querySelector('input[name="bulk_action"]:checked');
            
            if (!action) {
                alert('⚠️ Выберите действие');
                return;
            }

            const ids = Array.from(checked).map(cb => cb.value);
            const actionValue = action.value;
            
            const confirmText = {
                'activate': '<?= Yii::t('app','активировать выбранных пользователей') ?>',
                'deactivate': '<?= Yii::t('app','заблокировать выбранных пользователей') ?>',
                'delete': '<?= Yii::t('app','УДАЛИТЬ выбранных пользователей (это действие нельзя отменить)') ?>'
            };
            
            if (!confirm(`<?= Yii::t('app','Вы уверены, что хотите {action}?') ?>`.replace('{action}', confirmText[actionValue]))) {
                return;
            }

            const formData = new FormData();
            formData.append('action', actionValue);
            ids.forEach(id => formData.append('ids[]', id));
            formData.append('_csrf', '$csrfToken');

            fetch('$bulkUrl', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('❌ <?= Yii::t('app','Ошибка') ?>: ' + (data.error || '<?= Yii::t('app','Неизвестная ошибка') ?>'));
                }
            })
            .catch(error => {
                alert('❌ <?= Yii::t('app','Произошла ошибка при выполнении запроса') ?>');
            });
            
            closeModal();
        });
    }

    window.addEventListener('click', function(event) {
        if (event.target === bulkModal) {
            closeModal();
        }
    });
});
JS;
$this->registerJs($script);
?>