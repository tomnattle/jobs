<?php
namespace server;

use command\command;

interface Imaster extends Iserver
{
    
    // 处理注册
    public function registerSlaver(command $cmd, $server);
    // 注册为备用机
    public function registerStandby(command $cmd, $server);
    // 同步信息
    public function sync();
    // 添加进程
    public function addProcess($process);
    // 移除进程
    public function removeProcess($process);
    // 列出处理器
    public function listProcess();
    // 处理器检查
    public function checkProcess();
}

