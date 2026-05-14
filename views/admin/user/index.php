<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;





$this->title = 'Управление пользователями';
$this->params['breadcrumbs'][] = ['label' => 'Админ панель', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title">👥 Управление пользователями</h1>
        <p class="admin-subtitle">Просмотр, создание и управление пользователями системы</p>
    </div>

    <div class="admin-content">
        <div class="admin-actions">
            <?= Html::a('➕ Создать пользователя', ['create'], ['class' => 'btn-primary']) ?>
            <?= Html::button('🗑️ Удалить выбранные', ['class' => 'btn-danger', 'id' => 'bulk-delete', 'style' => 'display:none;']) ?>
        </div>

        <?php Pjax::begin(); ?>
        <div class="admin-card">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => [
                    [
                        'class' => 'yii\grid\CheckboxColumn',
                        'checkboxOptions' => function ($model) {
                            return ['value' => $model->id, 'class' => 'user-checkbox'];
                        },
                    ],
                    ['attribute' => 'id', 'label' => 'ID', 'headerOptions' => ['width' => '60px']],
                    [
                        'attribute' => 'username',
                        'label' => 'Имя пользователя',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return Html::a(Html::encode($model->username), ['view', 'id' => $model->id]);
                        },
                    ],
                    [
                        'attribute' => 'email',
                        'label' => 'Email',
                        'format' => 'email',
                    ],
                    [
                        'attribute' => 'status',
                        'label' => 'Статус',
                        'filter' => [
                            10 => 'Активен',
                            0 => 'Заблокирован',
                        ],
                        'format' => 'raw',
                        'value' => function ($model) {
                            if ($model->username === 'admin') {
                                return '<span class="badge badge-super-admin">Супер админ</span>';
                            }
                            $class = $model->status == 10 ? 'badge-success' : 'badge-danger';
                            $text = $model->status == 10 ? 'Активен' : 'Заблокирован';
                            return Html::tag('span', $text, ['class' => "badge $class"]);
                        },
                    ],
                    [
                        'attribute' => 'created_at',
                        'label' => 'Дата регистрации',
                        'format' => 'datetime',
                        'filter' => false,
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => 'Действия',
                        'template' => '{view} {update} {toggle} {delete}',
                        'buttons' => [
                            'toggle' => function ($url, $model, $key) {
                                if ($model->username === 'admin') {
                                    return '';
                                }
                                $icon = $model->status == 10 ? '🔒' : '🔓';
                                $title = $model->status == 10 ? 'Заблокировать' : 'Активировать';
                                return Html::a($icon, ['toggle-status', 'id' => $model->id], [
                                    'title' => $title,
                                    'data-pjax' => 0,
                                    'class' => 'action-btn',
                                ]);
                            },
                            'delete' => function ($url, $model, $key) {
                                if ($model->username === 'admin') {
                                    return '';
                                }
                                return Html::a('🗑️', ['delete', 'id' => $model->id], [
                                    'title' => 'Удалить',
                                    'data-confirm' => 'Вы уверены, что хотите удалить этого пользователя и все связанные с ним данные?',
                                    'data-method' => 'post',
                                    'data-pjax' => 0,
                                    'class' => 'action-btn text-danger',
                                ]);
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
<div id="bulkActionsModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Массовые действия</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p>Выберите действие для выбранных пользователей:</p>
            <div class="bulk-actions-options">
                <label>
                    <input type="radio" name="bulk_action" value="activate">
                    Активировать
                </label>
                <label>
                    <input type="radio" name="bulk_action" value="deactivate">
                    Заблокировать
                </label>
                <label>
                    <input type="radio" name="bulk_action" value="delete">
                    Удалить
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" id="cancelBulk">Отмена</button>
            <button type="button" class="btn-danger" id="confirmBulk">Выполнить</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    const bulkDeleteBtn = document.getElementById('bulk-delete');
    const bulkModal = document.getElementById('bulkActionsModal');
    const cancelBtn = document.getElementById('cancelBulk');
    const confirmBtn = document.getElementById('confirmBulk');

    function updateBulkButton() {
        const checked = document.querySelectorAll('.user-checkbox:checked');
        bulkDeleteBtn.style.display = checked.length > 0 ? 'inline-block' : 'none';
    }

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkButton);
    });

    bulkDeleteBtn.addEventListener('click', function() {
        bulkModal.style.display = 'block';
    });

    cancelBtn.addEventListener('click', function() {
        bulkModal.style.display = 'none';
    });

    confirmBtn.addEventListener('click', function() {
        const checked = document.querySelectorAll('.user-checkbox:checked');
        const action = document.querySelector('input[name="bulk_action"]:checked');
        
        if (!action) {
            alert('Выберите действие');
            return;
        }

        const ids = Array.from(checked).map(cb => cb.value);
        
        fetch('/admin/user/bulk-actions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: `action=${action.value}&ids=${ids.join(',')}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Ошибка: ' + data.error);
            }
        })
        .catch(error => {
            
            alert('Произошла ошибка');
        });
        
        bulkModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target === bulkModal) {
            bulkModal.style.display = 'none';
        }
    });
});
</script>
