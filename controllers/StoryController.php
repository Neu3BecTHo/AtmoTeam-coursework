<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\filters\AccessControl;
use app\models\User;
use app\models\Story;
use app\models\Follow;
use app\components\ApiValidator;
use app\components\RateLimiter;

class StoryController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'create', 'upload', 'delete'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['view'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {

        if (in_array($action->id, ['upload', 'delete'])) {
            Yii::$app->request->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    
    public function actionIndex()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/site/login']);
        }

        $userId = Yii::$app->user->id;
        $stories = Story::getFollowingStories($userId);

        $storiesByUser = [];
        foreach ($stories as $story) {
            $storiesByUser[$story->user_id][] = $story;
        }

        return $this->render('index', [
            'storiesByUser' => $storiesByUser,
        ]);
    }

    
    public function actionView($id)
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/site/login']);
        }

        $story = Story::find()
            ->with('user')
            ->where(['id' => $id])
            ->andWhere(['>', 'expires_at', time()])
            ->one();

        if (!$story) {
            throw new NotFoundHttpException('История не найдена или истекла');
        }

        $userId = Yii::$app->user->id;
        if ($story->user_id !== $userId && !Follow::isFollowing($userId, $story->user_id)) {
            throw new NotFoundHttpException('Доступ запрещен');
        }

        return $this->renderPartial('view', [
            'story' => $story,
        ]);
    }

    
    public function actionCreate()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/site/login']);
        }

        $model = new Story();
        $model->user_id = Yii::$app->user->id;

        if (Yii::$app->request->isPost) {
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            $model->caption = Yii::$app->request->post('caption');

            if ($model->validate()) {

                if ($model->imageFile) {
                    $filename = time() . '_' . uniqid() . '.' . $model->imageFile->extension;
                    $uploadPath = Yii::getAlias('@webroot/uploads/stories');
                    
                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0777, true);
                    }
                    
                    if ($model->imageFile->saveAs($uploadPath . '/' . $filename)) {
                        $model->image = $filename;
                        
                        if ($model->save()) {
                            return $this->redirect(['/story/index']);
                        }
                    }
                }
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    
    public function actionUpload()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        error_log('Story upload attempt - User ID: ' . (Yii::$app->user->id ?? 'null'));
        error_log('Is guest: ' . (Yii::$app->user->isGuest ? 'true' : 'false'));

        if (Yii::$app->user->isGuest) {
            error_log('User is guest, returning 401 error');
            return [
                'success' => false,
                'error' => 'Требуется авторизация',
                'code' => 401
            ];
        }
        
        $authCheck = ApiValidator::requireAuth();
        if ($authCheck !== true) {
            error_log('Auth check failed: ' . json_encode($authCheck));
            return $authCheck;
        }

        $rateLimitCheck = RateLimiter::checkApiLimit();
        if ($rateLimitCheck !== true) {
            return $rateLimitCheck;
        }

        $model = new Story();
        $model->user_id = Yii::$app->user->id;
        $model->imageFile = UploadedFile::getInstanceByName('image');
        $model->caption = Yii::$app->request->post('caption');

        if ($model->validate()) {
            if ($model->save()) {
                return ['success' => true, 'story' => $model->toArray()];
            } else {
                $errors = [];
                foreach ($model->getErrors() as $fieldErrors) {
                    $errors = array_merge($errors, (array)$fieldErrors);
                }
                return ['success' => false, 'error' => 'Ошибка сохранения: ' . implode(', ', $errors)];
            }
        } else {
            $errors = [];
            foreach ($model->getErrors() as $fieldErrors) {
                $errors = array_merge($errors, (array)$fieldErrors);
            }
            return ['success' => false, 'error' => 'Ошибка валидации: ' . implode(', ', $errors)];
        }
    }

    
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $authCheck = ApiValidator::requireAuth();
        if ($authCheck !== true) {
            return $authCheck;
        }

        $rateLimitCheck = RateLimiter::checkApiLimit();
        if ($rateLimitCheck !== true) {
            return $rateLimitCheck;
        }

        $rawBody = Yii::$app->request->getRawBody();
        $storyId = null;
        
        if ($rawBody) {
            $data = json_decode($rawBody, true);
            $storyId = $data['story_id'] ?? null;
        }

        if (!$storyId) {
            $storyId = Yii::$app->request->get('id');
        }
        
        if (!$storyId) {
            return ApiValidator::error('ID истории не указан');
        }
        
        $story = Story::findOne(['id' => $storyId, 'user_id' => Yii::$app->user->id]);

        if (!$story) {
            return ApiValidator::error('История не найдена');
        }

        $imagePath = Yii::getAlias('@webroot/uploads/stories/' . $story->image);
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        if ($story->delete()) {
            return ['success' => true, 'message' => 'История удалена'];
        }

        return ['success' => false, 'error' => 'Ошибка удаления'];
    }

    
    public function actionGetStories()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'error' => 'Не авторизован'];
        }

        $stories = Story::getFollowingStories(Yii::$app->user->id);
        
        return [
            'success' => true,
            'stories' => array_map(function($story) {
                return $story->toArray();
            }, $stories),
        ];
    }
}
