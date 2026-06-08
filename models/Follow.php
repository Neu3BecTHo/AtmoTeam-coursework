<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Follow model
 *
 * @property int $id
 * @property int $follower_id
 * @property int $following_id
 * @property int $created_at
 */
class Follow extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%follow}}';
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
            [['follower_id', 'following_id'], 'required'],
            [['follower_id', 'following_id'], 'integer'],
            [
                'following_id',
                'compare',
                'compareAttribute' => 'follower_id',
                'operator' => '!=',
                'message' => 'Нельзя подписаться на самого себя',
            ],
            [
                ['follower_id', 'following_id'],
                'unique',
                'targetAttribute' => ['follower_id', 'following_id'],
                'message' => 'Вы уже подписаны на этого пользователя',
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'follower_id' => 'Подписчик',
            'following_id' => 'Подписка',
            'created_at' => 'Дата подписки',
        ];
    }

    public function getFollower()
    {
        return $this->hasOne(User::class, ['id' => 'follower_id']);
    }

    public function getFollowing()
    {
        return $this->hasOne(User::class, ['id' => 'following_id']);
    }

    public static function follow($followerId, $followingId)
    {
        if ($followerId == $followingId || self::isFollowing($followerId, $followingId)) {
            return false;
        }

        $follow = new self([
            'follower_id' => $followerId,
            'following_id' => $followingId,
        ]);

        if ($follow->save()) {
            Notification::create(
                $followingId,
                Notification::TYPE_FOLLOW,
                $followerId,
                null,
                "Пользователь подписался на вас"
            );
            return $follow;
        }

        return false;
    }

    public static function unfollow($followerId, $followingId)
    {
        return self::deleteAll([
            'follower_id' => $followerId,
            'following_id' => $followingId,
        ]) > 0;
    }

    public static function isFollowing($followerId, $followingId)
    {
        return self::find()
            ->where(['follower_id' => $followerId, 'following_id' => $followingId])
            ->exists();
    }

    public static function getFollowingIds($userId)
    {
        return self::find()
            ->select('following_id')
            ->where(['follower_id' => $userId])
            ->column();
    }

    public static function getFollowersCount($userId)
    {
        return self::find()->where(['following_id' => $userId])->count();
    }

    public static function getFollowingCount($userId)
    {
        return self::find()->where(['follower_id' => $userId])->count();
    }
}