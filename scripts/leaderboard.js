const table = document.querySelector("[data-leaderboard-table]");
const searchInput = document.querySelector("[data-team-search]");
const winsSortSelect = document.querySelector("[data-wins-sort]");

function getFeedHref() {
    return document.body?.dataset.leaderboardFeedHref || "leaderboard-feed.php";
}

function getRows() {
    if (!table) {
        return [];
    }

    return Array.from(table.querySelectorAll("tbody tr")).filter((row) => row.dataset.teamName);
}

function updateRanks(rows) {
    let visibleRank = 1;

    rows.forEach((row) => {
        if (row.hidden) {
            return;
        }

        row.cells[0].textContent = String(visibleRank);
        row.cells[0].dataset.rank = String(visibleRank);
        visibleRank += 1;
    });
}

function filterTeams() {
    const searchTerm = (searchInput?.value || "").trim().toLowerCase();
    const rows = getRows();

    rows.forEach((row) => {
        const teamName = (row.dataset.teamName || "").toLowerCase();
        row.hidden = searchTerm !== "" && !teamName.includes(searchTerm);
    });

    updateRanks(rows);
}

function getSortValue(row, sortKey) {
    if (sortKey === "team_name") {
        return row.dataset.teamName || "";
    }

    if (sortKey === "trend") {
        return {
            down: 0,
            flat: 1,
            up: 2
        }[row.dataset.trend || "flat"] || 0;
    }

    return Number(row.dataset[sortKey]) || 0;
}

function compareRows(sortKey, direction) {
    return (left, right) => {
        const leftValue = getSortValue(left, sortKey);
        const rightValue = getSortValue(right, sortKey);

        if (typeof leftValue === "string" || typeof rightValue === "string") {
            return direction * String(leftValue).localeCompare(String(rightValue), "nl");
        }

        const numericCompare = Number(leftValue) - Number(rightValue);
        if (numericCompare !== 0) {
            return direction * numericCompare;
        }

        return String(left.dataset.teamName || "").localeCompare(String(right.dataset.teamName || ""), "nl");
    };
}

function sortRows(sortKey, direction) {
    if (!table) {
        return;
    }

    const body = table.querySelector("tbody");
    const rows = getRows();

    if (!body) {
        return;
    }

    rows.sort(compareRows(sortKey, direction));
    rows.forEach((row) => body.appendChild(row));
    updateRanks(rows);
}

function sortByWinsFilter() {
    const direction = winsSortSelect?.value === "asc" ? 1 : -1;
    sortRows("won", direction);
    filterTeams();
}

table?.querySelectorAll("[data-sort-key]").forEach((header) => {
    header.addEventListener("click", () => {
        const sortKey = header.dataset.sortKey;
        const currentDirection = Number(header.dataset.sortDirection || -1);
        const nextDirection = currentDirection * -1;

        table.querySelectorAll("[data-sort-key]").forEach((item) => {
            delete item.dataset.sortDirection;
        });

        header.dataset.sortDirection = String(nextDirection);
        sortRows(sortKey, nextDirection);
        filterTeams();
    });
});

searchInput?.addEventListener("input", filterTeams);
winsSortSelect?.addEventListener("change", sortByWinsFilter);
sortByWinsFilter();

table?.addEventListener("click", async (event) => {
    const target = event.target;
    if (!(target instanceof Element)) {
        return;
    }

    const deleteButton = target.closest("[data-leaderboard-delete-team]");
    if (!deleteButton) {
        return;
    }

    const teamId = deleteButton.getAttribute("data-leaderboard-delete-team");
    if (!teamId || !window.confirm("Weet je zeker dat je dit team uit de leaderboard data wilt verwijderen?")) {
        return;
    }

    try {
        const response = await fetch(getFeedHref(), {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json"
            },
            body: JSON.stringify({
                action: "deleteTeam",
                teamId,
                sort: winsSortSelect?.value || "desc"
            })
        });
        const result = await response.json().catch(() => ({}));

        if (!response.ok || result.deleted !== true) {
            throw new Error(result.error || "Team kon niet worden verwijderd.");
        }

        window.location.reload();
    } catch (error) {
        window.alert(error.message || "Team kon niet worden verwijderd.");
    }
});
