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
        "tone" => "green",
        "status" => isset($row["status"]) ? (string) $row["status"] : "",
        "href" => "#competities",
    );
}

function competitions_require_tournaments()
{
    global $pdo;

    if (!boules_table_exists($pdo, "tournaments")) {
        competitions_respond(500, array("error" => "Tabel tournaments bestaat niet."));
    }
}

function competitions_create_tournament(array $competition, $action)
{
    global $pdo;

    $createdBy = competitions_current_user_id();
    if ($createdBy === "") {
        competitions_respond(401, array("error" => "Log in om een competitieaanvraag te starten."));
    }

    if ($action === "create" && !competitions_is_admin()) {
        competitions_respond(403, array("error" => "Alleen admins kunnen direct een competitie toevoegen."));
    }

    $title = isset($competition["title"]) ? trim((string) $competition["title"]) : "";
    $location = isset($competition["type"]) ? trim((string) $competition["type"]) : "";
    $startDate = isset($competition["startDate"]) ? trim((string) $competition["startDate"]) : "";

    if ($title === "" || $location === "" || $startDate === "") {
        competitions_respond(422, array("error" => "Vul naam, locatie en startdatum in."));
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        competitions_respond(422, array("error" => "Gebruik een geldige startdatum."));
    }

    $status = competitions_status_for_action($action);

    try {
        $statement = $pdo->prepare(
            "INSERT INTO `tournaments` (`name`, `start_date`, `location`, `created_by`, `status`) VALUES (?, ?, ?, ?, ?)"
        );
        $statement->execute(array($title, $startDate, $location, $createdBy, $status));
    } catch (PDOException $exception) {
        competitions_respond(500, array("error" => "Competitie kon niet worden opgeslagen: " . $exception->getMessage()));
    }

    competitions_respond(201, array(
        "competition" => array(
            "id" => (string) $pdo->lastInsertId(),
            "title" => $title,
            "type" => $location,
            "startDate" => $startDate,
            "tone" => "green",
            "status" => $status,
            "href" => "#competities",
        ),
    ));
}

function competitions_list_pending()
{
    global $pdo;

    if (!competitions_is_admin()) {
        competitions_respond(403, array("error" => "Alleen admins kunnen competitieaanvragen beheren."));
    }

    $statement = $pdo->query(
        "SELECT `tournament_id`, `name`, `start_date`, `location`, `status` FROM `tournaments` " .
        "WHERE LOWER(`status`) IN ('pending', 'in afwachting', 'aangevraagd') ORDER BY `created_at` ASC"
    );

    competitions_respond(200, array(
        "competitions" => array_map("competitions_public_record", $statement->fetchAll()),
    ));
}

function competitions_update_status(array $data, $status)
{
    global $pdo;

    if (!competitions_is_admin()) {
        competitions_respond(403, array("error" => "Alleen admins kunnen competitieaanvragen beheren."));
    }

    $competitionId = isset($data["id"]) ? trim((string) $data["id"]) : "";

    if ($competitionId === "" || !is_numeric($competitionId)) {
        competitions_respond(422, array("error" => "Competitie-id ontbreekt."));
    }

    $statement = $pdo->prepare("UPDATE `tournaments` SET `status` = ? WHERE `tournament_id` = ?");
    $statement->execute(array($status, $competitionId));

    if ($statement->rowCount() === 0) {
        competitions_respond(404, array("error" => "Competitieaanvraag niet gevonden."));
    }

    $statement = $pdo->prepare("SELECT `tournament_id`, `name`, `start_date`, `location`, `status` FROM `tournaments` WHERE `tournament_id` = ? LIMIT 1");
    $statement->execute(array($competitionId));
    $competition = $statement->fetch();

    competitions_respond(200, array(
        "competition" => $competition ? competitions_public_record($competition) : null,
    ));
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    competitions_respond(405, array("error" => "Alleen POST is toegestaan."));
}

competitions_require_tournaments();

$data = competitions_json_body();
$action = isset($data["action"]) ? (string) $data["action"] : "request";

if ($action === "request" || $action === "create") {
    competitions_create_tournament(isset($data["competition"]) && is_array($data["competition"]) ? $data["competition"] : array(), $action);
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

competitions_respond(400, array("error" => "Onbekende competitie-actie."));
