<?php
date_default_timezone_set('Asia/Kolkata');

$host = 'localhost';
$db = 'ngomain';
$user = 'root';
$pass = '';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'"
];

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET time_zone = '+05:30'");
} catch (\PDOException $e) {
    // Automatic fallback for local development environment
    try {
        $localDsn = "mysql:host=localhost;dbname=jayfoundation;charset=utf8mb4";
        $pdo = new PDO($localDsn, 'root', '', $options);
        $pdo->exec("SET time_zone = '+05:30'");
    } catch (\PDOException $fallbackError) {
        die("Database Connection Error: " . $fallbackError->getMessage());
    }
}
?>