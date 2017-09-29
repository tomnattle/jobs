<?php
namespace environment;

use server\Iserver;
use server\master as _master;
use server\slaver as _slaver;
use event\eventManager;
use server\event\server;
use util\configManager;

// 环境代理
class prox implements Ienvironment
{

    private $environment;

    // 设置server属性

    public function __construct(Iserver $server)
    {
        if ($server instanceof _master) {
            $this->environment = new master();
        } elseif ($server instanceof _slaver) {
            $this->environment = new slaver();
        } else {
            throw new \Exception("unkown serverType");
        }
        
        $this->init();
    }

    // 初始化
    public function init()
    {
        $config = configManager::loadConfig('environment');
        foreach ($config['buildDir'] as $name => $dir) {
            $config['buildDir'][$name] = str_replace("_namespace", _NAMESPACE, $dir);
        }
        
        foreach ($config['clearDir'] as $name => $dir) {
            $config['clearDir'][$name] = str_replace("_namespace", _NAMESPACE, $dir);
        }
        
        $this->environment->setConfig($config);
        $this->environment->init();
        
        // 启动后检测
        eventManager::addCallBack(server::START_UP, [
            $this,
            'check'
        ]);
        
        // 启动后清空目录
        eventManager::addCallBack(server::START_UP, [
            $this,
            'clearDir'
        ]);
        // 启动后重建目录
        eventManager::addCallBack(server::START_UP, [
            $this,
            'buildDir'
        ]);
    }

    public function setConfig($config)
    {
        throw new \Exception("abstract method.");
    }

    public function getConfig()
    {
        return $this->environment->getConfig();
    }

    public function buildDir()
    {
        $this->environment->buildDir();
    }

    public function clearDir($_dir = null)
    {
        $this->environment->clearDir($_dir);
    }

    public function check()
    {
        $this->environment->check();
    }
}