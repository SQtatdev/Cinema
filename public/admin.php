<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/functions.php';

// ── Auth check ──────────────────────────────────────────────
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php'); exit;
}

$tab = $_GET['tab'] ?? 'stats';
$msg = '';

// ── Schedule data ──
function getSchedule() {
    global $pdo;
    return $pdo->query("
        SELECT s.id, s.show_time, s.price,
               m.id AS movie_id, m.title, m.genre, m.duration, m.poster,
               h.name AS hall_name
        FROM sessions s
        JOIN movies m ON s.movie_id = m.id
        JOIN halls  h ON s.hall_id  = h.id
        WHERE s.show_time > NOW()
        ORDER BY s.show_time ASC
    ")->fetchAll();
}

// ══════════════════════════════════════════════════════════════
// HANDLE POST ACTIONS
// ══════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Movies ──
    if (isset($_POST['action']) && in_array($_POST['action'], ['add_movie','edit_movie'])) {
        $posterData = null;
        if (!empty($_FILES['poster']['tmp_name'])) {
            $posterData = file_get_contents($_FILES['poster']['tmp_name']);
        }
        if ($_POST['action'] === 'add_movie') {
            addMovie($_POST['title'], $_POST['description'], $_POST['genre'],
                     $_POST['duration'], $_POST['release_date'], $posterData);
            $msg = 'Movie added successfully.';
        } else {
            updateMovie($_POST['id'], $_POST['title'], $_POST['description'],
                        $_POST['genre'], $_POST['duration'], $_POST['release_date'], $posterData);
            $msg = 'Movie updated.';
        }
        header('Location: admin.php?tab=movies&msg=' . urlencode($msg)); exit;
    }

    // ── Sessions ──
    if (isset($_POST['action']) && in_array($_POST['action'], ['add_session','edit_session'])) {
        if ($_POST['action'] === 'add_session') {
            addSession($_POST['movie_id'], $_POST['hall_id'], $_POST['show_time'], $_POST['price']);
            $msg = 'Session added.';
        } else {
            updateSession($_POST['id'], $_POST['movie_id'], $_POST['hall_id'], $_POST['show_time'], $_POST['price']);
            $msg = 'Session updated.';
        }
        header('Location: admin.php?tab=sessions&msg=' . urlencode($msg)); exit;
    }
}

// ── GET actions ──
if (isset($_GET['delete_movie'])) {
    deleteMovie((int)$_GET['delete_movie']);
    header('Location: admin.php?tab=movies&msg=Movie+deleted.'); exit;
}
if (isset($_GET['delete_session'])) {
    deleteSession((int)$_GET['delete_session']);
    header('Location: admin.php?tab=sessions&msg=Session+deleted.'); exit;
}
if (isset($_GET['cancel_booking'])) {
    cancelBooking((int)$_GET['cancel_booking']);
    header('Location: admin.php?tab=bookings&msg=Booking+cancelled.'); exit;
}

// ── Load data ──
$movies   = getAllMovies();
$halls    = getAllHalls();
$stats    = getStats();
$bookings = getAllBookings(100);

$editMovie   = isset($_GET['edit_movie'])   ? getMovieById($_GET['edit_movie'])     : null;
$editSession = isset($_GET['edit_session']) ? getSessionById($_GET['edit_session']) : null;

$sessions = getAllSessions();

if (isset($_GET['msg'])) $msg = htmlspecialchars($_GET['msg']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Panel — MyCinema</title>
    <a href="admin_tests.php" class="nav-link"><span class="nav-icon">🧪</span> Tests</a>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root {
    --orange: #ff6f00;
    --orange-dim: #cc5800;
    --bg: #0e0e0e;
    --surface: #171717;
    --surface2: #1f1f1f;
    --border: #262626;
    --text: #f0f0f0;
    --muted: #777;
}
* { box-sizing: border-box; }
body { background: var(--bg); color: var(--text); font-family: 'DM Sans', Arial, sans-serif; min-height: 100vh; }

/* Sidebar */
.admin-wrap { display: flex; min-height: 100vh; }
.sidebar {
    width: 220px; flex-shrink: 0;
    background: #111;
    border-right: 1px solid var(--border);
    display: flex; flex-direction: column;
    padding: 0;
    position: sticky; top: 0; height: 100vh;
}
.sidebar-brand {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.4rem; letter-spacing: 2px;
    color: var(--orange); padding: 22px 20px 16px;
    border-bottom: 1px solid var(--border);
    text-decoration: none; display: block;
}
.sidebar-brand span { color: #555; font-size: .85rem; display: block; font-family: 'DM Sans', sans-serif; letter-spacing: 0; margin-top: 2px; }
.nav-item { display: block; }
.nav-link {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 20px; color: #aaa !important;
    text-decoration: none; font-size: .9rem;
    transition: background .15s, color .15s;
    border-left: 3px solid transparent;
}
.nav-link:hover { background: rgba(255,111,0,.08); color: var(--text) !important; }
.nav-link.active { background: rgba(255,111,0,.12); color: var(--orange) !important; border-left-color: var(--orange); }
.nav-icon { font-size: 1rem; width: 20px; text-align: center; }
.sidebar-footer { margin-top: auto; padding: 16px 20px; border-top: 1px solid var(--border); }
.sidebar-footer a { color: var(--muted); font-size: .82rem; text-decoration: none; }
.sidebar-footer a:hover { color: #e74c3c; }

/* Main */
.admin-main { flex: 1; padding: 32px; overflow-x: auto; }
.page-title { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; letter-spacing: 2px; color: var(--text); margin: 0 0 6px; }
.page-subtitle { color: var(--muted); font-size: .88rem; margin-bottom: 28px; }

/* Stat cards */
.stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 32px; }
.stat-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 10px; padding: 20px;
}
.stat-card .stat-label { font-size: .72rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; }
.stat-card .stat-value { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; color: var(--orange); line-height: 1; }
.stat-card .stat-sub { font-size: .8rem; color: var(--muted); margin-top: 4px; }

/* Tables */
.admin-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
.admin-table th { background: #111; color: var(--muted); font-size: .7rem; letter-spacing: 1.5px; text-transform: uppercase; padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--border); }
.admin-table td { padding: 12px 14px; border-bottom: 1px solid var(--border); color: var(--text); vertical-align: middle; }
.admin-table tr:hover td { background: rgba(255,255,255,.02); }
.admin-table img { border-radius: 4px; object-fit: cover; }

/* Forms */
.admin-form { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 24px; margin-bottom: 28px; }
.admin-form h3 { font-family: 'Bebas Neue', sans-serif; font-size: 1.3rem; letter-spacing: 1px; color: var(--text); margin: 0 0 18px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }
.form-label { color: #aaa; font-size: .82rem; font-weight: 500; margin-bottom: 5px; display: block; }
.form-control, .form-select {
    background: #111 !important; color: #f0f0f0 !important;
    border: 1px solid var(--border) !important; border-radius: 7px !important;
    padding: 9px 13px !important; font-size: .88rem !important; width: 100%;
    transition: border-color .2s !important;
}
.form-control:focus, .form-select:focus { border-color: var(--orange) !important; box-shadow: 0 0 0 3px rgba(255,111,0,.12) !important; outline: none !important; }
.form-control::placeholder { color: #444 !important; }
.form-row { display: grid; gap: 14px; margin-bottom: 14px; }
.form-row.cols-2 { grid-template-columns: 1fr 1fr; }
.form-row.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
.form-row.cols-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }

/* Buttons */
.btn-primary-admin {
    background: var(--orange); color: #fff; border: none;
    border-radius: 7px; padding: 9px 20px; font-size: .88rem;
    font-weight: 600; cursor: pointer; transition: background .2s;
}
.btn-primary-admin:hover { background: var(--orange-dim); }
.btn-sm-edit {
    background: rgba(255,111,0,.12); color: var(--orange);
    border: 1px solid rgba(255,111,0,.25); border-radius: 5px;
    padding: 4px 10px; font-size: .78rem; font-weight: 600;
    text-decoration: none; transition: background .15s;
    white-space: nowrap;
}
.btn-sm-edit:hover { background: rgba(255,111,0,.25); color: var(--orange); }
.btn-sm-delete {
    background: rgba(231,76,60,.1); color: #e74c3c;
    border: 1px solid rgba(231,76,60,.2); border-radius: 5px;
    padding: 4px 10px; font-size: .78rem; font-weight: 600;
    text-decoration: none; transition: background .15s;
    white-space: nowrap;
}
.btn-sm-delete:hover { background: rgba(231,76,60,.2); color: #e74c3c; }
.btn-cancel-admin {
    background: transparent; color: var(--muted);
    border: 1px solid var(--border); border-radius: 7px;
    padding: 9px 16px; font-size: .88rem; font-weight: 600;
    cursor: pointer; text-decoration: none; transition: border-color .2s, color .2s;
}
.btn-cancel-admin:hover { border-color: var(--muted); color: var(--text); }

/* Alert */
.alert-admin {
    background: rgba(255,111,0,.12); border: 1px solid rgba(255,111,0,.3);
    color: var(--orange); border-radius: 8px; padding: 12px 16px;
    font-size: .88rem; margin-bottom: 20px;
}

/* Badges */
.badge-status {
    display: inline-block; padding: 3px 8px; border-radius: 4px;
    font-size: .72rem; font-weight: 700; letter-spacing: .5px; text-transform: uppercase;
}
.badge-booked  { background: rgba(76,175,80,.15);  color: #4caf50; }
.badge-premium { background: rgba(155,89,182,.15); color: #c9a0ff; }
.badge-paid    { background: rgba(33,150,243,.15); color: #64b5f6; }
.badge-cancelled { background: rgba(231,76,60,.15); color: #e74c3c; }

/* Top movies */
.top-movie-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border); }
.top-movie-row:last-child { border: none; }
.top-movie-name { font-weight: 600; font-size: .9rem; }
.top-movie-stats { display: flex; gap: 16px; font-size: .82rem; color: var(--muted); }
.top-movie-revenue { color: var(--orange); font-weight: 700; font-size: .95rem; }

/* Section card */
.section-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; margin-bottom: 28px; }
.section-card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
.section-card-header h3 { font-family: 'Bebas Neue', sans-serif; font-size: 1.2rem; letter-spacing: 1px; margin: 0; }
.section-card-body { padding: 0; }

@media (max-width: 768px) {
    .sidebar { display: none; }
    .form-row.cols-2, .form-row.cols-3, .form-row.cols-4 { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<div class="admin-wrap">

<!-- ── SIDEBAR ─────────────────────────────────────── -->
<aside class="sidebar">
    <a href="admin.php?tab=stats" class="sidebar-brand">
        🎬 MyCinema
        <span>Admin Panel</span>
    </a>
    <nav style="flex:1">
        <a href="admin.php?tab=stats"     class="nav-link <?= $tab==='stats'    ?'active':'' ?>"><span class="nav-icon">📊</span> Statistics</a>
        <a href="admin.php?tab=schedule"  class="nav-link <?= $tab==='schedule' ?'active':'' ?>"><span class="nav-icon">📅</span> Schedule</a>
        <a href="admin.php?tab=movies"    class="nav-link <?= $tab==='movies'   ?'active':'' ?>"><span class="nav-icon">🎬</span> Movies</a>
        <a href="admin.php?tab=sessions"  class="nav-link <?= $tab==='sessions' ?'active':'' ?>"><span class="nav-icon">🕐</span> Sessions</a>
        <a href="admin.php?tab=bookings"  class="nav-link <?= $tab==='bookings' ?'active':'' ?>"><span class="nav-icon">🎟️</span> Bookings</a>
    </nav>
    <div class="sidebar-footer">
        <div style="color:var(--muted);font-size:.8rem;margin-bottom:6px">
            👤 <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Admin') ?>
        </div>
        <a href="index.php">← Back to site</a><br>
        <a href="logout.php" style="color:#e74c3c;margin-top:4px;display:inline-block">🚪 Logout</a>
    </div>
</aside>

<!-- ── MAIN CONTENT ────────────────────────────────── -->
<main class="admin-main">

<?php if ($msg): ?>
    <div class="alert-admin">✓ <?= $msg ?></div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════
     STATS TAB
════════════════════════════════════════════════════ -->
<?php if ($tab === 'stats'): ?>
<h1 class="page-title">Statistics</h1>
<p class="page-subtitle">Overview of your cinema performance</p>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value"><?= number_format($stats['totalRevenue'], 0) ?></div>
        <div class="stat-sub">€ earned</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Bookings</div>
        <div class="stat-value"><?= $stats['totalBookings'] ?></div>
        <div class="stat-sub">tickets sold</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Movies</div>
        <div class="stat-value"><?= $stats['totalMovies'] ?></div>
        <div class="stat-sub">in catalogue</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Users</div>
        <div class="stat-value"><?= $stats['totalUsers'] ?></div>
        <div class="stat-sub">registered</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Upcoming</div>
        <div class="stat-value"><?= $stats['upcomingSess'] ?></div>
        <div class="stat-sub">sessions ahead</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

    <!-- Top movies -->
    <div class="section-card">
        <div class="section-card-header">
            <h3>🏆 Top Movies by Revenue</h3>
        </div>
        <div class="section-card-body" style="padding:16px 20px">
            <?php if ($stats['topMovies']): ?>
                <?php foreach ($stats['topMovies'] as $i => $tm): ?>
                    <div class="top-movie-row">
                        <div>
                            <span style="color:var(--muted);font-size:.8rem;margin-right:8px">#<?= $i+1 ?></span>
                            <span class="top-movie-name"><?= htmlspecialchars($tm['title']) ?></span>
                        </div>
                        <div style="text-align:right">
                            <div class="top-movie-revenue"><?= number_format($tm['revenue'], 2) ?> €</div>
                            <div style="color:var(--muted);font-size:.78rem"><?= $tm['tickets'] ?> tickets</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:var(--muted);font-size:.88rem">No bookings yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Last 7 days -->
    <div class="section-card">
        <div class="section-card-header">
            <h3>📅 Last 7 Days</h3>
        </div>
        <div class="section-card-body" style="padding:16px 20px">
            <?php if ($stats['revenueByDay']): ?>
                <?php foreach ($stats['revenueByDay'] as $day): ?>
                    <div class="top-movie-row">
                        <div style="font-size:.88rem"><?= date('d M', strtotime($day['day'])) ?></div>
                        <div style="text-align:right">
                            <div class="top-movie-revenue"><?= number_format($day['revenue'], 2) ?> €</div>
                            <div style="color:var(--muted);font-size:.78rem"><?= $day['tickets'] ?> tickets</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:var(--muted);font-size:.88rem">No data for last 7 days.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════
     MOVIES TAB
════════════════════════════════════════════════════ -->
<?php elseif ($tab === 'movies'): ?>
<h1 class="page-title"><?= $editMovie ? 'Edit Movie' : 'Movies' ?></h1>
<p class="page-subtitle"><?= $editMovie ? 'Update movie details' : 'Manage your movie catalogue' ?></p>

<!-- Form -->
<div class="admin-form">
    <h3><?= $editMovie ? '✏️ Edit Movie' : '➕ Add New Movie' ?></h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= $editMovie ? 'edit_movie' : 'add_movie' ?>">
        <?php if ($editMovie): ?>
            <input type="hidden" name="id" value="<?= (int)$editMovie['id'] ?>">
        <?php endif; ?>

        <div class="form-row cols-2">
            <div>
                <label class="form-label">Title *</label>
                <input class="form-control" name="title" placeholder="Movie title" required
                       value="<?= htmlspecialchars($editMovie['title'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Genre</label>
                <input class="form-control" name="genre" placeholder="e.g. Action, Comedy"
                       value="<?= htmlspecialchars($editMovie['genre'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row cols-2">
            <div>
                <label class="form-label">Duration (min)</label>
                <input class="form-control" type="number" name="duration" placeholder="120"
                       value="<?= htmlspecialchars($editMovie['duration'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Release Date</label>
                <input class="form-control" type="date" name="release_date"
                       value="<?= htmlspecialchars($editMovie['release_date'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row" style="margin-bottom:14px">
            <div>
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3"
                          placeholder="Movie description..."><?= htmlspecialchars($editMovie['description'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="form-row cols-2">
            <div>
                <label class="form-label">Poster Image <?= $editMovie ? '(leave empty to keep current)' : '' ?></label>
                <input class="form-control" type="file" name="poster" accept="image/*">
            </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:6px">
            <button type="submit" class="btn-primary-admin">
                <?= $editMovie ? '💾 Save Changes' : '➕ Add Movie' ?>
            </button>
            <?php if ($editMovie): ?>
                <a href="admin.php?tab=movies" class="btn-cancel-admin">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Movie list -->
<div class="section-card">
    <div class="section-card-header">
        <h3>🎬 All Movies (<?= count($movies) ?>)</h3>
    </div>
    <div class="section-card-body">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Poster</th>
                    <th>Title</th>
                    <th>Genre</th>
                    <th>Duration</th>
                    <th>Release</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movies as $m): ?>
                    <tr>
                        <td style="color:var(--muted)"><?= $m['id'] ?></td>
                        <td>
                            <?php if (!empty($m['poster'])): ?>
                                <img src="data:image/jpeg;base64,<?= base64_encode($m['poster']) ?>" width="40" height="56">
                            <?php else: ?>
                                <span style="color:var(--muted);font-size:.78rem">No poster</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars($m['title']) ?></strong></td>
                        <td style="color:var(--muted)"><?= htmlspecialchars($m['genre']) ?></td>
                        <td style="color:var(--muted)"><?= $m['duration'] ?> min</td>
                        <td style="color:var(--muted)"><?= $m['release_date'] ?></td>
                        <td>
                            <div style="display:flex;gap:6px">
                                <a href="admin.php?tab=movies&edit_movie=<?= $m['id'] ?>" class="btn-sm-edit">Edit</a>
                                <a href="admin.php?delete_movie=<?= $m['id'] ?>" class="btn-sm-delete"
                                   onclick="return confirm('Delete <?= htmlspecialchars(addslashes($m['title'])) ?>?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ════════════════════════════════════════════════════
     SESSIONS TAB
════════════════════════════════════════════════════ -->
<?php elseif ($tab === 'sessions'): ?>
<h1 class="page-title">Sessions</h1>
<p class="page-subtitle">Manage screening times and prices</p>

<!-- Form -->
<div class="admin-form">
    <h3><?= $editSession ? '✏️ Edit Session' : '➕ Add New Session' ?></h3>
    <form method="POST">
        <input type="hidden" name="action" value="<?= $editSession ? 'edit_session' : 'add_session' ?>">
        <?php if ($editSession): ?>
            <input type="hidden" name="id" value="<?= (int)$editSession['id'] ?>">
        <?php endif; ?>

        <div class="form-row cols-2">
            <div>
                <label class="form-label">Movie *</label>
                <select class="form-select" name="movie_id" required>
                    <option value="">— Select movie —</option>
                    <?php foreach ($movies as $m): ?>
                        <option value="<?= $m['id'] ?>"
                            <?= ($editSession && $editSession['movie_id'] == $m['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Hall *</label>
                <select class="form-select" name="hall_id" required>
                    <option value="">— Select hall —</option>
                    <?php foreach ($halls as $h): ?>
                        <option value="<?= $h['id'] ?>"
                            <?= ($editSession && $editSession['hall_id'] == $h['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($h['name']) ?> (<?= $h['total_rows'] ?>×<?= $h['seats_per_row'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row cols-2">
            <div>
                <label class="form-label">Date & Time *</label>
                <input class="form-control" type="datetime-local" name="show_time" required
                       value="<?= $editSession ? date('Y-m-d\TH:i', strtotime($editSession['show_time'])) : '' ?>">
            </div>
            <div>
                <label class="form-label">Ticket Price (€) *</label>
                <input class="form-control" type="number" step="0.01" name="price" placeholder="150.00" required
                       value="<?= htmlspecialchars($editSession['price'] ?? '') ?>">
            </div>
        </div>
        <div style="display:flex;gap:10px">
            <button type="submit" class="btn-primary-admin">
                <?= $editSession ? '💾 Save Changes' : '➕ Add Session' ?>
            </button>
            <?php if ($editSession): ?>
                <a href="admin.php?tab=sessions" class="btn-cancel-admin">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Sessions list -->
<div class="section-card">
    <div class="section-card-header">
        <h3>🕐 All Sessions (<?= count($sessions) ?>)</h3>
    </div>
    <div class="section-card-body">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Movie</th>
                    <th>Hall</th>
                    <th>Date & Time</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $s): ?>
                    <tr>
                        <td style="color:var(--muted)"><?= $s['id'] ?></td>
                        <td><strong><?= htmlspecialchars($s['movie_title']) ?></strong></td>
                        <td style="color:var(--muted)"><?= htmlspecialchars($s['hall_name']) ?></td>
                        <td>
                            <?php
                            $isPast = strtotime($s['show_time']) < time();
                            echo '<span style="color:' . ($isPast ? 'var(--muted)' : 'var(--text)') . '">';
                            echo date('d M Y, H:i', strtotime($s['show_time']));
                            echo '</span>';
                            if ($isPast) echo ' <span style="color:#e74c3c;font-size:.75rem">past</span>';
                            ?>
                        </td>
                        <td style="color:var(--orange);font-weight:600"><?= number_format($s['price'], 2) ?> €</td>
                        <td>
                            <div style="display:flex;gap:6px">
                                <a href="admin.php?tab=sessions&edit_session=<?= $s['id'] ?>" class="btn-sm-edit">Edit</a>
                                <a href="admin.php?delete_session=<?= $s['id'] ?>" class="btn-sm-delete"
                                   onclick="return confirm('Delete this session?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ════════════════════════════════════════════════════
     BOOKINGS TAB
════════════════════════════════════════════════════ -->
<?php elseif ($tab === 'bookings'): ?>
<h1 class="page-title">Bookings</h1>
<p class="page-subtitle">All ticket reservations — <?= count($bookings) ?> total</p>

<div class="section-card">
    <div class="section-card-header">
        <h3>🎟️ All Bookings</h3>
        <span style="color:var(--muted);font-size:.82rem">Total revenue: <strong style="color:var(--orange)"><?= number_format($stats['totalRevenue'], 2) ?> €</strong></span>
    </div>
    <div class="section-card-body">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Movie</th>
                    <th>Show Time</th>
                    <th>Seat</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Booked At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td style="color:var(--muted)"><?= $b['id'] ?></td>
                        <td><strong><?= htmlspecialchars($b['name']) ?></strong></td>
                        <td style="color:var(--muted);font-size:.82rem"><?= htmlspecialchars($b['email']) ?></td>
                        <td><?= htmlspecialchars($b['movie_title']) ?></td>
                        <td style="color:var(--muted);font-size:.82rem"><?= date('d M Y, H:i', strtotime($b['show_time'])) ?></td>
                        <td style="color:var(--muted)">R<?= $b['seat_row'] ?> S<?= $b['seat_number'] ?></td>
                        <td style="color:var(--orange);font-weight:600"><?= number_format($b['total_price'], 2) ?> €</td>
                        <td><span class="badge-status badge-<?= $b['status'] ?>"><?= $b['status'] ?></span></td>
                        <td style="color:var(--muted);font-size:.78rem"><?= date('d M Y', strtotime($b['created_at'])) ?></td>
                        <td>
                            <a href="admin.php?cancel_booking=<?= $b['id'] ?>&tab=bookings"
                               class="btn-sm-delete"
                               onclick="return confirm('Cancel this booking? Seat will be freed.')">Cancel</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($tab === 'schedule'):
$schedule = getSchedule();
// Group by date
$byDate = [];
foreach ($schedule as $s) {
    $day = date('Y-m-d', strtotime($s['show_time']));
    $byDate[$day][] = $s;
}
?>
<h1 class="page-title">Schedule</h1>
<p class="page-subtitle">Upcoming sessions in chronological order</p>

<?php if (empty($schedule)): ?>
    <div style="color:var(--muted);padding:40px 0;text-align:center">No upcoming sessions.</div>
<?php else: ?>
    <?php foreach ($byDate as $day => $daySessions): ?>

        <!-- Day header -->
        <div style="display:flex;align-items:center;gap:14px;margin:28px 0 14px">
            <div style="background:var(--orange);color:#fff;border-radius:8px;padding:6px 14px;font-family:'Bebas Neue',sans-serif;font-size:1.1rem;letter-spacing:1px;white-space:nowrap">
                <?= date('d M Y', strtotime($day)) ?>
                <?php if ($day === date('Y-m-d')): ?>
                    <span style="font-size:.75rem;background:rgba(255,255,255,.2);padding:2px 7px;border-radius:4px;margin-left:6px">TODAY</span>
                <?php elseif ($day === date('Y-m-d', strtotime('+1 day'))): ?>
                    <span style="font-size:.75rem;background:rgba(255,255,255,.2);padding:2px 7px;border-radius:4px;margin-left:6px">TOMORROW</span>
                <?php endif; ?>
            </div>
            <div style="flex:1;height:1px;background:var(--border)"></div>
            <div style="color:var(--muted);font-size:.8rem;white-space:nowrap"><?= count($daySessions) ?> session<?= count($daySessions)>1?'s':'' ?></div>
        </div>

        <!-- Sessions for this day -->
        <?php foreach ($daySessions as $s): ?>
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:16px 20px;margin-bottom:10px;display:flex;align-items:center;gap:16px;transition:border-color .2s"
                 onmouseover="this.style.borderColor='var(--orange)'" onmouseout="this.style.borderColor='var(--border)'">

                <!-- Poster -->
                <div style="flex-shrink:0">
                    <?php if (!empty($s['poster'])): ?>
                        <img src="data:image/jpeg;base64,<?= base64_encode($s['poster']) ?>"
                             style="width:44px;height:62px;object-fit:cover;border-radius:5px;border:1px solid var(--border)">
                    <?php else: ?>
                        <div style="width:44px;height:62px;background:#111;border-radius:5px;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:1.2rem">🎬</div>
                    <?php endif; ?>
                </div>

                <!-- Time -->
                <div style="flex-shrink:0;text-align:center;min-width:56px">
                    <div style="font-family:'Bebas Neue',sans-serif;font-size:1.6rem;color:var(--orange);line-height:1">
                        <?= date('H:i', strtotime($s['show_time'])) ?>
                    </div>
                </div>

                <!-- Movie info -->
                <div style="flex:1;min-width:0">
                    <div style="font-weight:600;font-size:.95rem;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        <?= htmlspecialchars($s['title']) ?>
                    </div>
                    <div style="color:var(--muted);font-size:.8rem;margin-top:3px;display:flex;gap:12px;flex-wrap:wrap">
                        <span>🏛 <?= htmlspecialchars($s['hall_name']) ?></span>
                        <?php if ($s['genre']): ?>
                            <span>🎭 <?= htmlspecialchars($s['genre']) ?></span>
                        <?php endif; ?>
                        <span>⏱ <?= $s['duration'] ?> min</span>
                    </div>
                </div>

                <!-- Price -->
                <div style="flex-shrink:0;text-align:right">
                    <div style="color:var(--orange);font-weight:700;font-size:1rem"><?= number_format($s['price'], 2) ?> €</div>
                    <div style="color:var(--muted);font-size:.72rem;margin-top:2px">standard</div>
                </div>

                <!-- Actions -->
                <div style="flex-shrink:0;display:flex;gap:6px">
                    <a href="admin.php?tab=sessions&edit_session=<?= $s['id'] ?>" class="btn-sm-edit">Edit</a>
                    <a href="admin.php?delete_session=<?= $s['id'] ?>" class="btn-sm-delete"
                       onclick="return confirm('Delete this session?')">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>

    <?php endforeach; ?>
<?php endif; ?>

<?php endif; ?>

</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>