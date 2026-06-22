import { translations } from "./app-translations.js";

const SCORE_LIMIT = 13;
const LIVE_REFRESH_MS = 5000;
const LANGUAGE_STORAGE_KEY = "boules_language";
let livePollTimer = 0;

const refs = {
    matches: document.querySelector("[data-scoreboard-matches]"),
    listStatus: document.querySelector("[data-scoreboard-list-status]"),
    detail: document.querySelector("[data-scoreboard-detail]"),
    empty: document.querySelector("[data-scoreboard-empty]"),
    detailKicker: document.querySelector("[data-scoreboard-detail-kicker]"),
    detailTitle: document.querySelector("[data-scoreboard-detail-title]"),
    detailMeta: document.querySelector("[data-scoreboard-detail-meta]"),
    stateChip: document.querySelector("[data-scoreboard-state-chip]"),
    teams: document.querySelector("[data-scoreboard-teams]"),
    feedback: document.querySelector("[data-scoreboard-feedback]"),
    save: document.querySelector("[data-scoreboard-save]"),
    minusRound: document.querySelector("[data-scoreboard-minus-round]"),
    refresh: document.querySelector("[data-scoreboard-refresh]"),
    heroTitle: document.querySelector("[data-scoreboard-hero-title]"),
    heroScores: Array.from(document.querySelectorAll("[data-scoreboard-hero-score]")),
    heroTeams: Array.from(document.querySelectorAll("[data-scoreboard-hero-team]")),
    heroRows: Array.from(document.querySelectorAll(".scoreboard-hero-row"))
};

const state = {
    matches: [],
    selectedId: "",
    pendingScores: {},
    lastChangedTeamId: "",
    loading: false,
    saving: false,
    listError: "",
    feedbackMessage: "",
    feedbackType: "",
    feedbackIsKey: false,
    loggedIn: false,
    role: "player",
    authClass: document.body.classList.contains("is-logged-in")
};

function getLanguage() {
    return localStorage.getItem(LANGUAGE_STORAGE_KEY) === "en" ? "en" : "nl";
}

function getLocale() {
    return getLanguage() === "en" ? "en-GB" : "nl-NL";
}

function t(key) {
    const language = getLanguage();

    return translations[language]?.[key] || translations.nl[key] || key;
}

function getApiUrl() {
    return document.body.dataset.scoreboardApi || "../api/scoreboard.php";
}

function normalizeMatch(match) {
    if (!match || typeof match !== "object") {
        return null;
    }

    const id = typeof match.id === "string" || typeof match.id === "number" ? String(match.id) : "";
    const teams = Array.isArray(match.teams)
        ? match.teams
            .map((team) => ({
                id: typeof team.id === "string" || typeof team.id === "number" ? String(team.id) : "",
                name: typeof team.name === "string" && team.name.trim() ? team.name.trim() : t("scoreboard.teamFallback"),
                score: Number.isFinite(Number(team.score)) ? Number(team.score) : 0,
                isMine: team.isMine === true
            }))
            .filter((team) => team.id)
        : [];

    if (!id || teams.length < 2) {
        return null;
    }

    return {
        id,
        date: typeof match.date === "string" ? match.date : "",
        verified: match.verified === true,
        pouleName: typeof match.pouleName === "string" ? match.pouleName : "",
        tournamentName: typeof match.tournamentName === "string" ? match.tournamentName : "",
        location: typeof match.location === "string" ? match.location : "",
        isMine: match.isMine === true,
        canEdit: match.canEdit === true,
        completed: match.completed === true,
        isLive: match.isLive === true,
        winnerTeamId: typeof match.winnerTeamId === "string" || typeof match.winnerTeamId === "number" ? String(match.winnerTeamId) : "",
        teams
    };
}

async function requestJson(url, options = {}) {
    const response = await fetch(url, {
        credentials: "same-origin",
        ...options,
        headers: {
            "Content-Type": "application/json",
            ...(options.headers || {})
        }
    });
    const result = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(result.error || t("scoreboard.error.load"));
    }

    return result;
}

function formatMatchDate(dateValue) {
    if (!dateValue) {
        return t("scoreboard.dateFallback");
    }

    const normalizedDate = dateValue.includes("T") ? dateValue : dateValue.replace(" ", "T");
    const date = new Date(normalizedDate);

    if (Number.isNaN(date.getTime())) {
        return dateValue;
    }

    return new Intl.DateTimeFormat(getLocale(), {
        day: "numeric",
        month: "long",
        year: "numeric"
    }).format(date);
}

function getSelectedMatch() {
    return state.matches.find((match) => match.id === state.selectedId) || null;
}

function getMatchTitle(match) {
    return match.teams.map((team) => team.name).join(" vs ");
}

function getWinner(match) {
    return match.teams.find((team) => team.id === match.winnerTeamId) || null;
}

function createElement(tagName, className = "", text = "") {
    const element = document.createElement(tagName);
    if (className) {
        element.className = className;
    }
    if (text) {
        element.textContent = text;
    }

    return element;
}

function setListStatus(message) {
    if (refs.listStatus) {
        refs.listStatus.textContent = message;
    }
}

function syncFeedback() {
    if (!refs.feedback) {
        return;
    }

    refs.feedback.textContent = state.feedbackIsKey ? t(state.feedbackMessage) : state.feedbackMessage;
    refs.feedback.classList.toggle("is-success", state.feedbackType === "success");
    refs.feedback.classList.toggle("is-error", state.feedbackType === "error");
}

function setFeedback(message = "", type = "", isKey = false) {
    state.feedbackMessage = message;
    state.feedbackType = type;
    state.feedbackIsKey = isKey;
    syncFeedback();
}

function setUrlMatch(matchId) {
    const url = new URL(window.location.href);
    if (matchId) {
        url.searchParams.set("wedstrijd", matchId);
    } else {
        url.searchParams.delete("wedstrijd");
    }

    window.history.replaceState({}, "", url);
}

function buildPendingScores(match) {
    return Object.fromEntries(match.teams.map((team) => [team.id, team.score]));
}

function hasScoreChanges(match) {
    return match.teams.some((team) => Number(state.pendingScores[team.id]) !== Number(team.score));
}

function hasInvalidScores() {
    const scores = Object.values(state.pendingScores).map(Number);
    const teamsAtLimit = scores.filter((score) => score >= SCORE_LIMIT).length;

    return scores.some((score) => !Number.isFinite(score) || score < 0 || score > SCORE_LIMIT) || teamsAtLimit > 1;
}

function renderMatches() {
    if (!refs.matches) {
        return;
    }

    refs.matches.innerHTML = "";

    if (state.loading) {
        setListStatus(t("scoreboard.loading"));
        return;
    }

    if (state.matches.length === 0) {
        setListStatus(state.listError || (state.loggedIn ? t("scoreboard.empty.mine") : t("scoreboard.empty.all")));
        return;
    }

    setListStatus(state.loggedIn && state.role !== "admin"
        ? t("scoreboard.status.mineLive")
        : t("scoreboard.status.allLive"));

    state.matches.forEach((match) => {
        const button = createElement("button", "scoreboard-match-button");
        button.type = "button";
        button.dataset.matchId = match.id;
        button.classList.toggle("is-active", match.id === state.selectedId);
        button.setAttribute("aria-current", match.id === state.selectedId ? "true" : "false");

        const main = createElement("span", "scoreboard-match-main");
        const title = createElement("strong", "", getMatchTitle(match));
        const meta = createElement("span", "", `${formatMatchDate(match.date)} - ${match.pouleName || t("scoreboard.poolFallback")} - ${match.tournamentName || t("scoreboard.tournamentFallback")}`);
        main.append(title, meta);

        const scoreLine = createElement("span", "scoreboard-match-score");
        match.teams.forEach((team) => {
            const pill = createElement("span", "scoreboard-score-pill");
            pill.classList.toggle("is-mine", team.isMine);
            pill.textContent = `${team.name}: ${team.score}`;
            scoreLine.appendChild(pill);
        });

        const chip = createElement("span", "scoreboard-match-chip");
        if (match.completed) {
            chip.classList.add("is-completed");
            chip.textContent = t("scoreboard.chip.completed");
        } else if (match.isLive) {
            chip.textContent = match.canEdit ? t("scoreboard.chip.scoreNow") : t("scoreboard.chip.watchLive");
        } else if (match.canEdit) {
            chip.textContent = match.isMine ? t("scoreboard.chip.yourMatch") : t("scoreboard.chip.manage");
        } else {
            chip.classList.add("is-locked");
            chip.textContent = t("scoreboard.chip.watch");
        }

        button.append(main, scoreLine, chip);
        refs.matches.appendChild(button);
    });
}

function renderEmptyDetail() {
    const hasSelectedMatch = Boolean(getSelectedMatch());

    if (refs.empty) {
        refs.empty.hidden = hasSelectedMatch;
    }

    if (refs.detail) {
        refs.detail.hidden = !hasSelectedMatch;
    }
}

function getHeroMatch() {
    return getSelectedMatch() || state.matches.find((match) => match.isLive) || null;
}

function updateHeroBoard() {
    const match = getHeroMatch();
    const heroTeams = match
        ? match.teams.slice(0, 2)
        : [
            { id: "demo-a", name: t("scoreboard.demo.teamA"), score: 9 },
            { id: "demo-b", name: t("scoreboard.demo.teamB"), score: 13 }
        ];
    const heroScores = heroTeams.map((team) => {
        if (match && match.id === state.selectedId && Object.prototype.hasOwnProperty.call(state.pendingScores, team.id)) {
            return Number(state.pendingScores[team.id]) || 0;
        }

        return Number(team.score) || 0;
    });
    const topScore = Math.max(...heroScores);

    if (refs.heroTitle) {
        refs.heroTitle.textContent = match?.tournamentName || "Jeu de Dabs";
    }

    heroTeams.forEach((team, index) => {
        const score = heroScores[index] || 0;
        const row = refs.heroRows[index];
        const isWinner = Boolean(match?.completed && String(match.winnerTeamId) === String(team.id));
        const isLeading = match ? (isWinner || (!match.completed && topScore > 0 && score === topScore)) : index === 1;

        if (refs.heroTeams[index]) {
            refs.heroTeams[index].textContent = team.name;
        }

        if (refs.heroScores[index]) {
            refs.heroScores[index].textContent = String(score).padStart(2, "0");
        }

        if (row) {
            row.classList.toggle("is-leading", isLeading);
        }
    });
}

function renderTeam(match, team) {
    const row = createElement("article", "scoreboard-team");
    const score = Number(state.pendingScores[team.id]) || 0;
    const winner = match.completed && String(match.winnerTeamId) === team.id;
    row.classList.toggle("is-winner", winner);

    const copy = createElement("div", "scoreboard-team-copy");
    const name = createElement("span", "scoreboard-team-name", team.name);
    const meta = createElement("span", "scoreboard-team-meta", team.isMine ? t("scoreboard.team.mine") : t("scoreboard.team.label"));
    copy.append(name, meta);

    const scoreControls = createElement("div", "scoreboard-team-score");

    const minus = createElement("button", "scoreboard-score-button");
    minus.type = "button";
    minus.dataset.scoreAction = "minus";
    minus.dataset.teamId = team.id;
    minus.setAttribute("aria-label", `${t("scoreboard.aria.minus")} ${team.name}`);
    minus.innerHTML = '<i class="fa-solid fa-minus" aria-hidden="true"></i>';

    const number = createElement("span", "scoreboard-score-number", String(score).padStart(2, "0"));

    const plus = createElement("button", "scoreboard-score-button");
    plus.type = "button";
    plus.dataset.scoreAction = "plus";
    plus.dataset.teamId = team.id;
    plus.setAttribute("aria-label", `${t("scoreboard.aria.plus")} ${team.name}`);
    plus.innerHTML = '<i class="fa-solid fa-plus" aria-hidden="true"></i>';

    const otherTeamHasLimit = Object.entries(state.pendingScores)
        .some(([teamId, teamScore]) => teamId !== team.id && Number(teamScore) >= SCORE_LIMIT);
    const controlsDisabled = !match.canEdit || state.saving;
    minus.disabled = controlsDisabled || score <= 0;
    plus.disabled = controlsDisabled || score >= SCORE_LIMIT || otherTeamHasLimit;

    scoreControls.append(minus, number, plus);
    row.append(copy, scoreControls);

    return row;
}

function renderDetail() {
    const match = getSelectedMatch();
    renderEmptyDetail();

    if (!match) {
        updateHeroBoard();
        return;
    }

    if (refs.detailKicker) {
        refs.detailKicker.textContent = match.tournamentName || t("scoreboard.detail.kicker");
    }

    if (refs.detailTitle) {
        refs.detailTitle.textContent = getMatchTitle(match);
    }

    if (refs.detailMeta) {
        const location = match.location ? ` - ${match.location}` : "";
        refs.detailMeta.textContent = `${formatMatchDate(match.date)} - ${match.pouleName || t("scoreboard.poolFallback")}${location}`;
    }

    if (refs.stateChip) {
        const winner = getWinner(match);
        refs.stateChip.classList.toggle("is-completed", match.completed);
        refs.stateChip.classList.toggle("is-locked", !match.canEdit && !match.completed);
        refs.stateChip.textContent = match.completed && winner
            ? `${t("scoreboard.state.winner")} ${winner.name}`
            : (match.isLive
                ? (match.canEdit ? t("scoreboard.state.liveScore") : t("scoreboard.state.liveWatch"))
                : (match.canEdit ? t("scoreboard.state.updateScore") : t("scoreboard.state.watch")));
    }

    if (refs.teams) {
        refs.teams.innerHTML = "";
        match.teams.forEach((team) => {
            refs.teams.appendChild(renderTeam(match, team));
        });
    }

    syncActionState();
    updateHeroBoard();
}

function syncActionState() {
    const match = getSelectedMatch();
    const canSave = Boolean(match)
        && match.canEdit
        && !state.saving
        && hasScoreChanges(match)
        && !hasInvalidScores();

    if (refs.save) {
        refs.save.disabled = !canSave;
    }

    if (refs.minusRound) {
        refs.minusRound.disabled = !match
            || !match.canEdit
            || state.saving
            || Object.values(state.pendingScores).every((score) => Number(score) <= 0);
    }
}

function render() {
    renderMatches();
    renderDetail();
}

function applyScoreboardTranslations() {
    document.title = t("scoreboard.meta.title");
    render();
    syncFeedback();
}

function selectMatch(matchId, updateUrl = true) {
    const match = state.matches.find((entry) => entry.id === String(matchId));
    if (!match) {
        state.selectedId = "";
        state.pendingScores = {};
        state.lastChangedTeamId = "";
        render();
        return;
    }

    state.selectedId = match.id;
    state.pendingScores = buildPendingScores(match);
    state.lastChangedTeamId = "";
    setFeedback();

    if (updateUrl) {
        setUrlMatch(match.id);
    }

    render();
}

function changeScore(teamId, delta) {
    const match = getSelectedMatch();
    if (!match || !match.canEdit || state.saving) {
        return;
    }

    const currentScore = Number(state.pendingScores[teamId]) || 0;
    const nextScore = Math.max(0, Math.min(SCORE_LIMIT, currentScore + delta));

    if (delta > 0) {
        const otherTeamHasLimit = Object.entries(state.pendingScores)
            .some(([candidateTeamId, score]) => candidateTeamId !== teamId && Number(score) >= SCORE_LIMIT);

        if (otherTeamHasLimit) {
            return;
        }
    }

    state.pendingScores[teamId] = nextScore;
    state.lastChangedTeamId = teamId;
    setFeedback();
    renderDetail();
}

function subtractLastPoint() {
    const match = getSelectedMatch();
    if (!match || !match.canEdit || state.saving) {
        return;
    }

    const preferredTeam = state.lastChangedTeamId && Number(state.pendingScores[state.lastChangedTeamId]) > 0
        ? state.lastChangedTeamId
        : match.teams
            .slice()
            .sort((left, right) => Number(state.pendingScores[right.id]) - Number(state.pendingScores[left.id]))[0]?.id;

    if (preferredTeam) {
        changeScore(preferredTeam, -1);
    }
}

async function saveScore() {
    const match = getSelectedMatch();
    if (!match || !match.canEdit || !hasScoreChanges(match) || hasInvalidScores()) {
        return;
    }

    state.saving = true;
    syncActionState();
    setFeedback("scoreboard.feedback.saving", "", true);

    try {
        const result = await requestJson(getApiUrl(), {
            method: "POST",
            body: JSON.stringify({
                action: "update",
                wedstrijdId: match.id,
                scores: match.teams.map((team) => ({
                    teamId: team.id,
                    score: Number(state.pendingScores[team.id]) || 0
                }))
            })
        });
        const updatedMatch = normalizeMatch(result.match);

        if (updatedMatch) {
            state.matches = state.matches.map((entry) => entry.id === updatedMatch.id ? updatedMatch : entry);
            state.pendingScores = buildPendingScores(updatedMatch);
            setFeedback("scoreboard.feedback.saved", "success", true);
        }
    } catch (error) {
        setFeedback(error.message || t("scoreboard.error.save"), "error");
    } finally {
        state.saving = false;
        render();
    }
}

function getInitialMatchId() {
    const params = new URLSearchParams(window.location.search);
    return params.get("wedstrijd") || params.get("wedstrijd_id") || params.get("matchId") || "";
}

function getPreferredMatch(previousSelectedId) {
    const urlMatchId = getInitialMatchId();
    const preferredId = previousSelectedId || urlMatchId;
    const selectedMatch = preferredId ? state.matches.find((match) => match.id === preferredId) : null;

    return selectedMatch
        || state.matches.find((match) => match.isLive && match.isMine)
        || state.matches.find((match) => match.isLive)
        || state.matches[0]
        || null;
}

async function loadMatches(options = {}) {
    const silent = options.silent === true;
    const previousSelectedId = state.selectedId;
    const previousMatch = getSelectedMatch();
    const hadLocalChanges = previousMatch ? hasScoreChanges(previousMatch) : false;

    if (!silent) {
        state.loading = true;
        state.listError = "";
        renderMatches();
    }

    try {
        const result = await requestJson(getApiUrl());
        state.matches = Array.isArray(result.matches)
            ? result.matches.map(normalizeMatch).filter(Boolean)
            : [];
        state.loggedIn = result.loggedIn === true;
        state.role = result.role === "admin" ? "admin" : "player";
        state.listError = "";

        const nextMatch = getPreferredMatch(previousSelectedId);

        if (nextMatch) {
            state.selectedId = nextMatch.id;

            if (!hadLocalChanges || previousSelectedId !== nextMatch.id || !nextMatch.canEdit) {
                state.pendingScores = buildPendingScores(nextMatch);
            } else if (silent) {
                setFeedback("scoreboard.feedback.livePaused", "", true);
            }

            if (previousSelectedId !== nextMatch.id) {
                state.lastChangedTeamId = "";
                if (!silent) {
                    setFeedback();
                }
            }

            render();
        } else {
            state.selectedId = "";
            state.pendingScores = {};
            state.lastChangedTeamId = "";
            render();
        }
    } catch (error) {
        state.listError = error.message || t("scoreboard.error.load");

        if (!silent) {
            state.matches = [];
            state.selectedId = "";
            state.pendingScores = {};
            renderEmptyDetail();
        }
    } finally {
        if (!silent) {
            state.loading = false;
        }

        renderMatches();
    }
}

function startLivePolling() {
    window.clearInterval(livePollTimer);
    livePollTimer = window.setInterval(() => {
        if (document.visibilityState === "visible") {
            loadMatches({ silent: true });
        }
    }, LIVE_REFRESH_MS);
}

refs.matches?.addEventListener("click", (event) => {
    const target = event.target;
    if (!(target instanceof Element)) {
        return;
    }

    const button = target.closest("[data-match-id]");
    if (button) {
        selectMatch(button.getAttribute("data-match-id"));
    }
});

refs.teams?.addEventListener("click", (event) => {
    const target = event.target;
    if (!(target instanceof Element)) {
        return;
    }

    const button = target.closest("[data-score-action]");
    if (!button) {
        return;
    }

    const teamId = button.getAttribute("data-team-id");
    if (!teamId) {
        return;
    }

    changeScore(teamId, button.getAttribute("data-score-action") === "plus" ? 1 : -1);
});

refs.save?.addEventListener("click", saveScore);
refs.minusRound?.addEventListener("click", subtractLastPoint);
refs.refresh?.addEventListener("click", loadMatches);

document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") {
        loadMatches({ silent: true });
    }
});

new MutationObserver(() => {
    applyScoreboardTranslations();
}).observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["lang"]
});

new MutationObserver(() => {
    const nextAuthClass = document.body.classList.contains("is-logged-in");
    if (nextAuthClass === state.authClass) {
        return;
    }

    state.authClass = nextAuthClass;
    loadMatches();
}).observe(document.body, {
    attributes: true,
    attributeFilter: ["class"]
});

applyScoreboardTranslations();
loadMatches();
startLivePolling();
