<?php
namespace executer;

interface Iexecuter
{
    // 获取名称
    public function getName();
    // 配置信息// 
    public function config($data);
    // 设置代理
    public function setProx($prox);
    // 设置任务管理器
    public function setTaskManager($taskManager);
    // 设置私服管理器
    public function setSlaverManafer($slaverManager);
    // 加载配置
    public function loadSysConfig();
    // 运行前
    public function beforeRun();
    // 运行
    public function run();
    // 运行后
    public function afterRun();
    // 保存结果
    public function saveResult();
}

