<?php
namespace environment;

// 环境相关的类
interface Ienvironment
{

    public function init();

    public function setConfig($config);

    public function getConfig();

    public function buildDir();

    public function clearDir($_dir = null);

    public function check();
}