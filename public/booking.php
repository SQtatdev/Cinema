<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$session_id = filter_input(INPUT_GET, 'session_id', FILTER_VALIDATE_INT);
if (!$session_id) { http_response_code(400); die('Session not found.'); }

$stmt = $pdo->prepare("
    SELECT s.*, m.title, m.id AS movie_id, h.name AS hall_name,
           h.total_rows, h.seats_per_row
    FROM sessions s
    JOIN movies  m ON s.movie_id = m.id
    JOIN halls   h ON s.hall_id  = h.id
    WHERE s.id = ?
");
$stmt->execute([$session_id]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$session) { http_response_code(404); die('Session not found.'); }

$totalRows   = (int)$session['total_rows'];
$seatsPerRow = (int)$session['seats_per_row'];

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM seats WHERE session_id = ?");
$countStmt->execute([$session_id]);
if ((int)$countStmt->fetchColumn() === 0) {
    $ins = $pdo->prepare("INSERT INTO seats (session_id, `row_number`, seat_number, `type`) VALUES (?, ?, ?, ?)");
    for ($r = 1; $r <= $totalRows; $r++) {
        $type = ($r <= 2) ? 'premium' : 'standard';
        for ($c = 1; $c <= $seatsPerRow; $c++) {
            $ins->execute([$session_id, $r, $c, $type]);
        }
    }
}

$seatsStmt = $pdo->prepare("
    SELECT id, `row_number`, seat_number, `type`, status
    FROM seats WHERE session_id = ?
    ORDER BY `row_number`, seat_number
");
$seatsStmt->execute([$session_id]);
$seats = $seatsStmt->fetchAll(PDO::FETCH_ASSOC);

$seatsJson    = json_encode($seats, JSON_HEX_TAG);
$basePrice    = (float)$session['price'];
$premiumPrice = round($basePrice * 1.20, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Select Seats — <?= htmlspecialchars($session['title']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/styles/style_booking.css?v=3">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container booking-container py-5">
    <a href="movie.php?id=<?= (int)$session['movie_id'] ?>" class="btn-back mb-4 d-inline-flex">← Back</a>
    <h1 class="text-orange mb-1">Select Seats</h1>

    <div class="summary-box mb-4">
        <h4><?= htmlspecialchars($session['title']) ?></h4>
        <p class="mb-1"><strong>Date & Time:</strong> <?= date('d M Y, H:i', strtotime($session['show_time'])) ?></p>
        <p class="mb-1"><strong>Hall:</strong> <?= htmlspecialchars($session['hall_name']) ?>
            <span style="color:var(--muted);font-size:.85rem">(<?= $totalRows ?> rows × <?= $seatsPerRow ?> seats)</span></p>
        <p class="mb-0">
            <strong>Standard:</strong> <?= number_format($basePrice, 2) ?> €&nbsp;&nbsp;
            <strong>Premium (rows 1–2):</strong> <?= number_format($premiumPrice, 2) ?> €
        </p>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="d-flex flex-wrap gap-3 mb-3" style="font-size:.85rem">
                <span><span class="legend-box" style="background:#2a2a3e"></span>Standard</span>
                <span><span class="legend-box" style="background:#3a2a50"></span>Premium</span>
                <span><span class="legend-box" style="background:#ff6f00"></span>Selected</span>
                <span><span class="legend-box" style="background:#1a1a1a;border:1px solid #333"></span>Taken</span>
            </div>
            <div class="screen">SCREEN</div>
            <div class="hall-wrap" id="hall-grid"></div>
        </div>

        <div class="col-lg-4">
            <div id="basket-card" class="summary-box" style="position:sticky;top:80px">
                <h5 class="mb-3">Your Order</h5>
                <div id="selected-list" class="mb-3">
                    <p style="color:var(--muted);font-size:.85rem">Click on an available seat</p>
                </div>
                <div class="d-flex justify-content-between mb-1" style="font-size:.85rem;color:var(--muted)">
                    <span>Seats:</span><span id="total-count">0</span>
                </div>
                <div class="d-flex justify-content-between mb-4">
                    <strong>Total:</strong>
                    <strong id="total-price" style="color:#ff6f00">0.00 €</strong>
                </div>

                <form action="confirm_booking.php" method="POST" id="booking-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="session_id" value="<?= (int)$session_id ?>">
                    <input type="hidden" name="seat_ids" id="seat-ids-input" value="">

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

<footer>
    <p>© <?= date('Y') ?> MyCinema. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const SEATS      = <?= $seatsJson ?>;
const BASE_PRICE = <?= $basePrice ?>;
const PREM_PRICE = <?= $premiumPrice ?>;
const MAX_SEATS  = 10;
const selected   = new Map();

const byRow = {};
SEATS.forEach(s => (byRow[s.row_number] ??= []).push(s));

const hall = document.getElementById('hall-grid');
Object.keys(byRow).sort((a,b) => +a-+b).forEach(row => {
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
        const isPrem = seat.type === 'premium';
        if (seat.status === 'booked') {
            btn.className = 'seat taken';
            btn.disabled = true;
            btn.title = 'Seat taken';
        } else {
            btn.className = `seat ${isPrem ? 'prm' : 'std'}`;
            btn.title = `Row ${seat.row_number}, seat ${seat.seat_number} — ${isPrem ? 'premium' : 'standard'}`;
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
        if (selected.size >= MAX_SEATS) { alert(`Max ${MAX_SEATS} seats.`); return; }
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
        list.innerHTML = '<p style="color:var(--muted);font-size:.85rem">Click on an available seat</p>';
        count.textContent = '0'; price.textContent = '0.00 €';
        input.value = ''; btn.disabled = true;
        btn.textContent = 'Proceed to payment (0)'; return;
    }
    let total = 0; const ids = [];
    selected.forEach((seat, id) => {
        total += seat.type === 'premium' ? PREM_PRICE : BASE_PRICE;
        ids.push(id);
        const pill = document.createElement('span');
        pill.className = 'pill';
        pill.innerHTML = `<span>${seat.row_number}×${seat.seat_number}${seat.type==='premium'?' ★':''}</span><span class="rm" data-id="${id}">×</span>`;
        list.appendChild(pill);
    });
    list.querySelectorAll('.rm').forEach(rm => {
        rm.addEventListener('click', () => {
            const id = +rm.dataset.id; const seat = selected.get(id);
            selected.delete(id);
            const seatBtn = hall.querySelector(`button[data-id="${id}"]`);
            if (seatBtn && seat) seatBtn.className = `seat ${seat.type==='premium'?'prm':'std'}`;
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
    if (selected.size === 0) { e.preventDefault(); alert('Please select at least one seat.'); }
});
document.getElementById('bookingType')?.addEventListener('change', function() {
    document.getElementById('passwordBlock').classList.toggle('d-none', this.value !== 'register');
});
</script>
</body>
</html>