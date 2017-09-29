<?php
namespace event;

// 事件接口
interface Ievent
{
    // 获取名称
    function getName();
    // 运行
    function run($data, $source = null);
    // 设置源
    function setSource($source);
    // 获取源
    function getSource();
}
