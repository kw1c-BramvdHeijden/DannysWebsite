<?php
session_start();

/* ===== SESSION ===== */

if (!isset($_SESSION['gekozen_datums'])) {
    $_SESSION['gekozen_datums'] = [];
}

/* ===== DATUMS OPSLAAN ===== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $maandDatums = $_POST['maand_datums'] ?? [];
    $geselecteerd = $_POST['datums'] ?? [];

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
        : date('m');

    $redirectJaar = isset($_GET['jaar'])
        ? (int)$_GET['jaar']
        : date('Y');

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
    12 => 'December'
];

$maandNaam = $maanden[$maand];

/* ===== KALENDER ===== */

$eersteDag = date('N', strtotime("$jaar-$maand-01"));

$aantalDagen = cal_days_in_month(
    CAL_GREGORIAN,
    $maand,
    $jaar
);

?>

<!DOCTYPE html>
<html lang="nl">

<head>

    <meta charset="UTF-8">

    <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
    >

    <title>Kalender</title>

    <link rel="stylesheet" href="../css/kalender.css">

</head>

<body>

<div class="kalender-wrapper">

    <div class="kalender">
    <!-- HEADER -->

    <div class="header">

        <a
                class="arrow"
                href="?maand=<?php echo $vorigeMaand; ?>&jaar=<?php echo $vorigeJaar; ?>"
        >
            ‹
        </a>

        <h1>
            <?php echo $maandNaam . ' ' . $jaar; ?>
        </h1>

        <a
                class="arrow"
                href="?maand=<?php echo $volgendeMaand; ?>&jaar=<?php echo $volgendeJaar; ?>"
        >
            ›
        </a>

    </div>

    <!-- KALENDER -->

    <form method="POST" class="kalender-form">

        <div class="grid">

            <!-- WEEKDAGEN -->

            <div class="weekdag">Ma</div>
            <div class="weekdag">Di</div>
            <div class="weekdag">Wo</div>
            <div class="weekdag">Do</div>
            <div class="weekdag">Vr</div>
            <div class="weekdag">Za</div>
            <div class="weekdag">Zo</div>

            <!-- LEGE CELLEN -->

            <?php for ($i = 1; $i < $eersteDag; $i++): ?>
                <div class="leeg"></div>
            <?php endfor; ?>

            <!-- DAGEN -->

            <?php for ($dag = 1; $dag <= $aantalDagen; $dag++): ?>

                <?php

                $datum = sprintf(
                    '%04d-%02d-%02d',
                    $jaar,
                    $maand,
                    $dag
                );

                $isVandaag = $datum === date('Y-m-d');

                $isVerleden =
                    strtotime($datum)
                    < strtotime(date('Y-m-d'));

                $checked = in_array(
                    $datum,
                    $_SESSION['gekozen_datums']
                );

                ?>

                <input
                        type="hidden"
                        name="maand_datums[]"
                        value="<?php echo $datum; ?>"
                >

                <label class="dag
                    <?php echo $checked ? ' selected' : ''; ?>
                    <?php echo $isVandaag ? ' vandaag' : ''; ?>
                    <?php echo $isVerleden ? ' verleden' : ''; ?>
                ">

                    <input
                            type="checkbox"

                            name="datums[]"

                            value="<?php echo $datum; ?>"

                        <?php echo $checked ? 'checked' : ''; ?>

                        <?php echo ($isVandaag || $isVerleden)
                            ? 'disabled'
                            : ''; ?>

                        <?php echo $isVandaag
                            ? 'checked'
                            : ''; ?>

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

    <!-- SELECTED DATUMS -->

    <div class="sidebar">

        <h2>Geselecteerde datums</h2>

        <?php if (!empty($_SESSION['gekozen_datums'])): ?>

            <ul>

                <?php
                sort($_SESSION['gekozen_datums']);

                foreach ($_SESSION['gekozen_datums'] as $datum):
                    ?>

                    <li>

                        <span>
                            <?php echo date('d-m-Y', strtotime($datum)); ?>
                        </span>

                        <a
                                class="remove-btn"

                                href="?maand=<?php echo $maand; ?>
        &jaar=<?php echo $jaar; ?>
        &remove=<?php echo $datum; ?>"
                        >
                            ✕
                        </a>

                    </li>

                <?php endforeach; ?>

            </ul>

        <?php else: ?>

            <p class="geen-datums">
                Geen datums geselecteerd
            </p>

        <?php endif; ?>

    </div>

</div>

</body>
</html>