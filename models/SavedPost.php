<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * SavedPost model
 *
 * @property int $id
 * @property int $user_id
 * @property int $post_id
 * @property int $created_at
 */
class SavedPost extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%saved_post}}';
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
            'created_at' => 'Дата сохранения',
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
        $existing = self::findOne(['user_id' => $userId, 'post_id' => $postId]);

        if ($existing) {
            $existing->delete();
        } else {
            $model = new self(['user_id' => $userId, 'post_id' => $postId]);
            $model->save();
        }

        return [
            'saved' => !$existing,
            'count' => self::getSavedCount($postId),
        ];
    }

    public static function isSaved($userId, $postId): bool
    {
        return self::find()
            ->where(['user_id' => $userId, 'post_id' => $postId])
            ->exists();
    }

    public static function getSavedCount($postId): int
    {
        return self::find()
            ->where(['post_id' => $postId])
            ->count();
    }

    public static function getUserSavedPosts($userId, $limit = 20, $offset = 0)
    {
        return Post::find()
            ->joinWith('savedPosts')
            ->where(['saved_post.user_id' => $userId])
            ->orderBy(['saved_post.created_at' => SORT_DESC])
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