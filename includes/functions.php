<?php
require_once 'db.php';

// ----------------- Movies -----------------
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
}

function updateMovie($id, $title, $description, $genre, $duration, $release_date, $poster = null) {
    global $pdo;

    if ($poster) {
        $stmt = $pdo->prepare("
            UPDATE movies SET title=?, description=?, genre=?, duration=?, release_date=?, poster=? WHERE id=?
        ");
        $stmt->execute([$title, $description, $genre, $duration, $release_date, $poster, $id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE movies SET title=?, description=?, genre=?, duration=?, release_date=? WHERE id=?
        ");
        $stmt->execute([$title, $description, $genre, $duration, $release_date, $id]);
    }
}

function deleteMovie($id) {
    global $pdo;
    $pdo->prepare("DELETE FROM sessions WHERE movie_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM movies WHERE id=?")->execute([$id]);
}

// ----------------- СЕССИИ -----------------
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