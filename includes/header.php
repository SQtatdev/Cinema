<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
$user = $_SESSION['user'] ?? null;
?>
<style>
.site-nav {
    background: #111 !important;
    border-bottom: 2px solid #ff6f00;
    padding: 10px 0;
}
.site-nav .navbar-brand {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.7rem;
    color: #ff6f00 !important;
    letter-spacing: 3px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
}
.site-nav .navbar-brand:hover {
    color: #cc5800 !important;
}
.site-nav .search-form input {
    background: #1a1a1a;
    border: 1px solid #333;
    color: #f0f0f0;
    border-radius: 6px 0 0 6px;
    padding: 8px 14px;
    font-size: 0.88rem;
    transition: border-color .2s;
    min-width: 220px;
}
.site-nav .search-form input:focus {
    background: #1a1a1a;
    border-color: #ff6f00;
    color: #f0f0f0;
    box-shadow: none;
    outline: none;
}
.site-nav .search-form input::placeholder { color: #555; }
.site-nav .search-form button {
    background: #ff6f00;
    color: #fff;
    border: none;
    border-radius: 0 6px 6px 0;
    padding: 8px 18px;
    font-size: 0.85rem;
    font-weight: 600;
    letter-spacing: .5px;
    transition: background .2s;
}
.site-nav .search-form button:hover { background: #cc5800; }

/* User dropdown */
.site-nav .nav-link {
    color: #ccc !important;
    font-size: 0.9rem;
    transition: color .2s;
}
.site-nav .nav-link:hover { color: #ff6f00 !important; }
.site-nav .dropdown-menu {
    background: #1a1a1a !important;
    border: 1px solid #2a2a2a !important;
    border-radius: 8px;
    padding: 6px;
    min-width: 200px;
}
.site-nav .dropdown-header {
    color: #888 !important;
    font-size: 0.78rem;
    padding: 4px 10px 8px;
}
.site-nav .dropdown-item {
    color: #ccc !important;
    border-radius: 6px;
    padding: 8px 12px;
    font-size: 0.88rem;
    transition: background .15s, color .15s;
}
.site-nav .dropdown-item:hover {
    background: rgba(255,111,0,.12) !important;
    color: #ff6f00 !important;
}
.site-nav .dropdown-item.text-danger { color: #e74c3c !important; }
.site-nav .dropdown-item.text-danger:hover {
    background: rgba(231,76,60,.12) !important;
    color: #e74c3c !important;
}
.site-nav .dropdown-divider {
    border-color: #2a2a2a !important;
    margin: 4px 0;
}
.site-nav .avatar-ring {
    border: 2px solid #ff6f00;
    border-radius: 50%;
}
.site-nav .btn-login {
    background: transparent;
    color: #ff6f00 !important;
    border: 1px solid #ff6f00;
    border-radius: 6px;
    padding: 7px 18px;
    font-size: 0.85rem;
    font-weight: 600;
    transition: background .2s, color .2s;
    text-decoration: none;
}
.site-nav .btn-login:hover {
    background: #ff6f00;
    color: #fff !important;
}
.navbar-toggler { border-color: #444 !important; }
.navbar-toggler-icon {
    filter: invert(60%) sepia(80%) saturate(500%) hue-rotate(0deg);
}
</style>

<nav class="navbar navbar-expand-lg site-nav">
    <div class="container">
        <!-- Logo — uses relative path so works everywhere -->
        <a class="navbar-brand" href="<?= rtrim(dirname($_SERVER['PHP_SELF']), '/\\') === '' ? 'index.php' : 'index.php' ?>">
            🎬 MyCinema
        </a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

       

            <!-- Right side -->
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if ($user): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                           href="#" role="button" data-bs-toggle="dropdown">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['name'] ?? 'U') ?>&background=ff6f00&color=fff&rounded=true&size=64"
                                 width="32" height="32" class="avatar-ring"
                                 alt="<?= htmlspecialchars($user['name'] ?? '') ?>">
                            <span><?= htmlspecialchars($user['name'] ?? 'User') ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <h6 class="dropdown-header"><?= htmlspecialchars($user['email'] ?? '') ?></h6>
                            </li>
                            <li><a class="dropdown-item" href="my_bookings.php">🎟 My Bookings</a></li>
                            <?php if (($user['role'] ?? '') === 'admin'): ?>
                                <li><a class="dropdown-item" href="admin.php">🛠 Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">🚪 Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn-login" href="login.php">Login / Sign up</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>