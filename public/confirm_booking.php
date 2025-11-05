<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Получаем данные из формы
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$session_id = $_POST['session_id'] ?? null;
$seats = (int)($_POST['seats'] ?? 1);
$isPremium = isset($_POST['premium']) ? 1 : 0;

// Проверка на обязательные поля
if (!$name || !$email || !$session_id) {
    die('Missing booking information.');
}

// Проверяем, есть ли пользователь
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user && !isset($_POST['password'])) {
    // Если пользователя нет — предлагаем создать пароль
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Create Account</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-dark text-white">
    <div class="container mt-5">
        <h2>Create an account</h2>
        <p>We couldn’t find your email in our system. Please set a password to continue your booking.</p>
        <form method="POST">
            <input type="hidden" name="name" value="<?= htmlspecialchars($name) ?>">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
            <input type="hidden" name="session_id" value="<?= htmlspecialchars($session_id) ?>">
            <input type="hidden" name="seats" value="<?= htmlspecialchars($seats) ?>">
            <input type="hidden" name="premium" value="<?= htmlspecialchars($isPremium) ?>">

            <div class="mb-3">
                <label for="password" class="form-label">Create password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-warning">Continue Booking</button>
        </form>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// Если пользователь не существует, но пароль уже введён — создаём
if (!$user && isset($_POST['password'])) {
    $hashedPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'user', NOW())");
    $stmt->execute([$name, $email, $hashedPassword]);

    $user_id = $pdo->lastInsertId();
} else {
    $user_id = $user['id'];
}

// Получаем цену сеанса
$stmt = $pdo->prepare("SELECT price FROM sessions WHERE id = ?");
$stmt->execute([$session_id]);
$session = $stmt->fetch();

if (!$session) die('Invalid session ID.');

$basePrice = $session['price'];
$finalPrice = $isPremium ? $basePrice * 1.2 : $basePrice;

// Создаём бронирование
for ($i = 0; $i < $seats; $i++) {
    $seat_row = rand(1, 10);
    $seat_number = rand(1, 20);
    $status = $isPremium ? 'premium' : 'booked';

    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, session_id, seat_row, seat_number, status, created_at)
                           VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$user_id, $session_id, $seat_row, $seat_number, $status, ]);
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
