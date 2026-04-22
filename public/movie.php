<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
session_start();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) die('Movie not found.');
$movie = getMovieById($id);
if (!$movie) die('Invalid movie ID.');
$sessions = getSessionsByMovie($movie['id']);

if (!empty($movie['poster'])) {
    $posterSrc = 'data:image/jpeg;base64,' . base64_encode($movie['poster']);
} else {
    $posterSrc = 'assets/posters/' . (int)$movie['id'] . '.jpg';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($movie['title']) ?> — MyCinema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles/style_movie.css?v=3">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container py-4">
    <a href="index.php" class="btn-back">← Back to movies</a>

    <div class="movie-header">
        <img src="<?= $posterSrc ?>"
             alt="<?= htmlspecialchars($movie['title']) ?>"
             onerror="this.src='assets/posters/default.jpg'">

        <div class="movie-details">
            <span class="movie-genre"><?= htmlspecialchars($movie['genre']) ?></span>
            <h1><?= htmlspecialchars($movie['title']) ?></h1>
            <p><?= !empty($movie['description']) ? nl2br(htmlspecialchars($movie['description'])) : '<em style="color:var(--muted)">No description available.</em>' ?></p>
            <div class="movie-meta-row">
                <div class="movie-meta-item">
                    <span class="meta-label">Duration</span>
                    <span class="meta-value"><?= (int)$movie['duration'] ?> min</span>
                </div>
                <?php if (!empty($movie['release_date'])): ?>
                <div class="movie-meta-item">
                    <span class="meta-label">Release</span>
                    <span class="meta-value"><?= date('Y', strtotime($movie['release_date'])) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="sessions-list">
        <h2>Available Sessions</h2>
        <?php if ($sessions): ?>
            <?php foreach ($sessions as $session): ?>
                <div class="session-card">
                    <div class="session-info">
                        <span class="session-time"><?= date('d M Y, H:i', strtotime($session['show_time'])) ?></span>
                        <span class="session-hall">🏛 <?= htmlspecialchars($session['hall_name']) ?></span>
                    </div>
                    <div style="display:flex;align-items:center;gap:20px">
                        <span class="session-price"><?= number_format($session['price'], 2) ?> €</span>
                        <a href="booking.php?session_id=<?= (int)$session['id'] ?>" class="btn-orange">Book</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color:var(--muted)">No sessions available at the moment.</p>
        <?php endif; ?>
    </div>
</div>

<footer>
    <p>© <?= date('Y') ?> MyCinema. All rights reserved.</p>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>