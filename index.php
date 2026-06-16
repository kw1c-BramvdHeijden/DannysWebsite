<?php
include_once("includes/header.php");
include_once("includes/leaderboard.php");
?>


<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <title>Boules Competities</title>
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/index.css">
</head>
<body data-competitions-href="pages/competities.php">
    <main class="page-shell">
        <?php render_site_header("home", true); ?>

        <section class="hero-section" id="home">
            <div class="hero-copy">
                <h1>
                    <span data-i18n="hero.line1">SPEEL.</span>
                    <span data-i18n="hero.line2">STRIJD.</span>
                    <span class="accent" data-i18n="hero.line3">GENIET.</span>
                </h1>
                <p data-i18n="hero.description">
                    Doe mee aan spannende jeu de boules competities. Schrijf je in als team
                    of individueel en klim naar de top van het leaderboard.
                </p>

                <!--SIGNUP-->
                <div class="hero-actions">
                    <button type="button" class="button button-primary button-large" data-auth-open="signup">
                        <span data-i18n="hero.ctaPrimary">Meld je aan</span>
                        <span aria-hidden="true">-&gt;</span>
                    </button>
                    <a href="pages/competities.php" class="button button-outline button-large">
                        <span data-i18n="hero.ctaSecondary">Bekijk competities</span>
                        <span aria-hidden="true">-&gt;</span>
                    </a>
                </div>

                <div class="hero-points">
                    <article class="point-card">
                        <i class="fa-solid fa-user-group"></i>
                        <div><h2 data-i18n="hero.point1">Speel als team of individueel</h2></div>
                    </article>
                    <article class="point-card">
                        <i class="fa-solid fa-trophy"></i>
                        <div><h2 data-i18n="hero.point2">Leuke &amp; eerlijke competities</h2></div>
                    </article>
                    <article class="point-card">
                        <i class="fa-solid fa-chess"></i>
                        <div><h2 data-i18n="hero.point3">Klim op het leaderboard</h2></div>
                    </article>
                </div>
            </div>

            <div class="hero-illustration">
                <img class="hero-image" src="images/hero-scene.png" data-i18n-alt="hero.imageAlt" alt="Jeu de boules speler tijdens een wedstrijd op het plein">
            </div>
        </section>

        <section class="content-grid" id="competities">
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
                        <a href="pages/kalender.php" class="button button-outline panel-admin-button" data-competition-create hidden>
                            <i class="fa-solid fa-plus"></i>
                            <span>Competitie toevoegen</span>
                        </a>
                    </div>
                </div>
            </section>

            <?php render_leaderboard_panel(); ?>
        </section>

        <section class="benefits-strip" id="werkt">
            <article class="benefit"><span class="benefit-icon"><i class="fa-solid fa-user-group"></i></span><div><h2 data-i18n="benefits.card1.title">Voor iedereen</h2><p data-i18n="benefits.card1.description">Van beginner tot kampioen, iedereen is welkom.</p></div></article>
            <article class="benefit"><span class="benefit-icon"><i class="fa-solid fa-shield-heart"></i></span><div><h2 data-i18n="benefits.card2.title">Eerlijk &amp; sportief</h2><p data-i18n="benefits.card2.description">Wij zorgen voor eerlijke wedstrijden en duidelijke regels.</p></div></article>
            <article class="benefit"><span class="benefit-icon"><i class="fa-solid fa-comments"></i></span><div><h2 data-i18n="benefits.card3.title">Gezelligheid</h2><p data-i18n="benefits.card3.description">Meer dan een spel. Samen genieten staat centraal.</p></div></article>
            <article class="benefit"><span class="benefit-icon"><i class="fa-solid fa-bullseye"></i></span><div><h2 data-i18n="benefits.card4.title">Altijd een doel</h2><p data-i18n="benefits.card4.description">Blijf verbeteren en klim naar de top.</p></div></article>
        </section>

        <section class="photo-hub" id="photos" data-photo-public data-photo-limit="3">
            <div class="photo-head">
                <div class="photo-intro">
                    <p class="photo-kicker" data-i18n="photos.kicker">BUURT GALERIJ</p>
                    <h2>De 3 nieuwste foto's uit de buurt.</h2>
                    <p>
                        De nieuwste uploads verschijnen hier automatisch. Foto's plaatsen kan op
                        de fotopagina wanneer je bent ingelogd.
                    </p>
                </div>

                <div class="photo-toolbar">
                    <span class="photo-visibility">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span>3 meest recente foto's</span>
                    </span>
                    <span class="photo-admin-indicator" data-admin-indicator hidden>
                        <i class="fa-solid fa-shield-halved"></i>
                        <span data-i18n="photos.adminMode">Admin-modus: je kunt gedeelde foto's verwijderen</span>
                    </span>
                </div>
            </div>

            <div class="photo-locked" data-photo-locked>
                <i class="fa-solid fa-lock"></i>
                <div class="photo-locked-copy">
                    <h2 data-i18n="photos.lockedTitle">Log in om buurtfoto's te bekijken</h2>
                    <p data-i18n="photos.lockedDescription">Als je bent ingelogd kun je foto's delen en foto's van andere spelers zien.</p>
                </div>
                <button type="button" class="button button-secondary" data-photo-login data-i18n="photos.lockedButton">Inloggen en foto's bekijken</button>
            </div>

            <div class="photo-feed" data-photo-feed hidden>
                <article class="photo-highlight">
                    <div>
                        <h3>Recente wedstrijdfoto's</h3>
                        <p>Bekijk hier de nieuwste uploads. Foto's toevoegen doe je op de fotopagina.</p>
                    </div>
                    <a class="button button-outline" href="pages/fotos.php">
                        <i class="fa-solid fa-images"></i>
                        <span>Alle foto's bekijken</span>
                    </a>
                </article>

                <div class="photo-grid" data-photo-grid></div>
            </div>
        </section>
    </main>

    <footer class="quote-footer" id="over">
        <div class="quote-inner">
            <div class="footer-boules" aria-hidden="true"><span></span><span></span><span class="small-red"></span></div>
            <blockquote data-i18n-html="footer.quote">" Jeu de boules is meer dan een spel.<br>Het is samenzijn, strategie en plezier. "</blockquote>
            <div class="footer-leaf" aria-hidden="true"></div>
        </div>
    </footer>

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

    <?php render_leaderboard_modal(); ?>

    <script type="module" src="scripts/index.js"></script>
</body>
</html>
