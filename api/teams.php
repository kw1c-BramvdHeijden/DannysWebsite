<?php

header("Content-Type: application/json; charset=utf-8");

session_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/bootstrap-data.php";

function teams_respond($statusCode, array $payload)
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function teams_json_body()
{
    $data = json_decode(file_get_contents("php://input"), true);

    return is_array($data) ? $data : array();
}

function teams_table_has_column($tableName, $column)
{
    global $pdo;

    return boules_table_exists($pdo, $tableName) && in_array($column, boules_table_columns($pdo, $tableName), true);
}

function teams_user_columns()
{
    global $pdo;

    if (!boules_table_exists($pdo, "users")) {
        return array(null, null);
    }

    $columns = boules_table_columns($pdo, "users");

    return array(
        boules_first_existing_column($columns, array("user_id", "id")),
        boules_first_existing_column($columns, array("username", "name", "naam", "full_name", "display_name", "email")),
    );
}

function teams_id_column()
{
    $columns = boules_table_columns($GLOBALS["pdo"], "teams");

    return boules_first_existing_column($columns, array("team_id", "id"));
}

function teams_name_column()
{
    $columns = boules_table_columns($GLOBALS["pdo"], "teams");

    return boules_first_existing_column($columns, array("team_name", "name", "team", "naam"));
}

function teams_member_team_column()
{
    $columns = boules_table_columns($GLOBALS["pdo"], "team_members");

    return boules_first_existing_column($columns, array("team_id", "id_team"));
}

function teams_member_user_column()
{
    $columns = boules_table_columns($GLOBALS["pdo"], "team_members");

    return boules_first_existing_column($columns, array("user_id", "id_user"));
}

function teams_ensure_schema()
{
    global $pdo;

    if (!boules_table_exists($pdo, "teams")) {
        teams_respond(500, array("error" => "Tabel teams ontbreekt."));
    }

    if (!teams_id_column() || !teams_name_column()) {
        teams_respond(500, array("error" => "Tabel teams mist team_id of team_name."));
    }

    if (!boules_table_exists($pdo, "team_members")) {
        teams_respond(500, array("error" => "Tabel team_members ontbreekt."));
    }

    if (!teams_member_team_column() || !teams_member_user_column()) {
        teams_respond(500, array("error" => "Tabel team_members mist team_id of user_id."));
    }
}

function teams_current_user_id()
{
    $userId = isset($_SESSION["user_id"]) ? trim((string) $_SESSION["user_id"]) : "";

    return $userId !== "" && is_numeric($userId) ? $userId : "";
}

function teams_user_id_from_request(array $data)
{
    $userId = isset($data["userId"]) ? trim((string) $data["userId"]) : "";

    return $userId !== "" && is_numeric($userId) ? $userId : "";
}

function teams_text_length($value)
{
    return function_exists("mb_strlen")
        ? mb_strlen($value, "UTF-8")
        : strlen($value);
}

function teams_public_user(array $row)
{
    $id = isset($row["id"]) ? (string) $row["id"] : "";
    $name = isset($row["name"]) ? trim((string) $row["name"]) : "";

    return array(
        "id" => $id,
        "name" => $name === "" ? "Account" : $name,
    );
}

function teams_list_users()
{
    global $pdo;

    list($idColumn, $nameColumn) = teams_user_columns();

    if (!$idColumn || !$nameColumn) {
        teams_respond(200, array("users" => array()));
    }

    $statement = $pdo->query("SELECT `$idColumn` AS `id`, `$nameColumn` AS `name` FROM `users` ORDER BY `$nameColumn` ASC");
    $users = array();

    foreach ($statement->fetchAll() as $row) {
        $user = teams_public_user($row);
        if ($user["id"] !== "") {
            $users[] = $user;
        }
    }

    teams_respond(200, array("users" => $users));
}

function teams_members_for_team($teamId)
{
    global $pdo;

    list($userIdColumn, $userNameColumn) = teams_user_columns();
    $teamMemberTeamColumn = teams_member_team_column();
    $teamMemberUserColumn = teams_member_user_column();

    if (!$userIdColumn || !$userNameColumn || !$teamMemberTeamColumn || !$teamMemberUserColumn) {
        return array("ids" => array(), "names" => array());
    }

    $orderParts = array();
    if (teams_table_has_column("team_members", "is_captain")) {
        $orderParts[] = "tm.`is_captain` DESC";
    }
    if (teams_table_has_column("team_members", "joined_at")) {
        $orderParts[] = "tm.`joined_at` ASC";
    }
    $orderParts[] = "tm.`$teamMemberUserColumn` ASC";

    $statement = $pdo->prepare(
        "SELECT tm.`$teamMemberUserColumn` AS `id`, u.`$userNameColumn` AS `name` " .
        "FROM `team_members` tm " .
        "INNER JOIN `users` u ON u.`$userIdColumn` = tm.`$teamMemberUserColumn` " .
        "WHERE tm.`$teamMemberTeamColumn` = ? ORDER BY " . implode(", ", $orderParts)
    );
    $statement->execute(array($teamId));

    $ids = array();
    $names = array();
    foreach ($statement->fetchAll() as $member) {
        $id = isset($member["id"]) ? (string) $member["id"] : "";
        $name = isset($member["name"]) ? trim((string) $member["name"]) : "";

        if ($id !== "") {
            $ids[] = $id;
        }
        if ($name !== "") {
            $names[] = $name;
        }
    }

    return array("ids" => $ids, "names" => $names);
}

function teams_creator_name(array $members)
{
    return isset($members["names"][0]) ? teams_first_name($members["names"][0]) : "";
}

function teams_first_name($name)
{
    $name = trim((string) $name);
    if ($name === "") {
        return "";
    }

    $parts = preg_split('/\s+/', $name);

    return $parts && isset($parts[0]) ? $parts[0] : $name;
}

function teams_public_record(array $row)
{
    $teamId = isset($row["team_id"]) ? (string) $row["team_id"] : "";
    $members = teams_members_for_team($teamId);

    return array(
        "id" => $teamId,
        "name" => isset($row["name"]) ? (string) $row["name"] : "",
        "playerOne" => "",
        "playerTwo" => "",
        "memberIds" => $members["ids"],
        "memberNames" => $members["names"],
        "createdByName" => teams_creator_name($members),
        "createdAt" => isset($row["created_at"]) ? (string) $row["created_at"] : "",
    );
}

function teams_list()
{
    global $pdo;

    $idColumn = teams_id_column();
    $nameColumn = teams_name_column();
    $createdAt = teams_table_has_column("teams", "created_at") ? "`created_at`" : "NOW()";
    $orderColumn = teams_table_has_column("teams", "created_at") ? "created_at" : $idColumn;

    $statement = $pdo->query(
        "SELECT `$idColumn` AS `team_id`, `$nameColumn` AS `name`, $createdAt AS `created_at` " .
        "FROM `teams` ORDER BY `$orderColumn` DESC, `$idColumn` DESC"
    );

    teams_respond(200, array(
        "teams" => array_map("teams_public_record", $statement->fetchAll()),
    ));
}

function teams_list_mine()
{
    global $pdo;

    teams_ensure_schema();

    $userId = teams_current_user_id();
    if ($userId === "") {
        teams_respond(401, array("error" => "Log opnieuw in om je teams te laden."));
    }

    $idColumn = teams_id_column();
    $nameColumn = teams_name_column();
    $teamMemberTeamColumn = teams_member_team_column();
    $teamMemberUserColumn = teams_member_user_column();
    $createdAt = teams_table_has_column("teams", "created_at") ? "t.`created_at`" : "NOW()";
    $orderColumn = teams_table_has_column("teams", "created_at") ? "t.`created_at`" : "t.`$idColumn`";

    $statement = $pdo->prepare(
        "SELECT t.`$idColumn` AS `team_id`, t.`$nameColumn` AS `name`, $createdAt AS `created_at` " .
        "FROM `teams` t " .
        "INNER JOIN `team_members` tm ON tm.`$teamMemberTeamColumn` = t.`$idColumn` " .
        "WHERE tm.`$teamMemberUserColumn` = ? ORDER BY $orderColumn DESC, t.`$idColumn` DESC"
    );
    $statement->execute(array($userId));

    teams_respond(200, array(
        "teams" => array_map("teams_public_record", $statement->fetchAll()),
    ));
}

function teams_save_members($teamId, array $memberIds, $captainId)
{
    global $pdo;

    $hasCaptain = teams_table_has_column("team_members", "is_captain");
    $hasJoinedAt = teams_table_has_column("team_members", "joined_at");
    $teamColumn = teams_member_team_column();
    $userColumn = teams_member_user_column();

    $columns = array("`$teamColumn`", "`$userColumn`");
    if ($hasCaptain) {
        $columns[] = "`is_captain`";
    }
    if ($hasJoinedAt) {
        $columns[] = "`joined_at`";
    }

    $statement = $pdo->prepare(
        "INSERT INTO `team_members` (" . implode(", ", $columns) . ") VALUES (" . implode(", ", array_fill(0, count($columns), "?")) . ")"
    );

    foreach ($memberIds as $index => $memberId) {
        $values = array($teamId, $memberId);
        if ($hasCaptain) {
            $values[] = $captainId !== "" ? ($memberId === $captainId ? 1 : 0) : ($index === 0 ? 1 : 0);
        }
        if ($hasJoinedAt) {
            $values[] = date("Y-m-d H:i:s");
        }

        $statement->execute($values);
    }
}

function teams_create(array $data)
{
    global $pdo;

    $team = isset($data["team"]) && is_array($data["team"]) ? $data["team"] : array();
    $name = isset($team["name"]) ? trim((string) $team["name"]) : "";
    $memberIds = isset($team["memberIds"]) && is_array($team["memberIds"]) ? $team["memberIds"] : array();
    $memberIds = array_values(array_unique(array_filter(array_map(function ($memberId) {
        $memberId = trim((string) $memberId);

        return $memberId !== "" && is_numeric($memberId) ? $memberId : "";
    }, $memberIds))));

    if ($name === "" || count($memberIds) === 0) {
        teams_respond(422, array("error" => "Vul een teamnaam in en selecteer minimaal 1 gebruiker."));
    }

    if (teams_text_length($name) > 35) {
        teams_respond(422, array("error" => "Teamnaam mag maximaal 35 karakters zijn."));
    }

    $requesterId = teams_current_user_id();
    if ($requesterId === "") {
        $requesterId = teams_user_id_from_request($data);
    }
    if ($requesterId !== "" && !in_array($requesterId, $memberIds, true)) {
        array_unshift($memberIds, $requesterId);
    }

    $nameColumn = teams_name_column();
    $columns = array("`$nameColumn`");
    $values = array($name);
    if (teams_table_has_column("teams", "created_at")) {
        $columns[] = "`created_at`";
        $values[] = date("Y-m-d H:i:s");
    }

    try {
        $pdo->beginTransaction();

        $statement = $pdo->prepare(
            "INSERT INTO `teams` (" . implode(", ", $columns) . ") VALUES (" . implode(", ", array_fill(0, count($values), "?")) . ")"
        );
        $statement->execute($values);
        $teamId = $pdo->lastInsertId();
        teams_save_members($teamId, $memberIds, $requesterId);

        $pdo->commit();
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        teams_respond(500, array("error" => "Team kon niet worden opgeslagen: " . $exception->getMessage()));
    }

    $statement = $pdo->prepare(
        "SELECT `" . teams_id_column() . "` AS `team_id`, `" . teams_name_column() . "` AS `name`, " .
        (teams_table_has_column("teams", "created_at") ? "`created_at`" : "NOW()") . " AS `created_at` " .
        "FROM `teams` WHERE `" . teams_id_column() . "` = ? LIMIT 1"
    );
    $statement->execute(array($teamId));
    $createdTeam = $statement->fetch();

    teams_respond(201, array(
        "team" => $createdTeam ? teams_public_record($createdTeam) : null,
    ));
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    teams_respond(405, array("error" => "Alleen POST is toegestaan."));
}

$data = teams_json_body();
$action = isset($data["action"]) ? (string) $data["action"] : "list";

try {
    if ($action !== "listUsers") {
        teams_ensure_schema();
    }

    if ($action === "list") {
        teams_list();
    }

    if ($action === "listUsers") {
        teams_list_users();
    }

    if ($action === "listMine") {
        teams_list_mine();
    }

    if ($action === "create") {
        teams_create($data);
    }
} catch (PDOException $exception) {
    teams_respond(500, array("error" => "Teams konden niet worden geladen: " . $exception->getMessage()));
}

teams_respond(400, array("error" => "Onbekende team-actie."));
