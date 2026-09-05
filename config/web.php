<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'app\components\LanguageComponent', 'securityHeaders'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'language' => 'ru-RU', // ← ЖЕСТКО задайте язык по умолчанию
    'sourceLanguage' => 'ru-RU',
    'timeZone' => 'Europe/Moscow',
    'modules' => [
        'api' => [
            'class' => 'app\modules\api\Module',
        ],
    ],
    'components' => [
        'securityHeaders' => [
            'class' => 'app\components\SecurityHeadersBootstrap',
        ],
        'request' => [
            'cookieValidationKey' => function() {
                $key = getenv('COOKIE_VALIDATION_KEY');
                if ($key === false || $key === '') {
                    throw new \yii\base\InvalidConfigException('COOKIE_VALIDATION_KEY environment variable is required');
                }
                return $key;
            }(),
            'baseUrl' => '',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'session' => [
            'class' => 'yii\web\Session',
            'cookieParams' => [
                'httponly' => true,
                'path' => '/',
                'samesite' => 'Lax',
            ],
        ],
        'i18n' => [
            'translations' => [
                'app*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => 'ru-RU',
                    'fileMap' => [
                        'app*' => 'app.php',
                    ],
                ],
            ],
        ],
        'db' => $db,
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
            'defaultRoles' => ['guest'],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '/' => 'feed/index',
                'feed' => 'feed/index',

                // User routes
                'api/user/save-public-key' => 'user/save-public-key',
                'api/user/public-key' => 'user/public-key',
                
                // API routes
                'api/posts' => 'feed/get-posts',
                'api/post/create' => 'feed/create-post',
                'api/post/like' => 'feed/like',
                'api/post/save' => 'feed/save-post',
                'api/saved-posts' => 'feed/saved-posts',
                'api/comments/<post_id:\d+>' => 'feed/get-comments',
                'api/comment/create' => 'feed/comment',
                'api/comment/update' => 'feed/update-comment',
                'api/comment/delete' => 'feed/delete-comment',
                'api/repost' => 'feed/repost',
                'api/vote' => 'poll/vote',
                'api/vote/cancel' => 'poll/cancel-vote',
                'api/poll' => 'feed/poll',
                'api/notifications' => 'feed/get-notifications',
                'api/notifications/read' => 'feed/mark-notifications-read',
                'api/notifications/unread-count' => 'feed/unread-count',
                'api/notifications/read-all' => 'feed/mark-notifications-read',
                
                // Message routes
                'message' => 'message/index',
                'message/dialogue/<id:\d+>' => 'message/dialogue',
                'api/message/unread-count' => 'message/unread-count',
                'api/message/send' => 'message/send',
                'api/message/mark-read' => 'message/mark-read',
                'api/message/upload-images' => 'message/upload-images',
                'api/message/get-dialogue/<id:\d+>' => 'message/get-dialogue',
                'api/message/get-dialogues' => 'message/get-dialogues',
                'api/message/delete' => 'message/delete',
                
                // Profile routes
                'profile' => 'profile/view',
                'profile/<id:\d+>' => 'profile/view',
                'profile/edit' => 'profile/edit',
                'profile/<id:\d+>/posts' => 'profile/posts',
                'profile/<id:\d+>/saved' => 'profile/saved',
                'profile/<id:\d+>/reposts' => 'profile/reposts',
                'profile/<id:\d+>/follow' => 'profile/follow',
                'profile/<id:\d+>/unfollow' => 'profile/unfollow',
                'profile/<id:\d+>/followers' => 'profile/followers',
                'profile/<id:\d+>/following' => 'profile/following',
                'api/profile/block' => 'profile/block',
                'api/profile/unblock' => 'profile/unblock',
                'api/profile/delete-account' => 'profile/delete-account',
                
                // Admin routes
                'admin' => 'admin/index',
                'api/admin/stats' => 'admin/stats',
                'api/admin/delete-post' => 'admin/delete-post',
                'api/admin/delete-user' => 'admin/delete-user',
                'api/admin/delete-comment' => 'admin/delete-comment',
                'api/admin/block-user' => 'admin/block-user',
                'api/admin/unblock-user' => 'admin/unblock-user',
                'api/admin/clear-comment-report' => 'admin/clear-comment-report',
                
                // Block routes
                'block/list' => 'block/list',
                'api/block/list' => 'block/list',
                'api/block/block' => 'block/block',
                'api/block/unblock' => 'block/unblock',
                
                // Post routes
                'post/<id:\d+>' => 'post/view',
                'post/<id:\d+>/delete' => 'post/delete',
                'post/<id:\d+>/get-html' => 'post/get-html',
                'post/modal' => 'post/modal',
                
                // Search routes
                'search' => 'search/index',
                'api/search' => 'search/api',
                
                // Story routes
                'story' => 'story/index',
                'story/<id:\d+>' => 'story/view',
                'story/create' => 'story/create',
                'api/story/upload' => 'story/upload',
                'api/story/delete' => 'story/delete',
                'api/story/view' => 'story/view',
                'api/story/get-stories' => 'story/get-stories',
                'api/story/get' => 'story/get',
                
                // Auth routes
                'login' => 'site/login',
                'register' => 'site/register',
                
                // Sitemap
                'sitemap' => 'sitemap/index',
                'sitemap.xml' => 'sitemap/index',
            ],
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;