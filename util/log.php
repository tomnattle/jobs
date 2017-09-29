<?php

namespace util;

class log {


    const EVENT = 'event';
    // php保存
    const ERROR = "erro";
    // 失败提示
    const WARN = "warn";
    // 成功提示
    const INFO = "info";
    // 调试值
    const DEBUG = "debug";
    // 接口调用失败
    const FAILURE = "failure";
    // 文件最大128M
    const maxSize = 128;
    
    const TYPE_LEN = 16;
    // 声称日志
    public static function write($type, $message, $level = self::INFO) {
        $level = substr($level, 0, 1);
        openlog("myScriptLog", LOG_PID | LOG_PERROR, LOG_LOCAL0);
        $pattern = "[#level] #time [#id] #message";
        $line = "[#id] #message";
        $line = str_replace('#level', $level, $pattern);
        $line = str_replace('#time', date("m-d H:i:s", time()), $line);
        $line = str_replace('#id', $type, $line);
        $line = str_replace('#message', $message, $line);
        // $line = $line . "\n";
        // openlog("task-process", LOG_PID | LOG_PERROR, LOG_LOCAL0);
        // syslog(static::getLevel($level), $line);
        $line = $line . PHP_EOL;
        echo $line;
        if ($type != 'keepalive') {
            log::saveTo($line, $level);
        }
    }
    // 保存为
    public static function saveTo($line, $level) {
        $path = ROOT . '/runtime/' . _NAMESPACE . '/log/';
        if (!is_dir($path)) {
            if (!is_dir(dirname($path)))
                mkdir(dirname($path));
            mkdir($path);
        }

        $file = $path . 'server.log';
        if (!is_file($file)) {
            touch($file);
        }
        if (filesize($file) > self::maxSize * 1024 * 1024) {
            for ($i = 0; $i < 100000; $i ++) {
                $newfile = $path . "server." . $i . ".log";
                if (!is_file($newfile)) {
                    copy($file, $newfile);
                    unlink($file);
                    break;
                }
            }
        }

        file_put_contents($file, $line, FILE_APPEND);
    }
    // 获取登记
    public static function getLevel($level) {
        switch ($level) {
            case "erro":
                return LOG_ERR;
                break;
            case "warn":
                return LOG_WARNING;
                break;
            case "info":
                return LOG_WARNING;
                break;
            case "debug":
                return LOG_DEBUG;
                break;
            case "failure":
                return LOG_WARNING;
                break;
        }
    }
    // 重复
    public static function repeat($string, $times = 40, $symbol = "=") {
        $times -= strlen($string);
        return str_repeat($symbol, $times / 2) . $string . str_repeat($symbol, $times / 2);
    }

}
