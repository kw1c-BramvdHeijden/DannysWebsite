export function createLeaderboardModule({ refs, state, t }) {
    let leaderboardModalTimer = 0;

    function getLeaderboardCollection() {
        return state.leaderboard
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
        if (!refs.leaderboardTeamFilter) {
            return;
        }

        const leaderboard = getLeaderboardCollection();
        const selectedValue = getSelectedLeaderboardTeam()?.team || "all";
        refs.leaderboardTeamFilter.innerHTML = "";
        refs.leaderboardTeamFilter.setAttribute("aria-label", t("leaderboard.teamFilter.aria"));

        const allOption = document.createElement("option");
        allOption.value = "all";
        allOption.textContent = t("leaderboard.teamFilter.all");
        refs.leaderboardTeamFilter.appendChild(allOption);

        leaderboard.forEach((entry) => {
            const option = document.createElement("option");
            option.value = entry.team;
            option.textContent = entry.team;
            refs.leaderboardTeamFilter.appendChild(option);
        });

        refs.leaderboardTeamFilter.disabled = leaderboard.length === 0;
        refs.leaderboardTeamFilter.value = selectedValue;
    }

    function getLeaderboardUpdatedLabel() {
        const leaderboard = getLeaderboardCollection();
        if (leaderboard.length === 0) {
            return t("leaderboard.updatedEmpty");
        }

        const selectedTeam = getSelectedLeaderboardTeam();
        if (selectedTeam) {
            return `${t("leaderboard.playersOf")} ${selectedTeam.team}`;
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

        if (refs.leaderboardTable) {
            refs.leaderboardTable.dataset.mode = selectedTeam ? "players" : "teams";
        }

        if (refs.leaderboardTableBody) {
            refs.leaderboardTableBody.innerHTML = "";

            if (leaderboard.length === 0) {
                const row = document.createElement("tr");
                const cell = document.createElement("td");
                cell.colSpan = 7;
                cell.className = "leaderboard-empty-cell";
                cell.textContent = t("leaderboard.empty");
                row.appendChild(cell);
                refs.leaderboardTableBody.appendChild(row);
            } else if (selectedTeam) {
                selectedTeam.players
                    .slice()
                    .sort((left, right) => right.played - left.played || left.name.localeCompare(right.name))
                    .forEach((player, index) => {
                        refs.leaderboardTableBody.appendChild(createLeaderboardPlayerRow(player, index + 1, selectedTeam.team));
                    });
            } else {
                leaderboard.forEach((entry, index) => {
                    refs.leaderboardTableBody.appendChild(createLeaderboardTableRow(entry, index + 1));
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
        closeLeaderboardModal
    };
}
