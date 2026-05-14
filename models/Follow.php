<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;


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
                'updatedAtAttribute' => false, // Только created_at
            ],
        ];
    }

    public function rules()
    {
        return [
            [['follower_id', 'following_id'], 'required'],
            [['follower_id', 'following_id'], 'integer'],

            ['following_id', 'compare', 'compareAttribute' => 'follower_id', 'operator' => '!=', 
             'message' => 'Нельзя подписаться на самого себя'],

            [['follower_id', 'following_id'], 'unique', 
             'targetAttribute' => ['follower_id', 'following_id'],
             'message' => 'Вы уже подписаны на этого пользователя'],
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
        if ($followerId == $followingId) {
            return false;
        }

        $follow = new self();
        $follow->follower_id = $followerId;
        $follow->following_id = $followingId;

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
        $follow = self::findOne([
            'follower_id' => $followerId,
            'following_id' => $followingId,
        ]);

        if ($follow) {
            return $follow->delete() !== false;
        }

        return false;
    }

    
    public static function isFollowing($followerId, $followingId)
    {
        return self::find()->where([
            'follower_id' => $followerId,
            'following_id' => $followingId,
        ])->exists();
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
