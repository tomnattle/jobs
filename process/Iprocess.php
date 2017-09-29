<?php
namespace process;

interface Iprocess
{
    
    // 引用服务器
    public function setServer($server);
    // 获取名称
    public function getName();
    // 设置服务器管理
    public function getSlaverManager();
    // 设置活动管理器
    public function getTaskManager();
    // 分派工作
    public function assignJob($jobId, $slaver, $task);
    // 运行
    public function run();
    // 进程入口
    public function process();

    public function getPid();
}

