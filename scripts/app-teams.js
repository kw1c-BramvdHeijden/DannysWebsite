export function createTeamsModule({ refs, state, t, onTeamsChanged = () => {} }) {
    let teamModalTimer = 0;
    let teamsLoaded = false;
    let teamsLoading = false;
    let usersLoaded = false;
    let usersLoading = false;
    let currentUserTeamsLoaded = false;
    let currentUserTeamsLoading = false;
    let currentUserTeamsError = "";
    let currentUserTeamsUserId = "";
    let teams = [];
    let currentUserTeams = [];
    let users = [];
    let userSearchTerm = "";
    let selectedUserIds = [];

    function getTeamApiUrl() {
        const script = document.querySelector("script[src$='scripts/index.js']");
        return script ? new URL("../api/teams.php", script.src).toString() : "api/teams.php";
    }

    function getCurrentUserId() {
        return state.user && (typeof state.user.id === "string" || typeof state.user.id === "number")
            ? String(state.user.id)
            : "";
    }

    async function requestTeams(payload) {
        const response = await fetch(getTeamApiUrl(), {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(result.error || t("teams.error"));
        }

        return result;
    }

    function normalizeTeam(team) {
        if (!team || typeof team !== "object") {
            return null;
        }

        const name = typeof team.name === "string" ? team.name.trim() : "";
        if (!name) {
            return null;
        }

        return {
            id: typeof team.id === "string" || typeof team.id === "number" ? String(team.id) : name,
            name,
            playerOne: typeof team.playerOne === "string" ? team.playerOne.trim() : "",
            playerTwo: typeof team.playerTwo === "string" ? team.playerTwo.trim() : "",
            memberIds: Array.isArray(team.memberIds) ? team.memberIds.map(String) : [],
            memberNames: Array.isArray(team.memberNames)
                ? team.memberNames.filter((name) => typeof name === "string" && name.trim()).map((name) => name.trim())
                : [],
            createdByName: typeof team.createdByName === "string" ? team.createdByName.trim() : "",
            createdAt: typeof team.createdAt === "string" ? team.createdAt.trim() : ""
        };
    }

    function normalizeUser(user) {
        if (!user || typeof user !== "object") {
            return null;
        }

        const id = typeof user.id === "string" || typeof user.id === "number" ? String(user.id).trim() : "";
        const name = typeof user.name === "string" ? user.name.trim() : "";

        if (!id || !name) {
            return null;
        }

        return { id, name };
    }

    function getCurrentUserTeams() {
        if (!state.loggedIn || currentUserTeamsUserId !== getCurrentUserId()) {
            return [];
        }

        return currentUserTeams.slice();
    }

    function getCurrentUserTeamsStatus() {
        return {
            loaded: currentUserTeamsLoaded,
            loading: currentUserTeamsLoading,
            error: currentUserTeamsError
        };
    }

    function setTeamFeedback(message = "", isSuccess = false) {
        if (!refs.teamFeedback) {
            return;
        }

        refs.teamFeedback.textContent = message;
        refs.teamFeedback.classList.toggle("is-success", isSuccess);
    }

    function setTeamStatus(message = "") {
        if (refs.teamStatus) {
            refs.teamStatus.textContent = message;
        }
    }

    function setUserStatus(message = "") {
        if (refs.teamUserStatus) {
            refs.teamUserStatus.textContent = message;
        }
    }

    function getSelectedUserIds() {
        return selectedUserIds.slice();
    }

    function syncUserSummary() {
        if (!refs.teamUserSummary) {
            return;
        }

        const selectedIds = getSelectedUserIds();
        if (selectedIds.length === 0) {
            refs.teamUserSummary.textContent = t("teams.form.membersPlaceholder");
            return;
        }

        const selectedNames = users
            .filter((user) => selectedIds.indexOf(user.id) !== -1)
            .map((user) => user.name);

        refs.teamUserSummary.textContent = selectedNames.length <= 2
            ? selectedNames.join(", ")
            : `${selectedNames.slice(0, 2).join(", ")} +${selectedNames.length - 2}`;
    }

    function setUserMenuOpen(isOpen) {
        if (!refs.teamUserMenu || !refs.teamUserToggle) {
            return;
        }

        refs.teamUserMenu.hidden = !isOpen;
        refs.teamUserToggle.setAttribute("aria-expanded", String(isOpen));

        if (isOpen && refs.teamUserSearch) {
            window.setTimeout(() => {
                refs.teamUserSearch.focus();
            }, 0);
        }
    }

    function createTeamCard(team) {
        const card = document.createElement("article");
        card.className = "team-card";

        const title = document.createElement("h4");
        title.textContent = team.name;

        const players = document.createElement("p");
        const playerNames = team.memberNames.length > 0
            ? team.memberNames
            : [team.playerOne, team.playerTwo].filter(Boolean);
        players.textContent = playerNames.join(", ");

        const meta = document.createElement("span");
        meta.className = "team-card-meta";
        meta.textContent = team.createdByName ? `${t("teams.createdBy")} ${team.createdByName}` : t("teams.createdByUnknown");

        card.append(title, players, meta);
        return card;
    }

    function renderUsers() {
        if (!refs.teamUserOptions) {
            return;
        }

        refs.teamUserOptions.innerHTML = "";

        if (usersLoading) {
            setUserStatus(t("teams.users.loading"));
            return;
        }

        if (users.length === 0) {
            setUserStatus(usersLoaded ? t("teams.users.empty") : t("teams.users.loading"));
            return;
        }

        setUserStatus("");

        const visibleUsers = userSearchTerm
            ? users.filter((user) => user.name.toLowerCase().indexOf(userSearchTerm) !== -1)
            : users;

        if (visibleUsers.length === 0) {
            setUserStatus(t("teams.users.noResults"));
            syncUserSummary();
            return;
        }

        visibleUsers.forEach((user) => {
            const label = document.createElement("label");
            label.className = "team-user-option";

            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.value = user.id;
            checkbox.checked = selectedUserIds.indexOf(user.id) !== -1;

            const text = document.createElement("span");
            text.textContent = user.name;

            checkbox.addEventListener("change", () => {
                if (checkbox.checked && selectedUserIds.indexOf(user.id) === -1) {
                    selectedUserIds.push(user.id);
                }

                if (!checkbox.checked) {
                    selectedUserIds = selectedUserIds.filter((selectedId) => selectedId !== user.id);
                }

                syncUserSummary();
            });
            label.append(checkbox, text);
            refs.teamUserOptions.appendChild(label);
        });

        syncUserSummary();
    }

    async function loadUsers(force = false) {
        if (usersLoading || (usersLoaded && !force)) {
            renderUsers();
            return;
        }

        usersLoading = true;
        renderUsers();

        try {
            const result = await requestTeams({ action: "listUsers" });
            users = Array.isArray(result.users)
                ? result.users.map(normalizeUser).filter(Boolean)
                : [];
            usersLoaded = true;
        } catch (error) {
            setUserStatus(error.message || t("teams.users.error"));
        } finally {
            usersLoading = false;
            renderUsers();
        }
    }

    function renderTeams() {
        if (!refs.teamList) {
            return;
        }

        refs.teamList.innerHTML = "";

        if (teamsLoading) {
            setTeamStatus(t("teams.loading"));
            return;
        }

        setTeamStatus("");

        if (teams.length === 0) {
            const empty = document.createElement("p");
            empty.className = "team-empty";
            empty.textContent = teamsLoaded ? t("teams.empty") : t("teams.loading");
            refs.teamList.appendChild(empty);
            return;
        }

        teams.forEach((team) => {
            refs.teamList.appendChild(createTeamCard(team));
        });
    }

    async function loadTeams(force = false) {
        if (teamsLoading || (teamsLoaded && !force)) {
            renderTeams();
            return;
        }

        teamsLoading = true;
        renderTeams();

        try {
            const result = await requestTeams({ action: "list" });
            teams = Array.isArray(result.teams)
                ? result.teams.map(normalizeTeam).filter(Boolean)
                : [];
            teamsLoaded = true;
            onTeamsChanged();
        } catch (error) {
            setTeamStatus(error.message || t("teams.error"));
        } finally {
            teamsLoading = false;
            renderTeams();
        }
    }

    async function loadCurrentUserTeams(force = false) {
        const currentUserId = getCurrentUserId();
        const userChanged = currentUserTeamsUserId !== currentUserId;

        if (!state.loggedIn || !currentUserId) {
            currentUserTeams = [];
            currentUserTeamsLoaded = false;
            currentUserTeamsError = "";
            currentUserTeamsUserId = "";
            onTeamsChanged();
            return [];
        }

        if (currentUserTeamsLoading) {
            return userChanged ? [] : currentUserTeams.slice();
        }

        if (currentUserTeamsLoaded && !force && !userChanged) {
            return currentUserTeams.slice();
        }

        currentUserTeamsLoading = true;
        currentUserTeamsError = "";
        onTeamsChanged();

        try {
            const result = await requestTeams({ action: "listMine" });
            currentUserTeams = Array.isArray(result.teams)
                ? result.teams.map(normalizeTeam).filter(Boolean)
                : [];
            currentUserTeamsLoaded = true;
            currentUserTeamsUserId = currentUserId;
            onTeamsChanged();
        } catch (error) {
            currentUserTeams = [];
            currentUserTeamsLoaded = false;
            currentUserTeamsError = error.message || t("teams.error");
            currentUserTeamsUserId = "";
            onTeamsChanged();
        } finally {
            currentUserTeamsLoading = false;
            onTeamsChanged();
        }

        return currentUserTeams.slice();
    }

    function openTeamModal() {
        if (!refs.teamModal || !state.loggedIn) {
            return;
        }

        clearTimeout(teamModalTimer);
        setTeamFeedback();
        refs.teamModal.hidden = false;
        refs.body.classList.add("team-modal-open");
        loadTeams();
        loadCurrentUserTeams();
        loadUsers();

        requestAnimationFrame(() => {
            refs.teamModal.classList.add("is-open");
        });

        window.setTimeout(() => {
            if (refs.teamModal && !refs.teamModal.hidden && refs.teamNameInput) {
                refs.teamNameInput.focus();
            }
        }, 120);
    }

    function preloadUsers() {
        if (!refs.teamUserOptions) {
            return;
        }

        loadUsers();
    }

    function preloadTeamData() {
        if (!refs.teamList && !refs.teamUserOptions) {
            return;
        }

        loadTeams();
        loadCurrentUserTeams();
        loadUsers();
    }

    function preloadCurrentUserTeams(force = false) {
        return loadCurrentUserTeams(force);
    }

    function closeTeamModal() {
        if (!refs.teamModal || refs.teamModal.hidden) {
            return;
        }

        clearTimeout(teamModalTimer);
        refs.teamModal.classList.remove("is-open");
        refs.body.classList.remove("team-modal-open");

        teamModalTimer = window.setTimeout(() => {
            refs.teamModal.hidden = true;
            if (refs.teamForm) {
                refs.teamForm.reset();
            }
            userSearchTerm = "";
            selectedUserIds = [];
            if (refs.teamUserSearch) {
                refs.teamUserSearch.value = "";
            }
            if (refs.teamUserOptions) {
                refs.teamUserOptions.querySelectorAll("input[type='checkbox']").forEach((input) => {
                    input.checked = false;
                });
            }
            setUserMenuOpen(false);
            syncUserSummary();
            setTeamFeedback();
        }, 220);
    }

    async function submitTeam(event) {
        event.preventDefault();

        if (!state.loggedIn || !refs.teamNameInput) {
            return;
        }

        const name = refs.teamNameInput.value.trim();
        const memberIds = getSelectedUserIds();

        if (!name) {
            refs.teamNameInput.focus();
            return;
        }

        if (memberIds.length === 0) {
            setTeamFeedback(t("teams.form.membersRequired"));
            refs.teamUserToggle?.focus();
            return;
        }

        const submitButton = refs.teamForm?.querySelector("button[type='submit']");
        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            const result = await requestTeams({
                action: "create",
                userId: getCurrentUserId(),
                team: {
                    name,
                    memberIds
                }
            });
            const createdTeam = normalizeTeam(result.team);
            if (createdTeam) {
                teams.unshift(createdTeam);
                currentUserTeams.unshift(createdTeam);
                currentUserTeamsLoaded = true;
                currentUserTeamsError = "";
                currentUserTeamsUserId = getCurrentUserId();
                teamsLoaded = true;
                onTeamsChanged();
            }
            if (refs.teamForm) {
                refs.teamForm.reset();
            }
            userSearchTerm = "";
            selectedUserIds = [];
            if (refs.teamUserSearch) {
                refs.teamUserSearch.value = "";
            }
            if (refs.teamUserOptions) {
                refs.teamUserOptions.querySelectorAll("input[type='checkbox']").forEach((input) => {
                    input.checked = false;
                });
            }
            setUserMenuOpen(false);
            syncUserSummary();
            setTeamFeedback(t("teams.success"), true);
            renderTeams();
        } catch (error) {
            setTeamFeedback(error.message || t("teams.error"));
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    }

    function syncTeamButtons() {
        refs.teamOpenButtons.forEach((button) => {
            button.hidden = !state.loggedIn;
        });
    }

    function toggleUserMenu() {
        if (!refs.teamUserMenu) {
            return;
        }

        setUserMenuOpen(refs.teamUserMenu.hidden);
    }

    function closeUserMenu() {
        setUserMenuOpen(false);
    }

    function filterUsers(value) {
        userSearchTerm = typeof value === "string" ? value.trim().toLowerCase() : "";
        renderUsers();
    }

    return {
        openTeamModal,
        preloadUsers,
        preloadTeamData,
        preloadCurrentUserTeams,
        loadTeams,
        loadCurrentUserTeams,
        getCurrentUserTeams,
        getCurrentUserTeamsStatus,
        closeTeamModal,
        submitTeam,
        toggleUserMenu,
        closeUserMenu,
        filterUsers,
        syncTeamButtons
    };
}
