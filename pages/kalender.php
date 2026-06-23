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

    // Filter de array met daadwerkelijk aangevinkte datums die via POST zijn meegestuurd op geldigheid.
    $geselecteerd = array_filter(
        $_POST['datums'] ?? [], // Als 'datums' niet bestaat in POST (bijv. alles uitgevinkt), gebruik dan een lege array.
        'is_valid_calendar_date_input' // Gebruik de validatiefunctie als filter.
    );

    // Verwijder alle datums van de huidige getoonde maand uit de sessie om verouderde vinkjes weg te halen.
    $_SESSION['gekozen_datums'] = array_diff(
        $_SESSION['gekozen_datums'], // De huidige lijst met opgeslagen datums.
        $maandDatums // De lijst met alle datums die bij deze maand horen.
    );

    // Voeg de nieuw geselecteerde vinkjes samen met de overgebleven sessiedatums en verwijder eventuele dubbelingen.
    $_SESSION['gekozen_datums'] = array_unique(
        array_merge($_SESSION['gekozen_datums'], $geselecteerd) // Voeg de arrays samen.
    );

    calendar_update_wedstrijd_datum($wedstrijdId, array_values($geselecteerd));
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
    <link rel="stylesheet" href="../css/base.css">
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
                        ?>



                        <input
                                type="hidden"
                                name="maand_datums[]"
                                value="<?php echo htmlspecialchars($datum); ?>"
                        >



                        <label class="dag<?php echo !empty($dagCompetities) ? ' has-event' : ''; ?><?php echo $checked ? ' selected' : ''; ?><?php echo $isVandaag ? ' vandaag' : ''; ?><?php echo $isVerleden ? ' verleden' : ''; ?>">

                            <input
                                    type="checkbox"
                                    name="datums[]"
                                    value="<?php echo htmlspecialchars($datum); ?>"
                                <?php echo $checked ? 'checked' : ''; ?>
                                <?php echo ($isVandaag || $isVerleden) ? 'disabled' : ''; ?>
                                <?php echo $isVandaag ? 'checked' : ''; ?>
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



    </section>
</main>



<script type="module" src="../scripts/index.js"></script>
</body>
</html>
