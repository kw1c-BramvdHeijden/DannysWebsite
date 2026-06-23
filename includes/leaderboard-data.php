<?php

function leaderboard_e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function leaderboard_table_exists($pdo, string $tableName): bool
{
    if (!$pdo instanceof PDO) {
        return false;
    }

    try {
        $statement = $pdo->prepare("SHOW TABLES LIKE ?");
        $statement->execute(array($tableName));

        return $statement->fetchColumn() !== false;
    } catch (Throwable $exception) {
        return false;
    }
}

function leaderboard_first_existing_table($pdo, array $tableNames): ?string
{
    foreach ($tableNames as $tableName) {
        if (leaderboard_table_exists($pdo, $tableName)) {
            return $tableName;
        }
    }

    return null;
}

function leaderboard_table_columns($pdo, string $tableName): array
{
    if (!$pdo instanceof PDO) {
        return array();
    }

    try {
        $columns = array();
        foreach ($pdo->query("DESCRIBE `$tableName`") as $column) {
            if (isset($column["Field"])) {
                $columns[] = $column["Field"];
            }
        }

        return $columns;
    } catch (Throwable $exception) {
        return array();
    }
}

function leaderboard_first_existing_column(array $columns, array $columnNames): ?string
{
    $columnsByLowerName = array();
    foreach ($columns as $column) {
        $columnsByLowerName[strtolower($column)] = $column;
    }

    foreach ($columnNames as $columnName) {
        $columnKey = strtolower($columnName);
        if (isset($columnsByLowerName[$columnKey])) {
            return $columnsByLowerName[$columnKey];
        }
    }

    return null;
}

function leaderboard_rank_tone(int $rank): string
{
    if ($rank === 1) {
        return "gold";
    }

    if ($rank === 2) {
        return "silver";
    }

    if ($rank === 3) {
        return "bronze";
    }

    return "olive";
}

function leaderboard_ensure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function leaderboard_current_user_id(): string
{
    leaderboard_ensure_session();
    $userId = isset($_SESSION["user_id"]) ? trim((string) $_SESSION["user_id"]) : "";

    return $userId !== "" && is_numeric($userId) ? $userId : "";
}

function leaderboard_is_admin($pdo): bool
{
    leaderboard_ensure_session();

    if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin") {
        return true;
    }

    $userId = leaderboard_current_user_id();
    if ($userId === "" || !leaderboard_table_exists($pdo, "user_roles") || !leaderboard_table_exists($pdo, "roles")) {
        return false;
    }

    try {
        $statement = $pdo->prepare(
            "SELECT COUNT(*) FROM `user_roles` ur " .
            "INNER JOIN `roles` r ON r.`role_id` = ur.`role_id` " .
            "WHERE ur.`user_id` = ? AND LOWER(r.`role_name`) IN ('admin', 'administrator', 'beheerder')"
        );
        $statement->execute(array($userId));

        return (int) $statement->fetchColumn() > 0;
    } catch (Throwable $exception) {
        return false;
    }
}

function leaderboard_normalize_sort_direction(?string $direction): string
{
    return strtolower((string) $direction) === "asc" ? "asc" : "desc";
}

function leaderboard_fetch_teams($pdo): array
{
    $table = leaderboard_first_existing_table($pdo, array("teams", "Teams"));
    if (!$table) {
        return array();
    }

    $columns = leaderboard_table_columns($pdo, $table);
    $idColumn = leaderboard_first_existing_column($columns, array("team_id", "teamid", "id"));
    $nameColumn = leaderboard_first_existing_column($columns, array("team_name", "teamnaam", "team_naam", "team", "name", "naam"));
    $winsColumn = leaderboard_first_existing_column($columns, array("wins", "win", "won", "gewonnen", "overwinningen", "aantal_wins"));

    if (!$idColumn || !$nameColumn) {
        return array();
    }

    $select = array(
        "t.`$idColumn` AS `team_id`",
        "t.`$nameColumn` AS `team_name`",
    );

    if ($winsColumn) {
        $select[] = "COALESCE(t.`$winsColumn`, 0) AS `stored_wins`";
    } else {
        $select[] = "0 AS `stored_wins`";
    }

    $sql = "SELECT " . implode(", ", $select) . " FROM `$table` t ORDER BY t.`$nameColumn` ASC";
    $teams = array();

    foreach ($pdo->query($sql)->fetchAll() as $row) {
        $teamId = (string) $row["team_id"];
        if ($teamId === "") {
            continue;
        }

        $teams[$teamId] = array(
            "team_id" => $teamId,
            "team_name" => trim((string) $row["team_name"]) !== "" ? (string) $row["team_name"] : "Team " . $teamId,
            "stored_wins" => (int) $row["stored_wins"],
        );
    }

    return $teams;
}

function leaderboard_fetch_match_rows($pdo): array
{
    $teamMatchTable = leaderboard_first_existing_table($pdo, array("teamwedstrijd", "TeamWedstrijd", "team_matches", "team_match"));
    if (!$teamMatchTable) {
        return array();
    }

    $teamMatchColumns = leaderboard_table_columns($pdo, $teamMatchTable);
    $teamColumn = leaderboard_first_existing_column($teamMatchColumns, array("team_id", "teamid"));
    $matchColumn = leaderboard_first_existing_column($teamMatchColumns, array("wedstrijd_id", "wedstrijdid", "match_id", "matchid"));
    $scoreColumn = leaderboard_first_existing_column($teamMatchColumns, array("score", "punten", "points"));

    if (!$teamColumn || !$matchColumn || !$scoreColumn) {
        return array();
    }

    $matchTable = leaderboard_first_existing_table($pdo, array("wedstrijden", "matches", "Matches", "Wedstrijden"));
    $matchJoin = "";
    $dateSelect = "NULL AS `match_date`";
    $verifiedSelect = "NULL AS `verified`";

    if ($matchTable) {
        $matchColumns = leaderboard_table_columns($pdo, $matchTable);
        $matchIdColumn = leaderboard_first_existing_column($matchColumns, array("wedstrijd_id", "wedstrijdid", "match_id", "matchid", "id"));
        $dateColumn = leaderboard_first_existing_column($matchColumns, array("datum", "date", "match_date", "played_at", "start_date"));
        $verifiedColumn = leaderboard_first_existing_column($matchColumns, array("verified", "is_verified", "bevestigd", "afgerond", "completed", "is_completed"));

        if ($matchIdColumn) {
            $matchJoin = " LEFT JOIN `$matchTable` w ON w.`$matchIdColumn` = tw.`$matchColumn`";
            if ($dateColumn) {
                $dateSelect = "w.`$dateColumn` AS `match_date`";
            }
            if ($verifiedColumn) {
                $verifiedSelect = "w.`$verifiedColumn` AS `verified`";
            }
        }
    }

    $sql =
        "SELECT " .
        "tw.`$teamColumn` AS `team_id`, " .
        "tw.`$matchColumn` AS `match_id`, " .
        "COALESCE(tw.`$scoreColumn`, 0) AS `score`, " .
        $dateSelect . ", " .
        $verifiedSelect . " " .
        "FROM `$teamMatchTable` tw" .
        $matchJoin .
        " ORDER BY `match_date` ASC, tw.`$matchColumn` ASC";

    return $pdo->query($sql)->fetchAll();
}

function leaderboard_match_sort_key(array $matchRows): string
{
    $firstRow = $matchRows[0] ?? array();
    $date = isset($firstRow["match_date"]) ? trim((string) $firstRow["match_date"]) : "";
    $matchId = isset($firstRow["match_id"]) ? trim((string) $firstRow["match_id"]) : "";
    $matchSortId = is_numeric($matchId) ? str_pad((string) (int) $matchId, 12, "0", STR_PAD_LEFT) : $matchId;

    return ($date !== "" ? $date : "0000-00-00 00:00:00") . "|" . $matchSortId;
}

function leaderboard_match_result(array $matchRows): array
{
    $topScore = null;
    $winnerTeamId = null;
    $topScoreCount = 0;

    foreach ($matchRows as $row) {
        $score = (int) $row["score"];

        if ($topScore === null || $score > $topScore) {
            $topScore = $score;
            $winnerTeamId = (string) $row["team_id"];
            $topScoreCount = 1;
        } elseif ($score === $topScore) {
            $topScoreCount++;
        }
    }

    $completed = $topScore !== null && $topScore >= 13 && $topScoreCount === 1;

    return array(
        "completed" => $completed,
        "winnerTeamId" => $completed ? $winnerTeamId : null,
    );
}

function leaderboard_opponent_score(array $matchRows, string $teamId): int
{
    $opponentScore = 0;
    $hasOpponent = false;

    foreach ($matchRows as $row) {
        if ((string) $row["team_id"] === $teamId) {
            continue;
        }

        $hasOpponent = true;
        $opponentScore = max($opponentScore, (int) $row["score"]);
    }

    return $hasOpponent ? $opponentScore : 0;
}

function leaderboard_empty_stats(array $team): array
{
    return array(
        "team_id" => $team["team_id"],
        "team_name" => $team["team_name"],
        "played" => 0,
        "won" => 0,
        "lost" => 0,
        "score_for" => 0,
        "score_against" => 0,
        "diff" => 0,
        "points" => 0,
        "trend" => "flat",
        "latest_result_key" => "",
        "stored_wins" => (int) $team["stored_wins"],
    );
}

function leaderboard_calculate_rows(array $teams, array $matchRows): array
{
    $statsByTeam = array();
    foreach ($teams as $teamId => $team) {
        $statsByTeam[$teamId] = leaderboard_empty_stats($team);
    }

    $matches = array();
    foreach ($matchRows as $row) {
        $matchId = isset($row["match_id"]) ? (string) $row["match_id"] : "";
        if ($matchId === "") {
            continue;
        }

        if (!isset($matches[$matchId])) {
            $matches[$matchId] = array();
        }

        $matches[$matchId][] = array(
            "match_id" => $matchId,
            "team_id" => (string) $row["team_id"],
            "score" => (int) $row["score"],
            "match_date" => isset($row["match_date"]) ? (string) $row["match_date"] : "",
            "verified" => isset($row["verified"]) ? $row["verified"] : null,
        );
    }

    foreach ($matches as $match) {
        if (count($match) < 2) {
            continue;
        }

        $result = leaderboard_match_result($match);
        if (!$result["completed"]) {
            continue;
        }

        $sortKey = leaderboard_match_sort_key($match);
        $winnerTeamId = (string) $result["winnerTeamId"];

        foreach ($match as $row) {
            $teamId = (string) $row["team_id"];
            if (!isset($statsByTeam[$teamId])) {
                continue;
            }

            $score = (int) $row["score"];
            $opponentScore = leaderboard_opponent_score($match, $teamId);
            $won = $teamId === $winnerTeamId;

            $statsByTeam[$teamId]["played"]++;
            $statsByTeam[$teamId]["score_for"] += $score;
            $statsByTeam[$teamId]["score_against"] += $opponentScore;

            if ($won) {
                $statsByTeam[$teamId]["won"]++;
            } else {
                $statsByTeam[$teamId]["lost"]++;
            }

            if ($sortKey >= $statsByTeam[$teamId]["latest_result_key"]) {
                $statsByTeam[$teamId]["latest_result_key"] = $sortKey;
                $statsByTeam[$teamId]["trend"] = $won ? "up" : "down";
            }
        }
    }

    foreach ($statsByTeam as $teamId => $stats) {
        if (count($matches) === 0 && $stats["stored_wins"] > 0) {
            $stats["won"] = $stats["stored_wins"];
        }

        $stats["diff"] = $stats["score_for"] - $stats["score_against"];
        $stats["points"] = $stats["won"];
        unset($stats["latest_result_key"], $stats["stored_wins"], $stats["score_for"], $stats["score_against"]);
        $statsByTeam[$teamId] = $stats;
    }

    return array_values($statsByTeam);
}

function leaderboard_sort_rows(array $rows, ?string $sortDirection = "desc"): array
{
    $sortDirection = leaderboard_normalize_sort_direction($sortDirection);
    $primaryDirection = $sortDirection === "asc" ? 1 : -1;

    usort($rows, function ($left, $right) use ($primaryDirection, $sortDirection) {
        $winsCompare = ((int) $left["won"]) <=> ((int) $right["won"]);
        if ($winsCompare !== 0) {
            return $primaryDirection * $winsCompare;
        }

        $diffCompare = ((int) $left["diff"]) <=> ((int) $right["diff"]);
        if ($diffCompare !== 0) {
            return $primaryDirection * $diffCompare;
        }

        $lossCompare = ((int) $left["lost"]) <=> ((int) $right["lost"]);
        if ($lossCompare !== 0) {
            return $sortDirection === "desc" ? $lossCompare : -$lossCompare;
        }

        return strcasecmp((string) $left["team_name"], (string) $right["team_name"]);
    });

    foreach ($rows as $index => $row) {
        $rank = $index + 1;
        $rows[$index]["rank"] = $rank;
        $rows[$index]["tone"] = leaderboard_rank_tone($rank);
    }

    return $rows;
}

function leaderboard_fetch_team_rows($pdo, ?string $sortDirection = "desc"): array
{
    if (!$pdo instanceof PDO) {
        return array();
    }

    try {
        $teams = leaderboard_fetch_teams($pdo);
        if (!$teams) {
            return array();
        }

        $rows = leaderboard_calculate_rows($teams, leaderboard_fetch_match_rows($pdo));

        return leaderboard_sort_rows($rows, $sortDirection);
    } catch (Throwable $exception) {
        return array();
    }
}

function leaderboard_bootstrap_entries(array $rows): array
{
    return array_map(function ($row) {
        return array(
            "id" => $row["team_id"],
            "teamId" => $row["team_id"],
            "team" => $row["team_name"],
            "played" => $row["played"],
            "won" => $row["won"],
            "lost" => $row["lost"],
            "diff" => $row["diff"],
            "points" => $row["points"],
            "trend" => $row["trend"],
            "tone" => $row["tone"],
        );
    }, $rows);
}

function leaderboard_format_diff($diff): string
{
    $diff = (int) $diff;

    return $diff > 0 ? "+" . $diff : (string) $diff;
}

function leaderboard_trend_label($trend): string
{
    if ($trend === "up") {
        return "Stijgt";
    }

    if ($trend === "down") {
        return "Daalt";
    }

    return "Gelijk";
}
