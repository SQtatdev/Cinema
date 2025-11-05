<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

// Получаем бронирования пользователя
$stmt = $pdo->prepare("
    SELECT b.*, m.title, s.show_time, h.name AS hall_name
    FROM bookings b
    JOIN sessions s ON b.session_id = s.id
    JOIN movies m ON s.movie_id = m.id
    JOIN halls h ON s.hall_id = h.id
    WHERE b.user_id = ?
    ORDER BY s.show_time DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Bookings - MyCinema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">
<div class="container mt-5">
    <h2 class="text-warning mb-4">🎟 My Bookings</h2>

    <?php if (empty($bookings)): ?>
        <div class="alert alert-secondary">You don’t have any bookings yet.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-dark table-striped align-middle">
                <thead class="table-warning text-dark">
                    <tr>
                        <th>#</th>
                        <th>Movie</th>
                        <th>Date & Time</th>
                        <th>Hall</th>
                        <th>Seat</th>
                        <th>Status</th>
                        <th>Booked on</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $i => $b): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($b['title']) ?></td>
                            <td><?= date('d M Y, H:i', strtotime($b['show_time'])) ?></td>
                            <td><?= htmlspecialchars($b['hall_name']) ?></td>
                            <td>Row <?= $b['seat_row'] ?>, Seat <?= $b['seat_number'] ?></td>
                            <td>
                                <?php if ($b['status'] === 'premium'): ?>
                                    <span class="badge bg-warning text-dark">Premium</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Standard</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d M Y', strtotime($b['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <a href="index.php" class="btn btn-outline-warning mt-4">← Back to Movies</a>
</div>
</body>
</html>
