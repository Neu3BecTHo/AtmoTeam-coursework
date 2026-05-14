<?php

namespace app\components;

use Yii;
use yii\base\Component;

class FileCache extends Component
{
    public $cachePath;
    public $defaultDuration = 3600; // 1 час
    
    public function init()
    {
        parent::init();
        $this->cachePath = Yii::getAlias('@runtime/cache');
        
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }
    }
    
    /**
     * Получить значение из кеша по ключу
     * @param string $key
     * @return mixed|false
     */
    public function get($key)
    {
        $filename = $this->getFilename($key);
        
        if (!file_exists($filename)) {
            return false;
        }
        
        $data = unserialize(file_get_contents($filename));

        if ($data['expires'] > 0 && $data['expires'] < time()) {
            $this->delete($key);
            return false;
        }
        
        return $data['value'];
    }
    
    /**
     * Сохранить значение в кеш
     * @param string $key
     * @param mixed $value
     * @param int|null $duration
     * @return bool
     */
    public function set($key, $value, $duration = null)
    {
        $duration = $duration ?? $this->defaultDuration;
        $expires = $duration > 0 ? time() + $duration : 0;
        
        $data = [
            'value' => $value,
            'expires' => $expires,
            'created' => time()
        ];
        
        $filename = $this->getFilename($key);
        return file_put_contents($filename, serialize($data)) !== false;
    }
    
    /**
     * Удалить значение из кеша
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        $filename = $this->getFilename($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return true;
    }
    
    /**
     * Очистить весь кеш
     * @return int количество удаленных файлов
     */
    public function flush()
    {
        $files = glob($this->cachePath . '/*.cache');
        $deleted = 0;
        
        if ($files) {
            foreach ($files as $file) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Получить имя файла для ключа
     * @param string $key
     * @return string
     */
    private function getFilename($key)
    {
        return $this->cachePath . '/' . md5($key) . '.cache';
    }
    
    /**
     * Получить значение из кеша или сохранить через callback
     * @param string $key
     * @param callable $callback
     * @param int|null $duration
     * @return mixed
     */
    public function getOrSet($key, $callback, $duration = null)
    {
        $value = $this->get($key);
        
        if ($value === false) {
            $value = call_user_func($callback);
            $this->set($key, $value, $duration);
        }
        
        return $value;
    }
    
    /**
     * Проверить существование ключа в кеше
     * @param string $key
     * @return bool
     */
    public function exists($key)
    {
        return $this->get($key) !== false;
    }
    
    /**
     * Получить метаданные кеша
     * @param string $key
     * @return array|null
     */
    public function getMetadata($key)
    {
        $filename = $this->getFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($filename));
        
        return [
            'created' => $data['created'] ?? null,
            'expires' => $data['expires'] ?? null,
            'size' => filesize($filename),
            'file' => $filename
        ];
    }
    
    /**
     * Сборка мусора - удаление просроченных кешей
     * @return int количество удаленных файлов
     */
    public function gc()
    {
        $files = glob($this->cachePath . '/*.cache');
        $now = time();
        $deleted = 0;
        
        if ($files) {
            foreach ($files as $file) {
                $data = unserialize(file_get_contents($file));
                
                if (isset($data['expires']) && $data['expires'] > 0 && $data['expires'] < $now) {
                    if (unlink($file)) {
                        $deleted++;
                    }
                }
            }
        }
        
        return $deleted;
    }
}