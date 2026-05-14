<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\web\Response;

class ApiValidator extends Component
{
    
    public static function validateCsrfToken($token = null)
    {
        if ($token === null) {
            $token = Yii::$app->request->post('_csrf') 
                    ?: Yii::$app->request->get('_csrf')
                    ?: Yii::$app->request->headers->get('X-CSRF-Token');
        }
        
        if (!$token) {
            return false;
        }
        
        return Yii::$app->security->validateCsrfToken($token);
    }
    
    
    public static function requireAuth()
    {
        if (Yii::$app->user->isGuest) {
            return self::error('Не авторизован', 401);
        }
        
        return true;
    }
    
    
    public static function requireAdmin()
    {
        if (Yii::$app->user->isGuest || Yii::$app->user->identity->username !== 'admin') {
            return self::error('Доступ запрещен', 403);
        }
        
        return true;
    }
    
    
    public static function validatePostData($data)
    {
        $errors = [];
        
        if (empty($data['content'])) {
            $errors[] = 'Содержание поста обязательно';
        } elseif (strlen($data['content']) > 2000) {
            $errors[] = 'Содержание поста слишком длинное (максимум 2000 символов)';
        }
        
        if (!empty($data['image_url']) && !filter_var($data['image_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Некорректный URL изображения';
        }
        
        return empty($errors) ? true : $errors;
    }
    
    
    public static function validateCommentData($data)
    {
        $errors = [];
        
        if (empty($data['content'])) {
            $errors[] = 'Текст комментария обязателен';
        } elseif (strlen($data['content']) > 1000) {
            $errors[] = 'Комментарий слишком длинный (максимум 1000 символов)';
        }
        
        if (empty($data['post_id']) || !is_numeric($data['post_id'])) {
            $errors[] = 'Некорректный ID поста';
        }
        
        return empty($errors) ? true : $errors;
    }
    
    
    public static function validatePollData($data)
    {
        $errors = [];
        
        if (empty($data['question'])) {
            $errors[] = 'Вопрос опроса обязателен';
        } elseif (strlen($data['question']) > 255) {
            $errors[] = 'Вопрос слишком длинный (максимум 255 символов)';
        }
        
        if (empty($data['options']) || !is_array($data['options'])) {
            $errors[] = 'Варианты ответа обязательны';
        } elseif (count($data['options']) < 2) {
            $errors[] = 'Минимум 2 варианта ответа';
        } elseif (count($data['options']) > 10) {
            $errors[] = 'Максимум 10 вариантов ответа';
        }
        
        if (!empty($data['options']) && is_array($data['options'])) {
            foreach ($data['options'] as $option) {
                if (empty($option) || strlen($option) > 100) {
                    $errors[] = 'Вариант ответа должен быть от 1 до 100 символов';
                    break;
                }
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    
    public static function validateUserData($data)
    {
        $errors = [];
        
        if (!empty($data['username'])) {
            if (strlen($data['username']) < 3 || strlen($data['username']) > 255) {
                $errors[] = 'Имя пользователя должно быть от 3 до 255 символов';
            }
            
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['username'])) {
                $errors[] = 'Имя пользователя может содержать только буквы, цифры, _ и -';
            }
        }
        
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Некорректный email';
            }
        }
        
        if (!empty($data['bio']) && strlen($data['bio']) > 500) {
            $errors[] = 'Биография слишком длинная (максимум 500 символов)';
        }
        
        if (!empty($data['location']) && strlen($data['location']) > 100) {
            $errors[] = 'Местоположение слишком длинное (максимум 100 символов)';
        }
        
        if (!empty($data['website'])) {
            if (!filter_var($data['website'], FILTER_VALIDATE_URL)) {
                $errors[] = 'Некорректный URL сайта';
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    
    public static function validateUserId($userId)
    {
        if (!is_numeric($userId) || $userId <= 0) {
            return 'Некорректный ID пользователя';
        }
        
        $user = \app\models\User::findOne($userId);
        if (!$user) {
            return 'Пользователь не найден';
        }
        
        return true;
    }
    
    
    public static function checkRateLimit($key, $limit = 60, $window = 3600)
    {
        $cache = Yii::$app->cache;
        $cacheKey = "rate_limit_{$key}";
        
        $requests = $cache->get($cacheKey, 0);
        
        if ($requests >= $limit) {
            return self::error('Слишком много запросов', 429);
        }
        
        $cache->set($cacheKey, $requests + 1, $window);
        
        return true;
    }
    
    
    public static function error($message, $code = 400)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->statusCode = $code;
        
        return [
            'success' => false,
            'error' => $message,
            'code' => $code
        ];
    }
    
    
    public static function success($data = null, $message = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $response = ['success' => true];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        return $response;
    }
    
    
    public static function sanitize($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    
    public static function getClientIp()
    {
        $ip = Yii::$app->request->getUserIP();

        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
        ];
        
        foreach ($headers as $header) {
            $ips = Yii::$app->request->headers->get($header);
            if ($ips) {
                $ipArray = explode(',', $ips);
                $ip = trim($ipArray[0]);
                break;
            }
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
    }
}
