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
                        'actions' => ['index', 'create', 'upload', 'delete', 'view', 'get-stories', 'get'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
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
        $story = Story::find()
            ->with('user')
            ->where(['id' => $id])
            ->andWhere(['>', 'expires_at', time()])
            ->one();

        if (!$story) {
            throw new NotFoundHttpException(Yii::t('app', 'История не найдена или истекла'));
        }

        $userId = Yii::$app->user->id;
        if ($story->user_id !== $userId && !Follow::isFollowing($userId, $story->user_id)) {
            throw new NotFoundHttpException(Yii::t('app', 'Доступ запрещен'));
        }

        return $this->renderPartial('view', ['story' => $story]);
    }

    public function actionCreate()
    {
        $model = new Story();

        if (Yii::$app->request->isPost) {
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            $model->caption = Yii::$app->request->post('caption');
        if (!$model->imageFile) {
            return ['success' => false, 'error' => 'Файл изображения обязателен'];
        }

            if ($model->validate() && $model->imageFile) {
                if ($model->save()) {
                    return $this->redirect(['/story/index']);
                }
            }
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpload()
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

        $model = new Story();
        $model->user_id = Yii::$app->user->id;
        $model->imageFile = UploadedFile::getInstanceByName('image');
        $model->caption = Yii::$app->request->post('caption');
        if (!$model->imageFile) {
            return ['success' => false, 'error' => 'Файл изображения обязателен'];
        }

        if ($model->validate() && $model->save()) {
            return ['success' => true, 'story' => $model->toArray()];
        }

        $errors = [];
        foreach ($model->getErrors() as $fieldErrors) {
            $errors = array_merge($errors, (array) $fieldErrors);
        }

        return ['success' => false, 'error' => implode(', ', $errors)];
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

        $data = ApiValidator::getRequestData();
        $storyId = $data['story_id'] ?? Yii::$app->request->get('id');

        if (!$storyId) {
            return ApiValidator::error(Yii::t('app', 'ID истории не указан'));
        }

        $story = Story::findOne(['id' => $storyId, 'user_id' => Yii::$app->user->id]);

        if (!$story) {
            return ApiValidator::error(Yii::t('app', 'История не найдена'));
        }

        $imagePath = Yii::getAlias('@webroot/uploads/stories/' . $story->image);
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        if ($story->delete()) {
            return ['success' => true, 'message' => Yii::t('app', 'История удалена')];
        }

        return ['success' => false, 'error' => Yii::t('app', 'Ошибка удаления')];
    }

    public function actionGetStories()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $authCheck = ApiValidator::requireAuth();
        if ($authCheck !== true) {
            return $authCheck;
        }

        $stories = Story::getFollowingStories(Yii::$app->user->id);

        return [
            'success' => true,
            'stories' => array_map(fn($story) => $story->toArray(), $stories),
        ];
    }

    /**
     * API: получить все активные истории конкретного пользователя
     */
    public function actionGet()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $authCheck = ApiValidator::requireAuth();
        if ($authCheck !== true) {
            return $authCheck;
        }

        $userId = Yii::$app->request->get('user_id');
        if (!$userId) {
            return ['success' => false, 'error' => Yii::t('app', 'Не указан пользователь')];
        }

        $currentUserId = Yii::$app->user->id;
        if ($currentUserId != $userId && !Follow::isFollowing($currentUserId, $userId)) {
            return ['success' => false, 'error' => Yii::t('app', 'Доступ запрещён')];
        }

        $stories = Story::find()
            ->with('user')
            ->where(['user_id' => $userId])
            ->andWhere(['>', 'expires_at', time()])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return [
            'success' => true,
            'stories' => array_map(fn($story) => $story->toArray(), $stories),
        ];
    }
}