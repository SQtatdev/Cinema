<?php
require_once '../includes/functions.php';
$movies = getAllMovies();
$id = $_GET['id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cinema - Movies</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Now Showing</h1>
    <div class="movies">
        <?php foreach ($movies as $movie): ?>
            <div class="movie-card">
                <img src="assets/posters/<?= htmlspecialchars($movie['poster']) ?>.jpg" alt="<?= htmlspecialchars($movie['title']) ?>" />
                <h2><?= htmlspecialchars($movie['title']) ?></h2>
                <p>Genre: <?= htmlspecialchars($movie['genre']) ?></p>
                <p>Duration: <?= htmlspecialchars($movie['duration']) ?> min</p>
                <a href="movie.php?id=<?= $movie['id'] ?>">View Sessions</a>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
