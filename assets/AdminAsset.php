<?php

namespace app\assets;

use yii\web\AssetBundle;

/**
 * AdminAsset - ассеты для админ-панели
 */
class AdminAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/main.css',
        'css/admin.css',
    ];
    public $js = [
        'js/common.js',
        'js/admin.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}