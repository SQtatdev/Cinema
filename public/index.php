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

// Count upcoming sessions per movie
$sessionCounts = [];
$rows = $pdo->query("
    SELECT movie_id, COUNT(*) AS cnt
    FROM sessions
    WHERE show_time > NOW()
    GROUP BY movie_id
")->fetchAll();
foreach ($rows as $r) $sessionCounts[$r['movie_id']] = $r['cnt'];

// Today's movies (have a session today)
$todayMovies = [];
if (!$search) {
    $todayRows = $pdo->query("
        SELECT DISTINCT m.*
        FROM movies m
        JOIN sessions s ON s.movie_id = m.id
        WHERE DATE(s.show_time) = CURDATE()
        ORDER BY m.title
    ")->fetchAll();
    $todayMovies = $todayRows;
}

// Collect unique genres for filter
$genres = [];
foreach ($movies as $m) {
    if (!empty($m['genre'])) {
        foreach (array_map('trim', explode(',', $m['genre'])) as $g) {
            if ($g) $genres[$g] = true;
        }
    }
}
ksort($genres);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MyCinema — Now Showing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles/style_index.css?v=4">
</head>
<body>
<?php include '../includes/header.php'; ?>

<?php if (!$search): ?>
<!-- ── HERO ─────────────────────────────────────────────── -->
<section class="hero">
    <div class="hero__bg"></div>
    <div class="hero__content">
        <p class="hero__label">Welcome to</p>
        <h1 class="hero__title">MyCinema</h1>
        <p class="hero__sub">Book your seat. Feel the magic.</p>
        <form class="hero__search" method="GET" action="index.php">
            <input type="search" name="search" placeholder="Search for a movie…">
            <button type="submit">Search</button>
        </form>
    </div>
</section>
<?php endif; ?>

<div class="container py-5">

    <?php if ($search): ?>
    <!-- Search heading -->
    <div class="page-heading mb-4">
        <span class="label-tag">Search</span>
        <h1>Results for <em>"<?= htmlspecialchars($search) ?>"</em></h1>
    </div>

    <?php else: ?>

    <!-- ── TODAY'S SCREENINGS ──────────────────────────── -->
    <?php if (!empty($todayMovies)): ?>
    <div class="section-block mb-5">
        <div class="section-header">
            <span class="section-dot"></span>
            <h2 class="section-title">Today on Screen</h2>
            <span class="section-count"><?= count($todayMovies) ?> film<?= count($todayMovies)>1?'s':'' ?></span>
        </div>
        <div class="today-strip">
            <?php foreach ($todayMovies as $m):
                $src = !empty($m['poster'])
                    ? 'data:image/jpeg;base64,' . base64_encode($m['poster'])
                    : 'assets/posters/' . (int)$m['id'] . '.jpg';
            ?>
            <a href="movie.php?id=<?= (int)$m['id'] ?>" class="today-card">
                <img src="<?= $src ?>" alt="<?= htmlspecialchars($m['title']) ?>"
                     onerror="this.src='assets/posters/default.jpg'">
                <div class="today-card__info">
                    <span class="today-card__title"><?= htmlspecialchars($m['title']) ?></span>
                    <?php if (!empty($sessionCounts[$m['id']])): ?>
                        <span class="today-card__sessions"><?= $sessionCounts[$m['id']] ?> sessions</span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── ALL MOVIES heading + genre filters ─────────── -->
    <div class="section-header mb-3">
        <span class="section-dot"></span>
        <h2 class="section-title">Now Showing</h2>
        <span class="section-count"><?= count($movies) ?> films</span>
    </div>

    <?php if (!empty($genres)): ?>
    <div class="genre-filters mb-4" id="genreFilters">
        <button class="genre-btn active" data-genre="all">All</button>
        <?php foreach ($genres as $g => $_): ?>
            <button class="genre-btn" data-genre="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>

    <!-- ── MOVIE GRID ───────────────────────────────────── -->
    <?php if (empty($movies)): ?>
        <p class="no-results">No movies found.</p>
    <?php else: ?>
        <div class="row g-4" id="movieGrid">
            <?php foreach ($movies as $movie):
                $posterSrc = !empty($movie['poster'])
                    ? 'data:image/jpeg;base64,' . base64_encode($movie['poster'])
                    : 'assets/posters/' . (int)$movie['id'] . '.jpg';
                $cnt = $sessionCounts[$movie['id']] ?? 0;
                // Collect genres for data attr
                $genreAttr = htmlspecialchars($movie['genre'] ?? '');
            ?>
                <div class="col-6 col-md-4 col-lg-3 movie-col"
                     data-genre="<?= $genreAttr ?>">
                    <div class="movie-card">
                        <div class="movie-card__poster-wrap">
                            <img src="<?= $posterSrc ?>"
                                 alt="<?= htmlspecialchars($movie['title']) ?>"
                                 class="movie-card__poster"
                                 onerror="this.src='assets/posters/default.jpg'">
                            <?php if ($cnt > 0): ?>
                                <div class="session-badge"><?= $cnt ?> sessions</div>
                            <?php endif; ?>
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
        <p class="no-results" id="noResults" style="display:none">No movies in this genre.</p>
    <?php endif; ?>
</div>

<footer class="site-footer">
    <p>© <?= date('Y') ?> MyCinema. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Genre filter ──────────────────────────────────────────
const filterBtns = document.querySelectorAll('.genre-btn');
const movieCols  = document.querySelectorAll('.movie-col');
const noResults  = document.getElementById('noResults');

filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const genre = btn.dataset.genre;
        let visible = 0;
        movieCols.forEach(col => {
            const colGenre = col.dataset.genre || '';
            const match = genre === 'all' || colGenre.toLowerCase().includes(genre.toLowerCase());
            col.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
    });
});
</script>
</body>
</html>