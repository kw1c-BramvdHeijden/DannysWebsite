<?php
require_once __DIR__ . "/../includes/header.php";
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <title>Boules Competities | Scoreboard</title>
    <link rel="stylesheet" href="../css/variables.css">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/scoreboard.css">
</head>
<body data-scoreboard-api="../api/scoreboard.php">
<main class="page-shell scoreboard-page">
    <?php render_site_header("scoreboard", false); ?>

    <section class="hero-section scoreboard-hero" id="scoreboard">
        <div class="hero-copy">
            <h1>
                <span data-i18n="scoreboard.hero.line1">KIES.</span>
                <span data-i18n="scoreboard.hero.line2">SCOOR.</span>
                <span class="accent" data-i18n="scoreboard.hero.line3">WIN.</span>
            </h1>
            <p data-i18n="scoreboard.hero.description">
                Selecteer je wedstrijd en werk de jeu de boules score bij tot de winnende 13 punten.
            </p>
        </div>

        <div class="scoreboard-hero-board" aria-hidden="true">
            <div class="scoreboard-hero-top">
                <span data-scoreboard-hero-title>Jeu de Dabs</span>
                <strong data-scoreboard-hero-target>13</strong>
            </div>
            <div class="scoreboard-hero-row">
                <span data-scoreboard-hero-team="0">Team A</span>
                <strong data-scoreboard-hero-score="0">09</strong>
            </div>
            <div class="scoreboard-hero-row is-leading">
                <span data-scoreboard-hero-team="1">Team B</span>
                <strong data-scoreboard-hero-score="1">13</strong>
            </div>
            <div class="scoreboard-hero-boules">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </section>

    <section class="scoreboard-layout" data-i18n-aria-label="scoreboard.layoutLabel" aria-label="Scoreboard wedstrijden">
        <section class="scoreboard-panel scoreboard-match-panel" aria-labelledby="scoreboard-matches-title">
            <div class="panel-heading scoreboard-panel-heading">
                <div>
                    <p class="photo-kicker" data-i18n="scoreboard.matches.kicker">WEDSTRIJDEN</p>
                    <h2 id="scoreboard-matches-title" data-i18n="scoreboard.matches.title">Kies je wedstrijd</h2>
                </div>
                <button class="button button-ghost scoreboard-refresh" type="button" data-scoreboard-refresh>
                    <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                    <span data-i18n="scoreboard.refresh">Ververs</span>
                </button>
            </div>

            <p class="scoreboard-status" data-scoreboard-list-status aria-live="polite" data-i18n="scoreboard.loading">Wedstrijden laden...</p>
            <div class="scoreboard-match-list" data-scoreboard-matches></div>
        </section>

        <section class="scoreboard-panel scoreboard-live-panel" aria-labelledby="scoreboard-live-title">
            <div class="scoreboard-empty-detail" data-scoreboard-empty>
                <span class="scoreboard-empty-icon" aria-hidden="true">
                    <i class="fa-solid fa-clipboard-list"></i>
                </span>
                <h2 data-i18n="scoreboard.empty.title">Geen wedstrijd geselecteerd</h2>
                <p data-i18n="scoreboard.empty.description">Kies links een wedstrijd om de score te bekijken.</p>
            </div>

            <div class="scoreboard-detail" data-scoreboard-detail hidden>
                <div class="scoreboard-detail-head">
                    <div>
                        <p class="photo-kicker" data-scoreboard-detail-kicker>WEDSTRIJD</p>
                        <h2 id="scoreboard-live-title" data-scoreboard-detail-title>Scoreboard</h2>
                        <p class="scoreboard-detail-meta" data-scoreboard-detail-meta></p>
                    </div>
                    <span class="scoreboard-state-chip" data-scoreboard-state-chip>Live</span>
                </div>

                <div class="scoreboard-teams" data-scoreboard-teams></div>

                <div class="scoreboard-actions">
                    <button class="button button-ghost" type="button" data-scoreboard-minus-round>
                        <i class="fa-solid fa-minus" aria-hidden="true"></i>
                        <span data-i18n="scoreboard.actions.roundBack">Ronde terug</span>
                    </button>
                    <button class="button button-primary" type="button" data-scoreboard-save>
                        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                        <span data-i18n="scoreboard.actions.save">Score opslaan</span>
                    </button>
                </div>

                <p class="scoreboard-feedback" data-scoreboard-feedback aria-live="polite"></p>
            </div>
        </section>
    </section>
</main>

<script type="module" src="../scripts/index.js"></script>
<script type="module" src="../scripts/scoreboard.js"></script>
</body>
</html>
