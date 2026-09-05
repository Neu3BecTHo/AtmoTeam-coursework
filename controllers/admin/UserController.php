<?php

namespace app\controllers\admin;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\models\User;
use app\models\search\UserSearch;
use app\models\Post;
use app\models\Comment;
use app\models\Like;
use app\models\Follow;

class UserController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'toggle-status', 'bulk-actions'],
                        'allow' => true,
                        'roles' => ['admin', 'super_admin'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'toggle-status' => ['POST'],
                    'bulk-actions' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);

        $stats = [
            'posts_count' => Post::find()->where(['user_id' => $id])->count(),
            'followers_count' => Follow::find()->where(['following_id' => $id])->count(),
            'following_count' => Follow::find()->where(['follower_id' => $id])->count(),
            'comments_count' => Comment::find()->where(['user_id' => $id])->count(),
            'likes_count' => Like::find()->where(['user_id' => $id])->count(),
        ];

        return $this->render('view', [
            'model' => $model,
            'stats' => $stats,
        ]);
    }

    public function actionCreate()
    {
        $model = new User(['scenario' => 'admin_create']);

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->password_hash = Yii::$app->security->generatePasswordHash($model->password);
            $model->auth_key = Yii::$app->security->generateRandomString();
            $model->status = $model->status ?? 10;
            $model->created_at = time();
            $model->updated_at = time();

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Пользователь успешно создан');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->scenario = 'admin_update';
        $oldPassword = $model->password_hash;

        if ($model->load(Yii::$app->request->post())) {
            if (!empty($model->password)) {
                $model->password_hash = Yii::$app->security->generatePasswordHash($model->password);
            } else {
                $model->password_hash = $oldPassword;
            }
            $model->updated_at = time();

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Пользователь успешно обновлен');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        if ($model->role === 'admin') {
            Yii::$app->session->setFlash('error', 'Нельзя удалить супер администратора');
            return $this->redirect(['index']);
        }
        
        // Просто удаляем пользователя — beforeDelete() сделает всё сам
        if ($model->delete()) {
            Yii::$app->session->setFlash('success', 'Пользователь успешно удален');
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при удалении пользователя');
        }
        
        return $this->redirect(['index']);
    }

    public function actionToggleStatus($id)
    {
        $model = $this->findModel($id);

        if ($model->role === 'admin') {
            Yii::$app->session->setFlash('error', 'Нельзя изменить статус супер администратора');
            return $this->redirect(['index']);
        }

        $model->status = $model->status == 10 ? 0 : 10;
        $model->updated_at = time();

        if ($model->save()) {
            Yii::$app->session->setFlash('success', 'Статус пользователя изменен');
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при изменении статуса');
        }

        return $this->redirect(['index']);
    }

    public function actionBulkActions()
    {
        $action = Yii::$app->request->post('action');
        $ids = Yii::$app->request->post('ids');

        if (empty($ids) || !is_array($ids)) {
            Yii::$app->session->setFlash('error', 'Выберите пользователей для выполнения действия');
            return $this->redirect(['index']);
        }

        $count = 0;
        foreach ($ids as $id) {
            $model = User::findOne($id);
            if (!$model || $model->role === 'admin') {
                continue;
            }

            switch ($action) {
                case 'activate':
                    $model->status = 10;
                    $model->updated_at = time();
                    if ($model->save()) $count++;
                    break;
                case 'deactivate':
                    $model->status = 0;
                    $model->updated_at = time();
                    if ($model->save()) $count++;
                    break;
                case 'delete':
                    if ($model->deleteWithContent()) $count++;
                    break;
            }
        }

        $actionText = [
            'activate' => 'активированы',
            'deactivate' => 'деактивированы',
            'delete' => 'удалены'
        ];

        Yii::$app->session->setFlash('success', "Пользователи успешно {$actionText[$action]}: {$count}");
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        $model = User::findOne($id);
        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Пользователь не найден');
    }
}