<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use app\models\User;
use app\models\Post;
use app\models\Comment;
use app\models\Notification;
use app\components\ApiValidator;
use app\components\RateLimiter;
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
        ];
    }

    public function beforeAction($action)
    {

        AdminAsset::register($this->view);
        return parent::beforeAction($action);
    }

    
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

    
    public function actionUsers()
    {
        $query = User::find();
        $users = $query->orderBy(['created_at' => SORT_DESC])->all();

        return $this->render('users', [
            'users' => $users,
        ]);
    }

    
    public function actionPosts()
    {
        $query = Post::find()->with('user');
        $posts = $query->orderBy(['created_at' => SORT_DESC])->all();

        return $this->render('posts', [
            'posts' => $posts,
        ]);
    }

    
    public function actionComments()
    {
        $query = Comment::find()->with(['user', 'post']);
        $comments = $query->orderBy(['created_at' => SORT_DESC])->all();

        return $this->render('comments', [
            'comments' => $comments,
        ]);
    }

    
    public function actionDeleteUser()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $adminCheck = ApiValidator::requireAdmin();
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $rateLimitCheck = RateLimiter::checkApiLimit();
        if ($rateLimitCheck !== true) {
            return $rateLimitCheck;
        }
        
        $userId = Yii::$app->request->post('user_id');
        $user = User::findOne($userId);
        
        if (!$user) {
            return ApiValidator::error('Пользователь не найден');
        }
        
        if ($user->username === 'admin') {
            return ApiValidator::error('Нельзя удалить администратора');
        }
        
        if ($user->delete()) {
            return ['success' => true, 'message' => 'Пользователь удален'];
        }
        
        return ['success' => false, 'error' => 'Ошибка удаления'];
    }

    
    public function actionDeletePost()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $adminCheck = ApiValidator::requireAdmin();
        if ($adminCheck !== true) {
            return $adminCheck;
        }
        
        $postId = Yii::$app->request->post('post_id');
        $post = Post::findOne($postId);
        
        if (!$post) {
            return ApiValidator::error('Пост не найден');
        }
        
        if ($post->delete()) {
            return ['success' => true, 'message' => 'Пост удален'];
        }
        
        return ['success' => false, 'error' => 'Ошибка удаления'];
    }

    
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
            return ApiValidator::error('Комментарий не найден');
        }
        
        if ($comment->delete()) {
            return ['success' => true, 'message' => 'Комментарий удален'];
        }
        
        return ['success' => false, 'error' => 'Ошибка удаления'];
    }

    
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
            return ApiValidator::error('Пользователь не найден');
        }
        if ($user->username === 'admin') {
            return ApiValidator::error('Нельзя заблокировать администратора');
        }
        if (!$user->hasAttribute('is_blocked')) {
            return ApiValidator::error('Блокировка недоступна');
        }

        if ($user->updateAttributes(['is_blocked' => 1])) {
            return ['success' => true, 'message' => 'Пользователь заблокирован'];
        }

        return ['success' => false, 'error' => 'Ошибка блокировки'];
    }

    
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
            return ApiValidator::error('Пользователь не найден');
        }
        if (!$user->hasAttribute('is_blocked')) {
            return ApiValidator::error('Блокировка недоступна');
        }

        if ($user->updateAttributes(['is_blocked' => 0])) {
            return ['success' => true, 'message' => 'Пользователь разблокирован'];
        }

        return ['success' => false, 'error' => 'Ошибка разблокировки'];
    }

    
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
            return ApiValidator::error('Комментарий не найден');
        }
        if (!$comment->hasAttribute('is_reported')) {
            return ApiValidator::error('Поле жалоб недоступно');
        }

        if ($comment->updateAttributes(['is_reported' => 0])) {
            return ['success' => true, 'message' => 'Жалоба снята'];
        }

        return ['success' => false, 'error' => 'Ошибка сохранения'];
    }
}
