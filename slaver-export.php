<?php
use \server\serverFactory;
use executer\exportFactory;

require_once 'env.php';
require_once ROOT . '/autoload.php';
require_once ROOT . '/vendor/autoload.php';
define("_NAMESPACE", "slaver-export");

// 创建slaver 初始并启动  加载导出任务处理脚本
$server = serverFactory::loadServer(serverFactory::SLAVER);
$server->init();
$server->loadExecuter(exportFactory::create());
$server->startUp();