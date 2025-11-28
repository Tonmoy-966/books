<?php
spl_autoload_register(function ($class) {
    
    if (strpos($class, 'Twig\\') !== 0) {
        return;
    }
    $relative = substr($class, strlen('Twig\\')); 
    $relative = str_replace('\\', '/', $relative);
    $file = __DIR__ . '/Twig/' . $relative . '.php';
    if (file_exists($file)) {
        require $file;
    }
});
