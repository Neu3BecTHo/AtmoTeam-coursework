<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\components\RateLimiter;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user = false;

    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => 'Имя пользователя',
            'password' => 'Пароль',
            'rememberMe' => 'Запомни меня',
        ];
    }

    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Неверное имя пользователя или пароль.');
            } elseif ($user->is_blocked) {
                $this->addError($attribute, 'Аккаунт заблокирован. Обратитесь к администратору.');
            } elseif ($user->status != 10) {
                $this->addError($attribute, 'Аккаунт деактивирован.');
            }
        }
    }

    public function login()
    {
        $rateLimitCheck = RateLimiter::checkAuthLimit();
        if ($rateLimitCheck !== true) {
            return false;
        }
        if ($this->validate()) {
            $duration = $this->rememberMe ? 3600 * 24 * 30 : 0;
            return Yii::$app->user->login($this->getUser(), $duration);
        }
        return false;
    }

    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::findByUsername($this->username);
        }
        return $this->_user;
    }
}
