<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;
use app\models\User;
use app\models\Message;
use app\models\Notification;
use app\components\ApiValidator;
use app\components\RateLimiter;

/**
 * MessageController - контроллер для работы с сообщениями
 */
class MessageController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'dialogue', 'get-dialogue', 'send', 'mark-read', 'unread-count', 'get-dialogues', 'upload-images', 'delete'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (in_array($action->id, ['send', 'mark-read', 'unread-count', 'get-dialogues', 'upload-images'])) {
            $this->enableCsrfValidation = false;
            // CSRF disabled for API endpoints - clients must use X-CSRF-Token header
            // Double Submit Cookie pattern recommended
        }
        
        return parent::beforeAction($action);
    }

    /**
     * Страница диалогов
     */
    public function actionIndex()
    {
        $userId = Yii::$app->user->id;
        $dialogues = Message::getDialogues($userId);
        
        return $this->render('index', [
            'dialogues' => $dialogues,
        ]);
    }

    /**
     * Страница диалога с пользователем
     */
    public function actionDialogue($id = null)
    {
        if (!$id) {
            return $this->redirect(['/message/index']);
        }

        $userId = Yii::$app->user->id;
        $otherUser = User::findOne($id);
        
        if (!$otherUser) {
        if (!$userId || !$otherUser->canInteractWith($userId)) {
            return ['success' => false, 'error' => 'Общение недоступно'];
        }
            throw new NotFoundHttpException('Пользователь не найден');
        }

        $currentUser = Yii::$app->user->identity;
        if (!$currentUser instanceof User || !$currentUser->canInteractWith($id)) {
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

    /**
     * API: Отправить сообщение
     */
    public function actionSend()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $authCheck = ApiValidator::requireAuth();
        if ($authCheck !== true) return $authCheck;

        $rateLimitCheck = RateLimiter::checkMessageLimit();
        if ($rateLimitCheck !== true)
            return $rateLimitCheck;

        $data = ApiValidator::getRequestData();
        $receiverId = $data['receiver_id'] ?? null;
        $encryptedText = $data['encrypted_text'] ?? '';
        $encryptedKey = $data['encrypted_key'] ?? '';
        $content = trim($data['content'] ?? '');

        // 1. Проверяем наличие хоть каких-то данных
        if (empty($encryptedText) && empty($encryptedKey) && empty($content)) {
            return ApiValidator::error('Нет данных сообщения');
        }

        // 2. Проверяем получателя
        $receiver = User::findOne($receiverId);
        if (!$receiver) {
            return ApiValidator::error('Пользователь не найден');
        }

        // 3. Проверяем возможность отправки (блокировки и т.д.)
        if (!Message::canSendMessage(Yii::$app->user->id, (int)$receiverId)) {
            return ApiValidator::error('Нельзя отправлять сообщения этому пользователю');
        }

        // 4. Отправка зашифрованного сообщения (текст)
        if (!empty($encryptedText) && !empty($encryptedKey)) {
            $message = Message::sendMessage(Yii::$app->user->id, (int)$receiverId, $encryptedText, $encryptedKey);
            if ($message) {
                return ['success' => true, 'message' => $message->toArray()];
            }
            return ApiValidator::error('Ошибка отправки зашифрованного сообщения');
        }

        // 5. Отправка незашифрованного сообщения (например, изображения или fallback)
        if (!empty($content)) {
            $message = Message::sendUnencryptedMessage(Yii::$app->user->id, (int)$receiverId, $content);
            if ($message) {
                return ['success' => true, 'message' => $message->toArray()];
            }
            return ApiValidator::error('Ошибка отправки сообщения');
        }

        return ApiValidator::error('Некорректное сообщение');
    }

    /**
     * API: Отметить сообщения как прочитанные
     */
    public function actionMarkRead()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = json_decode(Yii::$app->request->getRawBody(), true);
        $senderId = $data['sender_id'] ?? Yii::$app->request->post('sender_id');
        
        Message::markAsRead(Yii::$app->user->id, $senderId);
        
        return ['success' => true];
    }

    /**
     * API: Получить непрочитанные сообщения
     */
    public function actionUnreadCount()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $count = Message::getUnreadCount(Yii::$app->user->id);
        
        return ['success' => true, 'count' => $count];
    }

    /**
     * API: Получить диалог с пользователем
     */
    public function actionGetDialogue($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        Yii::$app->response->headers->set('Pragma', 'no-cache');
        Yii::$app->response->headers->set('Expires', '0');

        $userId = Yii::$app->user->id;
        $otherUser = User::findOne($id);

        if (!$otherUser) {
            return ['success' => false, 'error' => 'Пользователь не найден'];
        }

        if (!$userId || !$otherUser->canInteractWith($userId)) {
            return ['success' => false, 'error' => 'Общение недоступно'];
        }

        // Используем лимит для диалогов (120 в минуту)
        $rateLimitCheck = RateLimiter::checkDialogueLimit();
        if ($rateLimitCheck !== true) {
            return $rateLimitCheck;
        }

        Message::markAsRead($userId, $id);
        $messages = Message::getDialogue($userId, $id, 50);

        return [
            'success' => true,
            'messages' => array_map(fn($msg) => $msg->toArray(), $messages)
        ];
    }
    /**
     * API: Получить список диалогов
     */
    public function actionGetDialogues()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $userId = Yii::$app->user->id;

        // Используем лимит для списка диалогов (60 в минуту)
        $rateLimitCheck = RateLimiter::checkDialoguesListLimit();
        if ($rateLimitCheck !== true) {
            return $rateLimitCheck;
        }

        $dialogues = Message::getDialogues($userId);
        return ['success' => true, 'dialogues' => $dialogues];
    }

    /**
     * API: Удалить сообщение (только своё)
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = json_decode(Yii::$app->request->getRawBody(), true);
        $messageId = (int) ($data['message_id'] ?? 0);

        if (!$messageId) {
            return ['success' => false, 'error' => 'Неверный ID сообщения'];
        }

        $message = Message::findOne($messageId);
        if (!$message) {
            return ['success' => false, 'error' => 'Сообщение не найдено'];
        }

        // Можно удалять только свои сообщения
        if ($message->sender_id !== Yii::$app->user->id) {
            return ['success' => false, 'error' => 'Нельзя удалить чужое сообщение'];
        }

        // Удаляем изображения с сервера
        $this->deleteMessageImages($message);

        if ($message->delete()) {
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Ошибка удаления сообщения'];
    }

    /**
     * Удалить изображения сообщения с сервера
     */
    private function deleteMessageImages($message)
    {
        $imageUrls = $message->getImageUrls();
        if (empty($imageUrls)) {
            return;
        }

        $baseUrl = Yii::$app->request->baseUrl;
        foreach ($imageUrls as $url) {
            // Преобразуем URL в путь к файлу
            if (strpos($url, $baseUrl) === 0) {
                $relativePath = substr($url, strlen($baseUrl));
                $fullPath = Yii::getAlias('@webroot') . $relativePath;
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }
        }
    }

    /**
     * Получить список диалогов пользователя
     */
    public static function getDialogues($userId, $limit = 20, $offset = 0)
    {
        return Message::getDialogues($userId, $limit, $offset);
    }

    /**
     * API: Загрузить изображения для сообщения
     */
    public function actionUploadImages()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $rateLimitCheck = RateLimiter::checkImageUploadLimit();
        if ($rateLimitCheck !== true)
            return $rateLimitCheck;

        $receiverId = (int) Yii::$app->request->post('receiver_id');
        $content = trim(Yii::$app->request->post('content', ''));

        $receiver = User::findOne($receiverId);
        if (!$receiver) {
            return ['success' => false, 'error' => 'Пользователь не найден'];
        }

        if (!Message::canSendMessage(Yii::$app->user->id, $receiverId)) {
            return ['success' => false, 'error' => 'Нельзя отправлять сообщения этому пользователю'];
        }

        $images = UploadedFile::getInstancesByName('images');
        if (empty($images)) {
            $images = UploadedFile::getInstancesByName('images[]');
        }

        if (empty($images)) {
            return ['success' => false, 'error' => 'Изображения не найдены'];
        }

        $uploadedImages = [];
        foreach ($images as $image) {
            $url = $this->uploadMessageImage($image);
            if ($url !== null) {
                $uploadedImages[] = $url;
            }
        }

        if (empty($uploadedImages)) {
            return ['success' => false, 'error' => 'Не удалось загрузить ни одного изображения'];
        }

        $message = new Message([
            'sender_id' => Yii::$app->user->id,
            'receiver_id' => $receiverId,
            'content' => $content,
            'image_urls' => json_encode($uploadedImages, JSON_UNESCAPED_UNICODE),
        ]);

        if ($message->save()) {
            \app\models\Notification::create(
                $receiverId,
                \app\models\Notification::TYPE_MESSAGE,
                Yii::$app->user->id,
                null,
                'Новое сообщение с изображениями'
            );

            // ⚠️ ВАЖНО: используем $message->toArray(), но image_urls должно быть массивом
            $messageData = $message->toArray();
            // Переопределяем image_urls, т.к. в toArray() оно возвращается как массив через getImageUrls()
            // Но если в fields() уже определено, то все ок
            return ['success' => true, 'message' => $messageData];
        }

        return ['success' => false, 'error' => 'Не удалось отправить сообщение'];
    }

    /**
     * Загрузить изображение сообщения
     */
    private function uploadMessageImage(UploadedFile $image): ?string
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $extension = strtolower($image->extension);

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($image->tempName);
        if (!in_array($mimeType, $allowedMimes, true)) {
            return null;
        }

        if (!in_array($extension, $allowedExtensions, true) || $image->size > 5 * 1024 * 1024) {
            return null;
        }

        $uploadDir = Yii::getAlias('@webroot/uploads/messages/' . date('Y/m'));
        FileHelper::createDirectory($uploadDir, 0755, true);

        $filename = uniqid('msgimg_') . '_' . time() . '.' . $extension;
        $filePath = $uploadDir . '/' . $filename;

        if ($image->saveAs($filePath)) {
            return Yii::$app->request->baseUrl . '/uploads/messages/' . date('Y/m') . '/' . $filename;
        }

        return null;
    }

    private function getDialoguesWithUsers(int $userId): array
    {
        $dialogues = Message::getDialogues($userId);
        $dialoguesWithUsers = [];
        $currentUser = User::findOne($userId);
        
        foreach ($dialogues as $dialogue) {
            // Проверяем наличие ключа other_user_id
            if (!isset($dialogue['other_user_id'])) {
                Yii::error('Missing other_user_id in dialogue: ' . json_encode($dialogue), 'message');
                continue;
            }
            
            $otherUserId = $dialogue['other_user_id'];
            $otherUser = User::findOne($otherUserId);
            
            if (!$otherUser || !$currentUser->canInteractWith($otherUserId)) {
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
                'last_message_time' => $dialogue['last_message_time'] ?? 0,
                'last_message' => $dialogue['last_message'] ?? null,
                'unread_count' => $unreadCount,
            ];
        }
        
        return $dialoguesWithUsers;
    }
}