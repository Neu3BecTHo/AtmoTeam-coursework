<?php
/**
 * Сброс кэша для ISPManager / shared hosting
 * Открыть в браузере: /clear-cache.php
 */

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

$results = [];

if (function_exists('opcache_reset')) {
    opcache_reset();
    $results[] = 'OPcache: сброшен';
} else {
    $results[] = 'OPcache: недоступен';
}

if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    $results[] = 'APCu: сброшен';
}

$runtimeCache = __DIR__ . '/../runtime/cache';
if (is_dir($runtimeCache)) {
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($runtimeCache, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    $count = 0;
    foreach ($iter as $file) {
        $path = $file->getRealPath();
        if ($file->isDir()) {
            @rmdir($path);
        } else {
            @unlink($path);
            $count++;
        }
    }
    $results[] = "Yii2 runtime/cache: удалено {$count} файлов";
} else {
    $results[] = 'Yii2 runtime/cache: директория не найдена';
}

$debugDir = __DIR__ . '/../runtime/debug';
if (is_dir($debugDir)) {
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($debugDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    $count = 0;
    foreach ($iter as $file) {
        $path = $file->getRealPath();
        if ($file->isDir()) {
            @rmdir($path);
        } else {
            @unlink($path);
            $count++;
        }
    }
    $results[] = "Yii2 runtime/debug: удалено {$count} файлов";
}

$assetsDir = __DIR__ . '/assets';
if (is_dir($assetsDir)) {
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($assetsDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    $count = 0;
    foreach ($iter as $file) {
        $path = $file->getRealPath();
        if ($file->isDir()) {
            @rmdir($path);
        } else {
            @unlink($path);
            $count++;
        }
    }
    $results[] = "Web assets: удалено {$count} файлов";
}

header('Content-Type: text/plain; charset=utf-8');
echo "=== Сброс кэша ===\n\n";
echo implode("\n", $results) . "\n\n";
echo "=== Готово ===\n";
echo "Время: " . date('Y-m-d H:i:s') . "\n";
