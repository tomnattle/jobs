<?php
namespace conn;

use util\configManager;

class mysql
{

    static $conn;

    public static function getconn()
    {
        if (! self::$conn) {
            $config = configManager::loadSysConfig('conn');
            self::$conn = new \mysqli($config['host'], $config['user'], $config['password'], $config['database']);
        }
        if (self::$conn->connect_errno) {
            throw new \Exception("connect erro: " . self::$conn->connect_errno . ", " . self::$conn->connect_error);
        }
        return self::$conn;
    }
}

