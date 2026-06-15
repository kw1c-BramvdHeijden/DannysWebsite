<?php

session_start();

/* ===== SESSION ===== */

if (!isset($_SESSION['gekozen_datums'])) {
    $_SESSION['gekozen_datums'] = [];
}

function is_valid_calendar_date_input($datum)
{
    return is_string($datum) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $datum);
}

/* ===== DATUMS OPSLAAN ===== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        "Location: kalender.php?maand=$redirectMaand&jaar=$redirectJaar"
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
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/kalender.css">
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
