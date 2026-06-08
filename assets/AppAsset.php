<?php

namespace app\assets;

use yii\web\AssetBundle;

/**
 * AppAsset - основные ассеты приложения
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/main.css',
    ];
    public $js = [];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}