<?php

set_include_path(implode(PATH_SEPARATOR, [
    __DIR__,
    __DIR__."/../",
    __DIR__."/fixtures/",
    get_include_path()
]));

require_once "vendor/autoload.php";

// Fixture autoloader.
spl_autoload_register(function($className){
    $filename = $className.".php";
    if(file_exists(__DIR__."/fixtures/".$filename)){
        require_once ($filename);
        return true;
    }
    return false;
});