<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use app\models\User;
use app\components\ApiValidator;

class UserController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['save-public-key'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['public-key'],
                        'allow' => true,
                        'roles' => ['@', '?'], // гости тоже могут, но им не нужно
                    ],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (in_array($action->id, ['save-public-key'])) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    /**
     * Сохранить публичный ключ текущего пользователя
     */
    public function actionSavePublicKey()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $authCheck = ApiValidator::requireAuth();
        if ($authCheck !== true) {
            return $authCheck;
        }
        
        $data = ApiValidator::getRequestData();
        $publicKey = $data['public_key'] ?? null;
        
        if (!$publicKey || !is_string($publicKey)) {
            return ['success' => false, 'error' => 'Неверный публичный ключ'];
        }
        
        $user = Yii::$app->user->identity;
        $user->public_key = $publicKey;
        $user->key_updated_at = time();
        
        if ($user->save(false)) {
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Ошибка сохранения ключа'];
    }
    
    /**
     * Получить публичный ключ пользователя по ID
     */
    public function actionPublicKey($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $user = User::findOne($id);
        if (!$user) {
            return ['success' => false, 'error' => 'Пользователь не найден'];
        }
        
        if (Yii::$app->user->isGuest && !$user->canViewProfile(null)) {
            return ['success' => false, 'error' => 'Доступ запрещён'];
        }
        
        return [
            'success' => true,
            'public_key' => $user->public_key
        ];
    }
}