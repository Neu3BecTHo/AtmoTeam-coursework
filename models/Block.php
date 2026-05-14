<?php

namespace app\models;

use yii\db\ActiveRecord;

class Block extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%block}}';
    }

    public function rules()
    {
        return [
            [['blocker_id', 'blocked_id'], 'required'],
            [['blocker_id', 'blocked_id'], 'integer'],
            [['blocker_id', 'blocked_id'], 'unique', 'targetAttribute' => ['blocker_id', 'blocked_id']],
        ];
    }

    public function getBlocker()
    {
        return $this->hasOne(User::class, ['id' => 'blocker_id']);
    }

    public function getBlockedUser()
    {
        return $this->hasOne(User::class, ['id' => 'blocked_user_id']);
    }

    
    public static function block($blockerId, $blockedUserId)
    {
        if ($blockerId === $blockedUserId) {
            return false;
        }

        $existing = self::find()
            ->where(['blocker_id' => $blockerId, 'blocked_id' => $blockedUserId])
            ->one();

        if ($existing) {
            return false; // Уже заблокирован
        }

        $block = new self([
            'blocker_id' => $blockerId,
            'blocked_id' => $blockedUserId,
            'created_at' => time(),
        ]);

        return $block->save();
    }

    
    public static function unblock($blockerId, $blockedUserId)
    {
        return self::deleteAll([
            'blocker_id' => $blockerId,
            'blocked_id' => $blockedUserId,
        ]);
    }

    
    public static function isBlocked($blockerId, $blockedUserId)
    {
        return self::find()
            ->where(['blocker_id' => $blockerId, 'blocked_id' => $blockedUserId])
            ->exists();
    }

    
    public static function getBlockedUsers($blockerId, $limit = 20, $offset = 0)
    {
        return self::find()
            ->with(['blockedUser'])
            ->where(['blocker_id' => $blockerId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->offset($offset)
            ->all();
    }

    
    public static function getBlockedIds($blockerId)
    {
        return self::find()
            ->where(['blocker_id' => $blockerId])
            ->select(['blocked_id'])
            ->column();
    }

    
    public static function getBlockedCount($blockerId)
    {
        return self::find()
            ->where(['blocker_id' => $blockerId])
            ->count();
    }
}
