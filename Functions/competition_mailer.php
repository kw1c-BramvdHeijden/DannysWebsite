<?php

require_once __DIR__ . "/../includes/bootstrap-data.php";
require_once __DIR__ . "/mailer.php";

const COMPETITION_MAIL_EVENT_REGISTRATION = "registration_confirmation";
const COMPETITION_MAIL_EVENT_REMINDER_1_DAY = "competition_reminder_1_day";

function competition_mail_require_table(PDO $pdo, $tableName)
{
    if (!boules_table_exists($pdo, $tableName)) {
        throw new RuntimeException("Tabel $tableName bestaat niet.");
    }
}

function competition_mail_first_column(PDO $pdo, $tableName, array $candidates)
{
    competition_mail_require_table($pdo, $tableName);

    return boules_first_existing_column(boules_table_columns($pdo, $tableName), $candidates);
}

function competition_mail_require_column(PDO $pdo, $tableName, array $candidates, $label)
{
    $column = competition_mail_first_column($pdo, $tableName, $candidates);

    if (!$column) {
        throw new RuntimeException("Kolom $label ontbreekt in $tableName.");
    }

    return $column;
}

function competition_mail_add_column_if_missing(PDO $pdo, $tableName, $column, $definition)
{
    if (!in_array($column, boules_table_columns($pdo, $tableName), true)) {
        $pdo->exec("ALTER TABLE `$tableName` ADD COLUMN $definition");
    }
}

function competition_mail_ensure_log_table(PDO $pdo)
{
    if (!boules_table_exists($pdo, "tournament_email_logs")) {
        $pdo->exec(
            "CREATE TABLE `tournament_email_logs` (" .
            "`log_id` int(11) NOT NULL AUTO_INCREMENT, " .
            "`registration_id` int(11) DEFAULT NULL, " .
            "`tournament_id` int(11) DEFAULT NULL, " .
            "`team_id` int(11) DEFAULT NULL, " .
            "`user_id` int(11) DEFAULT NULL, " .
            "`email` varchar(255) NOT NULL, " .
            "`event_type` varchar(50) NOT NULL DEFAULT 'registration_confirmation', " .
            "`sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, " .
            "PRIMARY KEY (`log_id`), " .
            "KEY `idx_tournament_email_logs_event` (`event_type`, `registration_id`, `user_id`)" .
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        return;
    }

    competition_mail_add_column_if_missing($pdo, "tournament_email_logs", "registration_id", "`registration_id` int(11) DEFAULT NULL");
    competition_mail_add_column_if_missing($pdo, "tournament_email_logs", "tournament_id", "`tournament_id` int(11) DEFAULT NULL");
    competition_mail_add_column_if_missing($pdo, "tournament_email_logs", "team_id", "`team_id` int(11) DEFAULT NULL");
    competition_mail_add_column_if_missing($pdo, "tournament_email_logs", "user_id", "`user_id` int(11) DEFAULT NULL");
    competition_mail_add_column_if_missing($pdo, "tournament_email_logs", "email", "`email` varchar(255) NOT NULL DEFAULT ''");
    competition_mail_add_column_if_missing($pdo, "tournament_email_logs", "event_type", "`event_type` varchar(50) NOT NULL DEFAULT 'registration_confirmation'");
    competition_mail_add_column_if_missing($pdo, "tournament_email_logs", "sent_at", "`sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");
}

function competition_mail_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function competition_mail_text($value, $fallback = "")
{
    $value = trim((string) $value);

    return $value === "" ? $fallback : $value;
}

function competition_mail_format_date($value)
{
    $value = trim((string) $value);

    if ($value === "") {
        return "Datum volgt";
    }

    try {
        return (new DateTime($value))->format("d-m-Y");
    } catch (Exception $exception) {
        return $value;
    }
}

function competition_mail_registration_columns(PDO $pdo)
{
    return array(
        "id" => competition_mail_require_column($pdo, "tournament_registrations", array("registration_id", "id"), "registratie-id"),
        "tournament_id" => competition_mail_require_column($pdo, "tournament_registrations", array("tournament_id", "competition_id"), "competitie-id"),
        "team_id" => competition_mail_require_column($pdo, "tournament_registrations", array("team_id", "teamId"), "team-id"),
        "status" => competition_mail_first_column($pdo, "tournament_registrations", array("status", "state")),
    );
}

function competition_mail_tournament_columns(PDO $pdo)
{
    return array(
        "id" => competition_mail_require_column($pdo, "tournaments", array("tournament_id", "id", "competition_id"), "competitie-id"),
        "name" => competition_mail_require_column($pdo, "tournaments", array("name", "title", "naam", "tournament_name"), "competitienaam"),
        "start_date" => competition_mail_require_column($pdo, "tournaments", array("start_date", "startDate", "startdatum", "date", "datum"), "startdatum"),
        "location" => competition_mail_first_column($pdo, "tournaments", array("location", "type", "plaats", "locatie")),
        "status" => competition_mail_first_column($pdo, "tournaments", array("status", "state", "aanvraag_status", "approval_status")),
    );
}

function competition_mail_team_join(PDO $pdo)
{
    if (!boules_table_exists($pdo, "teams")) {
        return array(
            "join" => "",
            "select" => "'jullie team' AS `team_name`",
        );
    }

    $teamId = competition_mail_first_column($pdo, "teams", array("team_id", "id"));
    $teamName = competition_mail_first_column($pdo, "teams", array("team_name", "team", "name", "naam"));

    if (!$teamId || !$teamName) {
        return array(
            "join" => "",
            "select" => "'jullie team' AS `team_name`",
        );
    }

    $registrationColumns = competition_mail_registration_columns($pdo);

    return array(
        "join" => "LEFT JOIN `teams` team_data ON team_data.`$teamId` = tr.`{$registrationColumns["team_id"]}`",
        "select" => "COALESCE(NULLIF(team_data.`$teamName`, ''), 'jullie team') AS `team_name`",
    );
}

function competition_mail_fetch_registration_context(PDO $pdo, $registrationId)
{
    competition_mail_require_table($pdo, "tournament_registrations");
    competition_mail_require_table($pdo, "tournaments");

    $registrationColumns = competition_mail_registration_columns($pdo);
    $tournamentColumns = competition_mail_tournament_columns($pdo);
    $teamJoin = competition_mail_team_join($pdo);
    $locationSelect = $tournamentColumns["location"]
        ? "COALESCE(t.`{$tournamentColumns["location"]}`, '') AS `location`"
        : "'' AS `location`";

    $statement = $pdo->prepare(
        "SELECT " .
        "tr.`{$registrationColumns["id"]}` AS `registration_id`, " .
        "tr.`{$registrationColumns["tournament_id"]}` AS `tournament_id`, " .
        "tr.`{$registrationColumns["team_id"]}` AS `team_id`, " .
        "t.`{$tournamentColumns["name"]}` AS `competition_name`, " .
        "t.`{$tournamentColumns["start_date"]}` AS `start_date`, " .
        "$locationSelect, " .
        $teamJoin["select"] . " " .
        "FROM `tournament_registrations` tr " .
        "INNER JOIN `tournaments` t ON t.`{$tournamentColumns["id"]}` = tr.`{$registrationColumns["tournament_id"]}` " .
        $teamJoin["join"] . " " .
        "WHERE tr.`{$registrationColumns["id"]}` = ? LIMIT 1"
    );
    $statement->execute(array($registrationId));
    $context = $statement->fetch();

    if (!$context) {
        throw new RuntimeException("Registratie $registrationId niet gevonden.");
    }

    return $context;
}

function competition_mail_find_registration_id(PDO $pdo, $tournamentId, $teamId)
{
    $registrationColumns = competition_mail_registration_columns($pdo);

    $statement = $pdo->prepare(
        "SELECT tr.`{$registrationColumns["id"]}` " .
        "FROM `tournament_registrations` tr " .
        "WHERE tr.`{$registrationColumns["tournament_id"]}` = ? " .
        "AND tr.`{$registrationColumns["team_id"]}` = ? " .
        "ORDER BY tr.`{$registrationColumns["id"]}` DESC LIMIT 1"
    );
    $statement->execute(array($tournamentId, $teamId));
    $registrationId = $statement->fetchColumn();

    return $registrationId === false ? null : $registrationId;
}

function competition_mail_fetch_team_recipients(PDO $pdo, $teamId)
{
    competition_mail_require_table($pdo, "team_members");
    competition_mail_require_table($pdo, "users");
    competition_mail_add_column_if_missing($pdo, "users", "email_notifications", "`email_notifications` tinyint(1) NOT NULL DEFAULT 0");

    $teamMemberTeamId = competition_mail_require_column($pdo, "team_members", array("team_id", "teamId"), "team-id");
    $teamMemberUserId = competition_mail_require_column($pdo, "team_members", array("user_id", "userId", "id_user"), "user-id");
    $userId = competition_mail_require_column($pdo, "users", array("user_id", "id"), "user-id");
    $email = competition_mail_require_column($pdo, "users", array("email", "emailadres"), "e-mail");
    $name = competition_mail_first_column($pdo, "users", array("username", "name", "naam", "full_name", "gebruikersnaam"));
    $nameExpression = $name
        ? "COALESCE(NULLIF(u.`$name`, ''), u.`$email`) AS `name`"
        : "u.`$email` AS `name`";

    $statement = $pdo->prepare(
        "SELECT DISTINCT u.`$userId` AS `user_id`, $nameExpression, u.`$email` AS `email` " .
        "FROM `team_members` tm " .
        "INNER JOIN `users` u ON u.`$userId` = tm.`$teamMemberUserId` " .
        "WHERE tm.`$teamMemberTeamId` = ? " .
        "AND u.`$email` IS NOT NULL " .
        "AND u.`$email` != '' " .
        "AND u.`email_notifications` = 1"
    );
    $statement->execute(array($teamId));

    $recipients = array();
    $seen = array();

    foreach ($statement->fetchAll() as $recipient) {
        $emailAddress = strtolower(trim((string) $recipient["email"]));

        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL) || isset($seen[$emailAddress])) {
            continue;
        }

        $seen[$emailAddress] = true;
        $recipient["email"] = $emailAddress;
        $recipients[] = $recipient;
    }

    return $recipients;
}

function competition_mail_log_exists(PDO $pdo, $eventType, $registrationId, $userId, $email)
{
    competition_mail_ensure_log_table($pdo);

    $statement = $pdo->prepare(
        "SELECT COUNT(*) FROM `tournament_email_logs` " .
        "WHERE `event_type` = ? " .
        "AND `registration_id` = ? " .
        "AND `user_id` = ? " .
        "AND `email` = ?"
    );
    $statement->execute(array($eventType, $registrationId, $userId, $email));

    return (int) $statement->fetchColumn() > 0;
}

function competition_mail_log_sent(PDO $pdo, array $context, array $recipient, $eventType)
{
    competition_mail_ensure_log_table($pdo);

    $statement = $pdo->prepare(
        "INSERT INTO `tournament_email_logs` " .
        "(`registration_id`, `tournament_id`, `team_id`, `user_id`, `email`, `event_type`) " .
        "VALUES (?, ?, ?, ?, ?, ?)"
    );
    $statement->execute(array(
        $context["registration_id"],
        $context["tournament_id"],
        $context["team_id"],
        $recipient["user_id"],
        $recipient["email"],
        $eventType,
    ));
}

function competition_mail_build_message($eventType, array $context, array $recipient)
{
    $playerName = competition_mail_text($recipient["name"], "speler");
    $competitionName = competition_mail_text($context["competition_name"], "de competitie");
    $teamName = competition_mail_text($context["team_name"], "jullie team");
    $dateLabel = competition_mail_format_date($context["start_date"]);
    $location = competition_mail_text($context["location"], "Locatie volgt");

    if ($eventType === COMPETITION_MAIL_EVENT_REMINDER_1_DAY) {
        $subject = "Morgen start: " . $competitionName;
        $intro = "Morgen start de competitie waarvoor jullie team is aangemeld.";
        $action = "Zorg dat jullie op tijd aanwezig zijn.";
    } else {
        $subject = "Aanmelding bevestigd: " . $competitionName;
        $intro = "Jullie team is aangemeld voor deze competitie.";
        $action = "We sturen later nog een herinnering vlak voor de start.";
    }

    $html = "<!doctype html>" .
        "<html lang=\"nl\"><head><meta charset=\"UTF-8\"><title>" . competition_mail_escape($subject) . "</title></head>" .
        "<body style=\"font-family: Arial, sans-serif; color: #1f2933; line-height: 1.5;\">" .
        "<h2>Hoi " . competition_mail_escape($playerName) . ",</h2>" .
        "<p>" . competition_mail_escape($intro) . "</p>" .
        "<p><strong>Team:</strong> " . competition_mail_escape($teamName) . "</p>" .
        "<p><strong>Competitie:</strong> " . competition_mail_escape($competitionName) . "</p>" .
        "<p><strong>Startdatum:</strong> " . competition_mail_escape($dateLabel) . "</p>" .
        "<p><strong>Locatie:</strong> " . competition_mail_escape($location) . "</p>" .
        "<p>" . competition_mail_escape($action) . "</p>" .
        "<br>" .
        "<p>Met vriendelijke groet,<br>De organisatie</p>" .
        "</body></html>";

    $text = "Hoi $playerName,\n\n" .
        "$intro\n\n" .
        "Team: $teamName\n" .
        "Competitie: $competitionName\n" .
        "Startdatum: $dateLabel\n" .
        "Locatie: $location\n\n" .
        "$action\n\n" .
        "Met vriendelijke groet,\nDe organisatie";

    return array(
        "subject" => $subject,
        "html" => $html,
        "text" => $text,
    );
}

function competition_mail_empty_summary()
{
    return array(
        "sent" => 0,
        "skipped" => 0,
        "failed" => 0,
        "errors" => array(),
    );
}

function competition_mail_merge_summary(array $left, array $right)
{
    return array(
        "sent" => $left["sent"] + $right["sent"],
        "skipped" => $left["skipped"] + $right["skipped"],
        "failed" => $left["failed"] + $right["failed"],
        "errors" => array_merge($left["errors"], $right["errors"]),
    );
}

function competition_mail_send_registration_event(PDO $pdo, $registrationId, $eventType, array $options = array())
{
    $context = competition_mail_fetch_registration_context($pdo, $registrationId);
    $recipients = competition_mail_fetch_team_recipients($pdo, $context["team_id"]);
    $summary = competition_mail_empty_summary();
    $force = isset($options["force"]) && $options["force"] === true;
    $overrideEmail = isset($options["override_email"]) ? trim((string) $options["override_email"]) : "";
    $logSends = array_key_exists("log_sends", $options) ? $options["log_sends"] === true : $overrideEmail === "";
    $messageLimit = isset($options["message_limit"]) ? (int) $options["message_limit"] : 0;

    foreach ($recipients as $recipient) {
        if ($messageLimit > 0 && $summary["sent"] >= $messageLimit) {
            break;
        }

        if (!$force && competition_mail_log_exists($pdo, $eventType, $context["registration_id"], $recipient["user_id"], $recipient["email"])) {
            $summary["skipped"]++;
            continue;
        }

        $message = competition_mail_build_message($eventType, $context, $recipient);
        $sendToEmail = $overrideEmail !== "" ? $overrideEmail : $recipient["email"];

        try {
            boules_send_html_mail($sendToEmail, $recipient["name"], $message["subject"], $message["html"], $message["text"]);

            if ($logSends) {
                competition_mail_log_sent($pdo, $context, $recipient, $eventType);
            }

            $summary["sent"]++;
        } catch (Throwable $exception) {
            $summary["failed"]++;
            $summary["errors"][] = $recipient["email"] . ": " . $exception->getMessage();
        }
    }

    return $summary;
}

function competition_mail_send_registration_confirmation(PDO $pdo, $registrationId, array $options = array())
{
    return competition_mail_send_registration_event($pdo, $registrationId, COMPETITION_MAIL_EVENT_REGISTRATION, $options);
}

function competition_mail_send_registration_confirmation_for_team(PDO $pdo, $tournamentId, $teamId, array $options = array())
{
    $registrationId = competition_mail_find_registration_id($pdo, $tournamentId, $teamId);

    if (!$registrationId) {
        throw new RuntimeException("Geen registratie gevonden voor competitie $tournamentId en team $teamId.");
    }

    return competition_mail_send_registration_confirmation($pdo, $registrationId, $options);
}

function competition_mail_active_registration_status_clause(PDO $pdo, array $registrationColumns)
{
    if (!$registrationColumns["status"]) {
        return "";
    }

    return " AND (tr.`{$registrationColumns["status"]}` IS NULL OR LOWER(tr.`{$registrationColumns["status"]}`) IN " .
        "('pending', 'accepted', 'confirmed', 'geaccepteert', 'aangemeld', 'ingeschreven'))";
}

function competition_mail_active_tournament_status_clause(PDO $pdo, array $tournamentColumns)
{
    if (!$tournamentColumns["status"]) {
        return "";
    }

    return " AND (t.`{$tournamentColumns["status"]}` IS NULL OR LOWER(t.`{$tournamentColumns["status"]}`) NOT IN " .
        "('pending', 'in afwachting', 'aangevraagd', 'rejected', 'denied', 'afgewezen'))";
}

function competition_mail_fetch_registration_ids_for_reminder(PDO $pdo, $targetDate)
{
    $registrationColumns = competition_mail_registration_columns($pdo);
    $tournamentColumns = competition_mail_tournament_columns($pdo);

    $statement = $pdo->prepare(
        "SELECT DISTINCT tr.`{$registrationColumns["id"]}` AS `registration_id` " .
        "FROM `tournament_registrations` tr " .
        "INNER JOIN `tournaments` t ON t.`{$tournamentColumns["id"]}` = tr.`{$registrationColumns["tournament_id"]}` " .
        "WHERE DATE(t.`{$tournamentColumns["start_date"]}`) = ? " .
        competition_mail_active_registration_status_clause($pdo, $registrationColumns) .
        competition_mail_active_tournament_status_clause($pdo, $tournamentColumns) .
        " ORDER BY tr.`{$registrationColumns["id"]}` ASC"
    );
    $statement->execute(array($targetDate));

    return array_map(function ($row) {
        return $row["registration_id"];
    }, $statement->fetchAll());
}

function competition_mail_fetch_active_registration_ids(PDO $pdo)
{
    $registrationColumns = competition_mail_registration_columns($pdo);
    $tournamentColumns = competition_mail_tournament_columns($pdo);

    $statement = $pdo->query(
        "SELECT DISTINCT tr.`{$registrationColumns["id"]}` AS `registration_id` " .
        "FROM `tournament_registrations` tr " .
        "INNER JOIN `tournaments` t ON t.`{$tournamentColumns["id"]}` = tr.`{$registrationColumns["tournament_id"]}` " .
        "WHERE 1 = 1 " .
        competition_mail_active_registration_status_clause($pdo, $registrationColumns) .
        competition_mail_active_tournament_status_clause($pdo, $tournamentColumns) .
        " ORDER BY tr.`{$registrationColumns["id"]}` ASC"
    );

    return array_map(function ($row) {
        return $row["registration_id"];
    }, $statement->fetchAll());
}

function competition_mail_send_one_day_reminders(PDO $pdo, $targetDate = null, array $options = array())
{
    if ($targetDate === null || trim((string) $targetDate) === "") {
        $targetDate = (new DateTime("tomorrow"))->format("Y-m-d");
    }

    $summary = competition_mail_empty_summary();
    $registrationIds = competition_mail_fetch_registration_ids_for_reminder($pdo, $targetDate);
    $messageLimit = isset($options["message_limit"]) ? (int) $options["message_limit"] : 0;

    foreach ($registrationIds as $registrationId) {
        if ($messageLimit > 0 && $summary["sent"] >= $messageLimit) {
            break;
        }

        $registrationOptions = $options;

        if ($messageLimit > 0) {
            $registrationOptions["message_limit"] = $messageLimit - $summary["sent"];
        }

        $summary = competition_mail_merge_summary(
            $summary,
            competition_mail_send_registration_event($pdo, $registrationId, COMPETITION_MAIL_EVENT_REMINDER_1_DAY, $registrationOptions)
        );
    }

    return $summary;
}
