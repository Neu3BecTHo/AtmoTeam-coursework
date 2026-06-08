<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * PollOption model
 *
 * @property int $id
 * @property int $poll_id
 * @property string $text
 * @property int $votes_count
 */
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
            'text' => 'Вариант ответа',
            'votes_count' => 'Голосов',
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

    public function incrementVotes()
    {
        return $this->updateCounters(['votes_count' => 1]);
    }

    public function decrementVotes()
    {
        return $this->updateCounters(['votes_count' => -1]);
    }

    public function fields()
    {
        return [
            'id',
            'poll_id',
            'text',
            'votes_count',
        ];
    }
}