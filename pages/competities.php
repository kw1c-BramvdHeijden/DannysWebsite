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
        <header class="site-header">
            <a class="brand" href="../index.php#home" data-i18n-aria-label="nav.brandHome" aria-label="Boules Competities home">
                <span class="brand-ball" aria-hidden="true"></span>
                <span class="brand-copy">
                    <strong>BOULES</strong>
                    <small data-i18n="brand.subtitle">COMPETITIES</small>
                </span>
            </a>

            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-i18n-aria-label="nav.menu" aria-label="Open menu">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <div class="nav-panel" id="site-nav">
                <nav class="main-nav" data-i18n-aria-label="nav.primary" aria-label="Hoofdnavigatie">
                    <a href="../index.php#home">
                        <i class="fa-solid fa-house" aria-hidden="true"></i>
                        <span data-i18n="nav.home">Home</span>
                    </a>
                    <a href="#competities" class="is-current">
                        <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                        <span data-i18n="nav.competitions">Competities</span>
                    </a>
                    <a href="../index.php#over">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        <span data-i18n="nav.about">Over ons</span>
                    </a>
                    <a href="../index.php#werkt">
                        <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                        <span data-i18n="nav.how">Hoe het werkt</span>
                    </a>
                    <a href="../index.php#photos">
                        <i class="fa-solid fa-camera" aria-hidden="true"></i>
                        <span data-i18n="nav.photos">Foto's</span>
                    </a>
                </nav>

                <div class="header-actions">
                    <div class="lang-switcher" data-lang-switcher>
                        <button type="button" class="lang-toggle-button" data-lang-toggle aria-expanded="false" aria-haspopup="true">
                            <i class="fa-solid fa-globe"></i>
                            <span data-lang-current>NL</span>
                            <i class="fa-solid fa-chevron-down lang-chevron"></i>
                        </button>
                        <div class="lang-menu" data-lang-menu hidden>
                            <button type="button" class="lang-option is-active" data-lang-option="nl">Nederlands</button>
                            <button type="button" class="lang-option" data-lang-option="en">English</button>
                        </div>
                    </div>

                    <button type="button" class="button button-ghost auth-toggle" data-auth-toggle>Inloggen</button>
                    <button type="button" class="button button-primary" data-signup-cta data-auth-open="signup"><span data-i18n="auth.signup">Aanmelden</span></button>
                    <div class="account-switcher" data-account-switcher hidden>
                        <button type="button" class="account-toggle-button" data-account-toggle aria-expanded="false" aria-haspopup="true">
                            <span class="account-avatar" aria-hidden="true">A</span>
                            <span class="account-copy">
                                <strong data-account-name>Account</strong>
                                <small data-account-role>Speler</small>
                            </span>
                            <i class="fa-solid fa-chevron-down account-chevron"></i>
                        </button>
                        <div class="account-menu" data-account-menu hidden>
                            <button type="button" class="account-option is-active" data-role-option="player">
                                <span class="account-option-title" data-i18n="account.rolePlayer">Speler</span>
                                <span class="account-option-copy" data-i18n="account.rolePlayerDescription">Kan foto's delen en buurtfoto's bekijken.</span>
                            </button>
                            <button type="button" class="account-option" data-role-option="admin">
                                <span class="account-option-title" data-i18n="account.roleAdmin">Admin</span>
                                <span class="account-option-copy" data-i18n="account.roleAdminDescription">Kan gedeelde foto's beheren en verwijderen.</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

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
