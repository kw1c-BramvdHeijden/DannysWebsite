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

function teams_user_id_from_session()
{
    $userId = isset($_SESSION["user_id"]) ? trim((string) $_SESSION["user_id"]) : "";

    return $userId !== "" && is_numeric($userId) ? $userId : "";
}

function teams_user_id_from_request(array $data)
{
    $userId = isset($data["userId"]) ? trim((string) $data["userId"]) : "";

    return $userId !== "" && is_numeric($userId) ? $userId : "";
}

function teams_is_admin(array $data)
{
    if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin") {
        return true;
    }

    $userId = teams_user_id_from_request($data);

    return $userId !== "" && teams_user_is_admin($userId);
}

function teams_user_is_admin($userId)
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
        $role = $statement->fetchColumn();

        return strtolower((string) $role) === "admin";
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
        $roleValue = strtolower((string) $role[$roleName]);
        if ($roleValue === "admin" || $roleValue === "administrator" || $roleValue === "beheerder") {
            return true;
        }
    }

    return false;
}

function teams_has_column($column)
{
    global $pdo;

    return in_array($column, boules_table_columns($pdo, "teams"), true);
}

function teams_table_has_column($tableName, $column)
{
    global $pdo;

    return boules_table_exists($pdo, $tableName) && in_array($column, boules_table_columns($pdo, $tableName), true);
}

function teams_name_column()
{
    $columns = boules_table_columns($GLOBALS["pdo"], "teams");

    return boules_first_existing_column($columns, array("team_name", "name", "team"));
}

function teams_ensure_schema()
{
    global $pdo;

    if (!boules_table_exists($pdo, "teams")) {
        teams_respond(500, array("error" => "Tabel teams ontbreekt. Gebruik de bestaande database-tabellen."));
    }

    if (!teams_name_column()) {
        teams_respond(500, array("error" => "Kolom team_name ontbreekt in teams."));
    }

    if (!boules_table_exists($pdo, "team_members")) {
        teams_respond(500, array("error" => "Tabel team_members ontbreekt. Gebruik de bestaande database-tabellen."));
    }

    $requiredTeamColumns = array("team_id", "team_name");
    foreach ($requiredTeamColumns as $column) {
        if (!teams_table_has_column("teams", $column)) {
            teams_respond(500, array("error" => "Kolom $column ontbreekt in teams."));
        }
    }

    $requiredMemberColumns = array("team_id", "user_id", "is_captain");
    foreach ($requiredMemberColumns as $column) {
        if (!teams_table_has_column("team_members", $column)) {
            teams_respond(500, array("error" => "Kolom $column ontbreekt in team_members."));
        }
    }
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

function teams_creator_name_expression()
{
    if (!boules_table_exists($GLOBALS["pdo"], "users")) {
        return "'' AS `creator_name`";
    }

    list($userIdColumn, $userNameColumn) = teams_user_columns();
    $teamIdColumn = teams_id_column();

    if (!$userIdColumn || !$userNameColumn || !$teamIdColumn) {
        return "'' AS `creator_name`";
    }

    $memberOrder = teams_table_has_column("team_members", "is_captain")
        ? "tm.`is_captain` DESC, tm.`user_id` ASC"
        : "tm.`user_id` ASC";
    $memberExpression = boules_table_exists($GLOBALS["pdo"], "team_members")
        ? "(SELECT u.`$userNameColumn` FROM `team_members` tm INNER JOIN `users` u ON u.`$userIdColumn` = tm.`user_id` WHERE tm.`team_id` = `teams`.`$teamIdColumn` ORDER BY $memberOrder LIMIT 1)"
        : "NULL";

    return "COALESCE($memberExpression, '') AS `creator_name`";
}

function teams_user_columns()
{
    global $pdo;

    if (!boules_table_exists($pdo, "users")) {
        return array(null, null);
    }

    $userColumns = boules_table_columns($pdo, "users");

    return array(
        boules_first_existing_column($userColumns, array("user_id", "id")),
        boules_first_existing_column($userColumns, array("name", "naam", "full_name", "display_name", "username", "gebruikersnaam", "user_name", "email", "voornaam", "first_name", "firstname")),
    );
}

function teams_id_column()
{
    $columns = boules_table_columns($GLOBALS["pdo"], "teams");

    return boules_first_existing_column($columns, array("team_id", "id"));
}

function teams_select_columns()
{
    $id = teams_id_column();
    $name = teams_name_column();
    $createdAt = teams_has_column("created_at") ? "`created_at`" : "NOW()";
    $playerOne = teams_has_column("player_one") ? "`player_one`" : "''";
    $playerTwo = teams_has_column("player_two") ? "`player_two`" : "''";
    $memberIds = teams_has_column("member_ids") ? "`member_ids`" : "''";

    return "`$id` AS `team_id`, `$name` AS `name`, $playerOne AS `player_one`, $playerTwo AS `player_two`, $memberIds AS `member_ids`, $createdAt AS `created_at`";
}

function teams_public_record(array $row)
{
    $teamId = isset($row["team_id"]) ? (string) $row["team_id"] : "";
    $memberIds = teams_member_ids_for_team($teamId);
    if (count($memberIds) === 0 && isset($row["member_ids"]) && trim((string) $row["member_ids"]) !== "") {
        $memberIds = explode(",", (string) $row["member_ids"]);
    }
    $memberNames = count($memberIds) > 0
        ? teams_names_for_user_ids($memberIds)
        : array_filter(array(
            isset($row["player_one"]) ? (string) $row["player_one"] : "",
            isset($row["player_two"]) ? (string) $row["player_two"] : "",
        ));

    return array(
        "id" => (string) $row["team_id"],
        "name" => (string) $row["name"],
        "playerOne" => isset($row["player_one"]) ? (string) $row["player_one"] : "",
        "playerTwo" => isset($row["player_two"]) ? (string) $row["player_two"] : "",
        "memberIds" => $memberIds,
        "memberNames" => array_values($memberNames),
        "createdByName" => isset($row["creator_name"]) ? teams_first_name($row["creator_name"]) : "",
        "createdAt" => isset($row["created_at"]) ? (string) $row["created_at"] : "",
    );
}

function teams_public_user(array $row, $idColumn, $nameColumn)
{
    $name = isset($row[$nameColumn]) ? trim((string) $row[$nameColumn]) : "";

    return array(
        "id" => isset($row[$idColumn]) ? (string) $row[$idColumn] : "",
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

    foreach ($statement->fetchAll() as $user) {
        $publicUser = teams_public_user($user, "id", "name");
        if ($publicUser["id"] !== "") {
            $users[] = $publicUser;
        }
    }

    teams_respond(200, array("users" => $users));
}

function teams_list()
{
    global $pdo;
    $id = teams_id_column();
    $orderColumn = teams_has_column("created_at") ? "created_at" : $id;

    $statement = $pdo->query(
        "SELECT " . teams_select_columns() . ", " .
        teams_creator_name_expression() .
        " FROM `teams` ORDER BY `$orderColumn` DESC, `$id` DESC"
    );

    teams_respond(200, array(
        "teams" => array_map("teams_public_record", $statement->fetchAll()),
    ));
}

function teams_create(array $data)
{
    global $pdo;

    $team = isset($data["team"]) && is_array($data["team"]) ? $data["team"] : array();
    $name = isset($team["name"]) ? trim((string) $team["name"]) : "";
    $tournamentId = isset($team["tournamentId"]) ? trim((string) $team["tournamentId"]) : "";
    $memberIds = isset($team["memberIds"]) && is_array($team["memberIds"]) ? $team["memberIds"] : array();
    $memberIds = array_values(array_unique(array_filter(array_map(function ($memberId) {
        $memberId = trim((string) $memberId);

        return $memberId !== "" && is_numeric($memberId) ? $memberId : "";
    }, $memberIds))));

    if ($name === "" || $tournamentId === "" || count($memberIds) === 0) {
        teams_respond(422, array("error" => "Vul een teamnaam in, kies een competitie en selecteer minimaal 1 gebruiker."));
    }

    if (teams_text_length($name) > 35) {
        teams_respond(422, array("error" => "Teamnaam mag maximaal 35 karakters zijn."));
    }

    $name = teams_format_name_for_tournament($name, $tournamentId);

    $requesterId = teams_user_id_from_session();
    if ($requesterId === "") {
        $requesterId = teams_user_id_from_request($data);
    }

    if ($requesterId !== "" && !in_array($requesterId, $memberIds, true)) {
        array_unshift($memberIds, $requesterId);
    }

    $memberNames = teams_names_for_user_ids($memberIds);
    $playerOne = isset($memberNames[0]) ? $memberNames[0] : "";
    $playerTwo = isset($memberNames[1]) ? $memberNames[1] : "";

    try {
        $nameColumn = teams_name_column();
        if (!$nameColumn) {
            teams_respond(500, array("error" => "Kolom team_name ontbreekt in teams."));
        }

        $columns = array("`$nameColumn`");
        $values = array($name);

        if (teams_has_column("player_one")) {
            $columns[] = "`player_one`";
            $values[] = $playerOne;
        }
        if (teams_has_column("player_two")) {
            $columns[] = "`player_two`";
            $values[] = $playerTwo;
        }
        if (teams_has_column("member_ids")) {
            $columns[] = "`member_ids`";
            $values[] = implode(",", $memberIds);
        }

        $pdo->beginTransaction();

        $statement = $pdo->prepare(
            "INSERT INTO `teams` (" . implode(", ", $columns) . ") VALUES (" . implode(", ", array_fill(0, count($values), "?")) . ")"
        );
        $statement->execute($values);
        $teamId = $pdo->lastInsertId();
        teams_save_members($teamId, $memberIds, $requesterId);
        teams_save_tournament_registration($tournamentId, $teamId);
        $pdo->commit();
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        teams_respond(500, array("error" => "Team kon niet worden aangemeld: " . $exception->getMessage()));
    }

    $statement = $pdo->prepare(
        "SELECT " . teams_select_columns() . ", " .
        teams_creator_name_expression() .
        " FROM `teams` WHERE `" . teams_id_column() . "` = ? LIMIT 1"
    );
    $statement->execute(array($teamId));
    $createdTeam = $statement->fetch();

    teams_respond(201, array(
        "team" => $createdTeam ? teams_public_record($createdTeam) : null,
    ));
}

function teams_delete(array $data)
{
    global $pdo;

    if (!teams_is_admin($data)) {
        teams_respond(403, array("error" => "Alleen admins mogen teams verwijderen."));
    }

    $teamId = isset($data["id"]) ? trim((string) $data["id"]) : "";

    if ($teamId === "" || !is_numeric($teamId)) {
        teams_respond(422, array("error" => "Team-id ontbreekt."));
    }

    $teamIdColumn = teams_id_column();
    if (!$teamIdColumn) {
        teams_respond(500, array("error" => "Kolom team_id ontbreekt in teams."));
    }

    try {
        $pdo->beginTransaction();

        if (boules_table_exists($pdo, "team_members")) {
            $membersStatement = $pdo->prepare("DELETE FROM `team_members` WHERE `team_id` = ?");
            $membersStatement->execute(array($teamId));
        }

        $teamStatement = $pdo->prepare("DELETE FROM `teams` WHERE `$teamIdColumn` = ?");
        $teamStatement->execute(array($teamId));

        if ($teamStatement->rowCount() === 0) {
            $pdo->rollBack();
            teams_respond(404, array("error" => "Team niet gevonden."));
        }

        $pdo->commit();
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        teams_respond(500, array("error" => "Team kon niet worden verwijderd: " . $exception->getMessage()));
    }

    teams_respond(200, array("deleted" => true, "id" => $teamId));
}

function teams_text_length($value)
{
    return function_exists("mb_strlen")
        ? mb_strlen($value, "UTF-8")
        : strlen($value);
}

function teams_save_tournament_registration($tournamentId, $teamId)
{
    global $pdo;

    if (!$teamId || $tournamentId === "" || !boules_table_exists($pdo, "tournament_registrations")) {
        return;
    }

    $columns = boules_table_columns($pdo, "tournament_registrations");
    $tournamentColumn = boules_first_existing_column($columns, array("tournament_id", "competition_id", "id_tournament", "id_competition"));
    $teamColumn = boules_first_existing_column($columns, array("team_id", "id_team"));

    if (!$tournamentColumn || !$teamColumn) {
        return;
    }

    $check = $pdo->prepare("SELECT COUNT(*) FROM `tournament_registrations` WHERE `$tournamentColumn` = ? AND `$teamColumn` = ?");
    $check->execute(array($tournamentId, $teamId));

    if ((int) $check->fetchColumn() > 0) {
        return;
    }

    $insertColumns = array("`$tournamentColumn`", "`$teamColumn`");
    $values = array($tournamentId, $teamId);

    $statusColumn = boules_first_existing_column($columns, array("status", "state"));
    if ($statusColumn && teams_registration_status_accepts($statusColumn, "accepted")) {
        $insertColumns[] = "`$statusColumn`";
        $values[] = "accepted";
    }

    $registeredAtColumn = boules_first_existing_column($columns, array("registered_at", "created_at", "aangemaakt_op"));
    if ($registeredAtColumn) {
        $insertColumns[] = "`$registeredAtColumn`";
        $values[] = date("Y-m-d H:i:s");
    }

    $statement = $pdo->prepare(
        "INSERT INTO `tournament_registrations` (" . implode(", ", $insertColumns) . ") VALUES (" . implode(", ", array_fill(0, count($values), "?")) . ")"
    );
    $statement->execute($values);
}

function teams_registration_status_accepts($statusColumn, $status)
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

function teams_format_name_for_tournament($name, $tournamentId)
{
    $code = teams_tournament_code($tournamentId);
    $name = trim(preg_replace('/\s*\([^()]+-\d{4}\)\s*$/', '', (string) $name));

    return $name . " (" . $code . ")";
}

function teams_tournament_code($tournamentId)
{
    global $pdo;

    if ($tournamentId === "" || !is_numeric($tournamentId) || !boules_table_exists($pdo, "tournaments")) {
        teams_respond(422, array("error" => "Kies een geldige competitie."));
    }

    $columns = boules_table_columns($pdo, "tournaments");
    $idColumn = boules_first_existing_column($columns, array("tournament_id", "id"));
    $nameColumn = boules_first_existing_column($columns, array("name", "title", "naam", "tournament_name"));
    $startDateColumn = boules_first_existing_column($columns, array("start_date", "startDate", "startdatum", "date", "datum"));

    if (!$idColumn || !$nameColumn || !$startDateColumn) {
        teams_respond(500, array("error" => "Competitiegegevens missen id, naam of startdatum."));
    }

    $statement = $pdo->prepare("SELECT `$nameColumn` AS `name`, `$startDateColumn` AS `start_date` FROM `tournaments` WHERE `$idColumn` = ? LIMIT 1");
    $statement->execute(array($tournamentId));
    $tournament = $statement->fetch();

    if (!$tournament) {
        teams_respond(422, array("error" => "Kies een geldige competitie."));
    }

    $abbreviation = teams_tournament_abbreviation((string) $tournament["name"]);
    $year = substr((string) $tournament["start_date"], 0, 4);

    if ($abbreviation === "" || !preg_match('/^\d{4}$/', $year)) {
        teams_respond(422, array("error" => "De gekozen competitie mist een afkorting of geldig jaartal."));
    }

    return $abbreviation . "-" . $year;
}

function teams_tournament_abbreviation($name)
{
    $name = trim((string) $name);

    if (preg_match('/\(([^()]+)\)\s*$/', $name, $matches)) {
        $value = trim($matches[1]);
        $parts = explode("-", $value);

        return trim($parts[0]);
    }

    return "";
}

function teams_member_ids_for_team($teamId)
{
    global $pdo;

    if ($teamId === "" || !boules_table_exists($pdo, "team_members")) {
        return array();
    }

    $orderParts = array();
    if (teams_table_has_column("team_members", "is_captain")) {
        $orderParts[] = "`is_captain` DESC";
    }
    if (teams_table_has_column("team_members", "joined_at")) {
        $orderParts[] = "`joined_at` ASC";
    }
    $orderParts[] = "`user_id` ASC";

    $statement = $pdo->prepare("SELECT `user_id` FROM `team_members` WHERE `team_id` = ? ORDER BY " . implode(", ", $orderParts));
    $statement->execute(array($teamId));

    $memberIds = array();
    foreach ($statement->fetchAll() as $member) {
        $memberIds[] = (string) $member["user_id"];
    }

    return $memberIds;
}

function teams_save_members($teamId, array $memberIds, $requesterId)
{
    global $pdo;

    if (!$teamId || !boules_table_exists($pdo, "team_members")) {
        return;
    }

    $statement = $pdo->prepare("INSERT INTO `team_members` (`team_id`, `user_id`, `is_captain`) VALUES (?, ?, ?)");
    foreach ($memberIds as $index => $memberId) {
        $isCaptain = $requesterId !== "" ? $memberId === $requesterId : $index === 0;
        $statement->execute(array($teamId, $memberId, $isCaptain ? 1 : 0));
    }
}

function teams_names_for_user_ids(array $memberIds)
{
    global $pdo;

    list($idColumn, $nameColumn) = teams_user_columns();

    if (!$idColumn || !$nameColumn || count($memberIds) === 0) {
        return array();
    }

    $placeholders = implode(", ", array_fill(0, count($memberIds), "?"));
    $statement = $pdo->prepare("SELECT `$idColumn`, `$nameColumn` FROM `users` WHERE `$idColumn` IN ($placeholders)");
    $statement->execute($memberIds);

    $namesById = array();
    foreach ($statement->fetchAll() as $user) {
        $namesById[(string) $user[$idColumn]] = trim((string) $user[$nameColumn]);
    }

    $names = array();
    foreach ($memberIds as $memberId) {
        if (isset($namesById[$memberId]) && $namesById[$memberId] !== "") {
            $names[] = $namesById[$memberId];
        }
    }

    return $names;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    teams_respond(405, array("error" => "Alleen POST is toegestaan."));
}

try {
    $data = teams_json_body();
    $action = isset($data["action"]) ? (string) $data["action"] : "list";

    if ($action !== "listUsers") {
        teams_ensure_schema();
    }
} catch (PDOException $exception) {
    teams_respond(500, array("error" => "Teamtabel kon niet worden voorbereid: " . $exception->getMessage()));
}

if ($action === "list") {
    teams_list();
}

if ($action === "listUsers") {
    teams_list_users();
}

if ($action === "create") {
    teams_create($data);
}

if ($action === "delete") {
    teams_delete($data);
}

teams_respond(400, array("error" => "Onbekende team-actie."));
