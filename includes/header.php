<?php
function render_site_header(string $activePage = "home", bool $isRoot = true)
{
    $indexHome = $isRoot ? "#home" : "../index.php#home";
    $indexOver = $isRoot ? "#over" : "../index.php#over";
    $competitions = $isRoot ? "pages/competities.php" : "competities.php";
    $rules = $isRoot ? "pages/spelregels.php" : "spelregels.php";
    $photos = $isRoot ? "pages/fotos.php" : "fotos.php";

    $current = function (string $page) use ($activePage): string {
        return $activePage === $page ? ' class="is-current"' : "";
    };
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
                <a href="<?= htmlspecialchars($indexOver) ?>"<?= $current("about") ?>>
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    <span data-i18n="nav.about">Over ons</span>
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
    <?php
}
