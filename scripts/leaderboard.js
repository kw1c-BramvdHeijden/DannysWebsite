const table = document.querySelector("[data-leaderboard-table]");
const searchInput = document.querySelector("[data-team-search]");

function getRows() {
    if (!table) {
        return [];
    }

    return Array.from(table.querySelectorAll("tbody tr"));
}

function updateRanks(rows) {
    // De ranking wordt opnieuw genummerd na zoeken of sorteren.
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

    // Alleen de al geladen PHP data wordt gefilterd.
    rows.forEach((row) => {
        const teamName = row.querySelector("[data-team-name]")?.textContent.toLowerCase() || "";
        row.hidden = searchTerm !== "" && !teamName.includes(searchTerm);
    });

    updateRanks(rows);
}

function sortRows(sortKey, direction) {
    if (!table) {
        return;
    }

    const body = table.querySelector("tbody");
    const rows = getRows();
    const columnMap = {
        team_name: 1,
        played: 2,
        won: 3,
        lost: 4,
    };
    const columnNumber = columnMap[sortKey];

    if (!body || columnNumber === undefined) {
        return;
    }

    rows.sort((left, right) => {
        const leftValue = left.cells[columnNumber].textContent.trim();
        const rightValue = right.cells[columnNumber].textContent.trim();

        if (sortKey === "team_name") {
            return direction * leftValue.localeCompare(rightValue, "nl");
        }

        return direction * ((Number(leftValue) || 0) - (Number(rightValue) || 0));
    });

    rows.forEach((row) => body.appendChild(row));
    updateRanks(rows);
}

table?.querySelectorAll("[data-sort-key]").forEach((header) => {
    header.addEventListener("click", () => {
        // Klik nog een keer op dezelfde kolom om de richting om te draaien.
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
