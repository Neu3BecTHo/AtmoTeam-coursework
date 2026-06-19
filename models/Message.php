<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Query;

/**
 * Message model
 *
 * @property int $id
 * @property int $sender_id
 * @property int $receiver_id
 * @property string $content
 * @property string|null $image_urls
 * @property int $is_read
 * @property int $created_at
 * @property int $updated_at
 */
class Message extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%message}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
            ],
        ];
    }

    public function rules()
    {
        return [
            [['sender_id', 'receiver_id'], 'required'],
            [['encrypted_text', 'encrypted_key'], 'safe'],
            [['sender_id', 'receiver_id', 'is_read'], 'integer'],
            [['content', 'image_urls'], 'string'],
            ['is_read', 'boolean'],
            ['content', 'validateContentOrImages'],
            ['content', 'string', 'max' => 1000],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sender_id' => 'Отправитель',
            'receiver_id' => 'Получатель',
            'content' => 'Сообщение',
            'image_urls' => 'Изображения',
            'is_read' => 'Прочитано',
            'created_at' => 'Дата отправки',
            'updated_at' => 'Обновлено',
        ];
    }

    public function getSender()
    {
        return $this->hasOne(User::class, ['id' => 'sender_id']);
    }

    public function getReceiver()
    {
        return $this->hasOne(User::class, ['id' => 'receiver_id']);
    }

    public function getTimeAgo()
    {
        $diff = time() - $this->created_at;
        
        if ($diff < 60) return 'только что';
        if ($diff < 3600) return floor($diff / 60) . ' мин. назад';
        if ($diff < 86400) return floor($diff / 3600) . ' ч. назад';
        
        return date('d.m.Y H:i', $this->created_at);
    }

    public function getImageUrls(): array
    {
        if (!$this->image_urls) {
            return [];
        }
        $urls = json_decode($this->image_urls, true);
        return is_array($urls) ? $urls : [];
    }

    public function hasImages(): bool
    {
        return !empty($this->getImageUrls());
    }

    public function getPreviewText(): string
    {
        $text = trim($this->content);
        if ($text !== '') {
            return mb_substr($text, 0, 100);
        }
        return $this->hasImages() ? '📷 Фото' : '';
    }

    public function validateContentOrImages($attribute, $params)
    {
        if (trim($this->content) === '' && !$this->hasImages()) {
            $this->addError('content', 'Сообщение не может быть пустым');
        }
    }

    public function fields()
    {
        return [
            'id',
            'sender_id',
            'receiver_id',
            'encrypted_text',
            'encrypted_key',
            'content',
            'image_urls' => function() { return $this->getImageUrls(); },
            'is_read',
            'created_at',
            'updated_at',
            'timeAgo' => 'timeAgo',
            'sender' => function () {
                return $this->sender ? [
                    'id' => $this->sender->id,
                    'username' => $this->sender->username,
                    'avatar' => $this->sender->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $this->sender->id,
                ] : null;
            },
            'receiver' => function () {
                return $this->receiver ? [
                    'id' => $this->receiver->id,
                    'username' => $this->receiver->username,
                    'avatar' => $this->receiver->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $this->receiver->id,
                ] : null;
            },
        ];
    }

    public static function sendMessage($senderId, $receiverId, $encryptedText, $encryptedKey)
    {
        // Проверка возможности отправки
        if ($senderId === $receiverId || !self::canSendMessage($senderId, $receiverId)) {
            return false;
        }

        $message = new self([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'encrypted_text' => $encryptedText,
            'encrypted_key' => $encryptedKey,
            'content' => '🔒 Зашифрованное сообщение',
        ]);

        if ($message->save()) {
            Notification::create(
                $receiverId,
                Notification::TYPE_MESSAGE,
                $senderId,
                null,
                'Новое зашифрованное сообщение'
            );
            return $message;
        }

        return false;
    }

    public static function sendUnencryptedMessage($senderId, $receiverId, $content)
    {
        if ($senderId === $receiverId || !self::canSendMessage($senderId, $receiverId)) {
            return false;
        }
        $message = new self([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'content' => $content,
            'encrypted_text' => null,
            'encrypted_key' => null,
        ]);
        if ($message->save()) {
            Notification::create($receiverId, Notification::TYPE_MESSAGE, $senderId, null, 'Новое сообщение');
            return $message;
        }
        return false;
    }

    public static function getDialogue($userId1, $userId2, $limit = 50, $offset = 0)
    {
        return self::find()
            ->with(['sender', 'receiver'])
            ->where([
                'or',
                ['and', ['sender_id' => $userId1], ['receiver_id' => $userId2]],
                ['and', ['sender_id' => $userId2], ['receiver_id' => $userId1]],
            ])
            ->orderBy(['created_at' => SORT_ASC])
            ->limit($limit)
            ->offset($offset)
            ->all();
    }

    public static function getDialogues($userId, $limit = 20, $offset = 0)
    {
        // 1. Находим всех уникальных собеседников, с кем были сообщения
        $messages = self::find()
            ->select(['sender_id', 'receiver_id'])
            ->where(['or', ['sender_id' => $userId], ['receiver_id' => $userId]])
            ->asArray()
            ->all();

        $otherUserIds = [];
        foreach ($messages as $msg) {
            if ($msg['sender_id'] == $userId) {
                $otherUserIds[$msg['receiver_id']] = true;
            } else {
                $otherUserIds[$msg['sender_id']] = true;
            }
        }
        $otherUserIds = array_keys($otherUserIds);

        if (empty($otherUserIds)) {
            return [];
        }

        // 2. Для каждого собеседника получаем последнее сообщение
        $dialogues = [];
        foreach ($otherUserIds as $otherId) {
            $lastMessage = self::find()
                ->with(['sender', 'receiver'])
                ->where([
                    'or',
                    ['and', ['sender_id' => $userId, 'receiver_id' => $otherId]],
                    ['and', ['sender_id' => $otherId, 'receiver_id' => $userId]],
                ])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(1)
                ->one();

            if (!$lastMessage) {
                continue;
            }

            $otherUser = User::findOne($otherId);
            if (!$otherUser) {
                continue;
            }

            $dialogues[] = [
                'user' => [
                    'id' => $otherUser->id,
                    'username' => $otherUser->username,
                    'avatar' => $otherUser->getAvatarUrl(),
                ],
                'last_message' => $lastMessage->getPreviewText(),
                'last_message_time' => $lastMessage->created_at,
                'last_message_time_ago' => $lastMessage->timeAgo,
                'unread_count' => self::getUnreadCountFromUser($userId, $otherId),
            ];
        }

        // 3. Сортируем по времени последнего сообщения (новые сверху)
        usort($dialogues, function ($a, $b) {
            return $b['last_message_time'] - $a['last_message_time'];
        });

        // 4. Пагинация
        return array_slice($dialogues, $offset, $limit);
    }

    public static function markAsRead($receiverId, $senderId = null)
    {
        $condition = ['receiver_id' => $receiverId, 'is_read' => 0];
        if ($senderId) {
            $condition['sender_id'] = $senderId;
        }
        return self::updateAll(['is_read' => 1], $condition);
    }

    public static function getUnreadCount($userId)
    {
        return self::find()
            ->where(['receiver_id' => $userId, 'is_read' => 0])
            ->count();
    }

    public static function getUnreadCountFromUser($receiverId, $senderId)
    {
        return self::find()
            ->where(['receiver_id' => $receiverId, 'sender_id' => $senderId, 'is_read' => 0])
            ->count();
    }

    public static function getUnreadCounts($receiverId): array
    {
        $counts = (new Query())
            ->select(['sender_id', 'unread_count' => 'COUNT(*)'])
            ->from(self::tableName())
            ->where(['receiver_id' => $receiverId, 'is_read' => 0])
            ->groupBy('sender_id')
            ->indexBy('sender_id')
            ->column();

        return array_map('intval', $counts);
    }

    public static function canSendMessage($senderId, $receiverId)
    {
        if ($senderId === $receiverId) {
            return false;
        }

        $sender = User::findOne($senderId);
        return $sender && $sender->canInteractWith($receiverId);
    }
}