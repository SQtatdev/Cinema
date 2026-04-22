<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

$stmt = $pdo->prepare("
    SELECT b.*, m.title, m.id AS movie_id, m.poster,
           s.show_time, h.name AS hall_name
    FROM bookings b
    JOIN sessions s ON b.session_id = s.id
    JOIN movies   m ON s.movie_id   = m.id
    JOIN halls    h ON s.hall_id    = h.id
    WHERE b.user_id = ?
    ORDER BY s.show_time DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();

// Group by movie+session so multiple seats show together
$grouped = [];
foreach ($bookings as $b) {
    $key = $b['session_id'];
    if (!isset($grouped[$key])) {
        $grouped[$key] = [
            'title'      => $b['title'],
            'movie_id'   => $b['movie_id'],
            'poster'     => $b['poster'],
            'show_time'  => $b['show_time'],
            'hall_name'  => $b['hall_name'],
            'created_at' => $b['created_at'],
            'seats'      => [],
            'total'      => 0,
        ];
    }
    $grouped[$key]['seats'][] = [
        'row'    => $b['seat_row'],
        'seat'   => $b['seat_number'],
        'status' => $b['status'],
        'price'  => $b['total_price'],
    ];
    $grouped[$key]['total'] += $b['total_price'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Bookings — MyCinema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
    :root {
        --orange:     #ff6f00;
        --orange-dim: #cc5800;
        --bg:         #0e0e0e;
        --surface:    #171717;
        --border:     #262626;
        --text:       #f0f0f0;
        --muted:      #777;
    }
    *, *::before, *::after { box-sizing: border-box; }
    body { background: var(--bg); color: var(--text); font-family: 'DM Sans', Arial, sans-serif; min-height: 100vh; }

    .page-heading { margin-bottom: 32px; }
    .page-heading .label-tag { font-size: .7rem; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; color: var(--orange); }
    .page-heading h1 { font-family: 'Bebas Neue', sans-serif; font-size: clamp(2rem,5vw,2.8rem); letter-spacing: 2px; margin: 4px 0 0; line-height: 1; }

    /* Booking card */
    .booking-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        transition: border-color .2s, box-shadow .2s;
        margin-bottom: 16px;
    }
    .booking-card:hover {
        border-color: var(--orange);
        box-shadow: 0 6px 24px rgba(255,111,0,.12);
    }
    .booking-card.past { opacity: .6; }
    .booking-card.past:hover { opacity: .8; }

    /* Poster strip */
    .booking-poster {
        width: 80px;
        flex-shrink: 0;
        background: #111;
        position: relative;
    }
    .booking-poster img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center top;
        display: block;
    }
    .booking-poster .no-poster {
        width: 100%; height: 100%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem;
    }

    /* Content */
    .booking-content {
        flex: 1;
        padding: 18px 20px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        min-width: 0;
    }
    .booking-title {
        font-weight: 700;
        font-size: 1rem;
        color: var(--text);
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .booking-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        font-size: .83rem;
        color: var(--muted);
    }
    .booking-meta span { display: flex; align-items: center; gap: 5px; }

    /* Seats */
    .seats-row {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .seat-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: .78rem;
        font-weight: 600;
    }
    .seat-pill.premium  { background: rgba(155,89,182,.15); color: #c9a0ff; border: 1px solid rgba(155,89,182,.3); }
    .seat-pill.standard { background: rgba(255,111,0,.1);   color: var(--orange); border: 1px solid rgba(255,111,0,.25); }
    .seat-pill.booked   { background: rgba(76,175,80,.1);   color: #4caf50; border: 1px solid rgba(76,175,80,.25); }

    /* Right side */
    .booking-right {
        padding: 18px 20px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        justify-content: space-between;
        flex-shrink: 0;
        border-left: 1px solid var(--border);
        min-width: 130px;
        gap: 10px;
    }
    .booking-total {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 1.6rem;
        color: var(--orange);
        line-height: 1;
    }
    .booking-total-label {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: var(--muted);
        margin-bottom: 2px;
    }
    .tag-upcoming {
        background: rgba(76,175,80,.12);
        color: #4caf50;
        border: 1px solid rgba(76,175,80,.25);
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        padding: 3px 8px;
        border-radius: 4px;
    }
    .tag-past {
        background: rgba(119,119,119,.12);
        color: var(--muted);
        border: 1px solid rgba(119,119,119,.2);
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        padding: 3px 8px;
        border-radius: 4px;
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 80px 20px;
        color: var(--muted);
    }
    .empty-state .empty-icon { font-size: 4rem; margin-bottom: 16px; }
    .empty-state h3 { font-family: 'Bebas Neue', sans-serif; font-size: 1.8rem; letter-spacing: 2px; color: var(--text); margin-bottom: 8px; }

    /* Stats bar */
    .stats-bar {
        display: flex;
        gap: 20px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 16px 20px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }
    .stats-bar-item .s-label { font-size: .68rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--muted); }
    .stats-bar-item .s-value { font-family: 'Bebas Neue', sans-serif; font-size: 1.5rem; color: var(--orange); line-height: 1.1; }

    .btn-back-link {
        display: inline-flex; align-items: center; gap: 6px;
        color: var(--muted); font-size: .85rem; text-decoration: none;
        transition: color .2s; margin-bottom: 24px;
    }
    .btn-back-link:hover { color: var(--orange); }

    .btn-browse {
        display: inline-block;
        background: var(--orange); color: #fff;
        padding: 11px 28px; border-radius: 8px;
        font-weight: 700; font-size: .9rem;
        text-decoration: none; transition: background .2s;
    }
    .btn-browse:hover { background: var(--orange-dim); color: #fff; }

    footer { background: #111; border-top: 1px solid var(--border); padding: 22px; text-align: center; margin-top: 60px; color: var(--muted); font-size: .84rem; }

    @media (max-width: 576px) {
        .booking-poster { width: 56px; }
        .booking-right { min-width: 90px; padding: 12px; }
        .booking-total { font-size: 1.2rem; }
    }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container py-5">

    <a href="index.php" class="btn-back-link">← Back to Movies</a>

    <div class="page-heading">
        <span class="label-tag">Account</span>
        <h1>My Bookings</h1>
    </div>

    <?php if (empty($grouped)): ?>

        <div class="empty-state">
            <div class="empty-icon">🎟️</div>
            <h3>No Bookings Yet</h3>
            <p style="margin-bottom:24px">You haven't booked any tickets yet. Browse what's showing now!</p>
            <a href="index.php" class="btn-browse">Browse Movies</a>
        </div>

    <?php else: ?>

        <?php
        $totalSpent   = array_sum(array_column($grouped, 'total'));
        $totalTickets = array_sum(array_map(fn($g) => count($g['seats']), $grouped));
        $upcoming     = array_filter($grouped, fn($g) => strtotime($g['show_time']) > time());
        ?>

        <div class="stats-bar">
            <div class="stats-bar-item">
                <div class="s-label">Total Bookings</div>
                <div class="s-value"><?= count($grouped) ?></div>
            </div>
            <div class="stats-bar-item">
                <div class="s-label">Tickets</div>
                <div class="s-value"><?= $totalTickets ?></div>
            </div>
            <div class="stats-bar-item">
                <div class="s-label">Total Spent</div>
                <div class="s-value"><?= number_format($totalSpent, 0) ?> €</div>
            </div>
            <div class="stats-bar-item">
                <div class="s-label">Upcoming</div>
                <div class="s-value"><?= count($upcoming) ?></div>
            </div>
        </div>

        <?php foreach ($grouped as $session_id => $g):
            $isPast = strtotime($g['show_time']) < time();
            if (!empty($g['poster'])) {
                $posterSrc = 'data:image/jpeg;base64,' . base64_encode($g['poster']);
            } else {
                $posterSrc = 'assets/posters/' . (int)$g['movie_id'] . '.jpg';
            }
        ?>
        <div class="booking-card <?= $isPast ? 'past' : '' ?>">

            <!-- Poster -->
            <div class="booking-poster">
                <img src="<?= $posterSrc ?>"
                     alt="<?= htmlspecialchars($g['title']) ?>"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                <div class="no-poster" style="display:none">🎬</div>
            </div>

            <!-- Content -->
            <div class="booking-content">
                <h3 class="booking-title"><?= htmlspecialchars($g['title']) ?></h3>

                <div class="booking-meta">
                    <span>📅 <?= date('d M Y', strtotime($g['show_time'])) ?></span>
                    <span>🕐 <?= date('H:i', strtotime($g['show_time'])) ?></span>
                    <span>🏛 <?= htmlspecialchars($g['hall_name']) ?></span>
                    <span style="color:var(--muted);font-size:.75rem">Booked <?= date('d M Y', strtotime($g['created_at'])) ?></span>
                </div>

                <div class="seats-row">
                    <?php foreach ($g['seats'] as $seat):
                        $cls = $seat['status'] === 'premium' ? 'premium' : ($seat['status'] === 'booked' ? 'booked' : 'standard');
                    ?>
                        <span class="seat-pill <?= $cls ?>">
                            <?= $seat['status'] === 'premium' ? '★' : '🪑' ?>
                            R<?= $seat['row'] ?> S<?= $seat['seat'] ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right -->
            <div class="booking-right">
                <span class="<?= $isPast ? 'tag-past' : 'tag-upcoming' ?>">
                    <?= $isPast ? 'Past' : 'Upcoming' ?>
                </span>
                <div style="text-align:right">
                    <div class="booking-total-label">Total</div>
                    <div class="booking-total"><?= number_format($g['total'], 2) ?> €</div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    <?php endif; ?>
</div>

<footer>
    <p>© <?= date('Y') ?> MyCinema. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>