<?php

define("DS", DIRECTORY_SEPARATOR);

spl_autoload_register(function($class) {
    $root = dirname(__DIR__);
    $file = $root.DS.str_replace('\\', DS, $class).'.php';
    if (is_readable($file)) {
        require $file;
    }
});

require dirname(__DIR__).DS.'vendor'.DS.'autoload.php';