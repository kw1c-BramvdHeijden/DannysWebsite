<?php

header("Content-Type: application/json; charset=utf-8");

session_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/bootstrap-data.php";

function auth_respond($statusCode, array $payload)
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function auth_json_body()
{
    $data = json_decode(file_get_contents("php://input"), true);

    return is_array($data) ? $data : array();
}

function auth_column_meta($pdo, $tableName)
{
    $columns = array();

    foreach ($pdo->query("DESCRIBE `$tableName`") as $column) {
        if (isset($column["Field"])) {
            $columns[$column["Field"]] = $column;
        }
    }

    return $columns;
}

function auth_first_column(array $columns, array $candidates)
{
    foreach ($candidates as $candidate) {
        if (isset($columns[$candidate])) {
            return $candidate;
        }
    }

    return null;
}

function auth_initials($name)
{
    $name = trim((string) $name);

    return $name === "" ? "A" : strtoupper(substr($name, 0, 1));
}

function auth_password_matches($password, $storedPassword)
{
    $storedPassword = (string) $storedPassword;

    if ($storedPassword === "") {
        return false;
    }

    $info = password_get_info($storedPassword);
    if (!empty($info["algo"])) {
        return password_verify($password, $storedPassword);
    }

    return hash_equals($storedPassword, $password);
}

function auth_user_role($pdo, $userId, array $user, array $userColumns)
{
    $roleColumn = auth_first_column($userColumns, array("role", "rol"));
    if ($roleColumn && isset($user[$roleColumn])) {
        return strtolower((string) $user[$roleColumn]) === "admin" ? "admin" : "player";
    }

    if (!boules_table_exists($pdo, "user_roles") || !boules_table_exists($pdo, "roles")) {
        return "player";
    }

    $userRoleColumns = auth_column_meta($pdo, "user_roles");
    $roleColumns = auth_column_meta($pdo, "roles");
    $urUserId = auth_first_column($userRoleColumns, array("user_id", "userId", "id_user"));
    $urRoleId = auth_first_column($userRoleColumns, array("role_id", "roleId", "id_role"));
    $roleId = auth_first_column($roleColumns, array("role_id", "id"));
    $roleName = auth_first_column($roleColumns, array("role_name", "name", "role", "rol", "title"));

    if (!$urUserId || !$urRoleId || !$roleId || !$roleName) {
        return "player";
    }

    $statement = $pdo->prepare(
        "SELECT r.`$roleName` FROM `user_roles` ur " .
        "INNER JOIN `roles` r ON r.`$roleId` = ur.`$urRoleId` " .
        "WHERE ur.`$urUserId` = ?"
    );
    $statement->execute(array($userId));

    foreach ($statement->fetchAll() as $role) {
        $roleValue = strtolower((string) $role[$roleName]);
        if ($roleValue === "admin" || $roleValue === "administrator" || $roleValue === "beheerder") {
            return "admin";
        }
    }

    return "player";
}

function auth_assign_default_role($pdo, $userId)
{
    if (!$userId || !boules_table_exists($pdo, "user_roles") || !boules_table_exists($pdo, "roles")) {
        return;
    }

    $userRoleColumns = auth_column_meta($pdo, "user_roles");
    $roleColumns = auth_column_meta($pdo, "roles");
    $urUserId = auth_first_column($userRoleColumns, array("user_id", "userId", "id_user"));
    $urRoleId = auth_first_column($userRoleColumns, array("role_id", "roleId", "id_role"));
    $roleId = auth_first_column($roleColumns, array("role_id", "id"));
    $roleName = auth_first_column($roleColumns, array("role_name", "name", "role", "rol", "title"));

    if (!$urUserId || !$urRoleId || !$roleId || !$roleName) {
        return;
    }

    $statement = $pdo->prepare("SELECT `$roleId` FROM `roles` WHERE LOWER(`$roleName`) IN ('player', 'speler', 'user') LIMIT 1");
    $statement->execute();
    $roleIdValue = $statement->fetchColumn();

    if ($roleIdValue === false) {
        return;
    }

    $statement = $pdo->prepare("INSERT IGNORE INTO `user_roles` (`$urUserId`, `$urRoleId`) VALUES (?, ?)");
    $statement->execute(array($userId, $roleIdValue));
}

function auth_public_user($pdo, array $user, array $columns)
{
    $idColumn = auth_first_column($columns, array("user_id", "id"));
    $nameColumn = auth_first_column($columns, array("name", "naam", "full_name", "username", "gebruikersnaam", "email"));
    $emailColumn = auth_first_column($columns, array("email", "emailadres"));
    $id = $idColumn && isset($user[$idColumn]) ? (string) $user[$idColumn] : "";
    $name = $nameColumn && isset($user[$nameColumn]) ? trim((string) $user[$nameColumn]) : "";

    if ($name === "" && $emailColumn && isset($user[$emailColumn])) {
        $name = trim((string) $user[$emailColumn]);
    }

    return array(
        "id" => $id,
        "name" => $name === "" ? "Account" : $name,
        "initials" => auth_initials($name),
        "role" => auth_user_role($pdo, $id, $user, $columns),
    );
}

function auth_find_user_by_identity($pdo, $identity)
{
    $columns = auth_column_meta($pdo, "users");
    $searchColumns = array();

    foreach (array("username", "gebruikersnaam", "email", "emailadres", "name", "naam") as $candidate) {
        if (isset($columns[$candidate])) {
            $searchColumns[] = $candidate;
        }
    }

    if (!$searchColumns) {
        auth_respond(500, array("error" => "Geen login-kolom gevonden in users."));
    }

    $where = implode(" OR ", array_map(function ($column) {
        return "`$column` = ?";
    }, $searchColumns));

    $statement = $pdo->prepare("SELECT * FROM `users` WHERE $where LIMIT 1");
    $statement->execute(array_fill(0, count($searchColumns), $identity));
    $user = $statement->fetch();

    return $user ? array($user, $columns) : array(null, $columns);
}

function auth_handle_login($pdo, array $data)
{
    $identity = isset($data["identity"]) ? trim((string) $data["identity"]) : "";
    $password = isset($data["password"]) ? (string) $data["password"] : "";

    if ($identity === "" || $password === "") {
        auth_respond(422, array("error" => "Vul je gebruikersnaam en wachtwoord in."));
    }

    list($user, $columns) = auth_find_user_by_identity($pdo, $identity);
    $passwordColumn = auth_first_column($columns, array("passkey", "password_hash", "password", "wachtwoord"));

    if (!$user || !$passwordColumn || !auth_password_matches($password, $user[$passwordColumn])) {
        auth_respond(401, array("error" => "De ingevoerde gegevens zijn onjuist."));
    }

    $publicUser = auth_public_user($pdo, $user, $columns);
    $_SESSION["user_id"] = $publicUser["id"];
    $_SESSION["role"] = $publicUser["role"];

    auth_respond(200, array(
        "user" => array(
            "id" => $publicUser["id"],
            "name" => $publicUser["name"],
            "initials" => $publicUser["initials"],
        ),
        "role" => $publicUser["role"],
    ));
}

function auth_handle_signup($pdo, array $data)
{
    $name = isset($data["name"]) ? trim((string) $data["name"]) : "";
    $email = isset($data["email"]) ? trim((string) $data["email"]) : "";
    $password = isset($data["password"]) ? (string) $data["password"] : "";

    if ($name === "" || $email === "" || $password === "") {
        auth_respond(422, array("error" => "Vul alle velden in om je account aan te maken."));
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        auth_respond(422, array("error" => "Vul een geldig e-mailadres in."));
    }

    if (strlen($password) < 6) {
        auth_respond(422, array("error" => "Gebruik een wachtwoord van minimaal 6 tekens."));
    }

    $columns = auth_column_meta($pdo, "users");
    $idColumn = auth_first_column($columns, array("user_id", "id"));
    $nameColumn = auth_first_column($columns, array("name", "naam", "full_name"));
    $usernameColumn = auth_first_column($columns, array("username", "gebruikersnaam"));
    $emailColumn = auth_first_column($columns, array("email", "emailadres"));
    $passwordColumn = auth_first_column($columns, array("passkey", "password_hash", "password", "wachtwoord"));
    $createdColumn = auth_first_column($columns, array("created_at", "createdAt", "aangemaakt_op"));

    if (!$passwordColumn) {
        auth_respond(500, array("error" => "Geen wachtwoordkolom gevonden in users."));
    }

    if (!$emailColumn && !$usernameColumn) {
        auth_respond(500, array("error" => "Geen e-mail of gebruikersnaamkolom gevonden in users."));
    }

    if ($emailColumn) {
        $statement = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `$emailColumn` = ?");
        $statement->execute(array($email));
        if ((int) $statement->fetchColumn() > 0) {
            auth_respond(409, array("error" => "Er bestaat al een account met dit e-mailadres."));
        }
    }

    if ($usernameColumn) {
        $statement = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `$usernameColumn` = ?");
        $statement->execute(array($name));
        if ((int) $statement->fetchColumn() > 0) {
            auth_respond(409, array("error" => "Er bestaat al een account met deze gebruikersnaam."));
        }
    }

    $insert = array($passwordColumn => password_hash($password, PASSWORD_DEFAULT));

    if ($nameColumn) {
        $insert[$nameColumn] = $name;
    }
    if ($usernameColumn) {
        $insert[$usernameColumn] = $name;
    }
    if ($emailColumn) {
        $insert[$emailColumn] = $email;
    }
    if ($createdColumn) {
        $insert[$createdColumn] = date("Y-m-d H:i:s");
    }

    $columnSql = implode(", ", array_map(function ($column) {
        return "`$column`";
    }, array_keys($insert)));
    $placeholderSql = implode(", ", array_fill(0, count($insert), "?"));

    try {
        $statement = $pdo->prepare("INSERT INTO `users` ($columnSql) VALUES ($placeholderSql)");
        $statement->execute(array_values($insert));
    } catch (PDOException $exception) {
        auth_respond(500, array("error" => "Account kon niet worden aangemaakt: " . $exception->getMessage()));
    }

    $newId = $idColumn ? $pdo->lastInsertId() : "";
    auth_assign_default_role($pdo, $newId);
    $_SESSION["user_id"] = $newId;
    $_SESSION["role"] = "player";

    auth_respond(201, array(
        "user" => array(
            "id" => (string) $newId,
            "name" => $name,
            "initials" => auth_initials($name),
        ),
        "role" => "player",
    ));
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    auth_respond(405, array("error" => "Alleen POST is toegestaan."));
}

if (!boules_table_exists($pdo, "users")) {
    auth_respond(500, array("error" => "Tabel users bestaat niet."));
}

$data = auth_json_body();
$action = isset($data["action"]) ? (string) $data["action"] : "";

if ($action === "login") {
    auth_handle_login($pdo, $data);
}

if ($action === "signup") {
    auth_handle_signup($pdo, $data);
}

auth_respond(400, array("error" => "Onbekende auth-actie."));
