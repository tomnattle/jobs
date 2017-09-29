<?php
use \server\serverFactory;
use \process\importFactory;
use process\exportFactory;
use process\mailSendFactory;
use process\mailSplitFactory;

require_once 'env.php';
require_once ROOT . '/autoload.php';
require_once ROOT . '/vendor/autoload.php';
define("_NAMESPACE", "standby");

// master
$server = serverFactory::loadServer(serverFactory::MASTER);
$server->addProcess(mailSplitFactory::create());
$server->addProcess(mailSendFactory::create());
$server->addProcess(exportFactory::create());
$server->addProcess(importFactory::create());

$server->init();
$server->startUp();
//sudo /usr/local/Cellar/mysql/5.7.13/support-files/mysql.server start
