<?php
/**
 * FlowFetch Database Migration Runner
 */

require_once __DIR__ . '/../config/database.php';

echo "=== FlowFetch Database Migration ===\n";

try {
    $dbConfig = require __DIR__ . '/../config/database.php';
    
    // Connect to MySQL server first to ensure database exists
    $dsnNoDb = sprintf("mysql:host=%s;port=%s;charset=utf8mb4", $dbConfig['host'], $dbConfig['port']);
    $pdoRoot = new PDO($dsnNoDb, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $dbName = $dbConfig['database'];
    $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "[✓] Database `{$dbName}` verified/created.\n";
    
    // Now connect to the database
    $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", $dbConfig['host'], $dbConfig['port'], $dbName);
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // 1. Run Schema
    echo "[*] Executing schema.sql...\n";
    $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($schemaSql);
    echo "[✓] 14 Database tables created successfully.\n";
    
    // 2. Run Seed
    echo "[*] Executing seed.sql...\n";
    $seedSql = file_get_contents(__DIR__ . '/seed.sql');
    $pdo->exec($seedSql);
    echo "[✓] Initial seed data inserted successfully.\n";
    
    echo "=== Migration Complete! ===\n";
    echo "Default Admin: admin@download1.local (Password: Admin@123456)\n";
    echo "Default User:  user@download1.local  (Password: User@123456)\n";

} catch (PDOException $e) {
    echo "[!] Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}
