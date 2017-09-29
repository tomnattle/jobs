<?php
namespace server;

interface Iserver
{
    // loadconfig
    public function loadConfig();
    // 初始化
    public function init();
    // 启动
    public function startUp();
    // 关闭
    public function shutDown($template);
    // 暂停
    public function pause();
    // 设置类型
    public function setType();
    // 设置handel
    public function setHandle();
    // 检查
    public function check();
}