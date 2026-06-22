<?php

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
    foreach ($columnNames as $columnName) {
        if (in_array($columnName, $columns, true)) {
            return $columnName;
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

function boules_registered_team_ids($pdo, $tournamentId)
{
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

    foreach ($competitions as $index => $competition) {
        $competitionId = isset($competition["id"]) ? (string) $competition["id"] : "";
        $competitions[$index]["registeredTeamIds"] = boules_registered_team_ids($pdo, $competitionId);
    }

    return $competitions;
}

function boules_fetch_leaderboard($pdo)
{
    $table = boules_first_existing_table($pdo, array("leaderboard", "teams"));
    if (!$table) {
        return array();
    }

    $columns = boules_table_columns($pdo, $table);
    $team = boules_first_existing_column($columns, array("team", "team_name", "naam", "name"));
    $played = boules_first_existing_column($columns, array("played", "gespeeld", "matches_played", "wedstrijden"));
    $won = boules_first_existing_column($columns, array("won", "gewonnen", "wins"));
    $diff = boules_first_existing_column($columns, array("diff", "puntverschil", "point_diff", "doelsaldo"));
    $points = boules_first_existing_column($columns, array("points", "punten", "score"));
    $trend = boules_first_existing_column($columns, array("trend", "richting"));

    if (!$team) {
        return array();
    }

    $orderColumn = $points ? $points : $team;
    $sql = "SELECT " . implode(", ", array(
        boules_select_alias($team, "team", "''"),
        boules_select_alias($played, "played", "0"),
        boules_select_alias($won, "won", "0"),
        boules_select_alias($diff, "diff", "0"),
        boules_select_alias($points, "points", "0"),
        boules_select_alias($trend, "trend", "'flat'"),
    )) . " FROM `$table` ORDER BY `$orderColumn` DESC";

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

function boules_bootstrap_data($pdo, $competitionHref, $photoLimit, $assetPrefix)
{
    return array(
        "auth" => boules_fetch_auth($pdo),
        "competitions" => boules_fetch_competitions($pdo, $competitionHref),
        "leaderboard" => boules_fetch_leaderboard($pdo),
        "photos" => boules_fetch_photos($pdo, $photoLimit, $assetPrefix),
    );
}
