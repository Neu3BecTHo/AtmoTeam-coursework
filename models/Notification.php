<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;


class Notification extends ActiveRecord
{
    const TYPE_LIKE = 'like';
    const TYPE_COMMENT = 'comment';
    const TYPE_FOLLOW = 'follow';
    const TYPE_MENTION = 'mention';

    public static function tableName()
    {
        return '{{%notification}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public function rules()
    {
        return [
            [['user_id', 'type'], 'required'],
            [['user_id', 'from_user_id', 'post_id', 'comment_id'], 'integer'],
            ['type', 'in', 'range' => [self::TYPE_LIKE, self::TYPE_COMMENT, self::TYPE_FOLLOW, self::TYPE_MENTION]],
            ['is_read', 'boolean'],
            ['message', 'string', 'max' => 255],
        ];
    }

    
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    
    public function getFromUser()
    {
        return $this->hasOne(User::class, ['id' => 'from_user_id']);
    }

    
    public function getPost()
    {
        return $this->hasOne(Post::class, ['id' => 'post_id']);
    }

    
    public static function create($userId, $type, $fromUserId = null, $postId = null, $message = null)
    {

        if ($fromUserId && $fromUserId == $userId) {
            return null;
        }

        if ($message === null) {
            $fromUsername = $fromUserId ? User::findOne($fromUserId)?->username : 'Кто-то';
            
            switch ($type) {
                case self::TYPE_LIKE:
                    $message = $fromUsername . ' лайкнул(а) ваш пост';
                    break;
                case self::TYPE_COMMENT:
                    $message = $fromUsername . ' прокомментировал(а) ваш пост';
                    break;
                case self::TYPE_FOLLOW:
                    $message = $fromUsername . ' подписался(ась) на вас';
                    break;
                case self::TYPE_MENTION:
                    $message = $fromUsername . ' упомянул(а) вас в посте';
                    break;
                default:
                    $message = 'Новое уведомление';
                    break;
            }
        }

        $notification = new self();
        $notification->user_id = $userId;
        $notification->type = $type;
        $notification->from_user_id = $fromUserId;
        $notification->post_id = $postId;
        $notification->message = $message;

        return $notification->save() ? $notification : null;
    }

    
    public function markAsRead()
    {
        $this->is_read = true;
        return $this->save(false);
    }

    
    public static function markAllAsRead($userId)
    {
        return self::updateAll(['is_read' => true], ['user_id' => $userId, 'is_read' => false]);
    }

    
    public static function getUnread($userId, $limit = 20)
    {
        return self::find()
            ->with(['fromUser', 'post'])
            ->where(['user_id' => $userId, 'is_read' => false])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    
    public static function getUnreadCount($userId)
    {
        return self::find()
            ->where(['user_id' => $userId, 'is_read' => false])
            ->count();
    }

    
    public function getText()
    {
        if ($this->message) {
            return $this->message;
        }

        $fromUsername = $this->fromUser ? $this->fromUser->username : 'Кто-то';

        switch ($this->type) {
            case self::TYPE_LIKE:
                return $fromUsername . ' лайкнул(а) ваш пост';
            case self::TYPE_COMMENT:
                return $fromUsername . ' прокомментировал(а) ваш пост';
            case self::TYPE_FOLLOW:
                return $fromUsername . ' подписался(ась) на вас';
            case self::TYPE_MENTION:
                return $fromUsername . ' упомянул(а) вас в посте';
            default:
                return 'Новое уведомление';
        }
    }

    
    public function getIcon()
    {
        switch ($this->type) {
            case self::TYPE_LIKE:
                return '❤️';
            case self::TYPE_COMMENT:
                return '💬';
            case self::TYPE_FOLLOW:
                return '👤';
            case self::TYPE_MENTION:
                return '@';
            default:
                return '🔔';
        }
    }
}
