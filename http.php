<?php
use server\httpServer;
use util\configManager;

require_once 'env.php';
require_once ROOT . '/autoload.php';
require_once ROOT . '/vendor/autoload.php';

$http = new httpServer(configManager::loadSysConfig('http'));
$http->start();