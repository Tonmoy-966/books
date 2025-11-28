<?php
require "includes/db.php";


if (session_status() === PHP_SESSION_NONE) session_start();


require_once __DIR__ . "/vendor/autoload.php";
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . "/templates");
$twig = new \Twig\Environment($loader);

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"] ?? '';
    $password = $_POST["password"] ?? '';
    $captchaUser = trim($_POST['captcha'] ?? '');

    
    if (!isset($_SESSION['captcha_answer'])) {
        $error = "Captcha not available. Please reload the page.";
    } else {
        
        if (isset($_SESSION['captcha_ts']) && (time() - $_SESSION['captcha_ts'] > 300)) {
            $error = "Captcha expired. Please refresh and try again.";
        } elseif ($captchaUser === '' || !is_numeric($captchaUser)) {
            $error = "Please solve the captcha.";
        } elseif ((int)$captchaUser !== (int)$_SESSION['captcha_answer']) {
            $error = "Captcha incorrect. Try again.";
        }
    }

    
    if ($error === '') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
        $stmt->execute([$username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && password_verify($password, $row["password"])) {
           
            session_regenerate_id(true);
            $_SESSION["user_id"] = $row["user_id"];
           
            unset($_SESSION['captcha_answer'], $_SESSION['captcha_ts']);
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid username or password";
        }
    }
}

echo $twig->render("login.twig", ["error" => $error]);
?>
