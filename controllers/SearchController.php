<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\User;
use app\models\Post;

class SearchController extends Controller
{
    public function actionIndex($q = '')
    {
        $users = [];
        $posts = [];

        if (!empty($q)) {
            $users = User::find()
                ->where(['like', 'username', $q])
                ->orWhere(['like', 'email', $q])
                ->limit(10)
                ->all();

            $posts = Post::find()
                ->where(['like', 'content', $q])
                ->with('user')
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(20)
                ->all();
        }

        return $this->render('index', [
            'query' => $q,
            'users' => $users,
            'posts' => $posts,
        ]);
    }

    public function actionApi($q = '')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (empty($q)) {
            return ['users' => [], 'posts' => []];
        }

        $users = User::find()
            ->select(['id', 'username', 'avatar'])
            ->where(['like', 'username', $q])
            ->limit(5)
            ->asArray()
            ->all();

        $posts = Post::find()
            ->where(['like', 'content', $q])
            ->with('user')
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(10)
            ->all();

        $userId = Yii::$app->user->id;
        $isGuest = Yii::$app->user->isGuest;
        
        return [
            'users' => $users,
            'posts' => array_map(function($post) use ($userId, $isGuest) {
                $data = $post->toArray();
                $data['is_liked'] = !$isGuest && $post->isLikedBy($userId);
                return $data;
            }, $posts),
        ];
    }
}