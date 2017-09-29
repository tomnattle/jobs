<?php
namespace server;

class serverFactory
{

    static $server;

    const MASTER = "master";

    const SLAVER = "slaver";

    // 加载服务器
    public static function loadServer($type)
    {
        switch ($type) {
            case self::MASTER:
                self::$server = new master();
                break;
            case self::SLAVER:
                self::$server = new slaver();
                break;
            default:
                throw new \Exception("unkonw server type:" . $type);
        }
        return self::$server;
    }
}