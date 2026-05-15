<?php

namespace app\models;

use yii\db\ActiveRecord;

class Repost extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%repost}}';
    }

    public function behaviors()
    {
        return [];
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

    public function rules()
    {
        return [
            [['user_id', 'post_id'], 'required'],
            [['user_id', 'post_id'], 'integer'],
            [['user_id', 'post_id'], 'unique', 'targetAttribute' => ['user_id', 'post_id']],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getPost()
    {
        return $this->hasOne(Post::class, ['id' => 'post_id']);
    }

public static function toggle($userId, $postId)
    {
        $existing = self::find()
            ->where(['user_id' => $userId, 'post_id' => $postId])
            ->one();

        if ($existing) {
            $existing->delete();
            $newCount = self::getPostRepostsCount($postId);
            return ['reposted' => false, 'reposts_count' => $newCount];
        } else {
            $repost = new self([
                'user_id' => $userId,
                'post_id' => $postId,
                'created_at' => time(),
            ]);
            $repost->save();
            $newCount = self::getPostRepostsCount($postId);
            return ['reposted' => true, 'reposts_count' => $newCount];
        }
    }

    public static function isRepostedBy($userId, $postId)
    {
        return self::find()
            ->where(['user_id' => $userId, 'post_id' => $postId])
            ->exists();
    }

    public static function getPostRepostsCount($postId)
    {
        return self::find()
            ->where(['post_id' => $postId])
            ->count();
    }

    public static function getUserReposts($userId, $limit = 20, $offset = 0)
    {
        return self::find()
            ->with(['post', 'post.user'])
            ->where(['user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->offset($offset)
            ->all();
    }
}
