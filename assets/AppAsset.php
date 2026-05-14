<?php



namespace app\assets;

use yii\web\AssetBundle;


class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/main.css',
        'css/dark.css',
        'css/components.css',
    ];
    public $js = [
    ];
    public $depends = [
        'yii\web\YiiAsset'
    ];
}
