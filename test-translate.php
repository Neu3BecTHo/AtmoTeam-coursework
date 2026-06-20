<?php
// Загружаем Yii
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';
$app = new yii\web\Application($config);

// Получаем текущий язык
echo "=== ТЕСТ ПЕРЕВОДОВ ===\n";
echo "Current language: " . Yii::$app->language . "\n";
echo "Source language: " . Yii::$app->sourceLanguage . "\n";
echo "Cookie language: " . ($_COOKIE['language'] ?? 'not set') . "\n\n";

// Проверяем, что файл перевода существует
$messagePath = Yii::getAlias('@app/messages');
$langDir = $messagePath . '/' . Yii::$app->language;
$filePath = $langDir . '/app.php';

echo "Messages path: $messagePath\n";
echo "Language dir: $langDir\n";
echo "File exists: " . (file_exists($filePath) ? 'YES' : 'NO') . "\n";

if (file_exists($filePath)) {
    $messages = require $filePath;
    echo "Messages in file: " . count($messages) . "\n";
    echo "First keys: " . implode(', ', array_slice(array_keys($messages), 0, 3)) . "\n\n";
} else {
    echo "FILE NOT FOUND!\n\n";
}

// ТЕСТ: Проверка перевода через Yii::t()
echo "=== ПРОВЕРКА ПЕРЕВОДОВ ===\n";
$testPhrases = [
    'Лента новостей',
    'Войти',
    'Меню',
    'AtmoTeam - современная социальная сеть для общения, обмена фото и видео, создания постов и историй'
];

foreach ($testPhrases as $phrase) {
    $translated = Yii::t('app', $phrase);
    echo "Phrase: '$phrase'\n";
    echo "Translated: '$translated'\n";
    echo "Result: " . ($phrase === $translated ? '❌ NOT TRANSLATED' : '✅ TRANSLATED') . "\n\n";
}

// Проверяем, что i18n загружен
echo "=== ИНФОРМАЦИЯ О КОМПОНЕНТЕ ===\n";
$i18n = Yii::$app->i18n;
echo "I18n class: " . get_class($i18n) . "\n";

$translations = $i18n->translations;
echo "Translations config: " . print_r(array_keys($translations), true) . "\n";

if (isset($translations['app'])) {
    $source = $translations['app'];
    echo "Source class: " . get_class($source) . "\n";
    echo "BasePath: " . $source->basePath . "\n";
    echo "SourceLanguage: " . $source->sourceLanguage . "\n";
    echo "FileMap: " . print_r($source->fileMap, true) . "\n";
}

// Проверка через рефлексию (альтернативный способ получить переводы)
echo "\n=== ПРОВЕРКА ЧЕРЕЗ РЕФЛЕКСИЮ ===\n";
if (isset($translations['app'])) {
    try {
        $reflection = new \ReflectionClass($translations['app']);
        $method = $reflection->getMethod('loadMessages');
        $method->setAccessible(true);

        $messages = $method->invokeArgs($translations['app'], [Yii::$app->language, 'app']);
        echo "Messages loaded via reflection: " . count($messages) . "\n";
        echo "Test 'Лента новостей': " . ($messages['Лента новостей'] ?? 'NOT FOUND') . "\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}