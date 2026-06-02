<?php


$host = "st1739531586.splsites.nl";
$dbname = "st1739531586";
$username = "st1739531586";
$password = "K6sBm9z1fabKwtj";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
