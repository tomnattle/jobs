<?php
use \server\serverFactory;
use \executer\importFactory;

require_once 'env.php';
require_once ROOT . '/autoload.php';
require_once ROOT . '/vendor/autoload.php';
define("_NAMESPACE", "slaver-import");

$server = serverFactory::loadServer(serverFactory::SLAVER);
$server->init();
$server->loadExecuter(importFactory::create());
$server->startUp();
