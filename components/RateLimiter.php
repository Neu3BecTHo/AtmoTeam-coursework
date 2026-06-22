<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\web\Response;

class RateLimiter extends Component
{
    private static function getIdentifier()
    {
        if (!Yii::$app->user->isGuest) {
            return 'user_' . Yii::$app->user->id;
        }

        $ip = Yii::$app->request->getUserIP();
        return 'ip_' . md5($ip ?: '127.0.0.1');
    }

    private static function setRateLimitHeaders($limit, $remaining, $resetTime)
    {
        Yii::$app->response->headers->set('X-RateLimit-Limit', $limit);
        Yii::$app->response->headers->set('X-RateLimit-Remaining', $remaining);
        Yii::$app->response->headers->set('X-RateLimit-Reset', $resetTime);
    }

    public static function check($key, $limit = 60, $window = 3600, $identifier = null)
    {
        $identifier = $identifier ?: self::getIdentifier();
        $cacheKey = "rate_limit_{$key}_{$identifier}";

        $cache = Yii::$app->cache;

        $requests = $cache->get($cacheKey);
        if ($requests === false) {
            $requests = 0;
        }

        if ($requests >= $limit) {
            $expiresAt = $cache->get("{$cacheKey}_expires");
            $resetTime = $expiresAt ?: (time() + $window);

            self::setRateLimitHeaders($limit, 0, $resetTime);

            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'success' => false,
                'error' => 'Слишком много запросов. Попробуйте позже.',
                'code' => 429,
                'retry_after' => $resetTime - time()
            ];
        }

        $newRequests = $requests + 1;
        $cache->set($cacheKey, $newRequests, $window);

        if ($requests === 0) {
            $cache->set("{$cacheKey}_expires", time() + $window, $window);
        }

        $remaining = $limit - $newRequests;
        $expiresAt = $cache->get("{$cacheKey}_expires");
        $resetTime = $expiresAt ?: (time() + $window);

        self::setRateLimitHeaders($limit, $remaining, $resetTime);

        return true;
    }

    /**
     * Проверка лимита для постов — 100 в час
     */
    public static function checkPostLimit()
    {
        return self::check('posts', 100, 3600);
    }

    /**
     * Проверка лимита для комментариев — 100 в час (увеличено с 30)
     */
    public static function checkCommentLimit()
    {
        return self::check('comments', 100, 3600);
    }

    /**
     * Проверка лимита для лайков — 200 в час (увеличено с 100)
     */
    public static function checkLikeLimit()
    {
        return self::check('likes', 200, 3600);
    }

    /**
     * Проверка лимита для подписок — 100 в час (увеличено с 50)
     */
    public static function checkFollowLimit()
    {
        return self::check('follows', 100, 3600);
    }

    /**
     * Проверка лимита для сообщений — 60 в минуту (было 20 в час)
     * Для активного чата это более разумно
     */
    public static function checkMessageLimit()
    {
        return self::check('messages', 60, 60); // 60 сообщений в минуту
    }

    /**
     * Проверка лимита для загрузки изображений — 30 в минуту
     */
    public static function checkImageUploadLimit()
    {
        return self::check('image_upload', 30, 60); // 30 загрузок в минуту
    }

    /**
     * Проверка лимита для запросов диалогов (поллинг) — 120 в минуту
     */
    public static function checkDialogueLimit()
    {
        return self::check('dialogue', 120, 60); // 120 запросов в минуту
    }

    /**
     * Проверка лимита для списка диалогов — 60 в минуту
     */
    public static function checkDialoguesListLimit()
    {
        return self::check('dialogues_list', 60, 60); // 60 запросов в минуту
    }

    /**
     * Проверка лимита для голосований — 100 в час (увеличено с 50)
     */
    public static function checkVoteLimit()
    {
        return self::check('votes', 100, 3600);
    }

    /**
     * Проверка лимита для API — 2000 в час (увеличено с 1000)
     */
    public static function checkApiLimit()
    {
        return self::check('api', 2000, 3600);
    }

    /**
     * Проверка лимита для авторизации — 5 за 15 минут
     */
    public static function checkAuthLimit()
    {
        $ip = Yii::$app->request->getUserIP();
        $identifier = 'ip_' . md5($ip ?: '127.0.0.1');
        return self::check('auth', 5, 900, $identifier);
    }

    /**
     * Проверка лимита для регистрации — 3 в час
     */
    public static function checkRegisterLimit()
    {
        $ip = Yii::$app->request->getUserIP();
        $identifier = 'ip_' . md5($ip ?: '127.0.0.1');
        return self::check('register', 3, 3600, $identifier);
    }

    /**
     * Проверка лимита для сброса пароля — 3 в час
     */
    public static function checkPasswordResetLimit()
    {
        $ip = Yii::$app->request->getUserIP();
        $identifier = 'ip_' . md5($ip ?: '127.0.0.1');
        return self::check('password_reset', 3, 3600, $identifier);
    }

    public static function reset($key, $identifier = null)
    {
        $identifier = $identifier ?: self::getIdentifier();
        $cacheKey = "rate_limit_{$key}_{$identifier}";

        $cache = Yii::$app->cache;
        $cache->delete($cacheKey);
        $cache->delete("{$cacheKey}_expires");

        return true;
    }
}