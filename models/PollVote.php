<?php

namespace app\models;

use yii\db\ActiveRecord;

class PollVote extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%poll_vote}}';
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
            [['poll_id', 'poll_option_id', 'user_id'], 'required'],
            [['poll_id', 'poll_option_id', 'user_id'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'poll_id' => 'Опрос',
            'poll_option_id' => 'Вариант',
            'user_id' => 'Пользователь',
            'created_at' => 'Дата',
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

        if (!empty($existingVotes)) {
            foreach ($existingVotes as $vote) {

                $option = PollOption::findOne($vote->poll_option_id);
                if ($option && $option->votes_count > 0) {
                    $option->votes_count--;
                    $option->save(false);
                }
                $vote->delete();
            }
        }

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            foreach ($optionIds as $optionId) {
                $option = PollOption::findOne($optionId);
                if (!$option || $option->poll_id != $pollId) {
                    continue;
                }

                $vote = new self([
                    'poll_id' => $pollId,
                    'poll_option_id' => $optionId,
                    'user_id' => $userId,
                    'created_at' => time(),
                ]);

                if ($vote->save()) {

                    $option->votes_count++;
                    $option->save(false);
                }
            }

            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            return false;
        }
    }
}
