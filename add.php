<?php
require "includes/db.php";
require "includes/auth.php";
requireLogin();

$uploadDir = __DIR__ . '/uploads/covers/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}


require_once __DIR__ . "/vendor/autoload.php";
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . "/templates");
$twig = new \Twig\Environment($loader);

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $author = trim($_POST["author"]);
    $genre = trim($_POST["genre"]);
    $year = intval($_POST["year"]);

    if ($title == "" || $author == "") {
        $error = "Title and Author required";
    } else {
        
        $cover_image = null;
        if (is_uploaded_file($_FILES['cover_image']['tmp_name'])) {
            $fileName = uniqid() . '.jpg';
            $uploadFile = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadFile)) {
                $cover_image = $fileName;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO books (title, author, genre, year, cover_image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $author, $genre, $year, $cover_image]);
        header("Location: index.php");
        exit;
    }
}

echo $twig->render("add.twig", ["error" => $error]);
?>