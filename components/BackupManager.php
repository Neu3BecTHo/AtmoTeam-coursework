<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\db\Connection;


class BackupManager extends Component
{
    
    public static function createBackup($description = '')
    {
        $backupDir = Yii::getAlias('@runtime/backups');
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backupDir . '/' . $filename;
        
        $db = Yii::$app->db;
        
        try {

            $tables = $db->schema->getTableNames();
            
            $sql = "-- Database Backup\n";
            $sql .= "-- Created: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Description: " . $description . "\n";
            $sql .= "-- Tables: " . implode(', ', $tables) . "\n\n";
            
            foreach ($tables as $table) {
                $sql .= self::dumpTable($db, $table);
            }

            if (function_exists('gzencode')) {
                $compressed = gzencode($sql, 9);
                file_put_contents($filepath . '.gz', $compressed);
                $filepath .= '.gz';
            } else {
                file_put_contents($filepath, $sql);
            }

            ActivityLog::log(
                ActivityLog::ACTION_CREATE,
                "Создан бэкап: $filename",
                'backup',
                null
            );
            
            return [
                'success' => true,
                'filename' => basename($filepath),
                'size' => filesize($filepath),
                'path' => $filepath
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    
    private static function dumpTable($db, $table)
    {
        $sql = "-- Table: $table\n";

        $createTable = $db->createCommand("SHOW CREATE TABLE `$table`")->queryOne();
        $sql .= $createTable['Create Table'] . ";\n\n";

        $rows = $db->createCommand("SELECT * FROM `$table`")->queryAll();
        
        if (!empty($rows)) {
            $sql .= "-- Data for table: $table\n";
            
            foreach ($rows as $row) {
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = $db->quoteValue($value);
                    }
                }
                $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }
        
        return $sql;
    }
    
    
    public static function getBackupList()
    {
        $backupDir = Yii::getAlias('@runtime/backups');
        $backups = [];
        
        if (is_dir($backupDir)) {
            $files = scandir($backupDir);
            
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && preg_match('/^backup_.*\.sql(\.gz)?$/', $file)) {
                    $filepath = $backupDir . '/' . $file;
                    $backups[] = [
                        'filename' => $file,
                        'size' => filesize($filepath),
                        'created' => filemtime($filepath),
                        'path' => $filepath
                    ];
                }
            }

            usort($backups, function($a, $b) {
                return $b['created'] - $a['created'];
            });
        }
        
        return $backups;
    }
    
    
    public static function deleteBackup($filename)
    {
        $backupDir = Yii::getAlias('@runtime/backups');
        $filepath = $backupDir . '/' . $filename;
        
        if (file_exists($filepath) && is_file($filepath)) {
            if (unlink($filepath)) {
                ActivityLog::log(
                    ActivityLog::ACTION_DELETE,
                    "Удален бэкап: $filename",
                    'backup',
                    null
                );
                return true;
            }
        }
        
        return false;
    }
    
    
    public static function downloadBackup($filename)
    {
        $backupDir = Yii::getAlias('@runtime/backups');
        $filepath = $backupDir . '/' . $filename;
        
        if (file_exists($filepath) && is_file($filepath)) {

            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            
            readfile($filepath);
            exit;
        }
        
        return false;
    }
    
    
    public static function restoreBackup($filename)
    {
        $backupDir = Yii::getAlias('@runtime/backups');
        $filepath = $backupDir . '/' . $filename;
        
        if (!file_exists($filepath) || !is_file($filepath)) {
            return ['success' => false, 'error' => 'Файл бэкапа не найден'];
        }
        
        try {
            $db = Yii::$app->db;

            if (preg_match('/\.gz$/', $filename)) {
                $content = gzdecode(file_get_contents($filepath));
            } else {
                $content = file_get_contents($filepath);
            }
            
            if (!$content) {
                return ['success' => false, 'error' => 'Ошибка чтения файла бэкапа'];
            }

            $statements = array_filter(array_map('trim', explode(";\n", $content)));

            $transaction = $db->beginTransaction();
            
            try {
                foreach ($statements as $statement) {
                    if (!empty($statement) && !preg_match('/^--/', $statement)) {
                        $db->createCommand($statement)->execute();
                    }
                }
                
                $transaction->commit();
                
                ActivityLog::log(
                    ActivityLog::ACTION_UPDATE,
                    "Восстановлен бэкап: $filename",
                    'backup',
                    null
                );
                
                return ['success' => true, 'message' => 'Бэкап успешно восстановлен'];
                
            } catch (\Exception $e) {
                $transaction->rollBack();
                return ['success' => false, 'error' => 'Ошибка восстановления: ' . $e->getMessage()];
            }
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    
    public static function autoBackup()
    {
        $backupDir = Yii::getAlias('@runtime/backups');

        if (is_dir($backupDir)) {
            $files = glob($backupDir . '/backup_*.sql*');
            $cutoff = time() - (7 * 24 * 60 * 60); // 7 days ago
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff) {
                    unlink($file);
                }
            }
        }

        return self::createBackup('Автоматический бэкап');
    }
}
