<?php
function sendTicketEmail($to, $movie, $time, $seats) {
    $subject = "Your cinema ticket";
    $message = "
Your booking is confirmed

Movie: $movie
Date: $time
Tickets: $seats

Enjoy your movie!
";
    $headers = "From: cinema@example.com\r\n";
    mail($to, $subject, $message, $headers);
}
