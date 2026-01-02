<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/mail.php';

$session_id = $_POST['session_id'] ?? null;
$name = trim($_POST['name'] ?? '');
$seats = (int)($_POST['seats'] ?? 1);
$isPremium = isset($_POST['premium']);

if (!$name || !$session_id) {
    die('Missing booking information.');
}

/* ======================
   SESSION INFO
====================== */
$stmt = $pdo->prepare("
    SELECT s.*, m.title
    FROM sessions s
    JOIN movies m ON s.movie_id = m.id
    WHERE s.id = ?
");
$stmt->execute([$session_id]);
$session = $stmt->fetch();
if (!$session) die('Invalid session.');

$basePrice = $session['price'];
$finalPrice = $isPremium ? $basePrice * 1.2 : $basePrice;
$total = $finalPrice * $seats;

/* ======================
   USER LOGIC
====================== */
$user_id = null;

if (isset($_SESSION['user'])) {

    // 🔐 Logged in
    $user_id = $_SESSION['user']['id'];
    $email   = $_SESSION['user']['email'];

} else {

    // 👤 Guest or register
    $email = trim($_POST['email'] ?? '');
    $bookingType = $_POST['booking_type'] ?? 'guest';

    if (!$email) {
        die('Email is required.');
    }

    if ($bookingType === 'register') {

        // ➕ Create account
        $password = trim($_POST['password'] ?? '');
        if (strlen($password) < 6) die('Password too short');

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role, created_at)
            VALUES (?, ?, ?, 'user', NOW())
        ");
        $stmt->execute([$name, $email, $hash]);

        $user_id = $pdo->lastInsertId();

        $_SESSION['user'] = [
            'id' => $user_id,
            'name' => $name,
            'email' => $email
        ];
    }
}

/* ======================
   CREATE BOOKINGS
====================== */
for ($i = 0; $i < $seats; $i++) {
    $seat_row = rand(1, 10);
    $seat_number = rand(1, 20);
    $status = $isPremium ? 'premium' : 'booked';

    $stmt = $pdo->prepare("
        INSERT INTO bookings
        (user_id, session_id, seat_row, seat_number, status, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $user_id,
        $session_id,
        $seat_row,
        $seat_number,
        $status
    ]);
}

/* ======================
   EMAIL (ALWAYS)
====================== */
sendTicketEmail(
    $email,
    $session['title'],
    $session['show_time'],
    $seats,
    $total
);
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
    <h1 class="text-warning">Booking Confirmed 🎉</h1>
    <p>Thank you, <strong><?= htmlspecialchars($name) ?></strong></p>
    <p><?= $seats ?> ticket(s)</p>
    <p>Total: <strong><?= number_format($total, 2) ?> €</strong></p>
    <p class="text-success">Ticket sent to <?= htmlspecialchars($email) ?></p>

    <a href="index.php" class="btn btn-outline-light mt-4">Home</a>
</div>

</body>
</html>
