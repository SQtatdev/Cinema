<?php
require_once '../includes/db.php';

$session_id = $_GET['session_id'] ?? null;
if (!$session_id) die("Session ID is missing");

// Example: booking seat row 1, seat 1
$stmt = $pdo->prepare("INSERT INTO bookings (user_id, session_id, seat_row, seat_number) VALUES (1, ?, 1, 1)");
$stmt->execute([$session_id]);

echo "Booking successful! <a href='index.php'>Back to movies</a>";
