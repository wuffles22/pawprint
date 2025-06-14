<?php
// Only set session settings and start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set session settings BEFORE session_start()
    ini_set('session.gc_maxlifetime', 86400 * 30); // 30 days
    ini_set('session.cookie_lifetime', 86400 * 30); // 30 days
    session_set_cookie_params(86400 * 30); // 30 days

    session_start();
}

$host = 'localhost';
$db   = 'pet_adoption';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
