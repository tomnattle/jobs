<?php
use \server\serverFactory;
use executer\deleteColumnFactory;

require_once 'env.php';
require_once ROOT . '/autoload.php';
require_once ROOT . '/vendor/autoload.php';
define("_NAMESPACE", "slaver-delete-column");

$server = serverFactory::loadServer(serverFactory::SLAVER);
$server->init();
$server->loadExecuter(deleteColumnFactory::create());
$server->startUp();