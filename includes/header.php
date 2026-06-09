<?php
function render_site_header($activePage = "home", $isRoot = true)
{
    $indexHome = $isRoot ? "#home" : "../index.php#home";
    $competitions = $isRoot ? "pages/competities.php" : "competities.php";
    $rules = $isRoot ? "pages/spelregels.php" : "spelregels.php";
    $photos = $isRoot ? "pages/fotos.php" : "fotos.php";

    $current = function ($page) use ($activePage) {
        return $activePage === $page ? ' class="is-current"' : "";
    };

    // Page-specific anchors keep the shared auth modal behavior unchanged.
    $authForgotLinks = [
        "home" => "#home",
        "competitions" => "#competities",
        "photos" => "#photos",
        "rules" => "#regels",
    ];

    $authForgotHref = isset($authForgotLinks[$activePage]) ? $authForgotLinks[$activePage] : $indexHome;
    ?>
    <header class="site-header">
        <a class="brand" href="<?= htmlspecialchars($indexHome) ?>" data-i18n-aria-label="nav.brandHome" aria-label="Boules Competities home">
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
                <a href="<?= htmlspecialchars($indexHome) ?>"<?= $current("home") ?>>
                    <i class="fa-solid fa-house" aria-hidden="true"></i>
                    <span data-i18n="nav.home">Home</span>
                </a>
                <a href="<?= htmlspecialchars($competitions) ?>"<?= $current("competitions") ?>>
                    <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                    <span data-i18n="nav.competitions">Competities</span>
                </a>
                <a href="<?= htmlspecialchars($rules) ?>"<?= $current("rules") ?>>
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    <span data-i18n="nav.how">Hoe het werkt</span>
                </a>
                <a href="<?= htmlspecialchars($photos) ?>"<?= $current("photos") ?>>
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

    <!-- Login/signup modal lives with the header. -->
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
                    <p class="upload-description" data-auth-description>Inloggen om verder te gaan.</p>
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

                    <a href="<?= htmlspecialchars($authForgotHref) ?>" class="auth-inline-link" data-auth-forgot-password>Wachtwoord vergeten?</a>
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
    <?php
}
