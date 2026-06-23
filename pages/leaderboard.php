<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/leaderboard-data.php";

$sortDirection = leaderboard_normalize_sort_direction(isset($_GET["sort"]) ? (string) $_GET["sort"] : "desc");
$leaderboard = leaderboard_fetch_team_rows($pdo, $sortDirection);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard</title>
    <link rel="stylesheet" href="../css/variables.css">
    <link rel="stylesheet" href="../css/base.css">
    <link rel="stylesheet" href="../css/leaderboard.css">
</head>
<body data-leaderboard-feed-href="leaderboard-feed.php">
<main class="leaderboard-page">
    <section class="leaderboard-card" aria-labelledby="leaderboard-title">
        <div class="leaderboard-header">
            <div>
                <p class="eyebrow">Jeu de boules</p>
                <h1 id="leaderboard-title">Leaderboard</h1>
                <p class="intro">Bekijk de huidige stand per team.</p>
            </div>

            <div class="leaderboard-controls">
                <label class="team-search">
                    <span>Team zoeken</span>
                    <input type="search" data-team-search placeholder="Bijvoorbeeld: Team Danny">
                </label>

                <label class="team-search">
                    <span>Sorteer op wins</span>
                    <select data-wins-sort>
                        <option value="desc" <?= $sortDirection === "asc" ? "" : "selected" ?>>Meeste wins eerst</option>
                        <option value="asc" <?= $sortDirection === "asc" ? "selected" : "" ?>>Minste wins eerst</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="table-wrap">
            <table class="leaderboard-table" data-leaderboard-table>
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col" data-sort-key="team_name">Team</th>
                    <th scope="col" data-sort-key="won">Wins</th>
                    <th scope="col" data-sort-key="lost">Losses</th>
                    <th scope="col" data-sort-key="diff">Puntverschil</th>
                    <th scope="col" data-sort-key="trend">Trend</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($leaderboard) === 0): ?>
                    <tr>
                        <td colspan="6" class="message">Nog geen teams beschikbaar.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($leaderboard as $position => $team): ?>
                    <?php
                    $rank = $position + 1;
                    $trend = $team["trend"] === "up" || $team["trend"] === "down" ? $team["trend"] : "flat";
                    ?>
                    <tr
                        data-team-name="<?= leaderboard_e($team["team_name"]) ?>"
                        data-won="<?= (int) $team["won"] ?>"
                        data-lost="<?= (int) $team["lost"] ?>"
                        data-diff="<?= (int) $team["diff"] ?>"
                        data-trend="<?= leaderboard_e($trend) ?>"
                    >
                        <td data-label="#" data-rank="<?= $rank ?>"><?= $rank ?></td>
                        <td data-label="Team" data-team-name="<?= leaderboard_e($team["team_name"]) ?>">
                            <?= leaderboard_e($team["team_name"]) ?>
                        </td>
                        <td data-label="Wins"><?= (int) $team["won"] ?></td>
                        <td data-label="Losses"><?= (int) $team["lost"] ?></td>
                        <td data-label="Puntverschil"><?= leaderboard_e(leaderboard_format_diff($team["diff"])) ?></td>
                        <td data-label="Trend">
                            <span class="leaderboard-trend-badge <?= leaderboard_e($trend) ?>">
                                <?= leaderboard_e(leaderboard_trend_label($trend)) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script src="../scripts/leaderboard.js"></script>
</body>
</html>
