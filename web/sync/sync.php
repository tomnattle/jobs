<?php
namespace sync;

use util\log;
use client\clientFactory;
use command\command;

class sync
{

    static $node_root = "";

    static $lock = "sync.lock";

    static $slaverDir = "";

    static $standbyDir = "";

    static $masterDir = "";

    static $isStandby = "isStandby.flag";

    static $isMaster = "isMaster.flag";
    // 配置
    public static function config($environment)
    {
        $config = $environment->getConfig();
        self::$node_root = ROOT . "/" . $config['buildDir']['node_root'];
        self::$lock = self::$node_root . self::$lock;
        self::$slaverDir = ROOT . "/" . $config['buildDir']['slaver'];
        self::$standbyDir = ROOT . "/" . $config['buildDir']['standby'];
        self::$masterDir = ROOT . "/" . $config['buildDir']['master'];
        self::$isStandby = self::$node_root . self::$isStandby;
        self::$isMaster = self::$node_root . self::$isMaster;
    }
    // 获取slaver列表
    public static function loadSlavers($_name = null)
    {
        $slavers = [];
        $fh = opendir(self::$slaverDir);
        while (($file = readdir($fh)) !== false) {
            if ($_name && $file != $_name)
                continue;
            
            if (is_dir(self::$slaverDir . $file) && ! in_array($file, [
                '.',
                '..'
            ])) {
                $slaver_dir1 = self::$slaverDir . $file;
                $fh1 = opendir($slaver_dir1);
                while (($file1 = readdir($fh1)) !== false) {
                    if (is_file($slaver_dir1 . "/" . $file1))
                        $slavers[$file][$file1] = file_get_contents($slaver_dir1 . "/" . $file1);
                }
                closedir($fh1);
            }
        }
        closedir($fh);
        return $slavers;
    }
    // 获取备用机器 列表
    public static function loadStandbys()
    {
        $standbys = [];
        $fh = opendir(self::$standbyDir);
        while (($file = readdir($fh)) !== false) {
            if (is_file(self::$standbyDir . $file))
                $standbys[$file] = file_get_contents(self::$standbyDir . $file);
        }
        closedir($fh);
        return $standbys;
    }
    // 获取master列表
    public static function loadMaster()
    {
        $master = [];
        $fh = opendir(self::$masterDir);
        while (($file = readdir($fh)) !== false) {
            if (is_file(self::$masterDir . $file))
                $master[$file] = file_get_contents(self::$masterDir . $file);
        }
        closedir($fh);
        return $master;
    }       
    // 开始
    public static function start()
    {
        log::write('sync', 'begin sycn server info, server lock');
        
        if (! self::$slaverDir || ! self::$standbyDir || ! self::$masterDir) {
            throw new \Exception('please set some dir.');
        }
        
        $data = [
            'slavers' => self::loadSlavers(),
            'standbys' => self::loadStandbys(),
            'master' => self::loadMaster()
        ];
        
        file_put_contents(self::$lock, date('Y-m-d H:i:s'));
        
        foreach ($data['standbys'] as $key => $standby) {
            $standby = json_decode($standby);
            try {
                $client = clientFactory::create($standby->ip, $standby->port);
                $cmd = command::create("syncInfo", $data, - 1, - 1, 'sync standby');
                $client->send($cmd->toString());
            } catch (\Exception $e) {
                log::write('standby-sync', 'skip sever[' . $standby->ip . ':' . $standby->port . ']');
                continue;
            }
        }
        
        foreach ($data['slavers'] as $cate) {
            foreach ($cate as $name => $slaver) {
                $slaver = json_decode($slaver);
                try {
                    $client = clientFactory::create($slaver->ip, $slaver->port);
                    $cmd = command::create("syncInfo", $data, - 1, - 1, 'sync slaver');
                    $client->send($cmd->toString());
                } catch (\Exception $e) {
                    log::write('slavers-sync', 'skip sever[' . $slaver->ip . ':' . $slaver->port . ']');
                    continue;
                }
            }
        }
        
        unlink(self::$lock);
        log::write('sync', 'finish sycn server info, server unlock');
        log::write('sync-report', 'slaver count: ' . count($data['slavers']) . ',standby count: ' . count($data['standbys']));
    }
    // 是否是备用机
    public static function isStandby()
    {
        return is_file(self::$isStandby) || (! is_file(self::$isMaster) && ! is_file(self::$isStandby));
    }
    // 是否是master
    public static function isMaster()
    {
        return is_file(self::$isMaster) || (! is_file(self::$isMaster) && ! is_file(self::$isStandby));
    }
    // 写入
    public static function put($file, $content)
    {
        file_put_contents($file, $content);
    }
    // 删除
    public static function del($file)
    {
        unlink($file);
    }
    // 获取
    public static function get($file)
    {
        return file_get_contents($file);
    }
    // 是否锁定
    public static function isLock()
    {
        return is_file(self::$lock);
    }
    // 保存
    public static function save($data)
    {
        log::write('sync-data', 'begin sync data on disk');
        if (isset($data['master'])) {
            $filename = self::$masterDir . "master.info";
            file_put_contents($filename, $data['master']);
        }
        
        if (isset($data['standbys']) && is_array($data['standbys'])) {
            foreach ($data['standbys'] as $file => $_data) {
                $filename = self::$standbyDir . $file;
                file_put_contents($filename, $_data);
            }
        }
        
        if (isset($data['slavers']) && is_array($data['slavers'])) {
            foreach ($data['slavers'] as $cate => $slavers) {
                foreach ($slavers as $name => $slaver) {
                    if (! is_dir(self::$slaverDir . $cate))
                        mkdir(self::$slaverDir . $cate);
                    $filename = self::$slaverDir . $cate . "/" . $name;
                    file_put_contents($filename, $slaver);
                }
            }
        }
        log::write('sync-data', 'finish sync data on disk');
    }
}

