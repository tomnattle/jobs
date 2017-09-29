<?php
require_once 'env.php';
require_once ROOT . '/autoload.php';
require_once ROOT . '/vendor/autoload.php';
define("_NAMESPACE", "test");
var_dump($argv);
run($argv);
//
function run($arg)
{
    $callback = $arg[1];
    call_user_func($callback);
}

function addTask()
{
    fwrite(STDOUT, "choise task type： \n");
}


