<?php

session_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/bootstrap-data.php";

$competities = [];

$stmt = $pdo->prepare("
    SELECT name, start_date, end_date
    FROM tournaments
    WHERE status = 'geaccepteert'
 
");

$stmt->execute();

$resultaten = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($resultaten as $row) {
    $datum = date('Y-m-d', strtotime($row['start_date']));
    $competities[$datum][] = $row['name'];
}



foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $datum = date('Y-m-d', strtotime($row['start_date']));
    $competities[$datum][] = $row['name'];
}
/* ===== SESSION ===== */

if (!isset($_SESSION['gekozen_datums'])) {
    $_SESSION['gekozen_datums'] = [];
}

function is_valid_calendar_date_input($datum)
{
    return is_string($datum) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $datum);
}

function kalender_reschedule_file()
{
    return __DIR__ . "/../data/reschedule_requests.json";
}

function kalender_read_reschedule_requests()
{
    $file = kalender_reschedule_file();
    if (!is_file($file)) {
        return [];
    }

    $data = json_decode((string) file_get_contents($file), true);

    return is_array($data) ? $data : [];
}

function kalender_write_reschedule_requests(array $requests)
{
    $file = kalender_reschedule_file();
    $directory = dirname($file);
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    file_put_contents($file, json_encode($requests, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
}

function kalender_current_user_id()
{
    $userId = isset($_SESSION["user_id"]) ? trim((string) $_SESSION["user_id"]) : "";

    return $userId !== "" && is_numeric($userId) ? $userId : "";
}

function kalender_match_teams($pdo, $wedstrijdId)
{
    if ($wedstrijdId === "" || !boules_table_exists($pdo, "teamwedstrijd")) {
        return [];
    }

    $statement = $pdo->prepare("SELECT `team_id` FROM `teamwedstrijd` WHERE `wedstrijd_id` = ? ORDER BY `teamwedstrijd_id` ASC");
    $statement->execute([$wedstrijdId]);

    return array_map("strval", array_column($statement->fetchAll(), "team_id"));
}

function kalender_user_team_ids($pdo, $userId)
{
    if ($userId === "" || !boules_table_exists($pdo, "team_members")) {
        return [];
    }

    $statement = $pdo->prepare("SELECT `team_id` FROM `team_members` WHERE `user_id` = ?");
    $statement->execute([$userId]);

    return array_map("strval", array_column($statement->fetchAll(), "team_id"));
}

function kalender_match_date($pdo, $wedstrijdId)
{
    if ($wedstrijdId === "" || !boules_table_exists($pdo, "wedstrijden")) {
        return "";
    }

    $statement = $pdo->prepare("SELECT `datum` FROM `wedstrijden` WHERE `wedstrijd_id` = ? LIMIT 1");
    $statement->execute([$wedstrijdId]);

    $date = $statement->fetchColumn();

    return $date ? substr((string) $date, 0, 10) : "";
}

$herplanMatchId = isset($_GET["wedstrijd_id"]) && is_numeric($_GET["wedstrijd_id"]) ? (string) $_GET["wedstrijd_id"] : "";
$isHerplanMode = isset($_GET["herplan"]) && $_GET["herplan"] === "1" && $herplanMatchId !== "";
$herplanMessage = isset($_GET["requested"]) ? "Herplanaanvraag opgeslagen. Het andere team moet de datum nog goedkeuren." : "";
$herplanError = "";
$herplanCurrentDate = $isHerplanMode ? kalender_match_date($pdo, $herplanMatchId) : "";

function calendar_wedstrijd_id()
{
    $wedstrijdId = isset($_POST['wedstrijd_id'])
        ? trim((string)$_POST['wedstrijd_id'])
        : (isset($_GET['wedstrijd_id']) ? trim((string)$_GET['wedstrijd_id']) : "");

    return $wedstrijdId !== "" && is_numeric($wedstrijdId) ? $wedstrijdId : "";
}

function calendar_update_wedstrijd_datum($wedstrijdId, array $datums)
{
    global $pdo;

    if ($wedstrijdId === "" || count($datums) === 0) {
        return;
    }

    $datum = reset($datums);
    if (!is_valid_calendar_date_input($datum)) {
        return;
    }

    $statement = $pdo->prepare("UPDATE `wedstrijden` SET `datum` = ? WHERE `wedstrijd_id` = ?");
    $statement->execute(array($datum . " 00:00:00", $wedstrijdId));
}

function calendar_url($maand, $jaar, $extra = array())
{
    $params = array_merge(array(
        "maand" => (int)$maand,
        "jaar" => (int)$jaar,
    ), $extra);

    return "?" . http_build_query($params);
}

$wedstrijdId = calendar_wedstrijd_id();

/* ===== DATUMS OPSLAAN ===== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedRescheduleMatchId = isset($_POST["reschedule_match_id"]) && is_numeric($_POST["reschedule_match_id"])
        ? (string) $_POST["reschedule_match_id"]
        : "";

    if ($postedRescheduleMatchId !== "") {
        $selectedDates = array_values(array_filter($_POST['datums'] ?? [], 'is_valid_calendar_date_input'));
        $selectedDate = $selectedDates[0] ?? "";
        $userId = kalender_current_user_id();
        $matchTeams = kalender_match_teams($pdo, $postedRescheduleMatchId);
        $userTeamIds = kalender_user_team_ids($pdo, $userId);
        $requesterTeams = array_values(array_intersect($matchTeams, $userTeamIds));

        if ($userId === "" || count($requesterTeams) === 0 || count($matchTeams) < 2) {
            $herplanError = "Je kunt alleen herplannen als je in een van de twee teams zit.";
        } elseif ($selectedDate === "" || strtotime($selectedDate) <= strtotime(date('Y-m-d'))) {
            $herplanError = "Kies een geldige datum na vandaag.";
        } else {
            $requesterTeamId = (string) $requesterTeams[0];
            $approverTeamIds = array_values(array_filter($matchTeams, function ($teamId) use ($requesterTeamId) {
                return (string) $teamId !== $requesterTeamId;
            }));

            $requests = kalender_read_reschedule_requests();
            $requests[$postedRescheduleMatchId] = [
                "matchId" => $postedRescheduleMatchId,
                "proposedDate" => $selectedDate,
                "requesterTeamId" => $requesterTeamId,
                "approverTeamId" => isset($approverTeamIds[0]) ? (string) $approverTeamIds[0] : "",
                "requestedBy" => $userId,
                "requestedAt" => date("Y-m-d H:i:s"),
            ];
            kalender_write_reschedule_requests($requests);

            header("Location: competities.php#competities");
            exit;
        }
    } else {
        $maandDatums = array_filter(
            $_POST['maand_datums'] ?? [],
            'is_valid_calendar_date_input'
        );

        $geselecteerd = array_filter(
            $_POST['datums'] ?? [],
            'is_valid_calendar_date_input'
        );

        $_SESSION['gekozen_datums'] = array_diff(
            $_SESSION['gekozen_datums'],
            $maandDatums
        );

    $_SESSION['gekozen_datums'] = array_unique(
        array_merge($_SESSION['gekozen_datums'], $geselecteerd)
    );

    calendar_update_wedstrijd_datum($wedstrijdId, array_values($geselecteerd));
}

/* ===== DATUM VERWIJDEREN ===== */

if (isset($_GET['remove'])) {
    $removeDatum = $_GET['remove'];

    $_SESSION['gekozen_datums'] = array_diff(
        $_SESSION['gekozen_datums'],
        [$removeDatum]
    );

    $redirectMaand = isset($_GET['maand'])
        ? (int)$_GET['maand']
        : (int)date('m');

    $redirectJaar = isset($_GET['jaar'])
        ? (int)$_GET['jaar']
        : (int)date('Y');

    if ($redirectMaand < 1 || $redirectMaand > 12) {
        $redirectMaand = (int)date('m');
    }

    if ($redirectJaar < 1900 || $redirectJaar > 2100) {
        $redirectJaar = (int)date('Y');
    }

    header(
        "Location: kalender.php" . calendar_url($redirectMaand, $redirectJaar, $wedstrijdId !== "" ? array("wedstrijd_id" => $wedstrijdId) : array())
    );

    exit;
}

/* ===== HUIDIGE MAAND ===== */

$jaar = isset($_GET['jaar'])
    ? (int)$_GET['jaar']
    : (int)date('Y');

$maand = isset($_GET['maand'])
    ? (int)$_GET['maand']
    : (int)date('m');

if ($maand < 1 || $maand > 12) {
    $maand = (int)date('m');
}

if ($jaar < 1900 || $jaar > 2100) {
    $jaar = (int)date('Y');
}

/* ===== VORIGE / VOLGENDE ===== */

$vorigeMaand = $maand - 1;
$vorigeJaar = $jaar;

if ($vorigeMaand < 1) {
    $vorigeMaand = 12;
    $vorigeJaar--;
}

$volgendeMaand = $maand + 1;
$volgendeJaar = $jaar;

if ($volgendeMaand > 12) {
    $volgendeMaand = 1;
    $volgendeJaar++;
}

/* ===== MAANDNAMEN ===== */

$maanden = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maart',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Augustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'December',
];

$maandNaam = $maanden[$maand];
$vandaag = date('Y-m-d');

/* ===== KALENDER ===== */

$eersteDag = date('N', strtotime("$jaar-$maand-01"));

$aantalDagen = cal_days_in_month(
    CAL_GREGORIAN,
    $maand,
    $jaar
);

require_once __DIR__ . "/../includes/header.php";
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boules Competities | Kalender</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/variables.css">
    <link rel="stylesheet" href="../css/competities_fotos_kalender_spelregels.css">
    <link rel="stylesheet" href="../css/kalender.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>

<body data-competitions-href="competities.php">
<main class="page-shell">
    <?php render_site_header("competitions", false); ?>

    <section class="kalender-wrapper" id="competities">
        <div class="kalender">
            <div class="header">
                <a
                    class="arrow"
                    href="?maand=<?php echo $vorigeMaand; ?>&amp;jaar=<?php echo $vorigeJaar; ?>"
                    aria-label="Vorige maand"
                >
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </a>

                <h1>
                    <?php echo htmlspecialchars($maandNaam . ' ' . $jaar); ?>
                </h1>

                <a
                    class="arrow"
                    href="?maand=<?php echo $volgendeMaand; ?>&amp;jaar=<?php echo $volgendeJaar; ?>"
                    aria-label="Volgende maand"
                >
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>
            </div>

            <form method="POST" class="kalender-form">
                <div class="grid">
                    <div class="weekdag">Ma</div>
                    <div class="weekdag">Di</div>
                    <div class="weekdag">Wo</div>
                    <div class="weekdag">Do</div>
                    <div class="weekdag">Vr</div>
                    <div class="weekdag">Za</div>
                    <div class="weekdag">Zo</div>

                    <?php for ($i = 1; $i < $eersteDag; $i++): ?>
                        <div class="leeg"></div>
                    <?php endfor; ?>

                    <?php for ($dag = 1; $dag <= $aantalDagen; $dag++): ?>
                        <?php
                        $datum = sprintf(
                            '%04d-%02d-%02d',
                            $jaar,
                            $maand,
                            $dag
                        );

                        $isVandaag = $datum === $vandaag;
                        $isVerleden = strtotime($datum) < strtotime($vandaag);
                        $checked = in_array(
                            $datum,
                            $_SESSION['gekozen_datums']
                        );
                        ?>

                        <input
                            type="hidden"
                            name="maand_datums[]"
                            value="<?php echo htmlspecialchars($datum); ?>"
                        >

                        <label class="dag<?php echo $checked ? ' selected' : ''; ?><?php echo $isVandaag ? ' vandaag' : ''; ?><?php echo $isVerleden ? ' verleden' : ''; ?>">
                            <input
                                type="checkbox"
                                name="datums[]"
                                value="<?php echo htmlspecialchars($datum); ?>"
                                <?php echo $checked ? 'checked' : ''; ?>
                                <?php echo ($isVandaag || $isVerleden) ? 'disabled' : ''; ?>
                                <?php echo $isVandaag ? 'checked' : ''; ?>
                                onchange="this.form.submit()"
                            >

                            <span>
                                <?php echo $dag; ?>
                            </span>
                        </label>
                    <?php endfor; ?>
                </div>
            </form>
        </div>

        <aside class="sidebar">
            <h2>Geselecteerde datums</h2>

            <?php if (!empty($_SESSION['gekozen_datums'])): ?>
                <ul>
                    <?php
                    sort($_SESSION['gekozen_datums']);

                    foreach ($_SESSION['gekozen_datums'] as $datum):
                        ?>
                        <li>
                            <span>
                                <?php echo htmlspecialchars(date('d-m-Y', strtotime($datum))); ?>
                            </span>

                            <a
                                class="remove-btn"
                                href="?maand=<?php echo $maand; ?>&amp;jaar=<?php echo $jaar; ?>&amp;remove=<?php echo urlencode($datum); ?>"
                                aria-label="Verwijder <?php echo htmlspecialchars(date('d-m-Y', strtotime($datum))); ?>"
                            >
                                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="geen-datums">
                    Geen datums geselecteerd
                </p>
            <?php endif; ?>
        </aside>
    </section>
</main>

<script type="module" src="../scripts/index.js"></script>
</body>
</html>
