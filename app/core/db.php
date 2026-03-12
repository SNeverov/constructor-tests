<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = require __DIR__ . '/../../config/database.php';
    if (!is_array($cfg)) {
        throw new RuntimeException('Invalid database config format');
    }

    $host = (string)($cfg['host'] ?? '');
    $port = (string)($cfg['port'] ?? '');
    $name = (string)($cfg['name'] ?? '');
    $user = (string)($cfg['user'] ?? '');
    $pass = (string)($cfg['pass'] ?? '');
    $charset = (string)($cfg['charset'] ?? 'utf8mb4');

    if (trim($host) === '' || trim($port) === '' || trim($name) === '' || trim($user) === '') {
        throw new RuntimeException('Database config is incomplete');
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        throw new RuntimeException('DB connect failed', 0, $e);
    }

    return $pdo;
}
