<?php

require_once __DIR__ . "/competition_mailer.php";

function test_mail_arg($name, $default = "")
{
    global $argv;

    $prefix = "--" . $name . "=";

    foreach ($argv as $argument) {
        if (strpos($argument, $prefix) === 0) {
            return trim(substr($argument, strlen($prefix)));
        }
    }

    return $default;
}

if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("Alleen via CLI gebruiken.");
}

$db = test_mail_arg("db", "dannyproject_mailtest");
$host = test_mail_arg("host", "localhost");
$user = test_mail_arg("user", "root");
$pass = test_mail_arg("pass", "");
$type = test_mail_arg("type", "confirmation");

$pdo = new PDO(
    "mysql:host=$host;dbname=$db;charset=utf8mb4",
    $user,
    $pass,
    array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    )
);

$options = array();

if (test_mail_arg("force", "0") === "1") {
    $options["force"] = true;
}

$testEmail = test_mail_arg("testEmail", "");

if ($testEmail !== "") {
    $options["override_email"] = $testEmail;
    $options["force"] = true;
    $options["log_sends"] = false;
}

if ($type === "reminder") {
    $date = test_mail_arg("date", (new DateTime("tomorrow"))->format("Y-m-d"));
    $summary = competition_mail_send_one_day_reminders($pdo, $date, $options);
} else {
    $registrationId = test_mail_arg("registrationId", "5");
    $summary = competition_mail_send_registration_confirmation($pdo, $registrationId, $options);
}

echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
