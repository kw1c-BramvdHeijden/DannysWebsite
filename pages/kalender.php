<?php

// Start een nieuwe PHP-sessie of hervat een bestaande om gegevens (zoals geselecteerde datums) te onthouden tussen pagina-aanvragen.
session_start();

require_once __DIR__ . "/../includes/db.php";
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
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/bootstrap-data.php";

/* ===== SESSION ===== */



// Controleer of de sessievariabele 'gekozen_datums' nog niet bestaat.
if (!isset($_SESSION['gekozen_datums'])) {
    // Als deze niet bestaat, maak er dan een lege array van om fouten later te voorkomen.
    $_SESSION['gekozen_datums'] = [];
}



// Definieer een functie om te controleren of een invoerdatum het juiste kalenderformaat (YYYY-MM-DD) heeft.
function is_valid_calendar_date_input($datum)
{
    // Controleer of de invoer een tekstreeks (string) is én voldoet aan de reguliere expressie voor 4 cijfers, een streepje, 2 cijfers, een streepje, en weer 2 cijfers.
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



// Controleer of de pagina is geladen via een POST-verzoek (dus of het formulier is verzonden).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Filter de array met datums van de huidige maand die via POST zijn meegestuurd, zodat alleen geldige datums overblijven.
    $maandDatums = array_filter(
        $_POST['maand_datums'] ?? [], // Als 'maand_datums' niet bestaat in POST, gebruik dan een lege array.
        'is_valid_calendar_date_input' // Gebruik de eerder gemaakte validatiefunctie als filter.
    );
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

    // Filter de array met daadwerkelijk aangevinkte datums die via POST zijn meegestuurd op geldigheid.
    $geselecteerd = array_filter(
        $_POST['datums'] ?? [], // Als 'datums' niet bestaat in POST (bijv. alles uitgevinkt), gebruik dan een lege array.
        'is_valid_calendar_date_input' // Gebruik de validatiefunctie als filter.
    );
        $geselecteerd = array_filter(
            $_POST['datums'] ?? [],
            'is_valid_calendar_date_input'
        );

    // Verwijder alle datums van de huidige getoonde maand uit de sessie om verouderde vinkjes weg te halen.
    $_SESSION['gekozen_datums'] = array_diff(
        $_SESSION['gekozen_datums'], // De huidige lijst met opgeslagen datums.
        $maandDatums // De lijst met alle datums die bij deze maand horen.
    );
        $_SESSION['gekozen_datums'] = array_diff(
            $_SESSION['gekozen_datums'],
            $maandDatums
        );

    // Voeg de nieuw geselecteerde vinkjes samen met de overgebleven sessiedatums en verwijder eventuele dubbelingen.
    $_SESSION['gekozen_datums'] = array_unique(
        array_merge($_SESSION['gekozen_datums'], $geselecteerd) // Voeg de arrays samen.
    );

    calendar_update_wedstrijd_datum($wedstrijdId, array_values($geselecteerd));
        $_SESSION['gekozen_datums'] = array_unique(
            array_merge($_SESSION['gekozen_datums'], $geselecteerd)
        );
    }
}



/* ===== DATUM VERWIJDEREN ===== */



// Controleer of er een 'remove' parameter in de URL (GET-variabele) aanwezig is om een datum te wissen via de sidebar.
if (isset($_GET['remove'])) {
    // Sla de te verwijderen datum op in een variabele.
    $removeDatum = $_GET['remove'];

    // Verwijder deze specifieke datum uit de sessie-array met behulp van array_diff.
    $_SESSION['gekozen_datums'] = array_diff(
        $_SESSION['gekozen_datums'], // De huidige lijst.
        [$removeDatum] // De specifieke datum om te wissen in een nieuwe array.
    );

    // Bepaal naar welke maand de pagina moet teruglinken (gebruik de GET-waarde, of de huidige maand als back-up).
    $redirectMaand = isset($_GET['maand'])
        ? (int)$_GET['maand'] // Zet om naar een heel getal (integer).
        : (int)date('m'); // Huidige maand van het systeem.

    // Bepaal naar welk jaar de pagina moet teruglinken (gebruik de GET-waarde, of het huidige jaar als back-up).
    $redirectJaar = isset($_GET['jaar'])
        ? (int)$_GET['jaar'] // Zet om naar een heel getal (integer).
        : (int)date('Y'); // Huidig jaar van het systeem.

    // Controleer of de maandwaarde buiten het geldige bereik (1 t/m 12) valt.
    if ($redirectMaand < 1 || $redirectMaand > 12) {
        // Indien ongeldig, val terug op de huidige maand.
        $redirectMaand = (int)date('m');
    }

    // Controleer of het jaar buiten een realistisch bereik (1900 t/m 2100) valt.
    if ($redirectJaar < 1900 || $redirectJaar > 2100) {
        // Indien ongeldig, val terug op het huidige jaar.
        $redirectJaar = (int)date('Y');
    }

    // Stuur een HTTP-header om de browser te herladen naar de kalenderpagina met de juiste maand en jaar, zodat de URL schoon blijft.
    header(
        "Location: kalender.php" . calendar_url($redirectMaand, $redirectJaar, $wedstrijdId !== "" ? array("wedstrijd_id" => $wedstrijdId) : array())
    );

    // Stop de uitvoering van het PHP-script direct na het verzenden van de redirect-header.
    exit;
}



/* ===== HUIDIGE MAAND ===== */



// Bepaal het weer te geven jaar op basis van de URL (GET) of kies het huidige jaar als er geen waarde is meegegeven.
$jaar = isset($_GET['jaar'])
    ? (int)$_GET['jaar'] // Zet om naar een integer voor de veiligheid.
    : (int)date('Y'); // Huidig jaar.



// Bepaal de weer te geven maand op basis van de URL (GET) of kies de huidige maand als er geen waarde is meegegeven.
$maand = isset($_GET['maand'])
    ? (int)$_GET['maand'] // Zet om naar een integer voor de veiligheid.
    : (int)date('m'); // Huidige maand.



// Controleer of de gevraagde maand buiten het geldige bereik (1 t/m 12) valt.
if ($maand < 1 || $maand > 12) {
    // Reset naar de huidige maand bij een ongeldige waarde.
    $maand = (int)date('m');
}



// Controleer of het gevraagde jaar buiten het geldige bereik (1900 t/m 2100) valt.
if ($jaar < 1900 || $jaar > 2100) {
    // Reset naar het huidige jaar bij een ongeldige waarde.
    $jaar = (int)date('Y');
}



/* ===== VORIGE / VOLGENDE ===== */



// Bereken de waarde van de vorige maand door 1 van de huidige maand af te trekken.
$vorigeMaand = $maand - 1;
// Het jaar voor de vorige maand is standaard hetzelfde jaar.
$vorigeJaar = $jaar;



// Als de vorige maand kleiner is dan 1 (dus 0 wordt na januari), gaan we terug naar december van het vorige jaar.
if ($vorigeMaand < 1) {
    $vorigeMaand = 12; // Zet de maand op december.
    $vorigeJaar--; // Trek 1 jaar af van het huidige jaar.
}



// Bereken de waarde van de volgende maand door 1 bij de huidige maand op te tellen.
$volgendeMaand = $maand + 1;
// Het jaar voor de volgende maand is standaard hetzelfde jaar.
$volgendeJaar = $jaar;



// Als de volgende maand groter is dan 12 (dus 13 wordt na december), gaan we naar januari van het volgende jaar.
if ($volgendeMaand > 12) {
    $volgendeMaand = 1; // Zet de maand op januari.
    $volgendeJaar++; // Tel 1 jaar op bij het huidige jaar.
}



/* ===== MAANDNAMEN ===== */



// Maak een associatieve array aan om de numerieke maandwaarden te koppelen aan de Nederlandse maandnamen.
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



// Haal de juiste Nederlandse naam op voor de geselecteerde maand.
$maandNaam = $maanden[$maand];
// Sla de datum van vandaag op in het YYYY-MM-DD formaat om later te vergelijken.
$vandaag = date('Y-m-d');



/* ===== KALENDER ===== */



// Bereken op welke dag van de week de eerste dag van de geselecteerde maand valt (1 = maandag, 7 = zondag).
$eersteDag = date('N', strtotime("$jaar-$maand-01"));



// Bereken het totaal aantal dagen dat de specifieke maand in dat specifieke jaar heeft (bijv. 28, 29, 30 of 31 dagen).
$aantalDagen = cal_days_in_month(
    CAL_GREGORIAN, // Gebruik de standaard Gregoriaanse kalender.
    $maand,
    $jaar
);



// Laad het externe PHP-bestand in dat de header van de website bevat/regelt.
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
    <?php
    // Roep een PHP-functie aan (gedefinieerd in header.php) om de website-header te renderen met specifieke parameters.
    render_site_header("competitions", false);
    ?>



    <section class="kalender-wrapper" id="competities">
        <div class="kalender">
            <div class="header">
                <a
                    class="arrow"
                    href="<?php echo htmlspecialchars(calendar_url($vorigeMaand, $vorigeJaar, $wedstrijdId !== "" ? array("wedstrijd_id" => $wedstrijdId) : array())); ?>"
                    aria-label="Vorige maand"
                >
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </a>



                <h1>
                    <?php echo htmlspecialchars($maandNaam . ' ' . $jaar); ?>
                </h1>



                <a
                    class="arrow"
                    href="<?php echo htmlspecialchars(calendar_url($volgendeMaand, $volgendeJaar, $wedstrijdId !== "" ? array("wedstrijd_id" => $wedstrijdId) : array())); ?>"
                    aria-label="Volgende maand"
                >
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>
            </div>



            <form method="POST" class="kalender-form">
                <?php if ($wedstrijdId !== ""): ?>
                    <input type="hidden" name="wedstrijd_id" value="<?php echo htmlspecialchars($wedstrijdId); ?>">
                <?php endif; ?>

                <?php if ($isHerplanMode): ?>
                    <input type="hidden" name="reschedule_match_id" value="<?php echo htmlspecialchars($herplanMatchId); ?>">
                <?php endif; ?>

                <div class="grid">
                    <div class="weekdag">Ma</div>
                    <div class="weekdag">Di</div>
                    <div class="weekdag">Wo</div>
                    <div class="weekdag">Do</div>
                    <div class="weekdag">Vr</div>
                    <div class="weekdag">Za</div>
                    <div class="weekdag">Zo</div>



                    <?php
                    // Loop om lege cellen te genereren vóór de eerste dag van de maand, zodat de 1e van de maand op de juiste weekdag start.
                    for ($i = 1; $i < $eersteDag; $i++):
                        ?>
                        <div class="leeg"></div>
                    <?php endfor; ?>



                    <?php
                    // Loop om door alle genummerde dagen van de huidige maand te lopen (bijv. dag 1 t/m 31).
                    for ($dag = 1; $dag <= $aantalDagen; $dag++):
                        ?>
                        <?php
                        // Formatteer de huidige dag om naar een volwaardige YYYY-MM-DD string (bijv. 2024-05-01).
                        $datum = sprintf(
                            '%04d-%02d-%02d',
                            $jaar,
                            $maand,
                            $dag
                        );



                        // Controleer of deze specifieke datum gelijk is aan vandaag.
                        $isVandaag = $datum === $vandaag;
                        // Controleer of de datum in het verleden ligt door de timestamps te vergelijken.
                        $isVerleden = strtotime($datum) < strtotime($vandaag);
                        // Controleer of deze datum voorkomt in de lijst met gekozen datums in de sessie.
                        $checked = in_array(
                            $datum,
                            $_SESSION['gekozen_datums']
                        );
                        $dagCompetities = $competities[$datum] ?? [];
                        $checked = $isHerplanMode
                            ? $datum === $herplanCurrentDate
                            : in_array(
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
                                <?php echo $checked ? 'checked' : ''; // Voeg 'checked' toe als de datum in de sessie staat ?>
                                <?php echo ($isVandaag || $isVerleden) ? 'disabled' : ''; // Schakel de checkbox uit als het vandaag of in het verleden is ?>
                                <?php echo $isVandaag ? 'checked' : ''; // Vink vandaag automatisch aan (visueel) ?>
                                    onchange="this.form.submit()" >

                                type="checkbox"
                                name="datums[]"
                                value="<?php echo htmlspecialchars($datum); ?>"
                                <?php echo $checked ? 'checked' : ''; ?>
                                <?php echo ($isVandaag || $isVerleden) ? 'disabled' : ''; ?>
                                <?php echo (!$isHerplanMode && $isVandaag) ? 'checked' : ''; ?>
                                onchange="this.form.submit()"
                            >


                            <span class="day-number">
                                <?php echo $dag; ?>

                            </span>

                            <?php foreach ($dagCompetities as $competitie): ?>
                                <div class="calendar-event">
                                    <?php echo htmlspecialchars($competitie); ?>
                                </div>
                            <?php endforeach; ?>



                        </label>
                    <?php endfor; ?>
                </div>
            </form>
        </div>



        <aside class="sidebar">
            <h2><?php echo $isHerplanMode ? "Wedstrijd herplannen" : "Geselecteerde datums"; ?></h2>

            <?php if ($isHerplanMode): ?>
                <?php if ($herplanError !== ""): ?>
                    <p class="geen-datums"><?php echo htmlspecialchars($herplanError); ?></p>
                <?php elseif ($herplanMessage !== ""): ?>
                    <p class="geen-datums"><?php echo htmlspecialchars($herplanMessage); ?></p>
                <?php else: ?>
                    <p class="geen-datums">
                        Kies een nieuwe datum. Het andere team moet deze datum daarna goedkeuren.
                    </p>
                <?php endif; ?>

                <?php if ($herplanCurrentDate !== ""): ?>
                    <ul>
                        <li>
                            <span>Huidige datum: <?php echo htmlspecialchars(date('d-m-Y', strtotime($herplanCurrentDate))); ?></span>
                        </li>
                    </ul>
                <?php endif; ?>
            <?php elseif (!empty($_SESSION['gekozen_datums'])): ?>


            <?php
            // Controleer of er datums zijn opgeslagen in de sessie.
            if (!empty($_SESSION['gekozen_datums'])):
                ?>
                <ul>
                    <?php
                    // Sorteer de geselecteerde datums chronologisch (van oud naar nieuw).
                    sort($_SESSION['gekozen_datums']);



                    // Loop door alle opgeslagen datums heen om ze afzonderlijk te tonen.
                    foreach ($_SESSION['gekozen_datums'] as $datum):
                        ?>
                        <li>
                            <span>
                                <?php echo htmlspecialchars(date('d-m-Y', strtotime($datum))); ?>
                            </span>



                            <a
                                class="remove-btn"
                                href="<?php echo htmlspecialchars(calendar_url($maand, $jaar, array_merge(array("remove" => $datum), $wedstrijdId !== "" ? array("wedstrijd_id" => $wedstrijdId) : array()))); ?>"
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
