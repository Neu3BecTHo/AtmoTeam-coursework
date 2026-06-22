<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use app\models\Post;
use app\models\Comment;
use app\components\ApiValidator;
use app\components\RateLimiter;

/**
 * PostController - контроллер для работы с постами
 */
class PostController extends Controller
{
    /**
     * API: Создать пост
     */
    public function actionCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $authCheck = ApiValidator::requireAuth();
        if ($authCheck !== true) {
            return $authCheck;
        }
        
        $rateLimitCheck = RateLimiter::checkPostLimit();
        if ($rateLimitCheck !== true) {
            return $rateLimitCheck;
        }
        
        $content = trim(Yii::$app->request->post('content', ''));
        if (empty($content)) {
            return ApiValidator::error(Yii::t('app', 'Содержание поста обязательно'));
        }

        $post = new Post([
            'user_id' => Yii::$app->user->id,
            'content' => $content,
        ]);

        $post->imageFiles = UploadedFile::getInstancesByName('images[]');
        
        if ($post->save()) {
            return ['success' => true, 'post' => $post->toArray()];
        }
        
        return ['success' => false, 'error' => Yii::t('app', 'Ошибка создания поста'), 'errors' => $post->errors];
    }

    public function actionModalContent($id)
    {
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'text/html');
        
        $post = Post::find()
            ->with(['user', 'poll.options', 'images'])
            ->where(['id' => $id])
            ->one();
            
        if (!$post) {
            return '<p class="error-message">Пост не найден</p>';
        }
        
        // Проверка прав на просмотр
        if (!Yii::$app->user->isGuest && !$post->user->canViewProfile(Yii::$app->user->id)) {
            return '<p class="error-message">Доступ запрещён</p>';
        }
        
        return $this->renderPartial('/post/_post_modal_content', ['post' => $post]);
    }

    /**
     * API: Получить HTML поста
     */
    public function actionGetHtml($id)
    {
        $post = Post::find()
            ->with(['user', 'poll.options', 'images'])
            ->where(['id' => $id])
            ->one();
            
        if (!$post) {
            throw new NotFoundHttpException('Пост не найден');
        }
        
        return $this->renderAjax('_post_card', ['post' => $post]);
    }

    /**
     * API: Получить модальное окно поста
     */
    public function actionModal($id)
    {
        $post = Post::find()
            ->with(['user', 'poll.options', 'images'])
            ->where(['id' => $id])
            ->one();
            
        if (!$post) {
            Yii::$app->response->statusCode = 404;
            return 'Пост не найден';
        }
        
        // Важно! Используй renderPartial, а не render
        // renderPartial НЕ подключает layout
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'text/html');
        
        return $this->renderPartial('/post/_post_modal', ['post' => $post]);
    }

    /**
     * API: Получить комментарии поста
     */
    public function actionComments($id)
    {
        try {
            $post = Post::find()
                ->with(['images'])
                ->where(['id' => $id])
                ->one();
                
            if (!$post) {
                Yii::$app->response->statusCode = 404;
                return '<p>Пост не найден</p>';
            }
            
            $comments = $post->getComments()
                ->with('user')
                ->orderBy(['created_at' => SORT_DESC])
                ->all();
                
            Yii::$app->response->format = Response::FORMAT_RAW;
            Yii::$app->response->headers->set('Content-Type', 'text/html');
            
            return $this->renderPartial('/comment/_list', ['comments' => $comments]);
        } catch (\Exception $e) {
            Yii::error("Error in actionComments: " . $e->getMessage());
            Yii::$app->response->statusCode = 500;
            return '<p>Ошибка загрузки комментариев</p>';
        }
    }

    /**
     * API: Получить данные поста
     */
    public function actionGet($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $post = Post::find()
            ->with(['user', 'poll.options', 'images'])
            ->where(['id' => $id])
            ->one();
            
        if (!$post) {
            return ['success' => false, 'error' => 'Пост не найден'];
        }
        
        $isGuest = Yii::$app->user->isGuest;
        $userId = Yii::$app->user->id;
        
        $data = $post->toArray();
        $data['reposts_count'] = $post->getRepostsCount();
        $data['is_liked'] = !$isGuest && $post->isLikedBy($userId);
        $data['is_saved'] = !$isGuest && $post->isSavedBy($userId);
        $data['is_reposted'] = !$isGuest && $post->isRepostedBy($userId);

        if ($post->poll) {
            $pollData = $post->poll->toArray();
            $pollData['has_user_voted'] = !$isGuest && $post->poll->hasUserVoted($userId);
            $pollData['user_votes'] = !$isGuest ? $post->poll->getUserVotes($userId) : [];
            $pollData['total_votes'] = $post->poll->getTotalVotes();

            $pollData['options'] = array_map(function($option) use ($pollData) {
                $optionData = $option->toArray();
                $optionData['votes_count'] = $option->getVotesCount();
                $optionData['percentage'] = $pollData['total_votes'] > 0 
                    ? round(($optionData['votes_count'] / $pollData['total_votes']) * 100, 1) 
                    : 0;
                return $optionData;
            }, $post->poll->options);
            
            $data['poll'] = $pollData;
        }
        
        return ['success' => true, 'post' => $data];
    }

    /**
     * Страница просмотра поста
     */
    public function actionView($id)
    {
        $post = Post::findOne($id);
        if (!$post) {
            throw new NotFoundHttpException('Пост не найден');
        }

        $comments = Comment::find()
            ->where(['post_id' => $id])
            ->with('user')
            ->orderBy(['created_at' => SORT_ASC])
            ->all();

        $userId = Yii::$app->user->id;
        $isLiked = $userId ? $post->isLikedBy($userId) : false;

        return $this->render('view', [
            'post' => $post,
            'comments' => $comments,
            'isLiked' => $isLiked,
        ]);
    }

    /**
     * API: Удалить пост
     */
    public function actionDelete($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $authCheck = ApiValidator::requireAuth();
        if ($authCheck !== true) {
            return $authCheck;
        }

        $post = Post::findOne($id);
        if (!$post) {
            return ['success' => false, 'error' => 'Пост не найден'];
        }

        if ($post->user_id !== Yii::$app->user->id) {
            return ['success' => false, 'error' => 'Нет прав на удаление'];
        }

        if ($post->delete()) {
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Ошибка удаления'];
    }
}