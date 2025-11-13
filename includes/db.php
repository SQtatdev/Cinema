<?php
$host = 'localhost';
$user = 'root';
$pass = 'root'; // В Laragon по умолчанию пароль пустой!
$dbname = 'cinema_db';
$charset = 'utf8mb4';

$sqlFile = __DIR__ . '/cinema_db.sql';

try {
    // Подключаемся к серверу MySQL без указания базы
    $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Создаём базу, если её нет
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET $charset COLLATE ${charset}_general_ci");
    $pdo->exec("USE `$dbname`");

    // Импортируем SQL, если база пуста
    $stmt = $pdo->query("SHOW TABLES");
    if ($stmt->rowCount() === 0) {
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $pdo->exec($sql);
            echo "✅ База данных импортирована из '$sqlFile'.<br>";
        } else {
            echo "⚠️ Файл '$sqlFile' не найден.<br>";
        }
    } else {
        echo "ℹ️ База '$dbname' уже содержит таблицы — импорт не требуется.<br>";
    }

    echo "✅ Подключение к базе '$dbname' установлено.";
} catch (PDOException $e) {
    die("❌ Ошибка: " . $e->getMessage());
}
?>
