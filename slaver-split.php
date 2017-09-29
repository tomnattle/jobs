<?php
use \server\serverFactory;
use executer\splitFactory;

require_once 'env.php';
require_once ROOT . '/autoload.php';
require_once ROOT . '/vendor/autoload.php';
define("_NAMESPACE", "slaver-split");

$server = serverFactory::loadServer(serverFactory::SLAVER);
$server->init();
$server->loadExecuter(splitFactory::create());
$server->startUp();
