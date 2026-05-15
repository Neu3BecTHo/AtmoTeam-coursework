<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\web\UploadedFile;
use Yii;

class Post extends ActiveRecord
{
    
    public $imageFile;
    
    
    public $reposts_count;

    public static function tableName()
    {
        return '{{%post}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['content'], 'required'],
            [['content'], 'string', 'max' => 2000],
            [['user_id', 'likes_count', 'comments_count'], 'integer'],
            [['image'], 'string', 'max' => 255],
            [['imageFile'], 'file', 
                'extensions' => ['jpg', 'jpeg', 'png', 'gif'], 
                'maxSize' => 5 * 1024 * 1024, // 5MB
                'skipOnEmpty' => true,
            ],
        ];
    }

    public function updateStats()
    {

        $this->likes_count = (int)\app\models\Like::find()
            ->where(['post_id' => $this->id])
            ->count();

        $this->comments_count = (int)\app\models\Comment::find()
            ->where(['post_id' => $this->id])
            ->count();

        $this->reposts_count = (int)\app\models\Repost::find()
            ->where(['post_id' => $this->id])
            ->count();
        
        $this->save(false);
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Автор',
            'content' => 'Содержание',
            'image' => 'Изображение',
            'likes_count' => 'Лайки',
            'comments_count' => 'Комментарии',
            'created_at' => 'Создан',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getLikes()
    {
        return $this->hasMany(Like::class, ['post_id' => 'id']);
    }

    public function getComments()
    {
        return $this->hasMany(Comment::class, ['post_id' => 'id'])
            ->orderBy(['created_at' => SORT_DESC]);
    }

    public function getSavedPosts()
    {
        return $this->hasMany(SavedPost::class, ['post_id' => 'id']);
    }

    public function getReposts()
    {
        return $this->hasMany(Repost::class, ['post_id' => 'id']);
    }

    public function getPoll()
    {
        return $this->hasOne(Poll::class, ['post_id' => 'id']);
    }

    public function isLikedBy($userId)
    {
        return Like::find()->where(['post_id' => $this->id, 'user_id' => $userId])->exists();
    }

    public function isSavedBy($userId)
    {
        return SavedPost::find()->where(['post_id' => $this->id, 'user_id' => $userId])->exists();
    }

    public function isRepostedBy($userId)
    {
        return Repost::find()->where(['post_id' => $this->id, 'user_id' => $userId])->exists();
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $data = parent::toArray($fields, $expand, $recursive);
        $data['author'] = $this->user ? [
            'id' => $this->user->id,
            'username' => $this->user->username,
            'avatar' => $this->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $this->user->id,
        ] : null;
        $data['timeAgo'] = $this->getTimeAgo();
        $data['image_url'] = $this->getImageUrl();
        $repostsCount = $this->getRepostsCount();
        $data['is_reposted'] = $repostsCount > 0;
        $data['reposts_count'] = $repostsCount;

        if ($this->poll) {
            $data['poll'] = $this->poll->toArray();
        }
        
        return $data;
    }

    public function getTimeAgo()
    {
        $diff = time() - $this->created_at;
        
        if ($diff < 60) return 'только что';
        if ($diff < 3600) return floor($diff / 60) . ' мин. назад';
        if ($diff < 86400) return floor($diff / 3600) . ' ч. назад';
        if ($diff < 2592000) return floor($diff / 86400) . ' дн. назад';
        
        return date('d.m.Y', $this->created_at);
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($this->imageFile) {
            $uploadPath = 'uploads/posts/' . date('Y/m');
            $fullPath = \Yii::getAlias('@webroot/' . $uploadPath);
            
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
            
            $filename = uniqid() . '.' . $this->imageFile->extension;
            $filepath = $fullPath . '/' . $filename;
            
            if ($this->imageFile->saveAs($filepath)) {

                if (!$insert && $this->image) {
                    $oldPath = \Yii::getAlias('@webroot/' . $this->image);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                
                $this->image = $uploadPath . '/' . $filename;
            }
            
            $this->imageFile = null;
        }

        return true;
    }

    
    public function getRepostsCount()
    {
        return (int) Repost::find()->where(['post_id' => $this->id])->count();
    }

    
    public function getImageUrl()
    {
        if ($this->image) {
            return '/' . $this->image;
        }
        return null;
    }

    public function afterDelete()
    {
        parent::afterDelete();

        if ($this->image) {
            $path = \Yii::getAlias('@webroot/' . $this->image);
            if (file_exists($path)) {
                unlink($path);
            }
        }

        // Удаляем связанные репосты и сохранения
        \app\models\Repost::deleteAll(['post_id' => $this->id]);
        \app\models\SavedPost::deleteAll(['post_id' => $this->id]);

        Yii::$app->cache->delete('popular_posts');
    }

    
    public static function getPopularPosts($limit = 10)
    {
        $cache = Yii::$app->cache;
        $cacheKey = 'popular_posts';
        
        return $cache->getOrSet($cacheKey, function () use ($limit) {
            return self::find()
                ->with('user')
                ->orderBy(['likes_count' => SORT_DESC, 'comments_count' => SORT_DESC])
                ->limit($limit)
                ->all();
        }, 3600); // Кэшируем на 1 час
    }
}
