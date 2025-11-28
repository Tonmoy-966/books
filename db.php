<?php
$host = "localhost";
$dbname = "db2407966";
$user = "2407966";
$pass = "Jibontejpata@007";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                   $user,
                   $pass,
                   [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
