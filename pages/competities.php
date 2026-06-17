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
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/footer.css">
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
                <a href="../pages/kalender.php" class="button button-outline panel-admin-button" data-competition-create hidden>
                    <i class="fa-solid fa-plus"></i>
                    <span>Competitie toevoegen</span>
                </a>
            </div>
        </div>
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



<script type="module" src="../scripts/index.js"></script>
</body>
</html>
