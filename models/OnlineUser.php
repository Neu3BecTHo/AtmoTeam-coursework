<?php

namespace app\models;

use yii\db\ActiveRecord;
use Yii;


class OnlineUser extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%online_user}}';
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

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    
    public static function updateActivity($userId)
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        $sessionId = session_id();
        $ipAddress = Yii::$app->request->userIP;
        $userAgent = Yii::$app->request->userAgent;
        $now = time();

        $onlineUser = self::find()
            ->where(['user_id' => $userId, 'session_id' => $sessionId])
            ->one();

        if ($onlineUser) {

            $onlineUser->last_activity = $now;
            $onlineUser->save(false);
        } else {

            $onlineUser = new self([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'last_activity' => $now,
                'created_at' => $now,
            ]);
            $onlineUser->save();
        }

        self::cleanup();

        return true;
    }

    
    public static function removeUser($userId)
    {
        return self::deleteAll(['user_id' => $userId]);
    }

    
    public static function getOnlineCount()
    {
        return self::find()
            ->where(['>', 'last_activity', time() - 30 * 60]) // Активные последние 30 минут
            ->count();
    }

    
    public static function getOnlineUsers($limit = 50)
    {
        return self::find()
            ->with(['user'])
            ->where(['>', 'last_activity', time() - 30 * 60])
            ->orderBy(['last_activity' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    
    public static function isUserOnline($userId)
    {
        return self::find()
            ->where(['user_id' => $userId])
            ->andWhere(['>', 'last_activity', time() - 30 * 60])
            ->exists();
    }

    
    public static function cleanup()
    {
        return self::deleteAll(['<', 'last_activity', time() - 30 * 60]);
    }

    
    public static function getOnlineFriends($userId, $limit = 20)
    {

        $followingIds = Follow::getFollowingIds($userId);
        if (empty($followingIds)) {
            return [];
        }

        return self::find()
            ->with(['user'])
            ->where(['in', 'user_id', $followingIds])
            ->andWhere(['>', 'last_activity', time() - 30 * 60])
            ->orderBy(['last_activity' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert) {
            $this->created_at = time();
        }

        return true;
    }
}
