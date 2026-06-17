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
    return isset($_SESSION["role"]) && $_SESSION["role"] === "admin";
}

function competitions_current_user_id()
{
    $userId = isset($_SESSION["user_id"]) ? trim((string) $_SESSION["user_id"]) : "";

    return $userId !== "" && is_numeric($userId) ? $userId : "";
}

function competitions_user_id_from_request(array $data)
{
    $userId = isset($data["userId"]) ? trim((string) $data["userId"]) : "";

    return $userId !== "" && is_numeric($userId) ? $userId : "";
}

function competitions_status_for_action($action)
{
    return $action === "create" ? "geaccepteert" : "pending";
}

function competitions_public_record(array $row)
{
    return array(
        "id" => (string) $row["tournament_id"],
        "title" => (string) $row["name"],
        "type" => isset($row["location"]) && trim((string) $row["location"]) !== "" ? (string) $row["location"] : "Toernooi",
        "startDate" => (string) $row["start_date"],
        "tone" => isset($row["tone"]) && trim((string) $row["tone"]) !== "" ? (string) $row["tone"] : "green",
        "status" => isset($row["status"]) ? (string) $row["status"] : "",
        "requesterName" => isset($row["requester_name"]) ? competitions_first_name($row["requester_name"]) : "",
        "href" => "#competities",
    );
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

    $requiredColumns = array("tournament_id", "name", "start_date", "location", "created_by", "status");
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

function competitions_select_columns()
{
    $columns = array("`tournament_id`", "`name`", "`start_date`", "`location`", "`status`");

    if (competitions_tournament_has_column("tone")) {
        $columns[] = "`tone`";
    }

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
    $tone = isset($competition["tone"]) ? trim((string) $competition["tone"]) : "green";

    if ($title === "" || $location === "" || $startDate === "") {
        competitions_respond(422, array("error" => "Vul naam, locatie en startdatum in."));
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        competitions_respond(422, array("error" => "Gebruik een geldige startdatum."));
    }

    if (!in_array($tone, array("green", "yellow", "red", "olive"), true)) {
        $tone = "green";
    }

    return array(
        "title" => $title,
        "location" => $location,
        "startDate" => $startDate,
        "tone" => $tone,
    );
}

function competitions_create_tournament(array $competition, $action, array $data)
{
    global $pdo;

    $createdBy = competitions_current_user_id();
    if ($createdBy === "") {
        $createdBy = competitions_user_id_from_request($data);
    }
    $createdBy = $createdBy === "" ? null : $createdBy;

    $payload = competitions_validate_payload($competition);
    $status = competitions_status_for_action($action);
    $columns = array("`name`", "`start_date`", "`location`", "`created_by`", "`status`");
    $values = array($payload["title"], $payload["startDate"], $payload["location"], $createdBy, $status);

    if (competitions_tournament_has_column("tone")) {
        $columns[] = "`tone`";
        $values[] = $payload["tone"];
    }

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
    $setParts = array("`name` = ?", "`start_date` = ?", "`location` = ?");
    $values = array($payload["title"], $payload["startDate"], $payload["location"]);

    if (competitions_tournament_has_column("tone")) {
        $setParts[] = "`tone` = ?";
        $values[] = $payload["tone"];
    }

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
    competitions_create_tournament(isset($data["competition"]) && is_array($data["competition"]) ? $data["competition"] : array(), $action, $data);
}

if ($action === "listPending") {
    competitions_list_pending();
}

if ($action === "accept") {
    competitions_update_status($data, "geaccepteert");
}

if ($action === "reject") {
    competitions_update_status($data, "afgewezen");
}

if ($action === "update") {
    competitions_update_tournament($data);
}

if ($action === "delete") {
    competitions_delete_tournament($data);
}

competitions_respond(400, array("error" => "Onbekende competitie-actie."));
