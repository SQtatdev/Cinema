<?php
$host = 'localhost';
$dbname = 'cinema_db';
$user = 'root';
$pass = ''; // Laragon по умолчанию пустой пароль
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("❌ Ошибка подключения к базе: " . $e->getMessage());
}
