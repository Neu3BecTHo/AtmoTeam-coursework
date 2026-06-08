<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;

/**
 * Story model
 *
 * @property int $id
 * @property int $user_id
 * @property string $image
 * @property string|null $caption
 * @property int $expires_at
 * @property int $created_at
 */
class Story extends ActiveRecord
{
    public $imageFile;

    public static function tableName()
    {
        return '{{%story}}';
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
            [['user_id'], 'required'],
            [['user_id', 'expires_at', 'created_at'], 'integer'],
            [['caption'], 'string'],
            [['image'], 'string', 'max' => 255],
            [['imageFile'], 'file',
                'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                'maxSize' => 10 * 1024 * 1024,
                'skipOnEmpty' => true,
                'wrongExtension' => 'Неподдерживаемый формат. Допустимы: JPG, PNG, GIF, WEBP',
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Пользователь',
            'image' => 'Изображение истории',
            'imageFile' => 'Файл истории',
            'caption' => 'Подпись',
            'expires_at' => 'Истекает через',
            'created_at' => 'Дата создания',
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

        if ($insert && !$this->expires_at) {
            $this->expires_at = time() + 24 * 60 * 60;
        }

        $this->uploadImage();

        return true;
    }

    private function uploadImage()
    {
        if (!$this->imageFile instanceof UploadedFile) {
            return;
        }

        $uploadDir = Yii::getAlias('@webroot/uploads/stories/');
        FileHelper::createDirectory($uploadDir, 0755);

        $filename = 'story_' . $this->user_id . '_' . time() . '.' . $this->imageFile->extension;
        
        if ($this->imageFile->saveAs($uploadDir . $filename)) {
            $this->image = $filename;
        }

        $this->imageFile = null;
    }

    public function isActive(): bool
    {
        return $this->expires_at > time();
    }

    public function getTimeLeft(): string
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

    public function getImageUrl(): ?string
    {
        if (!$this->image) {
            return null;
        }
        
        if (strpos($this->image, 'http') === 0) {
            return $this->image;
        }
        
        return Yii::$app->request->baseUrl . '/uploads/stories/' . $this->image;
    }

    public function getTimeAgo(): string
    {
        $diff = time() - $this->created_at;
        
        if ($diff < 60) return 'только что';
        if ($diff < 3600) return floor($diff / 60) . ' мин. назад';
        if ($diff < 86400) return floor($diff / 3600) . ' ч. назад';
        
        return date('d.m.Y', $this->created_at);
    }

    public static function getFollowingStories($userId, $limit = 20)
    {
        $followingIds = Follow::getFollowingIds($userId);
        $followingIds[] = $userId;

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

    public static function deleteExpired()
    {
        return self::deleteAll(['<', 'expires_at', time()]);
    }

    public function afterDelete()
    {
        parent::afterDelete();

        if ($this->image) {
            $path = Yii::getAlias('@webroot/uploads/stories/' . $this->image);
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function fields()
    {
        return [
            'id',
            'user_id',
            'image_url' => 'imageUrl',
            'caption',
            'expires_at',
            'created_at',
            'time_left' => 'timeLeft',
            'timeAgo' => 'timeAgo',
            'is_active' => 'isActive',
            'author' => function () {
                return $this->user ? [
                    'id' => $this->user->id,
                    'username' => $this->user->username,
                    'avatar' => $this->user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $this->user->id,
                ] : null;
            },
        ];
    }
}