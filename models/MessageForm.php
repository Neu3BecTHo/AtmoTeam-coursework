<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;

/**
 * MessageForm - форма для отправки сообщений с изображениями
 */
class MessageForm extends Model
{
    public $receiver_id;
    public $content;
    public $images;

    public function rules()
    {
        return [
            [['receiver_id'], 'required'],
            [['receiver_id'], 'integer'],
            [['content'], 'string', 'max' => 1000],
            [['images'], 'file', 
                'skipOnEmpty' => true, 
                'extensions' => 'jpg, jpeg, png, gif, webp', 
                'maxSize' => 5 * 1024 * 1024,
                'maxFiles' => 5,
                'tooBig' => 'Максимальный размер файла 5MB',
                'wrongExtension' => 'Допустимые форматы: JPG, PNG, GIF, WEBP',
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'receiver_id' => 'Получатель',
            'content' => 'Сообщение',
            'images' => 'Изображения',
        ];
    }

    public function sendMessage()
    {
        if (!$this->validate()) {
            return false;
        }

        $userId = Yii::$app->user->id;

        if (!Message::canSendMessage($userId, $this->receiver_id)) {
            $this->addError('receiver_id', 'Вы не можете отправлять сообщения этому пользователю');
            return false;
        }

        $imageUrls = $this->uploadImages();

        return Message::sendMessage($userId, $this->receiver_id, $this->content, $imageUrls);
    }

    private function uploadImages(): array
    {
        if (!$this->images) {
            return [];
        }

        $uploadDir = Yii::getAlias('@webroot/uploads/messages/' . date('Y/m'));
        FileHelper::createDirectory($uploadDir, 0755);

        $urls = [];
        foreach ($this->images as $image) {
            $url = $this->saveImage($image, $uploadDir);
            if ($url) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    private function saveImage(UploadedFile $image, string $uploadDir): ?string
    {
        $filename = uniqid() . '_' . time() . '_' . rand(1000, 9999) . '.' . $image->extension;
        $filePath = $uploadDir . '/' . $filename;

        if ($image->saveAs($filePath)) {
            return Yii::$app->request->baseUrl . '/uploads/messages/' . date('Y/m') . '/' . $filename;
        }

        return null;
    }

    public function hasContent(): bool
    {
        return !empty(trim($this->content)) || !empty($this->images);
    }
}