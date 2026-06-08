<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use app\components\ApiValidator;
use app\models\User;
use app\models\Block;

class BlockController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['list', 'block', 'unblock'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionList()
    {
        $blockedUsers = Block::getBlockedUsers(Yii::$app->user->id);
        
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'success' => true,
                'blocked_users' => array_map(function($block) {
                    return [
                        'id' => $block->blockedUser->id,
                        'username' => $block->blockedUser->username,
                        'avatar' => $block->blockedUser->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $block->blockedUser->id,
                        'blocked_at' => $block->created_at,
                    ];
                }, $blockedUsers),
            ];
        }
        
        return $this->render('list', [
            'blockedUsers' => $blockedUsers,
        ]);
    }

    public function actionBlock()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $data = ApiValidator::getRequestData();
        $blockedUserId = $data['user_id'] ?? Yii::$app->request->post('user_id');
        
        $blockedUser = User::findOne($blockedUserId);
        if (!$blockedUser) {
            return ['success' => false, 'error' => 'Пользователь не найден'];
        }
        
        if ($blockedUserId == Yii::$app->user->id) {
            return ['success' => false, 'error' => 'Нельзя заблокировать себя'];
        }
        
        $currentUser = User::findOne(Yii::$app->user->id);
        if ($currentUser->hasBlocked($blockedUserId)) {
            return ['success' => false, 'error' => 'Пользователь уже заблокирован'];
        }
        
        if ($currentUser->blockUser($blockedUserId)) {
            return ['success' => true, 'message' => 'Пользователь заблокирован'];
        }
        
        return ['success' => false, 'error' => 'Ошибка блокировки'];
    }

    public function actionUnblock()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $data = ApiValidator::getRequestData();
        $blockedUserId = $data['user_id'] ?? Yii::$app->request->post('user_id');
        
        $blockedUser = User::findOne($blockedUserId);
        if (!$blockedUser) {
            return ['success' => false, 'error' => 'Пользователь не найден'];
        }
        
        $currentUser = User::findOne(Yii::$app->user->id);
        if ($currentUser->unblockUser($blockedUserId)) {
            return ['success' => true, 'message' => 'Пользователь разблокирован'];
        }
        
        return ['success' => false, 'error' => 'Ошибка разблокировки'];
    }
}