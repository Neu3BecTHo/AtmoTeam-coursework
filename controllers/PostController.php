<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use app\models\Post;
use app\models\Comment;

class PostController extends Controller
{
    public function actionCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'error' => 'Не авторизован'];
        }
        
        $content = Yii::$app->request->post('content');
        if (empty($content)) {
            return ['success' => false, 'error' => 'Содержание поста обязательно'];
        }
        
        $post = new Post([
            'user_id' => Yii::$app->user->id,
            'content' => $content,
        ]);

        $post->imageFile = UploadedFile::getInstanceByName('image');
        
        if ($post->save()) {
            return ['success' => true, 'post' => $post->toArray()];
        }
        
        return ['success' => false, 'error' => 'Ошибка создания поста'];
    }

    public function actionGetHtml($id)
    {
        $post = Post::find()
            ->with(['user', 'poll.options'])
            ->where(['id' => $id])
            ->one();
            
        if (!$post) {
            throw new NotFoundHttpException('Пост не найден');
        }
        
        return $this->renderAjax('_post_card', ['post' => $post]);
    }

    public function actionModal($id)
    {
        try {
            $post = Post::find()
                ->with(['user', 'poll.options'])
                ->where(['id' => $id])
                ->one();
                
            if (!$post) {
                Yii::$app->response->statusCode = 404;
                return '<p>Пост не найден</p>';
            }
            
            Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
            Yii::$app->response->headers->set('Content-Type', 'text/html');
            
            return $this->renderPartial('/post/_modal', ['post' => $post]);
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500;
            return '<p>Ошибка загрузки поста</p>';
        }
    }

    public function actionComments($id)
    {
        try {
            $post = Post::find()
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
                
            Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
            Yii::$app->response->headers->set('Content-Type', 'text/html');
            
            return $this->renderPartial('/comment/_list', ['comments' => $comments]);
        } catch (\Exception $e) {
            Yii::error("Error in actionComments: " . $e->getMessage());
            Yii::$app->response->statusCode = 500;
            return '<p>Ошибка загрузки комментариев</p>';
        }
    }

    public function actionGet($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $post = Post::find()
            ->with(['user', 'poll.options'])
            ->where(['id' => $id])
            ->one();
            
        if (!$post) {
            return ['success' => false, 'error' => 'Пост не найден'];
        }
        
        $data = $post->toArray();
        $data['is_liked'] = !Yii::$app->user->isGuest && $post->isLikedBy(Yii::$app->user->id);
        $data['is_saved'] = !Yii::$app->user->isGuest && $post->isSavedBy(Yii::$app->user->id);
        $data['is_reposted'] = !Yii::$app->user->isGuest && $post->isRepostedBy(Yii::$app->user->id);

        if ($post->poll) {
            $pollData = $post->poll->toArray();
            $pollData['has_user_voted'] = !Yii::$app->user->isGuest && $post->poll->hasUserVoted(Yii::$app->user->id);
            $pollData['user_votes'] = !Yii::$app->user->isGuest ? $post->poll->getUserVotes(Yii::$app->user->id) : [];
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

    public function actionDelete($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'error' => 'Не авторизован'];
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
