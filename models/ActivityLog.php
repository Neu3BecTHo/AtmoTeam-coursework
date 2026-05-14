<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;


class ActivityLog extends ActiveRecord
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_VIEW = 'view';
    const ACTION_LIKE = 'like';
    const ACTION_COMMENT = 'comment';
    const ACTION_FOLLOW = 'follow';
    const ACTION_UNFOLLOW = 'unfollow';
    const ACTION_UPLOAD = 'upload';

    
    public static function tableName()
    {
        return '{{%activity_log}}';
    }

    
    public function rules()
    {
        return [
            [['user_id', 'action', 'created_at'], 'required'],
            [['user_id', 'model_id', 'created_at'], 'integer'],
            [['action', 'description'], 'string', 'max' => 255],
            [['model_type'], 'string', 'max' => 100],
            [['ip_address'], 'string', 'max' => 45],
            [['user_agent'], 'string', 'max' => 500],
        ];
    }

    
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Пользователь',
            'action' => 'Действие',
            'model_type' => 'Тип модели',
            'model_id' => 'ID модели',
            'description' => 'Описание',
            'ip_address' => 'IP адрес',
            'user_agent' => 'User Agent',
            'created_at' => 'Дата',
        ];
    }

    
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    
    public static function log($action, $description = '', $modelType = null, $modelId = null)
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        $log = new self();
        $log->user_id = Yii::$app->user->id;
        $log->action = $action;
        $log->description = $description;
        $log->model_type = $modelType;
        $log->model_id = $modelId;
        $log->ip_address = Yii::$app->request->userIP;
        $log->user_agent = Yii::$app->request->userAgent;
        $log->created_at = time();

        return $log->save();
    }

    
    public static function getRecentActivities($limit = 50)
    {
        return self::find()
            ->with(['user'])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    
    public static function getUserActivities($userId, $limit = 100)
    {
        return self::find()
            ->where(['user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    
    public static function getActionLabels()
    {
        return [
            self::ACTION_CREATE => 'Создание',
            self::ACTION_UPDATE => 'Обновление',
            self::ACTION_DELETE => 'Удаление',
            self::ACTION_LOGIN => 'Вход',
            self::ACTION_LOGOUT => 'Выход',
            self::ACTION_VIEW => 'Просмотр',
            self::ACTION_LIKE => 'Лайк',
            self::ACTION_COMMENT => 'Комментарий',
            self::ACTION_FOLLOW => 'Подписка',
            self::ACTION_UNFOLLOW => 'Отписка',
            self::ACTION_UPLOAD => 'Загрузка',
        ];
    }

    
    public function getActionLabel()
    {
        $labels = self::getActionLabels();
        return $labels[$this->action] ?? $this->action;
    }
}
