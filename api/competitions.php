<?php

header("Content-Type: application/json; charset=utf-8");

session_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/bootstrap-data.php";

// Stuur JSON terug en stop de API.
function competitions_respond($statusCode, array $payload)
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Lees JSON-body uit het request.
function competitions_json_body()
{
    $data = json_decode(file_get_contents("php://input"), true);

    return is_array($data) ? $data : array();
}

function competitions_reschedule_file()
{
    return __DIR__ . "/../data/reschedule_requests.json";
}

function competitions_read_reschedule_requests()
{
    $file = competitions_reschedule_file();
    if (!is_file($file)) {
        return array();
    }

    $data = json_decode((string) file_get_contents($file), true);

    return is_array($data) ? $data : array();
}

function competitions_write_reschedule_requests(array $requests)
{
    $file = competitions_reschedule_file();
    $directory = dirname($file);
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    file_put_contents($file, json_encode($requests, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
}

// Controleer of de sessiegebruiker admin is.
function competitions_is_admin()
{
    if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin") {
        return true;
    }

    $userId = competitions_current_user_id();

    return $userId !== "" && competitions_user_is_admin($userId);
}

// Haal adminrol uit de database.
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

// Herken adminrollen uit verschillende tabellen.
function competitions_role_is_admin($role)
{
    $roleValue = strtolower(trim((string) $role));

    return $roleValue === "admin" || $roleValue === "administrator" || $roleValue === "beheerder";
}

// Haal de ingelogde gebruiker uit de sessie.
function competitions_current_user_id()
{
    $userId = isset($_SESSION["user_id"]) ? trim((string) $_SESSION["user_id"]) : "";

    return $userId !== "" && is_numeric($userId) ? $userId : "";
}

// Blokkeer acties die alleen admins mogen doen.
function competitions_require_admin()
{
    if (!competitions_is_admin()) {
        competitions_respond(403, array("error" => "Alleen admins mogen deze actie uitvoeren."));
    }
}

// Speleraanvragen zijn pending, admin-aanmaak is accepted.
function competitions_status_for_action($action)
{
    return $action === "create" ? "accepted" : "pending";
}

// Maak database-record geschikt voor de frontend.
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
        "registeredTeams" => competitions_registered_teams($tournamentId),
        "registeredTeamIds" => competitions_registered_team_ids($tournamentId),
        "matchesGenerated" => competitions_matches_generated($tournamentId),
        "href" => "#competities",
    );
}

// Haal aangemelde teams met naam op.
function competitions_registered_teams($tournamentId)
{
    global $pdo;

    if ($tournamentId === "" || !boules_table_exists($pdo, "tournament_registrations") || !boules_table_exists($pdo, "teams")) {
        return array();
    }

    $registrationColumns = boules_table_columns($pdo, "tournament_registrations");
    $teamColumns = boules_table_columns($pdo, "teams");
    $tournamentColumn = boules_first_existing_column($registrationColumns, array("tournament_id", "competition_id", "id_tournament", "id_competition"));
    $registrationTeamColumn = boules_first_existing_column($registrationColumns, array("team_id", "id_team"));
    $teamIdColumn = boules_first_existing_column($teamColumns, array("team_id", "id"));
    $teamNameColumn = boules_first_existing_column($teamColumns, array("team_name", "name", "team", "naam"));

    if (!$tournamentColumn || !$registrationTeamColumn || !$teamIdColumn || !$teamNameColumn) {
        return array();
    }

    $statement = $pdo->prepare(
        "SELECT tr.`$registrationTeamColumn` AS `id`, t.`$teamNameColumn` AS `name` " .
        "FROM `tournament_registrations` tr " .
        "INNER JOIN `teams` t ON t.`$teamIdColumn` = tr.`$registrationTeamColumn` " .
        "WHERE tr.`$tournamentColumn` = ? ORDER BY t.`$teamNameColumn` ASC"
    );
    $statement->execute(array($tournamentId));

    $teams = array();
    foreach ($statement->fetchAll() as $row) {
        if (isset($row["id"])) {
            $teams[] = array(
                "id" => (string) $row["id"],
                "name" => isset($row["name"]) && trim((string) $row["name"]) !== "" ? (string) $row["name"] : "Team " . (string) $row["id"],
            );
        }
    }

    return $teams;
}

// Alleen de ids zijn nodig voor aanmeldlogica.
function competitions_registered_team_ids($tournamentId)
{
    $teamIds = array();

    foreach (competitions_registered_teams($tournamentId) as $team) {
        if (isset($team["id"])) {
            $teamIds[] = (string) $team["id"];
        }
    }

    return $teamIds;
}

// Toon alleen de voornaam in adminlijsten.
function competitions_first_name($name)
{
    $name = trim((string) $name);

    if ($name === "") {
        return "";
    }

    $parts = preg_split('/\s+/', $name);

    return $parts && isset($parts[0]) ? $parts[0] : $name;
}

// Controleer en vul verplichte kolommen aan.
function competitions_ensure_tournaments_schema()
{
    global $pdo;

    if (!boules_table_exists($pdo, "tournaments")) {
        competitions_respond(500, array("error" => "Tabel tournaments ontbreekt. Gebruik de bestaande database-tabellen."));
    }

    $requiredColumns = array("tournament_id", "name", "start_date", "end_date", "location", "created_by", "status", "tone");
    foreach ($requiredColumns as $column) {
        if (!competitions_tournament_has_column($column)) {
            competitions_respond(500, array("error" => "Kolom $column ontbreekt in tournaments."));
        }
    }
}

// Controleer of een kolom bestaat.
function competitions_tournament_has_column($column)
{
    global $pdo;

    return in_array($column, boules_table_columns($pdo, "tournaments"), true);
}

// Selecteer vaste kolommen voor competities.
function competitions_select_columns()
{
    $columns = array("`tournament_id`", "`name`", "`start_date`", "`end_date`", "`location`", "`status`");

    $columns[] = "`tone`";

    return implode(", ", $columns);
}

// Haal naam van de aanvrager op.
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

// Selectie voor pending aanvragen.
function competitions_pending_select_columns()
{
    return competitions_select_columns() . ", " . competitions_requester_name_expression();
}

// Haal één competitie op.
function competitions_fetch_tournament($competitionId)
{
    global $pdo;

    $statement = $pdo->prepare("SELECT " . competitions_select_columns() . " FROM `tournaments` WHERE `tournament_id` = ? LIMIT 1");
    $statement->execute(array($competitionId));

    return $statement->fetch();
}

// Vanaf de startdatum sluiten teamaanmeldingen.
function competitions_registration_closed(array $competition)
{
    $startDate = isset($competition["start_date"]) ? trim((string) $competition["start_date"]) : "";

    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) && $startDate <= date("Y-m-d");
}

// Zoek een bestaande wedstrijdentabel zonder het schema aan te passen.
function competitions_match_table_name()
{
    global $pdo;

    return boules_first_existing_table($pdo, array("matches", "wedstrijden", "games"));
}

function competitions_has_poule_match_schema()
{
    global $pdo;

    return boules_table_exists($pdo, "poules")
        && boules_table_exists($pdo, "poule_teams")
        && boules_table_exists($pdo, "wedstrijden")
        && boules_table_exists($pdo, "teamwedstrijd");
}

function competitions_existing_match_count($tableName, $tournamentColumn, $competitionId)
{
    global $pdo;

    $statement = $pdo->prepare("SELECT COUNT(*) FROM `$tableName` WHERE `$tournamentColumn` = ?");
    $statement->execute(array($competitionId));

    return (int) $statement->fetchColumn();
}

function competitions_existing_poule_match_count($competitionId)
{
    global $pdo;

    $statement = $pdo->prepare(
        "SELECT COUNT(*) FROM `wedstrijden` w " .
        "INNER JOIN `poules` p ON p.`poule_id` = w.`poule_id` " .
        "WHERE p.`tournament_id` = ?"
    );
    $statement->execute(array($competitionId));

    return (int) $statement->fetchColumn();
}

function competitions_matches_generated($competitionId)
{
    global $pdo;

    if ($competitionId === "") {
        return false;
    }

    if (competitions_has_poule_match_schema()) {
        return competitions_existing_poule_match_count($competitionId) > 0;
    }

    $matchTable = competitions_match_table_name();
    if (!$matchTable) {
        return false;
    }

    $columns = boules_table_columns($pdo, $matchTable);
    $tournamentColumn = boules_first_existing_column($columns, array("tournament_id", "competition_id", "id_tournament", "id_competition"));

    return $tournamentColumn ? competitions_existing_match_count($matchTable, $tournamentColumn, $competitionId) > 0 : false;
}

function competitions_create_round_robin_poule($competitionId, array $teamIds)
{
    global $pdo;

    $pouleColumns = boules_table_columns($pdo, "poules");
    $pouleIdColumn = boules_first_existing_column($pouleColumns, array("poule_id", "id"));
    $pouleTournamentColumn = boules_first_existing_column($pouleColumns, array("tournament_id", "competition_id", "id_tournament", "id_competition"));
    $pouleNameColumn = boules_first_existing_column($pouleColumns, array("poule_name", "name", "naam"));

    $pouleTeamColumns = boules_table_columns($pdo, "poule_teams");
    $pouleTeamPouleColumn = boules_first_existing_column($pouleTeamColumns, array("poule_id", "id_poule"));
    $pouleTeamTeamColumn = boules_first_existing_column($pouleTeamColumns, array("team_id", "id_team"));

    if (!$pouleIdColumn || !$pouleTournamentColumn || !$pouleTeamPouleColumn || !$pouleTeamTeamColumn) {
        competitions_respond(500, array("error" => "De poule-tabellen missen poule_id, tournament_id of team_id."));
    }

    $insertColumns = array("`$pouleTournamentColumn`");
    $values = array($competitionId);
    if ($pouleNameColumn) {
        $insertColumns[] = "`$pouleNameColumn`";
        $values[] = "Round robin";
    }

    $statement = $pdo->prepare(
        "INSERT INTO `poules` (" . implode(", ", $insertColumns) . ") VALUES (" . implode(", ", array_fill(0, count($values), "?")) . ")"
    );
    $statement->execute($values);
    $pouleId = $pdo->lastInsertId();

    $teamStatement = $pdo->prepare(
        "INSERT INTO `poule_teams` (`$pouleTeamPouleColumn`, `$pouleTeamTeamColumn`) VALUES (?, ?)"
    );
    foreach ($teamIds as $teamId) {
        $teamStatement->execute(array($pouleId, $teamId));
    }

    return $pouleId;
}

function competitions_insert_poule_round_robin_matches($competitionId, array $teamIds)
{
    global $pdo;

    if (competitions_existing_poule_match_count($competitionId) > 0) {
        competitions_respond(409, array("error" => "Voor deze competitie zijn al wedstrijden gegenereerd."));
    }

    $wedstrijdColumns = boules_table_columns($pdo, "wedstrijden");
    $wedstrijdIdColumn = boules_first_existing_column($wedstrijdColumns, array("wedstrijd_id", "match_id", "id"));
    $wedstrijdPouleColumn = boules_first_existing_column($wedstrijdColumns, array("poule_id", "id_poule"));
    $wedstrijdDateColumn = boules_first_existing_column($wedstrijdColumns, array("datum", "match_date", "date", "scheduled_at", "planned_at"));
    $wedstrijdVerifiedColumn = boules_first_existing_column($wedstrijdColumns, array("verified", "geverifieerd"));

    $teamwedstrijdColumns = boules_table_columns($pdo, "teamwedstrijd");
    $teamwedstrijdMatchColumn = boules_first_existing_column($teamwedstrijdColumns, array("wedstrijd_id", "match_id", "id_wedstrijd"));
    $teamwedstrijdTeamColumn = boules_first_existing_column($teamwedstrijdColumns, array("team_id", "id_team"));
    $teamwedstrijdScoreColumn = boules_first_existing_column($teamwedstrijdColumns, array("score", "punten"));

    if (!$wedstrijdIdColumn || !$wedstrijdPouleColumn || !$teamwedstrijdMatchColumn || !$teamwedstrijdTeamColumn) {
        competitions_respond(500, array("error" => "De wedstrijdtabellen missen wedstrijd_id, poule_id of team_id."));
    }

    $pouleId = competitions_create_round_robin_poule($competitionId, $teamIds);
    $rounds = competitions_round_robin_pairings($teamIds);
    $startDate = new DateTime();
    $competition = competitions_fetch_tournament($competitionId);
    if ($competition && isset($competition["start_date"])) {
        $startDate = new DateTime($competition["start_date"]);
    }

    $wedstrijdInsertColumns = array("`$wedstrijdPouleColumn`");
    if ($wedstrijdDateColumn) {
        $wedstrijdInsertColumns[] = "`$wedstrijdDateColumn`";
    }
    if ($wedstrijdVerifiedColumn) {
        $wedstrijdInsertColumns[] = "`$wedstrijdVerifiedColumn`";
    }

    $wedstrijdStatement = $pdo->prepare(
        "INSERT INTO `wedstrijden` (" . implode(", ", $wedstrijdInsertColumns) . ") VALUES (" . implode(", ", array_fill(0, count($wedstrijdInsertColumns), "?")) . ")"
    );

    $teamwedstrijdInsertColumns = array("`$teamwedstrijdMatchColumn`", "`$teamwedstrijdTeamColumn`");
    if ($teamwedstrijdScoreColumn) {
        $teamwedstrijdInsertColumns[] = "`$teamwedstrijdScoreColumn`";
    }
    $teamwedstrijdStatement = $pdo->prepare(
        "INSERT INTO `teamwedstrijd` (" . implode(", ", $teamwedstrijdInsertColumns) . ") VALUES (" . implode(", ", array_fill(0, count($teamwedstrijdInsertColumns), "?")) . ")"
    );

    $createdMatches = 0;
    foreach ($rounds as $roundIndex => $pairings) {
        $matchDate = clone $startDate;
        $matchDate->modify("+" . $roundIndex . " days");

        foreach ($pairings as $pairing) {
            $wedstrijdValues = array($pouleId);
            if ($wedstrijdDateColumn) {
                $wedstrijdValues[] = $matchDate->format("Y-m-d H:i:s");
            }
            if ($wedstrijdVerifiedColumn) {
                $wedstrijdValues[] = 0;
            }
            $wedstrijdStatement->execute($wedstrijdValues);
            $wedstrijdId = $pdo->lastInsertId();

            foreach ($pairing as $teamId) {
                $teamwedstrijdValues = array($wedstrijdId, $teamId);
                if ($teamwedstrijdScoreColumn) {
                    $teamwedstrijdValues[] = 0;
                }
                $teamwedstrijdStatement->execute($teamwedstrijdValues);
            }

            $createdMatches++;
        }
    }

    return $createdMatches;
}

function competitions_list_generated_matches(array $data)
{
    global $pdo;

    $competitionId = isset($data["id"]) ? trim((string) $data["id"]) : "";
    if ($competitionId === "" || !is_numeric($competitionId)) {
        competitions_respond(422, array("error" => "Competitie-id ontbreekt."));
    }

    if (!competitions_has_poule_match_schema() || !boules_table_exists($pdo, "teams")) {
        competitions_respond(200, array("matches" => array()));
    }

    $teamColumns = boules_table_columns($pdo, "teams");
    $teamIdColumn = boules_first_existing_column($teamColumns, array("team_id", "id"));
    $teamNameColumn = boules_first_existing_column($teamColumns, array("team_name", "name", "team", "naam"));

    if (!$teamIdColumn || !$teamNameColumn) {
        competitions_respond(200, array("matches" => array()));
    }

    $statement = $pdo->prepare(
        "SELECT w.`wedstrijd_id`, w.`datum`, w.`verified`, tw.`team_id`, tw.`score`, t.`$teamNameColumn` AS `team_name` " .
        "FROM `wedstrijden` w " .
        "INNER JOIN `poules` p ON p.`poule_id` = w.`poule_id` " .
        "INNER JOIN `teamwedstrijd` tw ON tw.`wedstrijd_id` = w.`wedstrijd_id` " .
        "INNER JOIN `teams` t ON t.`$teamIdColumn` = tw.`team_id` " .
        "WHERE p.`tournament_id` = ? " .
        "ORDER BY w.`datum` ASC, w.`wedstrijd_id` ASC, tw.`teamwedstrijd_id` ASC"
    );
    $statement->execute(array($competitionId));

    $rescheduleRequests = competitions_read_reschedule_requests();
    $userTeamIds = competitions_user_team_ids(competitions_current_user_id());
    $matches = array();
    foreach ($statement->fetchAll() as $row) {
        $matchId = (string) $row["wedstrijd_id"];
        if (!isset($matches[$matchId])) {
            $pendingRequest = isset($rescheduleRequests[$matchId]) && is_array($rescheduleRequests[$matchId])
                ? $rescheduleRequests[$matchId]
                : null;
            $approverTeamId = $pendingRequest && isset($pendingRequest["approverTeamId"]) ? (string) $pendingRequest["approverTeamId"] : "";

            $matches[$matchId] = array(
                "id" => $matchId,
                "date" => isset($row["datum"]) ? (string) $row["datum"] : "",
                "verified" => isset($row["verified"]) ? (int) $row["verified"] === 1 : false,
                "reschedule" => $pendingRequest ? array(
                    "proposedDate" => isset($pendingRequest["proposedDate"]) ? (string) $pendingRequest["proposedDate"] : "",
                    "requesterTeamId" => isset($pendingRequest["requesterTeamId"]) ? (string) $pendingRequest["requesterTeamId"] : "",
                    "approverTeamId" => $approverTeamId,
                    "canRespond" => $approverTeamId !== "" && in_array($approverTeamId, $userTeamIds, true),
                ) : null,
                "teams" => array(),
            );
        }

        $matches[$matchId]["teams"][] = array(
            "id" => isset($row["team_id"]) ? (string) $row["team_id"] : "",
            "name" => isset($row["team_name"]) && trim((string) $row["team_name"]) !== "" ? (string) $row["team_name"] : "Team",
            "score" => isset($row["score"]) ? (int) $row["score"] : 0,
        );
    }

    competitions_respond(200, array("matches" => array_values($matches)));
}

function competitions_respond_to_reschedule(array $data, $approved)
{
    global $pdo;

    $matchId = isset($data["matchId"]) ? trim((string) $data["matchId"]) : "";
    if ($matchId === "" || !is_numeric($matchId)) {
        competitions_respond(422, array("error" => "Wedstrijd-id ontbreekt."));
    }

    $requests = competitions_read_reschedule_requests();
    if (!isset($requests[$matchId]) || !is_array($requests[$matchId])) {
        competitions_respond(404, array("error" => "Er staat geen herplanaanvraag open voor deze wedstrijd."));
    }

    $request = $requests[$matchId];
    $approverTeamId = isset($request["approverTeamId"]) ? (string) $request["approverTeamId"] : "";
    $userId = competitions_current_user_id();
    if ($userId === "" || $approverTeamId === "" || !competitions_team_belongs_to_user($approverTeamId, $userId)) {
        competitions_respond(403, array("error" => "Alleen het andere team mag deze herplanaanvraag accepteren of afwijzen."));
    }

    if ($approved) {
        $proposedDate = isset($request["proposedDate"]) ? trim((string) $request["proposedDate"]) : "";
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $proposedDate)) {
            competitions_respond(422, array("error" => "De voorgestelde datum is ongeldig."));
        }

        $columns = boules_table_columns($pdo, "wedstrijden");
        $dateColumn = boules_first_existing_column($columns, array("datum", "match_date", "date", "scheduled_at", "planned_at"));
        $verifiedColumn = boules_first_existing_column($columns, array("verified", "geverifieerd"));
        if (!$dateColumn) {
            competitions_respond(500, array("error" => "Wedstrijdentabel mist datumkolom."));
        }

        $setParts = array("`$dateColumn` = ?");
        $values = array($proposedDate . " 00:00:00");
        if ($verifiedColumn) {
            $setParts[] = "`$verifiedColumn` = ?";
            $values[] = 1;
        }
        $values[] = $matchId;

        $statement = $pdo->prepare("UPDATE `wedstrijden` SET " . implode(", ", $setParts) . " WHERE `wedstrijd_id` = ?");
        $statement->execute($values);
    }

    unset($requests[$matchId]);
    competitions_write_reschedule_requests($requests);

    competitions_respond(200, array("updated" => true, "approved" => $approved));
}

function competitions_table_status_accepts($tableName, $statusColumn, $status)
{
    global $pdo;

    $statement = $pdo->prepare("SHOW COLUMNS FROM `$tableName` LIKE ?");
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

// Circle-method round robin. Bij een oneven aantal teams krijgt steeds een team vrij.
function competitions_round_robin_pairings(array $teamIds)
{
    $teamIds = array_values(array_unique(array_map("strval", $teamIds)));
    if (count($teamIds) % 2 === 1) {
        $teamIds[] = null;
    }

    $rounds = array();
    $teamCount = count($teamIds);
    $lastIndex = $teamCount - 1;

    for ($round = 1; $round < $teamCount; $round++) {
        $pairings = array();

        for ($index = 0; $index < $teamCount / 2; $index++) {
            $home = $teamIds[$index];
            $away = $teamIds[$lastIndex - $index];

            if ($home !== null && $away !== null) {
                $pairings[] = array($home, $away);
            }
        }

        $rounds[] = $pairings;
        $fixed = array_shift($teamIds);
        $rotating = array_pop($teamIds);
        array_unshift($teamIds, $fixed);
        array_splice($teamIds, 1, 0, array($rotating));
    }

    return $rounds;
}

// Valideer formulierdata uit de frontend.
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

// Maak een competitie of aanvraag aan.
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

// Geef alle pending aanvragen terug.
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

// Zet een aanvraag op accepted of afgewezen.
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

// Werk een bestaande competitie bij.
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

// Verwijder een competitie.
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

// Genereer round-robin wedstrijden in de bestaande wedstrijdentabel.
function competitions_start_tournament(array $data)
{
    global $pdo;

    competitions_require_admin();

    $competitionId = isset($data["id"]) ? trim((string) $data["id"]) : "";
    if ($competitionId === "" || !is_numeric($competitionId)) {
        competitions_respond(422, array("error" => "Competitie-id ontbreekt."));
    }

    $competition = competitions_fetch_tournament($competitionId);
    if (!$competition) {
        competitions_respond(404, array("error" => "Competitie niet gevonden."));
    }

    if (!competitions_registration_closed($competition)) {
        competitions_respond(409, array("error" => "Je kunt de competitie pas starten op of na de startdatum."));
    }

    $teamIds = competitions_registered_team_ids($competitionId);
    if (count($teamIds) < 2) {
        competitions_respond(422, array("error" => "Er zijn minimaal 2 aangemelde teams nodig om wedstrijden te genereren."));
    }

    $createdMatches = 0;
    try {
        $pdo->beginTransaction();

        if (competitions_has_poule_match_schema()) {
            $createdMatches = competitions_insert_poule_round_robin_matches($competitionId, $teamIds);
        } else {
            $matchTable = competitions_match_table_name();
            if (!$matchTable) {
                competitions_respond(500, array("error" => "Geen bestaande wedstrijdentabel gevonden. Maak geen database-aanpassing via deze actie."));
            }

            $columns = boules_table_columns($pdo, $matchTable);
            $tournamentColumn = boules_first_existing_column($columns, array("tournament_id", "competition_id", "id_tournament", "id_competition"));
            $homeTeamColumn = boules_first_existing_column($columns, array("home_team_id", "team_home_id", "team1_id", "team_a_id", "team_id_1", "team1", "team_a"));
            $awayTeamColumn = boules_first_existing_column($columns, array("away_team_id", "team_away_id", "team2_id", "team_b_id", "team_id_2", "team2", "team_b"));

            if (!$tournamentColumn || !$homeTeamColumn || !$awayTeamColumn) {
                competitions_respond(500, array("error" => "De wedstrijdentabel mist kolommen voor competitie, team 1 of team 2."));
            }

            if (competitions_existing_match_count($matchTable, $tournamentColumn, $competitionId) > 0) {
                competitions_respond(409, array("error" => "Voor deze competitie zijn al wedstrijden gegenereerd."));
            }

            $roundColumn = boules_first_existing_column($columns, array("round", "round_number", "ronde", "ronde_nummer"));
            $matchDateColumn = boules_first_existing_column($columns, array("match_date", "date", "datum", "scheduled_at", "planned_at", "start_date"));
            $statusColumn = boules_first_existing_column($columns, array("status", "state"));
            if ($statusColumn && !competitions_table_status_accepts($matchTable, $statusColumn, "planned")) {
                $statusColumn = null;
            }
            $createdAtColumn = boules_first_existing_column($columns, array("created_at", "aangemaakt_op"));
            $rounds = competitions_round_robin_pairings($teamIds);
            $startDate = new DateTime($competition["start_date"]);

            $insertColumns = array("`$tournamentColumn`", "`$homeTeamColumn`", "`$awayTeamColumn`");
            if ($roundColumn) {
                $insertColumns[] = "`$roundColumn`";
            }
            if ($matchDateColumn) {
                $insertColumns[] = "`$matchDateColumn`";
            }
            if ($statusColumn) {
                $insertColumns[] = "`$statusColumn`";
            }
            if ($createdAtColumn) {
                $insertColumns[] = "`$createdAtColumn`";
            }

            $statement = $pdo->prepare(
                "INSERT INTO `$matchTable` (" . implode(", ", $insertColumns) . ") VALUES (" . implode(", ", array_fill(0, count($insertColumns), "?")) . ")"
            );

            foreach ($rounds as $roundIndex => $pairings) {
                $roundNumber = $roundIndex + 1;
                $matchDate = clone $startDate;
                $matchDate->modify("+" . $roundIndex . " days");

                foreach ($pairings as $pairing) {
                    $values = array($competitionId, $pairing[0], $pairing[1]);
                    if ($roundColumn) {
                        $values[] = $roundNumber;
                    }
                    if ($matchDateColumn) {
                        $values[] = $matchDate->format("Y-m-d");
                    }
                    if ($statusColumn) {
                        $values[] = "planned";
                    }
                    if ($createdAtColumn) {
                        $values[] = date("Y-m-d H:i:s");
                    }

                    $statement->execute($values);
                    $createdMatches++;
                }
            }
        }

        $pdo->commit();
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        competitions_respond(500, array("error" => "Wedstrijden konden niet worden gegenereerd: " . $exception->getMessage()));
    }

    $competition = competitions_fetch_tournament($competitionId);
    competitions_respond(200, array(
        "competition" => $competition ? competitions_public_record($competition) : null,
        "started" => true,
        "matchesCreated" => $createdMatches,
    ));
}

// Check of de sessiegebruiker lid is van het team.
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

function competitions_user_team_ids($userId)
{
    global $pdo;

    if ($userId === "" || !boules_table_exists($pdo, "team_members")) {
        return array();
    }

    $columns = boules_table_columns($pdo, "team_members");
    $teamColumn = boules_first_existing_column($columns, array("team_id", "id_team"));
    $userColumn = boules_first_existing_column($columns, array("user_id", "id_user"));

    if (!$teamColumn || !$userColumn) {
        return array();
    }

    $statement = $pdo->prepare("SELECT `$teamColumn` FROM `team_members` WHERE `$userColumn` = ?");
    $statement->execute(array($userId));

    return array_map("strval", array_column($statement->fetchAll(), $teamColumn));
}

// Controleer of de statuskolom deze waarde accepteert.
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

// Meld een team aan voor een competitie.
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

    if (competitions_registration_closed($competition)) {
        competitions_respond(409, array("error" => "De inschrijving is gesloten omdat de startdatum is bereikt."));
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

// Alleen POST-verzoeken zijn toegestaan.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    competitions_respond(405, array("error" => "Alleen POST is toegestaan."));
}

// Bereid de competitietabel voor.
try {
    competitions_ensure_tournaments_schema();
} catch (PDOException $exception) {
    competitions_respond(500, array("error" => "Competitietabel kon niet worden voorbereid: " . $exception->getMessage()));
}

$data = competitions_json_body();
$action = isset($data["action"]) ? (string) $data["action"] : "request";

// Routeer de gevraagde actie.
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

if ($action === "start") {
    competitions_start_tournament($data);
}

if ($action === "listMatches") {
    competitions_list_generated_matches($data);
}

if ($action === "approveReschedule") {
    competitions_respond_to_reschedule($data, true);
}

if ($action === "rejectReschedule") {
    competitions_respond_to_reschedule($data, false);
}

if ($action === "registerTeam") {
    competitions_register_team($data);
}

competitions_respond(400, array("error" => "Onbekende competitie-actie."));
