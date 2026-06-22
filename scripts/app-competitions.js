export function createCompetitionsModule({
    refs,
    state,
    t,
    localeMap,
    competitionToneMap,
    getLocalizedText,
    generateRecordId,
    saveCompetitions,
    getCurrentUserTeams = () => [],
    getCurrentUserTeamsStatus = () => ({ loaded: true, loading: false, error: "" }),
    loadCurrentUserTeams = () => {}
}) {
    let competitionModalTimer = 0;
    let competitionFormMode = "admin";

    // Admins mogen competities beheren.
    function canManageCompetitions() {
        return state.loggedIn && state.role === "admin";
    }

    // Sorteer competities op startdatum.
    function buildCompetitionCollection() {
        return state.competitions
            .slice()
            .sort((left, right) => left.startDate.localeCompare(right.startDate));
    }

    function getDefaultCompetitionHref() {
        return document.body.dataset.competitionsHref || "pages/competities.php";
    }

    function getCompetitionApiUrl() {
        const script = document.querySelector("script[src$='scripts/index.js']");
        return script ? new URL("../api/competitions.php", script.src).toString() : "api/competitions.php";
    }

    function isHexColor(value) {
        return typeof value === "string" && /^#[0-9a-f]{6}$/i.test(value);
    }

    function normalizeCompetitionTone(tone) {
        if (isHexColor(tone)) {
            return tone.toLowerCase();
        }

        return competitionToneMap[tone] ? tone : "#7b9151";
    }

    function competitionIconForTone(tone) {
        return competitionToneMap[tone] || "fa-calendar-days";
    }

    // Toon feedback onder het competitieformulier.
    function setCompetitionFeedback(message = "", isSuccess = false) {
        if (!refs.competitionFeedback) {
            return;
        }

        refs.competitionFeedback.textContent = message;
        refs.competitionFeedback.classList.toggle("is-success", isSuccess);
    }

    // Speler vraagt een competitie aan.
    async function requestCompetition(competitionData) {
        const response = await fetch(getCompetitionApiUrl(), {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "request",
                competition: competitionData
            })
        });

        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result.competition) {
            throw new Error(result.error || t("competitions.form.requestError"));
        }

        return result.competition;
    }

    // Admin maakt direct een geaccepteerde competitie aan.
    async function createAcceptedCompetition(competitionData) {
        const response = await fetch(getCompetitionApiUrl(), {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "create",
                competition: competitionData
            })
        });

        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result.competition) {
            throw new Error(result.error || t("competitions.form.createError"));
        }

        return result.competition;
    }

    // Admin werkt een bestaande competitie bij.
    async function updateAcceptedCompetition(competitionId, competitionData) {
        const response = await fetch(getCompetitionApiUrl(), {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "update",
                id: competitionId,
                competition: competitionData
            })
        });

        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result.competition) {
            throw new Error(result.error || t("competitions.form.createError"));
        }

        return result.competition;
    }

    // Admin verwijdert een competitie.
    async function deleteAcceptedCompetition(competitionId) {
        const response = await fetch(getCompetitionApiUrl(), {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "delete",
                id: competitionId
            })
        });

        const result = await response.json().catch(() => ({}));
        if (!response.ok || result.deleted !== true) {
            throw new Error(result.error || t("competitions.requests.error"));
        }

        return result;
    }

    // Zet API-data om naar het frontend-formaat.
    function normalizeCompetitionFromApi(competition) {
        if (!competition || typeof competition !== "object") {
            return null;
        }

        const title = typeof competition.title === "string" ? competition.title.trim() : "";
        const type = typeof competition.type === "string" ? competition.type.trim() : "";
        const startDate = typeof competition.startDate === "string" ? competition.startDate.trim() : "";
        const endDate = typeof competition.endDate === "string" ? competition.endDate.trim() : "";

        if (!title || !type || !startDate) {
            return null;
        }

        return {
            id: typeof competition.id === "string" || typeof competition.id === "number" ? String(competition.id) : generateRecordId(),
            title,
            type,
            startDate,
            endDate,
            tone: normalizeCompetitionTone(competition.tone),
            status: typeof competition.status === "string" ? competition.status : "",
            requesterName: typeof competition.requesterName === "string" ? competition.requesterName.trim() : "",
            registeredTeamIds: Array.isArray(competition.registeredTeamIds) ? competition.registeredTeamIds.map(String) : [],
            registeredTeams: Array.isArray(competition.registeredTeams)
                ? competition.registeredTeams
                    .filter((team) => team && typeof team === "object")
                    .map((team) => ({
                        id: typeof team.id === "string" || typeof team.id === "number" ? String(team.id) : "",
                        name: typeof team.name === "string" && team.name.trim() ? team.name.trim() : "Team"
                    }))
                    .filter((team) => team.id || team.name)
                : [],
            href: getDefaultCompetitionHref()
        };
    }

    // Algemene helper voor competitie-API-calls.
    async function requestCompetitionApi(payload) {
        const response = await fetch(getCompetitionApiUrl(), {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(result.error || t("competitions.requests.error"));
        }

        return result;
    }

    // Meld een team aan voor een competitie.
    async function registerTeamForCompetition(competitionId, teamId) {
        const result = await requestCompetitionApi({
            action: "registerTeam",
            competitionId,
            teamId
        });

        return result.competition;
    }

    // Laad pending aanvragen voor admins.
    async function loadCompetitionRequests() {
        if (!refs.competitionRequestsPanel || !canManageCompetitions() || state.competitionRequestsLoaded || state.competitionRequestsLoading) {
            return;
        }

        state.competitionRequestsLoading = true;
        renderCompetitionRequests();

        try {
            const result = await requestCompetitionApi({ action: "listPending" });
            state.competitionRequests = Array.isArray(result.competitions)
                ? result.competitions.map(normalizeCompetitionFromApi).filter(Boolean)
                : [];
            state.competitionRequestsLoaded = true;
        } catch (error) {
            if (refs.competitionRequestsStatus) {
                refs.competitionRequestsStatus.textContent = error.message || t("competitions.requests.error");
            }
        } finally {
            state.competitionRequestsLoading = false;
            renderCompetitionRequests();
        }
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

    // Formatteer alleen de datumwaarde.
    function formatPlainDate(dateValue) {
        if (!dateValue) {
            return "-";
        }

        const date = new Date(`${dateValue}T12:00:00`);
        return new Intl.DateTimeFormat(localeMap[state.lang], {
            day: "numeric",
            month: "long",
            year: "numeric"
        }).format(date);
    }

    // Bouw de kaart voor een aankomende competitie.
    function createCompetitionCard(competition) {
        const card = document.createElement("article");
        card.className = "competition-card";

        if (canManageCompetitions()) {
            // Beheerknoppen zijn alleen zichtbaar voor admins.
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
        const tone = normalizeCompetitionTone(competition.tone);
        icon.className = `competition-icon ${competitionToneMap[tone] ? tone : "custom"}`;
        if (isHexColor(tone)) {
            icon.style.backgroundColor = tone;
        }
        icon.innerHTML = `<i class="fa-solid ${competitionIconForTone(tone)}"></i>`;

        const title = document.createElement("h3");
        title.textContent = getLocalizedText(competition.title, state.lang);

        const header = document.createElement("div");
        header.className = "competition-card-header";
        header.append(icon, title);

        const details = document.createElement("dl");
        details.className = "competition-details";

        [
            [t("competitions.detail.location"), getLocalizedText(competition.type, state.lang)],
            [t("competitions.detail.startDate"), formatPlainDate(competition.startDate)],
            [t("competitions.detail.endDate"), formatPlainDate(competition.endDate)]
        ].forEach(([label, value]) => {
            const item = document.createElement("div");
            item.className = "competition-detail-item";
            const term = document.createElement("dt");
            term.textContent = label;
            const description = document.createElement("dd");
            description.textContent = value || "-";
            item.append(term, description);
            details.appendChild(item);
        });

        card.append(header, details);

        const userTeams = state.loggedIn ? getCurrentUserTeams() : [];
        const userTeamsStatus = state.loggedIn
            ? getCurrentUserTeamsStatus()
            : { loaded: true, loading: false, error: "" };
        const registeredTeamIds = Array.isArray(competition.registeredTeamIds)
            ? competition.registeredTeamIds.map(String)
            : [];
        const registeredTeams = Array.isArray(competition.registeredTeams)
            ? competition.registeredTeams.filter((team) => team && typeof team.name === "string" && team.name.trim())
            : [];
        const availableTeams = userTeams.filter((team) => registeredTeamIds.indexOf(String(team.id)) === -1);
        const alreadyRegisteredTeams = userTeams.filter((team) => registeredTeamIds.indexOf(String(team.id)) !== -1);

        const registeredTeamsSection = document.createElement("section");
        registeredTeamsSection.className = "competition-registered-teams";

        // Toon alle teams die al aangemeld zijn.
        const registeredTitle = document.createElement("h4");
        registeredTitle.textContent = t("competitions.registeredTeams.title");
        registeredTeamsSection.appendChild(registeredTitle);

        if (registeredTeams.length === 0) {
            const emptyTeams = document.createElement("p");
            emptyTeams.className = "competition-registered-empty";
            emptyTeams.textContent = t("competitions.registeredTeams.empty");
            registeredTeamsSection.appendChild(emptyTeams);
        } else {
            const list = document.createElement("ul");
            registeredTeams.forEach((team) => {
                const item = document.createElement("li");
                item.textContent = team.name;
                list.appendChild(item);
            });
            registeredTeamsSection.appendChild(list);
        }

        card.appendChild(registeredTeamsSection);

        const signup = document.createElement("div");
        signup.className = "competition-team-signup";

        // Toon de juiste aanmeldstatus per gebruiker.
        if (!state.loggedIn) {
            const message = document.createElement("p");
            message.textContent = t("competitions.teamSignup.loginRequired");
            signup.appendChild(message);
        } else if (userTeamsStatus.error) {
            const message = document.createElement("p");
            message.textContent = userTeamsStatus.error;
            signup.appendChild(message);
        } else if (userTeamsStatus.loading || !userTeamsStatus.loaded) {
            const message = document.createElement("p");
            message.textContent = t("teams.loading");
            signup.appendChild(message);
        } else if (alreadyRegisteredTeams.length > 0 && availableTeams.length === 0) {
            const message = document.createElement("p");
            message.textContent = `${t("competitions.teamSignup.alreadyRegistered")}: ${alreadyRegisteredTeams.map((team) => team.name).join(", ")}`;
            signup.appendChild(message);
        } else if (availableTeams.length === 0) {
            const message = document.createElement("p");
            message.textContent = t("competitions.teamSignup.noTeams");
            signup.appendChild(message);
        } else {
            const select = document.createElement("select");
            select.className = "competition-select";
            select.dataset.competitionTeamSelect = String(competition.id);
            select.setAttribute("aria-label", t("competitions.teamSignup.selectLabel"));

            availableTeams.forEach((team) => {
                const option = document.createElement("option");
                option.value = String(team.id);
                option.textContent = team.name;
                select.appendChild(option);
            });

            const button = document.createElement("button");
            button.type = "button";
            button.className = "button button-secondary";
            button.dataset.competitionTeamRegister = String(competition.id);
            button.innerHTML = `<i class="fa-solid fa-user-plus"></i><span>${t("competitions.teamSignup.submit")}</span>`;

            signup.append(select, button);
        }

        card.appendChild(signup);
        return card;
    }

    // Render alle aankomende competities.
    function renderCompetitions() {
        if (!refs.competitionGrid) {
            return;
        }

        refs.competitionGrid.innerHTML = "";

        const competitions = buildCompetitionCollection();
        if (competitions.length === 0) {
            const emptyState = document.createElement("div");
            emptyState.className = "competition-empty";

            const message = document.createElement("p");
            message.textContent = t("competitions.empty");

            emptyState.appendChild(message);
            refs.competitionGrid.appendChild(emptyState);
            renderCompetitionRequests();
            loadCompetitionRequests();
            return;
        }

        competitions.forEach((competition) => {
            refs.competitionGrid.appendChild(createCompetitionCard(competition));
        });

        renderCompetitionRequests();
        loadCompetitionRequests();
    }

    // Bouw een kaart voor een pending aanvraag.
    function createCompetitionRequestCard(competition) {
        const card = document.createElement("article");
        card.className = "competition-request-card";

        const title = document.createElement("h3");
        title.textContent = getLocalizedText(competition.title, state.lang);

        const type = document.createElement("p");
        type.textContent = getLocalizedText(competition.type, state.lang);

        const requester = document.createElement("p");
        requester.className = "competition-request-requester";
        requester.textContent = competition.requesterName || "-";

        const date = document.createElement("p");
        date.textContent = formatCompetitionDate(competition.startDate);

        const status = document.createElement("span");
        status.className = "competition-request-status";
        status.textContent = t("competitions.requests.pending");

        const actions = document.createElement("div");
        actions.className = "competition-request-actions";

        const acceptButton = document.createElement("button");
        acceptButton.type = "button";
        acceptButton.className = "button button-secondary";
        acceptButton.dataset.competitionRequestAccept = String(competition.id);
        acceptButton.innerHTML = `<i class="fa-solid fa-check"></i><span>${t("competitions.requests.accept")}</span>`;

        const rejectButton = document.createElement("button");
        rejectButton.type = "button";
        rejectButton.className = "button button-outline";
        rejectButton.dataset.competitionRequestReject = String(competition.id);
        rejectButton.innerHTML = `<i class="fa-solid fa-xmark"></i><span>${t("competitions.requests.reject")}</span>`;

        actions.append(acceptButton, rejectButton);
        card.append(status, title, requester, type, date, actions);
        return card;
    }

    // Render het admin-overzicht met aanvragen.
    function renderCompetitionRequests() {
        if (!refs.competitionRequestsPanel || !refs.competitionRequestsGrid) {
            return;
        }

        const canManage = canManageCompetitions();
        refs.competitionRequestsPanel.hidden = !canManage;

        if (!canManage) {
            refs.competitionRequestsGrid.innerHTML = "";
            return;
        }

        refs.competitionRequestsGrid.innerHTML = "";

        if (refs.competitionRequestsStatus) {
            refs.competitionRequestsStatus.textContent = state.competitionRequestsLoading
                ? t("competitions.requests.loading")
                : "";
        }

        if (state.competitionRequestsLoading) {
            return;
        }

        if (state.competitionRequests.length === 0) {
            const emptyState = document.createElement("div");
            emptyState.className = "competition-empty";

            const message = document.createElement("p");
            message.textContent = state.competitionRequestsLoaded
                ? t("competitions.requests.empty")
                : t("competitions.requests.loading");

            emptyState.appendChild(message);
            refs.competitionRequestsGrid.appendChild(emptyState);
            return;
        }

        state.competitionRequests.forEach((competition) => {
            refs.competitionRequestsGrid.appendChild(createCompetitionRequestCard(competition));
        });
    }

    // Zoek competitie in lokale state.
    function getCompetitionById(competitionId) {
        return state.competitions.find((competition) => String(competition.id) === String(competitionId)) || null;
    }

    // Zet de tekst van het formulier op aanvraag, toevoegen of bewerken.
    function syncCompetitionFormUI() {
        const isEditing = Boolean(state.pendingCompetitionId);
        const isRequesting = competitionFormMode === "request";

        if (refs.competitionFormKicker) {
            refs.competitionFormKicker.textContent = t(isRequesting ? "competitions.form.requestKicker" : (isEditing ? "competitions.form.editKicker" : "competitions.form.addKicker"));
        }

        if (refs.competitionFormTitle) {
            refs.competitionFormTitle.textContent = t(isRequesting ? "competitions.form.requestTitle" : (isEditing ? "competitions.form.editTitle" : "competitions.form.addTitle"));
        }

        if (refs.competitionFormDescription) {
            refs.competitionFormDescription.textContent = t(isRequesting ? "competitions.form.requestDescription" : "competitions.form.description");
        }

        if (refs.competitionSubmitLabel) {
            refs.competitionSubmitLabel.textContent = t(isRequesting ? "competitions.form.requestSubmit" : "competitions.form.save");
        }

    }

    // Reset formulier naar de standaardwaarden.
    function resetCompetitionForm() {
        state.pendingCompetitionId = null;
        competitionFormMode = "admin";
        setCompetitionFeedback();

        if (refs.competitionForm) {
            refs.competitionForm.reset();
        }

        if (refs.competitionToneInput) {
            refs.competitionToneInput.value = "#7b9151";
        }
    }

    // Open adminformulier voor toevoegen of bewerken.
    function openCompetitionModal(competitionId = null) {
        if (!canManageCompetitions() || !refs.competitionModal || !refs.competitionNameInput || !refs.competitionTypeInput || !refs.competitionDateInput || !refs.competitionEndDateInput || !refs.competitionToneInput) {
            return;
        }

        clearTimeout(competitionModalTimer);
        competitionFormMode = "admin";
        setCompetitionFeedback();

        state.pendingCompetitionId = competitionId ? String(competitionId) : null;

        const competition = state.pendingCompetitionId ? getCompetitionById(state.pendingCompetitionId) : null;

        refs.competitionNameInput.value = competition ? getLocalizedText(competition.title, state.lang) : "";
        refs.competitionTypeInput.value = competition ? getLocalizedText(competition.type, state.lang) : "";
        refs.competitionDateInput.value = competition?.startDate || "";
        refs.competitionEndDateInput.value = competition?.endDate || "";
        refs.competitionToneInput.value = competition && isHexColor(competition.tone) ? competition.tone : "#7b9151";

        syncCompetitionFormUI();

        refs.competitionModal.hidden = false;
        refs.body.classList.add("competition-modal-open");

        requestAnimationFrame(() => {
            refs.competitionModal.classList.add("is-open");
        });

        window.setTimeout(() => {
            if (refs.competitionModal && !refs.competitionModal.hidden) {
                refs.competitionNameInput.focus();
                refs.competitionNameInput.select();
            }
        }, 120);
    }

    // Open formulier waarmee spelers een aanvraag doen.
    function openCompetitionRequestModal() {
        if (!refs.competitionModal || !refs.competitionNameInput || !refs.competitionTypeInput || !refs.competitionDateInput || !refs.competitionEndDateInput || !refs.competitionToneInput) {
            return;
        }

        clearTimeout(competitionModalTimer);
        competitionFormMode = "request";
        state.pendingCompetitionId = null;
        refs.competitionForm?.reset();
        refs.competitionToneInput.value = "#7b9151";
        setCompetitionFeedback();
        syncCompetitionFormUI();

        refs.competitionModal.hidden = false;
        refs.body.classList.add("competition-modal-open");

        requestAnimationFrame(() => {
            refs.competitionModal.classList.add("is-open");
        });

        window.setTimeout(() => {
            if (refs.competitionModal && !refs.competitionModal.hidden) {
                refs.competitionNameInput.focus();
            }
        }, 120);
    }

    // Sluit het competitieformulier.
    function closeCompetitionModal() {
        if (!refs.competitionModal || refs.competitionModal.hidden) {
            resetCompetitionForm();
            syncCompetitionFormUI();
            return;
        }

        clearTimeout(competitionModalTimer);
        refs.competitionModal.classList.remove("is-open");
        refs.body.classList.remove("competition-modal-open");

        competitionModalTimer = window.setTimeout(() => {
            refs.competitionModal.hidden = true;
            resetCompetitionForm();
            syncCompetitionFormUI();
        }, 220);
    }

    // Valideer en sla het formulier op.
    async function saveCompetition(event) {
        event.preventDefault();

        const isRequesting = competitionFormMode === "request";
        if ((!isRequesting && !canManageCompetitions()) || !refs.competitionNameInput || !refs.competitionTypeInput || !refs.competitionDateInput || !refs.competitionEndDateInput || !refs.competitionToneInput) {
            closeCompetitionModal();
            return;
        }

        const title = refs.competitionNameInput.value.trim();
        const type = refs.competitionTypeInput.value.trim();
        const startDate = refs.competitionDateInput.value;
        const endDate = refs.competitionEndDateInput.value;
        const tone = normalizeCompetitionTone(refs.competitionToneInput.value);

        if (!title) {
            refs.competitionNameInput.focus();
            return;
        }

        if (!type) {
            refs.competitionTypeInput.focus();
            return;
        }

        if (!startDate) {
            refs.competitionDateInput.focus();
            return;
        }

        if (!endDate) {
            refs.competitionEndDateInput.focus();
            return;
        }

        if (endDate < startDate) {
            // Einddatum mag nooit voor startdatum liggen.
            setCompetitionFeedback(t("competitions.form.endDateBeforeStart"));
            refs.competitionEndDateInput.focus();
            return;
        }

        const competitionData = {
            title,
            type,
            startDate,
            endDate,
            tone,
            href: getDefaultCompetitionHref()
        };

        if (isRequesting) {
            const submitButton = refs.competitionForm?.querySelector("button[type='submit']");

            if (submitButton) {
                submitButton.disabled = true;
            }

            try {
                await requestCompetition(competitionData);
                setCompetitionFeedback(t("competitions.form.requestSuccess"), true);
                window.setTimeout(closeCompetitionModal, 900);
            } catch (error) {
                setCompetitionFeedback(error.message || t("competitions.form.requestError"));
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                }
            }

            return;
        }

        if (state.pendingCompetitionId) {
            const submitButton = refs.competitionForm?.querySelector("button[type='submit']");

            if (submitButton) {
                submitButton.disabled = true;
            }

            try {
                const updatedCompetition = normalizeCompetitionFromApi(await updateAcceptedCompetition(state.pendingCompetitionId, competitionData));
                state.competitions = state.competitions.map((competition) => (
                    String(competition.id) === String(state.pendingCompetitionId)
                        ? (updatedCompetition || { ...competition, ...competitionData })
                        : competition
                ));
            } catch (error) {
                setCompetitionFeedback(error.message || t("competitions.form.createError"));
                if (submitButton) {
                    submitButton.disabled = false;
                }
                return;
            }

            if (submitButton) {
                submitButton.disabled = false;
            }
            saveCompetitions(state.competitions);
            renderCompetitions();
            closeCompetitionModal();
            return;
        }

        const submitButton = refs.competitionForm?.querySelector("button[type='submit']");

        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            const createdCompetition = await createAcceptedCompetition(competitionData);
            state.competitions.unshift(normalizeCompetitionFromApi(createdCompetition) || {
                ...competitionData,
                id: createdCompetition.id || generateRecordId()
            });
        } catch (error) {
            setCompetitionFeedback(error.message || t("competitions.form.createError"));
            if (submitButton) {
                submitButton.disabled = false;
            }
            return;
        }

        if (submitButton) {
            submitButton.disabled = false;
        }
        saveCompetitions(state.competitions);
        renderCompetitions();
        closeCompetitionModal();
    }

    // Verwijder een competitie na bevestiging.
    async function deleteCompetition(competitionId) {
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

        try {
            await deleteAcceptedCompetition(competitionId);
            state.competitions = state.competitions.filter((entry) => String(entry.id) !== String(competitionId));
            saveCompetitions(state.competitions);
            renderCompetitions();
        } catch (error) {
            window.alert(error.message || t("competitions.requests.error"));
        }
    }

    // Registreer het gekozen team voor deze competitie.
    async function registerSelectedTeam(competitionId) {
        if (!state.loggedIn || !competitionId) {
            return;
        }

        const select = refs.competitionGrid?.querySelector(`[data-competition-team-select="${competitionId}"]`);
        const teamId = select instanceof HTMLSelectElement ? select.value : "";
        if (!teamId) {
            return;
        }

        const button = refs.competitionGrid?.querySelector(`[data-competition-team-register="${competitionId}"]`);
        if (button) {
            button.disabled = true;
        }

        try {
            const userTeams = await loadCurrentUserTeams(true);
            const canUseSelectedTeam = Array.isArray(userTeams) && userTeams.some((team) => String(team.id) === String(teamId));
            if (!canUseSelectedTeam) {
                renderCompetitions();
                window.alert(t("competitions.teamSignup.error"));
                return;
            }

            const updatedCompetition = normalizeCompetitionFromApi(await registerTeamForCompetition(competitionId, teamId));
            state.competitions = state.competitions.map((competition) => (
                String(competition.id) === String(competitionId)
                    ? (updatedCompetition || {
                        ...competition,
                        registeredTeamIds: Array.from(new Set([...(competition.registeredTeamIds || []).map(String), String(teamId)]))
                    })
                    : competition
            ));
            saveCompetitions(state.competitions);
            renderCompetitions();
        } catch (error) {
            window.alert(error.message || t("competitions.teamSignup.error"));
        } finally {
            if (button) {
                button.disabled = false;
            }
        }
    }


    // Accepteer of wijs een pending aanvraag af.
    async function updateCompetitionRequest(competitionId, action) {
        if (!canManageCompetitions() || !competitionId) {
            return;
        }

        try {
            const result = await requestCompetitionApi({
                action,
                id: competitionId
            });
            const updatedCompetition = normalizeCompetitionFromApi(result.competition);

            state.competitionRequests = state.competitionRequests.filter((entry) => String(entry.id) !== String(competitionId));

            if (action === "accept" && updatedCompetition) {
                state.competitions = state.competitions.filter((entry) => String(entry.id) !== String(updatedCompetition.id));
                state.competitions.unshift(updatedCompetition);
                saveCompetitions(state.competitions);
            }

            renderCompetitions();
            renderCompetitionRequests();
        } catch (error) {
            if (refs.competitionRequestsStatus) {
                refs.competitionRequestsStatus.textContent = error.message || t("competitions.requests.error");
            }
        }
    }

    function acceptCompetitionRequest(competitionId) {
        updateCompetitionRequest(competitionId, "accept");
    }

    function rejectCompetitionRequest(competitionId) {
        updateCompetitionRequest(competitionId, "reject");
    }

    return {
        canManageCompetitions,
        renderCompetitions,
        syncCompetitionFormUI,
        openCompetitionModal,
        openCompetitionRequestModal,
        closeCompetitionModal,
        saveCompetition,
        deleteCompetition,
        registerSelectedTeam,
        acceptCompetitionRequest,
        rejectCompetitionRequest
    };
}
