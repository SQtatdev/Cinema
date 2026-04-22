<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/mail.php';

$session_id = filter_input(INPUT_POST, 'session_id', FILTER_VALIDATE_INT);
$name       = trim($_POST['name'] ?? '');
$seatIdsRaw = trim($_POST['seat_ids'] ?? '');

if (!$session_id || !$name || !$seatIdsRaw) {
    http_response_code(400);
    die('There is not enough information to make a reservation');
}

$seatIds = array_values(array_unique(
    array_filter(array_map('intval', explode(',', $seatIdsRaw)), fn($id) => $id > 0)
));

if (count($seatIds) === 0 || count($seatIds) > 10) {
    http_response_code(400); die('Invalid number of seats selected.');
}

$stmt = $pdo->prepare("SELECT s.*, m.title FROM sessions s JOIN movies m ON s.movie_id = m.id WHERE s.id = ?");
$stmt->execute([$session_id]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$session) { http_response_code(404); die('Session not found.'); }

$user_id = null;
$email   = '';

if (isset($_SESSION['user'])) {
    $user_id = (int)$_SESSION['user']['id'];
    $email   = $_SESSION['user']['email'];
} else {
    $email       = trim($_POST['email'] ?? '');
    $bookingType = $_POST['booking_type'] ?? 'guest';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(400); die('Invalid email address.'); }
    if ($bookingType === 'register') {
        $password = $_POST['password'] ?? '';
        if (strlen($password) < 6) { http_response_code(400); die('Password must be at least 6 characters.'); }
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) { http_response_code(409); die('User already exists. <a href="login.php">Log in</a>.'); }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $insUser = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'user', NOW())");
        $insUser->execute([$name, $email, $hash]);
        $user_id = (int)$pdo->lastInsertId();
        $_SESSION['user'] = ['id' => $user_id, 'name' => $name, 'email' => $email];
    }
}

$lockedSeats = [];
$total       = 0.0;

try {
    $pdo->beginTransaction();
    $placeholders = implode(',', array_fill(0, count($seatIds), '?'));
    $params       = array_merge([$session_id], $seatIds);

    $lockStmt = $pdo->prepare("
        SELECT id, `type`, status, `row_number`, seat_number
        FROM seats WHERE session_id = ? AND id IN ($placeholders) FOR UPDATE
    ");
    $lockStmt->execute($params);
    $lockedSeats = $lockStmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($lockedSeats) !== count($seatIds)) { $pdo->rollBack(); die('One or more seats not found.'); }
    foreach ($lockedSeats as $ls) {
        if ($ls['status'] !== 'available') {
            $pdo->rollBack();
            die('One or more seats already taken. <a href="booking.php?session_id='.(int)$session_id.'">Choose different seats.</a>');
        }
    }

    $basePrice    = (float)$session['price'];
    $premiumPrice = round($basePrice * 1.20, 2);
    foreach ($lockedSeats as $ls) {
        $total += ($ls['type'] === 'premium') ? $premiumPrice : $basePrice;
    }

    $insBooking = $pdo->prepare("INSERT INTO bookings (user_id, session_id, seat_row, seat_number, name, email, total_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $insSeat    = $pdo->prepare("INSERT INTO booking_seats (booking_id, seat_id) VALUES (?, ?)");
    $updSeat    = $pdo->prepare("UPDATE seats SET status = 'booked', booking_id = ? WHERE id = ?");

    foreach ($lockedSeats as $ls) {
        $seatPrice = ($ls['type'] === 'premium') ? $premiumPrice : $basePrice;
        $insBooking->execute([$user_id, $session_id, $ls['row_number'], $ls['seat_number'], $name, $email, $seatPrice]);
        $booking_id = (int)$pdo->lastInsertId();
        $insSeat->execute([$booking_id, $ls['id']]);
        $updSeat->execute([$booking_id, $ls['id']]);
    }

    $pdo->commit();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Booking error: ' . $e->getMessage());
    http_response_code(500);
    die('Error: ' . $e->getMessage());
}

sendTicketEmail($email, $name, $session['title'], $session['movie_id'], $session['show_time'], count($seatIds), $total, $lockedSeats);

$seatLabels = array_map(
    fn($ls) => 'Row ' . $ls['row_number'] . ', Seat ' . $ls['seat_number'] . ($ls['type'] === 'premium' ? ' ★' : ''),
    $lockedSeats
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Booking Confirmed — MyCinema</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/styles/style_conf_bookings.css?v=3">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="confirmation-box">
        <div class="icon">🎟️</div>
        <h1>Booking Confirmed!</h1>
        <p class="subtitle">Thank you, <strong><?= htmlspecialchars($name) ?></strong></p>
        <p class="email-note">✉️ Tickets sent to <?= htmlspecialchars($email) ?></p>

        <div class="summary">
            <h5><?= htmlspecialchars($session['title']) ?></h5>
            <p>
                <strong>📅 Date & Time</strong>
                <span><?= date('d M Y, H:i', strtotime($session['show_time'])) ?></span>
            </p>
            <p>
                <strong>🎫 Tickets</strong>
                <span><?= count($seatLabels) ?> seat(s)</span>
            </p>
            <ul>
                <?php foreach ($seatLabels as $label): ?>
                    <li><?= htmlspecialchars($label) ?></li>
                <?php endforeach; ?>
            </ul>
            <div class="total-row">
                <span class="total-label">Total Paid</span>
                <span class="total-amount"><?= number_format($total, 2) ?> €</span>
            </div>
        </div>

        <a href="index.php" class="btn-orange">← Back to Movies</a>
    </div>
</div>

<footer>
    <p>© <?= date('Y') ?> MyCinema. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>