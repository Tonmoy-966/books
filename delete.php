<?php
require "includes/db.php";
require "includes/auth.php";
requireLogin();


require_once __DIR__ . "/vendor/autoload.php";
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . "/templates");
$twig = new \Twig\Environment($loader);

$id = $_GET["id"];
$stmt = $pdo->prepare("DELETE FROM books WHERE book_id=?");
$stmt->execute([$id]);

header("Location: index.php");
exit;
?>
