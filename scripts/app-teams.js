export function createTeamsModule({ refs, state, t }) {
    let teamModalTimer = 0;
    let teamsLoaded = false;
    let teamsLoading = false;
    let usersLoaded = false;
    let usersLoading = false;
    let teams = [];
    let users = [];

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
        if (!refs.teamUserOptions) {
            return [];
        }

        return Array.from(refs.teamUserOptions.querySelectorAll("input[type='checkbox']:checked"))
            .map((input) => input.value)
            .filter(Boolean);
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

        users.forEach((user) => {
            const label = document.createElement("label");
            label.className = "team-user-option";

            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.value = user.id;

            const text = document.createElement("span");
            text.textContent = user.name;

            checkbox.addEventListener("change", syncUserSummary);
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
        } catch (error) {
            setTeamStatus(error.message || t("teams.error"));
        } finally {
            teamsLoading = false;
            renderTeams();
        }
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
        loadUsers(true);

        requestAnimationFrame(() => {
            refs.teamModal.classList.add("is-open");
        });

        window.setTimeout(() => {
            if (refs.teamModal && !refs.teamModal.hidden && refs.teamNameInput) {
                refs.teamNameInput.focus();
            }
        }, 120);
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
                teamsLoaded = true;
            }
            if (refs.teamForm) {
                refs.teamForm.reset();
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

    return {
        openTeamModal,
        closeTeamModal,
        submitTeam,
        toggleUserMenu,
        closeUserMenu,
        syncTeamButtons
    };
}
