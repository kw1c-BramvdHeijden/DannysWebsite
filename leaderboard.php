<?php
// Tijdelijke voorbeelddata totdat de database later terugkomt.
$leaderboard = [
    ['team_name' => 'Team Danny', 'played' => 12, 'won' => 9, 'lost' => 3],
    ['team_name' => 'Boules Bazen', 'played' => 12, 'won' => 8, 'lost' => 4],
    ['team_name' => 'De Mikpunten', 'played' => 11, 'won' => 7, 'lost' => 4],
    ['team_name' => 'IJzeren Ballen', 'played' => 10, 'won' => 6, 'lost' => 4],
    ['team_name' => 'Team Pleinzicht', 'played' => 10, 'won' => 4, 'lost' => 6],
];

// Deze helper maakt tekst veilig voor HTML.
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard</title>
    <link rel="stylesheet" href="leaderboard.css">
</head>
<body>
    <main class="leaderboard-page">
        <section class="leaderboard-card" aria-labelledby="leaderboard-title">
            <div class="leaderboard-header">
                <div>
                    <p class="eyebrow">Jeu de boules</p>
                    <h1 id="leaderboard-title">Leaderboard</h1>
                    <p class="intro">Bekijk de huidige stand per team.</p>
                </div>

                <label class="team-search">
                    <span>Team zoeken</span>
                    <input type="search" data-team-search placeholder="Bijvoorbeeld: Team Danny">
                </label>
            </div>

            <div class="table-wrap">
                <table class="leaderboard-table" data-leaderboard-table>
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col" data-sort-key="team_name">Team</th>
                            <th scope="col" data-sort-key="played">Gespeeld</th>
                            <th scope="col" data-sort-key="won">Gewonnen</th>
                            <th scope="col" data-sort-key="lost">Verloren</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaderboard as $position => $team): ?>
                            <tr>
                                <td data-label="#" data-rank="<?= $position + 1 ?>"><?= $position + 1 ?></td>
                                <td data-label="Team" data-team-name="<?= e($team['team_name']) ?>">
                                    <?= e($team['team_name']) ?>
                                </td>
                                <td data-label="Gespeeld"><?= e($team['played']) ?></td>
                                <td data-label="Gewonnen"><?= e($team['won']) ?></td>
                                <td data-label="Verloren"><?= e($team['lost']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="leaderboard.js"></script>
</body>
</html>
