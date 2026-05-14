<?php

namespace app\models;

use yii\db\ActiveRecord;


class AuthAssignment extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%auth_assignment}}';
    }

    public function rules()
    {
        return [
            [['item_name', 'user_id'], 'required'],
            [['user_id'], 'integer'],
            [['item_name'], 'string', 'max' => 64],
            [['user_id', 'item_name'], 'unique', 'targetAttribute' => ['user_id', 'item_name']],
            [['created_at', 'expires_at'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'item_name' => 'Роль/Разрешение',
            'user_id' => 'Пользователь',
            'created_at' => 'Назначено',
            'expires_at' => 'Истекает',
        ];
    }

    
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    
    public function getItem()
    {
        return $this->hasOne(\yii\rbac\Item::class, ['name' => 'item_name']);
    }

    
    public function isActive()
    {
        return $this->expires_at === null || $this->expires_at > time();
    }

    
    public static function getUserAssignments($userId)
    {
        return static::find()
            ->with(['user', 'item'])
            ->where(['user_id' => $userId])
            ->andWhere(['or', ['expires_at' => null], ['>', 'expires_at', time()]])
            ->all();
    }

    
    public static function hasAssignment($userId, $itemName)
    {
        return static::find()
            ->where(['user_id' => $userId, 'item_name' => $itemName])
            ->andWhere(['or', ['expires_at' => null], ['>', 'expires_at', time()]])
            ->exists();
    }

    
    public static function assign($userId, $itemName, $expiresAt = null)
    {
        $assignment = new static();
        $assignment->user_id = $userId;
        $assignment->item_name = $itemName;
        $assignment->created_at = time();
        $assignment->expires_at = $expiresAt;
        
        return $assignment->save();
    }

    
    public static function revoke($userId, $itemName)
    {
        return static::deleteAll([
            'user_id' => $userId,
            'item_name' => $itemName
        ]);
    }

    
    public static function getUsersWithRole($roleName)
    {
        return static::find()
            ->with(['user'])
            ->where(['item_name' => $roleName])
            ->andWhere(['or', ['expires_at' => null], ['>', 'expires_at', time()]])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = time();
            }
            return true;
        }
        return false;
    }
}
