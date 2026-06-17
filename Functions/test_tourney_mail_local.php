<?php

$config = require __DIR__ . '/mail_config.php';

$pdo = new PDO(
    "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
    $config['db_user'],
    $config['db_pass'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);

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
    WHERE u.email IS NOT NULL
    AND u.email != ''
    AND tr.status IN ('pending', 'accepted')
");

$players = $stmt->fetchAll();

if (!$players) {
    echo "Geen spelers gevonden.\n";
    exit;
}

$outputDir = __DIR__ . '/mail_test_output';

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$count = 0;

foreach ($players as $player) {
    $email = trim($player['email']);
    $username = trim($player['username']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Ongeldig emailadres overgeslagen: {$email}\n";
        continue;
    }

    $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');

    $subject = 'Bevestiging Jeu de Boules Toernooi';

    $body = "
        <h2>Hoi {$safeUsername},</h2>
        <p>Je bent aangemeld voor het Jeu de Boules toernooi.</p>
        <p>We sturen je deze mail om je inschrijving te bevestigen.</p>
        <p>Meer informatie over de planning, teams en wedstrijden volgt later.</p>
        <br>
        <p>Met vriendelijke groet,</p>
        <p>De organisatie</p>
    ";

    $html = "
        <!DOCTYPE html>
        <html lang='nl'>
        <head>
            <meta charset='UTF-8'>
            <title>{$subject}</title>
        </head>
        <body>
            <p><strong>Aan:</strong> {$email}</p>
            <p><strong>Onderwerp:</strong> {$subject}</p>
            <hr>
            {$body}
        </body>
        </html>
    ";

    $safeFileName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $email);
    $filePath = $outputDir . '/' . $safeFileName . '.html';

    file_put_contents($filePath, $html);

    echo "Testmail gemaakt: {$filePath}\n";

    $count++;
}

echo "\nKlaar.\n";
echo "Testmails gemaakt: {$count}\n";