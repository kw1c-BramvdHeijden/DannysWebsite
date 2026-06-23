<?php

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/competition_mailer.php";

function reminder_is_cli()
{
    return PHP_SAPI === "cli";
}

function reminder_arg($name, $default = "")
{
    global $argv;

    if (!reminder_is_cli()) {
        return isset($_GET[$name]) ? trim((string) $_GET[$name]) : $default;
    }

    $prefix = "--" . $name . "=";

    foreach ($argv as $argument) {
        if (strpos($argument, $prefix) === 0) {
            return trim(substr($argument, strlen($prefix)));
        }
    }

    return $default;
}

function reminder_output(array $summary, $targetDate)
{
    $lines = array(
        "Herinneringen voor startdatum: " . $targetDate,
        "Verzonden: " . $summary["sent"],
        "Overgeslagen: " . $summary["skipped"],
        "Mislukt: " . $summary["failed"],
    );

    foreach ($summary["errors"] as $error) {
        $lines[] = "Fout: " . $error;
    }

    if (reminder_is_cli()) {
        echo implode(PHP_EOL, $lines) . PHP_EOL;
        return;
    }

    header("Content-Type: text/plain; charset=utf-8");
    echo implode("\n", $lines);
}

if (!reminder_is_cli()) {
    $expectedKey = getenv("MAIL_TASK_KEY");
    $expectedKey = $expectedKey === false || $expectedKey === "" ? "test123" : $expectedKey;
    $providedKey = isset($_GET["key"]) ? (string) $_GET["key"] : "";

    if (!hash_equals($expectedKey, $providedKey)) {
        http_response_code(403);
        exit("Geen toegang");
    }
}

$targetDate = reminder_arg("date", "");

if ($targetDate === "") {
    $targetDate = (new DateTime("tomorrow"))->format("Y-m-d");
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDate)) {
    http_response_code(422);
    exit("Gebruik een datum als YYYY-MM-DD.");
}

$options = array();
$testEmail = reminder_arg("testEmail", "");

if ($testEmail !== "") {
    $options["override_email"] = $testEmail;
    $options["force"] = true;
    $options["log_sends"] = false;
}

$limit = reminder_arg("limit", "");

if ($limit !== "" && is_numeric($limit)) {
    $options["message_limit"] = (int) $limit;
}

try {
    reminder_output(competition_mail_send_one_day_reminders($pdo, $targetDate, $options), $targetDate);
} catch (Throwable $exception) {
    http_response_code(500);
    echo "Herinneringen konden niet worden verzonden: " . $exception->getMessage();
}
