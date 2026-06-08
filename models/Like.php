<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Like model
 *
 * @property int $id
 * @property int $post_id
 * @property int $user_id
 * @property int $created_at
 */
class Like extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%like}}';
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
            [['post_id', 'user_id'], 'required'],
            [['post_id', 'user_id'], 'integer'],
            [['post_id', 'user_id'], 'unique', 'targetAttribute' => ['post_id', 'user_id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'post_id' => 'Пост',
            'user_id' => 'Пользователь',
            'created_at' => 'Дата лайка',
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

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        
        $this->updatePostLikesCount();

        if ($insert && $this->post && $this->post->user_id != $this->user_id) {
            Notification::create(
                $this->post->user_id,
                Notification::TYPE_LIKE,
                $this->user_id,
                $this->post_id
            );
        }
    }

    public function afterDelete()
    {
        parent::afterDelete();
        $this->updatePostLikesCount();
    }

    private function updatePostLikesCount()
    {
        $count = static::find()->where(['post_id' => $this->post_id])->count();
        
        Post::updateAllCounters(
            ['likes_count' => $count - ($this->post->likes_count ?? 0)],
            ['id' => $this->post_id]
        );
    }
}