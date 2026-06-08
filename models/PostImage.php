<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * PostImage model
 *
 * @property int $id
 * @property int $post_id
 * @property string $filename
 * @property int $sort_order
 * @property int $created_at
 * @property int $updated_at
 */
class PostImage extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%post_image}}';
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
            [['post_id', 'filename'], 'required'],
            [['post_id', 'sort_order', 'created_at', 'updated_at'], 'integer'],
            [['filename'], 'string', 'max' => 255],
            ['sort_order', 'default', 'value' => 0],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'post_id' => 'Пост',
            'filename' => 'Файл',
            'sort_order' => 'Порядок',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата обновления',
        ];
    }

    public function getPost()
    {
        return $this->hasOne(Post::class, ['id' => 'post_id']);
    }

    public function getImageUrl(): string
    {
        $year = date('Y', $this->created_at);
        $month = date('m', $this->created_at);
        return Yii::getAlias('@web') . "/uploads/posts/{$year}/{$month}/{$this->filename}";
    }

    public function getFullPath(): string
    {
        $year = date('Y', $this->created_at);
        $month = date('m', $this->created_at);
        return Yii::getAlias("@webroot/uploads/posts/{$year}/{$month}/{$this->filename}");
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        $filePath = $this->getFullPath();
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        return true;
    }

    public function fields()
    {
        return [
            'id',
            'post_id',
            'url' => 'imageUrl',
            'sort_order',
        ];
    }
}