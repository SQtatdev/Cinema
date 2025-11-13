<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$user = $_SESSION['user'] ?? null;
?>

<nav class="navbar navbar-expand-lg" style="color: #ff6f00;">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-warning" href="/Cinema/public/index.php">🎬 MyCinema</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
            <form class="d-flex ms-auto me-auto" role="search" method="GET" action="index.php" style="max-width: 400px;">
                <input class="form-control me-2 bg-dark text-white border-secondary" 
                       type="search"
                       name="search"
                       placeholder="Search movies..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <button class="btn" type="submit" style="background-color: #ff6f00;">Search</button>
            </form>

            <ul class="navbar-nav ms-auto">
                <?php if ($user): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <span class="me-2"><?= htmlspecialchars($user['name']) ?></span>
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&background=ff6f00&color=fff&rounded=true"
                                 width="35" height="35" class="rounded-circle">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end bg-dark border-secondary">
                            <li><h6 class="dropdown-header text-white"><?= htmlspecialchars($user['email']) ?></h6></li>
                            <li><a class="dropdown-item text-white" href="my_bookings.php">🎟 My Bookings</a></li>
                            <?php if ($user['role'] === 'admin'): ?>
                                <li><a class="dropdown-item text-white" href="admin.php">🛠 Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider bg-secondary"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">🚪 Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-warning ms-2" href="login.php">Login / Sign up</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
