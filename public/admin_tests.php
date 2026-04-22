<?php
declare(strict_types=1);

session_start();
ini_set('display_errors', '1');
error_reporting(E_ALL);

if (!isset($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') !== 'admin')) {
    header('Location: login.php');
    exit;
}

$validUser = 'admin';
$validPass = 'qwerty';

$user = $_SERVER['PHP_AUTH_USER'] ?? '';
$pass = $_SERVER['PHP_AUTH_PW'] ?? '';

if ($user !== $validUser || $pass !== $validPass) {
    header('WWW-Authenticate: Basic realm="Protected Tests"');
    header('HTTP/1.1 401 Unauthorized');
    exit('Authorization required');
}

$testsFile = dirname(__DIR__) . '/tests/run_tests.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function parseTestOutput(string $output): array
{
    $output = str_replace("\r\n", "\n", $output);
    $lines = array_values(array_filter(array_map('trim', explode("\n", $output)), static fn($line) => $line !== ''));

    $sections = [];
    $summary = [
        'passed' => 0,
        'failed' => 0,
        'total'  => 0,
    ];

    foreach ($lines as $line) {
        if (preg_match('/^──\s*(.+?)\s*─*$/u', $line, $m)) {
            $sections[] = [
                'title' => trim($m[1]),
                'tests' => [],
            ];
            continue;
        }

        if (preg_match('/^✓\s*(.+)$/u', $line, $m)) {
            if (empty($sections)) {
                $sections[] = ['title' => 'General', 'tests' => []];
            }
            $sections[array_key_last($sections)]['tests'][] = [
                'status' => 'passed',
                'text' => trim($m[1]),
            ];
            continue;
        }

        if (preg_match('/^[✗xX]\s*(.+)$/u', $line, $m)) {
            if (empty($sections)) {
                $sections[] = ['title' => 'General', 'tests' => []];
            }
            $sections[array_key_last($sections)]['tests'][] = [
                'status' => 'failed',
                'text' => trim($m[1]),
            ];
            continue;
        }

        if (preg_match('/Results:\s*(\d+)\s*passed,\s*(\d+)\s*failed\s*\/\s*(\d+)\s*total/i', $line, $m)) {
            $summary['passed'] = (int)$m[1];
            $summary['failed'] = (int)$m[2];
            $summary['total']  = (int)$m[3];
        }
    }

    if ($summary['total'] === 0) {
        foreach ($sections as $section) {
            foreach ($section['tests'] as $test) {
                $summary['total']++;
                if ($test['status'] === 'passed') {
                    $summary['passed']++;
                } else {
                    $summary['failed']++;
                }
            }
        }
    }

    return [$sections, $summary];
}

$error = null;
$output = '';

if (!file_exists($testsFile)) {
    $error = 'Test file not found: ' . $testsFile;
} elseif (!is_readable($testsFile)) {
    $error = 'Test file is not readable: ' . $testsFile;
} else {
    ob_start();
    require $testsFile;
    $output = (string)ob_get_clean();
}

[$sections, $summary] = parseTestOutput($output);
$progress = $summary['total'] > 0 ? round(($summary['passed'] / $summary['total']) * 100, 1) : 0;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyCinema Tests</title>
    <style>
        :root {
            --orange: #ff6f00;
            --bg: #0e0e0e;
            --surface: #171717;
            --surface2: #1f1f1f;
            --border: #2a2a2a;
            --text: #f5f5f5;
            --muted: #9a9a9a;
            --green: #27c46b;
            --red: #ff5c5c;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: linear-gradient(180deg, #0b0b0b, #131313);
            color: var(--text);
            font-family: Arial, sans-serif;
        }

        .wrap {
            max-width: 1400px;
            margin: 0 auto;
            padding: 28px;
        }

        .hero {
            background: linear-gradient(135deg, rgba(255,111,0,.18), rgba(255,255,255,.03));
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 22px;
        }

        .hero-top {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .title {
            font-size: 42px;
            font-weight: 900;
            margin: 0 0 8px;
        }

        .subtitle {
            color: var(--muted);
            font-size: 14px;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            text-decoration: none;
            padding: 12px 16px;
            border-radius: 10px;
            font-weight: 700;
            border: 1px solid var(--border);
            background: #222;
            color: white;
        }

        .btn.primary {
            background: var(--orange);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-top: 18px;
        }

        .stat {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 18px;
        }

        .label {
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 8px;
        }

        .value {
            font-size: 34px;
            font-weight: 900;
        }

        .progress {
            margin-top: 12px;
            height: 12px;
            border-radius: 999px;
            background: #111;
            overflow: hidden;
        }

        .progress div {
            height: 100%;
            width: <?= $progress ?>%;
            background: linear-gradient(90deg, var(--green), var(--orange));
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
        }

        .section-head {
            padding: 14px;
            border-bottom: 1px solid var(--border);
            background: var(--surface2);
        }

        .tests {
            padding: 14px;
            display: grid;
            gap: 10px;
        }

        .test {
            display: grid;
            grid-template-columns: 40px 1fr;
            gap: 12px;
            padding: 12px;
            border-radius: 12px;
            background: #141414;
        }

        .icon {
            display: grid;
            place-items: center;
            font-weight: bold;
        }

        .passed .icon { color: var(--green); }
        .failed .icon { color: var(--red); }

        .badge {
            margin-top: 6px;
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="wrap">

    <div class="hero">
        <div class="hero-top">
            <div>
                <h1 class="title">Test Dashboard</h1>
                <div class="subtitle">Clean overview of all tests grouped by sections.</div>
            </div>

            <div class="actions">
                <a class="btn primary" href="">Run Again</a>
                <a class="btn" href="admin.php">Back to Admin</a>
            </div>
        </div>

        <div class="stats">
            <div class="stat"><div class="label">Passed</div><div class="value"><?= $summary['passed'] ?></div></div>
            <div class="stat"><div class="label">Failed</div><div class="value"><?= $summary['failed'] ?></div></div>
            <div class="stat"><div class="label">Total</div><div class="value"><?= $summary['total'] ?></div></div>
            <div class="stat"><div class="label">Sections</div><div class="value"><?= count($sections) ?></div></div>
        </div>

        <div class="progress"><div></div></div>
    </div>

    <?php if ($error): ?>
        <div><?= h($error) ?></div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($sections as $section): ?>
                <div class="section">
                    <div class="section-head"><?= h($section['title']) ?></div>
                    <div class="tests">
                        <?php foreach ($section['tests'] as $test): ?>
                            <div class="test <?= $test['status'] ?>">
                                <div class="icon"><?= $test['status'] === 'passed' ? '✓' : '✕' ?></div>
                                <div>
                                    <?= h($test['text']) ?>
                                    <div class="badge"><?= ucfirst($test['status']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
</body>
</html>