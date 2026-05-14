<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use app\models\User;
use app\models\Message;
use app\components\ApiValidator;
use app\components\RateLimiter;

class MessageController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'dialogue', 'get-dialogue', 'send', 'mark-read', 'unread-count', 'get-dialogues'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    
    public function actionIndex()
    {
        $userId = Yii::$app->user->id;

        $currentUserId = Yii::$app->user->id;
        
        $dialogues = (new \yii\db\Query())
            ->select([
                'u.id',
                'u.username', 
                'u.avatar',
                'm.created_at as last_message_time',
                'm.content as last_message',
                'SUM(CASE WHEN m.sender_id != :current_user_id AND m.is_read = 0 THEN 1 ELSE 0 END) as unread_count'
            ])
            ->from('message m')
            ->innerJoin('user u', '(u.id = m.sender_id AND u.id != :current_user_id) OR (u.id = m.receiver_id AND u.id != :current_user_id)')
            ->where(['or', 
                ['m.sender_id' => $currentUserId],
                ['m.receiver_id' => $currentUserId]
            ])
            ->andWhere(['m.id' => (new \yii\db\Query())
                ->select('MAX(id)')
                ->from('message m2')
                ->where(['or', 
                    ['m2.sender_id' => $currentUserId, 'm2.receiver_id' => new \yii\db\Expression('u.id')],
                    ['m2.receiver_id' => $currentUserId, 'm2.sender_id' => new \yii\db\Expression('u.id')]
                ])
            ])
            ->addParams([':current_user_id' => $currentUserId])
            ->groupBy(['u.id', 'u.username', 'u.avatar', 'm.created_at', 'm.content'])
            ->orderBy(['last_message_time' => SORT_DESC])
            ->all();

        $dialoguesWithUsers = [];
        $currentUser = User::findOne(Yii::$app->user->id);
        foreach ($dialogues as $dialogue) {

            if (!$currentUser->canInteractWith($dialogue['id'])) {
                continue;
            }
            
            $dialoguesWithUsers[] = [
                'user' => [
                    'id' => $dialogue['id'],
                    'username' => $dialogue['username'],
                    'avatar' => $dialogue['avatar'] ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $dialogue['id'],
                ],
                'last_message_time' => $dialogue['last_message_time'],
                'last_message' => $dialogue['last_message'],
                'unread_count' => (int)$dialogue['unread_count'],
            ];
        }
        
        return $this->render('index', [
            'dialogues' => $dialoguesWithUsers,
        ]);
    }

    
    public function actionDialogue($id = null)
    {
        if (!$id) {
            return $this->redirect(['/message/index']);
        }

        $userId = Yii::$app->user->id;
        $otherUser = User::findOne($id);
        
        if (!$otherUser) {
            throw new NotFoundHttpException('Пользователь не найден');
        }

        $currentUser = User::findOne($userId);
        if (!$currentUser->canInteractWith($id)) {
            Yii::$app->session->setFlash('error', 'Вы не можете общаться с этим пользователем');
            return $this->redirect(['/message/index']);
        }

        Message::markAsRead($userId, $id);

        $messages = Message::getDialogue($userId, $id);
        
        return $this->render('dialogue', [
            'otherUser' => $otherUser,
            'messages' => $messages,
        ]);
    }

    
    public function actionSend()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $authCheck = ApiValidator::requireAuth();
        if ($authCheck !== true) {
            return $authCheck;
        }

        $rateLimitCheck = RateLimiter::checkMessageLimit();
        if ($rateLimitCheck !== true) {
            return $rateLimitCheck;
        }

        $data = json_decode(Yii::$app->request->getRawBody(), true);
        $receiverId = $data['receiver_id'] ?? Yii::$app->request->post('receiver_id');
        $content = $data['content'] ?? Yii::$app->request->post('content');
        
        if (empty($content)) {
            return ApiValidator::error('Некорректное сообщение');
        }

        $contentLength = mb_strlen($content, 'UTF-8');
        if ($contentLength > 1000) {
            return ApiValidator::error('Сообщение слишком длинное (максимум 1000 символов)');
        }
        
        $receiver = User::findOne($receiverId);
        if (!$receiver) {
            return ApiValidator::error('Пользователь не найден');
        }
        
        if (!Message::canSendMessage(Yii::$app->user->id, $receiverId)) {
            return ['success' => false, 'error' => 'Нельзя отправлять сообщения этому пользователю'];
        }
        
        $message = Message::sendMessage(Yii::$app->user->id, $receiverId, $content);
        
        if ($message) {
            return ['success' => true, 'message' => $message->toArray()];
        }
        
        return ['success' => false, 'error' => 'Ошибка отправки'];
    }

    
    public function actionMarkRead()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $data = json_decode(Yii::$app->request->getRawBody(), true);
        $senderId = $data['sender_id'] ?? Yii::$app->request->post('sender_id');
        
        Message::markAsRead(Yii::$app->user->id, $senderId);
        
        return ['success' => true];
    }

    
    public function actionUnreadCount()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $count = Message::getUnreadCount(Yii::$app->user->id);
        
        return ['success' => true, 'count' => $count];
    }

    
    public function actionGetDialogue($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'error' => 'Не авторизован'];
        }
        
        $userId = Yii::$app->user->id;
        $otherUser = User::findOne($id);
        
        if (!$otherUser) {
            return ['success' => false, 'error' => 'Пользователь не найден'];
        }

        Message::markAsRead($userId, $id);
        
        $messages = Message::getDialogue($userId, $id, 50);
        
        return [
            'success' => true,
            'messages' => array_map(function($msg) {
                return $msg->toArray();
            }, $messages)
        ];
    }

    
    public function actionGetDialogues()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'error' => 'Не авторизован'];
        }
        
        $userId = Yii::$app->user->id;
        $dialogues = Message::getDialogues($userId);

        $dialoguesWithUsers = [];
        $currentUser = User::findOne($userId);
        foreach ($dialogues as $dialogue) {
            $otherUserId = $dialogue['other_user_id'];
            $otherUser = User::findOne($otherUserId);
            if ($otherUser) {

                if (!$currentUser->canInteractWith($otherUserId)) {
                    continue;
                }

                $unreadCount = Message::find()
                    ->where(['receiver_id' => $userId, 'sender_id' => $otherUserId, 'is_read' => 0])
                    ->count();
                
                $dialoguesWithUsers[] = [
                    'user' => [
                        'id' => $otherUser->id,
                        'username' => $otherUser->username,
                        'avatar' => $otherUser->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $otherUser->id,
                    ],
                    'last_message_time' => $dialogue['last_message_time'],
                    'last_message' => $dialogue['last_message'] ?? null,
                    'unread_count' => $unreadCount,
                ];
            }
        }
        
        return [
            'success' => true,
            'dialogues' => $dialoguesWithUsers
        ];
    }
}
