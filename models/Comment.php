<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use Yii;

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
                'value' => time(),
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

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getPost()
    {
        return $this->hasOne(Post::class, ['id' => 'post_id']);
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $data = parent::toArray($fields, $expand, $recursive);
        $data['user_id'] = $this->user_id;
        $data['author'] = $this->user ? [
            'id' => $this->user->id,
            'username' => $this->user->username,
            'avatar' => $this->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $this->user->id,
        ] : null;
        $data['timeAgo'] = $this->getTimeAgo();
        return $data;
    }

    public function getTimeAgo()
    {
        $diff = time() - $this->created_at;
        
        if ($diff < 60) return 'только что';
        if ($diff < 3600) return floor($diff / 60) . ' мин. назад';
        if ($diff < 86400) return floor($diff / 3600) . ' ч. назад';
        
        return date('H:i', $this->created_at);
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

        $this->clearCommentCache();
    }

    
    public static function getLatestComments($limit = 10)
    {
        $cache = Yii::$app->cache;
        $cacheKey = 'latest_comments';
        
        return $cache->getOrSet($cacheKey, function () use ($limit) {
            return self::find()
                ->with(['user', 'post'])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit($limit)
                ->all();
        }, 300); // Кэшируем на 5 минут
    }

    
    public static function getPopularComments($limit = 10)
    {
        $cache = Yii::$app->cache;
        $cacheKey = 'popular_comments';
        
        return $cache->getOrSet($cacheKey, function () use ($limit) {
            return self::find()
                ->select(['comment.*', 'COUNT(DISTINCT like.id) as likes_count'])
                ->leftJoin('like', 'like.comment_id = comment.id')
                ->with(['user', 'post'])
                ->groupBy('comment.id')
                ->orderBy(['likes_count' => SORT_DESC, 'comment.created_at' => SORT_DESC])
                ->limit($limit)
                ->all();
        }, 600); // Кэшируем на 10 минут
    }

    
    public static function getPostCommentsCached($postId, $limit = 50)
    {
        $cache = Yii::$app->cache;
        $cacheKey = "post_comments_{$postId}";
        
        return $cache->getOrSet($cacheKey, function () use ($postId, $limit) {
            return self::find()
                ->where(['post_id' => $postId])
                ->with(['user'])
                ->orderBy(['created_at' => SORT_ASC])
                ->limit($limit)
                ->all();
        }, 180); // Кэшируем на 3 минуты
    }

    
    public static function getCommentsStats()
    {
        $cache = Yii::$app->cache;
        $cacheKey = 'comments_stats';
        
        return $cache->getOrSet($cacheKey, function () {
            return [
                'total_count' => self::find()->count(),
                'today_count' => self::find()
                    ->where(['>=', 'created_at', strtotime('today')])
                    ->count(),
                'week_count' => self::find()
                    ->where(['>=', 'created_at', time() - 86400 * 7])
                    ->count(),
                'most_active_users' => self::find()
                    ->select(['user_id', 'COUNT(*) as comments_count'])
                    ->groupBy('user_id')
                    ->orderBy(['comments_count' => SORT_DESC])
                    ->limit(5)
                    ->with(['user'])
                    ->all(),
            ];
        }, 900); // Кэшируем на 15 минут
    }

    public function afterDelete()
    {
        parent::afterDelete();

        $this->clearCommentCache();
    }

    
    private function clearCommentCache()
    {
        $cache = Yii::$app->cache;

        $cache->delete('latest_comments');
        $cache->delete('popular_comments');
        $cache->delete('comments_stats');

        $cache->delete("post_comments_{$this->post_id}");

        $cache->delete('popular_posts');
    }
}
