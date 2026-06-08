<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Poll model
 *
 * @property int $id
 * @property int|null $post_id
 * @property int $user_id
 * @property string $question
 * @property int $multiple_votes
 * @property int|null $expires_at
 * @property int $created_at
 */
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
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public function rules()
    {
        return [
            [['question', 'user_id'], 'required'],
            [['post_id', 'user_id', 'expires_at'], 'integer'],
            [['question'], 'string'],
            [['multiple_votes'], 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'post_id' => 'Пост',
            'user_id' => 'Автор',
            'question' => 'Вопрос',
            'multiple_votes' => 'Можно выбрать несколько вариантов',
            'expires_at' => 'Окончание опроса',
            'created_at' => 'Дата создания',
        ];
    }

    public function getPost()
    {
        return $this->hasOne(Post::class, ['id' => 'post_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getOptions()
    {
        return $this->hasMany(PollOption::class, ['poll_id' => 'id'])->orderBy(['id' => SORT_ASC]);
    }

    public function getVotes()
    {
        return $this->hasMany(PollVote::class, ['poll_id' => 'id']);
    }

    public function getTotalVotes(): int
    {
        return PollVote::find()->where(['poll_id' => $this->id])->count();
    }
    public function hasUserVoted($userId): bool
    {
        return PollVote::find()
            ->where(['poll_id' => $this->id, 'user_id' => $userId])
            ->exists();
    }

    public function getUserVotes($userId): array
    {
        return PollVote::find()
            ->where(['poll_id' => $this->id, 'user_id' => $userId])
            ->select('poll_option_id')
            ->column();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at < time();
    }

    public function fields()
    {
        return [
            'id',
            'post_id',
            'user_id',
            'question',
            'multiple_votes',
            'expires_at',
            'created_at',
            'total_votes' => 'totalVotes',
            'has_user_voted' => function () {
                return Yii::$app->user->id ? $this->hasUserVoted(Yii::$app->user->id) : false;
            },
            'user_votes' => function () {
                return Yii::$app->user->id ? $this->getUserVotes(Yii::$app->user->id) : [];
            },
            'is_expired' => 'isExpired',
            'options' => function () {
                $total = $this->getTotalVotes();
                return array_map(function ($option) use ($total) {
                    return [
                        'id' => $option->id,
                        'text' => $option->text,
                        'votes_count' => $option->votes_count,
                        'percentage' => $total > 0 ? round(($option->votes_count / $total) * 100, 1) : 0,
                    ];
                }, $this->options);
            },
        ];
    }
}