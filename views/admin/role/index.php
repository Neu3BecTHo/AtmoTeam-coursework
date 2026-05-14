<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;





$this->title = 'Управление ролями';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="role-index">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <p>
        <?= Html::a('Создать роль', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php Pjax::begin(); ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            
            [
                'attribute' => 'name',
                'label' => 'Название',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::a(Html::encode($model->name), ['view', 'id' => $model->id]);
                },
            ],
            
            [
                'attribute' => 'description',
                'label' => 'Описание',
                'format' => 'text',
            ],
            
            [
                'attribute' => 'is_system',
                'label' => 'Тип',
                'format' => 'raw',
                'value' => function ($model) {
                    return $model->is_system ? 
                        '<span class="badge bg-warning">Системная</span>' : 
                        '<span class="badge bg-info">Пользовательская</span>';
                },
            ],
            
            [
                'attribute' => 'created_at',
                'label' => 'Создана',
                'format' => 'datetime',
            ],
            
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {delete}',
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a('<i class="fas fa-eye"></i>', $url, [
                            'title' => 'Просмотр',
                            'class' => 'btn btn-sm btn-info',
                        ]);
                    },
                    'update' => function ($url, $model) {
                        if ($model->is_system) {
                            return '';
                        }
                        return Html::a('<i class="fas fa-edit"></i>', $url, [
                            'title' => 'Редактировать',
                            'class' => 'btn btn-sm btn-warning',
                        ]);
                    },
                    'delete' => function ($url, $model) {
                        if ($model->is_system) {
                            return '';
                        }
                        return Html::a('<i class="fas fa-trash"></i>', $url, [
                            'title' => 'Удалить',
                            'class' => 'btn btn-sm btn-danger',
                            'data' => [
                                'confirm' => 'Вы уверены, что хотите удалить эту роль?',
                                'method' => 'post',
                            ],
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
    <?php Pjax::end(); ?>
</div>
