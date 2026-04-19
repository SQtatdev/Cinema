<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// ── CSRF token ──────────────────────────────────────────────
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ── Параметры ───────────────────────────────────────────────
$session_id = filter_input(INPUT_GET, 'session_id', FILTER_VALIDATE_INT);
if (!$session_id) {
    http_response_code(400);
    die('Session not found.');
}

// ── Данные сессии + зала ─────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT s.*, m.title, h.name AS hall_name,
           h.total_rows, h.seats_per_row
    FROM sessions s
    JOIN movies  m ON s.movie_id = m.id
    JOIN halls   h ON s.hall_id  = h.id
    WHERE s.id = ?
");
$stmt->execute([$session_id]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$session) {
    http_response_code(404);
    die('Session not found.');
}

$totalRows   = (int)$session['total_rows'];
$seatsPerRow = (int)$session['seats_per_row'];

// ── Генерировать места, если их ещё нет ─────────────────────
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM seats WHERE session_id = ?");
$countStmt->execute([$session_id]);
if ((int)$countStmt->fetchColumn() === 0) {
    $ins = $pdo->prepare("
        INSERT INTO seats (session_id, `row_number`, seat_number, `type`)
        VALUES (?, ?, ?, ?)
    ");
    for ($r = 1; $r <= $totalRows; $r++) {
        $type = ($r <= 2) ? 'premium' : 'standard';
        for ($c = 1; $c <= $seatsPerRow; $c++) {
            $ins->execute([$session_id, $r, $c, $type]);
        }
    }
}

// ── Загрузить все места сессии ───────────────────────────────
$seatsStmt = $pdo->prepare("
    SELECT id, `row_number`, seat_number, `type`, status
    FROM seats
    WHERE session_id = ?
    ORDER BY `row_number`, seat_number
");
$seatsStmt->execute([$session_id]);
$seats = $seatsStmt->fetchAll(PDO::FETCH_ASSOC);

$seatsJson    = json_encode($seats, JSON_HEX_TAG);
$basePrice    = (float)$session['price'];
$premiumPrice = round($basePrice * 1.20, 2);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Выбор мест — <?= htmlspecialchars($session['title']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/styles/style_booking.css">
<style>
.screen {
    border-top: 3px solid rgba(255,255,255,.5);
    border-radius: 50%;
    height: 16px;
    margin: 0 80px 28px;
    text-align: center;
    font-size: 11px;
    color: #777;
    letter-spacing: 3px;
    padding-top: 3px;
}
.hall-wrap { overflow-x: auto; padding-bottom: 8px; }
.seat-row {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-bottom: 5px;
    align-items: center;
}
.row-label {
    width: 20px; flex-shrink: 0;
    text-align: right; margin-right: 6px;
    font-size: 11px; color: #666;
}
.seat {
    width: 28px; height: 26px;
    border-radius: 5px 5px 3px 3px;
    border: none; cursor: pointer;
    font-size: 10px;
    transition: transform .1s;
}
.seat:hover:not([disabled]) { transform: scale(1.18); }
.seat.std   { background: #3a3f4b; color: #bbb; }
.seat.prm   { background: #4a3560; color: #c9a0ff; }
.seat.taken { background: #222; color: #444; cursor: not-allowed; }
.seat.sel-std { background: #e07b39; color: #fff; }
.seat.sel-prm { background: #9b59b6; color: #fff; }

.legend-box {
    display: inline-block;
    width: 16px; height: 16px;
    border-radius: 3px 3px 2px 2px;
    vertical-align: middle; margin-right: 4px;
}
#basket-card { position: sticky; top: 80px; }
.pill {
    display: inline-flex; align-items: center; gap: 4px;
    background: rgba(255,255,255,.07);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 20px; padding: 2px 10px 2px 8px;
    font-size: 13px; margin: 2px;
}
.pill .rm { cursor: pointer; opacity: .55; font-size: 16px; line-height: 1; }
.pill .rm:hover { opacity: 1; }
</style>
</head>
<body>

<div class="container booking-container mt-5">
    <a href="movie.php?id=<?= (int)$session['movie_id'] ?>" class="btn btn-sm btn-outline-light mb-4">← Назад</a>
    <h1 class="text-orange mb-1">Выбор мест</h1>

    <div class="summary-box mb-4">
        <h4><?= htmlspecialchars($session['title']) ?></h4>
        <p class="mb-1"><strong>Date and time:</strong> <?= date('d M Y, H:i', strtotime($session['show_time'])) ?></p>
        <p class="mb-1"><strong>Hall:</strong> <?= htmlspecialchars($session['hall_name']) ?>
            <span class="text-muted small">(<?= $totalRows ?> rows × <?= $seatsPerRow ?> seats)</span></p>
        <p class="mb-0">
            <strong>Standard:</strong> <?= number_format($basePrice, 2) ?> €&nbsp;&nbsp;
            <strong>Premium (rows 1–2):</strong> <?= number_format($premiumPrice, 2) ?> €
        </p>
    </div>

    <div class="row g-4">

        <!-- ── ЗАЛ ──────────────────────────────────────────── -->
        <div class="col-lg-8">
            <div class="d-flex flex-wrap gap-3 mb-3 small">
                <span><span class="legend-box" style="background:#3a3f4b"></span>Standard</span>
                <span><span class="legend-box" style="background:#4a3560"></span>Premium</span>
                <span><span class="legend-box" style="background:#e07b39"></span>Selected</span>
                <span><span class="legend-box" style="background:#222"></span>Occupied</span>
            </div>
            <div class="screen">Screen</div>
            <div class="hall-wrap" id="hall-grid"></div>
        </div>

        <!-- ── КОРЗИНА / ФОРМА ───────────────────────────────── -->
        <div class="col-lg-4">
            <div id="basket-card" class="summary-box">
                <h5 class="mb-3">Your order</h5>
                <div id="selected-list" class="mb-3">
                    <p class="text-muted small" id="empty-hint">Click on an available seat</p>
                </div>
                <div class="d-flex justify-content-between mb-1 small text-muted">
                    <span>Seats:</span><span id="total-count">0</span>
                </div>
                <div class="d-flex justify-content-between mb-4">
                    <strong>Total:</strong>
                    <strong id="total-price">0.00 €</strong>
                </div>

                <form action="confirm_booking.php" method="POST" id="booking-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="session_id" value="<?= (int)$session_id ?>">
                    <input type="hidden" name="seat_ids"   id="seat-ids-input" value="">

                    <div class="mb-3">
                        <label for="name" class="form-label">Your name</label>
                        <input type="text" class="form-control" id="name" name="name" required
                            value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['name']) : '' ?>">
                    </div>

                    <?php if (!isset($_SESSION['user'])): ?>
                    <div class="mb-3">
                        <label class="form-label">Booking type</label>
                        <select class="form-select" id="bookingType" name="booking_type">
                            <option value="guest">Without account</option>
                            <option value="register">Create account</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3 d-none" id="passwordBlock">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="6">
                        <div class="form-text">Minimum 6 characters.</div>
                    </div>
                    <?php endif; ?>

                    <button type="submit" id="submit-btn" class="btn btn-orange w-100" disabled>
                        Proceed to payment (0)
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<footer class="mt-5 text-center text-secondary">
    <p>© <?= date('Y') ?> MyCinema. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const SEATS      = <?= $seatsJson ?>;
const BASE_PRICE = <?= $basePrice ?>;
const PREM_PRICE = <?= $premiumPrice ?>;
const MAX_SEATS  = 10;

const selected = new Map(); // id → seatObj

// Сгруппировать по рядам
const byRow = {};
SEATS.forEach(s => (byRow[s.row_number] ??= []).push(s));

// Построить зал
const hall = document.getElementById('hall-grid');
Object.keys(byRow).sort((a, b) => +a - +b).forEach(row => {
    const rowDiv = document.createElement('div');
    rowDiv.className = 'seat-row';

    const lbl = document.createElement('span');
    lbl.className = 'row-label';
    lbl.textContent = row;
    rowDiv.appendChild(lbl);

    byRow[row].forEach(seat => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.dataset.id = seat.id;
        btn.textContent = seat.seat_number;

        const isPrem   = seat.type === 'premium';
        const isBooked = seat.status === 'booked';

        if (isBooked) {
            btn.className = 'seat taken';
            btn.disabled  = true;
            btn.title     = 'Место занято';
        } else {
            btn.className = `seat ${isPrem ? 'prm' : 'std'}`;
            btn.title     = `Ряд ${seat.row_number}, место ${seat.seat_number} — ${isPrem ? 'премиум' : 'стандарт'}`;
            btn.addEventListener('click', () => toggleSeat(seat, btn));
        }

        rowDiv.appendChild(btn);
    });

    hall.appendChild(rowDiv);
});

function toggleSeat(seat, btn) {
    if (selected.has(seat.id)) {
        selected.delete(seat.id);
        btn.className = `seat ${seat.type === 'premium' ? 'prm' : 'std'}`;
    } else {
        if (selected.size >= MAX_SEATS) {
            alert(`Maximum ${MAX_SEATS} seats can be selected at once.`);
            return;
        }
        selected.set(seat.id, seat);
        btn.className = `seat ${seat.type === 'premium' ? 'sel-prm' : 'sel-std'}`;
    }
    renderBasket();
}

function renderBasket() {
    const list  = document.getElementById('selected-list');
    const count = document.getElementById('total-count');
    const price = document.getElementById('total-price');
    const input = document.getElementById('seat-ids-input');
    const btn   = document.getElementById('submit-btn');

    list.innerHTML = '';

    if (selected.size === 0) {
        const p = document.createElement('p');
        p.className = 'text-muted small';
        p.textContent = 'Click on an available seat';
        list.appendChild(p);
        count.textContent = '0';
        price.textContent = '0.00 €';
        input.value = '';
        btn.disabled = true;
        btn.textContent = 'Proceed to payment (0)';
        return;
    }

    let total = 0;
    const ids = [];

    selected.forEach((seat, id) => {
        total += seat.type === 'premium' ? PREM_PRICE : BASE_PRICE;
        ids.push(id);

        const pill = document.createElement('span');
        pill.className = 'pill';
        pill.innerHTML = `
            <span>${seat.row_number}×${seat.seat_number}${seat.type === 'premium' ? ' ★' : ''}</span>
            <span class="rm" data-id="${id}">×</span>`;
        list.appendChild(pill);
    });

    list.querySelectorAll('.rm').forEach(rm => {
        rm.addEventListener('click', () => {
            const id   = +rm.dataset.id;
            const seat = selected.get(id);
            selected.delete(id);
            const seatBtn = hall.querySelector(`button[data-id="${id}"]`);
            if (seatBtn && seat) {
                seatBtn.className = `seat ${seat.type === 'premium' ? 'prm' : 'std'}`;
            }
            renderBasket();
        });
    });

    count.textContent = selected.size;
    price.textContent = total.toFixed(2) + ' €';
    input.value = ids.join(',');
    btn.disabled = false;
    btn.textContent = `Proceed to payment (${selected.size})`;
}

document.getElementById('booking-form').addEventListener('submit', e => {
    if (selected.size === 0) {
        e.preventDefault();
        alert('Выберите хотя бы одно место.');
    }
});

document.getElementById('bookingType')?.addEventListener('change', function () {
    document.getElementById('passwordBlock').classList.toggle('d-none', this.value !== 'register');
});
</script>
</body>
</html>