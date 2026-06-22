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

function competitions_is_admin(array $data)
{
    if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin") {
        return true;
    }

    $userId = competitions_user_id_from_request($data);

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

        return strtolower((string) $statement->fetchColumn()) === "admin";
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
    return "pending";
}

function competitions_public_record(array $row)
{
    $tournamentId = (string) $row["tournament_id"];

    return array(
        "id" => $tournamentId,
        "title" => (string) $row["name"],
        "type" => isset($row["location"]) && trim((string) $row["location"]) !== "" ? (string) $row["location"] : "Toernooi",
        "startDate" => (string) $row["start_date"],
        "tone" => isset($row["tone"]) && trim((string) $row["tone"]) !== "" ? (string) $row["tone"] : "green",
        "status" => isset($row["status"]) ? (string) $row["status"] : "",
        "started" => competitions_tournament_has_matches($tournamentId),
        "requesterName" => isset($row["requester_name"]) ? competitions_first_name($row["requester_name"]) : "",
        "href" => "#competities",
    );
}

function competitions_tournament_has_matches($competitionId)
{
    global $pdo;

    if (!boules_table_exists($pdo, "poules") || !boules_table_exists($pdo, "wedstrijden")) {
        return false;
    }

    $pouleColumns = boules_table_columns($pdo, "poules");
    $pouleIdColumn = boules_first_existing_column($pouleColumns, array("poule_id", "id"));
    $pouleTournamentColumn = boules_first_existing_column($pouleColumns, array("tournament_id", "competition_id"));

    $matchColumns = boules_table_columns($pdo, "wedstrijden");
    $matchPouleColumn = boules_first_existing_column($matchColumns, array("poule_id", "id_poule"));

    if (!$pouleIdColumn || !$pouleTournamentColumn || !$matchPouleColumn) {
        return false;
    }

    $statement = $pdo->prepare(
        "SELECT COUNT(*) FROM `wedstrijden` w " .
        "INNER JOIN `poules` p ON p.`$pouleIdColumn` = w.`$matchPouleColumn` " .
        "WHERE p.`$pouleTournamentColumn` = ?"
    );
    $statement->execute(array($competitionId));

    return (int) $statement->fetchColumn() > 0;
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

    $requiredColumns = array("tournament_id", "name", "start_date", "location", "created_by");
    foreach ($requiredColumns as $column) {
        if (!competitions_tournament_has_column($column)) {
            competitions_respond(500, array("error" => "Kolom $column ontbreekt in tournaments."));
        }
    }

    competitions_ensure_status_column();
}

function competitions_tournament_has_column($column)
{
    global $pdo;

    return in_array($column, boules_table_columns($pdo, "tournaments"), true);
}

function competitions_ensure_status_column()
{
    boules_ensure_tournament_status_column($GLOBALS["pdo"]);
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
    $abbreviation = isset($competition["abbreviation"]) ? competitions_clean_abbreviation($competition["abbreviation"]) : "";
    $location = isset($competition["type"]) ? trim((string) $competition["type"]) : "";
    $startDate = isset($competition["startDate"]) ? trim((string) $competition["startDate"]) : "";
    $tone = isset($competition["tone"]) ? trim((string) $competition["tone"]) : "green";

    if ($title === "" || $abbreviation === "" || $location === "" || $startDate === "") {
        competitions_respond(422, array("error" => "Vul naam, afkorting, locatie en startdatum in."));
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        competitions_respond(422, array("error" => "Gebruik een geldige startdatum."));
    }

    if (!in_array($tone, array("green", "yellow", "red", "olive"), true)) {
        $tone = "green";
    }

    return array(
        "title" => competitions_format_tournament_name($title, $abbreviation),
        "abbreviation" => $abbreviation,
        "location" => $location,
        "startDate" => $startDate,
        "tone" => $tone,
    );
}

function competitions_format_tournament_name($title, $abbreviation)
{
    $title = trim(preg_replace('/\s*\([^()]+\)\s*$/', '', (string) $title));
    $abbreviation = competitions_clean_abbreviation($abbreviation);

    return $title . " (" . $abbreviation . ")";
}

function competitions_clean_abbreviation($abbreviation)
{
    return trim(str_replace(array("(", ")"), "", (string) $abbreviation));
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

function competitions_start_tournament(array $data)
{
    if (!competitions_is_admin($data)) {
        competitions_respond(403, array("error" => "Alleen admins mogen een toernooi starten."));
    }

    $competitionId = isset($data["id"]) ? trim((string) $data["id"]) : "";

    if ($competitionId === "" || !is_numeric($competitionId)) {
        competitions_respond(422, array("error" => "Competitie-id ontbreekt."));
    }

    $competition = competitions_fetch_tournament($competitionId);
    if (!$competition) {
        competitions_respond(404, array("error" => "Competitie niet gevonden."));
    }

    $storedMatches = competitions_matches_for_tournament($competitionId);
    if (count($storedMatches) > 0) {
        competitions_respond(200, array(
            "competition" => competitions_public_record($competition),
            "teams" => competitions_teams_from_matches($storedMatches),
            "matches" => $storedMatches,
        ));
    }

    $code = competitions_tournament_team_code($competition);
    $teams = competitions_registered_teams($competitionId, $code, $competition);

    if (count($teams) < 2) {
        competitions_respond(422, array("error" => "Er zijn minimaal 2 teams nodig om wedstrijden te maken."));
    }

    $matches = competitions_store_matches($competitionId, $teams, competitions_round_robin_matches($teams));
    $competition["started"] = true;

    competitions_respond(200, array(
        "competition" => competitions_public_record($competition),
        "teams" => $teams,
        "matches" => $matches,
    ));
}

function competitions_store_matches($competitionId, array $teams, array $matches)
{
    global $pdo;

    competitions_ensure_match_schema();

    $pouleId = competitions_poule_for_tournament($competitionId);
    competitions_sync_poule_teams($pouleId, $teams);

    $existingMatches = competitions_stored_matches($pouleId);
    if (count($existingMatches) > 0) {
        return $existingMatches;
    }

    $pdo->beginTransaction();

    try {
        $matchIds = array();

        foreach ($matches as $match) {
            $statement = $pdo->prepare("INSERT INTO `wedstrijden` (`poule_id`, `datum`, `verified`) VALUES (?, NULL, 0)");
            $statement->execute(array($pouleId));
            $matchId = $pdo->lastInsertId();
            $matchIds[] = $matchId;

            competitions_insert_team_match($matchId, $match["homeTeamId"]);
            competitions_insert_team_match($matchId, $match["awayTeamId"]);
        }

        competitions_reset_new_matches($matchIds);
        $pdo->commit();
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        competitions_respond(500, array("error" => "Wedstrijden konden niet worden opgeslagen: " . $exception->getMessage()));
    }

    return competitions_stored_matches($pouleId);
}

function competitions_reset_new_matches(array $matchIds)
{
    global $pdo;

    if (count($matchIds) === 0) {
        return;
    }

    $placeholders = implode(", ", array_fill(0, count($matchIds), "?"));
    $statement = $pdo->prepare("UPDATE `wedstrijden` SET `datum` = NULL, `verified` = 0 WHERE `wedstrijd_id` IN ($placeholders)");
    $statement->execute($matchIds);
}

function competitions_ensure_match_schema()
{
    global $pdo;

    $required = array("poules", "poule_teams", "wedstrijden", "teamwedstrijd");
    foreach ($required as $table) {
        if (!boules_table_exists($pdo, $table)) {
            competitions_respond(500, array("error" => "Tabel $table ontbreekt voor wedstrijdopslag."));
        }
    }
}

function competitions_poule_for_tournament($competitionId)
{
    global $pdo;

    $pouleId = competitions_existing_poule_for_tournament($competitionId);

    if ($pouleId) {
        return $pouleId;
    }

    $statement = $pdo->prepare("INSERT INTO `poules` (`tournament_id`, `poule_name`) VALUES (?, ?)");
    $statement->execute(array($competitionId, "Algemeen"));

    return $pdo->lastInsertId();
}

function competitions_existing_poule_for_tournament($competitionId)
{
    global $pdo;

    if (!boules_table_exists($pdo, "poules")) {
        return null;
    }

    $statement = $pdo->prepare("SELECT `poule_id` FROM `poules` WHERE `tournament_id` = ? ORDER BY `poule_id` ASC LIMIT 1");
    $statement->execute(array($competitionId));
    $pouleId = $statement->fetchColumn();

    return $pouleId ? $pouleId : null;
}

function competitions_sync_poule_teams($pouleId, array $teams)
{
    global $pdo;

    $check = $pdo->prepare("SELECT COUNT(*) FROM `poule_teams` WHERE `poule_id` = ? AND `team_id` = ?");
    $insert = $pdo->prepare("INSERT INTO `poule_teams` (`poule_id`, `team_id`) VALUES (?, ?)");

    foreach ($teams as $team) {
        $teamId = isset($team["id"]) ? (string) $team["id"] : "";
        if ($teamId === "" || !is_numeric($teamId)) {
            continue;
        }

        $check->execute(array($pouleId, $teamId));
        if ((int) $check->fetchColumn() === 0) {
            $insert->execute(array($pouleId, $teamId));
        }
    }
}

function competitions_insert_team_match($matchId, $teamId)
{
    global $pdo;

    $statement = $pdo->prepare("INSERT INTO `teamwedstrijd` (`wedstrijd_id`, `team_id`, `score`) VALUES (?, ?, 0)");
    $statement->execute(array($matchId, $teamId));
}

function competitions_stored_matches($pouleId)
{
    global $pdo;

    $statement = $pdo->prepare(
        "SELECT w.`wedstrijd_id`, w.`datum`, w.`verified`, tw1.`team_id` AS `home_team_id`, t1.`team_name` AS `home_team_name`, " .
        "tw2.`team_id` AS `away_team_id`, t2.`team_name` AS `away_team_name` " .
        "FROM `wedstrijden` w " .
        "INNER JOIN `teamwedstrijd` tw1 ON tw1.`wedstrijd_id` = w.`wedstrijd_id` " .
        "INNER JOIN `teamwedstrijd` tw2 ON tw2.`wedstrijd_id` = w.`wedstrijd_id` AND tw1.`teamwedstrijd_id` < tw2.`teamwedstrijd_id` " .
        "INNER JOIN `teams` t1 ON t1.`team_id` = tw1.`team_id` " .
        "INNER JOIN `teams` t2 ON t2.`team_id` = tw2.`team_id` " .
        "WHERE w.`poule_id` = ? ORDER BY w.`wedstrijd_id` ASC"
    );
    $statement->execute(array($pouleId));

    $matches = array();
    $round = 1;

    foreach ($statement->fetchAll() as $row) {
        $matches[] = array(
            "id" => (string) $row["wedstrijd_id"],
            "round" => $round,
            "date" => isset($row["datum"]) ? (string) $row["datum"] : "",
            "verified" => isset($row["verified"]) ? (int) $row["verified"] : 0,
            "homeTeamId" => (string) $row["home_team_id"],
            "homeTeamName" => preg_replace('/\s*\([^()]+-\d{4}\)\s*$/', '', (string) $row["home_team_name"]),
            "awayTeamId" => (string) $row["away_team_id"],
            "awayTeamName" => preg_replace('/\s*\([^()]+-\d{4}\)\s*$/', '', (string) $row["away_team_name"]),
        );
        $round++;
    }

    return $matches;
}

function competitions_matches_for_tournament($competitionId)
{
    $pouleId = competitions_existing_poule_for_tournament($competitionId);

    return $pouleId ? competitions_stored_matches($pouleId) : array();
}

function competitions_teams_from_matches(array $matches)
{
    $teams = array();
    $seen = array();

    foreach ($matches as $match) {
        $pairs = array(
            array("id" => $match["homeTeamId"], "name" => $match["homeTeamName"]),
            array("id" => $match["awayTeamId"], "name" => $match["awayTeamName"]),
        );

        foreach ($pairs as $team) {
            if ($team["id"] === "" || isset($seen[$team["id"]])) {
                continue;
            }

            $seen[$team["id"]] = true;
            $teams[] = $team;
        }
    }

    return $teams;
}

function competitions_matches_tournament(array $data)
{
    $competitionId = isset($data["id"]) ? trim((string) $data["id"]) : "";

    if ($competitionId === "" || !is_numeric($competitionId)) {
        competitions_respond(422, array("error" => "Competitie-id ontbreekt."));
    }

    $competition = competitions_fetch_tournament($competitionId);
    if (!$competition) {
        competitions_respond(404, array("error" => "Competitie niet gevonden."));
    }

    $matches = competitions_matches_for_tournament($competitionId);

    competitions_respond(200, array(
        "competition" => competitions_public_record($competition),
        "teams" => competitions_teams_from_matches($matches),
        "matches" => $matches,
    ));
}

function competitions_update_match_verification(array $data)
{
    global $pdo;

    if (!competitions_is_admin($data)) {
        competitions_respond(403, array("error" => "Alleen admins mogen wedstrijddatums controleren."));
    }

    $competitionId = isset($data["competitionId"]) ? trim((string) $data["competitionId"]) : "";
    $matchId = isset($data["matchId"]) ? trim((string) $data["matchId"]) : "";
    $verified = isset($data["verified"]) && (int) $data["verified"] === 1 ? 1 : 0;

    if ($competitionId === "" || !is_numeric($competitionId) || $matchId === "" || !is_numeric($matchId)) {
        competitions_respond(422, array("error" => "Competitie-id of wedstrijd-id ontbreekt."));
    }

    $competition = competitions_fetch_tournament($competitionId);
    if (!$competition) {
        competitions_respond(404, array("error" => "Competitie niet gevonden."));
    }

    if (!competitions_match_belongs_to_tournament($matchId, $competitionId)) {
        competitions_respond(404, array("error" => "Wedstrijd niet gevonden voor deze competitie."));
    }

    if ($verified === 1) {
        $dateStatement = $pdo->prepare("SELECT `datum` FROM `wedstrijden` WHERE `wedstrijd_id` = ? LIMIT 1");
        $dateStatement->execute(array($matchId));
        $date = trim((string) $dateStatement->fetchColumn());

        if ($date === "" || strpos($date, "0000-00-00") === 0) {
            competitions_respond(422, array("error" => "Plan eerst een datum in voordat je goedkeurt."));
        }

        $statement = $pdo->prepare("UPDATE `wedstrijden` SET `verified` = 1 WHERE `wedstrijd_id` = ?");
        $statement->execute(array($matchId));
    } else {
        $statement = $pdo->prepare("UPDATE `wedstrijden` SET `verified` = 0, `datum` = NULL WHERE `wedstrijd_id` = ?");
        $statement->execute(array($matchId));
    }

    $matches = competitions_matches_for_tournament($competitionId);

    competitions_respond(200, array(
        "competition" => competitions_public_record($competition),
        "teams" => competitions_teams_from_matches($matches),
        "matches" => $matches,
    ));
}

function competitions_match_belongs_to_tournament($matchId, $competitionId)
{
    global $pdo;

    $statement = $pdo->prepare(
        "SELECT COUNT(*) FROM `wedstrijden` w " .
        "INNER JOIN `poules` p ON p.`poule_id` = w.`poule_id` " .
        "WHERE w.`wedstrijd_id` = ? AND p.`tournament_id` = ?"
    );
    $statement->execute(array($matchId, $competitionId));

    return (int) $statement->fetchColumn() > 0;
}

function competitions_tournament_team_code(array $competition)
{
    $name = isset($competition["name"]) ? (string) $competition["name"] : "";
    $startDate = isset($competition["start_date"]) ? (string) $competition["start_date"] : "";
    $year = substr($startDate, 0, 4);
    $abbreviation = "";

    if (preg_match('/\(([^()]+)\)\s*$/', $name, $matches)) {
        $parts = explode("-", trim($matches[1]));
        $abbreviation = trim($parts[0]);
    }

    if ($abbreviation === "" || !preg_match('/^\d{4}$/', $year)) {
        competitions_respond(422, array("error" => "De competitie mist een afkorting of geldig jaartal."));
    }

    return $abbreviation . "-" . $year;
}

function competitions_registered_teams($competitionId, $code, array $competition)
{
    $teams = competitions_registered_teams_from_table($competitionId, $code, $competition);
    $suffixTeams = competitions_registered_teams_from_name_suffix($code);

    return competitions_unique_teams(array_merge($teams, $suffixTeams));
}

function competitions_registered_teams_from_table($competitionId, $code, array $competition)
{
    global $pdo;

    if (!boules_table_exists($pdo, "tournament_registrations")) {
        return array();
    }

    $registrationColumns = boules_table_columns($pdo, "tournament_registrations");
    $tournamentColumn = boules_first_existing_column($registrationColumns, array(
        "tournament_id", "tournamentId", "competition_id", "competitionId", "id_tournament", "id_competition",
        "tournament", "competition", "tournament_name", "competition_name", "toernooi", "competitie"
    ));
    $registrationTeamColumn = boules_first_existing_column($registrationColumns, array("team_id", "teamId", "id_team", "idTeam"));
    $registrationTeamNameColumn = boules_first_existing_column($registrationColumns, array("team_name", "teamName", "name", "naam", "team"));
    $registrationStatusColumn = boules_first_existing_column($registrationColumns, array("status", "state"));

    if (!$tournamentColumn) {
        return array();
    }

    if ($registrationTeamColumn && boules_table_exists($pdo, "teams")) {
        $teamColumns = boules_table_columns($pdo, "teams");
        $teamIdColumn = boules_first_existing_column($teamColumns, array("team_id", "teamId", "id_team", "id"));
        $teamNameColumn = boules_first_existing_column($teamColumns, array("team_name", "teamName", "name", "naam", "team"));

        if ($teamIdColumn && $teamNameColumn) {
            $sql = "SELECT t.`$teamIdColumn` AS `id`, t.`$teamNameColumn` AS `name` " .
                "FROM `tournament_registrations` tr INNER JOIN `teams` t ON t.`$teamIdColumn` = tr.`$registrationTeamColumn` " .
                "WHERE " . competitions_registration_tournament_where($tournamentColumn);

            if ($registrationStatusColumn) {
                $sql .= " AND " . competitions_registration_status_where($registrationStatusColumn);
            }

            $sql .= " ORDER BY t.`$teamNameColumn` ASC";
            $statement = $pdo->prepare($sql);
            $statement->execute(competitions_registration_tournament_values($competitionId, $code, $competition));
            $teams = $statement->fetchAll();

            if (count($teams) > 0) {
                return $teams;
            }
        }
    }

    if (!$registrationTeamNameColumn) {
        return array();
    }

    $idColumn = boules_first_existing_column($registrationColumns, array("registration_id", "id", "tournament_registration_id"));
    $idExpression = $idColumn ? "tr.`$idColumn`" : "tr.`$registrationTeamNameColumn`";
    $sql = "SELECT $idExpression AS `id`, tr.`$registrationTeamNameColumn` AS `name` " .
        "FROM `tournament_registrations` tr WHERE " . competitions_registration_tournament_where($tournamentColumn);

    if ($registrationStatusColumn) {
        $sql .= " AND " . competitions_registration_status_where($registrationStatusColumn);
    }

    $sql .= " ORDER BY tr.`$registrationTeamNameColumn` ASC";
    $statement = $pdo->prepare($sql);
    $statement->execute(competitions_registration_tournament_values($competitionId, $code, $competition));

    return $statement->fetchAll();
}

function competitions_registration_tournament_where($tournamentColumn)
{
    return "(tr.`$tournamentColumn` = ? OR tr.`$tournamentColumn` = ? OR tr.`$tournamentColumn` = ? OR tr.`$tournamentColumn` LIKE ?)";
}

function competitions_registration_tournament_values($competitionId, $code, array $competition)
{
    $name = isset($competition["name"]) ? (string) $competition["name"] : "";

    return array($competitionId, $code, $name, "%(" . $code . ")%");
}

function competitions_registration_status_where($registrationStatusColumn)
{
    return "(tr.`$registrationStatusColumn` IS NULL OR LOWER(tr.`$registrationStatusColumn`) NOT IN ('rejected', 'denied', 'afgewezen', 'cancelled', 'geannuleerd'))";
}

function competitions_registered_teams_from_name_suffix($code)
{
    global $pdo;

    if (!boules_table_exists($pdo, "teams")) {
        return array();
    }

    $teamColumns = boules_table_columns($pdo, "teams");
    $teamIdColumn = boules_first_existing_column($teamColumns, array("team_id", "id"));
    $teamNameColumn = boules_first_existing_column($teamColumns, array("team_name", "name", "team"));

    if (!$teamIdColumn || !$teamNameColumn) {
        return array();
    }

    $statement = $pdo->prepare("SELECT `$teamIdColumn` AS `id`, `$teamNameColumn` AS `name` FROM `teams` WHERE `$teamNameColumn` LIKE ? ORDER BY `$teamNameColumn` ASC");
    $statement->execute(array("%(" . $code . ")"));

    return competitions_unique_teams($statement->fetchAll());
}

function competitions_unique_teams(array $rows)
{
    $teams = array();
    $seen = array();

    foreach ($rows as $row) {
        $id = isset($row["id"]) ? (string) $row["id"] : "";
        $name = isset($row["name"]) ? trim((string) $row["name"]) : "";

        if ($id === "" || $name === "" || isset($seen[$id])) {
            continue;
        }

        $seen[$id] = true;
        $teams[] = array(
            "id" => $id,
            "name" => preg_replace('/\s*\([^()]+-\d{4}\)\s*$/', '', $name),
        );
    }

    return $teams;
}

function competitions_round_robin_matches(array $teams)
{
    $pool = array_values($teams);
    if (count($pool) % 2 === 1) {
        $pool[] = null;
    }

    $teamCount = count($pool);
    $roundCount = $teamCount - 1;
    $matches = array();

    for ($round = 1; $round <= $roundCount; $round++) {
        for ($index = 0; $index < $teamCount / 2; $index++) {
            $home = $pool[$index];
            $away = $pool[$teamCount - 1 - $index];

            if ($home !== null && $away !== null) {
                $matches[] = array(
                    "round" => $round,
                    "homeTeamId" => $home["id"],
                    "homeTeamName" => $home["name"],
                    "awayTeamId" => $away["id"],
                    "awayTeamName" => $away["name"],
                );
            }
        }

        $fixed = array_shift($pool);
        $last = array_pop($pool);
        array_unshift($pool, $fixed);
        array_splice($pool, 1, 0, array($last));
    }

    return $matches;
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

if ($action === "start") {
    competitions_start_tournament($data);
}

if ($action === "matches") {
    competitions_matches_tournament($data);
}

if ($action === "verifyMatch") {
    competitions_update_match_verification($data);
}

competitions_respond(400, array("error" => "Onbekende competitie-actie."));
