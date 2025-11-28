<?php
namespace Twig\Loader;

class FilesystemLoader {
    private $path;

    public function __construct($path){
        $this->path = rtrim($path,'/');
    }

    public function getSource($template){
        $file = $this->path.'/'.$template;
        if(!file_exists($file)) throw new \Exception("Template not found: $template");
        return file_get_contents($file);
    }
}
