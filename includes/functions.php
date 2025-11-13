<?php
require_once 'db.php';

function getMovieById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getAllMovies() {
    global $pdo;
    return $pdo->query("SELECT * FROM movies")->fetchAll();
}

function getSessionsByMovie($movie_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT s.*, h.name AS hall_name 
                           FROM sessions s 
                           JOIN halls h ON s.hall_id = h.id 
                           WHERE s.movie_id = ? 
                           ORDER BY s.show_time ASC");
    $stmt->execute([$movie_id]);
    return $stmt->fetchAll();
}

// ----------------- НОВЫЕ ФУНКЦИИ ДЛЯ АДМИНКИ -----------------

function addMovie($title, $description, $genre, $duration, $release_date, $posterFile = null) {
    global $pdo;

    // Обрабатываем пустую дату
    $release_date = !empty($release_date) ? $release_date : null;

    // Находим следующий poster_id
    $stmt = $pdo->query("SELECT MAX(poster) AS max_poster FROM movies");
    $maxPoster = $stmt->fetch()['max_poster'] ?? 0;
    $nextPoster = $maxPoster + 1;

    // Работа с файлом
    if ($posterFile && isset($posterFile['tmp_name']) && $posterFile['tmp_name']) {
        $ext = pathinfo($posterFile['name'], PATHINFO_EXTENSION);
        $fileName = $nextPoster . '.' . $ext;
        move_uploaded_file($posterFile['tmp_name'], __DIR__ . '/../public/assets/posters/' . $fileName);
    }

    $stmt = $pdo->prepare("INSERT INTO movies (title, description, genre, duration, release_date, poster) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $genre, $duration, $release_date, $nextPoster]);
}



function updateMovie($id, $title, $description, $genre, $duration, $release_date, $posterFile = null) {
    global $pdo;

    $release_date = !empty($release_date) ? $release_date : null;

    $movie = getMovieById($id);
    $posterName = $movie['poster'];

    if ($posterFile && isset($posterFile['tmp_name']) && $posterFile['tmp_name']) {
        $ext = pathinfo($posterFile['name'], PATHINFO_EXTENSION);
        $fileName = $posterName . '.' . $ext;
        move_uploaded_file($posterFile['tmp_name'], __DIR__ . '/../public/assets/posters/' . $fileName);
    }

    $stmt = $pdo->prepare("UPDATE movies SET title=?, description=?, genre=?, duration=?, release_date=? WHERE id=?");
    $stmt->execute([$title, $description, $genre, $duration, $release_date, $id]);
}




// Удалить фильм
function deleteMovie($id) {
    global $pdo;
    // Удаляем сессии этого фильма, если они есть
    $stmt = $pdo->prepare("DELETE FROM sessions WHERE movie_id=?");
    $stmt->execute([$id]);

    // Удаляем сам фильм
    $stmt = $pdo->prepare("DELETE FROM movies WHERE id=?");
    $stmt->execute([$id]);
}
