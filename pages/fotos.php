<?php require_once __DIR__ . "/../includes/header.php"; ?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <title>Boules Competities | Foto's</title>
    <link rel="stylesheet" href="../css/variables.css">
    <link rel="stylesheet" href="../css/index.css">
</head>
<body data-competitions-href="competities.php">
    <main class="page-shell">
        <?php render_site_header("photos", false); ?>

        <section class="photo-hub" id="photos">
            <div class="photo-head">
                <div class="photo-intro">
                    <p class="photo-kicker">FOTO'S</p>
                    <h2>Wedstrijdfoto's</h2>
                    <p>
                        Upload hier nieuwe foto's en bekijk de gedeelde momenten van wedstrijden,
                        trainingen en toernooien.
                    </p>
                </div>

                <div class="photo-toolbar">
                    <span class="photo-admin-indicator" data-admin-indicator hidden>
                        <i class="fa-solid fa-shield-halved"></i>
                        <span data-i18n="photos.adminMode">Admin-modus: je kunt gedeelde foto's verwijderen</span>
                    </span>
                    <input class="photo-input" data-photo-input type="file" accept="image/*">
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
                        <h3 data-i18n="photos.highlightTitle">Laat de buurt meegenieten</h3>
                        <p data-i18n="photos.highlightDescription">Upload een foto van jullie wedstrijd, training of toernooi. Andere ingelogde spelers zien hem direct terug.</p>
                    </div>
                    <button type="button" class="button button-outline" data-upload-trigger>
                        <i class="fa-solid fa-arrow-up-from-bracket"></i>
                        <span data-i18n="photos.highlightButton">Upload foto</span>
                    </button>
                </article>

                <div class="photo-grid" data-photo-grid></div>
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
                    <p class="upload-description" data-auth-description>Log in om verder te gaan.</p>
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

                    <a href="#photos" class="auth-inline-link" data-auth-forgot-password>Wachtwoord vergeten?</a>
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

    <div class="upload-modal" data-upload-modal hidden>
        <div class="upload-modal-backdrop" data-upload-cancel></div>
        <div class="upload-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="upload-modal-title">
            <button type="button" class="upload-modal-close" data-upload-cancel data-i18n-aria-label="upload.closeLabel" aria-label="Sluit uploadvenster">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="upload-modal-layout">
                <div class="upload-preview-shell">
                    <img src="" alt="" data-upload-preview>
                </div>

                <form class="upload-form" data-upload-form>
                    <p class="photo-kicker" data-upload-kicker-text data-i18n="upload.kicker">FOTO PUBLICEREN</p>
                    <h2 id="upload-modal-title" data-upload-title-text data-i18n="upload.title">Geef je foto een titel en beschrijving</h2>
                    <p class="upload-description" data-upload-description-text data-i18n="upload.description">Pas eerst de titel en beschrijving aan voordat je de foto post.</p>

                    <div class="upload-change-row">
                        <button type="button" class="button button-outline upload-change-button" data-upload-change>
                            <i class="fa-solid fa-images"></i>
                            <span data-i18n="upload.changePhoto">Kies andere foto</span>
                        </button>
                    </div>

                    <label class="upload-label">
                        <span data-i18n="upload.photoTitle">Titel</span>
                        <input type="text" data-upload-title data-i18n-placeholder="upload.photoTitlePlaceholder" placeholder="Bijvoorbeeld: Finale op het plein">
                    </label>

                    <label class="upload-label">
                        <span data-i18n="upload.photoDescription">Beschrijving</span>
                        <textarea rows="5" data-upload-description data-i18n-placeholder="upload.photoDescriptionPlaceholder" placeholder="Vertel kort wat er op deze foto te zien is"></textarea>
                    </label>

                    <div class="upload-actions">
                        <button type="button" class="button button-ghost" data-upload-cancel data-i18n="upload.cancel">Annuleren</button>
                        <button type="submit" class="button button-primary">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span data-upload-submit-label data-i18n="upload.submit">Publiceer foto</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script type="module" src="../scripts/index.js"></script>
</body>
</html>
