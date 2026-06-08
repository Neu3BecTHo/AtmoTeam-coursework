<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Block model for user blocks
 *
 * @property int $id
 * @property int $blocker_id
 * @property int $blocked_id
 * @property int $created_at
 */
class Block extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%block}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public function rules()
    {
        return [
            [['blocker_id', 'blocked_id'], 'required'],
            [['blocker_id', 'blocked_id', 'created_at'], 'integer'],
            [['blocker_id', 'blocked_id'], 'unique', 'targetAttribute' => ['blocker_id', 'blocked_id']],
            ['blocked_id', 'compare', 'compareAttribute' => 'blocker_id', 'operator' => '!=', 'message' => 'Нельзя заблокировать себя'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'blocker_id' => 'Кто заблокировал',
            'blocked_id' => 'Кто заблокирован',
            'created_at' => 'Дата блокировки',
        ];
    }

    public function getBlocker()
    {
        return $this->hasOne(User::class, ['id' => 'blocker_id']);
    }

    public function getBlockedUser()
    {
        return $this->hasOne(User::class, ['id' => 'blocked_id']);
    }

    public static function block($blockerId, $blockedUserId)
    {
        if ($blockerId == $blockedUserId) {
            return false;
        }

        if (self::isBlocked($blockerId, $blockedUserId)) {
            return false;
        }

        $block = new self([
            'blocker_id' => $blockerId,
            'blocked_id' => $blockedUserId,
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
            ->with('blockedUser')
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
            ->select('blocked_id')
            ->column();
    }

    public static function getBlockedCount($blockerId)
    {
        return self::find()
            ->where(['blocker_id' => $blockerId])
            ->count();
    }
}