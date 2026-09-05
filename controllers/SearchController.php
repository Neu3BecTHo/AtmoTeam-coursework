<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\Post;
use app\models\User;
use app\models\Follow;

class SearchController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionApi($q = '')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $q = trim(Yii::$app->request->get('q', ''));
        if (empty($q)) {
            return ['users' => [], 'posts' => []];
        }

        $users = User::find()
            ->select(['id', 'username', 'avatar'])
            ->where(['like', 'username', $q])
            ->andWhere(['is_private' => 0, 'is_blocked' => 0, 'status' => 10])
            ->limit(5)
            ->asArray()
            ->all();

        // Видимые авторы: публичные профили + подписки + свои
        $visibleUserIds = User::find()
            ->select('id')
            ->where(['is_private' => 0, 'is_blocked' => 0, 'status' => 10])
            ->column();

        if (!Yii::$app->user->isGuest) {
            $visibleUserIds = array_merge($visibleUserIds, Follow::getFollowingIds(Yii::$app->user->id));
            $visibleUserIds[] = Yii::$app->user->id;
        }

        $posts = Post::find()
            ->where(['like', 'content', $q])
            ->andWhere(['user_id' => $visibleUserIds])
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
