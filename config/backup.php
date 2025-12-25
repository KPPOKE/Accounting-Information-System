<?php
require_once __DIR__ . '/database.php';

define('BACKUP_PATH', __DIR__ . '/../backups');

function ensureBackupDirectory() {
    if (!is_dir(BACKUP_PATH)) {
        mkdir(BACKUP_PATH, 0755, true);
    }
    
    $htaccess = BACKUP_PATH . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Order deny,allow\nDeny from all\n");
    }
}

function createDatabaseBackup() {
    ensureBackupDirectory();
    
    $pdo = getDBConnection();
    $timestamp = date('Y-m-d_His');
    $filename = "backup_" . DB_NAME . "_" . $timestamp . ".sql";
    $filepath = BACKUP_PATH . '/' . $filename;
    
    $tables = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    $sql = "-- Database Backup\n";
    $sql .= "-- Database: " . DB_NAME . "\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- ----------------------------------------\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch();
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $row['Create Table'] . ";\n\n";
        
        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll();
        
        if (count($rows) > 0) {
            $columns = array_keys($rows[0]);
            $columnList = '`' . implode('`, `', $columns) . '`';
            
            foreach ($rows as $row) {
                $values = array_map(function($val) use ($pdo) {
                    if ($val === null) return 'NULL';
                    return $pdo->quote($val);
                }, array_values($row));
                
                $sql .= "INSERT INTO `$table` ($columnList) VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }
    }
    
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    if (file_put_contents($filepath, $sql)) {
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => filesize($filepath),
            'tables' => count($tables)
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to write backup file'];
}

function getBackupList() {
    ensureBackupDirectory();
    
    $backups = [];
    $files = glob(BACKUP_PATH . '/backup_*.sql');
    
    foreach ($files as $file) {
        $backups[] = [
            'filename' => basename($file),
            'filepath' => $file,
            'size' => filesize($file),
            'created' => filemtime($file)
        ];
    }
    
    usort($backups, function($a, $b) {
        return $b['created'] - $a['created'];
    });
    
    return $backups;
}

function deleteBackup($filename) {
    $filepath = BACKUP_PATH . '/' . basename($filename);
    
    if (file_exists($filepath) && strpos($filename, 'backup_') === 0) {
        return unlink($filepath);
    }
    
    return false;
}

function downloadBackup($filename) {
    $filepath = BACKUP_PATH . '/' . basename($filename);
    
    if (file_exists($filepath) && strpos($filename, 'backup_') === 0) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
    
    return false;
}

function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' bytes';
}
