<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * OnlineUser model
 *
 * @property int $id
 * @property int $user_id
 * @property string $session_id
 * @property string $ip_address
 * @property string|null $user_agent
 * @property int $last_activity
 * @property int $created_at
 */
class OnlineUser extends ActiveRecord
{
    const ONLINE_TTL = 1800; // 30 минут

    public static function tableName()
    {
        return '{{%online_user}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
                'createdAtAttribute' => 'created_at',
            ],
        ];
    }

    public function rules()
    {
        return [
            [['user_id', 'session_id', 'ip_address'], 'required'],
            [['user_id', 'last_activity', 'created_at'], 'integer'],
            [['session_id'], 'string', 'max' => 255],
            [['ip_address'], 'string', 'max' => 45],
            [['user_agent'], 'string', 'max' => 500],
            [['user_id', 'session_id'], 'unique', 'targetAttribute' => ['user_id', 'session_id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Пользователь',
            'session_id' => 'Сессия',
            'ip_address' => 'IP-адрес',
            'user_agent' => 'Браузер',
            'last_activity' => 'Последняя активность',
            'created_at' => 'Время входа',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public static function updateActivity($userId)
    {
        $sessionId = session_id();
        if (!$sessionId) {
            return false;
        }

        $now = time();

        $onlineUser = self::find()
            ->where(['user_id' => $userId, 'session_id' => $sessionId])
            ->one();

        if ($onlineUser) {
            $onlineUser->last_activity = $now;
            return $onlineUser->save(false);
        }

        $onlineUser = new self([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'ip_address' => Yii::$app->request->userIP,
            'user_agent' => Yii::$app->request->userAgent,
            'last_activity' => $now,
        ]);

        return $onlineUser->save();
    }

    public static function removeUser($userId)
    {
        return self::deleteAll(['user_id' => $userId]);
    }

    public static function getOnlineCount()
    {
        return self::find()
            ->where(['>', 'last_activity', time() - self::ONLINE_TTL])
            ->distinct('user_id')
            ->count();
    }

    public static function getOnlineUsers($limit = 50)
    {
        return self::find()
            ->with('user')
            ->where(['>', 'last_activity', time() - self::ONLINE_TTL])
            ->orderBy(['last_activity' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    public static function isUserOnline($userId)
    {
        return self::find()
            ->where(['user_id' => $userId])
            ->andWhere(['>', 'last_activity', time() - self::ONLINE_TTL])
            ->exists();
    }

    public static function getOnlineFriends($userId, $limit = 20)
    {
        $followingIds = Follow::getFollowingIds($userId);
        if (empty($followingIds)) {
            return [];
        }

        return self::find()
            ->with('user')
            ->where(['in', 'user_id', $followingIds])
            ->andWhere(['>', 'last_activity', time() - self::ONLINE_TTL])
            ->orderBy(['last_activity' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    public static function cleanup()
    {
        return self::deleteAll(['<', 'last_activity', time() - self::ONLINE_TTL]);
    }
}