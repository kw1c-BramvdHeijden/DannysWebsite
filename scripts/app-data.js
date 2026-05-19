import { STORAGE_KEYS, competitionToneMap } from "./app-config.js";

function getBootstrapData() {
    const bootstrap = globalThis.__BOULES_BOOTSTRAP__;
    return bootstrap && typeof bootstrap === "object" ? bootstrap : {};
}

function normalizeLocalizedField(value) {
    if (typeof value === "string") {
        return value.trim();
    }

    if (!value || typeof value !== "object" || Array.isArray(value)) {
        return "";
    }

    const localizedEntries = Object.entries(value)
        .filter(([language, text]) => typeof language === "string" && typeof text === "string" && text.trim())
        .map(([language, text]) => [language, text.trim()]);

    return localizedEntries.length > 0 ? Object.fromEntries(localizedEntries) : "";
}

function normalizeNumber(value) {
    return Number.isFinite(Number(value)) ? Number(value) : 0;
}

function normalizeUser(user) {
    if (!user || typeof user !== "object") {
        return null;
    }

    const name = typeof user.name === "string" ? user.name.trim() : "";
    const id = typeof user.id === "string" && user.id.trim()
        ? user.id.trim()
        : (name ? name.toLowerCase().replace(/\s+/g, "-") : "");

    if (!name && !id) {
        return null;
    }

    const displayName = name || id;

    return {
        id: id || displayName,
        name: displayName,
        initials: displayName.charAt(0).toUpperCase() || "A"
    };
}

function normalizePhoto(photo) {
    if (!photo || typeof photo !== "object") {
        return null;
    }

    const image = typeof photo.image === "string" ? photo.image.trim() : "";
    if (!image) {
        return null;
    }

    const createdAt = Number.isFinite(photo.createdAt) ? photo.createdAt : Date.now();

    return {
        id: typeof photo.id === "string" || typeof photo.id === "number" ? String(photo.id) : String(createdAt),
        author: typeof photo.author === "string" ? photo.author.trim() : "",
        ownerId: typeof photo.ownerId === "string" && photo.ownerId.trim() ? photo.ownerId.trim() : null,
        title: normalizeLocalizedField(photo.title ?? photo.caption),
        description: normalizeLocalizedField(photo.description),
        createdAt,
        image
    };
}

function normalizeCompetitionTone(tone) {
    return competitionToneMap[tone] ? tone : "green";
}

function normalizeCompetition(competition) {
    if (!competition || typeof competition !== "object") {
        return null;
    }

    const startDate = typeof competition.startDate === "string" ? competition.startDate : "";
    const title = normalizeLocalizedField(competition.title);
    const type = normalizeLocalizedField(competition.type);

    if (!startDate || !title || !type) {
        return null;
    }

    return {
        id: typeof competition.id === "string" || typeof competition.id === "number" ? String(competition.id) : generateRecordId(),
        title,
        type,
        startDate,
        tone: normalizeCompetitionTone(competition.tone),
        href: typeof competition.href === "string" && competition.href.trim() ? competition.href.trim() : "#competities"
    };
}

function normalizeLeaderboardPlayer(player) {
    if (!player || typeof player !== "object") {
        return null;
    }

    const name = typeof player.name === "string" ? player.name.trim() : "";
    if (!name) {
        return null;
    }

    return {
        name,
        played: normalizeNumber(player.played)
    };
}

function normalizeLeaderboardEntry(entry) {
    if (!entry || typeof entry !== "object") {
        return null;
    }

    const team = typeof entry.team === "string" ? entry.team.trim() : "";
    if (!team) {
        return null;
    }

    return {
        team,
        played: normalizeNumber(entry.played),
        won: normalizeNumber(entry.won),
        diff: normalizeNumber(entry.diff),
        points: normalizeNumber(entry.points),
        trend: entry.trend === "up" || entry.trend === "down" ? entry.trend : "flat",
        players: Array.isArray(entry.players)
            ? entry.players.map(normalizeLeaderboardPlayer).filter(Boolean)
            : []
    };
}

function readStoredList(key, normalizer) {
    try {
        const storedValue = JSON.parse(localStorage.getItem(key) || "[]");
        return Array.isArray(storedValue) ? storedValue.map(normalizer).filter(Boolean) : [];
    } catch (error) {
        console.error(`Could not load ${key}`, error);
        return [];
    }
}

function readList(bootstrapValue, key, normalizer) {
    return Array.isArray(bootstrapValue)
        ? bootstrapValue.map(normalizer).filter(Boolean)
        : readStoredList(key, normalizer);
}

export function normalizeLanguage(language) {
    return language === "en" ? "en" : "nl";
}

export function normalizeRole(role) {
    return role === "admin" ? "admin" : "player";
}

export function getLocalizedText(value, language) {
    if (value && typeof value === "object") {
        return value[language] || value.nl || value.en || "";
    }

    return typeof value === "string" ? value : "";
}

export function sanitizeFilename(fileName) {
    return fileName
        .replace(/\.[^.]+$/, "")
        .replace(/[_-]+/g, " ")
        .replace(/\s+/g, " ")
        .trim();
}

export function generateRecordId() {
    return globalThis.crypto && typeof globalThis.crypto.randomUUID === "function"
        ? globalThis.crypto.randomUUID()
        : String(Date.now());
}

export function createAppState() {
    const bootstrap = getBootstrapData();
    const auth = bootstrap.auth && typeof bootstrap.auth === "object" ? bootstrap.auth : {};
    const loggedIn = auth.loggedIn === true;

    return {
        lang: normalizeLanguage(localStorage.getItem(STORAGE_KEYS.lang)),
        loggedIn,
        role: normalizeRole(auth.role),
        user: loggedIn ? normalizeUser(bootstrap.user ?? auth.user) : null,
        photos: readList(bootstrap.photos, STORAGE_KEYS.photos, normalizePhoto),
        competitions: readList(bootstrap.competitions, STORAGE_KEYS.competitions, normalizeCompetition),
        leaderboard: readList(bootstrap.leaderboard, STORAGE_KEYS.leaderboard, normalizeLeaderboardEntry),
        pendingUpload: null,
        pendingPhotoId: null,
        pendingCompetitionId: null,
        leaderboardTeamFilter: "all"
    };
}

export function saveLanguage(language) {
    localStorage.setItem(STORAGE_KEYS.lang, language);
}

export function savePhotos(photos) {
    localStorage.setItem(STORAGE_KEYS.photos, JSON.stringify(photos));
}

export function saveCompetitions(competitions) {
    localStorage.setItem(STORAGE_KEYS.competitions, JSON.stringify(competitions));
}
