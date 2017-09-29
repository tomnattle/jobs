<?php
namespace api;

class apiFactory
{

    static $apis = [];

    public static function get($name)
    {
        // 会产生问题，第一次添加正常，第二次添加变成了更新
        // if (! isset(self::$apis[$name])) {
        $class = new \ReflectionClass($name);
        self::$apis[$name] = $class->newInstanceArgs();
        // }
        return self::$apis[$name];
    }
}

