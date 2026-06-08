<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Comment model
 *
 * @property int $id
 * @property int $post_id
 * @property int $user_id
 * @property string $content
 * @property int $created_at
 * @property int $updated_at
 */
class Comment extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%comment}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
            ],
        ];
    }

    public function rules()
    {
        return [
            [['content', 'post_id'], 'required'],
            [['content'], 'string', 'max' => 1000],
            [['post_id', 'user_id'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'post_id' => 'Пост',
            'user_id' => 'Автор',
            'content' => 'Комментарий',
            'created_at' => 'Дата создания',
            'updated_at' => 'Обновлено',
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

    public function getTimeAgo()
    {
        $diff = time() - $this->created_at;
        
        if ($diff < 60) return 'только что';
        if ($diff < 3600) return floor($diff / 60) . ' мин. назад';
        if ($diff < 86400) return floor($diff / 3600) . ' ч. назад';
        
        return date('H:i', $this->created_at);
    }

    public function fields()
    {
        return [
            'id',
            'post_id',
            'user_id',
            'content',
            'created_at',
            'updated_at',
            'timeAgo' => 'timeAgo',
            'author' => function () {
                return $this->user ? [
                    'id' => $this->user->id,
                    'username' => $this->user->username,
                    'avatar' => $this->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $this->user->id,
                ] : null;
            },
        ];
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert && $this->post && $this->post->user_id != $this->user_id) {
            Notification::create(
                $this->post->user_id,
                Notification::TYPE_COMMENT,
                $this->user_id,
                $this->post_id
            );
        }

        $this->invalidateCache();
    }

    public function afterDelete()
    {
        parent::afterDelete();
        $this->invalidateCache();
    }

    private function invalidateCache()
    {
        $cache = Yii::$app->cache;
        $cache->delete('latest_comments');
        $cache->delete('popular_comments');
        $cache->delete('comments_stats');
        $cache->delete("post_comments_{$this->post_id}");
    }

    public static function getLatestComments($limit = 10)
    {
        return Yii::$app->cache->getOrSet('latest_comments', function () use ($limit) {
            return self::find()
                ->with(['user', 'post'])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit($limit)
                ->all();
        }, 300);
    }

    public static function getPostComments($postId, $limit = 50)
    {
        return Yii::$app->cache->getOrSet("post_comments_{$postId}", function () use ($postId, $limit) {
            return self::find()
                ->where(['post_id' => $postId])
                ->with('user')
                ->orderBy(['created_at' => SORT_ASC])
                ->limit($limit)
                ->all();
        }, 180);
    }
}