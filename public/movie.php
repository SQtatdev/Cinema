<?php
require_once '../includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Movie ID is missing");

$movie = getMovieById($id);
$sessions = getSessionsByMovie($id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($movie['title']) ?></title>
    <img src="assets/posters/<?= $id ?>.jpg" />
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1><?= htmlspecialchars($movie['title']) ?></h1>
    <p>Genre: <?= htmlspecialchars($movie['genre']) ?></p>
    <p>Duration: <?= htmlspecialchars($movie['duration']) ?> min</p>
    <p>Description: <?= htmlspecialchars($movie['description']) ?></p>

    <h2>Sessions</h2>
    <?php if ($sessions): ?>
        <ul>
        <?php foreach ($sessions as $session): ?>
            <li>
                <?= $session['show_time'] ?> | Hall: <?= $session['hall_name'] ?> | Price: $<?= $session['price'] ?>
                <a href="booking.php?session_id=<?= $session['id'] ?>">Book Now</a>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No sessions available.</p>
    <?php endif; ?>
</body>
</html>
