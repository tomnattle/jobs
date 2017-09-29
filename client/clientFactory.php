<?php
namespace client;

class clientFactory
{

    // 创建一个客户端
    public static function create($ip, $port)
    {
        $client = new \swoole_client(SWOOLE_SOCK_TCP);
        // 尝试链接
        if (! $client->connect($ip, $port, - 1))
            throw new \Exception("net can not connected.");
        return $client;
    }
}


