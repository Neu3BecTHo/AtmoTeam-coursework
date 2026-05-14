<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use app\models\User;
use app\models\Post;
use app\models\Follow;
use yii\web\UploadedFile;
use app\components\ApiValidator;
use app\components\RateLimiter;

class ProfileController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['edit', 'update'],
                'rules' => [
                    [
                        'actions' => ['edit', 'update'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionView($id = null)
    {
        if ($id === null) {
            if (Yii::$app->user->isGuest) {
                return $this->redirect(['/site/login']);
            }
            $id = Yii::$app->user->id;
        }

        $user = User::findOne($id);
        if (!$user) {
            throw new NotFoundHttpException('Пользователь не найден');
        }

        $viewerId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        if (!$user->canViewProfile($viewerId)) {
            if (Yii::$app->user->isGuest) {
                return $this->redirect(['/site/login']);
            }
            throw new NotFoundHttpException('Профиль приватный или недоступен');
        }

        $posts = []; // Посты будут загружаться через JavaScript

        $stats = [
            'posts_count' => Post::find()->where(['user_id' => $id])->count(),
            'likes_received' => Post::find()
                ->where(['user_id' => $id])
                ->sum('likes_count') ?: 0,
            'followers' => Follow::getFollowersCount($id),
            'following' => Follow::getFollowingCount($id),
        ];

        $isFollowing = !Yii::$app->user->isGuest && 
                       Follow::isFollowing(Yii::$app->user->id, $id);

        $isBlocked = !Yii::$app->user->isGuest && 
                     User::findOne(Yii::$app->user->id)->hasBlocked($id);

        return $this->render('view', [
            'user' => $user,
            'posts' => $posts,
            'stats' => $stats,
            'isOwner' => !Yii::$app->user->isGuest && Yii::$app->user->id == $id,
            'isFollowing' => $isFollowing,
            'isBlocked' => $isBlocked,
        ]);
    }

    public function actionEdit()
    {
        $user = Yii::$app->user->identity;
        $user->scenario = 'update';
        
        if (Yii::$app->request->isPost) {
            $user->load(Yii::$app->request->post());

            $croppedAvatar = Yii::$app->request->post('cropped_avatar');
            if ($croppedAvatar && strpos($croppedAvatar, 'data:image') === 0) {
                $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $croppedAvatar);
                $decoded = base64_decode($base64Data);
                if ($decoded !== false && strlen($decoded) > 0) {
                    $filename = 'avatar_' . $user->id . '_' . time() . '.png';
                    $path = Yii::getAlias('@webroot/uploads/avatars/');
                    if (!is_dir($path)) {
                        mkdir($path, 0777, true);
                    }
                    $saved = file_put_contents($path . $filename, $decoded);
                    if ($saved !== false) {

                        if ($user->avatar && file_exists(Yii::getAlias('@webroot') . $user->avatar)) {
                            @unlink(Yii::getAlias('@webroot') . $user->avatar);
                        }
                        $user->avatar = '/uploads/avatars/' . $filename;
                    }
                }
            } else {

                $user->avatarFile = UploadedFile::getInstance($user, 'avatarFile');
            }
            
            if ($user->save()) {
                Yii::$app->session->setFlash('success', 'Профиль обновлен');
                return $this->redirect(['view', 'id' => $user->id]);
            } else {
                $errors = [];
                foreach ($user->getErrors() as $attr => $msgs) {
                    $errors = array_merge($errors, (array)$msgs);
                }
                Yii::$app->session->setFlash('error', 'Ошибка: ' . implode(', ', $errors));
            }
        }

        return $this->render('edit', [
            'user' => $user,
        ]);
    }

    public function actionPosts($id = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $offset = (int) Yii::$app->request->get('offset', 0);
        
        if ($id === null) {
            $id = Yii::$app->user->id;
        }

        $posts = Post::find()
            ->where(['user_id' => $id])
            ->with(['user', 'poll.options'])
            ->orderBy(['created_at' => SORT_DESC])
            ->offset($offset)
            ->limit(3)
            ->all();

        $html = '';
        foreach ($posts as $post) {
            $html .= $this->renderPartial('/post/_post_card', ['post' => $post]);
        }
        
        return ['html' => $html, 'count' => count($posts)];
    }

    
    public function actionFollow($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $authCheck = ApiValidator::requireAuth();
        if ($authCheck !== true) {
            return $authCheck;
        }

        $rateLimitCheck = RateLimiter::checkFollowLimit();
        if ($rateLimitCheck !== true) {
            return $rateLimitCheck;
        }

        if ($id == Yii::$app->user->id) {
            return ApiValidator::error('Нельзя подписаться на себя');
        }

        $result = Follow::follow(Yii::$app->user->id, $id);

        if ($result) {
            return [
                'success' => true,
                'followers_count' => Follow::getFollowersCount($id),
                'following' => true,
            ];
        }

        return ['success' => false, 'error' => 'Уже подписаны или ошибка'];
    }

    
    public function actionBlock()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'error' => 'Не авторизован'];
        }
        
        $data = json_decode(Yii::$app->request->getRawBody(), true);
        $blockedUserId = $data['user_id'] ?? Yii::$app->request->post('user_id');
        
        $blockedUser = User::findOne($blockedUserId);
        if (!$blockedUser) {
            return ['success' => false, 'error' => 'Пользователь не найден'];
        }
        
        if ($blockedUserId == Yii::$app->user->id) {
            return ['success' => false, 'error' => 'Нельзя заблокировать себя'];
        }
        
        $currentUser = User::findOne(Yii::$app->user->id);
        if ($currentUser->hasBlocked($blockedUserId)) {
            return ['success' => false, 'error' => 'Пользователь уже заблокирован'];
        }
        
        if ($currentUser->blockUser($blockedUserId)) {
            return ['success' => true, 'message' => 'Пользователь заблокирован'];
        }
        
        return ['success' => false, 'error' => 'Ошибка блокировки'];
    }

    
    public function actionUnblock()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'error' => 'Не авторизован'];
        }
        
        $data = json_decode(Yii::$app->request->getRawBody(), true);
        $unblockedUserId = $data['user_id'] ?? Yii::$app->request->post('user_id');
        
        $unblockedUser = User::findOne($unblockedUserId);
        if (!$unblockedUser) {
            return ['success' => false, 'error' => 'Пользователь не найден'];
        }
        
        if ($unblockedUserId == Yii::$app->user->id) {
            return ['success' => false, 'error' => 'Нельзя разблокировать себя'];
        }
        
        $currentUser = User::findOne(Yii::$app->user->id);
        if (!$currentUser->hasBlocked($unblockedUserId)) {
            return ['success' => false, 'error' => 'Пользователь не заблокирован'];
        }
        
        if ($currentUser->unblockUser($unblockedUserId)) {
            return ['success' => true, 'message' => 'Пользователь разблокирован'];
        }
        
        return ['success' => false, 'error' => 'Ошибка разблокировки'];
    }

    
    public function actionUnfollow($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'error' => 'Не авторизован'];
        }

        $result = Follow::unfollow(Yii::$app->user->id, $id);

        if ($result) {
            return [
                'success' => true,
                'followers_count' => Follow::getFollowersCount($id),
                'following' => false,
            ];
        }

        return ['success' => false, 'error' => 'Не подписаны или ошибка'];
    }

    
    public function actionFollowers($id)
    {
        $user = User::findOne($id);
        if (!$user) {
            throw new NotFoundHttpException('Пользователь не найден');
        }

        $followers = User::find()
            ->innerJoin('{{%follow}}', '{{%follow}}.follower_id = {{%user}}.id')
            ->where(['{{%follow}}.following_id' => $id])
            ->all();

        return $this->render('followers', [
            'user' => $user,
            'users' => $followers,
            'title' => 'Подписчики',
        ]);
    }

    
    public function actionFollowing($id)
    {
        $user = User::findOne($id);
        if (!$user) {
            throw new NotFoundHttpException('Пользователь не найден');
        }

        $following = User::find()
            ->innerJoin('{{%follow}}', '{{%follow}}.following_id = {{%user}}.id')
            ->where(['{{%follow}}.follower_id' => $id])
            ->all();

        return $this->render('following', [
            'user' => $user,
            'users' => $following,
            'title' => 'Подписки',
        ]);
    }
}
