<?php

header("Content-Type: application/json; charset=utf-8");

session_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/bootstrap-data.php";

function competitions_respond($statusCode, array $payload)
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function competitions_json_body()
{
    $data = json_decode(file_get_contents("php://input"), true);

    return is_array($data) ? $data : array();
}

function competitions_is_admin()
{
    if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin") {
        return true;
    }

    $userId = competitions_current_user_id();

    return $userId !== "" && competitions_user_is_admin($userId);
}

function competitions_user_is_admin($userId)
{
    global $pdo;

    if (!boules_table_exists($pdo, "users")) {
        return false;
    }

    $userColumns = boules_table_columns($pdo, "users");
    $userIdColumn = boules_first_existing_column($userColumns, array("user_id", "id"));
    $roleColumn = boules_first_existing_column($userColumns, array("role", "rol"));

    if (!$userIdColumn) {
        return false;
    }

    if ($roleColumn) {
        $statement = $pdo->prepare("SELECT `$roleColumn` FROM `users` WHERE `$userIdColumn` = ? LIMIT 1");
        $statement->execute(array($userId));

        return competitions_role_is_admin($statement->fetchColumn());
    }

    if (!boules_table_exists($pdo, "user_roles") || !boules_table_exists($pdo, "roles")) {
        return false;
    }

    $userRoleColumns = boules_table_columns($pdo, "user_roles");
    $roleColumns = boules_table_columns($pdo, "roles");
    $userRoleUserId = boules_first_existing_column($userRoleColumns, array("user_id", "userId", "id_user"));
    $userRoleRoleId = boules_first_existing_column($userRoleColumns, array("role_id", "roleId", "id_role"));
    $roleId = boules_first_existing_column($roleColumns, array("role_id", "id"));
    $roleName = boules_first_existing_column($roleColumns, array("role_name", "name", "role", "rol", "title"));

    if (!$userRoleUserId || !$userRoleRoleId || !$roleId || !$roleName) {
        return false;
    }

    $statement = $pdo->prepare(
        "SELECT r.`$roleName` FROM `user_roles` ur " .
        "INNER JOIN `roles` r ON r.`$roleId` = ur.`$userRoleRoleId` " .
        "WHERE ur.`$userRoleUserId` = ?"
    );
    $statement->execute(array($userId));

    foreach ($statement->fetchAll() as $role) {
        if (competitions_role_is_admin($role[$roleName])) {
            return true;
        }
    }

    return false;
}

function competitions_role_is_admin($role)
{
    $roleValue = strtolower(trim((string) $role));

    return $roleValue === "admin" || $roleValue === "administrator" || $roleValue === "beheerder";
}

function competitions_current_user_id()
{
    $userId = isset($_SESSION["user_id"]) ? trim((string) $_SESSION["user_id"]) : "";

    return $userId !== "" && is_numeric($userId) ? $userId : "";
}

function competitions_require_admin()
{
    if (!competitions_is_admin()) {
        competitions_respond(403, array("error" => "Alleen admins mogen deze actie uitvoeren."));
    }
}

function competitions_status_for_action($action)
{
    return $action === "create" ? "accepted" : "pending";
}

function competitions_public_record(array $row)
{
    $tournamentId = (string) $row["tournament_id"];

    return array(
        "id" => $tournamentId,
        "title" => (string) $row["name"],
        "type" => isset($row["location"]) && trim((string) $row["location"]) !== "" ? (string) $row["location"] : "Toernooi",
        "startDate" => (string) $row["start_date"],
        "endDate" => isset($row["end_date"]) ? (string) $row["end_date"] : "",
        "tone" => isset($row["tone"]) && trim((string) $row["tone"]) !== "" ? (string) $row["tone"] : "green",
        "status" => isset($row["status"]) ? (string) $row["status"] : "",
        "requesterName" => isset($row["requester_name"]) ? competitions_first_name($row["requester_name"]) : "",
        "registeredTeamIds" => competitions_registered_team_ids($tournamentId),
        "href" => "#competities",
    );
}

function competitions_registered_team_ids($tournamentId)
{
    global $pdo;

    if ($tournamentId === "" || !boules_table_exists($pdo, "tournament_registrations")) {
        return array();
    }

    $columns = boules_table_columns($pdo, "tournament_registrations");
    $tournamentColumn = boules_first_existing_column($columns, array("tournament_id", "competition_id", "id_tournament", "id_competition"));
    $teamColumn = boules_first_existing_column($columns, array("team_id", "id_team"));

    if (!$tournamentColumn || !$teamColumn) {
        return array();
    }

    $statement = $pdo->prepare("SELECT `$teamColumn` AS `team_id` FROM `tournament_registrations` WHERE `$tournamentColumn` = ?");
    $statement->execute(array($tournamentId));

    $teamIds = array();
    foreach ($statement->fetchAll() as $row) {
        if (isset($row["team_id"])) {
            $teamIds[] = (string) $row["team_id"];
        }
    }

    return $teamIds;
}

function competitions_first_name($name)
{
    $name = trim((string) $name);

    if ($name === "") {
        return "";
    }

    $parts = preg_split('/\s+/', $name);

    return $parts && isset($parts[0]) ? $parts[0] : $name;
}

function competitions_ensure_tournaments_schema()
{
    global $pdo;

    if (!boules_table_exists($pdo, "tournaments")) {
        competitions_respond(500, array("error" => "Tabel tournaments ontbreekt. Gebruik de bestaande database-tabellen."));
    }

    competitions_ensure_status_column();
    competitions_ensure_tone_column();

    $requiredColumns = array("tournament_id", "name", "start_date", "end_date", "location", "created_by", "status", "tone");
    foreach ($requiredColumns as $column) {
        if (!competitions_tournament_has_column($column)) {
            competitions_respond(500, array("error" => "Kolom $column ontbreekt in tournaments."));
        }
    }
}

function competitions_tournament_has_column($column)
{
    global $pdo;

    return in_array($column, boules_table_columns($pdo, "tournaments"), true);
}

function competitions_ensure_status_column()
{
    global $pdo;

    if (!competitions_tournament_has_column("status")) {
        $pdo->exec("ALTER TABLE `tournaments` ADD COLUMN `status` VARCHAR(32) NOT NULL DEFAULT 'pending'");
    }
}

function competitions_ensure_tone_column()
{
    global $pdo;

    if (!competitions_tournament_has_column("tone")) {
        $pdo->exec("ALTER TABLE `tournaments` ADD COLUMN `tone` VARCHAR(32) NOT NULL DEFAULT '#7b9151'");
    }
}

function competitions_select_columns()
{
    $columns = array("`tournament_id`", "`name`", "`start_date`", "`end_date`", "`location`", "`status`");

    $columns[] = "`tone`";

    return implode(", ", $columns);
}

function competitions_requester_name_expression()
{
    global $pdo;

    if (!boules_table_exists($pdo, "users") || !competitions_tournament_has_column("created_by")) {
        return "'' AS `requester_name`";
    }

    $userColumns = boules_table_columns($pdo, "users");
    $userId = boules_first_existing_column($userColumns, array("user_id", "id"));
    $userName = boules_first_existing_column($userColumns, array("name", "naam", "full_name", "username", "gebruikersnaam", "email"));

    if (!$userId || !$userName) {
        return "'' AS `requester_name`";
    }

    return "(SELECT u.`$userName` FROM `users` u WHERE u.`$userId` = `tournaments`.`created_by` LIMIT 1) AS `requester_name`";
}

function competitions_pending_select_columns()
{
    return competitions_select_columns() . ", " . competitions_requester_name_expression();
}

function competitions_fetch_tournament($competitionId)
{
    global $pdo;

    $statement = $pdo->prepare("SELECT " . competitions_select_columns() . " FROM `tournaments` WHERE `tournament_id` = ? LIMIT 1");
    $statement->execute(array($competitionId));

    return $statement->fetch();
}

function competitions_validate_payload(array $competition)
{
    $title = isset($competition["title"]) ? trim((string) $competition["title"]) : "";
    $location = isset($competition["type"]) ? trim((string) $competition["type"]) : "";
    $startDate = isset($competition["startDate"]) ? trim((string) $competition["startDate"]) : "";
    $endDate = isset($competition["endDate"]) ? trim((string) $competition["endDate"]) : "";
    $tone = isset($competition["tone"]) ? trim((string) $competition["tone"]) : "#7b9151";

    if ($title === "" || $location === "" || $startDate === "" || $endDate === "") {
        competitions_respond(422, array("error" => "Vul naam, startdatum, einddatum en locatie in."));
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        competitions_respond(422, array("error" => "Gebruik geldige datums."));
    }

    if ($endDate < $startDate) {
        competitions_respond(422, array("error" => "Einddatum mag niet voor startdatum liggen."));
    }

    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $tone) && !in_array($tone, array("green", "yellow", "red", "olive"), true)) {
        $tone = "#7b9151";
    }

    return array(
        "title" => $title,
        "location" => $location,
        "startDate" => $startDate,
        "endDate" => $endDate,
        "tone" => $tone,
    );
}

function competitions_create_tournament(array $competition, $action)
{
    global $pdo;

    $createdBy = competitions_current_user_id();
    if ($createdBy === "") {
        $createdBy = null;
    }

    $payload = competitions_validate_payload($competition);
    if ($action === "create") {
        competitions_require_admin();
    }
    $status = competitions_status_for_action($action);
    $columns = array("`name`", "`start_date`", "`end_date`", "`location`", "`created_by`", "`status`", "`tone`");
    $values = array($payload["title"], $payload["startDate"], $payload["endDate"], $payload["location"], $createdBy, $status, $payload["tone"]);

    try {
        $statement = $pdo->prepare(
            "INSERT INTO `tournaments` (" . implode(", ", $columns) . ") VALUES (" . implode(", ", array_fill(0, count($values), "?")) . ")"
        );
        $statement->execute($values);
    } catch (PDOException $exception) {
        competitions_respond(500, array("error" => "Competitie kon niet worden opgeslagen: " . $exception->getMessage()));
    }

    competitions_respond(201, array(
        "competition" => array(
            "id" => (string) $pdo->lastInsertId(),
            "title" => $payload["title"],
            "type" => $payload["location"],
            "startDate" => $payload["startDate"],
            "endDate" => $payload["endDate"],
            "tone" => $payload["tone"],
            "status" => $status,
            "href" => "#competities",
        ),
    ));
}

function competitions_list_pending()
{
    global $pdo;

    $statement = $pdo->query(
        "SELECT " . competitions_pending_select_columns() . " FROM `tournaments` " .
        "WHERE LOWER(`status`) IN ('pending', 'in afwachting', 'aangevraagd') ORDER BY `start_date` ASC, `tournament_id` ASC"
    );

    competitions_respond(200, array(
        "competitions" => array_map("competitions_public_record", $statement->fetchAll()),
    ));
}

function competitions_update_status(array $data, $status)
{
    global $pdo;

    $competitionId = isset($data["id"]) ? trim((string) $data["id"]) : "";

    if ($competitionId === "" || !is_numeric($competitionId)) {
        competitions_respond(422, array("error" => "Competitie-id ontbreekt."));
    }

    $statement = $pdo->prepare("UPDATE `tournaments` SET `status` = ? WHERE `tournament_id` = ?");
    $statement->execute(array($status, $competitionId));

    if ($statement->rowCount() === 0) {
        competitions_respond(404, array("error" => "Competitieaanvraag niet gevonden."));
    }

    $competition = competitions_fetch_tournament($competitionId);

    competitions_respond(200, array(
        "competition" => $competition ? competitions_public_record($competition) : null,
    ));
}

function competitions_update_tournament(array $data)
{
    global $pdo;

    $competitionId = isset($data["id"]) ? trim((string) $data["id"]) : "";

    if ($competitionId === "" || !is_numeric($competitionId)) {
        competitions_respond(422, array("error" => "Competitie-id ontbreekt."));
    }

    $payload = competitions_validate_payload(isset($data["competition"]) && is_array($data["competition"]) ? $data["competition"] : array());
    $setParts = array("`name` = ?", "`start_date` = ?", "`end_date` = ?", "`location` = ?", "`tone` = ?");
    $values = array($payload["title"], $payload["startDate"], $payload["endDate"], $payload["location"], $payload["tone"]);

    $values[] = $competitionId;

    try {
        $statement = $pdo->prepare("UPDATE `tournaments` SET " . implode(", ", $setParts) . " WHERE `tournament_id` = ?");
        $statement->execute($values);
    } catch (PDOException $exception) {
        competitions_respond(500, array("error" => "Competitie kon niet worden bijgewerkt: " . $exception->getMessage()));
    }

    $competition = competitions_fetch_tournament($competitionId);

    if (!$competition) {
        competitions_respond(404, array("error" => "Competitie niet gevonden."));
    }

    competitions_respond(200, array(
        "competition" => competitions_public_record($competition),
    ));
}

function competitions_delete_tournament(array $data)
{
    global $pdo;

    $competitionId = isset($data["id"]) ? trim((string) $data["id"]) : "";

    if ($competitionId === "" || !is_numeric($competitionId)) {
        competitions_respond(422, array("error" => "Competitie-id ontbreekt."));
    }

    try {
        $statement = $pdo->prepare("DELETE FROM `tournaments` WHERE `tournament_id` = ?");
        $statement->execute(array($competitionId));
    } catch (PDOException $exception) {
        competitions_respond(500, array("error" => "Competitie kon niet worden verwijderd: " . $exception->getMessage()));
    }

    if ($statement->rowCount() === 0) {
        competitions_respond(404, array("error" => "Competitie niet gevonden."));
    }

    competitions_respond(200, array("deleted" => true, "id" => $competitionId));
}

function competitions_team_belongs_to_user($teamId, $userId)
{
    global $pdo;

    if ($teamId === "" || $userId === "" || !boules_table_exists($pdo, "team_members")) {
        return false;
    }

    $columns = boules_table_columns($pdo, "team_members");
    $teamColumn = boules_first_existing_column($columns, array("team_id", "id_team"));
    $userColumn = boules_first_existing_column($columns, array("user_id", "id_user"));

    if (!$teamColumn || !$userColumn) {
        return false;
    }

    $statement = $pdo->prepare("SELECT COUNT(*) FROM `team_members` WHERE `$teamColumn` = ? AND `$userColumn` = ?");
    $statement->execute(array($teamId, $userId));

    return (int) $statement->fetchColumn() > 0;
}

function competitions_registration_status_accepts($statusColumn, $status)
{
    global $pdo;

    $statement = $pdo->prepare("SHOW COLUMNS FROM `tournament_registrations` LIKE ?");
    $statement->execute(array($statusColumn));
    $column = $statement->fetch();

    if (!$column || !isset($column["Type"])) {
        return false;
    }

    $type = (string) $column["Type"];
    if (stripos($type, "enum(") !== 0 && stripos($type, "set(") !== 0) {
        return true;
    }

    return strpos($type, "'" . str_replace("'", "\\'", $status) . "'") !== false;
}

function competitions_register_team(array $data)
{
    global $pdo;

    if (!boules_table_exists($pdo, "tournament_registrations")) {
        competitions_respond(500, array("error" => "Tabel tournament_registrations ontbreekt."));
    }

    $competitionId = isset($data["competitionId"]) ? trim((string) $data["competitionId"]) : "";
    $teamId = isset($data["teamId"]) ? trim((string) $data["teamId"]) : "";
    $userId = competitions_current_user_id();

    if ($competitionId === "" || !is_numeric($competitionId) || $teamId === "" || !is_numeric($teamId)) {
        competitions_respond(422, array("error" => "Competitie-id of team-id ontbreekt."));
    }

    if ($userId === "") {
        competitions_respond(401, array("error" => "Log opnieuw in om een team aan te melden."));
    }

    if (!competitions_team_belongs_to_user($teamId, $userId)) {
        competitions_respond(403, array("error" => "Je kunt alleen een team aanmelden waar je zelf in zit."));
    }

    $competition = competitions_fetch_tournament($competitionId);
    if (!$competition) {
        competitions_respond(404, array("error" => "Competitie niet gevonden."));
    }

    $columns = boules_table_columns($pdo, "tournament_registrations");
    $tournamentColumn = boules_first_existing_column($columns, array("tournament_id", "competition_id", "id_tournament", "id_competition"));
    $teamColumn = boules_first_existing_column($columns, array("team_id", "id_team"));

    if (!$tournamentColumn || !$teamColumn) {
        competitions_respond(500, array("error" => "Registratietabel mist tournament_id of team_id."));
    }

    $check = $pdo->prepare("SELECT COUNT(*) FROM `tournament_registrations` WHERE `$tournamentColumn` = ? AND `$teamColumn` = ?");
    $check->execute(array($competitionId, $teamId));
    if ((int) $check->fetchColumn() > 0) {
        competitions_respond(409, array("error" => "Dit team is al aangemeld voor deze competitie."));
    }

    $insertColumns = array("`$tournamentColumn`", "`$teamColumn`");
    $values = array($competitionId, $teamId);

    $statusColumn = boules_first_existing_column($columns, array("status", "state"));
    $status = competitions_role_is_admin(isset($_SESSION["role"]) ? $_SESSION["role"] : "") || competitions_user_is_admin($userId)
        ? "accepted"
        : "pending";
    if ($statusColumn && competitions_registration_status_accepts($statusColumn, $status)) {
        $insertColumns[] = "`$statusColumn`";
        $values[] = $status;
    }

    $registeredAtColumn = boules_first_existing_column($columns, array("registered_at", "created_at", "aangemaakt_op"));
    if ($registeredAtColumn) {
        $insertColumns[] = "`$registeredAtColumn`";
        $values[] = date("Y-m-d H:i:s");
    }

    try {
        $statement = $pdo->prepare(
            "INSERT INTO `tournament_registrations` (" . implode(", ", $insertColumns) . ") VALUES (" . implode(", ", array_fill(0, count($values), "?")) . ")"
        );
        $statement->execute($values);
    } catch (PDOException $exception) {
        competitions_respond(500, array("error" => "Team kon niet worden aangemeld: " . $exception->getMessage()));
    }

    $competition = competitions_fetch_tournament($competitionId);
    competitions_respond(200, array(
        "competition" => $competition ? competitions_public_record($competition) : null,
        "registered" => true,
        "teamId" => $teamId,
    ));
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    competitions_respond(405, array("error" => "Alleen POST is toegestaan."));
}

try {
    competitions_ensure_tournaments_schema();
} catch (PDOException $exception) {
    competitions_respond(500, array("error" => "Competitietabel kon niet worden voorbereid: " . $exception->getMessage()));
}

$data = competitions_json_body();
$action = isset($data["action"]) ? (string) $data["action"] : "request";

if ($action === "request" || $action === "create") {
    competitions_create_tournament(isset($data["competition"]) && is_array($data["competition"]) ? $data["competition"] : array(), $action);
}

if ($action === "listPending") {
    competitions_require_admin();
    competitions_list_pending();
}

if ($action === "accept") {
    competitions_require_admin();
    competitions_update_status($data, "accepted");
}

if ($action === "reject") {
    competitions_require_admin();
    competitions_update_status($data, "afgewezen");
}

if ($action === "update") {
    competitions_require_admin();
    competitions_update_tournament($data);
}

if ($action === "delete") {
    competitions_require_admin();
    competitions_delete_tournament($data);
}

if ($action === "registerTeam") {
    competitions_register_team($data);
}

competitions_respond(400, array("error" => "Onbekende competitie-actie."));
