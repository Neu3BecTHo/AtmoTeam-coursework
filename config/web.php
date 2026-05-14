<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'language' => 'ru_RU',
    'components' => [
        'request' => [

            'cookieValidationKey' => 'NA8T9KvhpGCoaKIaGEA8RKklqZMss0H_',
            'baseUrl' => '',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        'timeZone' => 'Europe/Moscow',
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
        'db' => $db,
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
            'defaultRoles' => ['guest'],
            'itemTable' => '{{%auth_item}}',
            'itemChildTable' => '{{%auth_item_child}}',
            'assignmentTable' => '{{%auth_assignment}}',
            'ruleTable' => '{{%auth_rule}}',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '/' => 'feed/index',
                'feed' => 'feed/index',
                'api/posts' => 'feed/get-posts',
                'api/post/create' => 'feed/create-post',
                'api/post/like' => 'feed/like',
                'api/comments/<post_id:\d+>' => 'feed/get-comments',
                'api/comment/create' => 'feed/comment',
                'api/comment/update' => 'feed/update-comment',
                'api/comment/delete' => 'feed/delete-comment',
                'api/repost' => 'feed/repost',
                'api/vote' => 'feed/vote',
                'api/poll' => 'feed/poll',
                'api/notifications' => 'feed/get-notifications',
                'api/notifications/read' => 'feed/mark-notifications-read',
                'api/notifications/read-all' => 'feed/mark-notifications-read',
                'api/post/save' => 'feed/save-post',
                'api/saved-posts' => 'feed/saved-posts',
                'login' => 'site/login',
                'register' => 'site/register',

                'profile' => 'profile/view',
                'profile/<id:\d+>' => 'profile/view',
                'profile/edit' => 'profile/edit',
                'profile/<id:\d+>/posts' => 'profile/posts',
                'profile/<id:\d+>/follow' => 'profile/follow',
                'profile/<id:\d+>/unfollow' => 'profile/unfollow',
                'profile/<id:\d+>/followers' => 'profile/followers',
                'profile/<id:\d+>/following' => 'profile/following',
                'api/profile/block' => 'profile/block',
                'api/profile/unblock' => 'profile/unblock',

                'admin' => 'admin/index',
                'api/admin/stats' => 'admin/stats',
                'api/admin/delete-post' => 'admin/delete-post',
                'api/admin/delete-user' => 'admin/delete-user',
                'api/admin/delete-comment' => 'admin/delete-comment',
                'api/admin/block-user' => 'admin/block-user',
                'api/admin/unblock-user' => 'admin/unblock-user',
                'api/admin/clear-comment-report' => 'admin/clear-comment-report',

                'block/list' => 'block/list',
                'api/block/list' => 'block/list',
                'api/block/block' => 'block/block',
                'api/block/unblock' => 'block/unblock',

                'post/<id:\d+>' => 'post/view',
                'post/<id:\d+>/delete' => 'post/delete',
                'post/<id:\d+>/get-html' => 'post/get-html',

                'search' => 'search/index',
                'api/search' => 'search/api',

                'story' => 'story/index',
                'story/<id:\d+>' => 'story/view',
                'story/create' => 'story/create',
                'api/story/upload' => 'story/upload',
                'api/story/delete' => 'story/delete',
                'api/story/view' => 'story/view',
                'api/story/get-stories' => 'story/get-stories',

                'api/message/unread-count' => 'message/unread-count',
                'api/message/send' => 'message/send',
                'api/message/mark-read' => 'message/mark-read',
                'api/message/get-dialogue/<id:\d+>' => 'message/get-dialogue',
                'api/message/get-dialogues' => 'message/get-dialogues',
                'message' => 'message/index',
                'message/dialogue/<id:\d+>' => 'message/dialogue',
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
