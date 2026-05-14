<?php

namespace app\models;

use yii\db\ActiveRecord;


class AuthItemChild extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%auth_item_child}}';
    }

    public function rules()
    {
        return [
            [['parent', 'child'], 'required'],
            [['parent', 'child'], 'string', 'max' => 64],
            [['parent', 'child'], 'unique', 'targetAttribute' => ['parent', 'child']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'parent' => 'Родитель',
            'child' => 'Потомок',
        ];
    }

    
    public function getParentItem()
    {
        return $this->hasOne(\yii\rbac\Item::class, ['name' => 'parent']);
    }

    
    public function getChildItem()
    {
        return $this->hasOne(\yii\rbac\Item::class, ['name' => 'child']);
    }

    
    public static function getChildren($parentName)
    {
        return static::find()
            ->with(['childItem'])
            ->where(['parent' => $parentName])
            ->all();
    }

    
    public static function getParents($childName)
    {
        return static::find()
            ->with(['parentItem'])
            ->where(['child' => $childName])
            ->all();
    }

    
    public static function existsRelation($parentName, $childName)
    {
        return static::find()
            ->where(['parent' => $parentName, 'child' => $childName])
            ->exists();
    }

    
    public static function createRelation($parentName, $childName)
    {
        if (!static::existsRelation($parentName, $childName)) {
            $relation = new static();
            $relation->parent = $parentName;
            $relation->child = $childName;
            return $relation->save();
        }
        return true;
    }

    
    public static function deleteRelation($parentName, $childName)
    {
        return static::deleteAll([
            'parent' => $parentName,
            'child' => $childName
        ]);
    }

    
    public static function deleteAllChildren($parentName)
    {
        return static::deleteAll(['parent' => $parentName]);
    }

    
    public static function deleteAllParents($childName)
    {
        return static::deleteAll(['child' => $childName]);
    }
}
