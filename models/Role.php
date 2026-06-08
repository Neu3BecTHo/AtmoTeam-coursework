<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Role model
 *
 * @property int $id
 * @property string $name
 * @property int $type
 * @property string $description
 * @property int $is_system
 * @property int $created_at
 * @property int $updated_at
 */
class Role extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%auth_item}}';
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
            [['name'], 'required'],
            [['name'], 'string', 'max' => 64],
            [['name'], 'unique'],
            [['description'], 'string', 'max' => 255],
            [['type'], 'integer'],
            [['is_system'], 'boolean'],
            [['is_system'], 'default', 'value' => 0],
            ['type', 'default', 'value' => 1],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название роли',
            'description' => 'Описание',
            'is_system' => 'Системная роль',
            'created_at' => 'Создана',
            'updated_at' => 'Обновлена',
        ];
    }

    public function getUsers()
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])
            ->viaTable('{{%auth_assignment}}', ['item_name' => 'name']);
    }
}