<?php

header("Content-Type: application/json; charset=utf-8");

session_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/bootstrap-data.php";
require_once __DIR__ . "/../Functions/competition_mailer.php";

function competition_registrations_respond($statusCode, array $payload)
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function competition_registrations_json_body()
{
    $data = json_decode(file_get_contents("php://input"), true);

    return is_array($data) ? $data : array();
}

function competition_registrations_current_user_id()
{
    $userId = isset($_SESSION["user_id"]) ? trim((string) $_SESSION["user_id"]) : "";

    return $userId !== "" && is_numeric($userId) ? $userId : "";
}

function competition_registrations_is_admin()
{
    return isset($_SESSION["role"]) && $_SESSION["role"] === "admin";
}

function competition_registrations_add_column_if_missing($column, $definition)
{
    global $pdo;

    if (!in_array($column, boules_table_columns($pdo, "tournament_registrations"), true)) {
        $pdo->exec("ALTER TABLE `tournament_registrations` ADD COLUMN $definition");
    }
}

function competition_registrations_ensure_schema()
{
    global $pdo;

    if (!boules_table_exists($pdo, "tournament_registrations")) {
        $pdo->exec(
            "CREATE TABLE `tournament_registrations` (" .
            "`registration_id` int(11) NOT NULL AUTO_INCREMENT, " .
            "`tournament_id` int(11) NOT NULL, " .
            "`team_id` int(11) NOT NULL, " .
            "`status` varchar(50) NOT NULL DEFAULT 'accepted', " .
            "`created_by` int(11) DEFAULT NULL, " .
            "`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, " .
            "PRIMARY KEY (`registration_id`), " .
            "UNIQUE KEY `uniq_tournament_team` (`tournament_id`, `team_id`)" .
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        return;
    }

    $columns = boules_table_columns($pdo, "tournament_registrations");

    if (!boules_first_existing_column($columns, array("tournament_id", "competition_id"))) {
        competition_registrations_add_column_if_missing("tournament_id", "`tournament_id` int(11) NOT NULL DEFAULT 0");
    }

    if (!boules_first_existing_column($columns, array("team_id", "teamId"))) {
        competition_registrations_add_column_if_missing("team_id", "`team_id` int(11) NOT NULL DEFAULT 0");
    }

    if (!boules_first_existing_column($columns, array("status", "state"))) {
        competition_registrations_add_column_if_missing("status", "`status` varchar(50) NOT NULL DEFAULT 'accepted'");
    }

    if (!boules_first_existing_column($columns, array("created_by", "createdBy", "user_id"))) {
        competition_registrations_add_column_if_missing("created_by", "`created_by` int(11) DEFAULT NULL");
    }

    if (!boules_first_existing_column($columns, array("created_at", "createdAt", "aangemaakt_op"))) {
        competition_registrations_add_column_if_missing("created_at", "`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }
}

function competition_registrations_tournament_exists($tournamentId)
{
    global $pdo;

    competition_mail_require_table($pdo, "tournaments");
    $columns = competition_mail_tournament_columns($pdo);
    $statement = $pdo->prepare("SELECT COUNT(*) FROM `tournaments` WHERE `{$columns["id"]}` = ?");
    $statement->execute(array($tournamentId));

    return (int) $statement->fetchColumn() > 0;
}

function competition_registrations_team_exists($teamId)
{
    global $pdo;

    if (boules_table_exists($pdo, "teams")) {
        $teamIdColumn = competition_mail_first_column($pdo, "teams", array("team_id", "id"));

        if ($teamIdColumn) {
            $statement = $pdo->prepare("SELECT COUNT(*) FROM `teams` WHERE `$teamIdColumn` = ?");
            $statement->execute(array($teamId));

            return (int) $statement->fetchColumn() > 0;
        }
    }

    competition_mail_require_table($pdo, "team_members");
    $teamMemberTeamId = competition_mail_require_column($pdo, "team_members", array("team_id", "teamId"), "team-id");
    $statement = $pdo->prepare("SELECT COUNT(*) FROM `team_members` WHERE `$teamMemberTeamId` = ?");
    $statement->execute(array($teamId));

    return (int) $statement->fetchColumn() > 0;
}

function competition_registrations_user_belongs_to_team($userId, $teamId)
{
    global $pdo;

    competition_mail_require_table($pdo, "team_members");
    $teamMemberTeamId = competition_mail_require_column($pdo, "team_members", array("team_id", "teamId"), "team-id");
    $teamMemberUserId = competition_mail_require_column($pdo, "team_members", array("user_id", "userId", "id_user"), "user-id");
    $statement = $pdo->prepare(
        "SELECT COUNT(*) FROM `team_members` " .
        "WHERE `$teamMemberTeamId` = ? AND `$teamMemberUserId` = ?"
    );
    $statement->execute(array($teamId, $userId));

    return (int) $statement->fetchColumn() > 0;
}

function competition_registrations_existing_registration($tournamentId, $teamId)
{
    global $pdo;

    $columns = competition_mail_registration_columns($pdo);
    $statement = $pdo->prepare(
        "SELECT `{$columns["id"]}` FROM `tournament_registrations` " .
        "WHERE `{$columns["tournament_id"]}` = ? AND `{$columns["team_id"]}` = ? " .
        "ORDER BY `{$columns["id"]}` DESC LIMIT 1"
    );
    $statement->execute(array($tournamentId, $teamId));
    $registrationId = $statement->fetchColumn();

    return $registrationId === false ? null : $registrationId;
}

function competition_registrations_insert_registration($tournamentId, $teamId, $userId)
{
    global $pdo;

    $registrationColumns = competition_mail_registration_columns($pdo);
    $tableColumns = boules_table_columns($pdo, "tournament_registrations");
    $insert = array(
        $registrationColumns["tournament_id"] => $tournamentId,
        $registrationColumns["team_id"] => $teamId,
    );

    if ($registrationColumns["status"]) {
        $insert[$registrationColumns["status"]] = "accepted";
    }

    $createdBy = boules_first_existing_column($tableColumns, array("created_by", "createdBy", "user_id"));
    if ($createdBy && $userId !== "") {
        $insert[$createdBy] = $userId;
    }

    $createdAt = boules_first_existing_column($tableColumns, array("created_at", "createdAt", "aangemaakt_op"));
    if ($createdAt) {
        $insert[$createdAt] = date("Y-m-d H:i:s");
    }

    $columnSql = implode(", ", array_map(function ($column) {
        return "`$column`";
    }, array_keys($insert)));
    $placeholderSql = implode(", ", array_fill(0, count($insert), "?"));

    $statement = $pdo->prepare("INSERT INTO `tournament_registrations` ($columnSql) VALUES ($placeholderSql)");
    $statement->execute(array_values($insert));

    $newId = $pdo->lastInsertId();

    if ($newId) {
        return $newId;
    }

    return competition_registrations_existing_registration($tournamentId, $teamId);
}

function competition_registrations_register_team(array $data)
{
    global $pdo;

    $userId = competition_registrations_current_user_id();

    if ($userId === "") {
        competition_registrations_respond(401, array("error" => "Log in om je team aan te melden."));
    }

    $tournamentId = isset($data["tournamentId"]) ? trim((string) $data["tournamentId"]) : "";
    $teamId = isset($data["teamId"]) ? trim((string) $data["teamId"]) : "";

    if ($tournamentId === "" || !is_numeric($tournamentId) || $teamId === "" || !is_numeric($teamId)) {
        competition_registrations_respond(422, array("error" => "Competitie-id en team-id zijn verplicht."));
    }

    try {
        competition_registrations_ensure_schema();

        if (!competition_registrations_tournament_exists($tournamentId)) {
            competition_registrations_respond(404, array("error" => "Competitie niet gevonden."));
        }

        if (!competition_registrations_team_exists($teamId)) {
            competition_registrations_respond(404, array("error" => "Team niet gevonden."));
        }

        if (!competition_registrations_is_admin() && !competition_registrations_user_belongs_to_team($userId, $teamId)) {
            competition_registrations_respond(403, array("error" => "Je kunt alleen je eigen team aanmelden."));
        }

        $registrationId = competition_registrations_existing_registration($tournamentId, $teamId);
        $created = false;

        if (!$registrationId) {
            $registrationId = competition_registrations_insert_registration($tournamentId, $teamId, $userId);
            $created = true;
        }

        $mailSummary = competition_mail_send_registration_confirmation($pdo, $registrationId);

        competition_registrations_respond($created ? 201 : 200, array(
            "registered" => true,
            "created" => $created,
            "registrationId" => (string) $registrationId,
            "mail" => $mailSummary,
        ));
    } catch (Throwable $exception) {
        competition_registrations_respond(500, array("error" => "Team kon niet worden aangemeld: " . $exception->getMessage()));
    }
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    competition_registrations_respond(405, array("error" => "Alleen POST is toegestaan."));
}

$data = competition_registrations_json_body();
$action = isset($data["action"]) ? (string) $data["action"] : "";

if ($action === "registerTeam" || $action === "register") {
    competition_registrations_register_team($data);
}

competition_registrations_respond(400, array("error" => "Onbekende registratie-actie."));
