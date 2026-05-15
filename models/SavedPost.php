<?php
namespace app\models;
use yii\db\ActiveRecord;


class SavedPost extends ActiveRecord
{
    public static function tableName() { return '{{%saved_post}}'; }
    
    public function behaviors() {
        return [];
    }
    
    public function beforeSave($insert) {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($insert) {
            $this->created_at = time();
        }
        return true;
    }
    
    public function rules() {
        return [
            [['user_id', 'post_id'], 'required'],
            [['user_id', 'post_id'], 'integer'],
            [['user_id', 'post_id'], 'unique', 'targetAttribute' => ['user_id', 'post_id']],
        ];
    }
    
    public function getUser() {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
    
    public function getPost() {
        return $this->hasOne(Post::class, ['id' => 'post_id']);
    }
    
public static function toggle($userId, $postId) {
        $existing = self::findOne(['user_id' => $userId, 'post_id' => $postId]);
        if ($existing) {
            $existing->delete();
            $newCount = self::getSavedCount($postId);
            return ['saved' => false, 'count' => $newCount];
        }
        $model = new self(['user_id' => $userId, 'post_id' => $postId]);
        if ($model->save()) {
            $newCount = self::getSavedCount($postId);
            return ['saved' => true, 'count' => $newCount];
        }
        return ['saved' => false, 'error' => 'Ошибка сохранения'];
    }
    
    public static function isSaved($userId, $postId) {
        return self::find()->where(['user_id' => $userId, 'post_id' => $postId])->exists();
    }
    
    public static function getSavedCount($postId) {
        return self::find()->where(['post_id' => $postId])->count();
    }
    
    public static function getUserSavedPosts($userId, $limit = 20, $offset = 0) {
        return Post::find()
            ->joinWith('savedPosts')
            ->where(['saved_post.user_id' => $userId])
            ->orderBy(['saved_post.created_at' => SORT_DESC])
            ->limit($limit)
            ->offset($offset)
            ->all();
    }
}
