<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\User;
use app\components\RateLimiter;
use yii\web\Cookie;

class SiteController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->redirect(['/feed/index']);
    }

    public function actionRegister()
    {
        $rateLimitCheck = RateLimiter::checkRegisterLimit();
        if ($rateLimitCheck !== true) {
            return $this->redirect(['/login']);
        }

        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new User();
        $model->scenario = 'register';
        if ($model->load(Yii::$app->request->post())) {
            $password = $model->password; // теперь password будет загружен
            if ($password) {
                $model->setPassword($password);
            }
            
            if ($model->save()) {
                Yii::$app->session->removeAllFlashes();
                Yii::$app->user->login($model, 3600 * 24 * 30);
                return $this->redirect(['/feed/index']);
            } elseif (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => false, 'errors' => $model->getErrors()];
            }
        }

        return $this->render('register', [
            'model' => $model,
        ]);
    }

    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        // Rate limiting on login attempts (5 per 15 min per IP)
        $rateLimitCheck = RateLimiter::checkAuthLimit();
        if ($rateLimitCheck !== true) {
            Yii::$app->session->setFlash('error', 'Слишком много попыток входа. Попробуйте позже.');
            $model = new LoginForm();
            $model->password = '';
            return $this->render('login', [
                'model' => $model,
            ]);
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }

    public function actionRequestPasswordReset()
    {
        $model = new \app\models\PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Инструкции по восстановлению пароля отправлены на вашу почту.');

                return $this->goHome();
            } else {
                Yii::$app->session->setFlash('error', 'Извините, мы не можем восстановить пароль для указанного email.');
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    public function actionResetPassword($token)
    {
        try {
            $model = new \app\models\ResetPasswordForm($token);
        } catch (\yii\base\InvalidArgumentException $e) {
            throw new \yii\web\BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'Новый пароль успешно установлен.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    public function actionRefresh()
    {
        $session = Yii::$app->session;
        if ($session->has('user_id')) {
            $user = User::findOne($session->get('user_id'));
            if ($user) {
                $session->set('user_data', $user->toArray());
                return $this->asJson(['success' => true]);
            }
        }
        return $this->asJson(['success' => false]);
    }
}
