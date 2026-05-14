<?php

namespace app\modules\api\controllers\v1;

use Yii;
use yii\rest\ActiveController;
use yii\web\Response;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;
use yii\filters\VerbFilter;
use app\models\User;
use app\models\LoginForm;
use app\models\ActivityLog;

/**
 * User API Controller for mobile application
 */
class UserController extends ActiveController
{
    public $modelClass = 'app\models\User';

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        $behaviors['contentNegotiator'] = [
            'class' => ContentNegotiator::class,
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['login', 'register', 'check'],
        ];

        $behaviors['verbFilter'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'login' => ['POST'],
                'register' => ['POST'],
                'profile' => ['GET', 'PUT'],
                'search' => ['GET'],
                'follow' => ['POST'],
                'unfollow' => ['POST'],
                'followers' => ['GET'],
                'following' => ['GET'],
            ],
        ];

        return $behaviors;
    }

    /**
     * User login
     */
    public function actionLogin()
    {
        $model = new LoginForm();
        $model->load(Yii::$app->request->getBodyParams(), '');

        if ($model->login()) {
            $user = $model->getUser();

            $user->access_token = Yii::$app->security->generateRandomString();
            $user->save(false);

            ActivityLog::log(
                ActivityLog::ACTION_LOGIN,
                'Вход через мобильное приложение',
                'user',
                $user->id
            );

            return [
                'success' => true,
                'data' => [
                    'access_token' => $user->access_token,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                        'status' => $user->status,
                        'created_at' => $user->created_at,
                    ]
                ]
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Неверные учетные данные',
                'details' => $model->getErrors()
            ];
        }
    }

    /**
     * User registration
     */
    public function actionRegister()
    {
        $model = new User(['scenario' => 'register']);
        $model->load(Yii::$app->request->getBodyParams(), '');

        if ($model->validate()) {
            $model->password_hash = Yii::$app->security->generatePasswordHash($model->password);
            $model->auth_key = Yii::$app->security->generateRandomString();
            $model->access_token = Yii::$app->security->generateRandomString();
            $model->status = 10;
            $model->created_at = time();
            $model->updated_at = time();

            if ($model->save()) {
                ActivityLog::log(
                    ActivityLog::ACTION_CREATE,
                    'Регистрация через мобильное приложение',
                    'user',
                    $model->id
                );

                return [
                    'success' => true,
                    'data' => [
                        'access_token' => $model->access_token,
                        'user' => [
                            'id' => $model->id,
                            'username' => $model->username,
                            'email' => $model->email,
                            'avatar' => $model->avatar,
                            'status' => $model->status,
                            'created_at' => $model->created_at,
                        ]
                    ]
                ];
            }
        }

        return [
            'success' => false,
            'error' => 'Ошибка регистрации',
            'details' => $model->getErrors()
        ];
    }

    /**
     * Get current user profile
     */
    public function actionProfile()
    {
        $user = Yii::$app->user->identity;

        $stats = [
            'posts_count' => \app\models\Post::find()->where(['user_id' => $user->id])->count(),
            'followers_count' => \app\models\Follow::find()->where(['following_id' => $user->id])->count(),
            'following_count' => \app\models\Follow::find()->where(['follower_id' => $user->id])->count(),
        ];

        return [
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'stats' => $stats
            ]
        ];
    }

    /**
     * Update user profile
     */
    public function actionProfileUpdate()
    {
        $user = Yii::$app->user->identity;
        $user->scenario = 'api_update';
        
        $user->load(Yii::$app->request->getBodyParams(), '');

        if (!empty($user->password)) {
            $user->password_hash = Yii::$app->security->generatePasswordHash($user->password);
        }
        
        $user->updated_at = time();

        if ($user->save()) {
            ActivityLog::log(
                ActivityLog::ACTION_UPDATE,
                'Обновление профиля через мобильное приложение',
                'user',
                $user->id
            );

            return [
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                        'updated_at' => $user->updated_at,
                    ]
                ]
            ];
        }

        return [
            'success' => false,
            'error' => 'Ошибка обновления профиля',
            'details' => $user->getErrors()
        ];
    }

    /**
     * Search users
     */
    public function actionSearch()
    {
        $q = Yii::$app->request->get('q');
        $limit = Yii::$app->request->get('limit', 20);
        $offset = Yii::$app->request->get('offset', 0);

        if (empty($q)) {
            return [
                'success' => false,
                'error' => 'Поисковый запрос не указан'
            ];
        }

        $users = User::find()
            ->where(['status' => 10])
            ->andWhere(['or', 
                ['like', 'username', $q],
                ['like', 'email', $q]
            ])
            ->limit($limit)
            ->offset($offset)
            ->all();

        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id' => $user->id,
                'username' => $user->username,
                'avatar' => $user->avatar,
                'followers_count' => \app\models\Follow::find()->where(['following_id' => $user->id])->count(),
            ];
        }

        return [
            'success' => true,
            'data' => [
                'users' => $result,
                'total' => count($result)
            ]
        ];
    }

    /**
     * Follow user
     */
    public function actionFollow()
    {
        $userId = Yii::$app->request->post('user_id');
        $currentUser = Yii::$app->user->identity;

        if ($userId == $currentUser->id) {
            return [
                'success' => false,
                'error' => 'Нельзя подписаться на самого себя'
            ];
        }

        $targetUser = User::findOne($userId);
        if (!$targetUser) {
            return [
                'success' => false,
                'error' => 'Пользователь не найден'
            ];
        }

        $follow = \app\models\Follow::find()
            ->where(['follower_id' => $currentUser->id, 'following_id' => $userId])
            ->one();

        if ($follow) {
            return [
                'success' => false,
                'error' => 'Вы уже подписаны на этого пользователя'
            ];
        }

        $follow = new \app\models\Follow();
        $follow->follower_id = $currentUser->id;
        $follow->following_id = $userId;
        $follow->created_at = time();

        if ($follow->save()) {
            ActivityLog::log(
                ActivityLog::ACTION_FOLLOW,
                "Подписка на пользователя: {$targetUser->username}",
                'user',
                $userId
            );

            return [
                'success' => true,
                'data' => [
                    'message' => 'Подписка оформлена'
                ]
            ];
        }

        return [
            'success' => false,
            'error' => 'Ошибка оформления подписки'
        ];
    }

    /**
     * Unfollow user
     */
    public function actionUnfollow()
    {
        $userId = Yii::$app->request->post('user_id');
        $currentUser = Yii::$app->user->identity;

        $follow = \app\models\Follow::find()
            ->where(['follower_id' => $currentUser->id, 'following_id' => $userId])
            ->one();

        if (!$follow) {
            return [
                'success' => false,
                'error' => 'Вы не подписаны на этого пользователя'
            ];
        }

        if ($follow->delete()) {
            ActivityLog::log(
                ActivityLog::ACTION_UNFOLLOW,
                "Отписка от пользователя",
                'user',
                $userId
            );

            return [
                'success' => true,
                'data' => [
                    'message' => 'Подписка отменена'
                ]
            ];
        }

        return [
            'success' => false,
            'error' => 'Ошибка отмены подписки'
        ];
    }

    /**
     * Get user followers
     */
    public function actionFollowers()
    {
        $userId = Yii::$app->request->get('user_id');
        $limit = Yii::$app->request->get('limit', 20);
        $offset = Yii::$app->request->get('offset', 0);

        $user = User::findOne($userId);
        if (!$user) {
            return [
                'success' => false,
                'error' => 'Пользователь не найден'
            ];
        }

        $followers = \app\models\Follow::find()
            ->where(['following_id' => $userId])
            ->with('follower')
            ->limit($limit)
            ->offset($offset)
            ->all();

        $result = [];
        foreach ($followers as $follow) {
            $result[] = [
                'id' => $follow->follower->id,
                'username' => $follow->follower->username,
                'avatar' => $follow->follower->avatar,
            ];
        }

        return [
            'success' => true,
            'data' => [
                'followers' => $result,
                'total' => count($result)
            ]
        ];
    }

    /**
     * Get user following
     */
    public function actionFollowing()
    {
        $userId = Yii::$app->request->get('user_id');
        $limit = Yii::$app->request->get('limit', 20);
        $offset = Yii::$app->request->get('offset', 0);

        $user = User::findOne($userId);
        if (!$user) {
            return [
                'success' => false,
                'error' => 'Пользователь не найден'
            ];
        }

        $following = \app\models\Follow::find()
            ->where(['follower_id' => $userId])
            ->with('following')
            ->limit($limit)
            ->offset($offset)
            ->all();

        $result = [];
        foreach ($following as $follow) {
            $result[] = [
                'id' => $follow->following->id,
                'username' => $follow->following->username,
                'avatar' => $follow->following->avatar,
            ];
        }

        return [
            'success' => true,
            'data' => [
                'following' => $result,
                'total' => count($result)
            ]
        ];
    }

    /**
     * Check token validity
     */
    public function actionCheck()
    {
        return [
            'success' => true,
            'data' => [
                'valid' => true,
                'user_id' => Yii::$app->user->id
            ]
        ];
    }
}
