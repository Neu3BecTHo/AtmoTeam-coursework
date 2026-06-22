<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\User;
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

        return $this->render('register', ['model' => $model]);
    }

    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            Yii::$app->session->removeAllFlashes();
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', ['model' => $model]);
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }

    public function actionLanguage($lang)
    {
        $allowed = ['en-US', 'ru-RU', 'ru_RU', 'en_US', 'es-ES', 'es_ES'];
        if (!in_array($lang, $allowed, true)) {
            return $this->goHome();
        }

        $lang = str_replace('_', '-', $lang);

        setcookie('language', $lang, time() + 60 * 60 * 24 * 365, '/', '', false, true);

        // Устанавливаем язык
        Yii::$app->language = $lang;
        Yii::$app->session->set('language', $lang);

        // Возвращаемся на предыдущую страницу
        $ref = Yii::$app->request->referrer;
        return $this->redirect($ref ?: ['/']);
    }
}