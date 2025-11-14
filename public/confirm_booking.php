<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

$session_id = $_POST['session_id'] ?? null;
$name = trim($_POST['name'] ?? '');
$seats = (int)($_POST['seats'] ?? 1);
$isPremium = isset($_POST['premium']) ? 1 : 0;

if (!$name || !$session_id) {
    die('Missing booking information.');
}

// Получаем цену сеанса
$stmt = $pdo->prepare("SELECT price FROM sessions WHERE id = ?");
$stmt->execute([$session_id]);
$session = $stmt->fetch();
if (!$session) die('Invalid session ID.');

$basePrice = $session['price'];
$finalPrice = $isPremium ? $basePrice * 1.2 : $basePrice;

// Определяем пользователя
if (isset($_SESSION['user'])) {
    // Пользователь залогинен
    $user_id = $_SESSION['user']['id'];
} else {
    // Гость → проверяем email
    $email = trim($_POST['email'] ?? '');
    if (!$email) die('Email is required for guests.');

    // Смотрим, есть ли такой пользователь
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Создаём нового пользователя с ролью user
        $password = password_hash(bin2hex(random_bytes(4)), PASSWORD_BCRYPT); // временный пароль
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'user', NOW())");
        $stmt->execute([$name, $email, $password]);
        $user_id = $pdo->lastInsertId();
    } else {
        $user_id = $user['id'];
    }
}

// Создаём бронирование
for ($i = 0; $i < $seats; $i++) {
    $seat_row = rand(1, 10);      // случайная строка
    $seat_number = rand(1, 20);   // случайное место
    $status = $isPremium ? 'premium' : 'booked';

    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, session_id, seat_row, seat_number, status, created_at)
                           VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$user_id, $session_id, $seat_row, $seat_number, $status]);
    exportMySQLToSql(); // если используешь экспорт в файл SQL
}

$total = $finalPrice * $seats;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Booking Confirmed</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">
<div class="container mt-5 text-center">
    <h1 class="text-warning mb-4">Booking Confirmed 🎉</h1>
    <p>Thank you, <strong><?= htmlspecialchars($name) ?></strong>!</p>
    <p>You have booked <strong><?= $seats ?></strong> <?= $isPremium ? 'Premium ' : '' ?>seat(s).</p>
    <p>Total price: <strong><?= number_format($total, 2) ?> €</strong></p>

    <a href="index.php" class="btn btn-outline-light mt-4">Return to Home</a>
</div>
</body>
</html>
