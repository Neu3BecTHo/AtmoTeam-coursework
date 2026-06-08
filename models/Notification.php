<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Notification model
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property int|null $from_user_id
 * @property int|null $post_id
 * @property int|null $comment_id
 * @property string $message
 * @property int $is_read
 * @property int $created_at
 */
class Notification extends ActiveRecord
{
    public const TYPE_LIKE = 'like';
    public const TYPE_COMMENT = 'comment';
    public const TYPE_FOLLOW = 'follow';
    public const TYPE_MENTION = 'mention';
    public const TYPE_MESSAGE = 'message';
    public const TYPE_REPOST = 'repost';

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
            [['user_id', 'from_user_id', 'post_id', 'comment_id', 'is_read'], 'integer'],
            ['type', 'in', 'range' => [self::TYPE_LIKE, self::TYPE_COMMENT, self::TYPE_FOLLOW, self::TYPE_MENTION, self::TYPE_MESSAGE, self::TYPE_REPOST]],
            ['is_read', 'boolean'],
            ['message', 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Пользователь',
            'type' => 'Тип',
            'from_user_id' => 'От кого',
            'post_id' => 'Пост',
            'comment_id' => 'Комментарий',
            'message' => 'Сообщение',
            'is_read' => 'Прочитано',
            'created_at' => 'Дата',
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
            $message = self::generateMessage($type, $fromUserId);
        }

        $notification = new self([
            'user_id' => $userId,
            'type' => $type,
            'from_user_id' => $fromUserId,
            'post_id' => $postId,
            'message' => $message,
        ]);

        return $notification->save() ? $notification : null;
    }

    private static function generateMessage($type, $fromUserId)
    {
        $fromUsername = $fromUserId ? User::findOne($fromUserId)?->username : 'Кто-то';

        $messages = [
            self::TYPE_LIKE => $fromUsername . ' лайкнул(а) ваш пост',
            self::TYPE_COMMENT => $fromUsername . ' прокомментировал(а) ваш пост',
            self::TYPE_FOLLOW => $fromUsername . ' подписался(ась) на вас',
            self::TYPE_MENTION => $fromUsername . ' упомянул(а) вас в посте',
            self::TYPE_MESSAGE => 'Новое сообщение от ' . $fromUsername,
            self::TYPE_REPOST => $fromUsername . ' сделал(а) репост вашего поста',
        ];

        return $messages[$type] ?? 'Новое уведомление';
    }

    public function markAsRead()
    {
        $this->is_read = 1;
        return $this->save(false);
    }

    public static function markAllAsRead($userId)
    {
        return self::updateAll(['is_read' => 1], ['user_id' => $userId, 'is_read' => 0]);
    }

    public static function getUnread($userId, $limit = 20)
    {
        return self::find()
            ->with(['fromUser', 'post'])
            ->where(['user_id' => $userId, 'is_read' => 0])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    public static function getUnreadCount($userId)
    {
        return self::find()
            ->where(['user_id' => $userId, 'is_read' => 0])
            ->count();
    }

    public function getText(): string
    {
        return $this->message ?: $this->generateMessage($this->type, $this->from_user_id);
    }

    public function getIcon(): string
    {
        $icons = [
            self::TYPE_LIKE => '❤️',
            self::TYPE_COMMENT => '💬',
            self::TYPE_FOLLOW => '👤',
            self::TYPE_MENTION => '@',
            self::TYPE_MESSAGE => '💬',
            self::TYPE_REPOST => '🔄',
        ];

        return $icons[$this->type] ?? '🔔';
    }

    public function fields()
    {
        return [
            'id',
            'type',
            'message' => 'text',
            'icon' => 'icon',
            'is_read',
            'created_at',
            'timeAgo' => function () {
                return $this->getTimeAgo();
            },
            'from_user' => function () {
                return $this->fromUser ? [
                    'id' => $this->fromUser->id,
                    'username' => $this->fromUser->username,
                    'avatar' => $this->fromUser->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $this->fromUser->id,
                ] : null;
            },
            'post_id',
        ];
    }

    private function getTimeAgo()
    {
        $diff = time() - $this->created_at;
        
        if ($diff < 60) return 'только что';
        if ($diff < 3600) return floor($diff / 60) . ' мин. назад';
        if ($diff < 86400) return floor($diff / 3600) . ' ч. назад';
        
        return date('d.m.Y', $this->created_at);
    }
}