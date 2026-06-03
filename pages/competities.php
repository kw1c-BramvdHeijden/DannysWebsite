<?php require_once __DIR__ . "/../includes/header.php"; ?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <title>Boules Competities | Competities</title>
    <link rel="stylesheet" href="../css/variables.css">
    <link rel="stylesheet" href="../css/index.css">
</head>
<body data-competitions-href="#competities">
    <main class="page-shell">
        <?php render_site_header("competitions", false); ?>

        <section class="hero-section" id="competities">
            <div class="hero-copy">
                <h1>
                    <span>BEKIJK.</span>
                    <span>PLAN.</span>
                    <span class="accent">SPEEL.</span>
                </h1>
                <p>
                    Alle geplande competities staan hier overzichtelijk bij elkaar.
                    Bekijk de datum, het type competitie en open de details.
                </p>
            </div>

            <div class="hero-illustration">
                <img class="hero-image" src="../images/hero-scene.png" data-i18n-alt="hero.imageAlt" alt="Jeu de boules speler tijdens een wedstrijd op het plein">
            </div>
        </section>

        <section class="competitions-panel">
            <div class="panel-heading">
                <h2 data-i18n="competitions.heading">AANKOMENDE COMPETITIES</h2>
                <div class="panel-actions">
                    <span class="panel-admin-indicator" data-competition-admin-indicator hidden>
                        <i class="fa-solid fa-pen-to-square"></i>
                        <span data-i18n="competitions.adminMode">Admin-modus: beheer aankomende competities</span>
                    </span>
                </div>
            </div>

            <div class="competition-cards" data-competition-grid></div>

            <div class="challenge-banner">
                <div class="challenge-copy">
                    <span class="challenge-boules" aria-hidden="true"></span>
                    <div>
                        <h3 data-i18n="challenge.title">Klaar om de uitdaging aan te gaan?</h3>
                        <p data-i18n="challenge.description">Meld je aan en laat zien wat je in huis hebt!</p>
                    </div>
                </div>
                <div class="challenge-actions">
                    <button type="button" class="button button-secondary" data-challenge-signup data-auth-open="signup"><span data-i18n="challenge.cta">Meld je aan</span> <span aria-hidden="true">-&gt;</span></button>
                    <button type="button" class="button button-outline panel-admin-button" data-competition-create hidden>
                        <i class="fa-solid fa-plus"></i>
                        <span data-i18n="competitions.add">Competitie toevoegen</span>
                    </button>
                </div>
            </div>
        </section>
    </main>

    <!-- Auth modal is rendered by includes/header.php. -->
    <div class="competition-modal" data-competition-modal hidden>
        <div class="competition-modal-backdrop" data-competition-cancel></div>
        <div class="competition-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="competition-modal-title">
            <button type="button" class="competition-modal-close" data-competition-cancel data-i18n-aria-label="competitions.form.closeLabel" aria-label="Sluit competitievenster">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <form class="competition-form" data-competition-form>
                <p class="photo-kicker" data-competition-form-kicker data-i18n="competitions.form.addKicker">COMPETITIE TOEVOEGEN</p>
                <h2 id="competition-modal-title" data-competition-form-title data-i18n="competitions.form.addTitle">Maak een aankomende competitie aan</h2>
                <p class="upload-description" data-competition-form-description data-i18n="competitions.form.description">Vul de belangrijkste informatie in zodat spelers zich kunnen voorbereiden.</p>

                <label class="upload-label">
                    <span data-i18n="competitions.form.name">Naam</span>
                    <input type="text" data-competition-name data-i18n-placeholder="competitions.form.namePlaceholder" placeholder="Bijvoorbeeld: Voorjaars Toernooi">
                </label>

                <label class="upload-label">
                    <span data-i18n="competitions.form.type">Type</span>
                    <input type="text" data-competition-type data-i18n-placeholder="competitions.form.typePlaceholder" placeholder="Bijvoorbeeld: Doublette | Vrije inschrijving">
                </label>

                <label class="upload-label">
                    <span data-i18n="competitions.form.date">Startdatum</span>
                    <input type="date" data-competition-date>
                </label>

                <label class="upload-label">
                    <span data-i18n="competitions.form.style">Accentkleur</span>
                    <select class="competition-select" data-competition-tone>
                        <option value="green" data-i18n="competitions.form.styleGreen">Groen</option>
                        <option value="yellow" data-i18n="competitions.form.styleYellow">Geel</option>
                        <option value="red" data-i18n="competitions.form.styleRed">Rood</option>
                        <option value="olive" data-i18n="competitions.form.styleOlive">Olijf</option>
                    </select>
                </label>

                <div class="upload-actions">
                    <button type="button" class="button button-ghost" data-competition-cancel data-i18n="competitions.form.cancel">Annuleren</button>
                    <button type="submit" class="button button-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span data-competition-submit-label data-i18n="competitions.form.save">Competitie opslaan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script type="module" src="../scripts/index.js"></script>
</body>
</html>
