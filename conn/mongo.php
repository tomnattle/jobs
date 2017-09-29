<?php
namespace conn;

use util\configManager;

class mongo
{

    public static function getConn($database = null)
    {
        $config = configManager::loadSysConfig('mongo')[ENV];
        if ($database)
            $config['database'] = $database;
        
        if (!isset($config['username'])){
        	$conn = new \MongoClient("mongodb://" . $config['host'] . ":" . $config['port'] . "/" . $config['database'] ."?wTimeoutMS=200000&socketTimeoutMS=300000");
        }else{
        	$conn = new \MongoClient("mongodb://" . $config['username'] . ":" . $config['password'] . "@" . $config['host'] . ":" . $config['port'] . "/" . $config['database'] ."?wTimeoutMS=200000&socketTimeoutMS=300000");
        }

        return $conn->selectDb($config['database']);
    }
}
