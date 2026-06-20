<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;

/**
 * Post model
 *
 * @property int $id
 * @property int $user_id
 * @property string $content
 * @property string|null $image
 * @property int $likes_count
 * @property int $comments_count
 * @property int $created_at
 * @property int $updated_at
 */
class Post extends ActiveRecord
{
    public $imageFiles = [];
    private $_pendingImages = [];

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
            [['content'], 'required', 'when' => function ($model) {
                return empty($model->imageFiles) && !Yii::$app->request->post('poll_question');
            }, 'message' => Yii::t('app', 'Введите текст поста или добавьте изображение/опрос')],
            [['content'], 'string', 'max' => 5000],
            [['user_id', 'likes_count', 'comments_count'], 'integer'],
            [['imageFiles'], 'file',
                'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                'maxSize' => 5 * 1024 * 1024,
                'skipOnEmpty' => true,
                'maxFiles' => 10,
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => Yii::t('app', 'Автор'),
            'content' => Yii::t('app', 'Содержание'),
            'image' => Yii::t('app', 'Изображение'),
            'likes_count' => Yii::t('app', 'Лайки'),
            'comments_count' => Yii::t('app', 'Комментарии'),
            'created_at' => Yii::t('app', 'Создан'),
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
        return $this->hasMany(Comment::class, ['post_id' => 'id'])->orderBy(['created_at' => SORT_DESC]);
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

    public function getImages()
    {
        return $this->hasMany(PostImage::class, ['post_id' => 'id'])->orderBy(['sort_order' => SORT_ASC]);
    }

    public function isLikedBy($userId): bool
    {
        return Like::find()->where(['post_id' => $this->id, 'user_id' => $userId])->exists();
    }

    public function isSavedBy($userId): bool
    {
        return SavedPost::find()->where(['post_id' => $this->id, 'user_id' => $userId])->exists();
    }

    public function isRepostedBy($userId): bool
    {
        return Repost::find()->where(['post_id' => $this->id, 'user_id' => $userId])->exists();
    }

    public function getRepostsCount(): int
    {
        return (int) Repost::find()->where(['post_id' => $this->id])->count();
    }

    public function getImageUrl(): ?string
    {
        $images = $this->getImages()->all();
        if (!empty($images)) {
            return $images[0]->getImageUrl();
        }
        return null;
    }

    public function getImageUrls(): array
    {
        $urls = [];
        foreach ($this->getImages()->all() as $image) {
            $urls[] = $image->getImageUrl();
        }
        return $urls;
    }

    public function getTimeAgo(): string
    {
        return \app\components\TimeAgoHelper::format((int) $this->created_at);
    }

    public function updateStats()
    {
        $this->likes_count = Like::find()->where(['post_id' => $this->id])->count();
        $this->comments_count = Comment::find()->where(['post_id' => $this->id])->count();
        $this->save(false, ['likes_count', 'comments_count']);
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if (!empty($this->imageFiles)) {
            $this->_pendingImages = $this->processImages($this->imageFiles);
        }

        return true;
    }

    private function processImages($files): array
    {
        $pending = [];
        $uploadPath = 'uploads/posts/' . date('Y') . '/' . date('m');
        $fullPath = Yii::getAlias('@webroot/' . $uploadPath);
        FileHelper::createDirectory($fullPath, 0755);

        $sortOrder = 0;
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $filename = uniqid() . '.' . $file->extension;
            if ($file->saveAs($fullPath . '/' . $filename)) {
                $postImage = new PostImage([
                    'post_id' => null,
                    'filename' => $filename,
                    'sort_order' => $sortOrder++,
                    'created_at' => time(),
                ]);
                $pending[] = $postImage;
            }
        }

        return $pending;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if (!empty($this->_pendingImages)) {
            foreach ($this->_pendingImages as $image) {
                $image->post_id = $this->id;
                $image->save(false);
            }
            $this->_pendingImages = [];
        }

        $this->imageFiles = [];
    }

    public function afterDelete()
    {
        parent::afterDelete();
        
        // Удаление одиночного изображения (старый формат)
        if ($this->image) {
            $path = Yii::getAlias('@webroot/' . $this->image);
            if (file_exists($path)) {
                unlink($path);
            }
        }
        
        // Удаление множественных изображений
        $images = PostImage::find()->where(['post_id' => $this->id])->all();
        foreach ($images as $image) {
            $image->delete(); // PostImage::beforeDelete() удалит файл
        }
        
        // Очистка кеша
        Yii::$app->cache->delete('popular_posts');
    }

    public function fields()
    {
        $fields = parent::fields();
        
        $fields['author'] = function() {
            return [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'avatar' => $this->user->getAvatarUrl(),
            ];
        };
        
        $fields['timeAgo'] = 'timeAgo';
        $fields['image_urls'] = 'imageUrls';
        $fields['image_url'] = 'imageUrl';
        $fields['reposts_count'] = 'repostsCount';
        $fields['is_liked'] = function() {
            return !Yii::$app->user->isGuest && $this->isLikedBy(Yii::$app->user->id);
        };
        $fields['is_saved'] = function() {
            return !Yii::$app->user->isGuest && $this->isSavedBy(Yii::$app->user->id);
        };
        $fields['is_reposted'] = function() {
            return !Yii::$app->user->isGuest && $this->isRepostedBy(Yii::$app->user->id);
        };
        
        // Исправляем сериализацию poll
        $fields['poll'] = function() {
            if (!$this->poll) return null;
            
            $poll = $this->poll;
            $userId = Yii::$app->user->id;
            $totalVotes = $poll->getTotalVotes();
            
            return [
                'id' => $poll->id,
                'question' => $poll->question,
                'multiple_votes' => (bool)$poll->multiple_votes,
                'has_user_voted' => !Yii::$app->user->isGuest && $poll->hasUserVoted($userId),
                'user_votes' => !Yii::$app->user->isGuest ? $poll->getUserVotes($userId) : [],
                'total_votes' => $totalVotes,
                'options' => array_map(function($option) use ($totalVotes) {
                    return [
                        'id' => $option->id,
                        'text' => $option->text,
                        'votes_count' => $option->votes_count,
                        'percentage' => $totalVotes > 0 ? round(($option->votes_count / $totalVotes) * 100, 1) : 0,
                    ];
                }, $poll->options),
            ];
        };
        
        return $fields;
    }

    public static function getPopularPosts($limit = 10)
    {
        return Yii::$app->cache->getOrSet('popular_posts', function () use ($limit) {
            return self::find()
                ->with('user')
                ->orderBy(['likes_count' => SORT_DESC, 'comments_count' => SORT_DESC])
                ->limit($limit)
                ->all();
        }, 3600);
    }
}