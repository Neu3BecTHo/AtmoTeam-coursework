<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * PollVote model
 *
 * @property int $id
 * @property int $poll_id
 * @property int $poll_option_id
 * @property int $user_id
 * @property int $created_at
 */
class PollVote extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%poll_vote}}';
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
            [['poll_id', 'poll_option_id', 'user_id'], 'required'],
            [['poll_id', 'poll_option_id', 'user_id', 'created_at'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'poll_id' => 'Опрос',
            'poll_option_id' => 'Вариант ответа',
            'user_id' => 'Пользователь',
            'created_at' => 'Дата голосования',
        ];
    }

    public function getPoll()
    {
        return $this->hasOne(Poll::class, ['id' => 'poll_id']);
    }

    public function getPollOption()
    {
        return $this->hasOne(PollOption::class, ['id' => 'poll_option_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public static function vote($pollId, $optionIds, $userId)
    {
        $poll = Poll::findOne($pollId);
        if (!$poll) {
            return false;
        }

        $existingVotes = self::find()->where(['poll_id' => $pollId, 'user_id' => $userId])->all();

        if (!$poll->multiple_votes && !empty($existingVotes)) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($existingVotes as $vote) {
                $vote->pollOption->decrementVotes();
                $vote->delete();
            }

            foreach ($optionIds as $optionId) {
                $option = PollOption::findOne($optionId);
                if (!$option || $option->poll_id != $pollId) {
                    continue;
                }

                $vote = new self([
                    'poll_id' => $pollId,
                    'poll_option_id' => $optionId,
                    'user_id' => $userId,
                ]);

                if ($vote->save()) {
                    $option->incrementVotes();
                }
            }

            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            return false;
        }
    }

    public function fields()
    {
        return [
            'id',
            'poll_id',
            'poll_option_id',
            'user_id',
            'created_at',
        ];
    }
}