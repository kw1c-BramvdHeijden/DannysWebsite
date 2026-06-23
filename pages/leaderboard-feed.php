<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/leaderboard-data.php";

header("Content-Type: application/json; charset=utf-8");

function leaderboard_feed_respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
    exit;
}

function leaderboard_feed_json_body(): array
{
    $data = json_decode(file_get_contents("php://input"), true);

    return is_array($data) ? $data : array();
}

function leaderboard_feed_payload($pdo, string $sortDirection = "desc"): array
{
    $rows = $pdo instanceof PDO ? leaderboard_fetch_team_rows($pdo, $sortDirection) : array();

    return array(
        "leaderboard" => leaderboard_bootstrap_entries($rows),
        "rows" => $rows,
        "error" => isset($GLOBALS["databaseConnectionError"]) ? $GLOBALS["databaseConnectionError"] : "",
        "loggedIn" => leaderboard_current_user_id() !== "",
        "role" => $pdo instanceof PDO && leaderboard_is_admin($pdo) ? "admin" : "player",
    );
}

function leaderboard_delete_team($pdo, array $data): void
{
    if (!$pdo instanceof PDO) {
        leaderboard_feed_respond(500, array("error" => "Databaseverbinding ontbreekt."));
    }

    if (!leaderboard_is_admin($pdo)) {
        leaderboard_feed_respond(403, array("error" => "Alleen admins mogen leaderboard teams verwijderen."));
    }

    $teamId = isset($data["teamId"]) ? trim((string) $data["teamId"]) : "";
    if ($teamId === "" || !is_numeric($teamId)) {
        leaderboard_feed_respond(422, array("error" => "Team-id ontbreekt."));
    }

    $table = leaderboard_first_existing_table($pdo, array("teams", "Teams"));
    if (!$table) {
        leaderboard_feed_respond(500, array("error" => "Tabel teams ontbreekt."));
    }

    $columns = leaderboard_table_columns($pdo, $table);
    $idColumn = leaderboard_first_existing_column($columns, array("team_id", "teamid", "id"));
    if (!$idColumn) {
        leaderboard_feed_respond(500, array("error" => "Geen team-id kolom gevonden."));
    }

    try {
        $statement = $pdo->prepare("DELETE FROM `$table` WHERE `$idColumn` = ?");
        $statement->execute(array($teamId));
    } catch (Throwable $exception) {
        leaderboard_feed_respond(500, array("error" => "Team kon niet worden verwijderd."));
    }

    $payload = leaderboard_feed_payload($pdo, isset($data["sort"]) ? (string) $data["sort"] : "desc");
    $payload["deleted"] = true;
    $payload["teamId"] = $teamId;
    leaderboard_feed_respond(200, $payload);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = leaderboard_feed_json_body();
    $action = isset($data["action"]) ? (string) $data["action"] : "";

    if ($action === "deleteTeam") {
        leaderboard_delete_team($pdo, $data);
    }

    leaderboard_feed_respond(400, array("error" => "Onbekende leaderboard-actie."));
}

$sortDirection = leaderboard_normalize_sort_direction(isset($_GET["sort"]) ? (string) $_GET["sort"] : "desc");
$payload = leaderboard_feed_payload($pdo, $sortDirection);
$json = json_encode(
    $payload,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);

echo $json ?: "{\"leaderboard\":[],\"rows\":[]}";
