<?php
use \server\serverFactory;
use executer\sendFactory;

require_once 'env.php';
require_once ROOT . '/autoload.php';
require_once ROOT . '/vendor/autoload.php';
define("_NAMESPACE", "slaver-send");

$server = serverFactory::loadServer(serverFactory::SLAVER);
$server->init();
$server->loadExecuter(sendFactory::create());
$server->startUp();
