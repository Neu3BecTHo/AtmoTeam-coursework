<?php



namespace app\assets;

use yii\web\AssetBundle;


class AdminAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/main.css',
        'css/dark.css',
        'css/components.css',
        'css/admin-base.css',
        'css/admin.css',
    ];
    public $js = [
        'js/common.js',
        'js/admin.js',
    ];
    public $depends = [
        'yii\web\YiiAsset'
    ];
}
