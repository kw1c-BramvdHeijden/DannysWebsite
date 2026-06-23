<?php
$host = "localhost";
$dbname = "dannyproject";
$username = "root";
$password = "";

/*$host = "st1739531586.splsites.nl";
$dbname = "st1739531586";
$username = "st1739531586";
$password = "K6sBm9z1fabKwtj";
*/
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        )
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

?>
