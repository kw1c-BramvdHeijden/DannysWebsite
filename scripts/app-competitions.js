export function createCompetitionsModule({
    refs,
    state,
    t,
    localeMap,
    competitionToneMap,
    getLocalizedText,
    generateRecordId,
    saveCompetitions
}) {
    let competitionModalTimer = 0;
    let competitionFormMode = "admin";

    function canManageCompetitions() {
        return state.loggedIn && state.role === "admin";
    }

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

    function setCompetitionFeedback(message = "", isSuccess = false) {
        if (!refs.competitionFeedback) {
            return;
        }

        refs.competitionFeedback.textContent = message;
        refs.competitionFeedback.classList.toggle("is-success", isSuccess);
    }

    function getCurrentUserId() {
        return state.user && (typeof state.user.id === "string" || typeof state.user.id === "number")
            ? String(state.user.id)
            : "";
    }

    async function requestCompetition(competitionData) {
        const response = await fetch(getCompetitionApiUrl(), {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "request",
                userId: getCurrentUserId(),
                competition: competitionData
            })
        });

        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result.competition) {
            throw new Error(result.error || t("competitions.form.requestError"));
        }

        return result.competition;
    }

    async function createAcceptedCompetition(competitionData) {
        const response = await fetch(getCompetitionApiUrl(), {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "create",
                userId: getCurrentUserId(),
                competition: competitionData
            })
        });

        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result.competition) {
            throw new Error(result.error || t("competitions.form.createError"));
        }

        return result.competition;
    }

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

    function normalizeCompetitionFromApi(competition) {
        if (!competition || typeof competition !== "object") {
            return null;
        }

        const title = typeof competition.title === "string" ? competition.title.trim() : "";
        const type = typeof competition.type === "string" ? competition.type.trim() : "";
        const startDate = typeof competition.startDate === "string" ? competition.startDate.trim() : "";

        if (!title || !type || !startDate) {
            return null;
        }

        return {
            id: typeof competition.id === "string" || typeof competition.id === "number" ? String(competition.id) : generateRecordId(),
            title,
            type,
            startDate,
            tone: competitionToneMap[competition.tone] ? competition.tone : "green",
            status: typeof competition.status === "string" ? competition.status : "",
            requesterName: typeof competition.requesterName === "string" ? competition.requesterName.trim() : "",
            href: getDefaultCompetitionHref()
        };
    }

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
        const tone = competitionToneMap[competition.tone] ? competition.tone : "green";
        icon.className = `competition-icon ${tone}`;
        icon.innerHTML = `<i class="fa-solid ${competitionToneMap[tone]}"></i>`;

        const title = document.createElement("h3");
        title.textContent = getLocalizedText(competition.title, state.lang);

        const type = document.createElement("p");
        type.textContent = getLocalizedText(competition.type, state.lang);

        const date = document.createElement("p");
        date.textContent = formatCompetitionDate(competition.startDate);

        const link = document.createElement("a");
        link.href = competition.href || getDefaultCompetitionHref();
        link.innerHTML = `<span>${t("competitions.more")}</span> <span aria-hidden="true">-&gt;</span>`;

        card.append(icon, title, type, date, link);
        return card;
    }

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

    function getCompetitionById(competitionId) {
        return state.competitions.find((competition) => String(competition.id) === String(competitionId)) || null;
    }

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

    function resetCompetitionForm() {
        state.pendingCompetitionId = null;
        competitionFormMode = "admin";
        setCompetitionFeedback();

        if (refs.competitionForm) {
            refs.competitionForm.reset();
        }

        if (refs.competitionToneInput) {
            refs.competitionToneInput.value = "green";
        }
    }

    function openCompetitionModal(competitionId = null) {
        if (!canManageCompetitions() || !refs.competitionModal || !refs.competitionNameInput || !refs.competitionTypeInput || !refs.competitionDateInput || !refs.competitionToneInput) {
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
        refs.competitionToneInput.value = competition?.tone || "green";

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

    function openCompetitionRequestModal() {
        if (!refs.competitionModal || !refs.competitionNameInput || !refs.competitionTypeInput || !refs.competitionDateInput || !refs.competitionToneInput) {
            return;
        }

        clearTimeout(competitionModalTimer);
        competitionFormMode = "request";
        state.pendingCompetitionId = null;
        refs.competitionForm?.reset();
        refs.competitionToneInput.value = "green";
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

    async function saveCompetition(event) {
        event.preventDefault();

        const isRequesting = competitionFormMode === "request";
        if ((!isRequesting && !canManageCompetitions()) || !refs.competitionNameInput || !refs.competitionTypeInput || !refs.competitionDateInput || !refs.competitionToneInput) {
            closeCompetitionModal();
            return;
        }

        const title = refs.competitionNameInput.value.trim();
        const type = refs.competitionTypeInput.value.trim();
        const startDate = refs.competitionDateInput.value;
        const tone = refs.competitionToneInput.value;

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

        const competitionData = {
            title,
            type,
            startDate,
            tone: competitionToneMap[tone] ? tone : "green",
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
        acceptCompetitionRequest,
        rejectCompetitionRequest
    };
}
