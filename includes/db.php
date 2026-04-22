<?php
$host = 'sql311.infinityfree.com';
$dbname = 'if0_41700966_cinema_db';
$user = 'if0_41700966';
$pass = 'tb45wuWfwM';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Fucking db password mysql, root or none: " . $e->getMessage());
}
