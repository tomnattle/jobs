<?php
namespace environment;

use util\log;

// master 环境
class master implements Ienvironment
{

    public function init()
    {}

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function getConfig()
    {
        return $this->config;
    }
    // 创建目录
    public function buildDir()
    {
        $dirs = $this->config['buildDir'];
        foreach ($dirs as $name => $dir) {
            $_dir = $dir;
            $dir = ROOT . "/" . $dir;
            $flag = is_dir($dir);
            if (! $flag) {
                mkdir($dir);
            }
            log::write('build-dir', ! $flag ? "create dir [" . $_dir . "] ." : "dir[" . $_dir . "] exist, skip.");
        }
    }

    // 清空目录
    public function clearDir($_dir = null)
    {
        log::write('clear-dir', "start clear dir");
        $this->clear($_dir);
        log::write('clear-dir', "finish clear dir");
    }

    public function check()
    {}

    // 清空目录
    public function clear($_dir = null)
    {
        if ($_dir) {
            $this->clearFile($_dir);
        } else {
            $dirs = $this->config['clearDir'];
            foreach ($dirs as $dir) {
                if (is_dir(ROOT . "/" . $dir)) {
                    $this->clearFile(ROOT . "/" . $dir);
                }
                if (is_file(ROOT . "/" . $dir))
                    unlink(ROOT . "/" . $dir);
            }
        }
    }

    // 清空文件
    public function clearFile($dir)
    {
        $_dir = dir($dir);
        while ($file = $_dir->read()) {
            if (is_file($dir . $file)) {
                unlink($dir . $file);
                log::write('clear-dir', $dir . $file);
            } elseif (is_dir($dir . $file)) {
                if ($file != "." && $file != "..")
                    $this->clearFile($dir . $file . "/");
            }
        }
    }
}

