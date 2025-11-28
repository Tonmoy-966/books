<?php


if (session_status() === PHP_SESSION_NONE) session_start();


$a = random_int(1, 9);
$b = random_int(1, 9);
$ops = ['+', '-'];
$op = $ops[array_rand($ops)];
$question = "$a $op $b = ?";
$answer = ($op === '+') ? ($a + $b) : ($a - $b);


$_SESSION['captcha_answer'] = $answer;
$_SESSION['captcha_ts'] = time();


if (function_exists('imagecreatetruecolor')) {
    $width = 180;
    $height = 46;
    $im = imagecreatetruecolor($width, $height);

    $bg = imagecolorallocate($im, 245, 245, 245);
    $textc = imagecolorallocate($im, 30, 30, 30);
    $noise = imagecolorallocate($im, 200, 200, 200);

    imagefilledrectangle($im, 0, 0, $width, $height, $bg);

   
    for ($i = 0; $i < 6; $i++) {
        imageline($im, random_int(0,$width), random_int(0,$height), random_int(0,$width), random_int(0,$height), $noise);
    }

   
    $font = __DIR__ . '/fonts/arial.ttf';
    if (file_exists($font)) {
        $fontSize = 18;
        $bbox = imagettfbbox($fontSize, 0, $font, $question);
        $textWidth = $bbox[2] - $bbox[0];
        $x = (int)(($width - $textWidth) / 2);
        $y = (int)(($height + $fontSize) / 2) - 4;
        imagettftext($im, $fontSize, 0, $x, $y, $textc, $font, $question);
    } else {
        imagestring($im, 5, 10, 12, $question, $textc);
    }

    header('Content-Type: image/png');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    imagepng($im);
    imagedestroy($im);
    exit;
} else {
    header('Content-Type: text/plain');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo $question;
    exit;
}
