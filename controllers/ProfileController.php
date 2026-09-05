<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\web\UploadedFile;
use app\models\User;
use app\models\Post;
use app\models\Follow;
use app\components\ApiValidator;
use app\components\RateLimiter;
use app\models\Repost;
use app\models\SavedPost;

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
            throw new NotFoundHttpException(Yii::t('app', 'Пользователь не найден'));
        }

        $viewerId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        if (!$user->canViewProfile($viewerId)) {
            if (Yii::$app->user->isGuest) {
                return $this->redirect(['/site/login']);
            }
            throw new NotFoundHttpException(Yii::t('app', 'Профиль приватный или недоступен'));
        }

        $stats = [
            'posts_count' => Post::find()->where(['user_id' => $id])->count(),
            'likes_received' => Post::find()->where(['user_id' => $id])->sum('likes_count') ?: 0,
            'followers' => Follow::getFollowersCount($id),
            'following' => Follow::getFollowingCount($id),
        ];

        $isFollowing = !Yii::$app->user->isGuest && Follow::isFollowing(Yii::$app->user->id, $id);
        $isBlocked = !Yii::$app->user->isGuest && User::findOne(Yii::$app->user->id)->hasBlocked($id);

        return $this->render('view', [
            'user' => $user,
            'posts' => [],
            'stats' => $stats,
            'isOwner' => !Yii::$app->user->isGuest && Yii::$app->user->id == $id,
            'isFollowing' => $isFollowing,
            'isBlocked' => $isBlocked,
        ]);
    }

    public function actionEdit()
    {
        /**
         * @var User $user
         */
        $user = Yii::$app->user->identity;
        $user->scenario = 'update';
        
        if (Yii::$app->request->isPost) {
            $user->load(Yii::$app->request->post());

            $croppedAvatar = Yii::$app->request->post('cropped_avatar');
            if ($croppedAvatar && strpos($croppedAvatar, 'data:image') === 0) {
                $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $croppedAvatar);
                $decoded = base64_decode($base64Data);
                if ($decoded !== false && strlen($decoded) > 0) {
                    // Лимит 2MB
                    if (strlen($decoded) > 2 * 1024 * 1024) {
                        return ['success' => false, 'error' => 'Размер аватара не должен превышать 2 МБ'];
                    }
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
                Yii::$app->session->setFlash('success', Yii::t('app', 'Профиль обновлен'));
                return $this->redirect(['view', 'id' => $user->id]);
            } else {
                $errors = [];
                foreach ($user->getErrors() as $attr => $msgs) {
                    $errors = array_merge($errors, (array)$msgs);
                }
                Yii::$app->session->setFlash('error', Yii::t('app', 'Ошибка: {error}', ['error' => implode(', ', $errors)]));
            }
        }

        return $this->render('edit', ['user' => $user]);
    }

    public function actionPosts($id = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $offset = (int) Yii::$app->request->get('offset', 0);
        $id = $id ?? Yii::$app->user->id;

        $profileUser = User::findOne($id);
        if (!$profileUser || !$profileUser->canViewProfile(Yii::$app->user->id)) {
            Yii::$app->response->statusCode = 403;
            return ['html' => '', 'count' => 0, 'error' => 'Доступ запрещён'];
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
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'error' => Yii::t('app', 'Необходимо авторизоваться')];
        }
        
        $currentUser = Yii::$app->user->identity;
        $userToFollow = User::findOne($id);
        
        if (!$userToFollow) {
            return ['success' => false, 'error' => 'Пользователь не найден'];
        }
        
        $follow = new Follow();
        $follow->follower_id = $currentUser->id;
        $follow->following_id = $userToFollow->id;
        
        if ($follow->save()) {
            return ['success' => true, 'following' => true, 'followers_count' => $userToFollow->getFollowers()->count()];
        }
        
        return ['success' => false, 'error' => Yii::t('app', 'Ошибка подписки')];
    }

    public function actionUnfollow($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'error' => Yii::t('app', 'Необходимо авторизоваться')];
        }
        
        $currentUser = Yii::$app->user->identity;
        $follow = Follow::find()
            ->where(['follower_id' => $currentUser->id, 'following_id' => $id])
            ->one();
        
        if ($follow && $follow->delete()) {
            $user = User::findOne($id);
            return ['success' => true, 'following' => false, 'followers_count' => $user ? $user->getFollowers()->count() : 0];
        }
        
        return ['success' => false, 'error' => Yii::t('app', 'Ошибка отписки')];
    }

    public function actionDeleteAccount()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'error' => Yii::t('app', 'Необходимо авторизоваться')];
        }
        
        $userId = Yii::$app->user->id;
        $user = User::findOne($userId);
        
        if (!$user) {
            return ['success' => false, 'error' => 'Пользователь не найден'];
        }
        
        // Пароль обязателен: защита от удаления при угнанной сессии/CSRF
        $password = Yii::$app->request->post('password');
        if (!$password || !$user->validatePassword($password)) {
            return ['success' => false, 'error' => Yii::t('app', 'Неверный пароль')];
        }
        
        // Удаляем пользователя со всем содержимым
        if ($user->deleteWithContent()) {
            Yii::$app->user->logout();
            return ['success' => true, 'message' => Yii::t('app', 'Аккаунт удалён')];
        }
        
        return ['success' => false, 'error' => Yii::t('app', 'Ошибка при удалении аккаунта')];
    }

    public function actionBlock()
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
        $blockedUserId = $data['user_id'] ?? Yii::$app->request->post('user_id');
        
        $blockedUser = User::findOne($blockedUserId);
        if (!$blockedUser) {
            return ['success' => false, 'error' => 'Пользователь не найден'];
        }
        
        if ($blockedUserId == Yii::$app->user->id) {
            return ['success' => false, 'error' => Yii::t('app', 'Нельзя заблокировать себя')];
        }
        
        $currentUser = User::findOne(Yii::$app->user->id);
        if ($currentUser->hasBlocked($blockedUserId)) {
            return ['success' => false, 'error' => Yii::t('app', 'Пользователь уже заблокирован')];
        }
        
        if ($currentUser->blockUser($blockedUserId)) {
            return ['success' => true, 'message' => Yii::t('app', 'Пользователь заблокирован')];
        }
        
        return ['success' => false, 'error' => Yii::t('app', 'Ошибка блокировки')];
    }

    public function actionUnblock()
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
        $unblockedUserId = $data['user_id'] ?? Yii::$app->request->post('user_id');
        
        $unblockedUser = User::findOne($unblockedUserId);
        if (!$unblockedUser) {
            return ['success' => false, 'error' => 'Пользователь не найден'];
        }
        
        if ($unblockedUserId == Yii::$app->user->id) {
            return ['success' => false, 'error' => Yii::t('app', 'Нельзя разблокировать себя')];
        }
        
        $currentUser = User::findOne(Yii::$app->user->id);
        if (!$currentUser->hasBlocked($unblockedUserId)) {
            return ['success' => false, 'error' => Yii::t('app', 'Пользователь не заблокирован')];
        }
        
        if ($currentUser->unblockUser($unblockedUserId)) {
            return ['success' => true, 'message' => Yii::t('app', 'Пользователь разблокирован')];
        }
        
        return ['success' => false, 'error' => Yii::t('app', 'Ошибка разблокировки')];
    }

    public function actionSaved($id = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $authCheck = ApiValidator::requireAuth();
        if ($authCheck !== true) {
            return ['html' => '', 'count' => 0];
        }

        $offset = (int) Yii::$app->request->get('offset', 0);
        $userId = Yii::$app->user->id;

        $posts = SavedPost::getUserSavedPosts($userId, 3, $offset);

        $html = '';
        foreach ($posts as $post) {
            $html .= $this->renderPartial('/post/_post_card', ['post' => $post]);
        }

        return ['html' => $html, 'count' => count($posts)];
    }

    public function actionReposts($id = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $id = $id ?? Yii::$app->user->id;
        $offset = (int) Yii::$app->request->get('offset', 0);

        $profileUser = User::findOne($id);
        if (!$profileUser || !$profileUser->canViewProfile(Yii::$app->user->id)) {
            Yii::$app->response->statusCode = 403;
            return ['html' => '', 'count' => 0, 'error' => 'Доступ запрещён'];
        }

        $reposts = Repost::find()
            ->with(['post', 'post.user', 'post.poll.options'])
            ->where(['user_id' => $id])
            ->orderBy(['created_at' => SORT_DESC])
            ->offset($offset)
            ->limit(3)
            ->all();

        $html = '';
        foreach ($reposts as $repost) {
            $post = $repost->post;
            if ($post && $post->user) {
                $html .= $this->renderPartial('/post/_post_card', ['post' => $post]);
            }
        }

        return ['html' => $html, 'count' => count($reposts)];
    }

    public function actionFollowers($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $user = User::findOne($id);
        if (!$user) {
            return ['success' => false, 'error' => 'Пользователь не найден'];
        }
        
        // getFollowers() должен возвращать User[], а не Follow[]
        $followers = $user->getFollowers()->all();
        
        $html = '';
        foreach ($followers as $follower) {
            // $follower уже должен быть объектом User
            $html .= $this->renderPartial('_user_card', [
                'user' => $follower,
                'currentUser' => Yii::$app->user->identity,
            ]);
        }
        
        return [
            'success' => true,
            'html' => $html,
            'count' => count($followers)
        ];
    }

    public function actionFollowing($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $user = User::findOne($id);
        if (!$user) {
            return ['success' => false, 'error' => 'Пользователь не найден'];
        }
        
        // getFollowing() должен возвращать User[], а не Follow[]
        $following = $user->getFollowing()->all();
        
        $html = '';
        foreach ($following as $follow) {
            // $follow должен быть объектом User
            $html .= $this->renderPartial('_user_card', [
                'user' => $follow,
                'currentUser' => Yii::$app->user->identity,
            ]);
        }
        
        return [
            'success' => true,
            'html' => $html,
            'count' => count($following)
        ];
    }

    public function actionModal($id)
    {
        $post = Post::findOne($id);
        if (!$post) {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Пост не найден'));
        }

        if (!$post->user->canViewProfile(Yii::$app->user->id)) {
            Yii::$app->response->statusCode = 403;
            return '<div class="error-message">Доступ запрещён</div>';
        }
        
        // Возвращаем только HTML, без layout
        return $this->renderPartial('_post_modal', ['post' => $post]);
    }
}