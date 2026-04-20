<?php
function sendTicketEmail($to, $name, $movie, $movieId, $time, $seats, $total, $lockedSeats = []) {
    $apiKey = getenv('BREVO_API_KEY');

    if (!$apiKey) {
        error_log('Brevo mail error: BREVO_API_KEY is not set');
        return [
            'code' => 500,
            'response' => json_encode(['error' => 'BREVO_API_KEY is not set'])
        ];
    }
    // Poster URL — served directly from your hosting
    $posterUrl = "https://cinematpt.free.nf/assets/posters/{$movieId}.jpg";
 
    // Build seat rows
    $seatRows = '';
    foreach ($lockedSeats as $ls) {
        $type      = $ls['type'] === 'premium' ? '★ Premium' : 'Standard';
        $typeColor = $ls['type'] === 'premium' ? '#c9a0ff' : '#bbb';
        $seatRows .= "
            <tr>
                <td style='padding:10px 16px;border-bottom:1px solid #2a2a3e;color:#fff;font-size:14px'>Row {$ls['row_number']}</td>
                <td style='padding:10px 16px;border-bottom:1px solid #2a2a3e;color:#fff;font-size:14px'>Seat {$ls['seat_number']}</td>
                <td style='padding:10px 16px;border-bottom:1px solid #2a2a3e;color:{$typeColor};font-size:14px'>{$type}</td>
            </tr>";
    }
 
    // QR code — encodes all key booking info
    $qrContent = "Movie: {$movie} | Date: {$time} | Name: {$name} | Seats: {$seats} | Total: " . number_format($total, 2) . " EUR";
    $qrUrl     = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($qrContent);
 
    $htmlContent = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#0d0d0d;font-family:Arial,sans-serif'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#0d0d0d;padding:30px 0'>
  <tr><td align='center'>
  <table width='560' cellpadding='0' cellspacing='0'
         style='background:#1a1a2e;border-radius:12px;overflow:hidden'>
 
    <!-- HEADER -->
    <tr>
      <td style='background:linear-gradient(135deg,#e07b39,#c0392b);padding:28px 32px;text-align:center'>
        <div style='font-size:44px;line-height:1'>🎟️</div>
        <h1 style='color:#fff;margin:10px 0 4px;font-size:26px;letter-spacing:2px'>BOOKING CONFIRMED</h1>
        <p style='color:rgba(255,255,255,.75);margin:0;font-size:13px;letter-spacing:1px'>MYCINEMA</p>
      </td>
    </tr>
 
    <!-- MOVIE INFO -->
    <tr>
      <td style='padding:28px 32px'>
        <table width='100%' cellpadding='0' cellspacing='0'>
          <tr>
            <td width='130' valign='top' style='padding-right:20px'>
                <img src='{$posterUrl}' alt='Movie Poster' width='120'
                    style='display:block;width:120px;max-width:120px;height:auto;border:2px solid #e07b39;'>
            </td>
            <td valign='top'>
              <h2 style='color:#e07b39;margin:0 0 16px;font-size:19px'>" . htmlspecialchars($movie) . "</h2>
              <table cellpadding='0' cellspacing='0'>
                <tr>
                  <td style='color:#888;font-size:13px;padding-bottom:10px;padding-right:12px;white-space:nowrap'>📅 Date &amp; Time</td>
                  <td style='color:#fff;font-size:13px;padding-bottom:10px'>{$time}</td>
                </tr>
                <tr>
                  <td style='color:#888;font-size:13px;padding-bottom:10px;padding-right:12px;white-space:nowrap'>👤 Name</td>
                  <td style='color:#fff;font-size:13px;padding-bottom:10px'>" . htmlspecialchars($name) . "</td>
                </tr>
                <tr>
                  <td style='color:#888;font-size:13px;padding-bottom:10px;padding-right:12px;white-space:nowrap'>✉️ Email</td>
                  <td style='color:#fff;font-size:13px;padding-bottom:10px'>" . htmlspecialchars($to) . "</td>
                </tr>
                <tr>
                  <td style='color:#888;font-size:13px;padding-right:12px;white-space:nowrap'>🎫 Tickets</td>
                  <td style='color:#fff;font-size:13px'>{$seats} seat(s)</td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>
 
    <!-- SEATS TABLE -->
    <tr>
      <td style='padding:0 32px 24px'>
        <h3 style='color:#fff;margin:0 0 12px;font-size:14px;letter-spacing:1px;
                   border-bottom:1px solid #2a2a3e;padding-bottom:10px'>🪑 YOUR SEATS</h3>
        <table width='100%' cellpadding='0' cellspacing='0'
               style='background:#111827;border-radius:8px;overflow:hidden'>
          <tr style='background:#0f172a'>
            <th style='padding:10px 16px;color:#666;font-size:11px;text-align:left;font-weight:normal;letter-spacing:1px'>ROW</th>
            <th style='padding:10px 16px;color:#666;font-size:11px;text-align:left;font-weight:normal;letter-spacing:1px'>SEAT</th>
            <th style='padding:10px 16px;color:#666;font-size:11px;text-align:left;font-weight:normal;letter-spacing:1px'>TYPE</th>
          </tr>
          {$seatRows}
        </table>
      </td>
    </tr>
 
    <!-- TOTAL + QR -->
    <tr>
      <td style='padding:0 32px 28px'>
        <table width='100%' cellpadding='0' cellspacing='0'>
          <tr>
            <td valign='middle'>
              <div style='background:#111827;border-radius:8px;padding:16px 20px'>
                <div style='color:#888;font-size:12px;letter-spacing:1px;margin-bottom:4px'>TOTAL PAID</div>
                <div style='color:#e07b39;font-size:28px;font-weight:bold'>" . number_format($total, 2) . " €</div>
              </div>
            </td>
            <td align='right' valign='middle'>
              <div style='text-align:center'>
                <img src='{$qrUrl}' alt='QR Code'
                     style='width:130px;height:130px;border-radius:8px;
                            border:3px solid #e07b39;display:block;margin:0 auto'>
                <div style='color:#666;font-size:11px;margin-top:6px;letter-spacing:1px'>SCAN AT ENTRANCE</div>
              </div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
 
    <!-- FOOTER -->
    <tr>
      <td style='background:#111;padding:18px 32px;text-align:center;border-top:1px solid #2a2a3e'>
        <p style='color:#555;font-size:12px;margin:0'>Please show this email or QR code at the cinema entrance.</p>
        <p style='color:#444;font-size:11px;margin:6px 0 0'>© " . date('Y') . " MyCinema. All rights reserved.</p>
      </td>
    </tr>
 
  </table>
  </td></tr>
</table>
</body>
</html>";
 
    $textContent = "BOOKING CONFIRMED\n\n"
        . "Movie: {$movie}\n"
        . "Date: {$time}\n"
        . "Name: {$name}\n"
        . "Email: {$to}\n\n"
        . "Seats:\n";
    foreach ($lockedSeats as $ls) {
        $textContent .= "  Row {$ls['row_number']}, Seat {$ls['seat_number']} ({$ls['type']})\n";
    }
    $textContent .= "\nTotal: " . number_format($total, 2) . " EUR\n\n"
        . "Please show this email at the cinema entrance.\n"
        . "© " . date('Y') . " MyCinema";
 
    $data = [
        'sender'      => ['name' => 'MyCinema', 'email' => 'karakatica.katrica@gmail.com'],
        'to'          => [['email' => $to, 'name' => $name]],
        'subject'     => "🎟️ Your tickets for {$movie} — " . date('d M Y', strtotime($time)),
        'htmlContent' => $htmlContent,
        'textContent' => $textContent,
    ];
 
    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json',
        ],
    ]);
 
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
 
    if ($httpCode !== 201) {
        error_log('Brevo mail error: ' . $response);
    }
 
    return ['code' => $httpCode, 'response' => $response];
}