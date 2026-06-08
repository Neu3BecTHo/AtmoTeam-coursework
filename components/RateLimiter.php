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
            return ApiValidator::error('Слишком много запросов. Попробуйте позже.', 429);
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

    public static function checkPostLimit()
    {
        return self::check('posts', 100, 3600);
    }

    public static function checkCommentLimit()
    {
        return self::check('comments', 30, 3600);
    }

    public static function checkLikeLimit()
    {
        return self::check('likes', 100, 3600);
    }

    public static function checkFollowLimit()
    {
        return self::check('follows', 50, 3600);
    }

    public static function checkMessageLimit()
    {
        return self::check('messages', 20, 3600);
    }

    public static function checkVoteLimit()
    {
        return self::check('votes', 50, 3600);
    }

    public static function checkApiLimit()
    {
        return self::check('api', 1000, 3600);
    }

    public static function checkAuthLimit()
    {
        $ip = Yii::$app->request->getUserIP();
        $identifier = 'ip_' . md5($ip ?: '127.0.0.1');
        return self::check('auth', 5, 900, $identifier);
    }

    public static function checkRegisterLimit()
    {
        $ip = Yii::$app->request->getUserIP();
        $identifier = 'ip_' . md5($ip ?: '127.0.0.1');
        return self::check('register', 3, 3600, $identifier);
    }

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