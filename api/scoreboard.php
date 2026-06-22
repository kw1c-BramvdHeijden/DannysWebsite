<?php

header("Content-Type: application/json; charset=utf-8");

session_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/bootstrap-data.php";

function scoreboard_respond($statusCode, array $payload)
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function scoreboard_json_body()
{
    $data = json_decode(file_get_contents("php://input"), true);

    return is_array($data) ? $data : array();
}

function scoreboard_require_tables()
{
    global $pdo;

    foreach (array("wedstrijden", "teamwedstrijd", "teams", "poules", "tournaments") as $table) {
        if (!boules_table_exists($pdo, $table)) {
            scoreboard_respond(500, array("error" => "Tabel $table ontbreekt in de database."));
        }
    }
}

function scoreboard_current_user_id()
{
    $userId = isset($_SESSION["user_id"]) ? trim((string) $_SESSION["user_id"]) : "";

    return $userId !== "" && is_numeric($userId) ? $userId : "";
}

function scoreboard_is_admin()
{
    global $pdo;

    if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin") {
        return true;
    }

    $userId = scoreboard_current_user_id();
    if ($userId === "" || !boules_table_exists($pdo, "user_roles") || !boules_table_exists($pdo, "roles")) {
        return false;
    }

    $statement = $pdo->prepare(
        "SELECT COUNT(*) FROM `user_roles` ur " .
        "INNER JOIN `roles` r ON r.`role_id` = ur.`role_id` " .
        "WHERE ur.`user_id` = ? AND LOWER(r.`role_name`) IN ('admin', 'administrator', 'beheerder')"
    );
    $statement->execute(array($userId));

    return (int) $statement->fetchColumn() > 0;
}

function scoreboard_user_in_match($wedstrijdId)
{
    global $pdo;

    $userId = scoreboard_current_user_id();
    if ($userId === "" || !boules_table_exists($pdo, "team_members")) {
        return false;
    }

    $statement = $pdo->prepare(
        "SELECT COUNT(*) FROM `teamwedstrijd` tw " .
        "INNER JOIN `team_members` tm ON tm.`team_id` = tw.`team_id` " .
        "WHERE tw.`wedstrijd_id` = ? AND tm.`user_id` = ?"
    );
    $statement->execute(array($wedstrijdId, $userId));

    return (int) $statement->fetchColumn() > 0;
}

function scoreboard_can_edit_match($wedstrijdId)
{
    return scoreboard_is_admin() || scoreboard_user_in_match($wedstrijdId);
}

function scoreboard_match_is_live(array $match)
{
    if ($match["completed"] || $match["date"] === "") {
        return false;
    }

    $matchDate = substr((string) $match["date"], 0, 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $matchDate)) {
        return false;
    }

    return $matchDate <= date("Y-m-d");
}

function scoreboard_finalize_match(array $match)
{
    $topScore = -1;
    $winnerTeamId = null;
    $hasTie = false;

    foreach ($match["teams"] as $team) {
        $score = (int) $team["score"];

        if ($score > $topScore) {
            $topScore = $score;
            $winnerTeamId = $team["id"];
            $hasTie = false;
        } elseif ($score === $topScore) {
            $hasTie = true;
        }
    }

    $match["completed"] = $topScore >= 13 && !$hasTie;
    $match["winnerTeamId"] = $match["completed"] ? (string) $winnerTeamId : null;
    $match["isLive"] = scoreboard_match_is_live($match);
    $match["canEdit"] = scoreboard_can_edit_match($match["id"]);

    return $match;
}

function scoreboard_fetch_matches($wedstrijdId = null, $scopeToCurrentUser = false)
{
    global $pdo;

    $userId = scoreboard_current_user_id();
    $isAdmin = scoreboard_is_admin();
    $joinUserId = $userId === "" ? 0 : $userId;
    $where = array();
    $params = array($joinUserId);

    if ($wedstrijdId !== null) {
        $where[] = "w.`wedstrijd_id` = ?";
        $params[] = $wedstrijdId;
    }

    if ($scopeToCurrentUser && $userId !== "" && !$isAdmin && boules_table_exists($pdo, "team_members")) {
        $where[] = "EXISTS (" .
            "SELECT 1 FROM `teamwedstrijd` tw_scope " .
            "INNER JOIN `team_members` tm_scope ON tm_scope.`team_id` = tw_scope.`team_id` " .
            "WHERE tw_scope.`wedstrijd_id` = w.`wedstrijd_id` AND tm_scope.`user_id` = ?" .
        ")";
        $params[] = $userId;
    }

    $sql =
        "SELECT " .
        "w.`wedstrijd_id`, w.`datum`, w.`verified`, " .
        "p.`poule_id`, p.`poule_name`, " .
        "t.`tournament_id`, t.`name` AS `tournament_name`, t.`location`, " .
        "tw.`team_id`, tw.`score`, teams.`team_name`, " .
        "CASE WHEN tm.`user_id` IS NULL THEN 0 ELSE 1 END AS `is_user_team` " .
        "FROM `wedstrijden` w " .
        "INNER JOIN `poules` p ON p.`poule_id` = w.`poule_id` " .
        "INNER JOIN `tournaments` t ON t.`tournament_id` = p.`tournament_id` " .
        "INNER JOIN `teamwedstrijd` tw ON tw.`wedstrijd_id` = w.`wedstrijd_id` " .
        "INNER JOIN `teams` teams ON teams.`team_id` = tw.`team_id` " .
        "LEFT JOIN `team_members` tm ON tm.`team_id` = teams.`team_id` AND tm.`user_id` = ? ";

    if ($where) {
        $sql .= "WHERE " . implode(" AND ", $where) . " ";
    }

    $sql .= "ORDER BY COALESCE(w.`datum`, '9999-12-31') ASC, w.`wedstrijd_id` ASC, tw.`teamwedstrijd_id` ASC";

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    $matches = array();
    foreach ($statement->fetchAll() as $row) {
        $id = (string) $row["wedstrijd_id"];

        if (!isset($matches[$id])) {
            $matches[$id] = array(
                "id" => $id,
                "date" => $row["datum"] ? (string) $row["datum"] : "",
                "verified" => (bool) $row["verified"],
                "pouleId" => (string) $row["poule_id"],
                "pouleName" => (string) $row["poule_name"],
                "tournamentId" => (string) $row["tournament_id"],
                "tournamentName" => (string) $row["tournament_name"],
                "location" => isset($row["location"]) ? (string) $row["location"] : "",
                "isMine" => false,
                "teams" => array(),
            );
        }

        $isUserTeam = (int) $row["is_user_team"] === 1;
        $matches[$id]["isMine"] = $matches[$id]["isMine"] || $isUserTeam;
        $matches[$id]["teams"][] = array(
            "id" => (string) $row["team_id"],
            "name" => (string) $row["team_name"],
            "score" => (int) $row["score"],
            "isMine" => $isUserTeam,
        );
    }

    $matches = array_map("scoreboard_finalize_match", array_values($matches));
    usort($matches, function ($left, $right) {
        if ($left["isMine"] !== $right["isMine"]) {
            return $left["isMine"] ? -1 : 1;
        }

        $leftDate = $left["date"] !== "" ? $left["date"] : "9999-12-31";
        $rightDate = $right["date"] !== "" ? $right["date"] : "9999-12-31";
        $dateCompare = strcmp($leftDate, $rightDate);

        return $dateCompare !== 0 ? $dateCompare : ((int) $left["id"] - (int) $right["id"]);
    });

    return $matches;
}

function scoreboard_match_team_ids($wedstrijdId)
{
    global $pdo;

    $statement = $pdo->prepare("SELECT `team_id` FROM `teamwedstrijd` WHERE `wedstrijd_id` = ? ORDER BY `teamwedstrijd_id` ASC");
    $statement->execute(array($wedstrijdId));

    return array_map("strval", $statement->fetchAll(PDO::FETCH_COLUMN));
}

function scoreboard_validate_scores($wedstrijdId, array $scores)
{
    $teamIds = scoreboard_match_team_ids($wedstrijdId);
    if (!$teamIds) {
        scoreboard_respond(404, array("error" => "Wedstrijd niet gevonden."));
    }

    $scoresByTeam = array();
    foreach ($scores as $scoreRow) {
        if (!is_array($scoreRow) || !isset($scoreRow["teamId"]) || !array_key_exists("score", $scoreRow)) {
            scoreboard_respond(422, array("error" => "Scoregegevens zijn ongeldig."));
        }

        $teamId = trim((string) $scoreRow["teamId"]);
        $score = $scoreRow["score"];

        if ($teamId === "" || !in_array($teamId, $teamIds, true) || !is_numeric($score)) {
            scoreboard_respond(422, array("error" => "Scoregegevens zijn ongeldig."));
        }

        $score = (int) $score;
        if ($score < 0 || $score > 13) {
            scoreboard_respond(422, array("error" => "Scores moeten tussen 0 en 13 liggen."));
        }

        $scoresByTeam[$teamId] = $score;
    }

    if (count($scoresByTeam) !== count($teamIds)) {
        scoreboard_respond(422, array("error" => "Vul de score van alle teams in."));
    }

    $teamsAtThirteen = 0;
    foreach ($scoresByTeam as $score) {
        if ($score >= 13) {
            $teamsAtThirteen++;
        }
    }

    if ($teamsAtThirteen > 1) {
        scoreboard_respond(422, array("error" => "Er kan maar een team 13 punten hebben."));
    }

    return $scoresByTeam;
}

function scoreboard_recalculate_team_wins()
{
    global $pdo;

    if (!in_array("wins", boules_table_columns($pdo, "teams"), true)) {
        return;
    }

    $pdo->exec("UPDATE `teams` SET `wins` = 0");

    $rows = $pdo->query("SELECT `wedstrijd_id`, `team_id`, `score` FROM `teamwedstrijd` ORDER BY `wedstrijd_id` ASC")->fetchAll();
    $matches = array();

    foreach ($rows as $row) {
        $matchId = (string) $row["wedstrijd_id"];
        if (!isset($matches[$matchId])) {
            $matches[$matchId] = array();
        }

        $matches[$matchId][] = array(
            "teamId" => (string) $row["team_id"],
            "score" => (int) $row["score"],
        );
    }

    $wins = array();
    foreach ($matches as $teams) {
        $topScore = -1;
        $winnerTeamId = null;
        $hasTie = false;

        foreach ($teams as $team) {
            if ($team["score"] > $topScore) {
                $topScore = $team["score"];
                $winnerTeamId = $team["teamId"];
                $hasTie = false;
            } elseif ($team["score"] === $topScore) {
                $hasTie = true;
            }
        }

        if ($topScore >= 13 && !$hasTie && $winnerTeamId !== null) {
            if (!isset($wins[$winnerTeamId])) {
                $wins[$winnerTeamId] = 0;
            }

            $wins[$winnerTeamId]++;
        }
    }

    if (!$wins) {
        return;
    }

    $statement = $pdo->prepare("UPDATE `teams` SET `wins` = ? WHERE `team_id` = ?");
    foreach ($wins as $teamId => $winCount) {
        $statement->execute(array($winCount, $teamId));
    }
}

function scoreboard_update_scores(array $data)
{
    global $pdo;

    $wedstrijdId = isset($data["wedstrijdId"]) ? trim((string) $data["wedstrijdId"]) : "";
    if ($wedstrijdId === "" || !is_numeric($wedstrijdId)) {
        scoreboard_respond(422, array("error" => "Wedstrijd-id ontbreekt."));
    }

    if (scoreboard_current_user_id() === "") {
        scoreboard_respond(401, array("error" => "Log in om scores op te slaan."));
    }

    if (!scoreboard_can_edit_match($wedstrijdId)) {
        scoreboard_respond(403, array("error" => "Je mag alleen je eigen wedstrijd bijwerken."));
    }

    $scores = isset($data["scores"]) && is_array($data["scores"]) ? $data["scores"] : array();
    $scoresByTeam = scoreboard_validate_scores($wedstrijdId, $scores);

    try {
        $pdo->beginTransaction();

        $statement = $pdo->prepare("UPDATE `teamwedstrijd` SET `score` = ? WHERE `wedstrijd_id` = ? AND `team_id` = ?");
        foreach ($scoresByTeam as $teamId => $score) {
            $statement->execute(array($score, $wedstrijdId, $teamId));
        }

        scoreboard_recalculate_team_wins();
        $pdo->commit();
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        scoreboard_respond(500, array("error" => "Score kon niet worden opgeslagen: " . $exception->getMessage()));
    }

    $matches = scoreboard_fetch_matches($wedstrijdId, false);
    scoreboard_respond(200, array(
        "match" => $matches ? $matches[0] : null,
    ));
}

scoreboard_require_tables();

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $wedstrijdId = isset($_GET["wedstrijd_id"]) ? trim((string) $_GET["wedstrijd_id"]) : "";
    if ($wedstrijdId === "" && isset($_GET["matchId"])) {
        $wedstrijdId = trim((string) $_GET["matchId"]);
    }

    if ($wedstrijdId !== "" && !is_numeric($wedstrijdId)) {
        scoreboard_respond(422, array("error" => "Wedstrijd-id is ongeldig."));
    }

    $matches = scoreboard_fetch_matches($wedstrijdId !== "" ? $wedstrijdId : null, false);
    scoreboard_respond(200, array(
        "matches" => $matches,
        "loggedIn" => scoreboard_current_user_id() !== "",
        "role" => scoreboard_is_admin() ? "admin" : "player",
    ));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = scoreboard_json_body();
    $action = isset($data["action"]) ? (string) $data["action"] : "update";

    if ($action === "update") {
        scoreboard_update_scores($data);
    }

    scoreboard_respond(400, array("error" => "Onbekende scoreboard-actie."));
}

scoreboard_respond(405, array("error" => "Alleen GET en POST zijn toegestaan."));
