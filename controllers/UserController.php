<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use app\models\User;
use app\components\ApiValidator;
use app\components\RateLimiter;

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
        
        $rateLimitCheck = RateLimiter::checkApiLimit();
        if ($rateLimitCheck !== true) {
            return $rateLimitCheck;
        }
        
        $data = ApiValidator::getRequestData();
        $publicKey = $data['public_key'] ?? null;
        
        if (!$publicKey || !is_string($publicKey)) {
            return ['success' => false, 'error' => Yii::t('app', 'Неверный публичный ключ')];
        }
        
        // Валидация: base64, длина 44-88 символов (X25519/Ed25519)
        if (!preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $publicKey)) {
            return ['success' => false, 'error' => Yii::t('app', 'Публичный ключ должен быть в формате base64')];
        }
        
        $decoded = base64_decode($publicKey, true);
        if ($decoded === false) {
            return ['success' => false, 'error' => Yii::t('app', 'Неверный формат base64')];
        }
        
        if (strlen($decoded) < 32 || strlen($decoded) > 64) {
            return ['success' => false, 'error' => Yii::t('app', 'Неверная длина ключа')];
        }
        
        $user = Yii::$app->user->identity;
        $user->public_key = $publicKey;
        $user->key_updated_at = time();
        
        if ($user->save(false)) {
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => Yii::t('app', 'Ошибка сохранения ключа')];
    }
    
    /**
     * Получить публичный ключ пользователя по ID
     */
    public function actionPublicKey($id = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!$id) {
            return ['success' => false, 'error' => 'User ID required'];
        }

        $user = User::findOne($id);
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }

        return [
            'success' => true,
            'public_key' => $user->public_key ?? null,
        ];
    }
}