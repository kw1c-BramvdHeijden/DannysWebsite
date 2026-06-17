<?php

header("Content-Type: application/json; charset=utf-8");

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
    if (!is_string($dataUrl) || !preg_match('/^data:image\/(png|jpe?g|webp|gif);base64,/', $dataUrl)) {
        respond_json(422, array("error" => "Ongeldige afbeelding."));
    }

    $base64 = substr($dataUrl, strpos($dataUrl, ",") + 1);
    if (base64_decode($base64, true) === false) {
        respond_json(422, array("error" => "Afbeelding kon niet worden gelezen."));
    }

    return $dataUrl;
}

function delete_photo($pdo, $table, array $columns, $imageColumn, $photoId)
{
    $idColumn = first_column_name($columns, array("id", "foto_id", "photo_id"));
    if (!$idColumn) {
        respond_json(500, array("error" => "Geen id-kolom gevonden in `$table`."));
    }

    $statement = $pdo->prepare("SELECT `$idColumn` FROM `$table` WHERE `$idColumn` = ? LIMIT 1");
    $statement->execute(array($photoId));
    if (!$statement->fetch()) {
        respond_json(404, array("error" => "Foto niet gevonden."));
    }

    $statement = $pdo->prepare("DELETE FROM `$table` WHERE `$idColumn` = ?");
    $statement->execute(array($photoId));

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
    $photoId = isset($data["id"]) ? trim((string) $data["id"]) : "";
    if ($photoId === "") {
        respond_json(422, array("error" => "Foto-id ontbreekt."));
    }

    delete_photo($pdo, $table, $columns, $imageColumn, $photoId);
}

if ($action !== "create") {
    respond_json(400, array("error" => "Onbekende foto-actie."));
}

$photo = isset($data["photo"]) && is_array($data["photo"]) ? $data["photo"] : array();
$title = isset($photo["title"]) ? trim((string) $photo["title"]) : "";
$description = isset($photo["description"]) ? trim((string) $photo["description"]) : "";
$author = isset($photo["author"]) ? trim((string) $photo["author"]) : "";
$ownerId = isset($photo["ownerId"]) ? trim((string) $photo["ownerId"]) : "";
$createdAt = isset($photo["createdAt"]) && is_numeric($photo["createdAt"]) ? (int) $photo["createdAt"] : time() * 1000;
$imageData = validate_data_url_image(isset($photo["image"]) ? $photo["image"] : "");

if ($title === "") {
    respond_json(422, array("error" => "Titel is verplicht."));
}

$insert = array($imageColumn => $imageData);

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
    respond_json(500, array("error" => "Foto kon niet in de database worden opgeslagen: " . $exception->getMessage()));
}

$idColumn = first_column_name($columns, array("id", "foto_id", "photo_id"));
$newId = $idColumn ? $pdo->lastInsertId() : $imageData;

respond_json(201, array(
    "photo" => array(
        "id" => (string) $newId,
        "author" => $author,
        "ownerId" => $ownerId,
        "title" => $title,
        "description" => $description,
        "createdAt" => $createdAt,
        "image" => $imageData,
    ),
));
