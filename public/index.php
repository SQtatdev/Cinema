<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

session_start();

$search = $_GET['search'] ?? '';
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE title LIKE ?");
    $stmt->execute(["%$search%"]);
    $movies = $stmt->fetchAll();
} else {
    $movies = getAllMovies();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MyCinema — Now Showing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles/style_index.css?v=<?= filemtime('assets/styles/style_index.css') ?>">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container py-5">

    <div class="page-heading mb-5">
        <?php if ($search): ?>
            <span class="label-tag">Search</span>
            <h1>Results for <em>"<?= htmlspecialchars($search) ?>"</em></h1>
        <?php else: ?>
            <span class="label-tag">On Screen</span>
            <h1>Now Showing</h1>
        <?php endif; ?>
    </div>

    <?php if (empty($movies)): ?>
        <p class="no-results">No movies found.</p>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($movies as $movie): ?>
                <?php
                // Use blob if available, otherwise fall back to file
                if (!empty($movie['poster'])) {
                    $posterSrc = 'data:image/jpeg;base64,' . base64_encode($movie['poster']);
                } else {
                    $posterSrc = 'assets/posters/' . (int)$movie['id'] . '.jpg';
                }
                ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="movie-card">
                        <div class="movie-card__poster-wrap">
                            <img src="<?= $posterSrc ?>"
                                 alt="<?= htmlspecialchars($movie['title']) ?>"
                                 class="movie-card__poster"
                                 onerror="this.src='assets/posters/default.jpg'">
                            <div class="movie-card__overlay">
                                <a href="movie.php?id=<?= (int)$movie['id'] ?>" class="btn-view">
                                    View Sessions
                                </a>
                            </div>
                        </div>
                        <div class="movie-card__body">
                            <h5 class="movie-card__title"><?= htmlspecialchars($movie['title']) ?></h5>
                            <div class="movie-card__meta">
                                <span class="genre-tag"><?= htmlspecialchars($movie['genre']) ?></span>
                                <span class="duration"><?= (int)$movie['duration'] ?> min</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<footer class="site-footer">
    <p>© <?= date('Y') ?> MyCinema. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>