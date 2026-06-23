<?php

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/competition_mailer.php";

function toernooi_mail_is_cli()
{
    return PHP_SAPI === "cli";
}

function toernooi_mail_arg($name, $default = "")
{
    global $argv;

    if (!toernooi_mail_is_cli()) {
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

function toernooi_mail_bool_arg($name, $default)
{
    $value = strtolower(toernooi_mail_arg($name, $default ? "1" : "0"));

    return in_array($value, array("1", "true", "yes", "ja"), true);
}

function toernooi_mail_output(array $summary)
{
    $lines = array(
        "Registratiebevestigingen",
        "Verzonden: " . $summary["sent"],
        "Overgeslagen: " . $summary["skipped"],
        "Mislukt: " . $summary["failed"],
    );

    foreach ($summary["errors"] as $error) {
        $lines[] = "Fout: " . $error;
    }

    if (toernooi_mail_is_cli()) {
        echo implode(PHP_EOL, $lines) . PHP_EOL;
        return;
    }

    header("Content-Type: text/plain; charset=utf-8");
    echo implode("\n", $lines);
}

if (!toernooi_mail_is_cli()) {
    $expectedKey = getenv("MAIL_TASK_KEY");
    $expectedKey = $expectedKey === false || $expectedKey === "" ? "test123" : $expectedKey;
    $providedKey = isset($_GET["key"]) ? (string) $_GET["key"] : "";

    if (!hash_equals($expectedKey, $providedKey)) {
        http_response_code(403);
        exit("Geen toegang");
    }
}

$config = file_exists(__DIR__ . "/mail_config.php") ? require __DIR__ . "/mail_config.php" : array();
$testMode = toernooi_mail_bool_arg("test", true);
$testEmail = toernooi_mail_arg("testEmail", isset($config["reply_to"]) ? $config["reply_to"] : "");
$registrationId = toernooi_mail_arg("registrationId", "");
$limit = toernooi_mail_arg("limit", $testMode ? "1" : "");
$summary = competition_mail_empty_summary();
$options = array();
$messageLimit = $limit !== "" && is_numeric($limit) ? (int) $limit : 0;

if ($testMode) {
    if ($testEmail === "") {
        http_response_code(422);
        exit("Geef testEmail mee of zet reply_to in mail_config.php.");
    }

    $options["override_email"] = $testEmail;
    $options["force"] = true;
    $options["log_sends"] = false;
}

if ($messageLimit > 0) {
    $options["message_limit"] = $messageLimit;
}

try {
    if ($registrationId !== "") {
        if (!is_numeric($registrationId)) {
            http_response_code(422);
            exit("registrationId moet numeriek zijn.");
        }

        $summary = competition_mail_send_registration_confirmation($pdo, $registrationId, $options);
        toernooi_mail_output($summary);
        exit;
    }

    foreach (competition_mail_fetch_active_registration_ids($pdo) as $activeRegistrationId) {
        if ($messageLimit > 0 && $summary["sent"] >= $messageLimit) {
            break;
        }

        $registrationOptions = $options;

        if ($messageLimit > 0) {
            $registrationOptions["message_limit"] = $messageLimit - $summary["sent"];
        }

        $summary = competition_mail_merge_summary(
            $summary,
            competition_mail_send_registration_confirmation($pdo, $activeRegistrationId, $registrationOptions)
        );

        if ($testMode && $summary["sent"] > 0) {
            break;
        }
    }

    toernooi_mail_output($summary);
} catch (Throwable $exception) {
    http_response_code(500);
    echo "Registratiebevestigingen konden niet worden verzonden: " . $exception->getMessage();
}
