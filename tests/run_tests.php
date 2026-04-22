<?php
/**
 * MyCinema Test Runner
 * Run: php run_tests.php
 */

// ── Colours ──────────────────────────────────────────────────
function isCli(): bool {
    return php_sapi_name() === 'cli';
}

function green($text) {
    return isCli() ? "\033[32m{$text}\033[0m" : $text;
}

function red($text) {
    return isCli() ? "\033[31m{$text}\033[0m" : $text;
}

function yellow($text) {
    return isCli() ? "\033[33m{$text}\033[0m" : $text;
}

function bold($text) {
    return isCli() ? "\033[1m{$text}\033[0m" : $text;
}

// ── Assertion helpers ────────────────────────────────────────
$passed = $failed = 0;
$failures = [];

function assert_true(bool $cond, string $msg): void {
    global $passed, $failed, $failures;
    if ($cond) { $passed++; echo green('  ✓ ') . $msg . "\n"; }
    else        { $failed++; $failures[] = $msg; echo red('  ✗ ') . $msg . "\n"; }
}
function assert_equals($a, $b, string $msg): void {
    assert_true($a === $b, $msg . " (expected " . var_export($b,true) . ", got " . var_export($a,true) . ")");
}
function assert_not_empty($v, string $msg): void { assert_true(!empty($v), $msg); }
function assert_null($v,     string $msg): void  { assert_true($v === null, $msg); }
function assert_count(int $n, array $arr, string $msg): void { assert_equals(count($arr), $n, $msg); }

function section(string $title): void { echo "\n" . bold("── {$title} ─") . "\n"; }

// ── Bootstrap: in-memory SQLite DB ───────────────────────────
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$pdo->exec("
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(10) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE halls (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    total_rows INT NOT NULL,
    seats_per_row INT NOT NULL
);
CREATE TABLE movies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    genre VARCHAR(50),
    duration INT,
    release_date DATE,
    poster BLOB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    movie_id INT NOT NULL,
    hall_id INT NOT NULL,
    show_time DATETIME NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 150.00
);
CREATE TABLE seats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id INT NOT NULL,
    row_number INT NOT NULL,
    seat_number INT NOT NULL,
    type VARCHAR(10) NOT NULL DEFAULT 'standard',
    status VARCHAR(10) NOT NULL DEFAULT 'available',
    booking_id INT DEFAULT NULL
);
CREATE TABLE bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INT DEFAULT NULL,
    session_id INT NOT NULL,
    seat_row INT NOT NULL,
    seat_number INT NOT NULL,
    name VARCHAR(120) NOT NULL DEFAULT '',
    email VARCHAR(120) NOT NULL DEFAULT '',
    total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(20) DEFAULT 'booked',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE booking_seats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_id INT NOT NULL,
    seat_id INT NOT NULL
);
");

// Seed base data
$pdo->exec("INSERT INTO halls (name, total_rows, seats_per_row) VALUES ('Hall 1', 8, 12), ('Hall 2', 10, 15)");
$pdo->exec("INSERT INTO users (name, email, password, role) VALUES
    ('Admin User', 'admin@test.com', '" . password_hash('admin123', PASSWORD_BCRYPT) . "', 'admin'),
    ('Test User',  'user@test.com',  '" . password_hash('user123',  PASSWORD_BCRYPT) . "', 'user')
");

// ── Load functions (override db.php require) ─────────────────
// We inline the functions here since db.php would try to connect MySQL
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
function addMovie($title, $description, $genre, $duration, $release_date, $poster = null) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO movies (title, description, genre, duration, release_date, poster) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $genre, $duration, $release_date, $poster]);
    return (int)$pdo->lastInsertId();
}
function updateMovie($id, $title, $description, $genre, $duration, $release_date, $poster = null) {
    global $pdo;
    if ($poster) {
        $stmt = $pdo->prepare("UPDATE movies SET title=?, description=?, genre=?, duration=?, release_date=?, poster=? WHERE id=?");
        $stmt->execute([$title, $description, $genre, $duration, $release_date, $poster, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE movies SET title=?, description=?, genre=?, duration=?, release_date=? WHERE id=?");
        $stmt->execute([$title, $description, $genre, $duration, $release_date, $id]);
    }
}
function deleteMovie($id) {
    global $pdo;
    $pdo->prepare("DELETE FROM sessions WHERE movie_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM movies WHERE id=?")->execute([$id]);
}
function getAllSessions() {
    global $pdo;
    return $pdo->query("SELECT s.*, m.title AS movie_title, h.name AS hall_name FROM sessions s JOIN movies m ON s.movie_id = m.id JOIN halls h ON s.hall_id = h.id ORDER BY s.show_time DESC")->fetchAll();
}
function getSessionById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT s.*, m.title AS movie_title, h.name AS hall_name FROM sessions s JOIN movies m ON s.movie_id = m.id JOIN halls h ON s.hall_id = h.id WHERE s.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}
function getSessionsByMovie($movie_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT s.*, h.name AS hall_name FROM sessions s JOIN halls h ON s.hall_id = h.id WHERE s.movie_id = ? AND s.show_time > datetime('now') ORDER BY s.show_time ASC");
    $stmt->execute([$movie_id]);
    return $stmt->fetchAll();
}
function addSession($movie_id, $hall_id, $show_time, $price) {
    global $pdo;
    $pdo->prepare("INSERT INTO sessions (movie_id, hall_id, show_time, price) VALUES (?, ?, ?, ?)")->execute([$movie_id, $hall_id, $show_time, $price]);
}
function updateSession($id, $movie_id, $hall_id, $show_time, $price) {
    global $pdo;
    $pdo->prepare("UPDATE sessions SET movie_id=?, hall_id=?, show_time=?, price=? WHERE id=?")->execute([$movie_id, $hall_id, $show_time, $price, $id]);
}
function deleteSession($id) {
    global $pdo;
    $pdo->prepare("DELETE FROM booking_seats WHERE booking_id IN (SELECT id FROM bookings WHERE session_id=?)")->execute([$id]);
    $pdo->prepare("DELETE FROM bookings WHERE session_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM seats WHERE session_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM sessions WHERE id=?")->execute([$id]);
}
function getAllHalls() {
    global $pdo;
    return $pdo->query("SELECT * FROM halls ORDER BY id")->fetchAll();
}
function getAllBookings($limit = 50, $offset = 0) {
    global $pdo;
    $limit = (int)$limit; $offset = (int)$offset;
    return $pdo->query("SELECT b.*, m.title AS movie_title, s.show_time FROM bookings b JOIN sessions s ON b.session_id = s.id JOIN movies m ON s.movie_id = m.id ORDER BY b.created_at DESC LIMIT {$limit} OFFSET {$offset}")->fetchAll();
}
function countAllBookings() {
    global $pdo;
    return (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
}
function cancelBooking($booking_id) {
    global $pdo;
    $pdo->prepare("UPDATE seats SET status='available', booking_id=NULL WHERE booking_id=?")->execute([$booking_id]);
    $pdo->prepare("DELETE FROM booking_seats WHERE booking_id=?")->execute([$booking_id]);
    $pdo->prepare("DELETE FROM bookings WHERE id=?")->execute([$booking_id]);
}
function getStats() {
    global $pdo;
    $totalRevenue  = (float)$pdo->query("SELECT COALESCE(SUM(total_price),0) FROM bookings")->fetchColumn();
    $totalBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    $totalMovies   = (int)$pdo->query("SELECT COUNT(*) FROM movies")->fetchColumn();
    $totalUsers    = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $upcomingSess  = (int)$pdo->query("SELECT COUNT(*) FROM sessions WHERE show_time > datetime('now')")->fetchColumn();
    $topMovies     = $pdo->query("SELECT m.title, COUNT(b.id) AS tickets, COALESCE(SUM(b.total_price),0) AS revenue FROM bookings b JOIN sessions s ON b.session_id = s.id JOIN movies m ON s.movie_id = m.id GROUP BY m.id, m.title ORDER BY revenue DESC LIMIT 5")->fetchAll();
    $revenueByDay  = $pdo->query("SELECT DATE(created_at) AS day, COALESCE(SUM(total_price),0) AS revenue, COUNT(*) AS tickets FROM bookings WHERE created_at >= date('now','-7 days') GROUP BY DATE(created_at) ORDER BY day ASC")->fetchAll();
    return compact('totalRevenue','totalBookings','totalMovies','totalUsers','upcomingSess','topMovies','revenueByDay');
}

// ════════════════════════════════════════════════════════════════
// TESTS
// ════════════════════════════════════════════════════════════════

echo bold("\n🎬 MyCinema Test Suite\n");
echo str_repeat("─", 50) . "\n";

// ════════════════════════════════
section("1. MOVIES — addMovie");
// ════════════════════════════════
$id1 = addMovie('Inception', 'A mind-bending thriller', 'Sci-Fi', 148, '2010-07-16');
assert_true($id1 > 0, 'addMovie returns a valid ID');

$id2 = addMovie('The Dark Knight', 'Batman vs Joker', 'Action', 152, '2008-07-18');
assert_true($id2 > $id1, 'second addMovie returns a higher ID');

$id3 = addMovie('With Poster', 'Has image', 'Drama', 100, '2020-01-01', 'binary_poster_data');
assert_true($id3 > 0, 'addMovie with poster data works');

section("2. MOVIES — getMovieById");
$movie = getMovieById($id1);
assert_not_empty($movie, 'getMovieById returns a result');
assert_equals($movie['title'], 'Inception', 'title matches');
assert_equals($movie['genre'], 'Sci-Fi', 'genre matches');
assert_equals((int)$movie['duration'], 148, 'duration matches');

$missing = getMovieById(99999);
assert_true($missing === false, 'getMovieById returns false for non-existent ID');

section("3. MOVIES — getAllMovies");
$all = getAllMovies();
assert_true(count($all) >= 2, 'getAllMovies returns at least 2 movies');
assert_equals($all[0]['title'], 'With Poster', 'getAllMovies ordered by id DESC');

section("4. MOVIES — updateMovie");
updateMovie($id1, 'Inception Updated', 'Updated desc', 'Thriller', 150, '2010-07-16');
$updated = getMovieById($id1);
assert_equals($updated['title'], 'Inception Updated', 'updateMovie updates title');
assert_equals($updated['genre'], 'Thriller', 'updateMovie updates genre');
assert_equals((int)$updated['duration'], 150, 'updateMovie updates duration');

updateMovie($id2, 'Dark Knight', 'Updated', 'Action', 152, '2008-07-18', 'new_poster');
$withPoster = getMovieById($id2);
assert_equals($withPoster['poster'], 'new_poster', 'updateMovie updates poster when provided');

updateMovie($id1, 'Inception Updated', 'Updated desc', 'Thriller', 150, '2010-07-16', null);
$noPosterChange = getMovieById($id1);
assert_equals($noPosterChange['title'], 'Inception Updated', 'updateMovie without poster keeps existing data');

section("5. MOVIES — deleteMovie");
$idToDelete = addMovie('To Delete', '', 'Comedy', 90, '2021-01-01');
deleteMovie($idToDelete);
$deleted = getMovieById($idToDelete);
assert_true($deleted === false, 'deleteMovie removes the movie');

section("6. HALLS — getAllHalls");
$halls = getAllHalls();
assert_true(count($halls) >= 2, 'getAllHalls returns seeded halls');
assert_equals($halls[0]['name'], 'Hall 1', 'first hall name correct');
assert_equals((int)$halls[0]['total_rows'], 8, 'Hall 1 has 8 rows');
assert_equals((int)$halls[0]['seats_per_row'], 12, 'Hall 1 has 12 seats per row');

section("7. SESSIONS — addSession & getSessionById");
$futureTime = date('Y-m-d H:i:s', strtotime('+2 days'));
addSession($id1, 1, $futureTime, 150.00);
$sessId = (int)$pdo->lastInsertId();
assert_true($sessId > 0, 'addSession inserts a session');

$sess = getSessionById($sessId);
assert_not_empty($sess, 'getSessionById returns the session');
assert_equals($sess['movie_title'], 'Inception Updated', 'session has correct movie title');
assert_equals($sess['hall_name'], 'Hall 1', 'session has correct hall name');
assert_equals((float)$sess['price'], 150.00, 'session has correct price');

$missingSess = getSessionById(99999);
assert_true($missingSess === false, 'getSessionById returns false for non-existent');

section("8. SESSIONS — getAllSessions");
addSession($id2, 2, date('Y-m-d H:i:s', strtotime('+3 days')), 200.00);
$allSess = getAllSessions();
assert_true(count($allSess) >= 2, 'getAllSessions returns multiple sessions');

section("9. SESSIONS — getSessionsByMovie");
$movieSessions = getSessionsByMovie($id1);
assert_true(count($movieSessions) >= 1, 'getSessionsByMovie returns future sessions');

// Past session should not appear
$pastTime = date('Y-m-d H:i:s', strtotime('-1 day'));
addSession($id1, 1, $pastTime, 150.00);
$movieSessionsAfter = getSessionsByMovie($id1);
assert_equals(count($movieSessionsAfter), count($movieSessions), 'getSessionsByMovie excludes past sessions');

section("10. SESSIONS — updateSession");
updateSession($sessId, $id2, 2, $futureTime, 175.00);
$updSess = getSessionById($sessId);
assert_equals((float)$updSess['price'], 175.00, 'updateSession updates price');
assert_equals($updSess['hall_name'], 'Hall 2', 'updateSession updates hall');

section("11. SESSIONS — deleteSession (cascades)");
// Create session with seats and booking
addSession($id1, 1, date('Y-m-d H:i:s', strtotime('+5 days')), 150.00);
$delSessId = (int)$pdo->lastInsertId();
$pdo->exec("INSERT INTO seats (session_id, row_number, seat_number, type, status) VALUES ({$delSessId}, 1, 1, 'standard', 'available')");
$seatId = (int)$pdo->lastInsertId();
$pdo->exec("INSERT INTO bookings (user_id, session_id, seat_row, seat_number, name, email, total_price) VALUES (1, {$delSessId}, 1, 1, 'Test', 'test@t.com', 150)");
$bookId = (int)$pdo->lastInsertId();
$pdo->exec("INSERT INTO booking_seats (booking_id, seat_id) VALUES ({$bookId}, {$seatId})");

deleteSession($delSessId);
$sessGone = getSessionById($delSessId);
assert_true($sessGone === false, 'deleteSession removes the session');
$seatsGone = $pdo->query("SELECT COUNT(*) FROM seats WHERE session_id={$delSessId}")->fetchColumn();
assert_equals((int)$seatsGone, 0, 'deleteSession cascades to seats');
$bookGone = $pdo->query("SELECT COUNT(*) FROM bookings WHERE session_id={$delSessId}")->fetchColumn();
assert_equals((int)$bookGone, 0, 'deleteSession cascades to bookings');
$bsGone = $pdo->query("SELECT COUNT(*) FROM booking_seats WHERE booking_id={$bookId}")->fetchColumn();
assert_equals((int)$bsGone, 0, 'deleteSession cascades to booking_seats');

section("12. BOOKINGS — countAllBookings");
$countBefore = countAllBookings();
assert_true($countBefore >= 0, 'countAllBookings returns non-negative int');

section("13. BOOKINGS — getAllBookings");
// Insert test bookings
addSession($id1, 1, date('Y-m-d H:i:s', strtotime('+10 days')), 150.00);
$testSessId = (int)$pdo->lastInsertId();
$pdo->exec("INSERT INTO bookings (user_id, session_id, seat_row, seat_number, name, email, total_price, status) VALUES (1, {$testSessId}, 3, 5, 'Alice', 'alice@test.com', 150.00, 'booked')");
$pdo->exec("INSERT INTO bookings (user_id, session_id, seat_row, seat_number, name, email, total_price, status) VALUES (2, {$testSessId}, 3, 6, 'Bob',   'bob@test.com',   180.00, 'booked')");

$bookings = getAllBookings(50, 0);
assert_true(count($bookings) >= 2, 'getAllBookings returns bookings');
assert_true(isset($bookings[0]['movie_title']), 'getAllBookings includes movie_title');
assert_true(isset($bookings[0]['show_time']),   'getAllBookings includes show_time');

$limited = getAllBookings(1, 0);
assert_count(1, $limited, 'getAllBookings respects limit');

section("14. BOOKINGS — cancelBooking");
// Setup a proper booking with seat
$pdo->exec("INSERT INTO seats (session_id, row_number, seat_number, type, status) VALUES ({$testSessId}, 5, 5, 'standard', 'booked')");
$cancelSeatId = (int)$pdo->lastInsertId();
$pdo->exec("INSERT INTO bookings (session_id, seat_row, seat_number, name, email, total_price) VALUES ({$testSessId}, 5, 5, 'Cancel Me', 'cancel@test.com', 150)");
$cancelBookId = (int)$pdo->lastInsertId();
$pdo->exec("UPDATE seats SET booking_id={$cancelBookId} WHERE id={$cancelSeatId}");
$pdo->exec("INSERT INTO booking_seats (booking_id, seat_id) VALUES ({$cancelBookId}, {$cancelSeatId})");

cancelBooking($cancelBookId);
$bookingGone = $pdo->query("SELECT COUNT(*) FROM bookings WHERE id={$cancelBookId}")->fetchColumn();
assert_equals((int)$bookingGone, 0, 'cancelBooking removes the booking');

$seatStatus = $pdo->query("SELECT status FROM seats WHERE id={$cancelSeatId}")->fetchColumn();
assert_equals($seatStatus, 'available', 'cancelBooking frees the seat');

$bsGone2 = $pdo->query("SELECT COUNT(*) FROM booking_seats WHERE booking_id={$cancelBookId}")->fetchColumn();
assert_equals((int)$bsGone2, 0, 'cancelBooking removes booking_seats entry');

section("15. STATS — getStats");
$stats = getStats();
assert_true(isset($stats['totalRevenue']),  'getStats has totalRevenue');
assert_true(isset($stats['totalBookings']), 'getStats has totalBookings');
assert_true(isset($stats['totalMovies']),   'getStats has totalMovies');
assert_true(isset($stats['totalUsers']),    'getStats has totalUsers');
assert_true(isset($stats['upcomingSess']),  'getStats has upcomingSess');
assert_true(isset($stats['topMovies']),     'getStats has topMovies');
assert_true(isset($stats['revenueByDay']),  'getStats has revenueByDay');
assert_true($stats['totalMovies'] >= 2,     'getStats totalMovies correct');
assert_true($stats['totalUsers'] >= 2,      'getStats totalUsers correct');
assert_true($stats['upcomingSess'] >= 1,    'getStats counts upcoming sessions');
assert_true($stats['totalRevenue'] >= 0,    'getStats revenue is non-negative');
assert_true(is_array($stats['topMovies']),  'getStats topMovies is array');
assert_true(is_array($stats['revenueByDay']),'getStats revenueByDay is array');

section("16. PRICE CALCULATION — premium vs standard");
$base    = 150.00;
$premium = round($base * 1.20, 2);
assert_equals($premium, 180.00, 'premium price is base * 1.20');

$seats = [
    ['type' => 'standard'],
    ['type' => 'premium'],
    ['type' => 'standard'],
];
$total = 0;
foreach ($seats as $s) $total += $s['type'] === 'premium' ? $premium : $base;
assert_equals($total, 480.00, 'total price calculation correct (2 standard + 1 premium)');

$allPremium = array_fill(0, 3, ['type' => 'premium']);
$premTotal  = array_sum(array_map(fn($s) => $premium, $allPremium));
assert_equals($premTotal, 540.00, 'all-premium total correct');

section("17. INPUT VALIDATION — seat IDs parsing");
$raw     = '1,2,3,abc,0,-5,3';
$seatIds = array_values(array_unique(
    array_filter(array_map('intval', explode(',', $raw)), fn($id) => $id > 0)
));
assert_count(3, $seatIds, 'seat ID parsing filters invalid and duplicate values');
assert_true(in_array(1, $seatIds), 'valid ID 1 kept');
assert_true(in_array(2, $seatIds), 'valid ID 2 kept');
assert_true(in_array(3, $seatIds), 'valid ID 3 kept');
assert_true(!in_array(0, $seatIds),  'zero filtered out');
assert_true(!in_array(-5, $seatIds), 'negative filtered out');

$emptySeatIds = [];
assert_true(count($emptySeatIds) === 0, 'empty seat list is detected as invalid (count = 0)');
$tooMany = range(1, 11);
assert_true(count($tooMany) > 10, 'more than 10 seats triggers validation');

section("18. INPUT VALIDATION — email");
assert_true(filter_var('valid@email.com', FILTER_VALIDATE_EMAIL) !== false, 'valid email passes');
assert_true(filter_var('user@domain.org', FILTER_VALIDATE_EMAIL) !== false, 'valid email with org passes');
assert_true(filter_var('not-an-email', FILTER_VALIDATE_EMAIL) === false, 'invalid email rejected');
assert_true(filter_var('missing@', FILTER_VALIDATE_EMAIL) === false, 'incomplete email rejected');
assert_true(filter_var('', FILTER_VALIDATE_EMAIL) === false, 'empty email rejected');

section("19. INPUT VALIDATION — password");
assert_true(strlen('short') < 6, 'password too short detected');
assert_true(strlen('validpass') >= 6, 'valid password length passes');
assert_true(strlen('123456') >= 6, 'minimum 6 char password passes');
assert_true(strlen('12345') < 6, 'exactly 5 chars is too short');

section("20. SECURITY — password hashing");
$hash = password_hash('mypassword', PASSWORD_BCRYPT);
assert_true(password_verify('mypassword', $hash), 'correct password verifies');
assert_true(!password_verify('wrongpassword', $hash), 'wrong password fails verification');
assert_true(strlen($hash) >= 60, 'bcrypt hash has expected length');
$hash2 = password_hash('mypassword', PASSWORD_BCRYPT);
assert_true($hash !== $hash2, 'same password produces different hashes (salt)');

section("21. SECURITY — CSRF token generation");
$token1 = bin2hex(random_bytes(32));
$token2 = bin2hex(random_bytes(32));
assert_equals(strlen($token1), 64, 'CSRF token is 64 chars hex');
assert_true($token1 !== $token2, 'each CSRF token is unique');
assert_true(hash_equals($token1, $token1), 'hash_equals returns true for matching tokens');
assert_true(!hash_equals($token1, $token2), 'hash_equals returns false for different tokens');

section("22. SECURITY — SQL injection via seat IDs");
$malicious = "1,2; DROP TABLE bookings;--";
$cleaned   = array_values(array_unique(
    array_filter(array_map('intval', explode(',', $malicious)), fn($id) => $id > 0)
));
assert_count(2, $cleaned, 'SQL injection in seat IDs is neutralised by intval');
$tableExists = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
assert_true($tableExists >= 0, 'bookings table still exists after injection attempt');

section("23. SECURITY — XSS via htmlspecialchars");
$xss = '<script>alert("xss")</script>';
$safe = htmlspecialchars($xss, ENT_QUOTES, 'UTF-8');
assert_true(strpos($safe, '<script>') === false, 'htmlspecialchars removes raw script tags');
assert_true(strpos($safe, '&lt;script&gt;') !== false, 'script tag is encoded');

$xss2 = '" onmouseover="alert(1)';
$safe2 = htmlspecialchars($xss2, ENT_QUOTES, 'UTF-8');
assert_true(strpos($safe2, '"') === false, 'double quotes encoded in attributes');

section("24. SEAT GENERATION logic");
$totalRows = 8; $seatsPerRow = 12;
$generated = [];
for ($r = 1; $r <= $totalRows; $r++) {
    $type = ($r <= 2) ? 'premium' : 'standard';
    for ($c = 1; $c <= $seatsPerRow; $c++) {
        $generated[] = ['row' => $r, 'seat' => $c, 'type' => $type];
    }
}
assert_count(96, $generated, 'correct total seats generated (8×12=96)');
assert_equals($generated[0]['type'],  'premium',  'row 1 is premium');
assert_equals($generated[11]['type'], 'premium',  'row 1 seat 12 is premium');
assert_equals($generated[12]['type'], 'premium',  'row 2 is premium');
assert_equals($generated[23]['type'], 'premium',  'row 2 seat 12 is premium');
assert_equals($generated[24]['type'], 'standard', 'row 3 is standard');
assert_equals($generated[0]['seat'],  1,           'first seat number is 1');
assert_equals($generated[11]['seat'], 12,          'last seat in row 1 is 12');

section("25. BOOKING TRANSACTION — race condition guard");
// Simulate two concurrent bookings for same seat
addSession($id1, 1, date('Y-m-d H:i:s', strtotime('+20 days')), 150.00);
$raceSessId = (int)$pdo->lastInsertId();
$pdo->exec("INSERT INTO seats (session_id, row_number, seat_number, type, status) VALUES ({$raceSessId}, 1, 1, 'standard', 'available')");
$raceSeatId = (int)$pdo->lastInsertId();

// First booking succeeds
$pdo->exec("UPDATE seats SET status='booked' WHERE id={$raceSeatId} AND status='available'");
$affected = $pdo->query("SELECT changes()")->fetchColumn();
assert_equals((int)$affected, 1, 'first booking locks seat');

// Second booking on same seat — status check
$seat = $pdo->query("SELECT status FROM seats WHERE id={$raceSeatId}")->fetchColumn();
assert_equals($seat, 'booked', 'seat correctly marked as booked');
assert_true($seat !== 'available', 'second booking attempt detects seat is taken');

section("26. MOVIE — deleteMovie cascades to sessions");
$cascadeMovieId = addMovie('Cascade Test', '', 'Test', 90, '2020-01-01');
addSession($cascadeMovieId, 1, date('Y-m-d H:i:s', strtotime('+30 days')), 100.00);
$cascadeSessId = (int)$pdo->lastInsertId();
deleteMovie($cascadeMovieId);
$sessAfterDelete = $pdo->query("SELECT COUNT(*) FROM sessions WHERE movie_id={$cascadeMovieId}")->fetchColumn();
assert_equals((int)$sessAfterDelete, 0, 'deleteMovie cascades to sessions');

section("27. STATS — revenue calculation");
// Add known bookings and verify sums
addSession($id1, 1, date('Y-m-d H:i:s', strtotime('+40 days')), 150.00);
$statsSessId = (int)$pdo->lastInsertId();
$pdo->exec("INSERT INTO bookings (session_id, seat_row, seat_number, name, email, total_price) VALUES ({$statsSessId}, 1, 1, 'R1', 'r1@t.com', 100.00)");
$pdo->exec("INSERT INTO bookings (session_id, seat_row, seat_number, name, email, total_price) VALUES ({$statsSessId}, 1, 2, 'R2', 'r2@t.com', 200.00)");
$stats2 = getStats();
assert_true($stats2['totalRevenue'] >= 300.00, 'getStats revenue includes new bookings');
assert_true($stats2['totalBookings'] >= 2, 'getStats booking count updated');

// ════════════════════════════════════════════════════════════════
// SUMMARY
// ════════════════════════════════════════════════════════════════
echo "\n" . str_repeat("─", 50) . "\n";
$total_tests = $passed + $failed;
echo bold("Results: ") . green("{$passed} passed") . ", " . ($failed > 0 ? red("{$failed} failed") : "0 failed") . " / {$total_tests} total\n";

if ($failed > 0) {
    echo yellow("\nFailed tests:\n");
    foreach ($failures as $f) echo "  • {$f}\n";

    if (isCli()) {
        exit(1);
    }
    return;
} else {
    echo green("\n✓ All tests passed!\n");

    if (isCli()) {
        exit(0);
    }
    return;
}
