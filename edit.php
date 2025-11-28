<?php
require "includes/db.php";
require "includes/auth.php";
requireLogin();


$uploadDir = __DIR__ . '/uploads/covers/';
if (!is_dir($uploadDir)) {
 
    @mkdir($uploadDir, 0755, true);
}


require_once __DIR__ . "/vendor/autoload.php";
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . "/templates");
$twig = new \Twig\Environment($loader);


$idRaw = null;
if (isset($_GET['id'])) {
    $idRaw = $_GET['id'];
} elseif (isset($_POST['id'])) {
    $idRaw = $_POST['id'];
}

if ($idRaw === null || !ctype_digit((string)$idRaw)) {
   
    header("Location: index.php?error=invalid_id");
    exit;
}

$id = (int)$idRaw;


$stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
   
    header("Location: index.php?error=book_not_found");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    $title  = isset($_POST["title"]) ? trim($_POST["title"]) : $book['title'];
    $author = isset($_POST["author"]) ? trim($_POST["author"]) : $book['author'];
    $genre  = isset($_POST["genre"]) ? trim($_POST["genre"]) : $book['genre'];
    $year   = isset($_POST["year"]) && $_POST["year"] !== '' ? intval($_POST["year"]) : ($book['year'] ?? null);

    if ($title === '' || $author === '') {
        $error = "Title and Author required";
    } else {
        
        $cover_image = $book['cover_image'] ?? null; 

        if (isset($_FILES['cover_image']) && isset($_FILES['cover_image']['error']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $f = $_FILES['cover_image'];

            if ($f['error'] !== UPLOAD_ERR_OK) {
                $error = "Image upload error (code {$f['error']}).";
            } else {
               
                $maxSize = 2 * 1024 * 1024; 
                if ($f['size'] > $maxSize) {
                    $error = "Image too large (max 2MB).";
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $f['tmp_name']);
                    finfo_close($finfo);

                    $allowed = [
                        'image/jpeg' => 'jpg',
                        'image/pjpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp',
                        'image/gif'  => 'gif'
                    ];

                    if (!array_key_exists($mime, $allowed)) {
                        $error = "Unsupported image type.";
                    } else {
                       
                        $ext = $allowed[$mime];
                        $fileName = uniqid('cover_', true) . '.' . $ext;
                        $uploadFile = $uploadDir . $fileName;

                       
                        if (!is_dir($uploadDir)) {
                            
                            @mkdir($uploadDir, 0755, true);
                        }

                        if (move_uploaded_file($f['tmp_name'], $uploadFile)) {
                            
                            @chmod($uploadFile, 0644);

                            
                            if (!empty($cover_image) && file_exists($uploadDir . $cover_image) && ($uploadDir . $cover_image) !== $uploadFile) {
                                @unlink($uploadDir . $cover_image);
                            }

                          
                            $cover_image = $fileName;
                        } else {
                            $error = "Failed to move uploaded file.";
                        }
                    }
                }
            }
        }

        
        if ($error === '') {
            $stmt = $pdo->prepare("UPDATE books SET title=?, author=?, genre=?, year=?, cover_image=? WHERE book_id=?");
            $stmt->execute([
                $title,
                $author,
                $genre ?: null,
                $year,
                $cover_image,
                $id
            ]);

            header("Location: index.php");
            exit;
        }
    }
}


echo $twig->render("edit.twig", ["book" => $book, "error" => $error]);
