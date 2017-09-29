<?php
use server\serverFactory;
use process\importFactory;
use process\exportFactory;
use process\mailSendFactory;
use process\mailSplitFactory;
use process\exportReportFactory;
use process\smsSendFactory;

require_once 'env.php';
require_once ROOT . '/autoload.php';
require_once ROOT . '/vendor/autoload.php';
define("_NAMESPACE", "master");

// 创建matser
$server = serverFactory::loadServer(serverFactory::MASTER);

// 添加守护进程
$server->addProcess(mailSplitFactory::create());
$server->addProcess(mailSendFactory::create());
$server->addProcess(exportFactory::create());
$server->addProcess(importFactory::create());
$server->addProcess(exportReportFactory::create());
$server->addProcess(smsSendFactory::create());

// 初始并启动
$server->init();
$server->startUp();