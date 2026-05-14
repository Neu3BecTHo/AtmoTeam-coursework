<?php

namespace app\models;

use yii\db\ActiveRecord;

class PollOption extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%poll_option}}';
    }

    public function rules()
    {
        return [
            [['poll_id', 'text'], 'required'],
            [['poll_id', 'votes_count'], 'integer'],
            [['text'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'poll_id' => 'Опрос',
            'text' => 'Вариант',
            'votes_count' => 'Голоса',
        ];
    }

    public function getPoll()
    {
        return $this->hasOne(Poll::class, ['id' => 'poll_id']);
    }

    public function getVotes()
    {
        return $this->hasMany(PollVote::class, ['poll_option_id' => 'id']);
    }

    public function getVotesCount()
    {
        return $this->getVotes()->count();
    }
}
