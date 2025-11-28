<?php
require "../includes/db.php";

$q = "%" . $_GET["q"] . "%";

$stmt = $pdo->prepare("SELECT * FROM books WHERE title LIKE ? OR author LIKE ? OR genre LIKE ?");
$stmt->execute([$q,$q,$q]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $b) {
    echo "<div class='search-item'>"
        . "<strong>".htmlspecialchars($b['title'])."</strong> by "
        . htmlspecialchars($b['author'])
        . " (" . $b['year'] . ")</div>";
}
?>
