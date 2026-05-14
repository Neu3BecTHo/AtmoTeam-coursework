<?php

namespace app\models;

use yii\db\ActiveRecord;

class Story extends ActiveRecord
{
    public $imageFile;

    public static function tableName()
    {
        return '{{%story}}';
    }

    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id'], 'integer'],
            [['caption'], 'string'],
            [['image'], 'string', 'max' => 255],
            [['imageFile'], 'file', 
                'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'], 
                'maxSize' => 10 * 1024 * 1024, // 10MB
                'skipOnEmpty' => true,
                'wrongExtension' => 'Неподдерживаемый формат. Допустимы: JPG, PNG, GIF, WebP',
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Автор',
            'image' => 'Изображение',
            'caption' => 'Подпись',
            'created_at' => 'Создано',
            'expires_at' => 'Истекает',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert) {

            $this->expires_at = time() + 24 * 60 * 60;
            $this->created_at = time();
        }

        if ($this->imageFile) {
            $filename = 'story_' . $this->user_id . '_' . time() . '.' . $this->imageFile->extension;
            $path = \Yii::getAlias('@webroot/uploads/stories/');
            
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
            
            if ($this->imageFile->saveAs($path . $filename)) {
                $this->image = $filename;
            }
            
            $this->imageFile = null;
        }

        return true;
    }

    
    public static function createStory($userId, $image, $caption = null)
    {
        $story = new self([
            'user_id' => $userId,
            'image' => $image,
            'caption' => $caption,
            'created_at' => time(),
        ]);

        return $story->save() ? $story : null;
    }

    
    public static function getFollowingStories($userId, $limit = 20)
    {
        $followingIds = Follow::getFollowingIds($userId);
        $followingIds[] = $userId; // Include own stories

        return self::find()
            ->with('user')
            ->where(['user_id' => $followingIds])
            ->andWhere(['>', 'expires_at', time()])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    
    public static function getUserStories($userId, $limit = 10)
    {
        return self::find()
            ->where(['user_id' => $userId])
            ->andWhere(['>', 'expires_at', time()])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    
    public function isActive()
    {
        return $this->expires_at > time();
    }

    
    public function getTimeLeft()
    {
        $timeLeft = $this->expires_at - time();
        
        if ($timeLeft <= 0) {
            return 'Истекло';
        }
        
        $hours = floor($timeLeft / 3600);
        $minutes = floor(($timeLeft % 3600) / 60);
        
        if ($hours > 0) {
            return $hours . 'ч ' . $minutes . 'м';
        }
        
        return $minutes . 'м';
    }

    
    public static function deleteExpired()
    {
        return self::deleteAll(['<', 'expires_at', time()]);
    }

    
    public function getImageUrl()
    {
        if (empty($this->image)) {
            return null;
        }
        
        if (strpos($this->image, 'http') === 0) {
            return $this->image;
        }
        
        return \Yii::$app->request->baseUrl . '/uploads/stories/' . $this->image;
    }

    public function afterDelete()
    {
        parent::afterDelete();

        if ($this->image) {
            $path = \Yii::getAlias('@webroot/uploads/stories/' . $this->image);
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $data = parent::toArray($fields, $expand, $recursive);
        $data['author'] = $this->user ? [
            'id' => $this->user->id,
            'username' => $this->user->username,
            'avatar' => $this->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $this->user->id,
        ] : null;
        $data['image_url'] = $this->getImageUrl();
        $data['time_left'] = $this->getTimeLeft();
        $data['timeAgo'] = $this->getTimeAgo();
        return $data;
    }

    public function getTimeAgo()
    {
        $diff = time() - $this->created_at;
        
        if ($diff < 60) return 'только что';
        if ($diff < 3600) return floor($diff / 60) . ' мин. назад';
        if ($diff < 86400) return floor($diff / 3600) . ' ч. назад';
        
        return date('d.m.Y', $this->created_at);
    }
}
