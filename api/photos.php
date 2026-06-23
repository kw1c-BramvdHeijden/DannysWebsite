<?php

header("Content-Type: application/json; charset=utf-8");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/bootstrap-data.php";

function respond_json($statusCode, array $payload)
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function read_json_body()
{
    $rawBody = file_get_contents("php://input");
    $data = json_decode($rawBody, true);

    return is_array($data) ? $data : array();
}

function column_meta_by_name($pdo, $tableName)
{
    $columns = array();

    foreach ($pdo->query("DESCRIBE `$tableName`") as $column) {
        if (isset($column["Field"])) {
            $columns[$column["Field"]] = $column;
        }
    }

    return $columns;
}

function first_column_name(array $columns, array $candidates)
{
    foreach ($candidates as $candidate) {
        if (isset($columns[$candidate])) {
            return $candidate;
        }
    }

    return null;
}

function validate_data_url_image($dataUrl)
{
    if (!is_string($dataUrl) || !preg_match('/^data:image\/(png|jpe?g|webp|gif);base64,/', $dataUrl, $matches)) {
        respond_json(422, array("error" => "Ongeldige afbeelding."));
    }

    $base64 = substr($dataUrl, strpos($dataUrl, ",") + 1);
    $binary = base64_decode($base64, true);
    if ($binary === false) {
        respond_json(422, array("error" => "Afbeelding kon niet worden gelezen."));
    }

    if (strlen($binary) > 10 * 1024 * 1024) {
        respond_json(413, array("error" => "Afbeelding is te groot."));
    }

    $type = strtolower($matches[1]);
    if ($type === "jpeg") {
        $type = "jpg";
    }

    return array(
        "extension" => $type,
        "contents" => $binary,
    );
}

function require_photo_user($pdo)
{
    $auth = boules_fetch_auth($pdo);

    if (!isset($auth["loggedIn"]) || $auth["loggedIn"] !== true || empty($auth["user"]["id"])) {
        respond_json(401, array("error" => "Log in om foto's te beheren."));
    }

    return $auth;
}

function save_photo_file($dataUrl)
{
    $image = validate_data_url_image($dataUrl);
    $uploadDir = __DIR__ . "/../uploads/fotos";

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        respond_json(500, array("error" => "Uploadmap kon niet worden aangemaakt."));
    }

    $fileName = bin2hex(random_bytes(16)) . "." . $image["extension"];
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

    if (file_put_contents($targetPath, $image["contents"], LOCK_EX) === false) {
        respond_json(500, array("error" => "Afbeelding kon niet worden opgeslagen."));
    }

    return "uploads/fotos/" . $fileName;
}

function delete_local_photo_file($imagePath)
{
    $path = parse_url((string) $imagePath, PHP_URL_PATH);
    $path = str_replace("\\", "/", $path ? $path : "");
    $path = ltrim($path, "/");

    while (strpos($path, "../") === 0) {
        $path = substr($path, 3);
    }

    if (strpos($path, "uploads/fotos/") !== 0) {
        return;
    }

    $baseDir = realpath(__DIR__ . "/../uploads/fotos");
    $targetPath = realpath(__DIR__ . "/../" . $path);

    if (!$baseDir || !$targetPath || strpos($targetPath, $baseDir . DIRECTORY_SEPARATOR) !== 0 || !is_file($targetPath)) {
        return;
    }

    @unlink($targetPath);
}

function delete_photo($pdo, $table, array $columns, $imageColumn, $photoId, array $auth)
{
    $idColumn = first_column_name($columns, array("id", "foto_id", "photo_id"));
    if (!$idColumn) {
        respond_json(500, array("error" => "Geen id-kolom gevonden in `$table`."));
    }

    $ownerColumn = first_column_name($columns, array("ownerId", "owner_id", "user_id"));
    $selectColumns = array("`$idColumn`", "`$imageColumn` AS `image`");
    if ($ownerColumn) {
        $selectColumns[] = "`$ownerColumn` AS `ownerId`";
    }

    $statement = $pdo->prepare("SELECT " . implode(", ", $selectColumns) . " FROM `$table` WHERE `$idColumn` = ? LIMIT 1");
    $statement->execute(array($photoId));
    $photo = $statement->fetch();
    if (!$photo) {
        respond_json(404, array("error" => "Foto niet gevonden."));
    }

    $isAdmin = isset($auth["role"]) && $auth["role"] === "admin";
    $currentUserId = isset($auth["user"]["id"]) ? (string) $auth["user"]["id"] : "";
    $photoOwnerId = isset($photo["ownerId"]) ? (string) $photo["ownerId"] : "";

    if (!$isAdmin && ($photoOwnerId === "" || $photoOwnerId !== $currentUserId)) {
        respond_json(403, array("error" => "Je mag alleen je eigen foto's verwijderen."));
    }

    $statement = $pdo->prepare("DELETE FROM `$table` WHERE `$idColumn` = ?");
    $statement->execute(array($photoId));
    delete_local_photo_file(isset($photo["image"]) ? $photo["image"] : "");

    respond_json(200, array("deleted" => true, "id" => (string) $photoId));
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respond_json(405, array("error" => "Alleen POST is toegestaan."));
}

$table = boules_first_existing_table($pdo, array("fotos", "photos"));
if (!$table) {
    respond_json(500, array("error" => "Geen fototabel gevonden."));
}

$columns = column_meta_by_name($pdo, $table);
$imageColumn = first_column_name($columns, array("image", "image_url", "image_path", "path", "url", "file_path", "foto", "foto_url", "afbeelding", "bestand", "bestandsnaam", "pad"));

if (!$imageColumn) {
    respond_json(500, array(
        "error" => "Geen afbeeldingskolom gevonden in `$table`. Gevonden kolommen: " . implode(", ", array_keys($columns)),
    ));
}

$data = read_json_body();
$action = isset($data["action"]) ? (string) $data["action"] : "create";

if ($action === "delete") {
    $auth = require_photo_user($pdo);
    $photoId = isset($data["id"]) ? trim((string) $data["id"]) : "";
    if ($photoId === "") {
        respond_json(422, array("error" => "Foto-id ontbreekt."));
    }

    delete_photo($pdo, $table, $columns, $imageColumn, $photoId, $auth);
}

if ($action !== "create") {
    respond_json(400, array("error" => "Onbekende foto-actie."));
}

$photo = isset($data["photo"]) && is_array($data["photo"]) ? $data["photo"] : array();
$auth = require_photo_user($pdo);
$title = isset($photo["title"]) ? trim((string) $photo["title"]) : "";
$description = isset($photo["description"]) ? trim((string) $photo["description"]) : "";
$author = isset($auth["user"]["name"]) ? trim((string) $auth["user"]["name"]) : "";
$ownerId = isset($auth["user"]["id"]) ? trim((string) $auth["user"]["id"]) : "";
$createdAt = isset($photo["createdAt"]) && is_numeric($photo["createdAt"]) ? (int) $photo["createdAt"] : time() * 1000;

if ($title === "") {
    respond_json(422, array("error" => "Titel is verplicht."));
}

$imagePath = save_photo_file(isset($photo["image"]) ? $photo["image"] : "");

$insert = array($imageColumn => $imagePath);

$titleColumn = first_column_name($columns, array("title", "caption", "titel", "naam"));
if ($titleColumn) {
    $insert[$titleColumn] = $title;
}

$descriptionColumn = first_column_name($columns, array("description", "beschrijving"));
if ($descriptionColumn) {
    $insert[$descriptionColumn] = $description;
}

$authorColumn = first_column_name($columns, array("author", "user_name", "auteur"));
if ($authorColumn) {
    $insert[$authorColumn] = $author;
}

$ownerColumn = first_column_name($columns, array("ownerId", "owner_id", "user_id"));
if ($ownerColumn && $ownerId !== "" && is_numeric($ownerId)) {
    $insert[$ownerColumn] = $ownerId;
}

$createdColumn = first_column_name($columns, array("createdAt", "created_at", "uploaded_at", "date", "datum", "aangemaakt_op", "geupload_op"));
if ($createdColumn) {
    $insert[$createdColumn] = date("Y-m-d H:i:s", (int) floor($createdAt / 1000));
}

$columnSql = implode(", ", array_map(function ($column) {
    return "`$column`";
}, array_keys($insert)));
$placeholderSql = implode(", ", array_fill(0, count($insert), "?"));

try {
    $statement = $pdo->prepare("INSERT INTO `$table` ($columnSql) VALUES ($placeholderSql)");
    $statement->execute(array_values($insert));
} catch (PDOException $exception) {
    delete_local_photo_file($imagePath);
    respond_json(500, array("error" => "Foto kon niet in de database worden opgeslagen: " . $exception->getMessage()));
}

$idColumn = first_column_name($columns, array("id", "foto_id", "photo_id"));
$newId = $idColumn ? $pdo->lastInsertId() : $imagePath;

respond_json(201, array(
    "photo" => array(
        "id" => (string) $newId,
        "author" => $author,
        "ownerId" => $ownerId,
        "title" => $title,
        "description" => $description,
        "createdAt" => $createdAt,
        "image" => $imagePath,
    ),
));
