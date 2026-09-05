<?php

namespace app\components;

use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\web\View;

/**
 * SecurityHeadersBootstrap adds security headers to all responses.
 */
class SecurityHeadersBootstrap implements BootstrapInterface
{
    public function bootstrap($app)
    {
        Event::on(View::className(), View::EVENT_BEGIN_PAGE, function () use ($app) {
            $app->response->headers->set('X-Content-Type-Options', 'nosniff');
            $app->response->headers->set('X-Frame-Options', 'DENY');
            $app->response->headers->set('X-XSS-Protection', '1; mode=block');
            $app->response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            $app->response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $app->response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        });
    }
}
