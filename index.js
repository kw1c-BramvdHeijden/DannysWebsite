const body = document.body;
const html = document.documentElement;
const siteHeader = document.querySelector(".site-header");
const navToggle = document.querySelector(".nav-toggle");
const navPanel = document.querySelector(".nav-panel");
const navLinks = Array.from(document.querySelectorAll(".main-nav a"));
const authToggles = Array.from(document.querySelectorAll("[data-auth-toggle]"));
const authOpeners = Array.from(document.querySelectorAll("[data-auth-open]"));
const signupCta = document.querySelector("[data-signup-cta]");
const accountSwitcher = document.querySelector("[data-account-switcher]");
const accountToggle = document.querySelector("[data-account-toggle]");
const accountMenu = document.querySelector("[data-account-menu]");
const accountName = document.querySelector("[data-account-name]");
const accountRole = document.querySelector("[data-account-role]");
const roleOptions = Array.from(document.querySelectorAll("[data-role-option]"));
const lockedLoginButton = document.querySelector("[data-photo-login]");
const authModal = document.querySelector("[data-auth-modal]");
const authModalTitle = document.querySelector("[data-auth-title]");
const authModalKicker = document.querySelector("[data-auth-kicker]");
const authModalDescription = document.querySelector("[data-auth-description]");
const authTabs = Array.from(document.querySelectorAll("[data-auth-tab]"));
const authSwitchButtons = Array.from(document.querySelectorAll("[data-auth-switch]"));
const authForms = Array.from(document.querySelectorAll("[data-auth-form]"));
const authCloseButtons = Array.from(document.querySelectorAll("[data-auth-close]"));
const authForgotPassword = document.querySelector("[data-auth-forgot-password]");
const authSubmitLogin = document.querySelector("[data-auth-submit-login]");
const authSubmitSignup = document.querySelector("[data-auth-submit-signup]");
const authSwitchCopyLogin = document.querySelector("[data-auth-switch-copy-login]");
const authSwitchCopySignup = document.querySelector("[data-auth-switch-copy-signup]");
const authLoginIdentityInput = document.querySelector("[data-auth-login-identity]");
const authLoginPasswordInput = document.querySelector("[data-auth-login-password]");
const authSignupNameInput = document.querySelector("[data-auth-signup-name]");
const authSignupEmailInput = document.querySelector("[data-auth-signup-email]");
const authSignupPasswordInput = document.querySelector("[data-auth-signup-password]");
const authSignupPasswordConfirmInput = document.querySelector("[data-auth-signup-password-confirm]");
const authFeedbackElements = Array.from(document.querySelectorAll("[data-auth-feedback]"));
const authPasswordToggles = Array.from(document.querySelectorAll("[data-auth-password-toggle]"));
const uploadTriggers = Array.from(document.querySelectorAll("[data-upload-trigger]"));
const photoInput = document.querySelector("[data-photo-input]");
const photoLocked = document.querySelector("[data-photo-locked]");
const photoFeed = document.querySelector("[data-photo-feed]");
const photoGrid = document.querySelector("[data-photo-grid]");
const adminIndicator = document.querySelector("[data-admin-indicator]");
const langSwitcher = document.querySelector("[data-lang-switcher]");
const langToggle = document.querySelector("[data-lang-toggle]");
const langMenu = document.querySelector("[data-lang-menu]");
const langCurrent = document.querySelector("[data-lang-current]");
const langOptions = Array.from(document.querySelectorAll("[data-lang-option]"));
const uploadModal = document.querySelector("[data-upload-modal]");
const uploadPreview = document.querySelector("[data-upload-preview]");
const uploadForm = document.querySelector("[data-upload-form]");
const uploadKickerText = document.querySelector("[data-upload-kicker-text]");
const uploadTitleText = document.querySelector("[data-upload-title-text]");
const uploadDescriptionText = document.querySelector("[data-upload-description-text]");
const uploadSubmitLabel = document.querySelector("[data-upload-submit-label]");
const uploadChangeButton = document.querySelector("[data-upload-change]");
const uploadTitleInput = document.querySelector("[data-upload-title]");
const uploadDescriptionInput = document.querySelector("[data-upload-description]");
const uploadCancelButtons = Array.from(document.querySelectorAll("[data-upload-cancel]"));
const competitionGrid = document.querySelector("[data-competition-grid]");
const competitionAdminIndicator = document.querySelector("[data-competition-admin-indicator]");
const competitionCreateButton = document.querySelector("[data-competition-create]");
const competitionModal = document.querySelector("[data-competition-modal]");
const competitionForm = document.querySelector("[data-competition-form]");
const competitionFormKicker = document.querySelector("[data-competition-form-kicker]");
const competitionFormTitle = document.querySelector("[data-competition-form-title]");
const competitionFormDescription = document.querySelector("[data-competition-form-description]");
const competitionSubmitLabel = document.querySelector("[data-competition-submit-label]");
const competitionNameInput = document.querySelector("[data-competition-name]");
const competitionTypeInput = document.querySelector("[data-competition-type]");
const competitionDateInput = document.querySelector("[data-competition-date]");
const competitionToneInput = document.querySelector("[data-competition-tone]");
const competitionCancelButtons = Array.from(document.querySelectorAll("[data-competition-cancel]"));
const leaderboardPreviewList = document.querySelector("[data-leaderboard-list]");
const leaderboardOpenButton = document.querySelector("[data-leaderboard-open]");
const leaderboardModal = document.querySelector("[data-leaderboard-modal]");
const leaderboardModalCloseButton = document.querySelector("[data-leaderboard-close-button]");
const leaderboardCloseButtons = Array.from(document.querySelectorAll("[data-leaderboard-close]"));
const leaderboardTable = document.querySelector(".leaderboard-table");
const leaderboardTableBody = document.querySelector("[data-leaderboard-table-body]");
const leaderboardUpdatedText = document.querySelector("[data-leaderboard-updated]");
const leaderboardTeamFilter = document.querySelector("[data-leaderboard-team-filter]");

const AUTH_KEY = "boules_logged_in";
const ROLE_KEY = "boules_role";
const PHOTOS_KEY = "boules_shared_photos";
const DELETED_PHOTOS_KEY = "boules_deleted_photos";
const COMPETITION_CUSTOM_KEY = "boules_custom_competitions";
const COMPETITION_EDITS_KEY = "boules_competition_edits";
const COMPETITION_DELETED_KEY = "boules_deleted_competitions";
const LANG_KEY = "boules_language";
const DEFAULT_AUTHOR = "Danny";
const CURRENT_USER_ID = "danny";
const localeMap = {
    nl: "nl-NL",
    en: "en-GB"
};

const translations = {
    nl: {
        "meta.title": "Boules Competities",
        "brand.subtitle": "COMPETITIES",
        "nav.menu": "Open navigatiemenu",
        "nav.closeMenu": "Sluit navigatiemenu",
        "nav.primary": "Hoofdnavigatie",
        "nav.brandHome": "Boules Competities home",
        "nav.home": "Home",
        "nav.competitions": "Competities",
        "nav.about": "Over ons",
        "nav.how": "Hoe het werkt",
        "nav.photos": "Foto's",
        "nav.contact": "Contact",
        "auth.login": "Inloggen",
        "auth.logout": "Uitloggen",
        "auth.signup": "Aanmelden",
        "auth.modal.close": "Sluit inlogvenster",
        "auth.modal.tabLogin": "Inloggen",
        "auth.modal.tabSignup": "Aanmelden",
        "auth.modal.loginKicker": "WELKOM TERUG",
        "auth.modal.loginTitle": "Inloggen",
        "auth.modal.loginDescription": "Log in om verder te gaan en de buurtcompetitie te openen.",
        "auth.modal.signupKicker": "DOE MEE",
        "auth.modal.signupTitle": "Aanmelden",
        "auth.modal.signupDescription": "Maak je account aan en start meteen met competities, foto's en buurtmomenten.",
        "auth.modal.identityPlaceholder": "Gebruikersnaam",
        "auth.modal.passwordPlaceholder": "Wachtwoord",
        "auth.modal.namePlaceholder": "Volledige naam",
        "auth.modal.emailPlaceholder": "E-mailadres",
        "auth.modal.passwordConfirmPlaceholder": "Herhaal wachtwoord",
        "auth.modal.forgotPassword": "Wachtwoord vergeten?",
        "auth.modal.switchToSignupLead": "Nog geen account?",
        "auth.modal.switchToLoginLead": "Heb je al een account?",
        "auth.modal.showPassword": "Toon wachtwoord",
        "auth.modal.hidePassword": "Verberg wachtwoord",
        "auth.modal.feedback.loginMissing": "Vul je gebruikersnaam en wachtwoord in.",
        "auth.modal.feedback.signupMissing": "Vul alle velden in om je account aan te maken.",
        "auth.modal.feedback.passwordShort": "Gebruik een wachtwoord van minimaal 6 tekens.",
        "auth.modal.feedback.passwordMismatch": "De wachtwoorden komen niet overeen.",
        "account.menuLabel": "Open accountmenu",
        "account.name": "Danny",
        "account.rolePlayer": "Speler",
        "account.roleAdmin": "Admin",
        "account.rolePlayerDescription": "Kan foto's delen en buurtfoto's bekijken.",
        "account.roleAdminDescription": "Kan gedeelde foto's beheren en verwijderen.",
        "hero.line1": "SPEEL.",
        "hero.line2": "DEEL.",
        "hero.line3": "GENIET.",
        "hero.description": "Breng de buurt samen voor een potje jeu de boules. Maak wedstrijden en toernooien aan, houd scores bij en deel foto's met andere spelers.",
        "hero.ctaPrimary": "Meld je aan",
        "hero.ctaSecondary": "Bekijk competities",
        "hero.point1": "Speel met buren of als team",
        "hero.point2": "Organiseer toernooien en houd scores bij",
        "hero.point3": "Deel foto's met de buurt",
        "hero.imageAlt": "Jeu de boules speler tijdens een wedstrijd op het plein",
        "competitions.heading": "AANKOMENDE COMPETITIES",
        "competitions.card1.title": "Plein Open",
        "competitions.card1.type": "Doublette | Buren & vrienden",
        "competitions.card1.date": "Start: 14 mei 2026",
        "competitions.card2.title": "Buurt Dubbel",
        "competitions.card2.type": "Doublette | Vrije inschrijving",
        "competitions.card2.date": "Start: 29 mei 2026",
        "competitions.card3.title": "Zomer Toernooi",
        "competitions.card3.type": "Triplette | Teams",
        "competitions.card3.date": "Start: 12 juni 2026",
        "competitions.more": "Meer info",
        "competitions.datePrefix": "Start",
        "competitions.adminMode": "Admin-modus: beheer aankomende competities",
        "competitions.add": "Competitie toevoegen",
        "competitions.edit": "Bewerken",
        "competitions.delete": "Verwijderen",
        "competitions.deleteConfirm": "Weet je zeker dat je deze competitie wilt verwijderen?",
        "competitions.empty": "Er staan nog geen aankomende competities gepland.",
        "competitions.form.addKicker": "COMPETITIE TOEVOEGEN",
        "competitions.form.addTitle": "Maak een aankomende competitie aan",
        "competitions.form.editKicker": "COMPETITIE BEWERKEN",
        "competitions.form.editTitle": "Pas de aankomende competitie aan",
        "competitions.form.description": "Vul de belangrijkste informatie in zodat spelers zich kunnen voorbereiden.",
        "competitions.form.name": "Naam",
        "competitions.form.namePlaceholder": "Bijvoorbeeld: Voorjaars Toernooi",
        "competitions.form.type": "Type",
        "competitions.form.typePlaceholder": "Bijvoorbeeld: Doublette | Vrije inschrijving",
        "competitions.form.date": "Startdatum",
        "competitions.form.style": "Accentkleur",
        "competitions.form.styleGreen": "Groen",
        "competitions.form.styleYellow": "Geel",
        "competitions.form.styleRed": "Rood",
        "competitions.form.styleOlive": "Olijf",
        "competitions.form.cancel": "Annuleren",
        "competitions.form.save": "Competitie opslaan",
        "competitions.form.closeLabel": "Sluit competitievenster",
        "challenge.title": "Klaar om de buurt uit te dagen?",
        "challenge.description": "Maak een wedstrijd of doe mee aan het volgende toernooi.",
        "challenge.cta": "Meld je aan",
        "leaderboard.heading": "LEADERBOARD",
        "leaderboard.viewAll": "Bekijk volledig leaderboard",
        "leaderboard.modalKicker": "VOLLEDIGE STAND",
        "leaderboard.modalTitle": "Volledig leaderboard",
        "leaderboard.modalDescription": "Bekijk alle teams, gespeelde wedstrijden en punten in een overzicht.",
        "leaderboard.modalClose": "Sluit leaderboardvenster",
        "leaderboard.pointsUnit": "pts",
        "leaderboard.column.rank": "#",
        "leaderboard.column.team": "Team",
        "leaderboard.column.player": "Speler",
        "leaderboard.teamFilter.all": "Alle teams",
        "leaderboard.teamFilter.aria": "Filter leaderboard op team",
        "leaderboard.column.played": "Gespeeld",
        "leaderboard.column.won": "Gewonnen",
        "leaderboard.column.diff": "Puntverschil",
        "leaderboard.column.points": "Punten",
        "leaderboard.column.trend": "Trend",
        "leaderboard.trend.up": "Stijgt",
        "leaderboard.trend.flat": "Gelijk",
        "leaderboard.trend.down": "Daalt",
        "leaderboard.playersOf": "Spelers van",
        "benefits.card1.title": "Voor de buurt",
        "benefits.card1.description": "Nodig buren uit en speel samen op jullie eigen plein.",
        "benefits.card2.title": "Makkelijk score bijhouden",
        "benefits.card2.description": "Werk live scores bij zonder gedoe met losse briefjes.",
        "benefits.card3.title": "Samen gezellig",
        "benefits.card3.description": "Gebruik het platform voor wedstrijden, foto's en buurtmomenten.",
        "benefits.card4.title": "Toernooien maken",
        "benefits.card4.description": "Zet in een paar stappen een nieuw buurttoernooi klaar.",
        "photos.kicker": "BUURT GALERIJ",
        "photos.heading": "Publiceer foto's van wedstrijden en bekijk foto's van andere spelers.",
        "photos.description": "Alleen ingelogde spelers kunnen foto's publiceren en zien. Deel momenten van trainingen, toernooien en gezellige buurtavonden.",
        "photos.visibility": "Alleen zichtbaar voor ingelogde spelers",
        "photos.publish": "Publiceer een foto",
        "photos.lockedTitle": "Log in om buurtfoto's te bekijken",
        "photos.lockedDescription": "Als je bent ingelogd kun je foto's delen en foto's van andere spelers zien.",
        "photos.lockedButton": "Inloggen en foto's bekijken",
        "photos.highlightTitle": "Laat de buurt meegenieten",
        "photos.highlightDescription": "Upload een foto van jullie wedstrijd, training of toernooi. Andere ingelogde spelers zien hem direct terug.",
        "photos.highlightButton": "Upload foto",
        "photos.adminMode": "Admin-modus: je kunt gedeelde foto's verwijderen",
        "photos.edit": "Bewerken",
        "photos.delete": "Verwijderen",
        "photos.deleteConfirm": "Weet je zeker dat je deze foto wilt verwijderen?",
        "photos.empty": "Nog geen foto's gedeeld. Wees de eerste die een foto publiceert.",
        "photos.time.today": "Vandaag",
        "photos.time.yesterday": "Gisteren",
        "footer.quote": "\" Jeu de boules is meer dan een spel.<br>Het is samenzijn, strategie en plezier. \"",
        "upload.kicker": "FOTO PUBLICEREN",
        "upload.title": "Geef je foto een titel en beschrijving",
        "upload.description": "Pas eerst de titel en beschrijving aan voordat je de foto post.",
        "upload.editKicker": "FOTO BEWERKEN",
        "upload.editTitle": "Pas de titel en beschrijving aan",
        "upload.editDescription": "Werk de titel en beschrijving van je eigen foto bij.",
        "upload.photoTitle": "Titel",
        "upload.photoTitlePlaceholder": "Bijvoorbeeld: Finale op het plein",
        "upload.photoDescription": "Beschrijving",
        "upload.photoDescriptionPlaceholder": "Vertel kort wat er op deze foto te zien is",
        "upload.changePhoto": "Kies andere foto",
        "upload.cancel": "Annuleren",
        "upload.submit": "Publiceer foto",
        "upload.saveChanges": "Wijzigingen opslaan",
        "upload.closeLabel": "Sluit uploadvenster",
        "upload.defaultTitle": "Nieuwe buurtfoto",
        "upload.defaultDescription": "Gedeeld vanuit de buurtcompetitie.",
        "upload.previewAlt": "Preview van de foto die je gaat publiceren",
        "lang.toggle": "Kies taal",
        "lang.option.nl": "Nederlands",
        "lang.option.en": "English"
    },
    en: {
        "meta.title": "Boules Competitions",
        "brand.subtitle": "COMPETITIONS",
        "nav.menu": "Open navigation menu",
        "nav.closeMenu": "Close navigation menu",
        "nav.primary": "Primary navigation",
        "nav.brandHome": "Boules Competitions home",
        "nav.home": "Home",
        "nav.competitions": "Competitions",
        "nav.about": "About us",
        "nav.how": "How it works",
        "nav.photos": "Photos",
        "nav.contact": "Contact",
        "auth.login": "Log in",
        "auth.logout": "Log out",
        "auth.signup": "Sign up",
        "auth.modal.close": "Close login dialog",
        "auth.modal.tabLogin": "Log in",
        "auth.modal.tabSignup": "Sign up",
        "auth.modal.loginKicker": "WELCOME BACK",
        "auth.modal.loginTitle": "Log in",
        "auth.modal.loginDescription": "Log in to continue and open the neighborhood competition.",
        "auth.modal.signupKicker": "JOIN IN",
        "auth.modal.signupTitle": "Sign up",
        "auth.modal.signupDescription": "Create your account and start with competitions, photos, and neighborhood moments right away.",
        "auth.modal.identityPlaceholder": "Username",
        "auth.modal.passwordPlaceholder": "Password",
        "auth.modal.namePlaceholder": "Full name",
        "auth.modal.emailPlaceholder": "Email address",
        "auth.modal.passwordConfirmPlaceholder": "Repeat password",
        "auth.modal.forgotPassword": "Forgot password?",
        "auth.modal.switchToSignupLead": "Don't have an account?",
        "auth.modal.switchToLoginLead": "Already have an account?",
        "auth.modal.showPassword": "Show password",
        "auth.modal.hidePassword": "Hide password",
        "auth.modal.feedback.loginMissing": "Enter your username and password.",
        "auth.modal.feedback.signupMissing": "Fill in all fields to create your account.",
        "auth.modal.feedback.passwordShort": "Use a password with at least 6 characters.",
        "auth.modal.feedback.passwordMismatch": "The passwords do not match.",
        "account.menuLabel": "Open account menu",
        "account.name": "Danny",
        "account.rolePlayer": "Player",
        "account.roleAdmin": "Admin",
        "account.rolePlayerDescription": "Can share photos and view neighborhood photos.",
        "account.roleAdminDescription": "Can manage and delete shared photos.",
        "hero.line1": "PLAY.",
        "hero.line2": "SHARE.",
        "hero.line3": "ENJOY.",
        "hero.description": "Bring the neighborhood together for a game of jeu de boules. Create matches and tournaments, track scores, and share photos with other players.",
        "hero.ctaPrimary": "Sign up",
        "hero.ctaSecondary": "View competitions",
        "hero.point1": "Play with neighbors or as a team",
        "hero.point2": "Organize tournaments and track scores",
        "hero.point3": "Share photos with the neighborhood",
        "hero.imageAlt": "Jeu de boules player during a match on the square",
        "competitions.heading": "UPCOMING COMPETITIONS",
        "competitions.card1.title": "Square Open",
        "competitions.card1.type": "Doublette | Neighbors & friends",
        "competitions.card1.date": "Starts: May 14, 2026",
        "competitions.card2.title": "Neighborhood Doubles",
        "competitions.card2.type": "Doublette | Open sign-up",
        "competitions.card2.date": "Starts: May 29, 2026",
        "competitions.card3.title": "Summer Tournament",
        "competitions.card3.type": "Triplette | Teams",
        "competitions.card3.date": "Starts: June 12, 2026",
        "competitions.more": "More info",
        "competitions.datePrefix": "Starts",
        "competitions.adminMode": "Admin mode: manage upcoming competitions",
        "competitions.add": "Add competition",
        "competitions.edit": "Edit",
        "competitions.delete": "Delete",
        "competitions.deleteConfirm": "Are you sure you want to delete this competition?",
        "competitions.empty": "There are no upcoming competitions yet.",
        "competitions.form.addKicker": "ADD COMPETITION",
        "competitions.form.addTitle": "Create an upcoming competition",
        "competitions.form.editKicker": "EDIT COMPETITION",
        "competitions.form.editTitle": "Update the upcoming competition",
        "competitions.form.description": "Fill in the key information so players know what is coming up.",
        "competitions.form.name": "Name",
        "competitions.form.namePlaceholder": "For example: Spring Tournament",
        "competitions.form.type": "Type",
        "competitions.form.typePlaceholder": "For example: Doublette | Open sign-up",
        "competitions.form.date": "Start date",
        "competitions.form.style": "Accent color",
        "competitions.form.styleGreen": "Green",
        "competitions.form.styleYellow": "Yellow",
        "competitions.form.styleRed": "Red",
        "competitions.form.styleOlive": "Olive",
        "competitions.form.cancel": "Cancel",
        "competitions.form.save": "Save competition",
        "competitions.form.closeLabel": "Close competition dialog",
        "challenge.title": "Ready to challenge the neighborhood?",
        "challenge.description": "Create a match or join the next tournament.",
        "challenge.cta": "Sign up",
        "leaderboard.heading": "LEADERBOARD",
        "leaderboard.viewAll": "View full leaderboard",
        "leaderboard.modalKicker": "FULL STANDINGS",
        "leaderboard.modalTitle": "Full leaderboard",
        "leaderboard.modalDescription": "See every team, matches played, and points in one overview.",
        "leaderboard.modalClose": "Close leaderboard dialog",
        "leaderboard.pointsUnit": "pts",
        "leaderboard.column.rank": "#",
        "leaderboard.column.team": "Team",
        "leaderboard.column.player": "Player",
        "leaderboard.teamFilter.all": "All teams",
        "leaderboard.teamFilter.aria": "Filter leaderboard by team",
        "leaderboard.column.played": "Played",
        "leaderboard.column.won": "Won",
        "leaderboard.column.diff": "Point diff",
        "leaderboard.column.points": "Points",
        "leaderboard.column.trend": "Trend",
        "leaderboard.trend.up": "Rising",
        "leaderboard.trend.flat": "Steady",
        "leaderboard.trend.down": "Falling",
        "leaderboard.playersOf": "Players of",
        "benefits.card1.title": "Built for the neighborhood",
        "benefits.card1.description": "Invite neighbors and play together on your own local square.",
        "benefits.card2.title": "Easy score tracking",
        "benefits.card2.description": "Update live scores without loose papers or confusion.",
        "benefits.card3.title": "Made for community",
        "benefits.card3.description": "Use the platform for matches, photos, and neighborhood moments.",
        "benefits.card4.title": "Create tournaments",
        "benefits.card4.description": "Set up a new local tournament in just a few steps.",
        "photos.kicker": "NEIGHBORHOOD GALLERY",
        "photos.heading": "Share match photos and see pictures from other players.",
        "photos.description": "Only logged-in players can publish and view photos. Share moments from practice sessions, tournaments, and relaxed neighborhood evenings.",
        "photos.visibility": "Visible only to logged-in players",
        "photos.publish": "Share a photo",
        "photos.lockedTitle": "Log in to view neighborhood photos",
        "photos.lockedDescription": "Once you are logged in, you can share photos and view pictures from other players.",
        "photos.lockedButton": "Log in to view photos",
        "photos.highlightTitle": "Let the neighborhood join in",
        "photos.highlightDescription": "Upload a photo from your match, practice session, or tournament. Other logged-in players will see it right away.",
        "photos.highlightButton": "Upload photo",
        "photos.adminMode": "Admin mode: you can remove shared photos",
        "photos.edit": "Edit",
        "photos.delete": "Delete",
        "photos.deleteConfirm": "Are you sure you want to delete this photo?",
        "photos.empty": "No photos have been shared yet. Be the first to publish one.",
        "photos.time.today": "Today",
        "photos.time.yesterday": "Yesterday",
        "footer.quote": "\" Jeu de boules is more than a game.<br>It is community, strategy, and enjoyment. \"",
        "upload.kicker": "PUBLISH PHOTO",
        "upload.title": "Give your photo a title and description",
        "upload.description": "Adjust the title and description before you publish the photo.",
        "upload.editKicker": "EDIT PHOTO",
        "upload.editTitle": "Update the title and description",
        "upload.editDescription": "Update the title and description of your own photo.",
        "upload.photoTitle": "Title",
        "upload.photoTitlePlaceholder": "For example: Final on the square",
        "upload.photoDescription": "Description",
        "upload.photoDescriptionPlaceholder": "Briefly describe what people can see in this photo",
        "upload.changePhoto": "Choose another photo",
        "upload.cancel": "Cancel",
        "upload.submit": "Publish photo",
        "upload.saveChanges": "Save changes",
        "upload.closeLabel": "Close upload dialog",
        "upload.defaultTitle": "New neighborhood photo",
        "upload.defaultDescription": "Shared from the neighborhood competition.",
        "upload.previewAlt": "Preview of the photo you are about to publish",
        "lang.toggle": "Choose language",
        "lang.option.nl": "Nederlands",
        "lang.option.en": "English"
    }
};

function makePlaceholder(author, title, colorA, colorB) {
    const svg = `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 700">
            <defs>
                <linearGradient id="gradient" x1="0" x2="1" y1="0" y2="1">
                    <stop offset="0%" stop-color="${colorA}" />
                    <stop offset="100%" stop-color="${colorB}" />
                </linearGradient>
            </defs>
            <rect width="800" height="700" fill="url(#gradient)" />
            <circle cx="560" cy="510" r="120" fill="rgba(255,255,255,0.5)" />
            <circle cx="250" cy="210" r="150" fill="rgba(255,255,255,0.16)" />
            <text x="60" y="520" fill="white" font-size="70" font-family="Arial" font-weight="700">${author}</text>
            <text x="60" y="595" fill="rgba(255,255,255,0.92)" font-size="34" font-family="Arial">${title}</text>
        </svg>
    `;

    return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`;
}

function hoursAgo(hours) {
    return Date.now() - hours * 60 * 60 * 1000;
}

function daysAgo(days) {
    return Date.now() - days * 24 * 60 * 60 * 1000;
}

const defaultPhotos = [
    {
        id: "seed-1",
        author: "Sanne",
        title: {
            nl: "Avondpartij op het plein",
            en: "Evening match on the square"
        },
        description: {
            nl: "De buren speelden nog een laatste partij terwijl de zon onderging.",
            en: "Neighbors played one last match while the sun was setting."
        },
        createdAt: hoursAgo(8),
        image: makePlaceholder("Sanne", "Avondpartij", "#7b9151", "#d8c8af")
    },
    {
        id: "seed-2",
        author: "Mo",
        title: {
            nl: "Finale van het buurttoernooi",
            en: "Neighborhood tournament final"
        },
        description: {
            nl: "Een spannende finale met veel publiek langs de baan.",
            en: "A tense final with a big crowd standing next to the court."
        },
        createdAt: daysAgo(1),
        image: makePlaceholder("Mo", "Toernooifinale", "#cb352c", "#f0c458")
    },
    {
        id: "seed-3",
        author: "Lisa",
        title: {
            nl: "Zondagmiddag met de buurt",
            en: "Sunday afternoon with the neighborhood"
        },
        description: {
            nl: "Een losse trainingsmiddag met nieuwe spelers en veel gezelligheid.",
            en: "An easy practice afternoon with new players and plenty of fun."
        },
        createdAt: daysAgo(6),
        image: makePlaceholder("Lisa", "Zondagmiddag", "#526833", "#e5dfcf")
    }
];

const competitionToneMap = {
    green: "fa-calendar-days",
    yellow: "fa-sun",
    red: "fa-trophy",
    olive: "fa-ranking-star"
};

const defaultCompetitions = [
    {
        id: "seed-comp-1",
        title: {
            nl: "Plein Open",
            en: "Square Open"
        },
        type: {
            nl: "Doublette | Buren & vrienden",
            en: "Doublette | Neighbors & friends"
        },
        startDate: "2026-05-14",
        tone: "green",
        href: "#competities"
    },
    {
        id: "seed-comp-2",
        title: {
            nl: "Buurt Dubbel",
            en: "Neighborhood Doubles"
        },
        type: {
            nl: "Doublette | Vrije inschrijving",
            en: "Doublette | Open sign-up"
        },
        startDate: "2026-05-29",
        tone: "yellow",
        href: "#competities"
    },
    {
        id: "seed-comp-3",
        title: {
            nl: "Zomer Toernooi",
            en: "Summer Tournament"
        },
        type: {
            nl: "Triplette | Teams",
            en: "Triplette | Teams"
        },
        startDate: "2026-06-12",
        tone: "red",
        href: "#competities"
    }
];

const defaultLeaderboard = [
    {
        team: "Les Boulistes",
        played: 14,
        won: 11,
        diff: 64,
        points: 1250,
        trend: "up",
        players: [
            { name: "Danny", played: 14 },
            { name: "Sanne", played: 13 },
            { name: "Mo", played: 11 },
            { name: "Lisa", played: 9 }
        ]
    },
    {
        team: "De Kugelkoningen",
        played: 14,
        won: 10,
        diff: 47,
        points: 1100,
        trend: "flat",
        players: [
            { name: "Noor", played: 14 },
            { name: "Bram", played: 12 },
            { name: "Youssef", played: 10 },
            { name: "Emma", played: 8 }
        ]
    },
    {
        team: "Team Cochonnet",
        played: 14,
        won: 9,
        diff: 31,
        points: 980,
        trend: "up",
        players: [
            { name: "Mila", played: 14 },
            { name: "Jens", played: 12 },
            { name: "Fatima", played: 11 },
            { name: "Rik", played: 7 }
        ]
    },
    {
        team: "Boule & Co",
        played: 14,
        won: 8,
        diff: 12,
        points: 860,
        trend: "down",
        players: [
            { name: "Iris", played: 13 },
            { name: "Koen", played: 12 },
            { name: "Ravi", played: 9 },
            { name: "Anne", played: 6 }
        ]
    },
    {
        team: "De Werpers",
        played: 14,
        won: 7,
        diff: 6,
        points: 750,
        trend: "flat",
        players: [
            { name: "Tessa", played: 14 },
            { name: "Milan", played: 11 },
            { name: "Omar", played: 10 },
            { name: "Kim", played: 8 }
        ]
    },
    {
        team: "Plein Precisie",
        played: 14,
        won: 7,
        diff: -3,
        points: 720,
        trend: "up",
        players: [
            { name: "Lotte", played: 12 },
            { name: "Freek", played: 11 },
            { name: "Amina", played: 9 },
            { name: "Tom", played: 7 }
        ]
    },
    {
        team: "Jeu de Ja",
        played: 14,
        won: 6,
        diff: -8,
        points: 680,
        trend: "down",
        players: [
            { name: "Eva", played: 13 },
            { name: "Sam", played: 10 },
            { name: "Bilal", played: 8 },
            { name: "Fleur", played: 6 }
        ]
    },
    {
        team: "De Gooiers",
        played: 14,
        won: 5,
        diff: -14,
        points: 610,
        trend: "flat",
        players: [
            { name: "Nina", played: 12 },
            { name: "Daan", played: 9 },
            { name: "Farid", played: 7 },
            { name: "Sofia", played: 5 }
        ]
    },
    {
        team: "Cafe Carreau",
        played: 14,
        won: 4,
        diff: -28,
        points: 540,
        trend: "down",
        players: [
            { name: "Roos", played: 11 },
            { name: "Gijs", played: 8 },
            { name: "Anouk", played: 6 },
            { name: "Pim", played: 4 }
        ]
    },
    {
        team: "Petanque Pioniers",
        played: 14,
        won: 3,
        diff: -41,
        points: 470,
        trend: "up",
        players: [
            { name: "Zoe", played: 10 },
            { name: "Lars", played: 7 },
            { name: "Ilias", played: 5 },
            { name: "Mara", played: 4 }
        ]
    }
];

function normalizeLanguage(language) {
    return language === "en" ? "en" : "nl";
}

function normalizeRole(role) {
    return role === "admin" ? "admin" : "player";
}

function readDeletedPhotoIds() {
    try {
        const storedIds = JSON.parse(localStorage.getItem(DELETED_PHOTOS_KEY) || "[]");
        return Array.isArray(storedIds) ? storedIds.map(String) : [];
    } catch (error) {
        console.error("Could not load deleted photo ids", error);
        return [];
    }
}

function readDeletedCompetitionIds() {
    try {
        const storedIds = JSON.parse(localStorage.getItem(COMPETITION_DELETED_KEY) || "[]");
        return Array.isArray(storedIds) ? storedIds.map(String) : [];
    } catch (error) {
        console.error("Could not load deleted competition ids", error);
        return [];
    }
}

function t(key) {
    return translations[state.lang][key] || translations.nl[key] || key;
}

function readStoredPhotos() {
    try {
        const storedPhotos = JSON.parse(localStorage.getItem(PHOTOS_KEY) || "[]");
        if (!Array.isArray(storedPhotos)) {
            return [];
        }

        return storedPhotos
            .map(normalizeStoredPhoto)
            .filter(Boolean)
            .filter((photo) => !String(photo.id).startsWith("seed-"));
    } catch (error) {
        console.error("Could not load stored photos", error);
        return [];
    }
}

function normalizeStoredPhoto(photo) {
    if (!photo || typeof photo !== "object" || typeof photo.image !== "string") {
        return null;
    }

    const rawTitle = typeof photo.title === "string" ? photo.title.trim() : "";
    const rawDescription = typeof photo.description === "string" ? photo.description.trim() : "";
    const legacyCaption = typeof photo.caption === "string" ? photo.caption.trim() : "";
    const createdAt = Number.isFinite(photo.createdAt) ? photo.createdAt : Date.now();

    return {
        id: typeof photo.id === "string" || typeof photo.id === "number" ? String(photo.id) : String(createdAt),
        author: typeof photo.author === "string" && photo.author.trim() ? photo.author.trim() : DEFAULT_AUTHOR,
        ownerId: typeof photo.ownerId === "string" && photo.ownerId.trim()
            ? photo.ownerId.trim()
            : (typeof photo.author === "string" && photo.author.trim() === DEFAULT_AUTHOR ? CURRENT_USER_ID : null),
        title: rawTitle || legacyCaption,
        description: rawDescription || "",
        createdAt,
        image: photo.image
    };
}

function normalizeCompetitionTone(tone) {
    return competitionToneMap[tone] ? tone : "green";
}

function normalizeStoredCompetition(competition) {
    if (!competition || typeof competition !== "object") {
        return null;
    }

    const title = typeof competition.title === "string" ? competition.title.trim() : "";
    const type = typeof competition.type === "string" ? competition.type.trim() : "";
    const startDate = typeof competition.startDate === "string" ? competition.startDate : "";

    if (!title || !type || !startDate) {
        return null;
    }

    return {
        id: typeof competition.id === "string" || typeof competition.id === "number" ? String(competition.id) : String(Date.now()),
        title,
        type,
        startDate,
        tone: normalizeCompetitionTone(competition.tone),
        href: typeof competition.href === "string" && competition.href.trim() ? competition.href.trim() : "#competities"
    };
}

function readStoredCompetitions() {
    try {
        const storedCompetitions = JSON.parse(localStorage.getItem(COMPETITION_CUSTOM_KEY) || "[]");
        return Array.isArray(storedCompetitions)
            ? storedCompetitions.map(normalizeStoredCompetition).filter(Boolean)
            : [];
    } catch (error) {
        console.error("Could not load stored competitions", error);
        return [];
    }
}

function readCompetitionEdits() {
    try {
        const storedEdits = JSON.parse(localStorage.getItem(COMPETITION_EDITS_KEY) || "{}");
        if (!storedEdits || typeof storedEdits !== "object" || Array.isArray(storedEdits)) {
            return {};
        }

        return Object.fromEntries(
            Object.entries(storedEdits)
                .map(([id, value]) => [String(id), normalizeStoredCompetition({ ...value, id })])
                .filter(([, value]) => Boolean(value))
        );
    } catch (error) {
        console.error("Could not load competition edits", error);
        return {};
    }
}

const state = {
    lang: normalizeLanguage(localStorage.getItem(LANG_KEY)),
    loggedIn: localStorage.getItem(AUTH_KEY) === "true",
    role: normalizeRole(localStorage.getItem(ROLE_KEY)),
    photos: readStoredPhotos(),
    deletedPhotoIds: readDeletedPhotoIds(),
    competitions: readStoredCompetitions(),
    competitionEdits: readCompetitionEdits(),
    deletedCompetitionIds: readDeletedCompetitionIds(),
    pendingUpload: null,
    pendingPhotoId: null,
    pendingCompetitionId: null,
    leaderboardTeamFilter: "all"
};

let languageMenuTimer = 0;
let accountMenuTimer = 0;
let uploadModalTimer = 0;
let competitionModalTimer = 0;
let authModalTimer = 0;
let leaderboardModalTimer = 0;
let scrollSpyFrame = 0;

const navSections = navLinks
    .map((link) => {
        const targetId = link.getAttribute("href")?.replace("#", "");
        if (!targetId) {
            return null;
        }

        const section = document.getElementById(targetId);
        if (!section) {
            return null;
        }

        return {
            id: targetId,
            link,
            section
        };
    })
    .filter(Boolean);

function savePhotos() {
    localStorage.setItem(PHOTOS_KEY, JSON.stringify(state.photos));
}

function saveDeletedPhotoIds() {
    localStorage.setItem(DELETED_PHOTOS_KEY, JSON.stringify(state.deletedPhotoIds));
}

function saveCompetitions() {
    localStorage.setItem(COMPETITION_CUSTOM_KEY, JSON.stringify(state.competitions));
}

function saveCompetitionEdits() {
    localStorage.setItem(COMPETITION_EDITS_KEY, JSON.stringify(state.competitionEdits));
}

function saveDeletedCompetitionIds() {
    localStorage.setItem(COMPETITION_DELETED_KEY, JSON.stringify(state.deletedCompetitionIds));
}

function canDeletePhoto(photo) {
    if (!state.loggedIn) {
        return false;
    }

    if (state.role === "admin") {
        return true;
    }

    return photo.ownerId === CURRENT_USER_ID;
}

function canEditPhoto(photo) {
    return state.loggedIn && photo.ownerId === CURRENT_USER_ID;
}

function buildPhotoCollection() {
    return [...state.photos, ...defaultPhotos]
        .filter((photo) => !state.deletedPhotoIds.includes(String(photo.id)))
        .slice()
        .sort((left, right) => right.createdAt - left.createdAt);
}

function buildCompetitionCollection() {
    const seededCompetitions = defaultCompetitions
        .filter((competition) => !state.deletedCompetitionIds.includes(String(competition.id)))
        .map((competition) => {
            const override = state.competitionEdits[String(competition.id)];
            return override ? { ...competition, ...override } : competition;
        });

    const customCompetitions = state.competitions
        .filter((competition) => !state.deletedCompetitionIds.includes(String(competition.id)));

    return [...seededCompetitions, ...customCompetitions]
        .slice()
        .sort((left, right) => left.startDate.localeCompare(right.startDate));
}

function getLocalizedPhotoField(value) {
    if (value && typeof value === "object") {
        return value[state.lang] || value.nl || value.en || "";
    }

    return typeof value === "string" ? value : "";
}

function formatPhotoTime(createdAt) {
    const photoDate = new Date(createdAt);
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    const photoDay = new Date(photoDate.getFullYear(), photoDate.getMonth(), photoDate.getDate());

    if (photoDay.getTime() === today.getTime()) {
        return t("photos.time.today");
    }

    if (photoDay.getTime() === yesterday.getTime()) {
        return t("photos.time.yesterday");
    }

    return new Intl.DateTimeFormat(localeMap[state.lang], {
        day: "numeric",
        month: "short",
        year: photoDate.getFullYear() === now.getFullYear() ? undefined : "numeric"
    }).format(photoDate);
}

function createPhotoCard(photo) {
    const card = document.createElement("article");
    card.className = "photo-card";

    if (canEditPhoto(photo) || canDeletePhoto(photo)) {
        const actions = document.createElement("div");
        actions.className = "photo-card-actions";

        if (canEditPhoto(photo)) {
            const editButton = document.createElement("button");
            editButton.type = "button";
            editButton.className = "photo-action-button photo-edit-button";
            editButton.dataset.photoEdit = String(photo.id);
            editButton.setAttribute("aria-label", t("photos.edit"));
            editButton.innerHTML = `<i class="fa-solid fa-pen"></i><span>${t("photos.edit")}</span>`;
            actions.appendChild(editButton);
        }

        if (canDeletePhoto(photo)) {
            const deleteButton = document.createElement("button");
            deleteButton.type = "button";
            deleteButton.className = "photo-delete-button";
            deleteButton.dataset.photoDelete = String(photo.id);
            deleteButton.setAttribute("aria-label", t("photos.delete"));
            deleteButton.innerHTML = `<i class="fa-solid fa-trash"></i><span>${t("photos.delete")}</span>`;
            actions.appendChild(deleteButton);
        }

        card.appendChild(actions);
    }

    const image = document.createElement("img");
    image.className = "photo-card-image";
    image.src = photo.image;
    image.alt = getLocalizedPhotoField(photo.title) || t("upload.previewAlt");

    const copy = document.createElement("div");
    copy.className = "photo-card-copy";

    const meta = document.createElement("div");
    meta.className = "photo-card-meta";

    const author = document.createElement("span");
    author.className = "photo-card-author";
    author.textContent = photo.author;

    const time = document.createElement("span");
    time.className = "photo-card-time";
    time.textContent = formatPhotoTime(photo.createdAt);

    const title = document.createElement("h3");
    title.className = "photo-card-title";
    title.textContent = getLocalizedPhotoField(photo.title) || t("upload.defaultTitle");

    const description = document.createElement("p");
    description.className = "photo-card-description";
    description.textContent = getLocalizedPhotoField(photo.description) || t("upload.defaultDescription");

    meta.append(author, time);
    copy.append(meta, title, description);
    card.append(image, copy);

    return card;
}

function renderPhotos() {
    if (!photoGrid) {
        return;
    }

    if (photoLocked) {
        photoLocked.hidden = state.loggedIn;
    }

    if (photoFeed) {
        photoFeed.hidden = !state.loggedIn;
    }

    photoGrid.innerHTML = "";

    if (!state.loggedIn) {
        return;
    }

    const allPhotos = buildPhotoCollection();

    if (allPhotos.length === 0) {
        const emptyState = document.createElement("div");
        emptyState.className = "photo-empty";

        const message = document.createElement("p");
        message.textContent = t("photos.empty");

        emptyState.appendChild(message);
        photoGrid.appendChild(emptyState);
        return;
    }

    allPhotos.forEach((photo) => {
        photoGrid.appendChild(createPhotoCard(photo));
    });
}

function canManageCompetitions() {
    return state.loggedIn && state.role === "admin";
}

function getCompetitionField(value) {
    if (value && typeof value === "object") {
        return value[state.lang] || value.nl || value.en || "";
    }

    return typeof value === "string" ? value : "";
}

function formatCompetitionDate(startDate) {
    if (!startDate) {
        return "";
    }

    const date = new Date(`${startDate}T12:00:00`);
    const formattedDate = new Intl.DateTimeFormat(localeMap[state.lang], {
        day: "numeric",
        month: "long",
        year: "numeric"
    }).format(date);

    return `${t("competitions.datePrefix")}: ${formattedDate}`;
}

function createCompetitionCard(competition) {
    const card = document.createElement("article");
    card.className = "competition-card";

    if (canManageCompetitions()) {
        const actions = document.createElement("div");
        actions.className = "competition-card-actions";

        const editButton = document.createElement("button");
        editButton.type = "button";
        editButton.className = "competition-action-button";
        editButton.dataset.competitionEdit = String(competition.id);
        editButton.setAttribute("aria-label", t("competitions.edit"));
        editButton.innerHTML = '<i class="fa-solid fa-pen"></i>';

        const deleteButton = document.createElement("button");
        deleteButton.type = "button";
        deleteButton.className = "competition-action-button is-delete";
        deleteButton.dataset.competitionDelete = String(competition.id);
        deleteButton.setAttribute("aria-label", t("competitions.delete"));
        deleteButton.innerHTML = '<i class="fa-solid fa-trash"></i>';

        actions.append(editButton, deleteButton);
        card.appendChild(actions);
    }

    const icon = document.createElement("div");
    icon.className = `competition-icon ${normalizeCompetitionTone(competition.tone)}`;
    icon.innerHTML = `<i class="fa-solid ${competitionToneMap[normalizeCompetitionTone(competition.tone)]}"></i>`;

    const title = document.createElement("h3");
    title.textContent = getCompetitionField(competition.title);

    const type = document.createElement("p");
    type.textContent = getCompetitionField(competition.type);

    const date = document.createElement("p");
    date.textContent = formatCompetitionDate(competition.startDate);

    const link = document.createElement("a");
    link.href = competition.href || "#competities";
    link.innerHTML = `<span>${t("competitions.more")}</span> <span aria-hidden="true">-&gt;</span>`;

    card.append(icon, title, type, date, link);
    return card;
}

function renderCompetitions() {
    if (!competitionGrid) {
        return;
    }

    competitionGrid.innerHTML = "";

    const competitions = buildCompetitionCollection();

    if (competitions.length === 0) {
        const emptyState = document.createElement("div");
        emptyState.className = "competition-empty";

        const message = document.createElement("p");
        message.textContent = t("competitions.empty");

        emptyState.appendChild(message);
        competitionGrid.appendChild(emptyState);
        return;
    }

    competitions.forEach((competition) => {
        competitionGrid.appendChild(createCompetitionCard(competition));
    });
}

function getLeaderboardCollection() {
    return defaultLeaderboard
        .slice()
        .sort((left, right) => right.points - left.points || right.diff - left.diff || right.won - left.won || left.team.localeCompare(right.team));
}

function getLeaderboardRankTone(rank) {
    if (rank === 1) {
        return "gold";
    }

    if (rank === 2) {
        return "silver";
    }

    if (rank === 3) {
        return "bronze";
    }

    return "olive";
}

function getLeaderboardTrendSymbol(trend) {
    if (trend === "up") {
        return "+";
    }

    if (trend === "down") {
        return "-";
    }

    return "=";
}

function getLeaderboardTrendLabel(trend) {
    const normalizedTrend = trend === "up" || trend === "down" ? trend : "flat";
    return t(`leaderboard.trend.${normalizedTrend}`);
}

function formatLeaderboardPoints(points) {
    return `${points} ${t("leaderboard.pointsUnit")}`;
}

function formatLeaderboardDiff(diff) {
    const normalizedDiff = Number(diff) || 0;
    return normalizedDiff > 0 ? `+${normalizedDiff}` : String(normalizedDiff);
}

function getSelectedLeaderboardTeam() {
    if (state.leaderboardTeamFilter === "all") {
        return null;
    }

    return getLeaderboardCollection().find((entry) => entry.team === state.leaderboardTeamFilter) || null;
}

function syncLeaderboardTeamFilter() {
    if (!leaderboardTeamFilter) {
        return;
    }

    const selectedValue = getSelectedLeaderboardTeam()?.team || "all";
    leaderboardTeamFilter.innerHTML = "";
    leaderboardTeamFilter.setAttribute("aria-label", t("leaderboard.teamFilter.aria"));

    const allOption = document.createElement("option");
    allOption.value = "all";
    allOption.textContent = t("leaderboard.teamFilter.all");
    leaderboardTeamFilter.appendChild(allOption);

    getLeaderboardCollection().forEach((entry) => {
        const option = document.createElement("option");
        option.value = entry.team;
        option.textContent = entry.team;
        leaderboardTeamFilter.appendChild(option);
    });

    leaderboardTeamFilter.value = selectedValue;
}

function getLeaderboardUpdatedLabel() {
    const selectedTeam = getSelectedLeaderboardTeam();
    if (selectedTeam) {
        return `${t("leaderboard.playersOf")} ${selectedTeam.team}`;
    }

    const round = getLeaderboardCollection().reduce((maxRound, entry) => Math.max(maxRound, Number(entry.played) || 0), 0);
    return state.lang === "en" ? `Updated after matchday ${round}` : `Bijgewerkt na speelronde ${round}`;
}

function createLeaderboardPreviewItem(entry, rank) {
    const item = document.createElement("li");

    const rankBadge = document.createElement("span");
    rankBadge.className = `rank ${getLeaderboardRankTone(rank)}`;
    rankBadge.textContent = String(rank);

    const team = document.createElement("span");
    team.className = "team";
    team.textContent = entry.team;

    const points = document.createElement("span");
    points.className = "points";
    points.textContent = formatLeaderboardPoints(entry.points);

    const trend = document.createElement("span");
    const trendState = entry.trend === "up" || entry.trend === "down" ? entry.trend : "flat";
    trend.className = `trend ${trendState}`;
    trend.textContent = getLeaderboardTrendSymbol(trendState);
    trend.setAttribute("aria-label", getLeaderboardTrendLabel(trendState));
    trend.title = getLeaderboardTrendLabel(trendState);

    item.append(rankBadge, team, points, trend);
    return item;
}

function createLeaderboardTableRow(entry, rank) {
    const row = document.createElement("tr");
    const trendState = entry.trend === "up" || entry.trend === "down" ? entry.trend : "flat";

    const rankCell = document.createElement("td");
    rankCell.dataset.label = t("leaderboard.column.rank");
    const rankBadge = document.createElement("span");
    rankBadge.className = `rank ${getLeaderboardRankTone(rank)}`;
    rankBadge.textContent = String(rank);
    rankCell.appendChild(rankBadge);

    const teamCell = document.createElement("td");
    teamCell.className = "leaderboard-table-team";
    teamCell.dataset.label = t("leaderboard.column.team");
    const teamName = document.createElement("strong");
    teamName.textContent = entry.team;
    teamCell.appendChild(teamName);

    const playedCell = document.createElement("td");
    playedCell.dataset.label = t("leaderboard.column.played");
    playedCell.textContent = String(entry.played);

    const wonCell = document.createElement("td");
    wonCell.dataset.label = t("leaderboard.column.won");
    wonCell.textContent = String(entry.won);

    const diffCell = document.createElement("td");
    diffCell.dataset.label = t("leaderboard.column.diff");
    diffCell.textContent = formatLeaderboardDiff(entry.diff);

    const pointsCell = document.createElement("td");
    pointsCell.className = "leaderboard-table-points";
    pointsCell.dataset.label = t("leaderboard.column.points");
    pointsCell.textContent = formatLeaderboardPoints(entry.points);

    const trendCell = document.createElement("td");
    trendCell.dataset.label = t("leaderboard.column.trend");
    const trendBadge = document.createElement("span");
    trendBadge.className = `leaderboard-trend-badge ${trendState}`;
    trendBadge.innerHTML = `<span aria-hidden="true">${getLeaderboardTrendSymbol(trendState)}</span><span>${getLeaderboardTrendLabel(trendState)}</span>`;
    trendCell.appendChild(trendBadge);

    row.append(rankCell, teamCell, playedCell, wonCell, diffCell, pointsCell, trendCell);
    return row;
}

function createLeaderboardPlayerRow(player, rank, teamName) {
    const row = document.createElement("tr");

    const rankCell = document.createElement("td");
    rankCell.dataset.label = t("leaderboard.column.rank");
    const rankBadge = document.createElement("span");
    rankBadge.className = "rank olive";
    rankBadge.textContent = String(rank);
    rankCell.appendChild(rankBadge);

    const playerCell = document.createElement("td");
    playerCell.className = "leaderboard-table-team";
    playerCell.dataset.label = t("leaderboard.column.player");
    const playerName = document.createElement("strong");
    playerName.textContent = player.name;
    const playerTeam = document.createElement("small");
    playerTeam.className = "leaderboard-table-subcopy";
    playerTeam.textContent = teamName;
    playerCell.append(playerName, playerTeam);

    const playedCell = document.createElement("td");
    playedCell.dataset.label = t("leaderboard.column.played");
    playedCell.textContent = String(player.played);

    const wonCell = document.createElement("td");
    wonCell.dataset.label = t("leaderboard.column.won");
    wonCell.textContent = "";

    const diffCell = document.createElement("td");
    diffCell.dataset.label = t("leaderboard.column.diff");
    diffCell.textContent = "";

    const pointsCell = document.createElement("td");
    pointsCell.className = "leaderboard-table-points";
    pointsCell.dataset.label = t("leaderboard.column.points");
    pointsCell.textContent = "";

    const trendCell = document.createElement("td");
    trendCell.dataset.label = t("leaderboard.column.trend");
    trendCell.textContent = "";

    row.append(rankCell, playerCell, playedCell, wonCell, diffCell, pointsCell, trendCell);
    return row;
}

function renderLeaderboard() {
    const leaderboard = getLeaderboardCollection();
    const selectedTeam = getSelectedLeaderboardTeam();

    if (leaderboardPreviewList) {
        leaderboardPreviewList.innerHTML = "";
        leaderboard.slice(0, 5).forEach((entry, index) => {
            leaderboardPreviewList.appendChild(createLeaderboardPreviewItem(entry, index + 1));
        });
    }

    syncLeaderboardTeamFilter();

    if (leaderboardTable) {
        leaderboardTable.dataset.mode = selectedTeam ? "players" : "teams";
    }

    if (leaderboardTableBody) {
        leaderboardTableBody.innerHTML = "";

        if (selectedTeam) {
            selectedTeam.players
                .slice()
                .sort((left, right) => right.played - left.played || left.name.localeCompare(right.name))
                .forEach((player, index) => {
                    leaderboardTableBody.appendChild(createLeaderboardPlayerRow(player, index + 1, selectedTeam.team));
                });
        } else {
            leaderboard.forEach((entry, index) => {
                leaderboardTableBody.appendChild(createLeaderboardTableRow(entry, index + 1));
            });
        }
    }

    if (leaderboardUpdatedText) {
        leaderboardUpdatedText.textContent = getLeaderboardUpdatedLabel();
    }
}

function setCurrentNavLink(targetId) {
    navLinks.forEach((link) => {
        const isCurrent = link.getAttribute("href") === `#${targetId}`;
        link.classList.toggle("is-current", isCurrent);
    });
}

function updateActiveNavLink() {
    if (navSections.length === 0) {
        return;
    }

    const headerOffset = (siteHeader?.offsetHeight || 0) + 120;
    const scrollMarker = window.scrollY + headerOffset;
    let activeSection = navSections[0];

    navSections.forEach((entry) => {
        if (entry.section.offsetTop <= scrollMarker) {
            activeSection = entry;
        }
    });

    const scrollBottom = window.scrollY + window.innerHeight;
    if (scrollBottom >= document.documentElement.scrollHeight - 4) {
        activeSection = navSections[navSections.length - 1];
    }

    setCurrentNavLink(activeSection.id);
}

function queueScrollSpyUpdate() {
    if (scrollSpyFrame) {
        return;
    }

    scrollSpyFrame = window.requestAnimationFrame(() => {
        scrollSpyFrame = 0;
        updateActiveNavLink();
    });
}

function syncNavToggleLabel() {
    if (!navToggle) {
        return;
    }

    navToggle.setAttribute("aria-label", body.classList.contains("nav-open") ? t("nav.closeMenu") : t("nav.menu"));
}

function setNavOpen(isOpen) {
    body.classList.toggle("nav-open", isOpen);

    if (navToggle) {
        navToggle.setAttribute("aria-expanded", String(isOpen));
    }

    if (!isOpen) {
        closeLanguageMenu();
        closeAccountMenu();
    }

    syncNavToggleLabel();
}

function closeMobileMenu() {
    setNavOpen(false);
}

function setLanguageMenuOpen(isOpen) {
    if (!langSwitcher || !langToggle || !langMenu) {
        return;
    }

    clearTimeout(languageMenuTimer);
    langToggle.setAttribute("aria-expanded", String(isOpen));

    if (isOpen) {
        closeAccountMenu();
        langMenu.hidden = false;
        requestAnimationFrame(() => {
            langSwitcher.classList.add("is-open");
        });
        return;
    }

    langSwitcher.classList.remove("is-open");
    languageMenuTimer = window.setTimeout(() => {
        if (!langSwitcher.classList.contains("is-open")) {
            langMenu.hidden = true;
        }
    }, 180);
}

function closeLanguageMenu() {
    setLanguageMenuOpen(false);
}

function setAccountMenuOpen(isOpen) {
    if (!accountSwitcher || !accountToggle || !accountMenu) {
        return;
    }

    if (isOpen && !state.loggedIn) {
        return;
    }

    clearTimeout(accountMenuTimer);
    accountToggle.setAttribute("aria-expanded", String(isOpen));

    if (isOpen) {
        closeLanguageMenu();
        accountMenu.hidden = false;
        requestAnimationFrame(() => {
            accountSwitcher.classList.add("is-open");
        });
        return;
    }

    accountSwitcher.classList.remove("is-open");
    accountMenuTimer = window.setTimeout(() => {
        if (!accountSwitcher.classList.contains("is-open")) {
            accountMenu.hidden = true;
        }
    }, 180);
}

function closeAccountMenu() {
    setAccountMenuOpen(false);
}

function syncLanguageUI() {
    if (langCurrent) {
        langCurrent.textContent = state.lang.toUpperCase();
    }

    if (langToggle) {
        langToggle.setAttribute("aria-label", t("lang.toggle"));
    }

    langOptions.forEach((option) => {
        const optionLang = normalizeLanguage(option.dataset.langOption);
        option.textContent = t(`lang.option.${optionLang}`);
        option.classList.toggle("is-active", optionLang === state.lang);
    });
}

function syncAccountUI() {
    const roleKey = state.role === "admin" ? "account.roleAdmin" : "account.rolePlayer";

    if (signupCta) {
        signupCta.hidden = state.loggedIn;
    }

    if (accountSwitcher) {
        accountSwitcher.hidden = !state.loggedIn;
    }

    if (accountToggle) {
        accountToggle.setAttribute("aria-label", t("account.menuLabel"));
    }

    if (!state.loggedIn && accountSwitcher && accountMenu && accountToggle) {
        accountSwitcher.classList.remove("is-open");
        accountMenu.hidden = true;
        accountToggle.setAttribute("aria-expanded", "false");
    }

    if (accountName) {
        accountName.textContent = t("account.name");
    }

    if (accountRole) {
        accountRole.textContent = t(roleKey);
    }

    roleOptions.forEach((option) => {
        option.classList.toggle("is-active", normalizeRole(option.dataset.roleOption) === state.role);
    });

    if (adminIndicator) {
        adminIndicator.hidden = !(state.loggedIn && state.role === "admin");
    }
}

function syncCompetitionAdminUI() {
    const canManage = canManageCompetitions();

    if (competitionAdminIndicator) {
        competitionAdminIndicator.hidden = !canManage;
    }

    if (competitionCreateButton) {
        competitionCreateButton.hidden = !canManage;
    }
}

function syncAuthUI() {
    const authLabel = state.loggedIn ? t("auth.logout") : t("auth.login");

    authToggles.forEach((toggle) => {
        toggle.textContent = authLabel;
    });

    if (lockedLoginButton) {
        lockedLoginButton.textContent = t("photos.lockedButton");
    }

    uploadTriggers.forEach((trigger) => {
        trigger.classList.toggle("is-disabled", !state.loggedIn);
        trigger.setAttribute("aria-disabled", String(!state.loggedIn));
    });

    syncAccountUI();
    syncCompetitionAdminUI();
}

function syncAuthModalUI() {
    const currentMode = authForms.find((form) => form.classList.contains("is-active"))?.dataset.authForm === "signup" ? "signup" : "login";

    if (authCloseButtons[0]) {
        authCloseButtons[0].setAttribute("aria-label", t("auth.modal.close"));
    }

    authTabs.forEach((tab) => {
        tab.textContent = t(tab.dataset.authTab === "signup" ? "auth.modal.tabSignup" : "auth.modal.tabLogin");
    });

    authSwitchButtons.forEach((button) => {
        button.textContent = t(button.dataset.authSwitch === "signup" ? "auth.modal.tabSignup" : "auth.modal.tabLogin");
    });

    if (authForgotPassword) {
        authForgotPassword.textContent = t("auth.modal.forgotPassword");
    }

    if (authSubmitLogin) {
        authSubmitLogin.textContent = t("auth.modal.tabLogin");
    }

    if (authSubmitSignup) {
        authSubmitSignup.textContent = t("auth.signup");
    }

    if (authSwitchCopyLogin) {
        authSwitchCopyLogin.textContent = t("auth.modal.switchToSignupLead");
    }

    if (authSwitchCopySignup) {
        authSwitchCopySignup.textContent = t("auth.modal.switchToLoginLead");
    }

    if (authLoginIdentityInput) {
        authLoginIdentityInput.placeholder = t("auth.modal.identityPlaceholder");
    }

    if (authLoginPasswordInput) {
        authLoginPasswordInput.placeholder = t("auth.modal.passwordPlaceholder");
    }

    if (authSignupNameInput) {
        authSignupNameInput.placeholder = t("auth.modal.namePlaceholder");
    }

    if (authSignupEmailInput) {
        authSignupEmailInput.placeholder = t("auth.modal.emailPlaceholder");
    }

    if (authSignupPasswordInput) {
        authSignupPasswordInput.placeholder = t("auth.modal.passwordPlaceholder");
    }

    if (authSignupPasswordConfirmInput) {
        authSignupPasswordConfirmInput.placeholder = t("auth.modal.passwordConfirmPlaceholder");
    }

    authPasswordToggles.forEach((toggle) => {
        const isActive = toggle.classList.contains("is-active");
        toggle.setAttribute("aria-label", t(isActive ? "auth.modal.hidePassword" : "auth.modal.showPassword"));
    });

    setAuthMode(currentMode);
}

function applyTranslations() {
    html.lang = state.lang;
    document.title = t("meta.title");

    document.querySelectorAll("[data-i18n]").forEach((element) => {
        element.textContent = t(element.dataset.i18n);
    });

    document.querySelectorAll("[data-i18n-html]").forEach((element) => {
        element.innerHTML = t(element.dataset.i18nHtml);
    });

    document.querySelectorAll("[data-i18n-placeholder]").forEach((element) => {
        element.placeholder = t(element.dataset.i18nPlaceholder);
    });

    document.querySelectorAll("[data-i18n-aria-label]").forEach((element) => {
        element.setAttribute("aria-label", t(element.dataset.i18nAriaLabel));
    });

    document.querySelectorAll("[data-i18n-alt]").forEach((element) => {
        element.setAttribute("alt", t(element.dataset.i18nAlt));
    });

    syncNavToggleLabel();
    syncLanguageUI();
    syncAuthUI();
    syncAuthModalUI();
    syncUploadModalUI();
    syncCompetitionFormUI();
    renderLeaderboard();
    renderCompetitions();
    renderPhotos();

    if (uploadPreview && state.pendingUpload && !uploadTitleInput?.value.trim()) {
        uploadPreview.alt = t("upload.previewAlt");
    }
}

function setLanguage(language) {
    state.lang = normalizeLanguage(language);
    localStorage.setItem(LANG_KEY, state.lang);
    applyTranslations();
}

function setRole(role) {
    state.role = normalizeRole(role);
    localStorage.setItem(ROLE_KEY, state.role);

    if (state.role !== "admin") {
        closeCompetitionModal();
    }

    syncAccountUI();
    syncCompetitionAdminUI();
    renderCompetitions();
    renderPhotos();
}

function setLoggedIn(loggedIn) {
    state.loggedIn = loggedIn;
    localStorage.setItem(AUTH_KEY, String(loggedIn));
    body.classList.toggle("is-logged-in", loggedIn);

    if (!loggedIn) {
        closeAuthModal();
        closeUploadModal();
        closeCompetitionModal();
        closeLeaderboardModal();
        closeAccountMenu();
    }

    syncAuthUI();
    renderLeaderboard();
    renderCompetitions();
    renderPhotos();
}

function setAuthMode(mode) {
    const nextMode = mode === "signup" ? "signup" : "login";

    authTabs.forEach((tab) => {
        tab.classList.toggle("is-active", tab.dataset.authTab === nextMode);
    });

    authForms.forEach((form) => {
        const isActive = form.dataset.authForm === nextMode;
        form.hidden = !isActive;
        form.classList.toggle("is-active", isActive);
    });

    authFeedbackElements.forEach((element) => {
        element.textContent = "";
        element.classList.remove("is-success");
    });

    if (authModalKicker) {
        authModalKicker.textContent = t(nextMode === "signup" ? "auth.modal.signupKicker" : "auth.modal.loginKicker");
    }

    if (authModalTitle) {
        authModalTitle.textContent = t(nextMode === "signup" ? "auth.modal.signupTitle" : "auth.modal.loginTitle");
    }

    if (authModalDescription) {
        authModalDescription.textContent = t(nextMode === "signup" ? "auth.modal.signupDescription" : "auth.modal.loginDescription");
    }
}

function openAuthModal(mode = "login") {
    if (!authModal || state.loggedIn) {
        return;
    }

    clearTimeout(authModalTimer);
    setAuthMode(mode);
    authModal.hidden = false;
    body.classList.add("auth-modal-open");

    requestAnimationFrame(() => {
        authModal.classList.add("is-open");
    });

    window.setTimeout(() => {
        if (!authModal || authModal.hidden) {
            return;
        }

        const activeForm = authForms.find((form) => form.dataset.authForm === (mode === "signup" ? "signup" : "login"));
        const firstInput = activeForm?.querySelector("input");
        firstInput?.focus();
    }, 120);
}

function closeAuthModal() {
    if (!authModal || authModal.hidden) {
        return;
    }

    clearTimeout(authModalTimer);
    authModal.classList.remove("is-open");
    body.classList.remove("auth-modal-open");

    authModalTimer = window.setTimeout(() => {
        authModal.hidden = true;

        authForms.forEach((form) => {
            form.reset();
        });

        authFeedbackElements.forEach((element) => {
            element.textContent = "";
            element.classList.remove("is-success");
        });

        authPasswordToggles.forEach((toggle) => {
            toggle.classList.remove("is-active");
            const icon = toggle.querySelector("i");
            if (icon) {
                icon.className = "fa-regular fa-eye";
            }
        });

        authForms.forEach((form) => {
            form.querySelectorAll("input[type='text'], input[type='email']").forEach((input) => {
                input.value = input.value.trim();
            });

            form.querySelectorAll("input[type='text'], input[type='email'], input[type='password']").forEach((input) => {
                if (input instanceof HTMLInputElement && input.dataset.authInputOriginalType) {
                    input.type = input.dataset.authInputOriginalType;
                }
            });
        });
    }, 220);
}

function setAuthFeedback(mode, message, isSuccess = false) {
    const feedback = authFeedbackElements.find((element) => element.dataset.authFeedback === mode);
    if (!feedback) {
        return;
    }

    feedback.textContent = message;
    feedback.classList.toggle("is-success", isSuccess);
}

function sanitizeFilename(fileName) {
    return fileName
        .replace(/\.[^.]+$/, "")
        .replace(/[_-]+/g, " ")
        .replace(/\s+/g, " ")
        .trim();
}

function generateUploadId() {
    return globalThis.crypto && typeof globalThis.crypto.randomUUID === "function"
        ? globalThis.crypto.randomUUID()
        : String(Date.now());
}

function getStoredPhotoById(photoId) {
    return state.photos.find((photo) => String(photo.id) === String(photoId)) || null;
}

function syncUploadModalUI() {
    const isEditing = Boolean(state.pendingPhotoId);

    if (uploadKickerText) {
        uploadKickerText.textContent = t(isEditing ? "upload.editKicker" : "upload.kicker");
    }

    if (uploadTitleText) {
        uploadTitleText.textContent = t(isEditing ? "upload.editTitle" : "upload.title");
    }

    if (uploadDescriptionText) {
        uploadDescriptionText.textContent = t(isEditing ? "upload.editDescription" : "upload.description");
    }

    if (uploadSubmitLabel) {
        uploadSubmitLabel.textContent = t(isEditing ? "upload.saveChanges" : "upload.submit");
    }

    if (uploadChangeButton) {
        uploadChangeButton.hidden = isEditing;
    }
}

function setPendingUploadImage(imageData, fileName) {
    const previousSuggestedTitle = sanitizeFilename(state.pendingUpload?.fileName || "");
    const nextSuggestedTitle = sanitizeFilename(fileName);
    const currentTitle = uploadTitleInput?.value.trim() || "";

    state.pendingUpload = {
        ...(state.pendingUpload || {}),
        image: imageData,
        fileName
    };

    if (uploadPreview) {
        uploadPreview.src = imageData;
        uploadPreview.alt = currentTitle || t("upload.previewAlt");
    }

    if (uploadTitleInput && (!currentTitle || currentTitle === previousSuggestedTitle)) {
        uploadTitleInput.value = nextSuggestedTitle;
    }
}

function resetPendingUpload() {
    state.pendingUpload = null;
    state.pendingPhotoId = null;

    if (uploadPreview) {
        uploadPreview.src = "";
        uploadPreview.alt = "";
    }

    if (uploadForm) {
        uploadForm.reset();
    }

    if (photoInput) {
        photoInput.value = "";
    }

    syncUploadModalUI();
}

function openUploadModal(imageData, fileName) {
    if (!uploadModal || !uploadPreview || !uploadTitleInput || !uploadDescriptionInput) {
        return;
    }

    clearTimeout(uploadModalTimer);

    state.pendingPhotoId = null;
    state.pendingUpload = null;
    setPendingUploadImage(imageData, fileName);
    uploadDescriptionInput.value = "";
    syncUploadModalUI();

    uploadModal.hidden = false;
    body.classList.add("upload-modal-open");

    requestAnimationFrame(() => {
        uploadModal.classList.add("is-open");
    });

    window.setTimeout(() => {
        if (uploadModal && !uploadModal.hidden) {
            uploadTitleInput.focus();
            uploadTitleInput.select();
        }
    }, 120);
}

function openPhotoEditModal(photoId) {
    const photo = getStoredPhotoById(photoId);
    if (!photo || !canEditPhoto(photo) || !uploadModal || !uploadPreview || !uploadTitleInput || !uploadDescriptionInput) {
        return;
    }

    clearTimeout(uploadModalTimer);

    state.pendingPhotoId = String(photo.id);
    state.pendingUpload = {
        image: photo.image,
        fileName: ""
    };

    uploadPreview.src = photo.image;
    uploadPreview.alt = photo.title || t("upload.previewAlt");
    uploadTitleInput.value = typeof photo.title === "string" ? photo.title : "";
    uploadDescriptionInput.value = typeof photo.description === "string" ? photo.description : "";
    syncUploadModalUI();

    uploadModal.hidden = false;
    body.classList.add("upload-modal-open");

    requestAnimationFrame(() => {
        uploadModal.classList.add("is-open");
    });

    window.setTimeout(() => {
        if (uploadModal && !uploadModal.hidden) {
            uploadTitleInput.focus();
            uploadTitleInput.select();
        }
    }, 120);
}

function closeUploadModal() {
    if (!uploadModal || uploadModal.hidden) {
        resetPendingUpload();
        return;
    }

    clearTimeout(uploadModalTimer);
    uploadModal.classList.remove("is-open");
    body.classList.remove("upload-modal-open");

    uploadModalTimer = window.setTimeout(() => {
        uploadModal.hidden = true;
        resetPendingUpload();
    }, 220);
}

function getCompetitionById(competitionId) {
    return buildCompetitionCollection().find((competition) => String(competition.id) === String(competitionId)) || null;
}

function syncCompetitionFormUI() {
    const isEditing = Boolean(state.pendingCompetitionId);

    if (competitionFormKicker) {
        competitionFormKicker.textContent = t(isEditing ? "competitions.form.editKicker" : "competitions.form.addKicker");
    }

    if (competitionFormTitle) {
        competitionFormTitle.textContent = t(isEditing ? "competitions.form.editTitle" : "competitions.form.addTitle");
    }

    if (competitionFormDescription) {
        competitionFormDescription.textContent = t("competitions.form.description");
    }

    if (competitionSubmitLabel) {
        competitionSubmitLabel.textContent = t("competitions.form.save");
    }
}

function resetCompetitionForm() {
    state.pendingCompetitionId = null;

    if (competitionForm) {
        competitionForm.reset();
    }

    if (competitionToneInput) {
        competitionToneInput.value = "green";
    }
}

function openCompetitionModal(competitionId = null) {
    if (!canManageCompetitions() || !competitionModal || !competitionNameInput || !competitionTypeInput || !competitionDateInput || !competitionToneInput) {
        return;
    }

    clearTimeout(competitionModalTimer);

    state.pendingCompetitionId = competitionId ? String(competitionId) : null;

    const competition = state.pendingCompetitionId ? getCompetitionById(state.pendingCompetitionId) : null;

    competitionNameInput.value = competition ? getCompetitionField(competition.title) : "";
    competitionTypeInput.value = competition ? getCompetitionField(competition.type) : "";
    competitionDateInput.value = competition?.startDate || "";
    competitionToneInput.value = competition ? normalizeCompetitionTone(competition.tone) : "green";

    syncCompetitionFormUI();

    competitionModal.hidden = false;
    body.classList.add("competition-modal-open");

    requestAnimationFrame(() => {
        competitionModal.classList.add("is-open");
    });

    window.setTimeout(() => {
        if (competitionModal && !competitionModal.hidden) {
            competitionNameInput.focus();
            competitionNameInput.select();
        }
    }, 120);
}

function closeCompetitionModal() {
    if (!competitionModal || competitionModal.hidden) {
        resetCompetitionForm();
        return;
    }

    clearTimeout(competitionModalTimer);
    competitionModal.classList.remove("is-open");
    body.classList.remove("competition-modal-open");

    competitionModalTimer = window.setTimeout(() => {
        competitionModal.hidden = true;
        resetCompetitionForm();
        syncCompetitionFormUI();
    }, 220);
}

function openLeaderboardModal() {
    if (!leaderboardModal) {
        return;
    }

    clearTimeout(leaderboardModalTimer);
    leaderboardModal.hidden = false;
    body.classList.add("leaderboard-modal-open");

    requestAnimationFrame(() => {
        leaderboardModal.classList.add("is-open");
    });

    window.setTimeout(() => {
        if (leaderboardModal && !leaderboardModal.hidden) {
            leaderboardModalCloseButton?.focus();
        }
    }, 120);
}

function closeLeaderboardModal() {
    if (!leaderboardModal || leaderboardModal.hidden) {
        return;
    }

    clearTimeout(leaderboardModalTimer);
    leaderboardModal.classList.remove("is-open");
    body.classList.remove("leaderboard-modal-open");

    leaderboardModalTimer = window.setTimeout(() => {
        leaderboardModal.hidden = true;
    }, 220);
}

function saveCompetition(event) {
    event.preventDefault();

    if (!canManageCompetitions() || !competitionNameInput || !competitionTypeInput || !competitionDateInput || !competitionToneInput) {
        closeCompetitionModal();
        return;
    }

    const title = competitionNameInput.value.trim();
    const type = competitionTypeInput.value.trim();
    const startDate = competitionDateInput.value;
    const tone = normalizeCompetitionTone(competitionToneInput.value);

    if (!title) {
        competitionNameInput.focus();
        return;
    }

    if (!type) {
        competitionTypeInput.focus();
        return;
    }

    if (!startDate) {
        competitionDateInput.focus();
        return;
    }

    const competitionData = {
        title,
        type,
        startDate,
        tone,
        href: "#competities"
    };

    if (state.pendingCompetitionId) {
        if (String(state.pendingCompetitionId).startsWith("seed-comp-")) {
            state.competitionEdits[String(state.pendingCompetitionId)] = {
                id: String(state.pendingCompetitionId),
                ...competitionData
            };
            saveCompetitionEdits();
        } else {
            state.competitions = state.competitions.map((competition) => (
                String(competition.id) === String(state.pendingCompetitionId)
                    ? { ...competition, ...competitionData }
                    : competition
            ));
            saveCompetitions();
        }
    } else {
        state.competitions.unshift({
            id: generateUploadId(),
            ...competitionData
        });
        saveCompetitions();
    }

    renderCompetitions();
    closeCompetitionModal();
}

function deleteCompetition(competitionId) {
    if (!canManageCompetitions()) {
        return;
    }

    const competition = getCompetitionById(competitionId);
    if (!competition) {
        return;
    }

    if (!window.confirm(t("competitions.deleteConfirm"))) {
        return;
    }

    if (String(competitionId).startsWith("seed-comp-")) {
        delete state.competitionEdits[String(competitionId)];
        if (!state.deletedCompetitionIds.includes(String(competitionId))) {
            state.deletedCompetitionIds.push(String(competitionId));
        }
        saveCompetitionEdits();
        saveDeletedCompetitionIds();
    } else {
        state.competitions = state.competitions.filter((entry) => String(entry.id) !== String(competitionId));
        saveCompetitions();
    }

    renderCompetitions();
}

function publishPendingPhoto(event) {
    event.preventDefault();

    if (!state.pendingUpload || !state.loggedIn) {
        closeUploadModal();
        return;
    }

    const title = uploadTitleInput?.value.trim() || t("upload.defaultTitle");
    const description = uploadDescriptionInput?.value.trim() || t("upload.defaultDescription");

    if (state.pendingPhotoId) {
        state.photos = state.photos.map((photo) => (
            String(photo.id) === String(state.pendingPhotoId)
                ? {
                    ...photo,
                    title,
                    description,
                    image: state.pendingUpload.image
                }
                : photo
        ));
    } else {
        state.photos.unshift({
            id: generateUploadId(),
            author: DEFAULT_AUTHOR,
            ownerId: CURRENT_USER_ID,
            title,
            description,
            createdAt: Date.now(),
            image: state.pendingUpload.image
        });
    }

    savePhotos();
    renderPhotos();
    closeUploadModal();
}

function deletePhoto(photoId) {
    const allPhotos = buildPhotoCollection();
    const photo = allPhotos.find((entry) => String(entry.id) === String(photoId));

    if (!photo || !canDeletePhoto(photo)) {
        return;
    }

    if (!window.confirm(t("photos.deleteConfirm"))) {
        return;
    }

    const isSeedPhoto = String(photo.id).startsWith("seed-");

    if (isSeedPhoto) {
        if (!state.deletedPhotoIds.includes(String(photo.id))) {
            state.deletedPhotoIds.push(String(photo.id));
            saveDeletedPhotoIds();
        }
    } else {
        state.photos = state.photos.filter((entry) => String(entry.id) !== String(photoId));
        savePhotos();
    }

    renderPhotos();
}

navToggle?.addEventListener("click", () => {
    setNavOpen(!body.classList.contains("nav-open"));
});

navLinks.forEach((link) => {
    link.addEventListener("click", () => {
        const targetId = link.getAttribute("href")?.replace("#", "");
        if (targetId) {
            setCurrentNavLink(targetId);
        }

        if (window.innerWidth <= 920) {
            closeMobileMenu();
        }
    });
});

authToggles.forEach((toggle) => {
    toggle.addEventListener("click", (event) => {
        event.preventDefault();

        if (state.loggedIn) {
            setLoggedIn(false);
        } else {
            openAuthModal("login");
        }

        if (window.innerWidth <= 920) {
            closeMobileMenu();
        }
    });
});

authOpeners.forEach((opener) => {
    opener.addEventListener("click", (event) => {
        event.preventDefault();

        if (state.loggedIn) {
            return;
        }

        openAuthModal(opener.dataset.authOpen);

        if (window.innerWidth <= 920) {
            closeMobileMenu();
        }
    });
});

lockedLoginButton?.addEventListener("click", () => {
    if (!state.loggedIn) {
        openAuthModal("login");
    }
});

authTabs.forEach((tab) => {
    tab.addEventListener("click", () => {
        setAuthMode(tab.dataset.authTab);
    });
});

authSwitchButtons.forEach((button) => {
    button.addEventListener("click", () => {
        setAuthMode(button.dataset.authSwitch);
    });
});

authCloseButtons.forEach((button) => {
    button.addEventListener("click", () => {
        closeAuthModal();
    });
});

authPasswordToggles.forEach((toggle) => {
    const input = toggle.closest(".auth-input-password")?.querySelector("input");
    if (input instanceof HTMLInputElement) {
        input.dataset.authInputOriginalType = input.type;
    }

    toggle.addEventListener("click", () => {
        const field = toggle.closest(".auth-input-password")?.querySelector("input");
        const icon = toggle.querySelector("i");

        if (!(field instanceof HTMLInputElement)) {
            return;
        }

        const revealPassword = field.type === "password";
        field.type = revealPassword ? "text" : "password";
        toggle.classList.toggle("is-active", revealPassword);
        toggle.setAttribute("aria-label", revealPassword ? "Verberg wachtwoord" : "Toon wachtwoord");

        if (icon) {
            icon.className = revealPassword ? "fa-regular fa-eye-slash" : "fa-regular fa-eye";
        }
    });
});

authForms.forEach((form) => {
    form.addEventListener("submit", (event) => {
        event.preventDefault();

        const mode = form.dataset.authForm === "signup" ? "signup" : "login";
        setAuthFeedback(mode, "");

        if (mode === "login") {
            const identityInput = form.querySelector("[data-auth-login-identity]");
            const passwordInput = form.querySelector("[data-auth-login-password]");
            const identity = identityInput instanceof HTMLInputElement ? identityInput.value.trim() : "";
            const password = passwordInput instanceof HTMLInputElement ? passwordInput.value.trim() : "";

            if (!identity || !password) {
                setAuthFeedback("login", t("auth.modal.feedback.loginMissing"));
                return;
            }

            setRole("player");
            setLoggedIn(true);
            closeAuthModal();
            return;
        }

        const nameInput = form.querySelector("[data-auth-signup-name]");
        const emailInput = form.querySelector("[data-auth-signup-email]");
        const passwordInput = form.querySelector("[data-auth-signup-password]");
        const confirmInput = form.querySelector("[data-auth-signup-password-confirm]");
        const name = nameInput instanceof HTMLInputElement ? nameInput.value.trim() : "";
        const email = emailInput instanceof HTMLInputElement ? emailInput.value.trim() : "";
        const password = passwordInput instanceof HTMLInputElement ? passwordInput.value : "";
        const passwordConfirm = confirmInput instanceof HTMLInputElement ? confirmInput.value : "";

        if (!name || !email || !password || !passwordConfirm) {
            setAuthFeedback("signup", t("auth.modal.feedback.signupMissing"));
            return;
        }

        if (password.length < 6) {
            setAuthFeedback("signup", t("auth.modal.feedback.passwordShort"));
            return;
        }

        if (password !== passwordConfirm) {
            setAuthFeedback("signup", t("auth.modal.feedback.passwordMismatch"));
            return;
        }

        setRole("player");
        setLoggedIn(true);
        closeAuthModal();
    });
});

langToggle?.addEventListener("click", () => {
    setLanguageMenuOpen(!langSwitcher?.classList.contains("is-open"));
});

accountToggle?.addEventListener("click", () => {
    setAccountMenuOpen(!accountSwitcher?.classList.contains("is-open"));
});

langOptions.forEach((option) => {
    option.addEventListener("click", () => {
        setLanguage(option.dataset.langOption);
        closeLanguageMenu();
    });
});

roleOptions.forEach((option) => {
    option.addEventListener("click", () => {
        setRole(option.dataset.roleOption);
        closeAccountMenu();
    });
});

uploadTriggers.forEach((trigger) => {
    trigger.addEventListener("click", () => {
        if (!state.loggedIn || !photoInput) {
            return;
        }

        photoInput.value = "";
        photoInput.click();
    });
});

uploadChangeButton?.addEventListener("click", () => {
    if (!photoInput) {
        return;
    }

    photoInput.value = "";
    photoInput.click();
});

photoInput?.addEventListener("change", (event) => {
    const [file] = event.target.files || [];

    if (!file || !state.loggedIn) {
        return;
    }

    const reader = new FileReader();
    reader.addEventListener("load", () => {
        const result = String(reader.result);

        if (uploadModal && !uploadModal.hidden && !state.pendingPhotoId) {
            setPendingUploadImage(result, file.name);
            return;
        }

        openUploadModal(result, file.name);
    });
    reader.readAsDataURL(file);
});

uploadTitleInput?.addEventListener("input", () => {
    if (uploadPreview) {
        uploadPreview.alt = uploadTitleInput.value.trim() || t("upload.previewAlt");
    }
});

uploadCancelButtons.forEach((button) => {
    button.addEventListener("click", () => {
        closeUploadModal();
    });
});

competitionCreateButton?.addEventListener("click", () => {
    openCompetitionModal();
});

competitionCancelButtons.forEach((button) => {
    button.addEventListener("click", () => {
        closeCompetitionModal();
    });
});

leaderboardOpenButton?.addEventListener("click", () => {
    openLeaderboardModal();
});

leaderboardTeamFilter?.addEventListener("change", (event) => {
    const nextValue = event.target instanceof HTMLSelectElement ? event.target.value : "all";
    state.leaderboardTeamFilter = nextValue || "all";
    renderLeaderboard();
});

leaderboardCloseButtons.forEach((button) => {
    button.addEventListener("click", () => {
        closeLeaderboardModal();
    });
});

uploadForm?.addEventListener("submit", publishPendingPhoto);
competitionForm?.addEventListener("submit", saveCompetition);

competitionGrid?.addEventListener("click", (event) => {
    const target = event.target;

    if (!(target instanceof Element)) {
        return;
    }

    const editButton = target.closest("[data-competition-edit]");
    if (editButton) {
        openCompetitionModal(editButton.getAttribute("data-competition-edit"));
        return;
    }

    const deleteButton = target.closest("[data-competition-delete]");
    if (deleteButton) {
        deleteCompetition(deleteButton.getAttribute("data-competition-delete"));
    }
});

photoGrid?.addEventListener("click", (event) => {
    const target = event.target;

    if (!(target instanceof Element)) {
        return;
    }

    const editButton = target.closest("[data-photo-edit]");
    if (editButton) {
        openPhotoEditModal(editButton.getAttribute("data-photo-edit"));
        return;
    }

    const deleteButton = target.closest("[data-photo-delete]");
    if (!deleteButton) {
        return;
    }

    deletePhoto(deleteButton.getAttribute("data-photo-delete"));
});

document.addEventListener("click", (event) => {
    const target = event.target;

    if (!(target instanceof Node)) {
        return;
    }

    if (langSwitcher && !langSwitcher.contains(target)) {
        closeLanguageMenu();
    }

    if (accountSwitcher && !accountSwitcher.contains(target)) {
        closeAccountMenu();
    }

    if (authModal && !authModal.hidden) {
        const dialog = authModal.querySelector(".auth-modal-dialog");
        if (dialog && !dialog.contains(target) && target instanceof Element && target.hasAttribute("data-auth-close")) {
            closeAuthModal();
        }
    }

    if (window.innerWidth <= 920 && siteHeader && !siteHeader.contains(target) && body.classList.contains("nav-open")) {
        closeMobileMenu();
    }
});

document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") {
        return;
    }

    if (leaderboardModal && !leaderboardModal.hidden) {
        closeLeaderboardModal();
        return;
    }

    if (competitionModal && !competitionModal.hidden) {
        closeCompetitionModal();
        return;
    }

    if (authModal && !authModal.hidden) {
        closeAuthModal();
        return;
    }

    if (uploadModal && !uploadModal.hidden) {
        closeUploadModal();
        return;
    }

    if (langSwitcher?.classList.contains("is-open")) {
        closeLanguageMenu();
    }

    if (accountSwitcher?.classList.contains("is-open")) {
        closeAccountMenu();
    }

    if (body.classList.contains("nav-open")) {
        closeMobileMenu();
    }
});

window.addEventListener("resize", () => {
    if (window.innerWidth > 920) {
        closeMobileMenu();
    }

    queueScrollSpyUpdate();
});

window.addEventListener("scroll", queueScrollSpyUpdate, { passive: true });
window.addEventListener("load", updateActiveNavLink);
window.addEventListener("hashchange", queueScrollSpyUpdate);

applyTranslations();
setLoggedIn(state.loggedIn);
setLanguageMenuOpen(false);
setAccountMenuOpen(false);
closeCompetitionModal();
closeLeaderboardModal();
setAuthMode("login");
syncNavToggleLabel();
updateActiveNavLink();
