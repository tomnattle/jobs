<?php
namespace server;

interface Islaver extends Iserver
{
    // 注册
    public function register($master_id);
    // 加载executer
    public function loadExecuter($executer);
    // 任务
    public function onTask($data);
}

