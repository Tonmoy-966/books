<?php
require "includes/db.php";


if (session_status() === PHP_SESSION_NONE) session_start();


require_once __DIR__ . "/vendor/autoload.php";
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . "/templates");
$twig = new \Twig\Environment($loader);

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"] ?? '');
    $password = $_POST["password"] ?? '';
    $captchaUser = trim($_POST['captcha'] ?? '');

   
    if ($username === '' || $password === '') {
        $error = "Please fill in both username and password.";
    }

    
    if ($error === '') {
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
    }

 
    if ($error === '') {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $hashed]);
       
        unset($_SESSION['captcha_answer'], $_SESSION['captcha_ts']);
        header("Location: login.php");
        exit;
    }
}

echo $twig->render("register.twig", ["error" => $error]);
?>
