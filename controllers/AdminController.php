<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\models\User;
use app\models\Post;
use app\models\Comment;
use app\models\Notification;
use app\components\ApiValidator;
use app\assets\AdminAsset;

class AdminController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                'matchCallback' => function ($rule, $action) {
                    return Yii::$app->user->can('accessAdminPanel');
                },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete-user' => ['POST'],
                    'delete-post' => ['POST'],
                    'delete-comment' => ['POST'],
                    'block-user' => ['POST'],
                    'unblock-user' => ['POST'],
                    'clear-comment-report' => ['POST'],
                    'stats' => ['GET', 'POST'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        // Регистрируем Asset и язык только для HTML-экшенов (не JSON API)
        if ($this->response->format !== Response::FORMAT_JSON) {
            AdminAsset::register($this->view);

            // Устанавливаем язык для админки
            if (isset($_COOKIE['language'])) {
                $lang = $_COOKIE['language'];
                if (in_array($lang, ['en-US', 'ru-RU'], true)) {
                    Yii::$app->language = $lang;
                }
            }
        }

        return parent::beforeAction($action);
    }

    /**
     * API: Получить статистику
     */
    public function actionStats()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $adminCheck = ApiValidator::requireAdmin();
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $stats = [
            'users' => User::find()->count(),
            'posts' => Post::find()->count(),
            'comments' => Comment::find()->count(),
            'notifications' => Notification::find()->count(),
        ];

        return [
            'success' => true,
            'stats' => $stats
        ];
    }

    /**
     * Главная страница админки
     */
    public function actionIndex()
    {
        $stats = [
            'users' => User::find()->count(),
            'posts' => Post::find()->count(),
            'comments' => Comment::find()->count(),
            'notifications' => Notification::find()->count(),
        ];

        $recentUsers = User::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(10)
            ->all();

        $recentPosts = Post::find()
            ->with('user')
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(10)
            ->all();

        return $this->render('index', [
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'recentPosts' => $recentPosts,
        ]);
    }

    /**
     * Страница управления пользователями
     */
    public function actionUsers()
    {
        $users = User::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return $this->render('users', [
            'users' => $users,
        ]);
    }

    /**
     * Страница управления постами
     */
    public function actionPosts()
    {
        $posts = Post::find()
            ->with('user')
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $stats = [
            'posts' => count($posts),
            'today' => count(array_filter($posts, fn($p) => $p->created_at > time() - 86400)),
            'poll' => count(array_filter($posts, fn($p) => $p->poll)),
            'image' => count(array_filter($posts, fn($p) => $p->image)),
            'likes' => array_sum(array_map(fn($p) => $p->likes_count, $posts)),
        ];

        return $this->render('posts', [
            'posts' => $posts,
            'stats' => $stats,
        ]);
    }

    /**
     * Страница управления комментариями
     */
    public function actionComments()
    {
        $comments = Comment::find()
            ->with(['user', 'post'])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return $this->render('comments', [
            'comments' => $comments,
        ]);
    }

    /**
     * API: Удалить пользователя
     */
    public function actionDeleteUser()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $adminCheck = ApiValidator::requireAdmin();
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $userId = Yii::$app->request->post('user_id');
        $user = User::findOne($userId);

        if (!$user) {
            return ['success' => false, 'error' => 'Пользователь не найден'];
        }

        if ($user->username === 'admin') {
            return ['success' => false, 'error' => 'Нельзя удалить администратора'];
        }

        // Удаляем пользователя со всем содержимым в транзакции
        // (посты с картинками, сообщения, уведомления, сторис, аватар)
        if ($user->deleteWithContent()) {
            return ['success' => true, 'message' => 'Пользователь удален'];
        }

        return ['success' => false, 'error' => 'Ошибка удаления'];
    }

    /**
     * API: Удалить пост
     */
    public function actionDeletePost()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $adminCheck = ApiValidator::requireAdmin();
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $postId = Yii::$app->request->post('post_id');

        if (!$postId) {
            return ['success' => false, 'error' => 'Не указан ID поста'];
        }

        $post = Post::findOne($postId);
        if (!$post) {
            return ['success' => false, 'error' => 'Пост не найден'];
        }

        // Удаляем изображения поста
        $this->deletePostImages($post);

        if ($post->delete()) {
            return ['success' => true, 'message' => 'Пост удален'];
        }

        return ['success' => false, 'error' => 'Ошибка удаления'];
    }

    /**
     * Удалить изображения поста с сервера
     */
    private function deletePostImages($post)
    {
        $images = $post->getImages()->all();
        foreach ($images as $image) {
            $imageFile = Yii::getAlias('@webroot') . $image->file_path;
            if (file_exists($imageFile)) {
                @unlink($imageFile);
            }
            $image->delete();
        }
    }

    /**
     * API: Удалить комментарий
     */
    public function actionDeleteComment()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $adminCheck = ApiValidator::requireAdmin();
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $commentId = Yii::$app->request->post('comment_id');
        $comment = Comment::findOne($commentId);

        if (!$comment) {
            return ['success' => false, 'error' => 'Комментарий не найден'];
        }

        if ($comment->delete()) {
            return ['success' => true, 'message' => 'Комментарий удален'];
        }

        return ['success' => false, 'error' => 'Ошибка удаления'];
    }

    /**
     * API: Заблокировать пользователя
     */
    public function actionBlockUser()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $adminCheck = ApiValidator::requireAdmin();
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $userId = Yii::$app->request->post('user_id');
        $user = User::findOne($userId);

        if (!$user) {
            return ['success' => false, 'error' => 'Пользователь не найден'];
        }

        if ($user->username === 'admin') {
            return ['success' => false, 'error' => 'Нельзя заблокировать администратора'];
        }

        $user->is_blocked = 1;
        if ($user->save()) {
            return ['success' => true, 'message' => 'Пользователь заблокирован'];
        }

        return ['success' => false, 'error' => 'Ошибка блокировки'];
    }

    /**
     * API: Разблокировать пользователя
     */
    public function actionUnblockUser()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $adminCheck = ApiValidator::requireAdmin();
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $userId = Yii::$app->request->post('user_id');
        $user = User::findOne($userId);

        if (!$user) {
            return ['success' => false, 'error' => 'Пользователь не найден'];
        }

        $user->is_blocked = 0;
        if ($user->save()) {
            return ['success' => true, 'message' => 'Пользователь разблокирован'];
        }

        return ['success' => false, 'error' => 'Ошибка разблокировки'];
    }

    /**
     * API: Снять жалобу с комментария
     */
    public function actionClearCommentReport()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $adminCheck = ApiValidator::requireAdmin();
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $commentId = Yii::$app->request->post('comment_id');
        $comment = Comment::findOne($commentId);

        if (!$comment) {
            return ['success' => false, 'error' => 'Комментарий не найден'];
        }

        $comment->is_reported = 0;
        if ($comment->save()) {
            return ['success' => true, 'message' => 'Жалоба снята'];
        }

        return ['success' => false, 'error' => 'Ошибка сохранения'];
    }
}