<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/bootstrap-data.php";
require_once __DIR__ . "/../includes/header.php";

$bootstrapData = boules_bootstrap_data($pdo, "competities.php", 100, "../");
?>
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

    <!-- Auth modal is rendered by includes/header.php. -->
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

    <script>
        window.__BOULES_BOOTSTRAP__ = <?= json_encode($bootstrapData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script type="module" src="../scripts/index.js"></script>
</body>
</html>
