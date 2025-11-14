<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';


session_start();

// 🔍 поиск фильмов
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
    <title>Cinema - Movies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles/style_index.css">
</head>
<body class="bg-dark text-white">
<?php include '../includes/header.php'; ?>

<div class="container mt-5">
    <?php if ($search): ?>
        <h2 class="text" style="color: #ff6f00;">Search results for: “<?= htmlspecialchars($search) ?>”</h2>
    <?php else: ?>
        <h1 class="text" style="color: #ff6f00;">🎞 Now Showing</h1>
    <?php endif; ?>

    <?php if (empty($movies)): ?>
        <p class="text-secondary">No movies found.</p>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($movies as $movie): ?>
                <div class="col-md-3">
                    <div class="movie-card text-center bg-secondary bg-opacity-10 border border-secondary rounded-3 p-3 h-100">
                        <img src="assets/posters/<?= htmlspecialchars($movie['poster']) ?>.jpg"
                             alt="<?= htmlspecialchars($movie['title']) ?>"
                             class="img-fluid rounded mb-3"
                             onerror="this.src='assets/posters/default.jpg'">
                        <h5><?= htmlspecialchars($movie['title']) ?></h5>
                        <p class="text"><?= htmlspecialchars($movie['genre']) ?> • <?= htmlspecialchars($movie['duration']) ?> min</p>
                        <a href="movie.php?id=<?= $movie['id'] ?>" class="btn w-100" style="background-color: #ff6f00;">View Sessions</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<footer class="text-center text-secondary mt-5 py-4 border-top border-secondary">
    <p>© <?= date('Y') ?> MyCinema. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
