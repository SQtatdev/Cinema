<?php
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request.');
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$seats = (int)($_POST['seats'] ?? 0);
$session_id = (int)($_POST['session_id'] ?? 0);

if (!$name || !$email || !$session_id || $seats < 1) {
    die('Please fill out all fields.');
}

// Получаем данные о сеансе и фильме
global $pdo;
$stmt = $pdo->prepare("SELECT s.*, m.title, h.name AS hall_name 
                       FROM sessions s
                       JOIN movies m ON s.movie_id = m.id
                       JOIN halls h ON s.hall_id = h.id
                       WHERE s.id = ?");
$stmt->execute([$session_id]);
$session = $stmt->fetch();

if (!$session) {
    die('Invalid session.');
}

// Общая сумма
$total_price = $session['price'] * $seats;

// Сохраняем бронь в БД
$stmt = $pdo->prepare("INSERT INTO bookings (session_id, name, email, seats, total_price, booking_time) 
                       VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->execute([$session_id, $name, $email, $seats, $total_price]);
$booking_id = $pdo->lastInsertId();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Confirmation</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles/style_conf_bookings.css">

</head>
<body>

<div class="container">
    <div class="confirmation-box">
        <h1>✅ Booking Confirmed!</h1>
        <p>Thank you, <strong><?= htmlspecialchars($name) ?></strong>.<br>
           Your booking has been successfully saved.</p>

        <div class="summary">
            <p><strong>Booking ID:</strong> #<?= $booking_id ?></p>
            <p><strong>Movie:</strong> <?= htmlspecialchars($session['title']) ?></p>
            <p><strong>Date & Time:</strong> <?= date('d M Y, H:i', strtotime($session['show_time'])) ?></p>
            <p><strong>Hall:</strong> <?= htmlspecialchars($session['hall_name']) ?></p>
            <p><strong>Tickets:</strong> <?= $seats ?></p>
            <p><strong>Total price:</strong> <?= number_format($total_price, 2) ?> ₽</p>
        </div>

        <a href="index.php" class="btn btn-orange mt-4">Back to Movies</a>
    </div>
</div>

<footer>
    <p>© <?= date('Y') ?> MyCinema. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
