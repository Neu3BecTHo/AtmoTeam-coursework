<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\User;
use app\models\Post;

class SitemapController extends Controller
{
    public function actionIndex()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/xml');

        $baseUrl = Yii::$app->request->hostInfo;
        $today = date('Y-m-d');

        $staticUrls = [
            ['loc' => '/feed', 'changefreq' => 'hourly', 'priority' => '1.0'],
            ['loc' => '/register', 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => '/login', 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => '/search', 'changefreq' => 'hourly', 'priority' => '0.7'],
            ['loc' => '/post', 'changefreq' => 'daily', 'priority' => '0.9'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

        foreach ($staticUrls as $url) {
            $xml .= $this->renderUrl($baseUrl . $url['loc'], $today, $url['changefreq'], $url['priority']);
        }

        $users = User::find()
            ->select(['id', 'username', 'created_at'])
            ->where(['is_private' => 0, 'is_blocked' => 0, 'status' => 10])
            ->all();
        foreach ($users as $user) {
            $xml .= $this->renderUrl(
                $baseUrl . '/profile/' . $user->id,
                date('Y-m-d', $user->created_at),
                'weekly',
                '0.8'
            );
        }

        $posts = Post::find()
            ->where(['is_private' => 0])
            ->all();
        foreach ($posts as $post) {
            $xml .= $this->renderUrl(
                $baseUrl . '/post/' . $post->id,
                date('Y-m-d', $post->created_at),
                'daily',
                '0.7'
            );
        }

        $xml .= '</urlset>';
        echo $xml;
    }

    private function renderUrl($loc, $lastmod, $changefreq, $priority)
    {
        return <<<XML
    <url>
        <loc>$loc</loc>
        <lastmod>$lastmod</lastmod>
        <changefreq>$changefreq</changefreq>
        <priority>$priority</priority>
    </url>
XML;
    }
}
