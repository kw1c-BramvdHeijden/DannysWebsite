<?php
session_start();

ob_start();
require_once __DIR__ . "/../includes/db.php";
ob_end_clean();

require_once __DIR__ . "/../includes/header.php";

function escape_html($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function get_photo_user_id()
{
    if (isset($_SESSION["user_id"]) && (int) $_SESSION["user_id"] > 0) {
        return (int) $_SESSION["user_id"];
    }

    if (isset($_POST["user_id"]) && (int) $_POST["user_id"] > 0) {
        return (int) $_POST["user_id"];
    }

    return null;
}

function is_admin_logged_in()
{
    if (isset($_SESSION["is_admin"]) && (int) $_SESSION["is_admin"] === 1) {
        return true;
    }

    if (isset($_SESSION["admin"]) && (int) $_SESSION["admin"] === 1) {
        return true;
    }

    if (isset($_SESSION["role"]) && strtolower((string) $_SESSION["role"]) === "admin") {
        return true;
    }

    if (isset($_SESSION["user_role"]) && strtolower((string) $_SESSION["user_role"]) === "admin") {
        return true;
    }

    if (isset($_SESSION["account_type"]) && strtolower((string) $_SESSION["account_type"]) === "admin") {
        return true;
    }

    if (isset($_POST["is_admin"]) && (int) $_POST["is_admin"] === 1) {
        return true;
    }

    return false;
}

function save_photo_upload($file)
{
    if (!isset($file["error"]) || $file["error"] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("De foto kon niet worden geupload.");
    }

    $allowedTypes = array(
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp",
            "image/gif" => "gif"
    );

    $mimeType = mime_content_type($file["tmp_name"]);

    if (!isset($allowedTypes[$mimeType])) {
        throw new RuntimeException("Upload alleen JPG, PNG, WEBP of GIF bestanden.");
    }

    $uploadPath = __DIR__ . "/../uploads/fotos";

    if (!is_dir($uploadPath) && !mkdir($uploadPath, 0755, true)) {
        throw new RuntimeException("De uploadmap kon niet worden aangemaakt.");
    }

    $fileName = bin2hex(random_bytes(16)) . "." . $allowedTypes[$mimeType];
    $destination = $uploadPath . "/" . $fileName;

    if (!move_uploaded_file($file["tmp_name"], $destination)) {
        throw new RuntimeException("De foto kon niet worden opgeslagen.");
    }

    return "../uploads/fotos/" . $fileName;
}

function delete_photo_file($imageUrl)
{
    $imageUrl = (string) $imageUrl;

    if ($imageUrl === "") {
        return;
    }

    $relativePath = str_replace("../", "", $imageUrl);
    $fullPath = __DIR__ . "/../" . $relativePath;

    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}

function redirect_to_photos($status)
{
    header("Location: fotos.php?status=" . urlencode($status));
    exit;
}

$photoError = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = isset($_POST["photo_action"]) ? $_POST["photo_action"] : "";

    try {
        if ($action === "upload") {
            $caption = trim(isset($_POST["caption"]) ? $_POST["caption"] : "");

            if ($caption === "") {
                throw new RuntimeException("Vul een titel in voor de foto.");
            }

            if (!isset($_FILES["image"])) {
                throw new RuntimeException("Kies eerst een foto.");
            }

            $imageUrl = save_photo_upload($_FILES["image"]);

            $sql = "INSERT INTO fotos (user_id, caption, image_url, uploaded_at)
                    VALUES (:user_id, :caption, :image_url, NOW())";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(array(
                    ":user_id" => get_photo_user_id(),
                    ":caption" => $caption,
                    ":image_url" => $imageUrl
            ));

            redirect_to_photos("uploaded");
        }

        if ($action === "delete") {
            if (!is_admin_logged_in()) {
                throw new RuntimeException("Alleen een admin kan foto's verwijderen.");
            }

            $fotoId = (int) (isset($_POST["foto_id"]) ? $_POST["foto_id"] : 0);

            if ($fotoId < 1) {
                throw new RuntimeException("Ongeldig foto ID.");
            }

            $stmt = $pdo->prepare("SELECT image_url FROM fotos WHERE foto_id = :foto_id LIMIT 1");
            $stmt->execute(array(
                    ":foto_id" => $fotoId
            ));

            $photo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$photo) {
                throw new RuntimeException("Foto niet gevonden.");
            }

            $stmt = $pdo->prepare("DELETE FROM fotos WHERE foto_id = :foto_id");
            $stmt->execute(array(
                    ":foto_id" => $fotoId
            ));

            delete_photo_file($photo["image_url"]);

            redirect_to_photos("deleted");
        }
    } catch (Throwable $error) {
        $photoError = $error->getMessage();
    }
}

$photos = array();

try {
    $stmt = $pdo->query("SELECT foto_id, user_id, caption, image_url, uploaded_at FROM fotos ORDER BY uploaded_at DESC");
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $error) {
    if ($photoError === "") {
        $photoError = $error->getMessage();
    }
}

$bootstrapPhotos = array();

foreach ($photos as $photo) {
    $uploadedAt = strtotime((string) $photo["uploaded_at"]);
    $ownerId = $photo["user_id"] !== null ? (string) $photo["user_id"] : "";

    $bootstrapPhotos[] = array(
            "id" => (string) $photo["foto_id"],
            "ownerId" => $ownerId,
            "author" => $ownerId !== "" ? "Gebruiker " . $ownerId : "Onbekende gebruiker",
            "caption" => (string) $photo["caption"],
            "createdAt" => $uploadedAt ? $uploadedAt * 1000 : time() * 1000,
            "image" => (string) $photo["image_url"]
    );
}

$photoStatus = isset($_GET["status"]) ? $_GET["status"] : "";
$photoMessage = "";

if ($photoStatus === "uploaded") {
    $photoMessage = "De foto is opgeslagen in de database.";
} elseif ($photoStatus === "deleted") {
    $photoMessage = "De foto is verwijderd uit de database.";
}

$isAdmin = is_admin_logged_in();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boules Competities | Foto's</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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
                <span class="photo-admin-indicator" data-admin-indicator <?= $isAdmin ? "" : "hidden" ?>>
                    <i class="fa-solid fa-shield-halved"></i>
                    <span data-i18n="photos.adminMode">Admin-modus: je kunt gedeelde foto's verwijderen</span>
                </span>
                <input class="photo-input" data-photo-input type="file" accept="image/*" name="image" form="photo-upload-form" required>
            </div>
        </div>

        <?php if ($photoMessage !== ""): ?>
            <p class="auth-popup-feedback is-success"><?= escape_html($photoMessage) ?></p>
        <?php endif; ?>

        <?php if ($photoError !== ""): ?>
            <p class="auth-popup-feedback"><?= escape_html($photoError) ?></p>
        <?php endif; ?>

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

                <button type="submit" class="button button-primary auth-popup-submit">Account aanmaken</button>

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

            <form class="upload-form" data-upload-form id="photo-upload-form" method="post" action="fotos.php" enctype="multipart/form-data">
                <input type="hidden" name="photo_action" value="upload">
                <input type="hidden" name="user_id" value="" data-photo-user-id>

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
                    <input type="text" data-upload-title name="caption" required data-i18n-placeholder="upload.photoTitlePlaceholder" placeholder="Bijvoorbeeld: Finale op het plein">
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
    globalThis.__BOULES_BOOTSTRAP__ = {
        photos: <?= json_encode($bootstrapPhotos, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
        isAdmin: <?= $isAdmin ? "true" : "false" ?>
    };

    function valueMeansAdmin(value) {
        const text = String(value).toLowerCase();

        return text === "admin" ||
            text === "administrator" ||
            text === "1" ||
            text === "true";
    }

    function objectHasAdminAccess(value) {
        if (!value || typeof value !== "object") {
            return false;
        }

        for (const key in value) {
            const keyText = String(key).toLowerCase();
            const fieldValue = value[key];

            if (
                keyText === "role" ||
                keyText === "user_role" ||
                keyText === "type" ||
                keyText === "accounttype" ||
                keyText === "account_type" ||
                keyText === "is_admin" ||
                keyText === "isadmin" ||
                keyText === "admin"
            ) {
                if (valueMeansAdmin(fieldValue)) {
                    return true;
                }
            }

            if (typeof fieldValue === "object" && objectHasAdminAccess(fieldValue)) {
                return true;
            }
        }

        return false;
    }

    function clientIsAdmin() {
        if (globalThis.__BOULES_BOOTSTRAP__ && globalThis.__BOULES_BOOTSTRAP__.isAdmin) {
            return true;
        }

        for (let index = 0; index < localStorage.length; index++) {
            const key = localStorage.key(index);
            const value = localStorage.getItem(key);

            try {
                const parsed = JSON.parse(value);

                if (objectHasAdminAccess(parsed)) {
                    return true;
                }
            } catch (error) {
                if (valueMeansAdmin(value)) {
                    return true;
                }
            }
        }

        return false;
    }

    document.addEventListener("submit", function (event) {
        const form = event.target instanceof HTMLFormElement ? event.target : null;

        if (!form || form.id !== "photo-upload-form") {
            return;
        }

        const userIdInput = form.querySelector("[data-photo-user-id]");

        if (!(userIdInput instanceof HTMLInputElement)) {
            return;
        }

        try {
            const auth = JSON.parse(localStorage.getItem("boules_auth") || "null");
            const numericUserId = Number(auth && auth.user ? auth.user.id : 0);

            userIdInput.value = Number.isInteger(numericUserId) && numericUserId > 0 ? String(numericUserId) : "";
        } catch (error) {
            userIdInput.value = "";
        }

        event.stopImmediatePropagation();
    }, true);

    document.addEventListener("click", function (event) {
        const target = event.target instanceof Element ? event.target : null;
        const deleteButton = target ? target.closest("[data-photo-delete]") : null;

        if (!deleteButton) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        if (!clientIsAdmin()) {
            window.alert("Alleen een admin kan foto's verwijderen.");
            return;
        }

        if (!window.confirm("Weet je zeker dat je deze foto wilt verwijderen?")) {
            return;
        }

        const form = document.createElement("form");
        form.method = "post";
        form.action = "fotos.php";

        const action = document.createElement("input");
        action.type = "hidden";
        action.name = "photo_action";
        action.value = "delete";

        const fotoId = document.createElement("input");
        fotoId.type = "hidden";
        fotoId.name = "foto_id";
        fotoId.value = deleteButton.getAttribute("data-photo-delete") || "";

        const isAdminInput = document.createElement("input");
        isAdminInput.type = "hidden";
        isAdminInput.name = "is_admin";
        isAdminInput.value = "1";

        form.append(action, fotoId, isAdminInput);
        document.body.appendChild(form);
        form.submit();
    }, true);
</script>

<script type="module" src="../scripts/index.js"></script>
</body>
</html>