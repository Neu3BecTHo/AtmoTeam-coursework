<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Repost model
 *
 * @property int $id
 * @property int $user_id
 * @property int $post_id
 * @property int $created_at
 */
class Repost extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%repost}}';
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
            [['user_id', 'post_id'], 'required'],
            [['user_id', 'post_id', 'created_at'], 'integer'],
            [['user_id', 'post_id'], 'unique', 'targetAttribute' => ['user_id', 'post_id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Пользователь',
            'post_id' => 'Пост',
            'created_at' => 'Дата репоста',
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
        } else {
            $repost = new self([
                'user_id' => $userId,
                'post_id' => $postId,
            ]);
            $repost->save();
        }

        return [
            'reposted' => !$existing,
            'reposts_count' => self::getPostRepostsCount($postId),
        ];
    }

    public static function isRepostedBy($userId, $postId): bool
    {
        return self::find()
            ->where(['user_id' => $userId, 'post_id' => $postId])
            ->exists();
    }

    public static function getPostRepostsCount($postId): int
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

    public function fields()
    {
        return [
            'id',
            'user_id',
            'post_id',
            'created_at',
        ];
    }
}