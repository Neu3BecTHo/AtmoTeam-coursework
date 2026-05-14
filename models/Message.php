<?php

namespace app\models;

use yii\db\ActiveRecord;

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
                'class' => \yii\behaviors\TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
            ],
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        
        return true;
    }

    public function rules()
    {
        return [
            [['sender_id', 'receiver_id', 'content'], 'required'],
            [['sender_id', 'receiver_id'], 'integer'],
            [['content'], 'string'],
            ['is_read', 'boolean'],
            ['content', 'match', 'pattern' => '/^[\p{L}\p{N}\p{M}\p{P}\p{S}\p{Z}\h\n\r\t]{1,1000}$/u', 'message' => 'Сообщение содержит недопустимые символы'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sender_id' => 'Отправитель',
            'receiver_id' => 'Получатель',
            'content' => 'Сообщение',
            'is_read' => 'Прочитано',
            'created_at' => 'Дата',
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
        return date('d.m.Y H:i', $this->created_at);
    }

    
    public static function sendMessage($senderId, $receiverId, $content)
    {
        if ($senderId === $receiverId) {
            return false;
        }

        $sender = User::findOne($senderId);
        $receiver = User::findOne($receiverId);
        
        if (!$sender || !$receiver) {
            return false;
        }

        if (!$sender->canInteractWith($receiverId)) {
            return false;
        }

        $message = new self([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'content' => $content,
        ]);

        if ($message->save()) {
            $notification = new Notification([
                'user_id' => $receiverId,
                'from_user_id' => $senderId,
                'type' => 'message',
                'message' => 'Новое сообщение от ' . $sender->username,
            ]);
            $notification->save();
            
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
        $sql = "
            SELECT DISTINCT 
                CASE 
                    WHEN sender_id = :userId THEN receiver_id 
                    ELSE sender_id 
                END as other_user_id,
                MAX(created_at) as last_message_time
            FROM {{%message}}
            WHERE sender_id = :userId OR receiver_id = :userId
            GROUP BY other_user_id
            ORDER BY last_message_time DESC
            LIMIT :limit OFFSET :offset
        ";

        return \Yii::$app->db->createCommand($sql)
            ->bindValue(':userId', $userId)
            ->bindValue(':limit', $limit)
            ->bindValue(':offset', $offset)
            ->queryAll();
    }

    
    public static function markAsRead($receiverId, $senderId = null)
    {
        if ($senderId) {
            return self::updateAll(['is_read' => 1], [
                'receiver_id' => $receiverId, 
                'sender_id' => $senderId, 
                'is_read' => 0
            ]);
        }
        
        return self::updateAll(['is_read' => 1], ['receiver_id' => $receiverId, 'is_read' => 0]);
    }

    
    public static function getUnreadCount($userId)
    {
        return self::find()
            ->where(['receiver_id' => $userId, 'is_read' => 0])
            ->count();
    }

    
    public static function canSendMessage($senderId, $receiverId)
    {
        if ($senderId === $receiverId) {
            return false;
        }

        $sender = User::findOne($senderId);
        if (!$sender) {
            return false;
        }

        return $sender->canInteractWith($receiverId);
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $data = parent::toArray($fields, $expand, $recursive);
        $data['sender'] = $this->sender ? [
            'id' => $this->sender->id,
            'username' => $this->sender->username,
            'avatar' => $this->sender->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $this->sender->id,
        ] : null;
        $data['receiver'] = $this->receiver ? [
            'id' => $this->receiver->id,
            'username' => $this->receiver->username,
            'avatar' => $this->receiver->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $this->receiver->id,
        ] : null;
        $data['timeAgo'] = $this->getTimeAgo();
        return $data;
    }
}
