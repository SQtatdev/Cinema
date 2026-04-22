<?php
require_once 'db.php';

// ═══════════════════════════════════════════
// MOVIES
// ═══════════════════════════════════════════
function getMovieById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE id=?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getAllMovies() {
    global $pdo;
    return $pdo->query("SELECT * FROM movies ORDER BY id DESC")->fetchAll();
}

function addMovie($title, $description, $genre, $duration, $release_date, $poster = null) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO movies (title, description, genre, duration, release_date, poster)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$title, $description, $genre, $duration, $release_date, $poster]);
    return (int)$pdo->lastInsertId();
}

function updateMovie($id, $title, $description, $genre, $duration, $release_date, $poster = null) {
    global $pdo;
    if ($poster) {
        $stmt = $pdo->prepare("UPDATE movies SET title=?, description=?, genre=?, duration=?, release_date=?, poster=? WHERE id=?");
        $stmt->execute([$title, $description, $genre, $duration, $release_date, $poster, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE movies SET title=?, description=?, genre=?, duration=?, release_date=? WHERE id=?");
        $stmt->execute([$title, $description, $genre, $duration, $release_date, $id]);
    }
}

function deleteMovie($id) {
    global $pdo;
    $pdo->prepare("DELETE FROM sessions WHERE movie_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM movies WHERE id=?")->execute([$id]);
}

// ═══════════════════════════════════════════
// SESSIONS
// ═══════════════════════════════════════════
function getSessionsByMovie($movie_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT s.*, h.name AS hall_name, m.title
        FROM sessions s
        JOIN halls h ON s.hall_id = h.id
        JOIN movies m ON s.movie_id = m.id
        WHERE s.movie_id = ?
          AND s.show_time > NOW()
        ORDER BY s.show_time ASC
    ");
    $stmt->execute([$movie_id]);
    return $stmt->fetchAll();
}

function getAllSessions() {
    global $pdo;
    return $pdo->query("
        SELECT s.*, m.title AS movie_title, h.name AS hall_name
        FROM sessions s
        JOIN movies m ON s.movie_id = m.id
        JOIN halls  h ON s.hall_id  = h.id
        ORDER BY s.show_time DESC
    ")->fetchAll();
}

function getSessionById($id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT s.*, m.title AS movie_title, h.name AS hall_name
        FROM sessions s
        JOIN movies m ON s.movie_id = m.id
        JOIN halls  h ON s.hall_id  = h.id
        WHERE s.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function addSession($movie_id, $hall_id, $show_time, $price) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO sessions (movie_id, hall_id, show_time, price) VALUES (?, ?, ?, ?)");
    $stmt->execute([$movie_id, $hall_id, $show_time, $price]);
}

function updateSession($id, $movie_id, $hall_id, $show_time, $price) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE sessions SET movie_id=?, hall_id=?, show_time=?, price=? WHERE id=?");
    $stmt->execute([$movie_id, $hall_id, $show_time, $price, $id]);
}

function deleteSession($id) {
    global $pdo;
    $pdo->prepare("DELETE FROM booking_seats WHERE booking_id IN (SELECT id FROM bookings WHERE session_id=?)")->execute([$id]);
    $pdo->prepare("DELETE FROM bookings WHERE session_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM seats WHERE session_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM sessions WHERE id=?")->execute([$id]);
}

function getAllHalls() {
    global $pdo;
    return $pdo->query("SELECT * FROM halls ORDER BY id")->fetchAll();
}

// ═══════════════════════════════════════════
// BOOKINGS
// ═══════════════════════════════════════════
function getAllBookings($limit = 50, $offset = 0) {
    global $pdo;
    $limit  = (int)$limit;
    $offset = (int)$offset;
    $stmt = $pdo->query("
        SELECT b.*, m.title AS movie_title, s.show_time
        FROM bookings b
        JOIN sessions s ON b.session_id = s.id
        JOIN movies   m ON s.movie_id   = m.id
        ORDER BY b.created_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ");
    return $stmt->fetchAll();
}

function countAllBookings() {
    global $pdo;
    return (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
}

function cancelBooking($booking_id) {
    global $pdo;
    $pdo->prepare("UPDATE seats SET status='available', booking_id=NULL WHERE booking_id=?")->execute([$booking_id]);
    $pdo->prepare("DELETE FROM booking_seats WHERE booking_id=?")->execute([$booking_id]);
    $pdo->prepare("DELETE FROM bookings WHERE id=?")->execute([$booking_id]);
}

// ═══════════════════════════════════════════
// STATISTICS
// ═══════════════════════════════════════════
function getStats() {
    global $pdo;

    $totalRevenue  = (float)$pdo->query("SELECT COALESCE(SUM(total_price),0) FROM bookings")->fetchColumn();
    $totalBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    $totalMovies   = (int)$pdo->query("SELECT COUNT(*) FROM movies")->fetchColumn();
    $totalUsers    = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $upcomingSess  = (int)$pdo->query("SELECT COUNT(*) FROM sessions WHERE show_time > NOW()")->fetchColumn();

    $topMovies = $pdo->query("
        SELECT m.title, COUNT(b.id) AS tickets, COALESCE(SUM(b.total_price),0) AS revenue
        FROM bookings b
        JOIN sessions s ON b.session_id = s.id
        JOIN movies   m ON s.movie_id   = m.id
        GROUP BY m.id, m.title
        ORDER BY revenue DESC
        LIMIT 5
    ")->fetchAll();

    $revenueByDay = $pdo->query("
        SELECT DATE(created_at) AS day,
               COALESCE(SUM(total_price),0) AS revenue,
               COUNT(*) AS tickets
        FROM bookings
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY day ASC
    ")->fetchAll();

    return compact('totalRevenue','totalBookings','totalMovies','totalUsers','upcomingSess','topMovies','revenueByDay');
}