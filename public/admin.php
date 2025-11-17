<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

// admin check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        addMovie(
            $_POST['title'],
            $_POST['description'],
            $_POST['genre'],
            $_POST['duration'],
            $_POST['release_date'],
        );
    } elseif (isset($_POST['edit'])) {
        updateMovie(
            $_POST['id'],
            $_POST['title'],
            $_POST['description'],
            $_POST['genre'],
            $_POST['duration'],
            $_POST['release_date'],
        );
    }
    header('Location: admin.php');
    exit;
}

if (isset($_GET['delete'])) {
    deleteMovie($_GET['delete']);
    header('Location: admin.php');
    exit;
}

$editMovie = isset($_GET['edit_id']) ? getMovieById($_GET['edit_id']) : null;
$movies = getAllMovies();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Panel - Cinema</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h1 class="mb-4">Admin Panel</h1>
    <p>Hello, <?= htmlspecialchars($_SESSION['user']['name'] ?? $_SESSION['user']['email']) ?>! <a href="logout.php">Logout</a></p>

    <h2><?= $editMovie ? 'Edit Movie' : 'Add New Movie' ?></h2>
    <form method="post" class="mb-4" enctype="multipart/form-data">
        <?php if($editMovie): ?>
            <input type="hidden" name="id" value="<?= $editMovie['id'] ?>">
        <?php endif; ?>
        <div class="mb-2">
            <input class="form-control" name="title" placeholder="Title" value="<?= $editMovie['title'] ?? '' ?>" required>
        </div>
        <div class="mb-2">
            <textarea class="form-control" name="description" placeholder="Description"><?= $editMovie['description'] ?? '' ?></textarea>
        </div>
        <div class="mb-2">
            <input class="form-control" name="genre" placeholder="Genre" value="<?= $editMovie['genre'] ?? '' ?>">
        </div>
        <div class="mb-2">
            <input class="form-control" type="number" name="duration" placeholder="Duration (minutes)" value="<?= $editMovie['duration'] ?? '' ?>">
        </div>
        <div class="mb-2">
            <input class="form-control" type="date" name="release_date" placeholder="Release Date" value="<?= $editMovie['release_date'] ?? '' ?>">
        </div>
        <div class="mb-2">
            <input class="form-control" type="file" name="poster">
            <?php if($editMovie): ?>
                <small>Current poster: <?= $editMovie['poster'] ?></small>
            <?php endif; ?>
        </div>
        <button type="submit" name="<?= $editMovie ? 'edit' : 'add' ?>" class="btn btn-primary"><?= $editMovie ? 'Save Changes' : 'Add Movie' ?></button>
    </form>

    <h2>Movie List</h2>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Description</th>
                <th>Genre</th>
                <th>Duration</th>
                <th>Release Date</th>
                <th>Poster</th>
                <th>Edit</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movies as $m): ?>
            <?php 
                // file extension check
                $posterFile = null;
                $extensions = ['jpg','png','jpeg','webp'];
                foreach($extensions as $ext){
                    if(file_exists(__DIR__ . '/../public/assets/posters/' . $m['poster'] . '.' . $ext)){
                        $posterFile = $m['poster'] . '.' . $ext;
                        break;
                    }
                }
            ?>
            <tr>
                <td><?= $m['id'] ?></td>
                <td><?= htmlspecialchars($m['title']) ?></td>
                <td><?= htmlspecialchars($m['description']) ?></td>
                <td><?= htmlspecialchars($m['genre']) ?></td>
                <td><?= $m['duration'] ?></td>
                <td><?= $m['release_date'] ?></td>
                <td>
                    <?php if ($posterFile): ?>
                        <img src="/public/assets/posters/<?= $posterFile ?>" alt="Poster" width="50">
                    <?php else: ?>
                        No poster
                    <?php endif; ?>
                </td>
                <td><a class="btn btn-sm btn-warning" href="?edit_id=<?= $m['id'] ?>">Edit</a></td>
                <td><a class="btn btn-sm btn-danger" href="?delete=<?= $m['id'] ?>" onclick="return confirm('Are you sure to delete this movie?')">Delete</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
