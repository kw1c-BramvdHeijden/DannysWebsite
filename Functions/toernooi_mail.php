<?php
require_once __DIR__ . '/../includes/db.php';

if (!isset($pdo) || !$pdo instanceof PDO) {
    exit('Database verbinding niet gevonden.');
}
$key = $_GET['key'] ?? '';

if ($key !== 'test123') {
    http_response_code(403);
    exit('Geen toegang');
}

require_once __DIR__ . '/../includes/db.php';

$testMode = true;
$testEmail = 'neobrugman12@gmail.com';

$fromEmail = 'noreply@st1739531586.splsites.nl';
$fromName = 'Jeu De Dabs';
$replyTo = 'neobrugman12@gmail.com';

$stmt = $pdo->query("
    SELECT DISTINCT
        tr.registration_id,
        tr.tournament_id,
        tr.team_id,
        u.user_id,
        u.username,
        u.email
    FROM tournament_registrations tr
    INNER JOIN team_members tm ON tm.team_id = tr.team_id
    INNER JOIN users u ON u.user_id = tm.user_id
    LEFT JOIN tournament_email_logs tel
        ON tel.registration_id = tr.registration_id
        AND tel.user_id = u.user_id
    WHERE u.email IS NOT NULL
    AND u.email != ''
    AND tr.status IN ('pending', 'accepted')
    AND tel.log_id IS NULL
");

$players = $stmt->fetchAll();

if (!$players) {
    echo "Geen spelers gevonden om te mailen.";
    exit;
}

$sent = 0;
$failed = 0;

foreach ($players as $player) {
    $originalEmail = trim($player['email']);
    $sendToEmail = $testMode ? $testEmail : $originalEmail;
    $username = trim($player['username']);

    if (!filter_var($sendToEmail, FILTER_VALIDATE_EMAIL)) {
        $failed++;
        echo "Ongeldig emailadres overgeslagen: {$sendToEmail}<br>";
        continue;
    }

    $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $subject = 'Bevestiging Jeu de Boules Toernooi';

    $body = "
        <html>
        <body>
            <h2>Hoi {$safeUsername},</h2>
            <p>Je bent aangemeld voor het Jeu de Boules toernooi.</p>
            <p>We sturen je deze mail om je inschrijving te bevestigen.</p>
            <p>Meer informatie over de planning, teams en wedstrijden volgt later.</p>
            <br>
            <p>Met vriendelijke groet,</p>
            <p>De organisatie</p>
        </body>
        </html>
    ";

    $headers = "From: {$fromName} <{$fromEmail}>\r\n";
    $headers .= "Reply-To: {$replyTo}\r\n";
    $headers .= "Return-Path: {$fromEmail}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $success = mail(
        $sendToEmail,
        $subject,
        $body,
        $headers,
        "-f{$fromEmail}"
    );

    if ($success) {
        if (!$testMode) {
            $log = $pdo->prepare("
                INSERT INTO tournament_email_logs (registration_id, user_id, email)
                VALUES (?, ?, ?)
            ");

            $log->execute([
                $player['registration_id'],
                $player['user_id'],
                $originalEmail
            ]);
        }

        $sent++;

        echo "Verzonden naar {$sendToEmail}";

        if ($testMode) {
            echo " als test voor originele speler {$originalEmail}";
        }

        echo "<br>";

        if ($testMode) {
            break;
        }
    } else {
        $failed++;
        echo "Mislukt naar {$sendToEmail}<br>";
    }

    sleep(1);
}

echo "<br>Klaar.<br>";
echo "Verzonden: {$sent}<br>";
echo "Mislukt: {$failed}<br>";