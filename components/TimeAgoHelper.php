<?php

namespace app\components;

use Yii;

class TimeAgoHelper
{
    public static function format(int $timestamp, bool $includeDays = true): string
    {
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return Yii::t('app', 'только что');
        }
        if ($diff < 3600) {
            return floor($diff / 60) . ' ' . Yii::t('app', 'мин. назад');
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . ' ' . Yii::t('app', 'ч. назад');
        }
        if ($includeDays && $diff < 2592000) {
            return floor($diff / 86400) . ' ' . Yii::t('app', 'дн. назад');
        }

        return date('d.m.Y', $timestamp);
    }
}
