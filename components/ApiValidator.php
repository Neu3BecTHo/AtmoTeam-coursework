<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\web\Response;
use app\models\User;

class ApiValidator extends Component
{
    public static function getRequestData(): array
    {
        $params = Yii::$app->request->getBodyParams();
        if (!empty($params)) {
            return $params;
        }

        $rawBody = Yii::$app->request->getRawBody();
        $data = $rawBody ? json_decode($rawBody, true) : [];

        return is_array($data) ? $data : [];
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
        $user = Yii::$app->user;
        if ($user->isGuest || !$user->can('accessAdminPanel')) {
            return self::error('Доступ запрещен', 403);
        }
        return true;
    }

    public static function validatePostData($data)
    {
        $errors = [];

        if (empty($data['content'])) {
            $errors[] = 'Содержание поста обязательно';
        } elseif (strlen($data['content']) > 5000) {
            $errors[] = 'Содержание поста слишком длинное (максимум 5000 символов)';
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
        } elseif (!empty($data['options'])) {
            foreach ($data['options'] as $option) {
                if (empty($option) || strlen($option) > 100) {
                    $errors[] = 'Вариант ответа должен быть от 1 до 100 символов';
                    break;
                }
            }
        }

        return empty($errors) ? true : $errors;
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
}