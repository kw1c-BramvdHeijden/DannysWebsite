<?php
function render_leaderboard_panel()
{
    ?>
    <aside class="leaderboard-panel">
        <div class="panel-heading leaderboard-heading">
            <div class="leaderboard-title">
                <span class="competition-icon olive"><i class="fa-solid fa-ranking-star"></i></span>
                <h2 data-i18n="leaderboard.heading">LEADERBOARD</h2>
            </div>
            <button type="button" class="leaderboard-open-button" data-leaderboard-open>
                <span data-i18n="leaderboard.viewAll">Bekijk volledig leaderboard</span>
                <span aria-hidden="true">-&gt;</span>
            </button>
        </div>

        <ol class="leaderboard-list" data-leaderboard-list></ol>
    </aside>
    <?php
}

function render_leaderboard_modal()
{
    ?>
    <div class="leaderboard-modal" data-leaderboard-modal hidden>
        <div class="leaderboard-modal-backdrop" data-leaderboard-close></div>
        <div class="leaderboard-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="leaderboard-modal-title">
            <button type="button" class="leaderboard-modal-close" data-leaderboard-close data-leaderboard-close-button data-i18n-aria-label="leaderboard.modalClose" aria-label="Sluit leaderboardvenster">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="leaderboard-modal-shell">
                <div class="leaderboard-modal-copy">
                    <p class="photo-kicker" data-i18n="leaderboard.modalKicker">VOLLEDIGE STAND</p>
                    <h2 id="leaderboard-modal-title" data-i18n="leaderboard.modalTitle">Volledig leaderboard</h2>
                    <p class="upload-description" data-i18n="leaderboard.modalDescription">Bekijk alle teams, gespeelde wedstrijden en punten in een overzicht.</p>
                </div>

                <p class="leaderboard-modal-meta">
                    <span class="leaderboard-status-chip">
                        <i class="fa-solid fa-rotate-right"></i>
                        <span data-leaderboard-updated>Bijgewerkt na speelronde 14</span>
                    </span>
                </p>

                <div class="leaderboard-table-shell">
                    <table class="leaderboard-table">
                        <thead>
                            <tr>
                                <th scope="col" data-i18n="leaderboard.column.rank">#</th>
                                <th scope="col" class="leaderboard-team-header">
                                    <span class="leaderboard-team-header-copy" data-i18n="leaderboard.column.team">Team</span>
                                    <select class="leaderboard-team-filter" data-leaderboard-team-filter aria-label="Filter leaderboard op team">
                                        <option value="all">Alle teams</option>
                                    </select>
                                </th>
                                <th scope="col" data-i18n="leaderboard.column.played">Gespeeld</th>
                                <th scope="col" data-i18n="leaderboard.column.won">Gewonnen</th>
                                <th scope="col" data-i18n="leaderboard.column.diff">Puntverschil</th>
                                <th scope="col" data-i18n="leaderboard.column.points">Punten</th>
                                <th scope="col" data-i18n="leaderboard.column.trend">Trend</th>
                            </tr>
                        </thead>
                        <tbody data-leaderboard-table-body></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php
}
