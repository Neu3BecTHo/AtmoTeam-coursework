<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

$this->title = Yii::t('app','Управление ролями');
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="role-index admin-container">
    <div class="admin-header">
        <h1 class="admin-title"><?= Html::encode($this->title) ?></h1>
        <p class="admin-subtitle"><?= Yii::t('app','Управление ролями пользователей и их правами') ?></p>
    </div>

    <div class="admin-section">
        <div class="section-header">
            <h2 class="section-title"><?= Yii::t('app','Список ролей') ?></h2>
            <div class="section-actions">
                <?= Html::a('+ ' . Yii::t('app','Создать роль'), ['create'], ['class' => 'btn btn-primary']) ?>
            </div>
        </div>

        <?php Pjax::begin(['id' => 'roles-pjax', 'timeout' => 5000]); ?>
        
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'tableOptions' => ['class' => 'admin-table data-table'],
            'layout' => "{items}\n<div class=\"pagination-container\">{pager}</div>",
            'columns' => [
                [
                    'class' => 'yii\grid\SerialColumn',
                    'headerOptions' => ['style' => 'width: 50px;'],
                    'contentOptions' => ['class' => 'text-center'],
                ],
                
                [
                    'attribute' => 'name',
                    'label' => Yii::t('app','Название'),
                    'format' => 'raw',
                    'value' => function ($model) {
                        return Html::a(
                            Html::encode($model->name), 
                            ['view', 'id' => $model->id],
                            ['class' => 'role-link']
                        );
                    },
                ],
                
                [
                    'attribute' => 'description',
                    'label' => Yii::t('app','Описание'),
                    'format' => 'text',
                    'filterInputOptions' => ['class' => 'form-input', 'placeholder' => Yii::t('app','Поиск...')],
                ],
                
                [
                    'attribute' => 'is_system',
                    'label' => Yii::t('app','Тип'),
                    'format' => 'raw',
                    'filter' => [0 => Yii::t('app','Пользовательская'), 1 => Yii::t('app','Системная')],
                    'value' => function ($model) {
                        return $model->is_system 
                            ? '<span class="badge badge-warning">🔒 ' . Yii::t('app','Системная') . '</span>' 
                            : '<span class="badge badge-info">👤 ' . Yii::t('app','Пользовательская') . '</span>';
                    },
                ],
                
                [
                    'attribute' => 'created_at',
                    'label' => Yii::t('app','Создана'),
                    'format' => 'datetime',
                    'headerOptions' => ['style' => 'width: 180px;'],
                ],
                
                [
                    'class' => 'yii\grid\ActionColumn',
                    'header' => Yii::t('app','Действия'),
                    'headerOptions' => ['style' => 'width: 120px;'],
                    'template' => '{view} {update} {delete}',
                    'buttons' => [
                        'view' => function ($url, $model) {
                            return Html::a(
                                '👁️', 
                                $url, 
                                [
                                    'title' => Yii::t('app','Просмотр'),
                                    'class' => 'action-btn action-btn-view',
                                ]
                            );
                        },
                        'update' => function ($url, $model) {
                            if ($model->is_system) {
                                return '<span class="action-disabled" title="' . Yii::t('app','Системную роль нельзя редактировать') . '">✏️</span>';
                            }
                            return Html::a(
                                '✏️', 
                                $url, 
                                [
                                    'title' => Yii::t('app','Редактировать'),
                                    'class' => 'action-btn action-btn-edit',
                                ]
                            );
                        },
                        'delete' => function ($url, $model) {
                            if ($model->is_system) {
                                return '<span class="action-disabled" title="' . Yii::t('app','Системную роль нельзя удалить') . '">🗑️</span>';
                            }
                            return Html::a(
                                '🗑️', 
                                $url, 
                                [
                                    'title' => Yii::t('app','Удалить'),
                                    'class' => 'action-btn action-btn-delete',
                                    'data' => [
                                        'confirm' => Yii::t('app','Вы уверены, что хотите удалить эту роль? Это действие нельзя отменить.'),
                                        'method' => 'post',
                                    ],
                                ]
                            );
                        },
                    ],
                ],
            ],
        ]); ?>
        
        <?php Pjax::end(); ?>
    </div>
</div>