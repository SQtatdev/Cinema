<?php
require_once 'db.php';

// ----------------- ФУНКЦИЯ ДЛЯ ОБНОВЛЕНИЯ SQL-ФАЙЛА -----------------
function exportMySQLToSql() {
    global $pdo;
    $config = include __DIR__ . '/config.php';
    $db = 'cinema_db';
    $user = 'root';
    $pass = ''; 
    $file = __DIR__ . '/../cinema_db.sql';

    // 1: config.php
    $mysqldump = $config['mysqldump_path'];

    // 2: if MySQL added to PATH
    if (!file_exists($mysqldump)) {
        $mysqldump = 'mysqldump'; // find in PATH
    }

    $cmd = "\"$mysqldump\" -u $user --password=\"$pass\" $db > " . escapeshellarg($file);
    exec($cmd, $output, $return_var);

    if ($return_var !== 0) {
        error_log("Ошибка экспорта базы в SQL: " . implode("\n", $output));
    }
}


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

function addMovie($title, $description, $genre, $duration, $release_date) {
    global $pdo;

    // poster = max(poster) + 1
    $posterId = $pdo->query("SELECT MAX(poster) FROM movies")->fetchColumn();
    $posterId = $posterId ? $posterId + 1 : 1;

    $stmt = $pdo->prepare("
        INSERT INTO movies (title, description, genre, duration, release_date, poster)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$title, $description, $genre, $duration, $release_date, $posterId]);

    exportMySQLToSql(); 
    return $posterId;
}

function updateMovie($id, $title, $description, $genre, $duration, $release_date) {
    global $pdo;
    $stmt = $pdo->prepare("
        UPDATE movies SET title=?, description=?, genre=?, duration=?, release_date=? WHERE id=?
    ");
    $stmt->execute([$title, $description, $genre, $duration, $release_date, $id]);

    exportMySQLToSql(); 
}

function deleteMovie($id) {
    global $pdo;
    $pdo->prepare("DELETE FROM sessions WHERE movie_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM movies WHERE id=?")->execute([$id]);

    exportMySQLToSql(); 
}

// ----------------- СЕССИИ -----------------
function getSessionsByMovie($movie_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT s.*, h.name AS hall_name
        FROM sessions s
        JOIN halls h ON s.hall_id = h.id
        WHERE s.movie_id=?
        ORDER BY s.show_time ASC
    ");
    $stmt->execute([$movie_id]);
    return $stmt->fetchAll();
}
