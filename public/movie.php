<?php
require_once '../includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die('Movie not found.');
}

$movie = getMovieById($id);
if (!$movie) {
    die('Invalid movie ID.');
}

$sessions = getSessionsByMovie($movie['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($movie['title']) ?> - Cinema</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles/style_movie.css">
</head>
<body>

<div class="container">
    <a href="index.php" class="btn btn-sm btn-outline-light mt-4 mb-3">← Back to movies</a>

    <div class="movie-header row">
        <div class="col-md-4">
            <?php if (!empty($movie['poster'])): ?>
                <img src="data:image/jpeg;base64,<?= base64_encode($movie['poster']) ?>" 
                     alt="<?= htmlspecialchars($movie['title']) ?>" class="img-fluid rounded mb-3">
            <?php else: ?>
                <img src="assets/posters/default.jpg" 
                     alt="No poster" class="img-fluid rounded mb-3">
            <?php endif; ?>
        </div>

        <div class="movie-details col-md-7">
            <h1><?= htmlspecialchars($movie['title']) ?></h1>
            <p><strong>Genre:</strong> <?= htmlspecialchars($movie['genre']) ?></p>
            <p><strong>Duration:</strong> <?= htmlspecialchars($movie['duration']) ?> min</p>
            <?php if (!empty($movie['description'])): ?>
                <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($movie['description'])) ?></p>
            <?php else: ?>
                <p class="text">No description available.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="sessions-list mt-4">
        <h3 class="text-orange mb-3">Available Sessions</h3>
        <?php if ($sessions): ?>
            <?php foreach ($sessions as $session): ?>
                <div class="session-card d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                    <div>
                        <h5 class="mb-1"><?= date('d M Y, H:i', strtotime($session['show_time'])) ?></h5>
                        <p class="mb-0 text">Hall: <?= htmlspecialchars($session['hall_name']) ?></p>
                    </div>
                    <div>
                        <p class="text-orange fw-bold mb-2"><?= number_format($session['price'], 2) ?> €</p>
                        <a href="booking.php?session_id=<?= $session['id'] ?>" class="btn btn-orange btn-sm">Book</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted">No sessions available at the moment.</p>
        <?php endif; ?>
    </div>
</div>

<footer class="mt-5 text-center text-secondary">
    <p>© <?= date('Y') ?> MyCinema. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>