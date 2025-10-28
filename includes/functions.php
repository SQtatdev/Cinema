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

function getPosterByid($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT poster FROM movies WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}