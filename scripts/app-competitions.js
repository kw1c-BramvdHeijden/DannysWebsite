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

    function canManageCompetitions() {
        return state.loggedIn && state.role === "admin";
    }

    function buildCompetitionCollection() {
        return state.competitions
            .slice()
            .sort((left, right) => left.startDate.localeCompare(right.startDate));
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
        link.href = competition.href || "#competities";
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
            return;
        }

        competitions.forEach((competition) => {
            refs.competitionGrid.appendChild(createCompetitionCard(competition));
        });
    }

    function getCompetitionById(competitionId) {
        return state.competitions.find((competition) => String(competition.id) === String(competitionId)) || null;
    }

    function syncCompetitionFormUI() {
        const isEditing = Boolean(state.pendingCompetitionId);

        if (refs.competitionFormKicker) {
            refs.competitionFormKicker.textContent = t(isEditing ? "competitions.form.editKicker" : "competitions.form.addKicker");
        }

        if (refs.competitionFormTitle) {
            refs.competitionFormTitle.textContent = t(isEditing ? "competitions.form.editTitle" : "competitions.form.addTitle");
        }

        if (refs.competitionFormDescription) {
            refs.competitionFormDescription.textContent = t("competitions.form.description");
        }

        if (refs.competitionSubmitLabel) {
            refs.competitionSubmitLabel.textContent = t("competitions.form.save");
        }
    }

    function resetCompetitionForm() {
        state.pendingCompetitionId = null;

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

    function saveCompetition(event) {
        event.preventDefault();

        if (!canManageCompetitions() || !refs.competitionNameInput || !refs.competitionTypeInput || !refs.competitionDateInput || !refs.competitionToneInput) {
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
            href: "#competities"
        };

        if (state.pendingCompetitionId) {
            state.competitions = state.competitions.map((competition) => (
                String(competition.id) === String(state.pendingCompetitionId)
                    ? { ...competition, ...competitionData }
                    : competition
            ));
        } else {
            state.competitions.unshift({
                id: generateRecordId(),
                ...competitionData
            });
        }

        saveCompetitions(state.competitions);
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

        state.competitions = state.competitions.filter((entry) => String(entry.id) !== String(competitionId));
        saveCompetitions(state.competitions);
        renderCompetitions();
    }

    return {
        canManageCompetitions,
        renderCompetitions,
        syncCompetitionFormUI,
        openCompetitionModal,
        closeCompetitionModal,
        saveCompetition,
        deleteCompetition
    };
}
