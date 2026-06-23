<?php
require_once __DIR__ . "/leaderboard-data.php";

function boules_table_exists($pdo, $tableName)
{
    $statement = $pdo->prepare("SHOW TABLES LIKE ?");
    $statement->execute(array($tableName));

    return $statement->fetchColumn() !== false;
}

function boules_first_existing_table($pdo, array $tableNames)
{
    foreach ($tableNames as $tableName) {
        if (boules_table_exists($pdo, $tableName)) {
            return $tableName;
        }
    }

    return null;
}

function boules_table_columns($pdo, $tableName)
{
    $columns = array();

    foreach ($pdo->query("DESCRIBE `$tableName`") as $column) {
        if (isset($column["Field"])) {
            $columns[] = $column["Field"];
        }
    }

    return $columns;
}

function boules_first_existing_column(array $columns, array $columnNames)
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

function boules_select_alias($column, $alias, $fallback)
{
    return $column ? "`$column` AS `$alias`" : "$fallback AS `$alias`";
}

function boules_auth_initials($name)
{
    $name = trim((string) $name);

    return $name === "" ? "A" : strtoupper(substr($name, 0, 1));
}

function boules_auth_normalize_role($role)
{
    $role = strtolower(trim((string) $role));

    return $role === "admin" || $role === "administrator" || $role === "beheerder" ? "admin" : "player";
}

function boules_auth_user_role($pdo, $userId, array $user, array $userColumns)
{
    $roleColumn = boules_first_existing_column($userColumns, array("role", "rol"));
    if ($roleColumn && isset($user[$roleColumn])) {
        return boules_auth_normalize_role($user[$roleColumn]);
    }

    if (!boules_table_exists($pdo, "user_roles") || !boules_table_exists($pdo, "roles")) {
        return "player";
    }

    $userRoleColumns = boules_table_columns($pdo, "user_roles");
    $roleColumns = boules_table_columns($pdo, "roles");
    $userRoleUserId = boules_first_existing_column($userRoleColumns, array("user_id", "userId", "id_user"));
    $userRoleRoleId = boules_first_existing_column($userRoleColumns, array("role_id", "roleId", "id_role"));
    $roleId = boules_first_existing_column($roleColumns, array("role_id", "id"));
    $roleName = boules_first_existing_column($roleColumns, array("role_name", "name", "role", "rol", "title"));

    if (!$userRoleUserId || !$userRoleRoleId || !$roleId || !$roleName) {
        return "player";
    }

    $statement = $pdo->prepare(
        "SELECT r.`$roleName` FROM `user_roles` ur " .
        "INNER JOIN `roles` r ON r.`$roleId` = ur.`$userRoleRoleId` " .
        "WHERE ur.`$userRoleUserId` = ?"
    );
    $statement->execute(array($userId));

    foreach ($statement->fetchAll() as $role) {
        if (boules_auth_normalize_role($role[$roleName]) === "admin") {
            return "admin";
        }
    }

    return "player";
}

// Geef de huidige sessiegebruiker door aan JavaScript.
function boules_fetch_auth($pdo)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $sessionUserId = isset($_SESSION["user_id"]) ? trim((string) $_SESSION["user_id"]) : "";
    if ($sessionUserId === "" || !is_numeric($sessionUserId) || !boules_table_exists($pdo, "users")) {
        return array("loggedIn" => false);
    }

    $columns = boules_table_columns($pdo, "users");
    $userIdColumn = boules_first_existing_column($columns, array("user_id", "id"));
    $nameColumn = boules_first_existing_column($columns, array("name", "naam", "full_name", "username", "gebruikersnaam", "email"));
    $emailColumn = boules_first_existing_column($columns, array("email", "emailadres"));

    if (!$userIdColumn) {
        return array("loggedIn" => false);
    }

    $statement = $pdo->prepare("SELECT * FROM `users` WHERE `$userIdColumn` = ? LIMIT 1");
    $statement->execute(array($sessionUserId));
    $user = $statement->fetch();

    if (!$user) {
        return array("loggedIn" => false);
    }

    $name = $nameColumn && isset($user[$nameColumn]) ? trim((string) $user[$nameColumn]) : "";
    if ($name === "" && $emailColumn && isset($user[$emailColumn])) {
        $name = trim((string) $user[$emailColumn]);
    }
    if ($name === "") {
        $name = "Account";
    }

    $role = isset($_SESSION["role"]) ? boules_auth_normalize_role($_SESSION["role"]) : boules_auth_user_role($pdo, $sessionUserId, $user, $columns);
    $_SESSION["role"] = $role;

    return array(
        "loggedIn" => true,
        "role" => $role,
        "accountRole" => $role,
        "user" => array(
            "id" => (string) $sessionUserId,
            "name" => $name,
            "initials" => boules_auth_initials($name),
        ),
    );
}

function boules_user_name_expression($pdo, $photoTable, $ownerColumn)
{
    if (!$ownerColumn || !boules_table_exists($pdo, "users")) {
        return null;
    }

    $userColumns = boules_table_columns($pdo, "users");
    $userId = boules_first_existing_column($userColumns, array("user_id", "id"));
    $username = boules_first_existing_column($userColumns, array("username", "name", "naam", "email"));

    if (!$userId || !$username) {
        return null;
    }

    return "(SELECT u.`$username` FROM `users` u WHERE u.`$userId` = `$photoTable`.`$ownerColumn` LIMIT 1)";
}

function boules_public_image_path($image, $assetPrefix)
{
    $image = trim((string) $image);

    if ($image === "" || preg_match('/^(https?:\/\/|\/|data:image\/)/', $image)) {
        return $image;
    }

    return $assetPrefix . $image;
}

// Haal aangemelde teams met naam op.
function boules_registered_teams($pdo, $tournamentId)
{
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

// Geef alleen team-id's terug voor aanmeldlogica.
function boules_registered_team_ids($pdo, $tournamentId)
{
    $teamIds = array();

    foreach (boules_registered_teams($pdo, $tournamentId) as $team) {
        if (isset($team["id"])) {
            $teamIds[] = (string) $team["id"];
        }
    }

    return $teamIds;
}

// Haal competities op voor de eerste paginalaad.
function boules_fetch_competitions($pdo, $competitionHref)
{
    $table = boules_first_existing_table($pdo, array("tournaments", "competitions"));
    if (!$table) {
        return array();
    }

    $columns = boules_table_columns($pdo, $table);
    $id = boules_first_existing_column($columns, array("id", "tournament_id", "competition_id"));
    $title = boules_first_existing_column($columns, array("title", "name", "naam", "tournament_name"));
    $type = boules_first_existing_column($columns, array("type", "competition_type", "soort", "format", "location"));
    $startDate = boules_first_existing_column($columns, array("startDate", "start_date", "startdatum", "date", "datum"));
    $endDate = boules_first_existing_column($columns, array("endDate", "end_date", "einddatum"));
    $tone = boules_first_existing_column($columns, array("tone", "kleur", "color", "accent"));
    $status = boules_first_existing_column($columns, array("status", "state", "aanvraag_status", "approval_status"));

    if (!$title || !$startDate) {
        return array();
    }

    $sql = "SELECT " . implode(", ", array(
        boules_select_alias($id, "id", "`$title`"),
        boules_select_alias($title, "title", "''"),
        boules_select_alias($type, "type", "'Toernooi'"),
        boules_select_alias($startDate, "startDate", "''"),
        boules_select_alias($endDate, "endDate", "''"),
        boules_select_alias($tone, "tone", "'green'"),
        $pdo->quote($competitionHref) . " AS `href`",
    )) . " FROM `$table`";

    if ($status) {
        $sql .= " WHERE `$status` IS NULL OR LOWER(`$status`) NOT IN ('pending', 'in afwachting', 'aangevraagd', 'rejected', 'denied', 'afgewezen')";
    }

    $sql .= " ORDER BY `$startDate` ASC";

    $competitions = $pdo->query($sql)->fetchAll();

    // Voeg aangemelde teams toe aan elke competitie.
    foreach ($competitions as $index => $competition) {
        $competitionId = isset($competition["id"]) ? (string) $competition["id"] : "";
        $registeredTeams = boules_registered_teams($pdo, $competitionId);
        $competitions[$index]["registeredTeams"] = $registeredTeams;
        $competitions[$index]["registeredTeamIds"] = array_map(function ($team) {
            return isset($team["id"]) ? (string) $team["id"] : "";
        }, $registeredTeams);
    }

    return $competitions;
}

function boules_fetch_leaderboard($pdo)
{
    if (function_exists("leaderboard_fetch_team_rows") && function_exists("leaderboard_bootstrap_entries")) {
        return leaderboard_bootstrap_entries(leaderboard_fetch_team_rows($pdo));
    }

    $table = boules_first_existing_table($pdo, array("teams", "Teams", "leaderboard"));
    if (!$table) {
        return array();
    }

    $columns = boules_table_columns($pdo, $table);
    $id = boules_first_existing_column($columns, array("team_id", "teamid", "id"));
    $team = boules_first_existing_column($columns, array("team", "team_name", "naam", "name"));
    $played = boules_first_existing_column($columns, array("played", "gespeeld", "matches_played", "wedstrijden"));
    $won = boules_first_existing_column($columns, array("won", "gewonnen", "wins"));
    $diff = boules_first_existing_column($columns, array("diff", "puntverschil", "point_diff", "doelsaldo"));
    $points = boules_first_existing_column($columns, array("points", "punten", "score"));
    $trend = boules_first_existing_column($columns, array("trend", "richting"));
    $active = boules_first_existing_column($columns, array("active", "is_active"));

    if (!$team || !$won) {
        return array();
    }

    $join = "";
    $playedExpression = $played ? "COALESCE(lb.`$played`, 0)" : "0";

    $teamMatchTable = boules_first_existing_table($pdo, array("teamwedstrijd", "TeamWedstrijd"));
    if (!$played && $id && $teamMatchTable) {
        $teamMatchColumns = boules_table_columns($pdo, $teamMatchTable);
        $teamMatchTeamColumn = boules_first_existing_column($teamMatchColumns, array("team_id", "teamid"));
        $teamMatchMatchColumn = boules_first_existing_column($teamMatchColumns, array("wedstrijd_id", "wedstrijdid", "match_id", "matchid"));

        if ($teamMatchTeamColumn && $teamMatchMatchColumn) {
            $join = " LEFT JOIN `$teamMatchTable` tw ON tw.`$teamMatchTeamColumn` = lb.`$id`";
            $playedExpression = "COUNT(DISTINCT tw.`$teamMatchMatchColumn`)";
        }
    }

    $wonExpression = "COALESCE(lb.`$won`, 0)";
    $pointsExpression = $points ? "COALESCE(lb.`$points`, 0)" : $wonExpression;
    $diffExpression = $diff ? "COALESCE(lb.`$diff`, 0)" : "(" . $wonExpression . " - GREATEST(0, " . $playedExpression . " - " . $wonExpression . "))";
    $trendExpression = $trend ? "lb.`$trend`" : "'flat'";

    $sql = "SELECT " . implode(", ", array(
        $id ? "lb.`$id` AS `teamId`" : "lb.`$team` AS `teamId`",
        "lb.`$team` AS `team`",
        $playedExpression . " AS `played`",
        $wonExpression . " AS `won`",
        $diffExpression . " AS `diff`",
        $pointsExpression . " AS `points`",
        $trendExpression . " AS `trend`",
    )) . " FROM `$table` lb" . $join;

    if ($active) {
        $sql .= " WHERE lb.`$active` = 1";
    }

    $groupColumns = array("lb.`$team`", "lb.`$won`");
    if ($id) {
        $groupColumns[] = "lb.`$id`";
    }
    if ($played) {
        $groupColumns[] = "lb.`$played`";
    }
    if ($diff) {
        $groupColumns[] = "lb.`$diff`";
    }
    if ($points) {
        $groupColumns[] = "lb.`$points`";
    }
    if ($trend) {
        $groupColumns[] = "lb.`$trend`";
    }

    $sql .= " GROUP BY " . implode(", ", array_unique($groupColumns));
    $sql .= " ORDER BY `won` DESC, `played` DESC, `team` ASC";

    return $pdo->query($sql)->fetchAll();
}

function boules_fetch_photos($pdo, $limit, $assetPrefix)
{
    $table = boules_first_existing_table($pdo, array("fotos", "photos"));
    if (!$table) {
        return array();
    }

    $columns = boules_table_columns($pdo, $table);
    $id = boules_first_existing_column($columns, array("id", "foto_id", "photo_id"));
    $author = boules_first_existing_column($columns, array("author", "user_name", "name", "naam", "auteur"));
    $ownerId = boules_first_existing_column($columns, array("ownerId", "owner_id", "user_id"));
    $title = boules_first_existing_column($columns, array("title", "caption", "titel", "naam"));
    $description = boules_first_existing_column($columns, array("description", "beschrijving"));
    $image = boules_first_existing_column($columns, array("image", "image_url", "image_path", "path", "url", "file_path", "foto", "foto_url", "afbeelding", "bestand", "bestandsnaam", "pad"));
    $createdAt = boules_first_existing_column($columns, array("createdAt", "created_at", "uploaded_at", "date", "datum", "aangemaakt_op", "geupload_op"));

    if (!$image) {
        return array();
    }

    $authorExpression = $author
        ? "`$author`"
        : boules_user_name_expression($pdo, $table, $ownerId);

    $orderColumn = $createdAt ? $createdAt : ($id ? $id : $image);
    $sql = "SELECT " . implode(", ", array(
        boules_select_alias($id, "id", "`$image`"),
        $authorExpression ? "$authorExpression AS `author`" : "'' AS `author`",
        boules_select_alias($ownerId, "ownerId", "NULL"),
        boules_select_alias($title, "title", "''"),
        boules_select_alias($description, "description", "''"),
        boules_select_alias($image, "image", "''"),
        boules_select_alias($createdAt, "createdAt", "NOW()"),
    )) . " FROM `$table` ORDER BY `$orderColumn` DESC LIMIT " . (int) $limit;

    $photos = $pdo->query($sql)->fetchAll();

    foreach ($photos as $index => $photo) {
        $photos[$index]["image"] = boules_public_image_path($photo["image"], $assetPrefix);

        if (!is_numeric($photo["createdAt"])) {
            $photos[$index]["createdAt"] = strtotime((string) $photo["createdAt"]) * 1000;
        }
    }

    return $photos;
}

// Bundel alle startdata voor de frontend.
function boules_bootstrap_data($pdo, $competitionHref, $photoLimit, $assetPrefix)
{
    return array(
        "auth" => boules_fetch_auth($pdo),
        "competitions" => boules_fetch_competitions($pdo, $competitionHref),
        "leaderboard" => boules_fetch_leaderboard($pdo),
        "photos" => boules_fetch_photos($pdo, $photoLimit, $assetPrefix),
    );
}
