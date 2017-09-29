<?php
use \server\serverFactory;
use executer\exportReportFactory;

require_once 'env.php';
require_once ROOT . '/autoload.php';
require_once ROOT . '/vendor/autoload.php';
define("_NAMESPACE", "slaver-report-export");

$server = serverFactory::loadServer(serverFactory::SLAVER);
$server->init();
$server->loadExecuter(exportReportFactory::create());
$server->startUp();
