<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\User;

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
            $password = $model->password;
            if ($password) {
                $model->setPassword($password);
            }
            
            if ($model->save()) {

                foreach (Yii::$app->session->getAllFlashes() as $key => $value) {
                    Yii::$app->session->removeFlash($key);
                }
                Yii::$app->user->login($model, 3600 * 24 * 30);
                return $this->redirect(['/feed/index']);
            } else {

                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return [
                        'success' => false,
                        'errors' => $model->getErrors()
                    ];
                }
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

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {

            foreach (Yii::$app->session->getAllFlashes() as $key => $value) {
                Yii::$app->session->removeFlash($key);
            }
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

}
