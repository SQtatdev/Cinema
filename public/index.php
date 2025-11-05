<?php
require_once '../includes/functions.php';
$movies = getAllMovies();
$id = $_GET['id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cinema - Now Showing</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles/style_index.css">

</head>
<body>

    <div class="container">
        <h1>Now Showing</h1>

        <div class="row g-4">
            <?php foreach ($movies as $movie): ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="movie-card">
                        <?php 
                            $posterPath = "assets/posters/" . htmlspecialchars($movie['poster']) . ".jpg";
                            if (!file_exists($posterPath)) {
                                $posterPath = "assets/posters/default.jpg";
                            }
                        ?>
                        <img src="<?= $posterPath ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
                        <div class="movie-info">
                            <h2><?= htmlspecialchars($movie['title']) ?></h2>
                            <p><strong>Genre:</strong> <?= htmlspecialchars($movie['genre']) ?></p>
                            <p><strong>Duration:</strong> <?= htmlspecialchars($movie['duration']) ?> min</p>
                            <a href="movie.php?id=<?= $movie['id'] ?>" class="btn btn-orange w-100 mt-2">View Sessions</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <footer>
        <p>© <?= date('Y') ?> MyCinema. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
