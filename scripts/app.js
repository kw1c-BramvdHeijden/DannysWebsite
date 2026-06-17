import { competitionToneMap, localeMap } from "./app-config.js";
import { refs } from "./app-dom.js";
import { translations } from "./app-translations.js";
import {
    createAppState,
    generateRecordId,
    getLocalizedText,
    normalizeLanguage,
    normalizeRole,
    saveAuth,
    sanitizeFilename,
    saveCompetitions,
    saveLanguage,
    savePhotos
} from "./app-data.js";
import { createPhotosModule } from "./app-photos.js";
import { createCompetitionsModule } from "./app-competitions.js";
import { createLeaderboardModule } from "./app-leaderboard.js";
import { createUiModule } from "./app-ui.js";
import { bindEvents } from "./app-events.js";

export function createApp() {
    const state = createAppState();
    const t = (key) => translations[state.lang]?.[key] || translations.nl[key] || key;

    const photos = createPhotosModule({
        refs,
        state,
        t,
        localeMap,
        getLocalizedText,
        sanitizeFilename,
        generateRecordId,
        savePhotos
    });

    const competitions = createCompetitionsModule({
        refs,
        state,
        t,
        localeMap,
        competitionToneMap,
        getLocalizedText,
        generateRecordId,
        saveCompetitions
    });

    const leaderboard = createLeaderboardModule({
        refs,
        state,
        t
    });

    const ui = createUiModule({
        refs,
        state,
        t,
        normalizeLanguage,
        normalizeRole,
        saveAuth,
        saveLanguage,
        renderPhotos: photos.renderPhotos,
        renderCompetitions: competitions.renderCompetitions,
        renderLeaderboard: leaderboard.renderLeaderboard,
        syncUploadModalUI: photos.syncUploadModalUI,
        syncCompetitionFormUI: competitions.syncCompetitionFormUI,
        closeUploadModal: photos.closeUploadModal,
        closeCompetitionModal: competitions.closeCompetitionModal,
        closeLeaderboardModal: leaderboard.closeLeaderboardModal,
        canManageCompetitions: competitions.canManageCompetitions
    });

    bindEvents({
        refs,
        state,
        photos,
        competitions,
        leaderboard,
        ui,
        t
    });

    ui.applyTranslations();
    ui.setLoggedIn(state.loggedIn);
    ui.setLanguageMenuOpen(false);
    ui.setAccountMenuOpen(false);
    competitions.closeCompetitionModal();
    leaderboard.closeLeaderboardModal();
    ui.setAuthMode("login");
    ui.syncNavToggleLabel();
    ui.updateActiveNavLink();
}
