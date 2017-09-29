<?php
use server\serverFactory;
use process\importFactory;
use process\exportFactory;
use process\mailSendFactory;
use process\mailSplitFactory;
//use process\deleteColumnFactory;

require_once 'env.php';
require_once ROOT . '/autoload.php';
require_once ROOT . '/vendor/autoload.php';
define("_NAMESPACE", "server-standby-b");

// master
$server = serverFactory::loadServer(serverFactory::MASTER);

$server->addProcess(mailSplitFactory::create());
$server->addProcess(mailSendFactory::create());
$server->addProcess(exportFactory::create());
$server->addProcess(importFactory::create());
//$server->addProcess(deleteColumnFactory::create());

$server->init();
$server->startUp();
