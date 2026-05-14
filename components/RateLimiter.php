<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\web\Response;

class RateLimiter extends Component
{
    
    public static function check($key, $limit = 60, $window = 3600, $identifier = null)
    {
        $identifier = $identifier ?: self::getIdentifier();
        $cacheKey = "rate_limit_{$key}_{$identifier}";
        
        $cache = Yii::$app->cache;

        $requests = $cache->get($cacheKey, 0);
        
        if ($requests >= $limit) {

            $remaining = 0;
            $resetTime = time() + $cache->get("{$cacheKey}_expires", $window);
            
            self::setRateLimitHeaders($limit, $remaining, $resetTime);
            
            return ApiValidator::error('Слишком много запросов. Попробуйте позже.', 429);
        }

        $newRequests = $requests + 1;
        $cache->set($cacheKey, $newRequests, $window);

        if ($requests === 0) {
            $cache->set("{$cacheKey}_expires", time() + $window, $window);
        }
        
        $remaining = $limit - $newRequests;
        $resetTime = time() + $cache->get("{$cacheKey}_expires", $window);
        
        self::setRateLimitHeaders($limit, $remaining, $resetTime);
        
        return true;
    }
    
    
    private static function getIdentifier()
    {

        if (!Yii::$app->user->isGuest) {
            return 'user_' . Yii::$app->user->id;
        }

        $ip = ApiValidator::getClientIp();
        return 'ip_' . md5($ip);
    }
    
    
    private static function setRateLimitHeaders($limit, $remaining, $resetTime)
    {
        Yii::$app->response->headers->set('X-RateLimit-Limit', $limit);
        Yii::$app->response->headers->set('X-RateLimit-Remaining', $remaining);
        Yii::$app->response->headers->set('X-RateLimit-Reset', $resetTime);
    }
    
    
    public static function checkPostLimit()
    {
        return self::check('posts', 100, 3600); // 100 постов в час (dev-friendly)
    }
    
    
    public static function checkCommentLimit()
    {
        return self::check('comments', 30, 3600); // 30 комментариев в час
    }
    
    
    public static function checkLikeLimit()
    {
        return self::check('likes', 100, 3600); // 100 лайков в час
    }
    
    
    public static function checkFollowLimit()
    {
        return self::check('follows', 50, 3600); // 50 подписок в час
    }
    
    
    public static function checkMessageLimit()
    {
        return self::check('messages', 20, 3600); // 20 сообщений в час
    }
    
    
    public static function checkVoteLimit()
    {
        return self::check('votes', 50, 3600); // 50 голосований в час
    }
    
    
    public static function checkApiLimit()
    {
        return self::check('api', 1000, 3600); // 1000 запросов в час
    }
    
    
    public static function checkAuthLimit()
    {
        $ip = ApiValidator::getClientIp();
        return self::check('auth', 5, 900, 'ip_' . md5($ip)); // 5 попыток за 15 минут
    }
    
    
    public static function checkRegisterLimit()
    {
        $ip = ApiValidator::getClientIp();
        return self::check('register', 3, 3600, 'ip_' . md5($ip)); // 3 регистрации в час с IP
    }
    
    
    public static function checkPasswordResetLimit()
    {
        $ip = ApiValidator::getClientIp();
        return self::check('password_reset', 3, 3600, 'ip_' . md5($ip)); // 3 запроса в час
    }
    
    
    public static function getStats($key, $identifier = null)
    {
        $identifier = $identifier ?: self::getIdentifier();
        $cacheKey = "rate_limit_{$key}_{$identifier}";
        
        $cache = Yii::$app->cache;
        $requests = $cache->get($cacheKey, 0);
        $expires = $cache->get("{$cacheKey}_expires", 0);
        
        return [
            'requests' => $requests,
            'remaining' => max(0, self::getLimit($key) - $requests),
            'reset_time' => $expires,
            'reset_in' => max(0, $expires - time()),
        ];
    }
    
    
    private static function getLimit($key)
    {
        $limits = [
            'posts' => 10,
            'comments' => 30,
            'likes' => 100,
            'follows' => 50,
            'messages' => 20,
            'votes' => 50,
            'api' => 1000,
            'auth' => 5,
            'register' => 3,
            'password_reset' => 3,
        ];
        
        return $limits[$key] ?? 60;
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
    
    
    public static function cleanup()
    {


        return true;
    }
}
