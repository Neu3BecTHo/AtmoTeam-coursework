<?php

return function() {
    $dsn = getenv('DB_DSN');
    $username = getenv('DB_USERNAME');
    $password = getenv('DB_PASSWORD');

    if ($dsn === false || $dsn === '') {
        throw new \yii\base\InvalidConfigException('DB_DSN environment variable is required');
    }
    if ($username === false || $username === '') {
        throw new \yii\base\InvalidConfigException('DB_USERNAME environment variable is required');
    }
    if ($password === false || $password === '') {
        throw new \yii\base\InvalidConfigException('DB_PASSWORD environment variable is required');
    }

    return [
        'class' => 'yii\db\Connection',
        'dsn' => $dsn,
        'username' => $username,
        'password' => $password,
        'charset' => 'utf8mb4',
    ];
};
