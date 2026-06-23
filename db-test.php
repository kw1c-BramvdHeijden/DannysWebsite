<?php

ob_start();
include_once __DIR__ . "/includes/db.php";
ob_end_clean();

$tables = [];
$tableColumns = [];
$error = "";

try {
    foreach ($pdo->query("SHOW TABLES") as $row) {
        $table = reset($row);
        $tables[] = $table;
        $tableColumns[$table] = [];

        foreach ($pdo->query("DESCRIBE `$table`") as $column) {
            $tableColumns[$table][] = $column;
        }
    }
} catch (PDOException $exception) {
    $error = $exception->getMessage();
}

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database test</title>
    <style>
        body {
            margin: 40px;
            font-family: Arial, sans-serif;
            color: #1f2933;
            background: #f6f1e8;
        }

        main {
            max-width: 760px;
            padding: 24px;
            border: 1px solid #ded4c5;
            border-radius: 8px;
            background: #fffaf2;
        }

        code,
        li,
        td,
        th {
            font-size: 16px;
        }

        table {
            width: 100%;
            margin: 12px 0 28px;
            border-collapse: collapse;
            background: #fff;
        }

        th,
        td {
            padding: 8px 10px;
            border: 1px solid #ded4c5;
            text-align: left;
        }

        th {
            background: #f3eadb;
        }

        .ok {
            color: #216e39;
            font-weight: 700;
        }

        .error {
            color: #b42318;
            font-weight: 700;
        }
    </style>
</head>
<body>
<main>
    <h1>Database test</h1>

    <?php if ($error): ?>
        <p class="error">Query mislukt.</p>
        <p><code><?php echo htmlspecialchars($error); ?></code></p>
    <?php else: ?>
        <p class="ok">Databaseverbinding werkt.</p>
        <p>Gevonden tabellen: <?php echo count($tables); ?></p>

        <?php if ($tables): ?>
            <?php foreach ($tables as $table): ?>
                <h2><?php echo htmlspecialchars($table); ?></h2>

                <table>
                    <thead>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Null</th>
                        <th>Key</th>
                        <th>Default</th>
                        <th>Extra</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tableColumns[$table] as $column): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($column["Field"]); ?></td>
                            <td><?php echo htmlspecialchars($column["Type"]); ?></td>
                            <td><?php echo htmlspecialchars($column["Null"]); ?></td>
                            <td><?php echo htmlspecialchars($column["Key"]); ?></td>
                            <td><?php echo htmlspecialchars((string) $column["Default"]); ?></td>
                            <td><?php echo htmlspecialchars($column["Extra"]); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</main>
</body>
</html>
