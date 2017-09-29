<?php
namespace process;

use util\log;
use slaver\slaverManager;
use task\taskManager;
use slaver\slaver;

class base implements Iprocess
{
    // 名称
    protected $name = "";
    // 服务器
    protected $server;
    // 进程
    protected $process;
    // pid
    protected $pid;
    // 伺服管理器
    protected $slaverManager;
    // 任务管理器
    protected $taskManager;
    
    // 构造方法
    public function __construct()
    {
        $this->name = $this->getName();
        $this->process = new \swoole_process([
            $this,
            'process'
        ]);
    }
    
    // 入口方法
    public function process()
    {
        throw new \Exception('unsupport method.');
    }
    // 获取名称
    public function getName()
    {
        return $this->name;
    }
    
    // 设置服务器管理
    public function getSlaverManager()
    {
        if (! $this->slaverManager) {
            $this->slaverManager = new slaverManager($this);
        }
        return $this->slaverManager;
    }
    
    // 设置活动管理器
    public function getTaskManager()
    {
        if (! $this->taskManager) {
            $this->taskManager;
        }
        return $this->taskManager = new taskManager($this);
    }
    
    // 分派工作
    public function assignJob($jobId, $slaver, $task, $batch = null)
    {
        $slaver = new slaver($slaver->ip, $slaver->port);
        return $slaver->assignTask($jobId, $task, $batch);
    }
    
    // 设置服务器
    public function setServer($server)
    {
        $this->server = $server;
    }
    
    // 运行
    public function run()
    {
        log::write('p-' . $this->getName(), 'run');
        $this->pid = $this->process->start();
        $this->process->name('php:' . $this->getName());
    }
    
    // 获取pid
    public function getPid()
    {
        return $this->pid;
    }
}

