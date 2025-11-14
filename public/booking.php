<?php
session_start();
require_once '../includes/functions.php';
require_once '../includes/db.php';

$session_id = $_GET['session_id'] ?? null;
if (!$session_id) die('Session not found.');

$stmt = $pdo->prepare("SELECT s.*, m.title, h.name AS hall_name 
                       FROM sessions s
                       JOIN movies m ON s.movie_id = m.id
                       JOIN halls h ON s.hall_id = h.id
                       WHERE s.id = ?");
$stmt->execute([$session_id]);
$session = $stmt->fetch();

if (!$session) die('Invalid session ID.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Booking - <?= htmlspecialchars($session['title']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/styles/style_booking.css">
</head>
<body>
<div class="container booking-container mt-5">
    <a href="movie.php?id=<?= $session['movie_id'] ?>" class="btn btn-sm btn-outline-light mb-4">← Back</a>

    <h1 class="text-orange">Book Ticket</h1>

    <div class="summary-box mt-3">
        <h4><?= htmlspecialchars($session['title']) ?></h4>
        <p><strong>Date & Time:</strong> <?= date('d M Y, H:i', strtotime($session['show_time'])) ?></p>
        <p><strong>Hall:</strong> <?= htmlspecialchars($session['hall_name']) ?></p>
        <p><strong>Price per ticket:</strong> <?= number_format($session['price'], 2) ?> €</p>
    </div>

    <form action="confirm_booking.php" method="POST" class="mt-4">
        <input type="hidden" name="session_id" value="<?= $session['id'] ?>">

        <div class="mb-3">
            <label for="name" class="form-label">Your name</label>
            <input type="text" class="form-control" id="name" name="name" required
                   value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['name']) : '' ?>">
        </div>

        <?php if (!isset($_SESSION['user'])): ?>
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="seats" class="form-label">Number of tickets</label>
            <select class="form-select" id="seats" name="seats" required>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="premium" name="premium" value="1">
            <label class="form-check-label" for="premium">
                Premium seat (+20%)
            </label>
        </div>

        <button type="submit" class="btn btn-orange w-100 mt-3">Confirm Booking</button>
    </form>
</div>

<footer>
    <p>© <?= date('Y') ?> MyCinema. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
