<?php
namespace app\components;

use yii\base\Component;
use yii\base\BootstrapInterface;
use Yii;

class LanguageComponent extends Component implements BootstrapInterface
{
    public function bootstrap($app)
    {
        $lang = $_COOKIE['language'] ?? null;
        if (!$lang && !$app->session->isActive) {
            $app->session->open();
        }
        if (!$lang && $app->session->has('language')) {
            $lang = $app->session->get('language');
        }
        if ($lang) {
            $lang = str_replace('_', '-', $lang);
            // ← ДОБАВЬ 'es-ES' в разрешённые языки
            if (in_array($lang, ['en-US', 'ru-RU', 'es-ES'], true)) {
                $app->language = $lang;
            }
        }
    }
}