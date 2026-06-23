export function createLeaderboardModule({ refs, state, t }) {
    let leaderboardModalTimer = 0;
    let leaderboardRefreshTimer = 0;
    let leaderboardRefreshInFlight = false;
    const leaderboardRefreshMs = 30000;

    function getLeaderboardCollection() {
        return state.leaderboard
            .slice()
            .sort(compareLeaderboardEntries);
    }

    function compareLeaderboardEntries(left, right) {
        const sortDirection = state.leaderboardWinsSort === "asc" ? "asc" : "desc";
        const primaryDirection = sortDirection === "asc" ? 1 : -1;
        const winsCompare = Number(left.won) - Number(right.won);

        if (winsCompare !== 0) {
            return primaryDirection * winsCompare;
        }

        const diffCompare = Number(left.diff) - Number(right.diff);
        if (diffCompare !== 0) {
            return primaryDirection * diffCompare;
        }

        const lossCompare = Number(left.lost) - Number(right.lost);
        if (lossCompare !== 0) {
            return sortDirection === "desc" ? lossCompare : -lossCompare;
        }

        return left.team.localeCompare(right.team, state.lang === "en" ? "en" : "nl");
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

    function normalizeFeedNumber(value) {
        return Number.isFinite(Number(value)) ? Number(value) : 0;
    }

    function normalizeFeedEntry(entry) {
        if (!entry || typeof entry !== "object") {
            return null;
        }

        const team = typeof entry.team === "string"
            ? entry.team.trim()
            : (typeof entry.team_name === "string" ? entry.team_name.trim() : "");

        if (!team) {
            return null;
        }

        const won = normalizeFeedNumber(entry.won !== undefined ? entry.won : entry.wins);

        return {
            id: typeof entry.id === "string" || typeof entry.id === "number"
                ? String(entry.id)
                : (typeof entry.teamId === "string" || typeof entry.teamId === "number"
                    ? String(entry.teamId)
                    : (typeof entry.team_id === "string" || typeof entry.team_id === "number" ? String(entry.team_id) : team)),
            team,
            played: normalizeFeedNumber(entry.played),
            won,
            lost: normalizeFeedNumber(entry.lost !== undefined ? entry.lost : entry.losses),
            diff: normalizeFeedNumber(entry.diff),
            points: normalizeFeedNumber(entry.points !== undefined ? entry.points : won),
            trend: entry.trend === "up" || entry.trend === "down" ? entry.trend : "flat",
            players: Array.isArray(entry.players) ? entry.players : []
        };
    }

    function getLeaderboardFeedHref() {
        return refs.body?.dataset.leaderboardFeedHref || "";
    }

    function canManageLeaderboard() {
        return state.loggedIn && state.role === "admin";
    }

    async function refreshLeaderboardFromServer() {
        const feedHref = getLeaderboardFeedHref();
        if (!feedHref || typeof fetch !== "function" || leaderboardRefreshInFlight) {
            return;
        }

        leaderboardRefreshInFlight = true;

        try {
            const response = await fetch(feedHref, {
                cache: "no-store",
                headers: {
                    Accept: "application/json"
                }
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            if (payload.error) {
                return;
            }

            if (payload.role === "admin") {
                state.role = "admin";
                state.accountRole = "admin";
            }

            const nextLeaderboard = Array.isArray(payload.leaderboard)
                ? payload.leaderboard.map(normalizeFeedEntry).filter(Boolean)
                : [];

            state.leaderboard = nextLeaderboard;
            renderLeaderboard();
        } catch (error) {
            console.error("Could not refresh leaderboard", error);
        } finally {
            leaderboardRefreshInFlight = false;
        }
    }

    function startLiveUpdates() {
        if (leaderboardRefreshTimer || !getLeaderboardFeedHref() || (!refs.leaderboardPreviewList && !refs.leaderboardTableBody)) {
            return;
        }

        refreshLeaderboardFromServer();
        leaderboardRefreshTimer = window.setInterval(refreshLeaderboardFromServer, leaderboardRefreshMs);
    }

    function getSelectedLeaderboardTeam() {
        if (state.leaderboardTeamFilter === "all") {
            return null;
        }

        return getLeaderboardCollection().find((entry) => entry.id === state.leaderboardTeamFilter) || null;
    }

    function syncLeaderboardTeamFilter() {
        if (!refs.leaderboardTeamFilter) {
            return;
        }

        const leaderboard = getLeaderboardCollection();
        const selectedValue = getSelectedLeaderboardTeam()?.id || "all";
        refs.leaderboardTeamFilter.innerHTML = "";
        refs.leaderboardTeamFilter.setAttribute("aria-label", t("leaderboard.teamFilter.aria"));

        const allOption = document.createElement("option");
        allOption.value = "all";
        allOption.textContent = t("leaderboard.teamFilter.all");
        refs.leaderboardTeamFilter.appendChild(allOption);

        leaderboard.forEach((entry) => {
            const option = document.createElement("option");
            option.value = entry.id;
            option.textContent = entry.team;
            refs.leaderboardTeamFilter.appendChild(option);
        });

        refs.leaderboardTeamFilter.disabled = leaderboard.length === 0;
        refs.leaderboardTeamFilter.value = selectedValue;
    }

    function syncLeaderboardSortFilter() {
        if (!refs.leaderboardSortFilter) {
            return;
        }

        refs.leaderboardSortFilter.value = state.leaderboardWinsSort === "asc" ? "asc" : "desc";
    }

    function applyLeaderboardPayload(payload) {
        const nextLeaderboard = Array.isArray(payload.leaderboard)
            ? payload.leaderboard.map(normalizeFeedEntry).filter(Boolean)
            : [];

        state.leaderboard = nextLeaderboard;
        state.leaderboardTeamFilter = "all";
        renderLeaderboard();
    }

    async function deleteLeaderboardTeam(teamId) {
        if (!canManageLeaderboard() || !teamId) {
            return;
        }

        if (!window.confirm(t("leaderboard.deleteConfirm"))) {
            return;
        }

        const feedHref = getLeaderboardFeedHref();
        if (!feedHref) {
            return;
        }

        try {
            const response = await fetch(feedHref, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json"
                },
                body: JSON.stringify({
                    action: "deleteTeam",
                    teamId,
                    sort: state.leaderboardWinsSort
                })
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok || payload.deleted !== true) {
                throw new Error(payload.error || t("leaderboard.deleteError"));
            }

            applyLeaderboardPayload(payload);
        } catch (error) {
            window.alert(error.message || t("leaderboard.deleteError"));
        }
    }

    function getLeaderboardUpdatedLabel() {
        const leaderboard = getLeaderboardCollection();
        if (leaderboard.length === 0) {
            return t("leaderboard.updatedEmpty");
        }

        const selectedTeam = getSelectedLeaderboardTeam();
        if (selectedTeam) {
            return `${t("leaderboard.selectedTeam")} ${selectedTeam.team}`;
        }

        const round = leaderboard.reduce((maxRound, entry) => Math.max(maxRound, Number(entry.played) || 0), 0);
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

        if (canManageLeaderboard()) {
            const deleteButton = document.createElement("button");
            deleteButton.type = "button";
            deleteButton.className = "leaderboard-delete-button";
            deleteButton.dataset.leaderboardDeleteTeam = entry.id;
            deleteButton.setAttribute("aria-label", `${t("leaderboard.deleteTeam")} ${entry.team}`);
            deleteButton.innerHTML = '<i class="fa-solid fa-trash" aria-hidden="true"></i><span>' + t("leaderboard.deleteTeam") + '</span>';
            teamCell.appendChild(deleteButton);
        }

        const wonCell = document.createElement("td");
        wonCell.dataset.label = t("leaderboard.column.won");
        wonCell.textContent = String(entry.won);

        const lostCell = document.createElement("td");
        lostCell.dataset.label = t("leaderboard.column.lost");
        lostCell.textContent = String(entry.lost);

        const diffCell = document.createElement("td");
        diffCell.dataset.label = t("leaderboard.column.diff");
        diffCell.textContent = formatLeaderboardDiff(entry.diff);

        const trendCell = document.createElement("td");
        trendCell.dataset.label = t("leaderboard.column.trend");
        const trendBadge = document.createElement("span");
        trendBadge.className = `leaderboard-trend-badge ${trendState}`;
        trendBadge.innerHTML = `<span aria-hidden="true">${getLeaderboardTrendSymbol(trendState)}</span><span>${getLeaderboardTrendLabel(trendState)}</span>`;
        trendCell.appendChild(trendBadge);

        row.append(rankCell, teamCell, wonCell, lostCell, diffCell, trendCell);
        return row;
    }

    function renderLeaderboard() {
        const leaderboard = getLeaderboardCollection();
        const selectedTeam = getSelectedLeaderboardTeam();
        const visibleLeaderboard = selectedTeam
            ? leaderboard.filter((entry) => entry.id === selectedTeam.id)
            : leaderboard;
        const ranksById = new Map(leaderboard.map((entry, index) => [entry.id, index + 1]));

        if (refs.leaderboardPreviewList) {
            refs.leaderboardPreviewList.innerHTML = "";

            if (leaderboard.length === 0) {
                const emptyItem = document.createElement("li");
                emptyItem.className = "leaderboard-empty";
                emptyItem.textContent = t("leaderboard.empty");
                refs.leaderboardPreviewList.appendChild(emptyItem);
            } else {
                leaderboard.slice(0, 5).forEach((entry, index) => {
                    refs.leaderboardPreviewList.appendChild(createLeaderboardPreviewItem(entry, index + 1));
                });
            }
        }

        syncLeaderboardTeamFilter();
        syncLeaderboardSortFilter();

        if (refs.leaderboardTable) {
            refs.leaderboardTable.dataset.mode = "teams";
        }

        if (refs.leaderboardTableBody) {
            refs.leaderboardTableBody.innerHTML = "";

            if (visibleLeaderboard.length === 0) {
                const row = document.createElement("tr");
                const cell = document.createElement("td");
                cell.colSpan = 6;
                cell.className = "leaderboard-empty-cell";
                cell.textContent = t("leaderboard.empty");
                row.appendChild(cell);
                refs.leaderboardTableBody.appendChild(row);
            } else {
                visibleLeaderboard.forEach((entry, index) => {
                    refs.leaderboardTableBody.appendChild(createLeaderboardTableRow(entry, ranksById.get(entry.id) || index + 1));
                });
            }
        }

        if (refs.leaderboardUpdatedText) {
            refs.leaderboardUpdatedText.textContent = getLeaderboardUpdatedLabel();
        }
    }

    function openLeaderboardModal() {
        if (!refs.leaderboardModal) {
            return;
        }

        refreshLeaderboardFromServer();
        clearTimeout(leaderboardModalTimer);
        refs.leaderboardModal.hidden = false;
        refs.body.classList.add("leaderboard-modal-open");

        requestAnimationFrame(() => {
            refs.leaderboardModal.classList.add("is-open");
        });

        window.setTimeout(() => {
            if (refs.leaderboardModal && !refs.leaderboardModal.hidden) {
                refs.leaderboardModalCloseButton?.focus();
            }
        }, 120);
    }

    function closeLeaderboardModal() {
        if (!refs.leaderboardModal || refs.leaderboardModal.hidden) {
            return;
        }

        clearTimeout(leaderboardModalTimer);
        refs.leaderboardModal.classList.remove("is-open");
        refs.body.classList.remove("leaderboard-modal-open");

        leaderboardModalTimer = window.setTimeout(() => {
            refs.leaderboardModal.hidden = true;
        }, 220);
    }

    return {
        renderLeaderboard,
        openLeaderboardModal,
        closeLeaderboardModal,
        deleteLeaderboardTeam,
        startLiveUpdates
    };
}
