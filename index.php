<?php
require "includes/db.php";
require "includes/auth.php";


require_once __DIR__ . "/vendor/autoload.php";

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . "/templates");
$twig = new \Twig\Environment($loader);


$q = isset($_GET['q']) ? trim($_GET['q']) : '';


$allowedSort = ["title", "author", "year"];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSort) ? $_GET['sort'] : "book_id";

$allowedDir = ["asc", "desc"];
$dir = isset($_GET['dir']) && in_array(strtolower($_GET['dir']), $allowedDir) ? strtolower($_GET['dir']) : "desc";


try {
    if ($q !== '') {
        $term = '%' . $q . '%';

        $stmt = $pdo->prepare("
            SELECT * FROM books
            WHERE title LIKE ? OR author LIKE ?
            ORDER BY $sort $dir
        ");
        $stmt->execute([$term, $term]);
    } else {
        $stmt = $pdo->query("
            SELECT * FROM books
            ORDER BY $sort $dir
        ");
    }

    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $books = [];
}


foreach ($books as &$book) {
    if (empty($book['cover_image'])) {
        $book['cover_image'] = null; 
    }
}



echo $twig->render("index.twig", [
    "books" => $books,
    "logged" => function_exists('isLoggedIn') ? isLoggedIn() : isset($_SESSION["user_id"]),
    "q" => $q,
    "sort" => $sort,
    "dir" => $dir
]);
?>