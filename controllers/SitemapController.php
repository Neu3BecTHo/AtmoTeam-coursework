<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\User;
use app\models\Post;

class SitemapController extends Controller
{
    public $enableCsrfValidation = false;
    
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
            ['loc' => '/message', 'changefreq' => 'hourly', 'priority' => '0.7'],
            ['loc' => '/story', 'changefreq' => 'hourly', 'priority' => '0.6'],
        ];
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
        
        foreach ($staticUrls as $url) {
            $xml .= $this->renderUrl($baseUrl . $url['loc'], $today, $url['changefreq'], $url['priority']);
        }
        
        $users = User::find()->select(['id', 'username', 'created_at'])->all();
        foreach ($users as $user) {
            $xml .= $this->renderUrl(
                $baseUrl . '/profile/' . $user->id,
                date('Y-m-d', $user->created_at),
                'weekly',
                '0.6',
                'Профиль пользователя ' . htmlspecialchars($user->username)
            );
        }
        
        $posts = Post::find()->select(['id', 'created_at'])->limit(100)->all();
        foreach ($posts as $post) {
            $xml .= $this->renderUrl(
                $baseUrl . '/post/view?id=' . $post->id,
                date('Y-m-d', $post->created_at),
                'monthly',
                '0.5',
                'Пост #' . $post->id
            );
        }
        
        $xml .= '
</urlset>';
        
        return $xml;
    }
    
    private function renderUrl($loc, $lastmod, $changefreq, $priority, $comment = null)
    {
        $xml = '';
        if ($comment) {
            $xml .= "\n    <!-- {$comment} -->";
        }
        $xml .= "\n    <url>
        <loc>{$loc}</loc>
        <lastmod>{$lastmod}</lastmod>
        <changefreq>{$changefreq}</changefreq>
        <priority>{$priority}</priority>
    </url>";
        
        return $xml;
    }
}