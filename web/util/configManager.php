<?php
namespace util;

class configManager
{

    public static $_config;

    public static $_sysConfig;
    // 加载配置
    public static function loadConfig($name, $flag = 0)
    {
        // 读取配置
        $config[$name] = require ROOT . '/config/' . _NAMESPACE . '/' . $name . '.php';
        self::$_config = $config;
        if ($flag)
            return self::$_config;
        return self::$_config[$name];
    }
    // 加载系统配置
    public static function loadSysConfig($name, $flag = 0)
    {
        // 读取配置
        $_sysConfig[$name] = require ROOT . '/config/' . $name . '.php';
        self::$_sysConfig = $_sysConfig;
        if ($flag)
            return self::$_sysConfig;
        return self::$_sysConfig[$name];
    }
}

?>