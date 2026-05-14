<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class Poll extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%poll}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::class,
                'updatedAtAttribute' => false, // Только created_at
            ],
        ];
    }

    
    public function rules()
    {
        return [
            [['question'], 'required'],
            [['post_id'], 'integer'],
            [['question'], 'string'],
            [['multiple_votes'], 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'post_id' => 'Пост',
            'question' => 'Вопрос',
            'created_at' => 'Создано',
        ];
    }

    public function getPost()
    {
        return $this->hasOne(Post::class, ['id' => 'post_id']);
    }

    public function getOptions()
    {
        return $this->hasMany(PollOption::class, ['poll_id' => 'id'])
            ->orderBy(['id' => SORT_ASC]);
    }

    public function getVotes()
    {
        return $this->hasMany(PollVote::class, ['poll_id' => 'id']);
    }

    public function getTotalVotes()
    {
        return PollVote::find()->where(['poll_id' => $this->id])->count();
    }

    public function hasUserVoted($userId)
    {
        return PollVote::find()->where(['poll_id' => $this->id, 'user_id' => $userId])->exists();
    }

    public function getUserVotes($userId)
    {
        return PollVote::find()
            ->where(['poll_id' => $this->id, 'user_id' => $userId])
            ->select(['poll_option_id'])
            ->column();
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $data = parent::toArray($fields, $expand, $recursive);
        $data['multiple_votes'] = $this->multiple_votes;
        $data['options'] = array_map(function($option) {
            return [
                'id' => $option->id,
                'text' => $option->text,
                'votes_count' => $option->votes_count,
                'percentage' => $this->getTotalVotes() > 0 ? round(($option->votes_count / $this->getTotalVotes()) * 100, 1) : 0,
            ];
        }, $this->options);
        $data['total_votes'] = $this->getTotalVotes();
        $data['has_user_voted'] = \Yii::$app->user->id ? $this->hasUserVoted(\Yii::$app->user->id) : false;
        $data['user_votes'] = \Yii::$app->user->id ? $this->getUserVotes(\Yii::$app->user->id) : [];
        return $data;
    }
}
