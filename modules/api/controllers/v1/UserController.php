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
use app\models\Follow;

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

    public function actionLogin()
    {
        $model = new LoginForm();
        $model->load(Yii::$app->request->getBodyParams(), '');

        if ($model->login()) {
            $user = $model->getUser();
            $user->generateAccessToken();
            $user->save(false);

            return [
                'success' => true,
                'data' => [
                    'access_token' => $user->access_token,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'avatar' => $user->getAvatarUrl(),
                        'status' => $user->status,
                        'created_at' => $user->created_at,
                    ]
                ]
            ];
        }

        return [
            'success' => false,
            'error' => 'Неверные учетные данные',
            'details' => $model->getErrors()
        ];
    }

    public function actionRegister()
    {
        $model = new User(['scenario' => 'register']);
        $model->load(Yii::$app->request->getBodyParams(), '');

        if ($model->validate()) {
            $model->setPassword($model->password);
            $model->auth_key = Yii::$app->security->generateRandomString();
            $model->generateAccessToken();
            $model->status = 10;

            if ($model->save()) {
                return [
                    'success' => true,
                    'data' => [
                        'access_token' => $model->access_token,
                        'user' => [
                            'id' => $model->id,
                            'username' => $model->username,
                            'email' => $model->email,
                            'avatar' => $model->getAvatarUrl(),
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

    public function actionProfile()
    {
        $user = Yii::$app->user->identity;

        $stats = [
            'posts_count' => $user->getPosts()->count(),
            'followers_count' => Follow::getFollowersCount($user->id),
            'following_count' => Follow::getFollowingCount($user->id),
        ];

        return [
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'avatar' => $user->getAvatarUrl(),
                    'bio' => $user->bio,
                    'location' => $user->location,
                    'website' => $user->website,
                    'is_private' => $user->is_private,
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'stats' => $stats
            ]
        ];
    }

    public function actionProfileUpdate()
    {
        $user = Yii::$app->user->identity;
        $user->scenario = 'update';
        
        $user->load(Yii::$app->request->getBodyParams(), '');

        if (!empty($user->newPassword)) {
            $user->setPassword($user->newPassword);
        }

        if ($user->save()) {
            return [
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'avatar' => $user->getAvatarUrl(),
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

    public function actionSearch()
    {
        $q = Yii::$app->request->get('q');
        $limit = (int)Yii::$app->request->get('limit', 20);
        $offset = (int)Yii::$app->request->get('offset', 0);

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
                'avatar' => $user->getAvatarUrl(),
                'followers_count' => Follow::getFollowersCount($user->id),
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

        if (Follow::isFollowing($currentUser->id, $userId)) {
            return [
                'success' => false,
                'error' => 'Вы уже подписаны на этого пользователя'
            ];
        }

        if (Follow::follow($currentUser->id, $userId)) {
            return [
                'success' => true,
                'data' => ['message' => 'Подписка оформлена']
            ];
        }

        return [
            'success' => false,
            'error' => 'Ошибка оформления подписки'
        ];
    }

    public function actionUnfollow()
    {
        $userId = Yii::$app->request->post('user_id');
        $currentUser = Yii::$app->user->identity;

        if (!Follow::isFollowing($currentUser->id, $userId)) {
            return [
                'success' => false,
                'error' => 'Вы не подписаны на этого пользователя'
            ];
        }

        if (Follow::unfollow($currentUser->id, $userId)) {
            return [
                'success' => true,
                'data' => ['message' => 'Подписка отменена']
            ];
        }

        return [
            'success' => false,
            'error' => 'Ошибка отмены подписки'
        ];
    }

    public function actionFollowers()
    {
        $userId = Yii::$app->request->get('user_id');
        $limit = (int)Yii::$app->request->get('limit', 20);
        $offset = (int)Yii::$app->request->get('offset', 0);

        $user = User::findOne($userId);
        if (!$user) {
            return [
                'success' => false,
                'error' => 'Пользователь не найден'
            ];
        }

        $followers = Follow::find()
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
                'avatar' => $follow->follower->getAvatarUrl(),
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

    public function actionFollowing()
    {
        $userId = Yii::$app->request->get('user_id');
        $limit = (int)Yii::$app->request->get('limit', 20);
        $offset = (int)Yii::$app->request->get('offset', 0);

        $user = User::findOne($userId);
        if (!$user) {
            return [
                'success' => false,
                'error' => 'Пользователь не найден'
            ];
        }

        $following = Follow::find()
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
                'avatar' => $follow->following->getAvatarUrl(),
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

    public function actionCheck()
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            return [
                'success' => true,
                'data' => [
                    'valid' => false,
                    'user_id' => null,
                    'username' => null,
                ],
            ];
        }
        
        return [
            'success' => true,
            'data' => [
                'valid' => true,
                'user_id' => $user->id,
                'username' => $user->username,
            ]
        ];
    }
}