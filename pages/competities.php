<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/bootstrap-data.php";
require_once __DIR__ . "/../includes/header.php";

$bootstrapData = boules_bootstrap_data($pdo, "#competities", 0, "../");
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <title>Boules Competities | Competities</title>
    <link rel="stylesheet" href="../css/variables.css">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/competities_fotos_kalender_spelregels.css">
    <link rel="stylesheet" href="../css/index_competities.css">
    <link rel="stylesheet" href="../css/competities.css">
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

    <section class="teams-panel">
        <div class="challenge-banner">
            <div class="challenge-copy">
                <span class="challenge-boules" aria-hidden="true"></span>
                <div>
                    <p class="photo-kicker" data-i18n="teams.blockKicker">TEAMS</p>
                    <h3 data-i18n="teams.blockTitle">Team aanmelden of bekijken</h3>
                    <p data-i18n="teams.blockDescription">Log in om teams te bekijken en je eigen team aan te melden voor de competitie.</p>
                </div>
            </div>
            <div class="challenge-actions">
                <button type="button" class="button button-secondary" data-team-open hidden>
                    <i class="fa-solid fa-user-group"></i>
                    <span data-i18n="teams.open">Teams bekijken</span>
                </button>
            </div>
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
                <button type="button" class="button button-secondary" data-competition-request hidden><i class="fa-solid fa-paper-plane"></i><span data-i18n="challenge.cta">Aanvraag starten</span></button>
                <button type="button" class="button button-outline panel-admin-button" data-competition-create hidden>
                    <i class="fa-solid fa-plus"></i>
                    <span>Competitie toevoegen</span>
                </button>
            </div>
        </div>
    </section>

    <section class="competitions-panel competition-requests-panel" data-competition-requests-panel hidden>
        <div class="panel-heading">
            <h2 data-i18n="competitions.requests.heading">AANGEVRAAGDE COMPETITIES</h2>
            <div class="panel-actions">
                <span class="panel-admin-indicator">
                    <i class="fa-solid fa-clock"></i>
                    <span data-i18n="competitions.requests.adminOnly">Alleen zichtbaar voor admins</span>
                </span>
            </div>
        </div>

        <p class="competition-requests-status" data-competition-requests-status aria-live="polite"></p>
        <div class="competition-request-header" aria-hidden="true">
            <span data-i18n="competitions.requests.column.status">Status</span>
            <span data-i18n="competitions.requests.column.name">Naam</span>
            <span data-i18n="competitions.requests.column.location">Locatie</span>
            <span data-i18n="competitions.requests.column.startDate">Startdatum</span>
            <span></span>
        </div>
        <div class="competition-request-list" data-competition-requests-grid></div>
    </section>
</main>

<div class="auth-modal" data-auth-modal hidden>
    <div class="auth-modal-backdrop" data-auth-close></div>
    <div class="auth-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="auth-modal-title">
        <button type="button" class="auth-modal-close" data-auth-close aria-label="Sluit inlogvenster">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="auth-panel">
            <div class="auth-panel-emblem" aria-hidden="true">
                <span class="auth-panel-leaf"></span>
                <span class="auth-panel-avatar"><i class="fa-regular fa-user"></i></span>
                <span class="auth-panel-leaf"></span>
            </div>

            <div class="auth-panel-tabs" role="tablist" aria-label="Authenticatie">
                <button type="button" class="auth-panel-tab is-active" data-auth-tab="login">Inloggen</button>
                <button type="button" class="auth-panel-tab" data-auth-tab="signup">Aanmelden</button>
            </div>

            <div class="auth-panel-copy">
                <p class="photo-kicker" data-auth-kicker>WELKOM TERUG</p>
                <h2 id="auth-modal-title" data-auth-title>Inloggen</h2>
                <p class="upload-description" data-auth-description>Log in om verder te gaan en de buurtcompetitie te openen.</p>
            </div>

            <form class="auth-popup-form is-active" data-auth-form="login">
                <label class="auth-input">
                    <i class="fa-regular fa-user"></i>
                    <input type="text" data-auth-login-identity autocomplete="username">
                </label>

                <label class="auth-input auth-input-password">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" data-auth-login-password autocomplete="current-password">
                    <button type="button" class="auth-password-toggle" data-auth-password-toggle aria-label="Toon wachtwoord">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </label>

                <a href="#competities" class="auth-inline-link" data-auth-forgot-password>Wachtwoord vergeten?</a>
                <p class="auth-popup-feedback" data-auth-feedback="login" aria-live="polite"></p>

                <button type="submit" class="button button-primary auth-popup-submit" data-auth-submit-login>Inloggen</button>

                <p class="auth-switch-row">
                    <span data-auth-switch-copy-login>Nog geen account?</span>
                    <button type="button" class="auth-switch-button" data-auth-switch="signup">Aanmelden</button>
                </p>
            </form>

            <form class="auth-popup-form" data-auth-form="signup" hidden>
                <label class="auth-input">
                    <i class="fa-regular fa-user"></i>
                    <input type="text" data-auth-signup-name autocomplete="name">
                </label>

                <label class="auth-input">
                    <i class="fa-regular fa-envelope"></i>
                    <input type="email" data-auth-signup-email autocomplete="email">
                </label>

                <label class="auth-input auth-input-password">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" data-auth-signup-password autocomplete="new-password">
                    <button type="button" class="auth-password-toggle" data-auth-password-toggle aria-label="Toon wachtwoord">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </label>

                <label class="auth-input auth-input-password">
                    <i class="fa-solid fa-shield-halved"></i>
                    <input type="password" data-auth-signup-password-confirm autocomplete="new-password">
                    <button type="button" class="auth-password-toggle" data-auth-password-toggle aria-label="Toon wachtwoord">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </label>

                <p class="auth-popup-feedback" data-auth-feedback="signup" aria-live="polite"></p>

                <button type="submit" class="button button-primary auth-popup-submit" data-auth-submit-signup>Account aanmaken</button>

                <p class="auth-switch-row">
                    <span data-auth-switch-copy-signup>Heb je al een account?</span>
                    <button type="button" class="auth-switch-button" data-auth-switch="login">Inloggen</button>
                </p>
            </form>
        </div>
    </div>
</div>

<div class="competition-modal" data-competition-modal hidden>
    <div class="competition-modal-backdrop" data-competition-cancel></div>
    <div class="competition-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="competition-modal-title">
        <button type="button" class="competition-modal-close" data-competition-cancel data-i18n-aria-label="competitions.form.closeLabel" aria-label="Sluit competitievenster">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <form class="competition-form" data-competition-form>
            <p class="photo-kicker" data-competition-form-kicker data-i18n="competitions.form.requestKicker">COMPETITIE AANVRAGEN</p>
            <h2 id="competition-modal-title" data-competition-form-title data-i18n="competitions.form.requestTitle">Start een competitieaanvraag</h2>
            <p class="upload-description" data-competition-form-description data-i18n="competitions.form.requestDescription">Vul je voorstel in. De aanvraag wordt opgeslagen en komt op pending te staan.</p>

            <label class="upload-label">
                <span data-i18n="competitions.form.name">Naam</span>
                <input type="text" data-competition-name data-i18n-placeholder="competitions.form.namePlaceholder" placeholder="Bijvoorbeeld: Voorjaars Toernooi">
            </label>

            <label class="upload-label">
                <span data-i18n="competitions.form.type">Locatie</span>
                <input type="text" data-competition-type data-i18n-placeholder="competitions.form.typePlaceholder" placeholder="Bijvoorbeeld: Dorpsplein 4 of Baan 2">
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
                    <i class="fa-solid fa-paper-plane"></i>
                    <span data-competition-submit-label data-i18n="competitions.form.requestSubmit">Aanvraag versturen</span>
                </button>
            </div>
            <p class="auth-popup-feedback" data-competition-feedback aria-live="polite"></p>
        </form>
    </div>
</div>

<div class="team-modal" data-team-modal hidden>
    <div class="team-modal-backdrop" data-team-close></div>
    <div class="team-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="team-modal-title">
        <button type="button" class="team-modal-close" data-team-close data-i18n-aria-label="teams.closeLabel" aria-label="Sluit teamvenster">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="team-modal-shell">
            <form class="team-form" data-team-form>
                <p class="photo-kicker" data-i18n="teams.kicker">TEAM AANMELDEN</p>
                <h2 id="team-modal-title" data-i18n="teams.title">Meld je team aan</h2>
                <p class="upload-description" data-i18n="teams.description">Vul je teamnaam en spelers in. Het team komt direct in het overzicht.</p>

                <label class="upload-label">
                    <span data-i18n="teams.form.name">Teamnaam</span>
                    <input type="text" data-team-name data-i18n-placeholder="teams.form.namePlaceholder" placeholder="Bijvoorbeeld: De Pleinwerpers">
                </label>

                <div class="team-user-picker">
                    <span class="team-user-picker-label" data-i18n="teams.form.members">Gebruikers</span>
                    <button type="button" class="team-user-picker-toggle" data-team-user-toggle aria-expanded="false">
                        <span data-team-user-summary data-i18n="teams.form.membersPlaceholder">Selecteer gebruikers</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div class="team-user-menu" data-team-user-menu hidden>
                        <p class="team-user-menu-status" data-team-user-status data-i18n="teams.users.loading">Gebruikers laden...</p>
                        <div class="team-user-options" data-team-user-options></div>
                    </div>
                </div>

                <div class="upload-actions">
                    <button type="button" class="button button-ghost" data-team-close data-i18n="teams.cancel">Annuleren</button>
                    <button type="submit" class="button button-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span data-i18n="teams.submit">Team aanmelden</span>
                    </button>
                </div>
                <p class="auth-popup-feedback" data-team-feedback aria-live="polite"></p>
            </form>

            <section class="team-overview" aria-labelledby="team-overview-title">
                <div class="team-overview-head">
                    <p class="photo-kicker" data-i18n="teams.overviewKicker">BESTAANDE TEAMS</p>
                    <h3 id="team-overview-title" data-i18n="teams.overviewTitle">Teamoverzicht</h3>
                </div>
                <p class="team-overview-status" data-team-status aria-live="polite"></p>
                <div class="team-list" data-team-list></div>
            </section>
        </div>
    </div>
</div>


<script>
    window.__BOULES_BOOTSTRAP__ = <?= json_encode($bootstrapData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script type="module" src="../scripts/index.js"></script>
</body>
</html>
